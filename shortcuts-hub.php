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
require_once SHORTCUTS_HUB_PATH . 'includes/auth.php'; // Authorization & token management
require_once SHORTCUTS_HUB_PATH . 'includes/admin-page.php'; // Admin page logic
require_once SHORTCUTS_HUB_PATH . 'includes/security.php'; // Security & nonce
require_once SHORTCUTS_HUB_PATH . 'includes/sb-integration.php'; // Integration with WP Shortcuts post type