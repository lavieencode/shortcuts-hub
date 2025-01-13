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

define('SHORTCUTS_HUB_FILE', __FILE__);
define('SHORTCUTS_HUB_PATH', plugin_dir_path(__FILE__));
define('SHORTCUTS_HUB_VERSION', '1.0.0');

// Only include the main class file - it will handle all other dependencies
require_once SHORTCUTS_HUB_PATH . 'includes/class-shortcuts-hub.php';

/**
 * Main plugin instance
 */
function shortcuts_hub() {
    return ShortcutsHub\Shortcuts_Hub::get_instance();
}

/**
 * Activation handler
 */
register_activation_hook(__FILE__, function() {
    // Use singleton instance but don't initialize
    $instance = shortcuts_hub();
    $instance->activate();
});

/**
 * Deactivation handler
 */
register_deactivation_hook(__FILE__, function() {
    // Use singleton instance but don't initialize
    $instance = shortcuts_hub();
    $instance->deactivate();
});

// Hook into WordPress initialization - but AFTER activation is complete
add_action('plugins_loaded', 'shortcuts_hub_init', 10);

/**
 * Initialize the plugin - this is the ONLY place where we should initialize
 */
function shortcuts_hub_init() {
    // Only initialize during plugins_loaded
    if (current_filter() !== 'plugins_loaded') {
        return;
    }
    
    // Get the instance and initialize it
    $instance = shortcuts_hub();
    $instance->initialize();
}

// Register settings
function shortcuts_hub_register_settings() {
    // Register settings
    register_setting('shortcuts_hub_settings', 'shortcuts_hub_settings');

    // Add settings section
    add_settings_section(
        'shortcuts_hub_main_section',
        'Switchblade API Settings',
        null,
        'shortcuts_hub_settings'
    );

    // Add settings fields
    add_settings_field(
        'sb_url',
        'Switchblade API URL',
        'shortcuts_hub_url_callback',
        'shortcuts_hub_settings',
        'shortcuts_hub_main_section'
    );

    add_settings_field(
        'sb_username',
        'Switchblade Username',
        'shortcuts_hub_username_callback',
        'shortcuts_hub_settings',
        'shortcuts_hub_main_section'
    );

    add_settings_field(
        'sb_password',
        'Switchblade Password',
        'shortcuts_hub_password_callback',
        'shortcuts_hub_settings',
        'shortcuts_hub_main_section'
    );
}

// Settings callbacks
function shortcuts_hub_url_callback() {
    $settings = get_shortcuts_hub_settings();
    echo '<input type="text" name="shortcuts_hub_settings[sb_url]" value="' . esc_attr($settings['sb_url']) . '" class="regular-text">';
}

function shortcuts_hub_username_callback() {
    $settings = get_shortcuts_hub_settings();
    echo '<input type="text" name="shortcuts_hub_settings[sb_username]" value="' . esc_attr($settings['sb_username']) . '" class="regular-text">';
}

function shortcuts_hub_password_callback() {
    $settings = get_shortcuts_hub_settings();
    echo '<input type="password" name="shortcuts_hub_settings[sb_password]" value="' . esc_attr($settings['sb_password']) . '" class="regular-text">';
}

// Add settings page to menu
function shortcuts_hub_add_settings_page() {
    add_options_page(
        'Shortcuts Hub Settings',
        'Shortcuts Hub',
        'manage_options',
        'shortcuts_hub_settings',
        'shortcuts_hub_settings_page'
    );
}

// Settings page callback
function shortcuts_hub_settings_page() {
    ?>
    <div class="wrap">
        <h1>Shortcuts Hub Settings</h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('shortcuts_hub_settings');
            do_settings_sections('shortcuts_hub_settings');
            submit_button();
            ?>
        </form>
    </div>
    <?php
}

// Register settings and admin pages
add_action('admin_init', 'shortcuts_hub_register_settings');
add_action('admin_menu', 'shortcuts_hub_add_settings_page');
