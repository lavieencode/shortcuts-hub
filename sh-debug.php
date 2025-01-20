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
        $log_entry = '';
        $source_line = '';
        
        // Handle source info - either from source field or data.source
        $source_info = null;
        if (isset($data['source'])) {
            $source_info = $data['source'];
        }
        
        if ($source_info && is_array($source_info)) {
            $file = isset($source_info['file']) ? basename($source_info['file']) : 'unknown';
            $line = $source_info['line'] ?? 'unknown';
            $source_line = sprintf("[SOURCE] %s:%s\n", $file, $line);
        }
        
        // Handle data - remove debug flags and source info before logging
        $log_data = $data;
        unset($log_data['debug'], $log_data['source']);
        $log_entry = json_encode($log_data, JSON_PRETTY_PRINT);
        
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
    
    // Get page from either GET params or POST data (for AJAX)
    $page = isset($_GET['page']) ? $_GET['page'] : 
           (isset($_POST['page']) ? $_POST['page'] : 'not set');
           
    error_log('[Shortcuts Hub Debug] Checking if debug should be enabled');
    error_log('[Shortcuts Hub Debug] Page: ' . $page);

    // Enable for development
    if (defined('WP_DEBUG') && WP_DEBUG) {
        $is_checking = false;
        error_log('[Shortcuts Hub Debug] Debug enabled via WP_DEBUG');
        return true;
    }

    // Only check once per request
    static $should_debug = null;
    if ($should_debug !== null) {
        $is_checking = false;
        return $should_debug;
    }

    // Check if we're on a plugin page
    if (is_admin()) {
        // Enable on our plugin pages
        if (($page && strpos($page, 'shortcuts-hub') === 0) || 
            (isset($_POST['action']) && strpos($_POST['action'], 'sh_') === 0)) {
            $is_checking = false;
            error_log('[Shortcuts Hub Debug] Debug enabled on plugin page');
            return true;
        }
    }
    
    // Enable on single shortcut pages and shortcut archive
    if (is_singular('shortcut') || is_post_type_archive('shortcut')) {
        $is_checking = false;
        error_log('[Shortcuts Hub Debug] Debug enabled on shortcut page');
        return true;
    }
    
    $is_checking = false;
    error_log('[Shortcuts Hub Debug] Debug not enabled');
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
}
add_action('wp_enqueue_scripts', 'sh_enqueue_debug_script');
add_action('admin_enqueue_scripts', 'sh_enqueue_debug_script');

// Always register AJAX handlers
add_action('wp_ajax_sh_debug_log', 'sh_debug_log_ajax_handler');
add_action('wp_ajax_nopriv_sh_debug_log', 'sh_debug_log_ajax_handler');

// Add error log handler
add_action('wp_ajax_sh_error_log', 'sh_error_log_ajax_handler');
add_action('wp_ajax_nopriv_sh_error_log', 'sh_error_log_ajax_handler');

/**
 * Handle error logging from JavaScript
 */
function sh_error_log_ajax_handler() {
    $message = isset($_POST['message']) ? sanitize_text_field($_POST['message']) : '';
    if (!empty($message)) {
        error_log('[Shortcuts Hub Debug] ' . $message);
    }
    wp_send_json_success();
}

function sh_debug_log_ajax_handler() {
    // Only process if this is explicitly a debug log request
    if (!isset($_POST['action']) || $_POST['action'] !== 'sh_debug_log') {
        error_log('[Shortcuts Hub Debug] Invalid action: ' . (isset($_POST['action']) ? $_POST['action'] : 'not set'));
        wp_send_json_error('Invalid action');
        return;
    }

    // Log the received security token
    error_log('[Shortcuts Hub Debug] Received security token: ' . (isset($_POST['security']) ? $_POST['security'] : 'not set'));
    
    // Verify nonce
    if (!isset($_POST['security']) || !wp_verify_nonce($_POST['security'], 'shortcuts_hub_debug_log_nonce')) {
        error_log('[Shortcuts Hub Debug] Nonce verification failed. Received: ' . (isset($_POST['security']) ? $_POST['security'] : 'not set'));
        error_log('[Shortcuts Hub Debug] POST data: ' . print_r($_POST, true));
        wp_send_json_error('Invalid security token');
        return;
    }

    $message = isset($_POST['message']) ? sanitize_text_field($_POST['message']) : '';
    $source = isset($_POST['source']) ? sanitize_text_field($_POST['source']) : 'unknown';
    
    // Handle data
    $data = null;
    if (isset($_POST['data']) && $_POST['data'] !== 'null') {
        $decoded = json_decode(stripslashes($_POST['data']), true);
        if (json_last_error() === JSON_ERROR_NONE) {
            $data = $decoded;
        }
    }

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