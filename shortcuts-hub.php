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

// Load debug functionality first
require_once plugin_dir_path(__FILE__) . 'sh-debug.php';

define('SHORTCUTS_HUB_FILE', __FILE__);
define('SHORTCUTS_HUB_PATH', plugin_dir_path(__FILE__));
define('SHORTCUTS_HUB_VERSION', '1.0.0');

// Only include the main class file - it will handle all other dependencies
require_once SHORTCUTS_HUB_PATH . 'includes/class-shortcuts-hub.php';

// Include core files
require_once plugin_dir_path(__FILE__) . 'core/class-sb-db-manager.php';

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
    
    // Register the Shortcuts endpoint for WooCommerce My Account
    if (class_exists('\ShortcutsHub\Elementor\Widgets\My_Account_Widget')) {
        \ShortcutsHub\Elementor\Widgets\My_Account_Widget::register_on_activation();
    }
});

/**
 * Deactivation handler
 */
register_deactivation_hook(__FILE__, function() {
    // Use singleton instance but don't initialize
    $instance = shortcuts_hub();
    $instance->deactivate();
});

// Register WooCommerce endpoints
add_action('init', 'shortcuts_hub_register_endpoints', 5);

/**
 * Register the Shortcuts endpoint for WooCommerce
 */
function shortcuts_hub_register_endpoints() {
    // Register the shortcuts endpoint
    add_rewrite_endpoint('shortcuts', EP_ROOT | EP_PAGES);
    
    // Only flush rewrite rules if the flag is set
    if (get_option('shortcuts_hub_flush_rewrite_rules')) {
        flush_rewrite_rules(false);
        delete_option('shortcuts_hub_flush_rewrite_rules');
    }
}

// Add template for the Shortcuts endpoint
add_action('woocommerce_account_shortcuts_endpoint', 'shortcuts_hub_endpoint_content');

/**
 * Content for the Shortcuts endpoint
 */
function shortcuts_hub_endpoint_content() {
    // Include the template
    include_once(plugin_dir_path(__FILE__) . 'templates/myaccount/shortcuts.php');
}

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

// Initialize Elementor integration when Elementor is loaded
add_action('elementor/loaded', function() {
    \ShortcutsHub\Elementor\Elementor_Manager::get_instance();
});

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

// Register shutdown hook to cleanup database connections
register_shutdown_function(function() {
    $db = SB_DB_Manager::get_instance();
    $db->close_connection();
});
