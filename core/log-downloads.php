<?php

if (!defined('ABSPATH')) {
    exit;
}

function log_shortcut_download($shortcut_name, $version, $version_url) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'shortcut_downloads';

    $ip_address = $_SERVER['REMOTE_ADDR'];
    $data = [
        'ip_address' => $ip_address,
        'shortcut_name' => $shortcut_name,
        'version' => $version,
        'version_url' => $version_url,
    ];

    // Log the data object
    error_log('Logging download data: ' . print_r($data, true));

    $wpdb->insert($table_name, $data);
}
