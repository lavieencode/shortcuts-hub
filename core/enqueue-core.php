<?php

if (!defined('ABSPATH')) {
    exit;
}

// Function to include widget and dynamic tag files
function shortcuts_hub_register_elementor_components() {
    require_once SHORTCUTS_HUB_PATH . 'core/download-button.php';
    require_once SHORTCUTS_HUB_PATH . 'core/elementor-dynamic-fields.php';
}

// Hook to register widgets and dynamic tags
add_action('elementor/widgets/widgets_registered', 'shortcuts_hub_register_elementor_components');

// Function to enqueue scripts and styles
function shortcuts_hub_enqueue_scripts() {
    error_log('Enqueuing scripts and styles');
    if (is_page('shortcuts-gallery/login') || is_singular('shortcut')) {
        require_once SHORTCUTS_HUB_PATH . 'core/registration-flow.php';
        require_once SHORTCUTS_HUB_PATH . 'core/user-role.php';

        wp_enqueue_script('versions-fetch-script', plugins_url('../assets/js/versions/versions-fetch.js', __FILE__), array('jquery'), null, true);
        wp_localize_script('versions-fetch-script', 'shortcutsHubData', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'security' => wp_create_nonce('shortcuts_hub_nonce'),
            'shortcut_id' => isset($_GET['sb_id']) ? sanitize_text_field($_GET['sb_id']) : ''
        ));
    }
    error_log('Scripts and styles enqueued');
}

// Hook to enqueue scripts and styles
add_action('wp_enqueue_scripts', 'shortcuts_hub_enqueue_scripts');