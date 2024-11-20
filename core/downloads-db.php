<?php

if (!defined('ABSPATH')) {
    exit;
}

function create_downloads_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'shortcut_downloads';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        time datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        ip_address varchar(100) DEFAULT '' NOT NULL,
        shortcut_name varchar(255) NOT NULL,
        version varchar(50) NOT NULL,
        version_url varchar(255) NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

register_activation_hook(__FILE__, 'create_downloads_table');
