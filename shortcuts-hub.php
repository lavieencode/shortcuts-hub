<?php
/*
Plugin Name: Shortcuts Hub
Description: Manage and display all Apple Shortcuts via SwitchBlade API.
Version: 1.0
Author: Nicole Archambault
*/

if (!defined('ABSPATH')) {
    exit;
}

define('SHORTCUTS_HUB_PATH', plugin_dir_path(__FILE__));

// Load the main plugin class
require_once SHORTCUTS_HUB_PATH . 'includes/class-shortcuts-hub.php';

// Initialize the plugin
function shortcuts_hub_init() {
    return ShortcutsHub\Shortcuts_Hub::instance();
}

// Start the plugin
add_action('plugins_loaded', 'shortcuts_hub_init');

// Register activation/deactivation hooks
register_activation_hook(__FILE__, [shortcuts_hub_init(), 'activate']);
register_deactivation_hook(__FILE__, [shortcuts_hub_init(), 'deactivate']);

// Load core files
require_once SHORTCUTS_HUB_PATH . 'core/database.php';
require_once SHORTCUTS_HUB_PATH . 'includes/security.php';
require_once SHORTCUTS_HUB_PATH . 'core/enqueue-core.php';
require_once SHORTCUTS_HUB_PATH . 'includes/enqueue-assets.php';
require_once SHORTCUTS_HUB_PATH . 'includes/ajax/shortcuts-ajax.php';
require_once SHORTCUTS_HUB_PATH . 'includes/ajax/versions-ajax.php';
require_once SHORTCUTS_HUB_PATH . 'includes/sb-api.php';
require_once SHORTCUTS_HUB_PATH . 'includes/auth.php';
require_once SHORTCUTS_HUB_PATH . 'includes/pages/shortcuts-list-page.php';
require_once SHORTCUTS_HUB_PATH . 'includes/pages/add-shortcut-page.php';
require_once SHORTCUTS_HUB_PATH . 'includes/pages/edit-shortcut-page.php';
require_once SHORTCUTS_HUB_PATH . 'includes/pages/add-version-page.php';
require_once SHORTCUTS_HUB_PATH . 'includes/pages/edit-version-page.php';
require_once SHORTCUTS_HUB_PATH . 'includes/pages/settings.php';

function register_shortcuts_post_type() {
    $labels = array(
        'name' => 'Shortcuts',
        'singular_name' => 'Shortcut',
        'menu_name' => 'Shortcuts',
        'add_new' => 'Add New',
        'add_new_item' => 'Add New Shortcut',
        'edit_item' => 'Edit Shortcut',
        'new_item' => 'New Shortcut',
        'view_item' => 'View Shortcut',
        'search_items' => 'Search Shortcuts',
        'not_found' => 'No shortcuts found',
        'not_found_in_trash' => 'No shortcuts found in trash',
        'parent_item_colon' => '',
        'all_items' => 'All Shortcuts'
    );

    $args = array(
        'labels' => $labels,
        'public' => true,
        'publicly_queryable' => true,
        'show_ui' => true,
        'show_in_menu' => false,
        'query_var' => true,
        'rewrite' => array(
            'slug' => 'shortcut',
            'with_front' => false
        ),
        'capability_type' => 'post',
        'has_archive' => true,
        'hierarchical' => false,
        'menu_position' => null,
        'supports' => array('title', 'editor', 'thumbnail')
    );

    register_post_type('shortcut', $args);
}

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
