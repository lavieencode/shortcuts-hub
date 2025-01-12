<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Debug logging function that writes to both console and file
 * 
 * @param string $message The debug message describing what's happening
 * @param mixed $data Optional data to log with the message
 * @param string $source Optional source information for the log entry
 * @return void
 */
function sh_debug_log($message, $data = null, $source = null) {
    $log_file = dirname(__FILE__) . '/debug-log.php';
    
    // Format the message line
    if ($source === 'session-start') {
        date_default_timezone_set('America/New_York');
        $datetime = date('Y-m-d h:i:s A');
        $asterisks = str_repeat('*', 116);
        $datetime_line = str_pad("[START DEBUG LOG: " . $datetime . " EST]", 116, " ", STR_PAD_BOTH);
        $log_entry = "\n\n" . $asterisks . "\n" . $datetime_line . "\n" . $asterisks . "\n\n";
    } else {
        $log_entry = "[DEBUG] $message\n";
        if ($source) {
            $log_entry .= "[SOURCE] $source\n";
        }
    }

    if ($data !== null) {
        $json_flags = JSON_PRETTY_PRINT;
        if (is_string($data)) {
            // If data is a string, try to decode it first
            $decoded = json_decode($data);
            if ($decoded !== null) {
                $data = $decoded;
            }
        }
        
        if (is_array($data) || is_object($data)) {
            $formatted_data = json_encode($data, $json_flags);
            // Make arrays single line but keep overall structure pretty
            $formatted_data = preg_replace('/\[\s+(.+?)\s+\]/', '[$1]', $formatted_data);
            $log_entry .= $formatted_data . "\n";
        } else {
            $log_entry .= $data . "\n";
        }
    }

    $log_entry .= "\n";

    // Ensure the log file exists and is writable
    if (!file_exists($log_file)) {
        file_put_contents($log_file, "<?php\n// This file is for debug logging only\n// Do not include any PHP code here\n?>\n");
        chmod($log_file, 0666);
    }

    // Append the log entry
    file_put_contents($log_file, $log_entry, FILE_APPEND);
}

function should_enable_debug() {
    // Always enable for AJAX debug logging
    if (defined('DOING_AJAX') && DOING_AJAX && isset($_REQUEST['action'])) {
        if ($_REQUEST['action'] === 'sh_debug_log') {
            return true;
        }
    }
    
    // Enable on shop page
    if (function_exists('is_shop') && is_shop()) {
        return true;
    }
    
    return false;
}

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

    sh_debug_log($message, $data, $source);
    wp_send_json_success();
}