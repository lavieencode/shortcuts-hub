<?php
/*
Plugin Name: Shortcuts Hub
Description: Manage and display all Apple Shortcuts via SwitchBlade API.
Version: 1.0
Author: Nicole Archambault
*/

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Define the plugin path
define('SHORTCUTS_HUB_PATH', plugin_dir_path(__FILE__));

// Include separate modular PHP files
require_once SHORTCUTS_HUB_PATH . 'includes/security.php'; // Security & nonce
require_once SHORTCUTS_HUB_PATH . 'core/database.php'; // Database functionality - must be loaded before activation hook
require_once SHORTCUTS_HUB_PATH . 'core/enqueue-core.php'; // Enqueue core functionalities
require_once SHORTCUTS_HUB_PATH . 'includes/enqueue-assets.php'; // Enqueue scripts/styles
require_once SHORTCUTS_HUB_PATH . 'includes/ajax/shortcuts-ajax.php'; // Shortcuts-related AJAX handlers
require_once SHORTCUTS_HUB_PATH . 'includes/ajax/versions-ajax.php'; // Version-related AJAX handlers
require_once SHORTCUTS_HUB_PATH . 'includes/sb-api.php'; // Switchblade integration and API calls
require_once SHORTCUTS_HUB_PATH . 'includes/auth.php'; // Authorization & token management
require_once SHORTCUTS_HUB_PATH . 'includes/pages/shortcuts-list-page.php'; // Shortcuts list page logic
require_once SHORTCUTS_HUB_PATH . 'includes/pages/add-shortcut-page.php'; // Add shortcut page logic
require_once SHORTCUTS_HUB_PATH . 'includes/pages/edit-shortcut-page.php'; // Edit shortcut page logic
require_once SHORTCUTS_HUB_PATH . 'includes/pages/add-version-page.php'; // Add version page logic
require_once SHORTCUTS_HUB_PATH . 'includes/pages/edit-version-page.php'; // Edit version page logic
require_once SHORTCUTS_HUB_PATH . 'includes/pages/settings.php'; // Settings page logic

// Include Elementor integration
if (did_action('elementor/loaded')) {
    require_once SHORTCUTS_HUB_PATH . 'core/elementor-init.php';
}

function register_shortcuts_post_type() {
    $labels = array(
        'name'                  => 'Shortcuts',
        'singular_name'         => 'Shortcut',
        'menu_name'             => 'Shortcuts',
        'all_items'             => 'All Shortcuts',
        'edit_item'             => 'Edit Shortcut',
        'view_item'             => 'View Shortcut',
        'view_items'            => 'View Shortcuts',
        'add_new_item'          => 'Add New Shortcut',
        'add_new'               => 'Add New Shortcut',
        'new_item'              => 'New Shortcut',
        'parent_item_colon'     => 'Parent Shortcut:',
        'search_items'          => 'Search Shortcuts',
        'not_found'             => 'No Shortcuts found',
        'not_found_in_trash'    => 'No Shortcuts found in Trash'
    );

    $args = array(
        'labels'             => $labels,
        'public'             => true,
        'publicly_queryable' => true,
        'show_ui'            => true,
        'show_in_menu'       => true,
        'query_var'          => true,
        'rewrite'            => array(
            'slug' => 'shortcut',
            'with_front' => false
        ),
        'capability_type'    => 'post',
        'has_archive'        => true,
        'hierarchical'       => false,
        'menu_position'      => null,
        'supports'           => array('title', 'editor', 'thumbnail', 'custom-fields')
    );

    register_post_type('shortcut', $args);
}
add_action('init', 'register_shortcuts_post_type', 0);

function register_shortcuts_menu() {
    remove_menu_page('shortcuts-hub');

    add_menu_page(
        'Shortcuts Hub', 
        'Shortcuts Hub', 
        'manage_options', 
        'shortcuts-hub', 
        'shortcuts_hub_render_shortcuts_list_page', 
        'dashicons-list-view', 
        6 
    );

    add_submenu_page(
        'shortcuts-hub', 
        'Shortcuts List', 
        'Shortcuts List', 
        'manage_options', 
        'shortcuts-list', 
        'shortcuts_hub_render_shortcuts_list_page' 
    );

    add_submenu_page(
        'shortcuts-hub', 
        'Add Shortcut', 
        'Add Shortcut', 
        'manage_options', 
        'add-shortcut', 
        'shortcuts_hub_render_add_shortcut_page' 
    );

    add_submenu_page(
        'shortcuts-hub', 
        'Edit Shortcut', 
        'Edit Shortcut', 
        'manage_options', 
        'edit-shortcut', 
        'shortcuts_hub_render_edit_shortcut_page' 
    );

    add_submenu_page(
        'shortcuts-hub', 
        'Add Version', 
        'Add Version', 
        'manage_options', 
        'add-version', 
        'shortcuts_hub_render_add_version_page' 
    );

    add_submenu_page(
        'shortcuts-hub', 
        'Edit Version', 
        'Edit Version', 
        'manage_options', 
        'edit-version', 
        'shortcuts_hub_render_edit_version_page' 
    );

    global $submenu;
    unset($submenu['shortcuts-hub'][0]);
}
add_action('admin_menu', 'register_shortcuts_menu');

function shortcuts_hub_admin_body_class($classes) {
    $screen = get_current_screen();
    
    if ($screen) {
        $page_slug = $screen->id;

        if (strpos($page_slug, 'shortcuts-hub_page_') === 0) {
            $classes .= ' shortcuts-hub_page';

            if ($page_slug === 'shortcuts-hub_page_shortcuts-list') {
                $classes .= ' shortcuts-hub_page_shortcuts-list';
            } elseif ($page_slug === 'shortcuts-hub_page_add-shortcut') {
                $classes .= ' shortcuts-hub_page_add-shortcut';
            } elseif ($page_slug === 'shortcuts-hub_page_edit-shortcut') {
                $classes .= ' shortcuts-hub_page_edit-shortcut';
            } elseif ($page_slug === 'shortcuts-hub_page_shortcuts-settings') {
                $classes .= ' shortcuts-hub_page_shortcuts-settings';
            }
        }
    }
    
    return $classes;
}
add_filter('admin_body_class', 'shortcuts_hub_admin_body_class');

// Register activation hook
register_activation_hook(__FILE__, 'shortcuts_hub_activate');

function shortcuts_hub_activate() {
    flush_rewrite_rules();
}

// Plugin deactivation hook
register_deactivation_hook(__FILE__, 'shortcuts_hub_deactivate');

function shortcuts_hub_deactivate() {
    flush_rewrite_rules();
}

// Add AJAX handlers
add_action('wp_ajax_log_shortcut_download', 'sh_log_shortcut_download');

function sh_log_shortcut_download() {
    if (!check_ajax_referer('shortcut_download', 'nonce', false)) {
        wp_send_json_error('Invalid nonce');
        return;
    }

    $shortcut_id = isset($_POST['shortcut_id']) ? sanitize_text_field($_POST['shortcut_id']) : '';
    $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
    $shortcut_name = isset($_POST['shortcut_name']) ? sanitize_text_field($_POST['shortcut_name']) : '';
    $version = isset($_POST['version']) ? sanitize_text_field($_POST['version']) : '';

    if (empty($shortcut_id) || empty($post_id)) {
        wp_send_json_error('Missing required fields');
        return;
    }

    $user_id = get_current_user_id();
    $logged = log_shortcut_download_to_db($shortcut_id, $post_id, $user_id, $shortcut_name, $version);

    if ($logged) {
        wp_send_json_success('Download logged successfully');
    } else {
        wp_send_json_error('Failed to log download');
    }
}

function log_shortcut_download_to_db($shortcut_id, $post_id, $user_id, $shortcut_name, $version) {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'shortcut_downloads';
    
    $result = $wpdb->insert(
        $table_name,
        array(
            'shortcut_id' => $shortcut_id,
            'post_id' => $post_id,
            'user_id' => $user_id,
            'shortcut_name' => $shortcut_name,
            'version' => $version,
            'download_date' => current_time('mysql'),
            'ip_address' => $_SERVER['REMOTE_ADDR']
        ),
        array(
            '%s',
            '%d',
            '%d',
            '%s',
            '%s',
            '%s',
            '%s'
        )
    );
    
    return $result !== false;
}
