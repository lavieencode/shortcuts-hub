<?php

if (!defined('ABSPATH')) {
    exit;
}

// Include necessary files
function shortcuts_hub_include_files() {
    // Ensure the SHORTCUTS_HUB_PATH constant is defined
    if (!defined('SHORTCUTS_HUB_PATH')) {
        error_log('SHORTCUTS_HUB_PATH is not defined.');
        return;
    }

    // Include the registration webhooks file to ensure it's loaded everywhere
    $registration_webhooks_path = SHORTCUTS_HUB_PATH . 'core/registration-webhooks.php';
    if (file_exists($registration_webhooks_path)) {
        require_once $registration_webhooks_path;
    } else {
        error_log('Registration webhooks file not found: ' . $registration_webhooks_path);
    }

    // Include the registration flow file
    $registration_flow_path = SHORTCUTS_HUB_PATH . 'core/registration-flow.php';
    if (file_exists($registration_flow_path)) {
        require_once $registration_flow_path;
    } else {
        error_log('Registration flow file not found: ' . $registration_flow_path);
    }

    // Include other necessary components
    require_once SHORTCUTS_HUB_PATH . 'core/download-button.php';
    require_once SHORTCUTS_HUB_PATH . 'core/elementor-dynamic-fields.php';
    require_once SHORTCUTS_HUB_PATH . 'core/user-role.php';
}

add_action('init', 'shortcuts_hub_include_files');

// Enqueue scripts and styles
function shortcuts_hub_enqueue_scripts() {
    if (is_page('shortcuts-gallery/login') || is_singular('shortcut')) {
        wp_enqueue_script('versions-fetch-script', plugins_url('../assets/js/versions/versions-fetch.js', __FILE__), array('jquery'), null, true);
        wp_localize_script('versions-fetch-script', 'shortcutsHubData', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'security' => wp_create_nonce('shortcuts_hub_nonce'),
            'shortcut_id' => isset($_GET['sb_id']) ? sanitize_text_field($_GET['sb_id']) : ''
        ));

        wp_enqueue_script('login-register-script', plugins_url('../assets/js/core/login-register-page.js', __FILE__), array('jquery'), null, true);
        wp_localize_script('login-register-script', 'shortcutsHubData', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'security' => wp_create_nonce('shortcuts_hub_nonce')
        ));
    }
}

add_action('wp_enqueue_scripts', 'shortcuts_hub_enqueue_scripts');