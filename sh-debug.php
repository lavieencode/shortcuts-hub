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
        // Format source information if provided
        $source_info = '';
        if (!empty($data['source']) && is_array($data['source'])) {
            $file = isset($data['source']['file']) ? $data['source']['file'] : '';
            $line = isset($data['source']['line']) ? $data['source']['line'] : '';
            $function = isset($data['source']['function']) ? $data['source']['function'] : '';
            
            $source_info = sprintf(
                '[SOURCE] %s%s%s',
                $file,
                $line ? ":$line" : '',  // Only add line number if it exists
                $function ? " [$function]" : ''
            );
        }
        
        // Handle data - remove debug flags and source info before logging
        $log_data = $data;
        unset($log_data['debug'], $log_data['source']);
        $log_entry = json_encode($log_data, JSON_PRETTY_PRINT);
        
        // If this is a session start, add the header
        if (isset($data['source']['header'])) {
            $asterisks = str_repeat('*', 116);
            $log_entry = $asterisks . "\n" . str_pad($data['source']['header'], 116, ' ', STR_PAD_BOTH) . "\n" . $asterisks . "\n\n" . $log_entry;
        }
        
        $content = sprintf("[DEBUG] %s\n%s\n%s\n\n", $message, $source_info, $log_entry);
    } else {
        $content = sprintf("[DEBUG] %s\n\n", $message);
    }

    // Try to write to our debug log file
    $write_success = false;
    $error_message = '';
    
    // Check if file exists and is writable, or if we can create it
    if (file_exists($debug_file)) {
        if (!is_writable($debug_file)) {
            $error_message = sprintf('File exists but is not writable. Current permissions: %s, Owner: %s, Group: %s', 
                substr(sprintf('%o', fileperms($debug_file)), -4),
                posix_getpwuid(fileowner($debug_file))['name'],
                posix_getgrgid(filegroup($debug_file))['name']
            );
        }
    } else {
        // Try to create the file
        $create_result = @file_put_contents($debug_file, '');
        if ($create_result === false) {
            $error_message = sprintf('Cannot create file. Directory permissions: %s, Owner: %s, Group: %s', 
                substr(sprintf('%o', fileperms(dirname($debug_file))), -4),
                posix_getpwuid(fileowner(dirname($debug_file)))['name'],
                posix_getgrgid(filegroup(dirname($debug_file)))['name']
            );
        }
    }
    
    // If no error so far, try to write
    if (empty($error_message)) {
        $write_success = @file_put_contents($debug_file, $content, FILE_APPEND);
        if ($write_success === false) {
            $error_message = sprintf('Write failed. File permissions: %s, Owner: %s, Group: %s', 
                substr(sprintf('%o', fileperms($debug_file)), -4),
                posix_getpwuid(fileowner($debug_file))['name'],
                posix_getgrgid(filegroup($debug_file))['name']
            );
        }
    }
    
    // Log success or failure to WordPress debug log if WP_DEBUG is enabled
    if (defined('WP_DEBUG') && WP_DEBUG) {
        if ($write_success !== false) {
            error_log(sprintf('[DEBUG] Successfully wrote log to %s: %s', basename($debug_file), $message));
        } else {
            $error_reason = file_exists($debug_file) ? 
                sprintf('Permissions: %s Owner: %s', substr(sprintf('%o', fileperms($debug_file)), -4), posix_getpwuid(fileowner($debug_file))['name']) :
                sprintf('Directory permissions: %s Owner: %s', substr(sprintf('%o', fileperms(dirname($debug_file))), -4), posix_getpwuid(fileowner(dirname($debug_file)))['name']);
            error_log(sprintf('[DEBUG] Failed to write log to %s: %s (%s)', basename($debug_file), $message, $error_reason));
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

    // We no longer need to validate the data format here since the JavaScript side now handles both formats

    // Call the debug logging function with the correct format
    // Add source information to the data array to match the PHP function signature
    $log_data = is_array($data) ? $data : array();
    
    // If we have source information, add it to the data array
    if (!empty($source) && is_array($source)) {
        $log_data['source'] = $source;
    }
    
    sh_debug_log($message, $log_data);

    wp_send_json_success();
}