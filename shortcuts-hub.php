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
