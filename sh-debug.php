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

    try {
        $session_started = get_transient('sh_debug_session_started');
        $debug_file = dirname(__FILE__) . '/sh-debug.log';
        
        // Check if file exists and is writable, if not create it
        if (!file_exists($debug_file)) {
            touch($debug_file);
            chmod($debug_file, 0666);
            delete_transient('sh_debug_session_started');
            $session_started = false;
        }
        
        if (!is_writable($debug_file)) {
            error_log("Debug file not writable: " . $debug_file);
            return;
        }

        if (!$session_started) {
            // Log the start block first
            $start_message = str_repeat('*', 116) . "\n";
            $start_message .= str_pad("[START DEBUG LOG: " . date('Y-m-d h:i:s A T') . "]", 116, ' ', STR_PAD_BOTH) . "\n";
            $start_message .= str_repeat('*', 116) . "\n\n";
            
            file_put_contents($debug_file, $start_message, FILE_APPEND);
            
            set_transient('sh_debug_session_started', true, HOUR_IN_SECONDS);
        }
        
        // Format source information if available
        $source_info = '';
        if (is_array($data) && isset($data['source'])) {
            $source = $data['source'];
            $source_info = sprintf(
                "[%s:%d in %s]",
                basename($source['file']),
                $source['line'],
                $source['function']
            );
            // Remove source from data to avoid duplication
            unset($data['source']);
        }
        
        // Now log the actual message
        $log_message = "[DEBUG] " . $message . "\n";
        if ($source_info) {
            $log_message .= "SOURCE: " . $source_info . "\n\n";
        }
        if ($data !== null) {
            $log_message .= json_encode($data, JSON_PRETTY_PRINT) . "\n";
        }
        $log_message .= "\n";
        
        file_put_contents($debug_file, $log_message, FILE_APPEND);
        
    } catch (Exception $e) {
        error_log("Error writing to debug log: " . $e->getMessage());
    }
}

function sh_error_log($message, $file = '', $line = '') {
    if (!should_enable_debug()) {
        return;
    }

    $debug_file = dirname(__FILE__) . '/sh-debug.log';
    $error_message = "[ERROR] ";
    if ($file && $line) {
        $error_message .= "[$file:$line] ";
    }
    $error_message .= $message . "\n\n";
    file_put_contents($debug_file, $error_message, FILE_APPEND);
}

function should_enable_debug() {
    // Always enable debugging for now
    return true;
    
    // Original conditions below:
    /*
    // Always enable for AJAX debug logging
    if (defined('DOING_AJAX') && DOING_AJAX && isset($_REQUEST['action'])) {
        if (strpos($_REQUEST['action'], 'sh_') === 0 || strpos($_REQUEST['action'], 'shortcuts_') === 0) {
            return true;
        }
    }
    
    // Enable on Shortcuts Hub admin pages
    if (is_admin() && isset($_GET['page']) && strpos($_GET['page'], 'shortcuts') === 0) {
        return true;
    }
    
    // Enable on single shortcut pages and shortcut archive
    if (is_singular('shortcut') || is_post_type_archive('shortcut')) {
        return true;
    }
    
    return false;
    */
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