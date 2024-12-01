<?php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

function install_db() {
    global $wpdb;

    $charset_collate = $wpdb->get_charset_collate();
    $table_name = $wpdb->prefix . 'shortcutshub_downloads';

    // Only create table if it doesn't exist
    if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
        // SQL for creating the downloads table with enhanced fields
        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            shortcut_id varchar(255) NOT NULL,
            post_id bigint(20) NOT NULL,
            post_url text NOT NULL,
            shortcut_name varchar(255) NOT NULL,
            version varchar(50) NOT NULL,
            version_notes text,
            minimum_ios varchar(50),
            minimum_mac varchar(50),
            download_url text NOT NULL,
            ip_address varchar(45) NOT NULL,
            is_required BOOLEAN DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            download_date datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY shortcut_id (shortcut_id),
            KEY post_id (post_id),
            KEY download_date (download_date),
            KEY ip_address (ip_address)
        ) $charset_collate;";

        // Include WordPress upgrade functions
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        // Create/update the table
        dbDelta($sql);

        if ($wpdb->last_error) {
            error_log('Shortcuts Hub DB Installation Error: ' . $wpdb->last_error);
        }

        update_option('shortcuts_hub_db_version', '1.2');
    }
    return true;
}

// Function to log shortcut downloads with enhanced data
function log_download($shortcut_name, $version_data, $download_url) {
    global $wpdb;
    
    try {
        $user_id = get_current_user_id();
        if (!$user_id) {
            return false;
        }

        // Get post ID and URL
        $post_id = get_the_ID();
        $post_url = get_permalink($post_id);

        // Get IP address
        $ip_address = $_SERVER['REMOTE_ADDR'];
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip_address = $_SERVER['HTTP_X_FORWARDED_FOR'];
        }

        $table_name = $wpdb->prefix . 'shortcutshub_downloads';
        
        // Prepare version data
        $version_data = is_array($version_data) ? $version_data : array();
        
        // Insert the download record with enhanced data
        $data = array(
            'user_id' => $user_id,
            'shortcut_id' => isset($version_data['shortcut']['id']) ? $version_data['shortcut']['id'] : '',
            'post_id' => $post_id,
            'post_url' => $post_url,
            'shortcut_name' => sanitize_text_field($shortcut_name),
            'version' => isset($version_data['version']['version']) ? sanitize_text_field($version_data['version']['version']) : '',
            'version_notes' => isset($version_data['version']['notes']) ? sanitize_text_field($version_data['version']['notes']) : '',
            'minimum_ios' => isset($version_data['version']['minimumiOS']) ? sanitize_text_field($version_data['version']['minimumiOS']) : '',
            'minimum_mac' => isset($version_data['version']['minimumMac']) ? sanitize_text_field($version_data['version']['minimumMac']) : '',
            'download_url' => esc_url_raw($download_url),
            'ip_address' => sanitize_text_field($ip_address),
            'is_required' => isset($version_data['version']['required']) ? (bool)$version_data['version']['required'] : false
        );

        $result = $wpdb->insert(
            $table_name,
            $data,
            array('%d', '%s', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d')
        );

        if ($result === false) {
            error_log('Failed to log shortcut download: ' . $wpdb->last_error);
            return false;
        }

        return true;
    } catch (Exception $e) {
        error_log('Error logging shortcut download: ' . $e->getMessage());
        return false;
    }
}

// Function to ensure the downloads table exists
function check_exists() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'shortcutshub_downloads';
    
    if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
        install_db();
    }
}

// Enhanced function to get user's download history
function get_downloads($user_id = null) {
    global $wpdb;
    
    if (!$user_id) {
        $user_id = get_current_user_id();
    }

    if (!$user_id) {
        return array();
    }

    $table_name = $wpdb->prefix . 'shortcutshub_downloads';
    
    try {
        $downloads = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_name WHERE user_id = %d ORDER BY download_date DESC",
            $user_id
        ));

        return $downloads;
    } catch (Exception $e) {
        error_log('Error retrieving download history: ' . $e->getMessage());
        return array();
    }
}

// Register activation hook in your main plugin file
register_activation_hook(SHORTCUTS_HUB_PATH . 'shortcuts-hub.php', 'install_db');

// Also ensure table exists on plugin load
add_action('plugins_loaded', 'check_exists');

add_action('wp_ajax_log_shortcut_download', function() {
    check_ajax_referer('shortcuts_hub_nonce', 'security');

    $shortcut_id = isset($_POST['shortcut_id']) ? sanitize_text_field($_POST['shortcut_id']) : '';
    $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
    $version_data = isset($_POST['version_data']) ? $_POST['version_data'] : [];

    if (empty($shortcut_id) || empty($post_id)) {
        wp_send_json_error(['message' => 'Missing required data']);
        return;
    }

    $result = log_download(
        get_the_title($post_id),
        [
            'shortcut' => ['id' => $shortcut_id],
            'version' => $version_data
        ],
        isset($version_data['url']) ? $version_data['url'] : ''
    );
    
    if (!$result) {
        wp_send_json_error(['message' => 'Failed to log download']);
        return;
    }

    wp_send_json_success(['message' => 'Download logged successfully']);
});

// AJAX handler for logging downloads
function ajax_log_download() {
    // Only verify nonce for logged-in users
    if (is_user_logged_in()) {
        if (!isset($_POST['nonce'])) {
            error_log('[Download Log] No nonce provided for logged-in user');
            wp_send_json_error(['message' => 'No nonce provided for logged-in user']);
            return;
        }

        if (!wp_verify_nonce($_POST['nonce'], 'shortcuts_hub_nonce')) {
            error_log('[Download Log] Invalid nonce');
            wp_send_json_error(['message' => 'Invalid nonce']);
            return;
        }
    }
    
    // Handle both token-based and direct shortcut data logging
    $token = isset($_POST['token']) ? sanitize_text_field($_POST['token']) : '';
    $download_url = isset($_POST['download_url']) ? esc_url_raw($_POST['download_url']) : '';
    $redirect_url = isset($_POST['redirect_url']) ? esc_url_raw($_POST['redirect_url']) : '';

    error_log('[Download Log] Processing request:');
    error_log('[Download Log] - Download URL: ' . $download_url);
    error_log('[Download Log] - Redirect URL: ' . $redirect_url);
    error_log('[Download Log] - Token: ' . $token);

    // Generate a new token for the download
    $new_token = wp_generate_password(12, false);
    set_transient('sh_download_' . $new_token, [
        'download_url' => $download_url,
        'redirect_url' => $redirect_url
    ], HOUR_IN_SECONDS);

    error_log('[Download Log] Generated token: ' . $new_token);
    wp_send_json_success(['token' => $new_token]);
}
add_action('wp_ajax_ajax_log_download', 'ajax_log_download');
