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
            // Parse the source info from JavaScript
            $file = isset($source_info['file']) ? basename($source_info['file']) : 'unknown';
            
            // The file might contain a line number already (e.g. "file.js:66")
            if (strpos($file, ':') !== false) {
                list($file, $line) = explode(':', $file);
            } else {
                $line = $source_info['line'] ?? '';
            }
            
            $function = $source_info['function'] ?? '';
            
            // Format source line to match JavaScript output
            if ($function) {
                $source_line = sprintf("[SOURCE] %s:%s [%s]\n", $file, $line, $function);
            } else {
                $source_line = sprintf("[SOURCE] %s:%s\n", $file, $line);
            }
        }
        
        // Handle data - remove debug flags and source info before logging
        $log_data = $data;
        unset($log_data['debug'], $log_data['source']);
        $log_entry = json_encode($log_data, JSON_PRETTY_PRINT);
        
        // If this is a session start, add the header
        if (isset($source_info['header'])) {
            $asterisks = str_repeat('*', 116);
            $log_entry = $asterisks . "\n" . str_pad($source_info['header'], 116, ' ', STR_PAD_BOTH) . "\n" . $asterisks . "\n\n" . $log_entry;
        }
        
        $content = sprintf("[DEBUG] %s\n%s%s\n\n", $message, $source_line, $log_entry);
    } else {
        $content = sprintf("[DEBUG] %s\n\n", $message);
    }

    // Try to write to our debug log file
    $write_success = @file_put_contents($debug_file, $content, FILE_APPEND);
    
    // Log success or failure to WordPress debug log if WP_DEBUG is enabled
    if (defined('WP_DEBUG') && WP_DEBUG) {
        if ($write_success !== false) {
            error_log(sprintf('[DEBUG] Successfully wrote log to %s: %s', basename($debug_file), $message));
        } else {
            error_log(sprintf('[DEBUG] Failed to write log to %s: %s', basename($debug_file), $message));
        }
    }
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

    // Enable for development
    if (defined('WP_DEBUG') && WP_DEBUG) {
        $is_checking = false;
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
        // Removed error_log call
    }
    wp_send_json_success();
}

function sh_debug_log_ajax_handler() {
    check_ajax_referer('debug_log_nonce', 'security');

    $message = isset($_POST['message']) ? sanitize_text_field($_POST['message']) : '';
    $data = isset($_POST['data']) ? json_decode(stripslashes($_POST['data']), true) : null;
    $source = isset($_POST['source']) ? json_decode(stripslashes($_POST['source']), true) : null;

    if (empty($message)) {
        error_log('[DEBUG] sh_debug_log_ajax_handler failed: No message provided in POST data');
        wp_send_json_error('No message provided');
        return;
    }

    // Validate data format
    if (is_array($data) && isset($data['message']) && isset($data['source']) && isset($data['data'])) {
        error_log('[DEBUG] sh_debug_log_ajax_handler failed: Incorrect data format - received combined object instead of separate message, data, source parameters');
        wp_send_json_error('Invalid data format');
        return;
    }

    // Validate source format
    if ($source !== null) {
        if (!is_array($source)) {
            error_log('[DEBUG] sh_debug_log_ajax_handler failed: Source must be an object with file and function');
            wp_send_json_error('Invalid source format');
            return;
        }

        if (!isset($source['file']) || !isset($source['line'])) {
            error_log('[DEBUG] sh_debug_log_ajax_handler failed: Source must contain file and line');
            wp_send_json_error('Invalid source data');
            return;
        }
    }

    // Call the debug logging function
    sh_debug_log($message, array_merge(
        (array)$data,
        array(
            'source' => $source,
            'debug' => true
        )
    ));

    wp_send_json_success();
}