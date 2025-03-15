<?php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

require_once SHORTCUTS_HUB_PATH . 'sh-debug.php';

/**
 * Creates the downloads table if it doesn't exist
 * 
 * @return bool True if table exists or was created successfully, false otherwise
 */
function create_downloads_table() {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'shortcutshub_downloads';
    
    // Check if table already exists
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
    
    if ($table_exists) {
        return true; // Table already exists, no need to create it
    }
    
    // Table doesn't exist, create it
    $charset_collate = $wpdb->get_charset_collate();
    
    $sql = "CREATE TABLE $table_name (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        user_id bigint(20) NOT NULL,
        shortcut_id bigint(20) NULL,
        shortcut_name varchar(255) NOT NULL,
        version varchar(50) NULL,
        download_url text NULL,
        post_url text NULL,
        post_id bigint(20) NULL,
        ip_address varchar(100) NULL,
        download_date datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY  (id),
        KEY user_id (user_id),
        KEY shortcut_id (shortcut_id)
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    $result = dbDelta($sql);
    
    // Verify table exists after creation attempt
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
    
    if (!$table_exists) {
        sh_debug_log('Download Table Creation Error', array(
            'message' => 'Failed to create downloads table',
            'source' => array(
                'file' => __FILE__,
                'line' => __LINE__,
                'function' => __FUNCTION__
            ),
            'data' => array(
                'error' => $wpdb->last_error,
                'result' => $result
            ),
            'debug' => true
        ));
        return false;
    }
    
    return true;
}

// Initialize the downloads table
add_action('plugins_loaded', function() {
    // Only try to create the table if we're not in the middle of an AJAX request
    if (!defined('DOING_AJAX') || !DOING_AJAX) {
        create_downloads_table();
    }
}, 20); // Priority 20 to ensure it runs after the main plugin initialization

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
    try {
        $result = $wpdb->insert($table_name, $data);
    } catch (Exception $e) {
        // Log the error
        sh_debug_log('Download Log Error', array(
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ));
        return false;
    }
    
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
    
    try {
        $results = $wpdb->get_results($query);
    } catch (Exception $e) {
        // Log the error
        sh_debug_log('Download History Error', array(
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ));
        return false;
    }
    
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
    check_ajax_referer('download_nonce', 'nonce');
    
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
