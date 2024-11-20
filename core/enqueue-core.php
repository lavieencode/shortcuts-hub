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

    // Include the registration flow file
    $registration_flow_path = SHORTCUTS_HUB_PATH . 'core/registration-flow.php';
    if (file_exists($registration_flow_path)) {
        require_once $registration_flow_path;
    }

    // Include other necessary components
    require_once SHORTCUTS_HUB_PATH . 'core/download-button.php';
    require_once SHORTCUTS_HUB_PATH . 'core/elementor-dynamic-fields.php';
    require_once SHORTCUTS_HUB_PATH . 'core/user-role.php';
    require_once SHORTCUTS_HUB_PATH . 'core/downloads-db.php';
    require_once SHORTCUTS_HUB_PATH . 'core/log-downloads.php';
}

add_action('init', 'shortcuts_hub_include_files');

function shortcuts_hub_enqueue_scripts() {
    if (is_page('shortcuts-gallery/login') || is_singular('shortcut')) {
        wp_enqueue_script('versions-fetch-script', plugins_url('../assets/js/versions/versions-fetch.js', __FILE__), array('jquery'), null, true);
        wp_localize_script('versions-fetch-script', 'shortcutsHubData', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'security' => wp_create_nonce('shortcuts_hub_nonce'),
            'id' => get_post_meta(get_the_ID(), 'id', true)
        ));

        wp_enqueue_script('login-register-script', plugins_url('../assets/js/core/login-register-page.js', __FILE__), array('jquery'), null, true);
        wp_localize_script('login-register-script', 'shortcutsHubData', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'security' => wp_create_nonce('shortcuts_hub_nonce')
        ));

        wp_enqueue_script('download-button-script', plugins_url('../assets/js/core/download-button.js', __FILE__), array('jquery'), null, true);
        wp_localize_script('download-button-script', 'shortcutsHubData', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'security' => wp_create_nonce('shortcuts_hub_nonce'),
            'post_id' => get_the_ID(),
            'id' => get_post_meta(get_the_ID(), 'id', true)
        ));
    }
}

add_action('wp_enqueue_scripts', 'shortcuts_hub_enqueue_scripts');