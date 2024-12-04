<?php

if (!defined('ABSPATH')) {
    exit;
}

// Get settings from WordPress options
function get_shortcuts_hub_settings() {
    static $settings = null;
    
    // Return cached settings if already loaded
    if ($settings !== null) {
        return $settings;
    }
    
    // Get settings from options
    $settings = get_option('shortcuts_hub_settings');
    
    // If settings don't exist, set up defaults
    if (false === $settings) {
        $settings = array(
            'sb_url' => 'https://debotchery-switchblade-bc6fa1ee4e01.herokuapp.com',
            'sb_username' => 'nicole',
            'sb_password' => 'QCW*nN@q8RfN&bFI2^qKuYZpG'
        );
        // Save the default settings
        update_option('shortcuts_hub_settings', $settings);
    }

    return $settings;
}

// Register settings
function register_shortcuts_hub_settings() {
    register_setting(
        'shortcuts_hub_settings',
        'shortcuts_hub_settings',
        array(
            'type' => 'array',
            'sanitize_callback' => 'sanitize_shortcuts_hub_settings',
            'show_in_rest' => false
        )
    );
}

// Sanitize settings before saving
function sanitize_shortcuts_hub_settings($settings) {
    if (!is_array($settings)) {
        return array();
    }

    $sanitized = array();
    $sanitized['sb_url'] = esc_url_raw($settings['sb_url']);
    $sanitized['sb_username'] = sanitize_text_field($settings['sb_username']);
    
    // For password, keep existing if not changed
    if (!empty($settings['sb_password'])) {
        $sanitized['sb_password'] = $settings['sb_password'];
    } else {
        $existing = get_option('shortcuts_hub_settings');
        $sanitized['sb_password'] = $existing['sb_password'] ?? '';
    }

    return $sanitized;
}

// Add settings page to menu
function add_shortcuts_hub_settings_page() {
    add_submenu_page(
        'options-general.php',
        'Shortcuts Hub Settings',
        'Shortcuts Hub',
        'manage_options',
        'shortcuts_hub_settings',
        'render_shortcuts_hub_settings_page'
    );
}

// Render settings page
function render_shortcuts_hub_settings_page() {
    if (!current_user_can('manage_options')) {
        return;
    }

    $settings = get_shortcuts_hub_settings();
    
    if (isset($_POST['submit'])) {
        check_admin_referer('shortcuts_hub_settings');
        
        $new_settings = array(
            'sb_url' => $settings['sb_url'], // URL stays the same
            'sb_username' => sanitize_text_field($_POST['sb_username']),
            'sb_password' => sanitize_text_field($_POST['sb_password'])
        );
        
        update_option('shortcuts_hub_settings', $new_settings);
        echo '<div class="notice notice-success"><p>Settings saved.</p></div>';
        $settings = $new_settings;
    }
    ?>
    <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        <form method="post" action="">
            <?php wp_nonce_field('shortcuts_hub_settings'); ?>
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="sb_url">Switchblade API URL</label>
                    </th>
                    <td>
                        <input type="url" id="sb_url" name="sb_url" 
                               value="<?php echo esc_attr($settings['sb_url']); ?>" class="regular-text" readonly>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="sb_username">Username</label>
                    </th>
                    <td>
                        <input type="text" id="sb_username" name="sb_username" 
                               value="<?php echo esc_attr($settings['sb_username']); ?>" class="regular-text" required>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="sb_password">Password</label>
                    </th>
                    <td>
                        <input type="password" id="sb_password" name="sb_password" 
                               value="<?php echo esc_attr($settings['sb_password']); ?>" class="regular-text" required>
                    </td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}

// Initialize everything
add_action('admin_init', 'register_shortcuts_hub_settings');
add_action('admin_menu', 'add_shortcuts_hub_settings_page');
