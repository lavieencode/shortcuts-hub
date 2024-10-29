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
require_once SHORTCUTS_HUB_PATH . 'includes/enqueue-assets.php'; // Enqueue scripts/styles
require_once SHORTCUTS_HUB_PATH . 'includes/ajax/shortcuts-ajax.php'; // Shortcuts-related AJAX handlers
require_once SHORTCUTS_HUB_PATH . 'includes/ajax/versions-ajax.php'; // Version-related AJAX handlers
require_once SHORTCUTS_HUB_PATH . 'includes/sb-api.php'; // Switchblade integration and API calls
require_once SHORTCUTS_HUB_PATH . 'includes/auth.php'; // Authorization & token management
require_once SHORTCUTS_HUB_PATH . 'includes/security.php'; // Security & nonce
require_once SHORTCUTS_HUB_PATH . 'includes/pages/shortcuts-list-page.php'; // Shortcuts list page logic
require_once SHORTCUTS_HUB_PATH . 'includes/pages/add-shortcut-page.php'; // Add shortcut page logic
require_once SHORTCUTS_HUB_PATH . 'includes/pages/edit-shortcut-page.php'; // Edit shortcut page logic
require_once SHORTCUTS_HUB_PATH . 'includes/pages/settings.php'; // Settings page logic

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
        'not_found_in_trash'    => 'No Shortcuts found in Trash',
        'archives'              => 'Shortcut archives',
        'attributes'            => 'Shortcuts attributes',
        'featured_image'        => 'Featured image for this Shortcut',
        'set_featured_image'    => 'Set featured image for this Shortcut',
        'remove_featured_image' => 'Remove featured image for this Shortcut',
        'use_featured_image'    => 'Use as featured image for this Shortcut',
        'insert_into_item'      => 'Insert into Shortcut',
        'uploaded_to_this_item' => 'Uploaded to this Shortcut',
        'filter_items_list'     => 'Filter Shortcut list',
        'filter_by_date'        => 'Filter shortcuts by date',
        'items_list_navigation' => 'Shortcuts list navigation',
        'items_list'            => 'Shortcuts list',
        'item_published'        => 'Shortcut published',
        'item_published_privately' => 'Shortcut published privately',
        'item_reverted_to_draft' => 'Shortcut reverted to draft',
        'item_scheduled'        => 'Shortcut scheduled',
        'item_updated'          => 'Shortcut updated',
    );

    $args = array(
        'labels'                => $labels,
        'public'                => true,
        'hierarchical'          => false,
        'exclude_from_search'   => false,
        'publicly_queryable'    => true,
        'show_ui'               => true,
        'show_in_menu'          => false, // Do not show in the admin menu
        'show_in_admin_bar'     => false,
        'show_in_nav_menus'     => false,
        'show_in_rest'          => true,
        'rest_base'             => 'shortcut',
        'menu_icon'             => 'dashicons-admin-post',
        'supports'              => array('title', 'thumbnail', 'custom-fields'),
        'taxonomies'            => array('shortcut-category'),
        'has_archive'           => false,
        'rewrite'               => array('with_front' => false),
        'can_export'            => true,
        'delete_with_user'      => false,
    );

    register_post_type('shortcuts', $args);
}

add_action('init', 'register_shortcuts_post_type');

function shortcuts_hub_admin_menu() {
    // Add top-level menu page (no actual page linked)
    add_menu_page(
        __('Shortcuts Hub', 'shortcuts-hub'), // Page title
        __('Shortcuts Hub', 'shortcuts-hub'), // Menu title
        'manage_options',                     // Capability
        'shortcuts-hub',                      // Menu slug
        '__return_null',                      // No function to display a page
        'dashicons-admin-generic',            // Icon URL
        6                                     // Position
    );

    // Add submenu for Shortcuts List
    add_submenu_page(
        'shortcuts-hub',                      // Parent slug
        __('Shortcuts List', 'shortcuts-hub'),// Page title
        __('Shortcuts List', 'shortcuts-hub'),// Menu title
        'manage_options',                     // Capability
        'shortcuts-list',                     // Menu slug
        'shortcuts_hub_render_shortcuts_list_page' // Function to display the page
    );

    // Add submenu for Add Shortcut
    add_submenu_page(
        'shortcuts-hub',                      // Parent slug
        __('Add Shortcut', 'shortcuts-hub'),  // Page title
        __('Add Shortcut', 'shortcuts-hub'),  // Menu title
        'manage_options',                     // Capability
        'add-shortcut',                       // Menu slug
        'shortcuts_hub_render_add_shortcut_page' // Function to display the page
    );
}

add_action('admin_menu', 'shortcuts_hub_admin_menu');

function shortcuts_hub_admin_body_class($classes) {
    $screen = get_current_screen();
    
    if ($screen && $screen->id === 'shortcuts-hub_page_shortcuts-list') {
        $classes .= ' shortcuts-hub_page_shortcuts-list';
    }
    
    return $classes;
}

add_filter('admin_body_class', 'shortcuts_hub_admin_body_class');
