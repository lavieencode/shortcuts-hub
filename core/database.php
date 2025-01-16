<?php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

require_once SHORTCUTS_HUB_PATH . 'sh-debug.php';

// Function to log shortcut downloads with enhanced data
function log_download($shortcut_name, $version_data, $download_url) {
    global $wpdb;
    
    $user_id = get_current_user_id();
    $ip_address = $_SERVER['REMOTE_ADDR'];
    
    $data = array(
        'user_id' => $user_id,
        'shortcut_name' => $shortcut_name,
        'download_url' => $download_url,
        'ip_address' => $ip_address,
        'download_date' => current_time('mysql')
    );
    
    if (is_array($version_data)) {
        $data = array_merge($data, $version_data);
    }
    
    $table_name = $wpdb->prefix . 'shortcutshub_downloads';
    $result = $wpdb->insert($table_name, $data);
    
    if ($result === false) {
        sh_debug_log('Download Log Error', array(
            'message' => 'Failed to log download',
            'source' => array(
                'file' => __FILE__,
                'line' => __LINE__,
                'function' => __FUNCTION__
            ),
            'data' => array(
                'error' => $wpdb->last_error,
                'data' => $data
            ),
            'debug' => true
        ));
        return false;
    }
    
    return $wpdb->insert_id;
}

// Enhanced function to get user's download history
function get_downloads($user_id = null) {
    global $wpdb;
    
    if (!$user_id) {
        $user_id = get_current_user_id();
    }
    
    $table_name = $wpdb->prefix . 'shortcutshub_downloads';
    $query = $wpdb->prepare(
        "SELECT * FROM $table_name WHERE user_id = %d ORDER BY download_date DESC",
        $user_id
    );
    
    $results = $wpdb->get_results($query);
    
    if ($wpdb->last_error) {
        sh_debug_log('Download History Error', array(
            'message' => 'Failed to retrieve download history',
            'source' => array(
                'file' => __FILE__,
                'line' => __LINE__,
                'function' => __FUNCTION__
            ),
            'data' => array(
                'error' => $wpdb->last_error,
                'user_id' => $user_id
            ),
            'debug' => true
        ));
        return false;
    }
    
    return $results;
}

// AJAX handler for logging downloads
function ajax_log_download() {
    check_ajax_referer('shortcuts_hub_download', 'nonce');
    
    $shortcut_name = isset($_POST['shortcut_name']) ? sanitize_text_field($_POST['shortcut_name']) : '';
    $version_data = isset($_POST['version_data']) ? $_POST['version_data'] : array();
    $download_url = isset($_POST['download_url']) ? esc_url_raw($_POST['download_url']) : '';
    
    if (empty($shortcut_name) || empty($download_url)) {
        wp_send_json_error('Missing required data');
        return;
    }
    
    $result = log_download($shortcut_name, $version_data, $download_url);
    
    if ($result) {
        wp_send_json_success(array('download_id' => $result));
    } else {
        wp_send_json_error('Failed to log download');
    }
}
add_action('wp_ajax_ajax_log_download', 'ajax_log_download');

?>
