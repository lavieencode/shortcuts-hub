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
            $log_entry .= "\n" . $formatted_data;
        } else {
            $log_entry .= "\n" . $data;
        }
    }

    $log_entry .= "\n\n";
    
    // Write to debug-log.php
    $log_file = dirname(__FILE__) . '/debug-log.php';
    
    // Create the file with PHP header if it doesn't exist
    if (!file_exists($log_file)) {
        file_put_contents($log_file, "<?php if (!defined('ABSPATH')) { exit; } ?>\n");
    }
    
    // Append the log entry
    file_put_contents($log_file, $log_entry, FILE_APPEND);

    // Output to console if it's an AJAX request
    if (wp_doing_ajax()) {
        wp_send_json_success([
            'message' => $message,
            'data' => $data
        ]);
    }
}

// Add Ajax action for JavaScript debug logging
add_action('wp_ajax_sh_debug_log', 'sh_debug_log_ajax_handler');
function sh_debug_log_ajax_handler() {
    check_ajax_referer('shortcuts_hub_nonce', 'security');

    $message = isset($_POST['message']) ? sanitize_text_field($_POST['message']) : '';
    $data = isset($_POST['data']) ? json_decode(stripslashes($_POST['data']), true) : null;
    $source = isset($_POST['source']) ? sanitize_text_field($_POST['source']) : 'unknown';

    sh_debug_log($message, $data, $source);
    wp_send_json_success();
}