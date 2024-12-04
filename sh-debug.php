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
        $message_line = "\n\n" . $asterisks . "\n" . $datetime_line . "\n" . $asterisks . "\n\n";
    } else {
        $message_line = sprintf("[DEBUG] %s\n%s\n", $message, $source ?: 'unknown');
    }

    // Format the data if present
    $data_section = '';
    if ($data !== null) {
        // Format JSON with indentation but keep arrays compact
        $json_flags = JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES;
        $formatted_data = json_encode($data, $json_flags);
        // Make arrays single line but keep overall structure pretty
        $formatted_data = preg_replace('/\[\s+(.+?)\s+\]/', '[$1]', $formatted_data);
        $data_section = $formatted_data . "\n";
    }

    // Combine message and data
    $log_entry = $message_line . $data_section;

    // Write to debug-log.php
    $log_file = dirname(__FILE__) . '/debug-log.php';
    
    // Create the file with PHP header if it doesn't exist
    if (!file_exists($log_file)) {
        file_put_contents($log_file, "<?php if (!defined('ABSPATH')) { exit; } ?>\n");
    }
    
    // Append the log entry
    file_put_contents(
        $log_file, 
        $log_entry . "\n",
        FILE_APPEND
    );

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
    // Debug: Verify nonce to ensure request security
    check_ajax_referer('shortcuts_hub_nonce', 'security');

    // Debug: Validate and sanitize incoming message and data
    $message = isset($_POST['message']) ? sanitize_text_field($_POST['message']) : '';
    $data = isset($_POST['data']) ? json_decode(stripslashes($_POST['data']), true) : null;
    $source = isset($_POST['source']) ? sanitize_text_field($_POST['source']) : 'unknown';

    // Debug: Log the sanitized message and data to verify proper formatting and content
    sh_debug_log($message, $data, $source);
    wp_send_json_success();
}