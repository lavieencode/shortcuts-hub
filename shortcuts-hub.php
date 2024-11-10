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
        'show_in_menu'          => true,
        'show_in_admin_bar'     => true,
        'show_in_nav_menus'     => true,
        'show_in_rest'          => true,
        'rest_base'             => 'shortcut',
        'menu_icon'             => 'dashicons-admin-post',
        'supports'              => array('title', 'editor', 'thumbnail', 'custom-fields'),
        'taxonomies'            => array('shortcut-category'),
        'has_archive'           => true,
        'rewrite'               => array('with_front' => false),
        'can_export'            => true,
        'delete_with_user'      => false,
    );

    register_post_type('shortcut', $args);
}
add_action('init', 'register_shortcuts_post_type');

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

