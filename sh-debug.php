<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Debug logging function that writes to both console and file
 * 
 * @param string $message The debug message describing what's happening
 * @param mixed $data Optional data to log with the message
 * @return void
 */
function sh_debug_log($message, $data = null) {
    if (!should_enable_debug()) {
        return;
    }

    // Skip if debug is explicitly set to false in data
    if (is_array($data) && isset($data['debug']) && $data['debug'] === false) {
        return;
    }

    $debug_file = dirname(__FILE__) . '/sh-debug.log';
    
    if (is_array($data)) {
        // Format source information if available
        $log_entry = json_encode($data['data'] ?? $data, JSON_PRETTY_PRINT);
        $source_line = '';
        if (isset($data['source'])) {
            $source = $data['source'];
            $file = basename($source['file']);
            $source_line = sprintf("[SOURCE] %s:%d\n", $file, $source['line']);
            unset($data['source'], $data['debug']);
        }
        
        $content = sprintf("[DEBUG] %s\n%s%s\n\n", $message, $source_line, $log_entry);
    } else {
        $content = sprintf("[DEBUG] %s\n\n", $message);
    }

    file_put_contents($debug_file, $content, FILE_APPEND);
}

function should_enable_debug() {
    static $is_checking = false;
    
    // Prevent infinite recursion
    if ($is_checking) {
        return true;
    }
    
    $is_checking = true;
    
    // Always enable for plugin initialization
    if (did_action('plugins_loaded') <= 1) {
        $is_checking = false;
        return true;
    }
    
    // Always enable in Elementor contexts
    if (
        // Check if Elementor is active
        defined('ELEMENTOR_VERSION') ||
        // Check if we're in any Elementor action
        did_action('elementor/loaded') ||
        did_action('elementor/init') ||
        did_action('elementor/dynamic_tags/register') ||
        // Check if we're in Elementor's editor
        (isset($_GET['action']) && $_GET['action'] === 'elementor') ||
        (isset($_POST['action']) && $_POST['action'] === 'elementor_ajax') ||
        // Check if we're in preview mode
        (isset($_GET['elementor-preview']))
    ) {
        $is_checking = false;
        return true;
    }

    // Check if we're in an AJAX request
    if (defined('DOING_AJAX') && DOING_AJAX && isset($_REQUEST['action'])) {
        $action = sanitize_text_field($_REQUEST['action']);
        if (
            strpos($action, 'sh_') === 0 || 
            strpos($action, 'shortcuts_') === 0 ||
            strpos($action, 'elementor') === 0
        ) {
            $is_checking = false;
            return true;
        }
    }
    
    // For non-AJAX requests, check if we're in the admin area
    if (is_admin()) {
        // Enable on plugins page
        global $pagenow;
        if ($pagenow === 'plugins.php') {
            $is_checking = false;
            return true;
        }

        // Enable on our plugin pages
        if (isset($_GET['page']) && strpos($_GET['page'], 'shortcuts-hub') === 0) {
            $is_checking = false;
            return true;
        }
    }
    
    // Enable on single shortcut pages and shortcut archive
    if (is_singular('shortcut') || is_post_type_archive('shortcut')) {
        $is_checking = false;
        return true;
    }
    
    $is_checking = false;
    return false;
}

// Enqueue debug script
function sh_enqueue_debug_script() {
    if (!should_enable_debug()) {
        return;
    }

    wp_enqueue_script(
        'sh-debug',
        plugins_url('assets/js/sh-debug.js', __FILE__),
        array('jquery'),
        filemtime(plugin_dir_path(__FILE__) . 'assets/js/sh-debug.js'),
        true
    );

    wp_localize_script('sh-debug', 'shDebugData', array(
        'ajaxurl' => admin_url('admin-ajax.php'),
        'security' => wp_create_nonce('shortcuts_hub_nonce')
    ));
}
add_action('wp_enqueue_scripts', 'sh_enqueue_debug_script');
add_action('admin_enqueue_scripts', 'sh_enqueue_debug_script');

// Always register AJAX handlers
add_action('wp_ajax_sh_debug_log', 'sh_debug_log_ajax_handler');
add_action('wp_ajax_nopriv_sh_debug_log', 'sh_debug_log_ajax_handler');

function sh_debug_log_ajax_handler() {
    // Only process if this is explicitly a debug log request
    if (!isset($_POST['action']) || $_POST['action'] !== 'sh_debug_log') {
        wp_send_json_error('Invalid action');
        return;
    }

    // Verify nonce
    if (!isset($_POST['security']) || !wp_verify_nonce($_POST['security'], 'shortcuts_hub_nonce')) {
        wp_send_json_error('Invalid security token');
        return;
    }

    $message = isset($_POST['message']) ? sanitize_text_field($_POST['message']) : '';
    $data = isset($_POST['data']) ? $_POST['data'] : null;
    if ($data !== null) {
        $data = is_string($data) ? json_decode(stripslashes($data), true) : $data;
    }
    $source = isset($_POST['source']) ? sanitize_text_field($_POST['source']) : 'unknown';

    // If this is a session start request, ensure we start a new session
    if ($source === 'session-start') {
        delete_transient('sh_debug_session_started');
    }

    // Only log if there's actually a message
    if (!empty($message)) {
        sh_debug_log($message, $data);
    }
    wp_send_json_success();
}