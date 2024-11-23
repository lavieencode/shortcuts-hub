<?php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

function shortcuts_hub_install_db() {
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

        // Log any errors
        if ($wpdb->last_error) {
            error_log('Shortcuts Hub DB Installation Error: ' . $wpdb->last_error);
            return false;
        }

        update_option('shortcuts_hub_db_version', '1.2');
    }
    return true;
}

// Function to log shortcut downloads with enhanced data
function log_shortcut_download($shortcut_name, $version_data, $download_url) {
    global $wpdb;
    
    try {
        $user_id = get_current_user_id();
        if (!$user_id) {
            error_log('Attempted to log download for non-logged-in user');
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
            'version' => sanitize_text_field($version_data['version'] ?? ''),
            'version_notes' => sanitize_text_field($version_data['notes'] ?? ''),
            'minimum_ios' => sanitize_text_field($version_data['minimumiOS'] ?? ''),
            'minimum_mac' => sanitize_text_field($version_data['minimumMac'] ?? ''),
            'download_url' => esc_url_raw($download_url),
            'ip_address' => sanitize_text_field($ip_address),
            'is_required' => isset($version_data['required']) ? (bool)$version_data['required'] : false
        );

        // Log the complete download data for debugging
        error_log('Complete download data: ' . print_r($data, true));
        
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
function ensure_downloads_table_exists() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'shortcutshub_downloads';
    
    if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
        shortcuts_hub_install_db();
    }
}

// Enhanced function to get user's download history
function get_user_downloads($user_id = null) {
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

        // Log the download history for the specified user
        error_log(sprintf('Download history for user %d:', $user_id));
        error_log(print_r($downloads, true));

        return $downloads;
    } catch (Exception $e) {
        error_log('Error retrieving download history: ' . $e->getMessage());
        return array();
    }
}

// Register activation hook in your main plugin file
register_activation_hook(SHORTCUTS_HUB_PATH . 'shortcuts-hub.php', 'shortcuts_hub_install_db');

// Also ensure table exists on plugin load
add_action('plugins_loaded', 'ensure_downloads_table_exists');
