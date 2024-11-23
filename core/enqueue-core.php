<?php

if (!defined('ABSPATH')) {
    exit;
}

function shortcuts_hub_include_files() {
    if (!defined('SHORTCUTS_HUB_PATH')) {
        return;
    }

    // Core database functionality must be loaded first
    require_once SHORTCUTS_HUB_PATH . 'core/database.php';
    
    // Core functionality files
    require_once SHORTCUTS_HUB_PATH . 'core/download-button.php';
    require_once SHORTCUTS_HUB_PATH . 'core/registration-flow.php';
    require_once SHORTCUTS_HUB_PATH . 'core/login-flow.php';
    require_once SHORTCUTS_HUB_PATH . 'core/elementor-dynamic-fields.php';
    require_once SHORTCUTS_HUB_PATH . 'core/user-role.php';
}

add_action('init', 'shortcuts_hub_include_files');

function shortcuts_hub_enqueue_scripts() {
    // Enqueue core scripts
    wp_enqueue_script('shortcuts-hub-download-button', 
        plugins_url('../assets/js/core/download-button.js', __FILE__), 
        array('jquery'), 
        filemtime(plugin_dir_path(__FILE__) . '../assets/js/core/download-button.js'), 
        true
    );
    
    wp_enqueue_script('shortcuts-hub-login-redirect', 
        plugins_url('../assets/js/core/login-redirect.js', __FILE__), 
        array('jquery'), 
        filemtime(plugin_dir_path(__FILE__) . '../assets/js/core/login-redirect.js'), 
        true
    );
    
    // Enqueue logout handler script for logged-in users
    if (is_user_logged_in()) {
        wp_enqueue_script(
            'shortcuts-hub-logout-handler',
            plugins_url('/assets/js/core/logout-handler.js', dirname(__FILE__)),
            array('jquery'),
            filemtime(plugin_dir_path(__FILE__) . '../assets/js/core/logout-handler.js'),
            true
        );
        wp_localize_script('shortcuts-hub-logout-handler', 'shortcutsHubLogout', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('shortcuts_hub_ajax_logout')
        ));
    }
    
    // Get current page URL for redirect
    $current_url = get_permalink();
    if (!$current_url) {
        $current_url = home_url($_SERVER['REQUEST_URI']);
    }
    
    // Basic data for all pages
    $shortcuts_hub_data = array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('shortcuts_hub_nonce'),
        'is_user_logged_in' => is_user_logged_in(),
        'login_url' => site_url('/shortcuts-gallery/login/'),
        'redirect_url' => $current_url
    );
    
    // Add shortcut data if we're on a shortcut page
    $post_id = get_the_ID();
    if ($post_id && is_singular('shortcut')) {
        $sb_id = get_post_meta($post_id, 'sb_id', true);
        if ($sb_id) {
            $response = sb_api_call("shortcuts/{$sb_id}/version/latest", 'GET');
            if (!is_wp_error($response)) {
                $shortcuts_hub_data['shortcut'] = $response;
            }
        }
    }
    
    // Localize scripts
    wp_localize_script('shortcuts-hub-download-button', 'shortcuts_hub', $shortcuts_hub_data);
    wp_localize_script('shortcuts-hub-login-redirect', 'shortcuts_hub', $shortcuts_hub_data);
}

add_action('wp_enqueue_scripts', 'shortcuts_hub_enqueue_scripts');