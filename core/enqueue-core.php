<?php

if (!defined('ABSPATH')) {
    exit;
}

function shortcuts_hub_include_files() {
    if (!defined('SHORTCUTS_HUB_PATH')) {
        return;
    }

    // Core functionality files (database.php is now loaded directly in main plugin file)
    require_once SHORTCUTS_HUB_PATH . 'core/registration-flow.php';
    require_once SHORTCUTS_HUB_PATH . 'core/login-flow.php';
    require_once SHORTCUTS_HUB_PATH . 'core/user-role.php';
}

add_action('init', 'shortcuts_hub_include_files');

function shortcuts_hub_enqueue_scripts() {
    // Check if we're on a plugin page or have a plugin shortcode
    $is_plugin_page = false;
    
    // Check if we're on an admin page
    if (is_admin()) {
        global $pagenow;
        // Get the current admin page
        $page = isset($_GET['page']) ? sanitize_text_field($_GET['page']) : '';
        
        // List of our admin pages
        $admin_pages = array(
            'shortcuts-list',
            'add-shortcut',
            'edit-shortcut',
            'add-version',
            'edit-version'
        );
        
        if (in_array($page, $admin_pages)) {
            $is_plugin_page = true;
        }
    } else {
        // Frontend checks
        global $post;
        if ($post) {
            $plugin_pages = array(
                'shortcuts-gallery'
            );
            
            // Check URL path
            $current_path = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
            $path_parts = explode('/', $current_path);
            
            // Check if we're on a plugin page
            if (!empty($path_parts[0]) && in_array($path_parts[0], $plugin_pages)) {
                $is_plugin_page = true;
            }
            
            // Check for shortcodes in content
            if (has_shortcode($post->post_content, 'shortcuts_hub') ||
                has_shortcode($post->post_content, 'shortcuts_gallery')) {
                $is_plugin_page = true;
            }
        }
    }
    
    // Only load scripts if we're on a plugin page
    if (!$is_plugin_page) {
        return;
    }

    // Enqueue core scripts
    wp_enqueue_script('shortcuts-hub-login-redirect', 
        plugins_url('../assets/js/core/login-redirect.js', __FILE__), 
        array('jquery'), 
        filemtime(plugin_dir_path(__FILE__) . '../assets/js/core/login-redirect.js'), 
        true
    );
    
    // Localize login redirect script
    wp_localize_script('shortcuts-hub-login-redirect', 'shortcutsHubAjax', array(
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('shortcuts_hub_nonce')
    ));
    
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
    $nonce = wp_create_nonce('shortcuts_hub_nonce');  // Changed to match AJAX handler
    
    $shortcuts_hub_data = array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => $nonce,
        'is_user_logged_in' => is_user_logged_in(),
        'login_url' => site_url('/shortcuts-gallery/login/'),
        'redirect_url' => $current_url
    );
    
    // Localize scripts with core data
    wp_localize_script('shortcuts-hub-login-redirect', 'shortcuts_hub', $shortcuts_hub_data);
}

add_action('wp_enqueue_scripts', 'shortcuts_hub_enqueue_scripts');