<?php

if (!defined('ABSPATH')) {
    exit;
}

function shortcuts_hub_enqueue_assets($hook) {
    // Get the current screen to determine which page we're on
    $screen = get_current_screen();
    $allowed_screens = array(
        'toplevel_page_shortcuts-list',
        'shortcuts-hub_page_add-shortcut',
        'shortcuts-hub_page_edit-shortcut',
        'shortcuts-hub_page_add-version',
        'shortcuts-hub_page_settings',
        'shortcuts-hub_page_actions-manager',
        'shortcuts-hub_page_edit-version'
    );

    if (!in_array($screen->id, $allowed_screens)) {
        return;
    }

    wp_enqueue_script('jquery');

    // Check if we're in the admin area
    $is_admin_page = in_array($screen->id, [
        'toplevel_page_shortcuts-list',
        'shortcuts-hub_page_add-shortcut',
        'shortcuts-hub_page_edit-shortcut',
        'shortcuts-hub_page_settings',
        'shortcuts-hub_page_actions-manager'
    ]);

    // Continue with existing admin page-specific enqueues
    if ($is_admin_page) {
        // Enqueue Font Awesome and icon selector assets globally
        wp_enqueue_style('shortcuts-hub-fontawesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css', array(), '5.15.4');
        
        // Add preload for Font Awesome
        add_action('admin_head', function() {
            echo '<link rel="preload" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" as="style">';
            echo '<link rel="preload" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/webfonts/fa-solid-900.woff2" as="font" type="font/woff2" crossorigin>';
            echo '<link rel="preload" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/webfonts/fa-regular-400.woff2" as="font" type="font/woff2" crossorigin>';
            echo '<link rel="preload" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/webfonts/fa-brands-400.woff2" as="font" type="font/woff2" crossorigin>';
        });
        
        wp_enqueue_style('shortcuts-hub-icon-selector', plugins_url('../assets/css/core/icon-selector.css', __FILE__), array(), filemtime(plugin_dir_path(__FILE__) . '../assets/css/core/icon-selector.css'));
        
        // Enqueue icon selector script first, and ensure it loads in the header
        wp_enqueue_script('shortcuts-hub-icon-selector', 
            plugins_url('../assets/js/core/icon-selector.js', __FILE__), 
            array('jquery'), 
            filemtime(plugin_dir_path(__FILE__) . '../assets/js/core/icon-selector.js'),
            true
        );

        // Add debug script for admin pages only
        wp_enqueue_script('sh-debug', 
            plugins_url('../assets/js/sh-debug.js', __FILE__), 
            array('jquery', 'shortcuts-hub-icon-selector'), 
            filemtime(plugin_dir_path(__FILE__) . '../assets/js/sh-debug.js')
        );
        
        // Create nonce for debug script
        $general_nonce = wp_create_nonce('shortcuts_hub_nonce');
        $versions_nonce = wp_create_nonce('shortcuts_hub_versions_nonce');
        $shortcuts_hub_data = array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'site_url' => get_site_url(),
            'security' => $general_nonce,
            'versions_security' => $versions_nonce,
            'isElementorActive' => defined('ELEMENTOR_VERSION'),
            'isWooCommerceActive' => class_exists('WooCommerce')
        );
        wp_localize_script('sh-debug', 'shortcutsHubData', $shortcuts_hub_data);
    }

    switch ($hook) {
        case 'toplevel_page_shortcuts-list':
            wp_enqueue_style('shortcuts-hub-shortcuts-display', plugins_url('../assets/css/shortcuts/shortcuts-display.css', __FILE__), array(), filemtime(plugin_dir_path(__FILE__) . '../assets/css/shortcuts/shortcuts-display.css'));
            wp_enqueue_style('shortcuts-hub-shortcut-modal', plugins_url('../assets/css/shortcuts/shortcut-modal.css', __FILE__), array(), filemtime(plugin_dir_path(__FILE__) . '../assets/css/shortcuts/shortcut-modal.css'));
            wp_enqueue_style('shortcuts-hub-shortcut-single', plugins_url('../assets/css/shortcuts/shortcut-single.css', __FILE__), array(), filemtime(plugin_dir_path(__FILE__) . '../assets/css/shortcuts/shortcut-single.css'));
            wp_enqueue_style('shortcuts-hub-version-modal', plugins_url('../assets/css/versions/version-modal.css', __FILE__), array(), filemtime(plugin_dir_path(__FILE__) . '../assets/css/versions/version-modal.css'));
            wp_enqueue_style('shortcuts-hub-version-single', plugins_url('../assets/css/versions/version-single.css', __FILE__), array(), filemtime(plugin_dir_path(__FILE__) . '../assets/css/versions/version-single.css'));
            wp_enqueue_style('shortcuts-hub-versions-display', plugins_url('../assets/css/versions/versions-display.css', __FILE__), array(), filemtime(plugin_dir_path(__FILE__) . '../assets/css/versions/versions-display.css'));
            wp_enqueue_style('shortcuts-hub-general-styles', plugins_url('../assets/css/general.css', __FILE__), array(), filemtime(plugin_dir_path(__FILE__) . '../assets/css/general.css'));

            wp_enqueue_script('shortcuts-hub-shortcuts-handlers', plugins_url('../assets/js/shortcuts/shortcuts-handlers.js', __FILE__), array('jquery'), filemtime(plugin_dir_path(__FILE__) . '../assets/js/shortcuts/shortcuts-handlers.js'));
            wp_enqueue_script('shortcuts-hub-shortcuts-render', plugins_url('../assets/js/shortcuts/shortcuts-render.js', __FILE__), array('jquery'), filemtime(plugin_dir_path(__FILE__) . '../assets/js/shortcuts/shortcuts-render.js'));
            wp_enqueue_script('shortcuts-hub-shortcuts-fetch', plugins_url('../assets/js/shortcuts/shortcuts-fetch.js', __FILE__), array('jquery'), filemtime(plugin_dir_path(__FILE__) . '../assets/js/shortcuts/shortcuts-fetch.js'));
            
            // Get current view parameters
            $view = isset($_GET['view']) ? sanitize_text_field($_GET['view']) : '';
            $id = isset($_GET['id']) ? sanitize_text_field($_GET['id']) : '';
            $show_versions = $view === 'versions';

            // Create nonce for versions AJAX
            $versions_nonce = wp_create_nonce('shortcuts_hub_versions_nonce');
            $general_nonce = wp_create_nonce('shortcuts_hub_nonce');

            // Create a single, comprehensive data object
            $shortcuts_hub_data = array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'site_url' => get_site_url(),
                'security' => $general_nonce,
                'versions_security' => $versions_nonce,
                'nonces' => array(
                    'fetch_versions' => wp_create_nonce('shortcuts_hub_versions_nonce'),
                    'create_version' => wp_create_nonce('shortcuts_hub_create_version_nonce'),
                    'update_version' => wp_create_nonce('shortcuts_hub_update_version_nonce'),
                    'delete_version' => wp_create_nonce('shortcuts_hub_delete_version_nonce')
                ),
                'view' => $view,
                'shortcutId' => $id,
                'initialView' => $show_versions ? 'versions' : 'shortcuts',
                'isElementorActive' => defined('ELEMENTOR_VERSION'),
                'isWooCommerceActive' => class_exists('WooCommerce')
            );

            // First enqueue versions-fetch.js since other scripts depend on it
            wp_enqueue_script('shortcuts-hub-versions-fetch', 
                plugins_url('../assets/js/versions/versions-fetch.js', __FILE__), 
                array('jquery', 'sh-debug'), 
                filemtime(plugin_dir_path(__FILE__) . '../assets/js/versions/versions-fetch.js')
            );

            // Localize script data immediately after enqueuing first script
            wp_localize_script('shortcuts-hub-versions-fetch', 'shortcutsHubData', $shortcuts_hub_data);

            // Then enqueue other version-related scripts that depend on versions-fetch.js
            wp_enqueue_script('shortcuts-hub-versions-handlers', 
                plugins_url('../assets/js/versions/versions-handlers.js', __FILE__), 
                array('jquery', 'shortcuts-hub-versions-fetch'), 
                filemtime(plugin_dir_path(__FILE__) . '../assets/js/versions/versions-handlers.js')
            );

            wp_enqueue_script('shortcuts-hub-shortcuts-versions-view', 
                plugins_url('../assets/js/shortcuts/shortcuts-versions-view.js', __FILE__), 
                array('jquery', 'shortcuts-hub-versions-fetch'), 
                filemtime(plugin_dir_path(__FILE__) . '../assets/js/shortcuts/shortcuts-versions-view.js')
            );

            // Then enqueue other version scripts
            wp_enqueue_script('shortcuts-hub-versions-render', 
                plugins_url('../assets/js/versions/versions-render.js', __FILE__), 
                array('jquery', 'shortcuts-hub-versions-fetch'), 
                filemtime(plugin_dir_path(__FILE__) . '../assets/js/versions/versions-render.js')
            );

            wp_enqueue_script('shortcuts-hub-versions-filters', 
                plugins_url('../assets/js/versions/versions-filters.js', __FILE__), 
                array('jquery', 'shortcuts-hub-versions-fetch'), 
                filemtime(plugin_dir_path(__FILE__) . '../assets/js/versions/versions-filters.js')
            );

            wp_enqueue_script('shortcuts-hub-versions-modal', plugins_url('../assets/js/versions/versions-modal.js', __FILE__), array('jquery'), filemtime(plugin_dir_path(__FILE__) . '../assets/js/versions/versions-modal.js'));
            wp_enqueue_script('shortcuts-hub-versions-delete', plugins_url('../assets/js/versions/versions-delete.js', __FILE__), array('jquery', 'shortcuts-hub-versions-fetch'), filemtime(plugin_dir_path(__FILE__) . '../assets/js/versions/versions-delete.js'));
            wp_enqueue_script('shortcuts-hub-version-update', plugins_url('../assets/js/versions/version-update.js', __FILE__), array('jquery'), filemtime(plugin_dir_path(__FILE__) . '../assets/js/versions/version-update.js'));
            wp_enqueue_script('shortcuts-hub-version-create', plugins_url('../assets/js/versions/version-create.js', __FILE__), array('jquery'), filemtime(plugin_dir_path(__FILE__) . '../assets/js/versions/version-create.js'));

            // Localize other scripts AFTER enqueuing them
            wp_localize_script('shortcuts-hub-shortcuts-versions-view', 'shortcutsHubData', $shortcuts_hub_data);
            wp_localize_script('shortcuts-hub-versions-fetch', 'shortcutsHubData', $shortcuts_hub_data);
            wp_localize_script('shortcuts-hub-versions-render', 'shortcutsHubData', $shortcuts_hub_data);
            wp_localize_script('shortcuts-hub-versions-filters', 'shortcutsHubData', $shortcuts_hub_data);
            wp_localize_script('shortcuts-hub-versions-modal', 'shortcutsHubData', $shortcuts_hub_data);
            wp_localize_script('shortcuts-hub-versions-delete', 'shortcutsHubData', $shortcuts_hub_data);
            wp_localize_script('shortcuts-hub-version-update', 'shortcutsHubData', $shortcuts_hub_data);
            wp_localize_script('shortcuts-hub-version-create', 'shortcutsHubData', $shortcuts_hub_data);

            wp_enqueue_script('shortcuts-hub-shortcuts-filters', plugins_url('../assets/js/shortcuts/shortcuts-filters.js', __FILE__), array('jquery'), filemtime(plugin_dir_path(__FILE__) . '../assets/js/shortcuts/shortcuts-filters.js'));
            wp_enqueue_script('shortcuts-hub-shortcuts-modal', plugins_url('../assets/js/shortcuts/shortcuts-modal.js', __FILE__), array('jquery'), filemtime(plugin_dir_path(__FILE__) . '../assets/js/shortcuts/shortcuts-modal.js'));

            // Create nonce once
            $nonce = wp_create_nonce('shortcuts_hub_nonce');
            
            // Enqueue delete script with its dependencies
            wp_enqueue_script('shortcuts-hub-shortcut-delete', 
                plugins_url('../assets/js/shortcuts/shortcut-delete.js', __FILE__), 
                array('jquery'), 
                filemtime(plugin_dir_path(__FILE__) . '../assets/js/shortcuts/shortcut-delete.js')
            );

            // Localize script with required data
            wp_localize_script('shortcuts-hub-shortcut-delete', 'shortcutsHubData', $shortcuts_hub_data);

            wp_localize_script('shortcuts-hub-shortcuts-handlers', 'shortcutsHubData', $shortcuts_hub_data);

            wp_localize_script('shortcuts-hub-shortcuts-render', 'shortcutsHubData', $shortcuts_hub_data);
            break;

        case 'shortcuts-hub_page_edit-shortcut':
            wp_enqueue_media();
            wp_enqueue_style('wp-color-picker');
            wp_enqueue_script('wp-color-picker');
            
            wp_enqueue_style('shortcuts-hub-edit-shortcut', plugins_url('../assets/css/pages/edit-shortcut.css', __FILE__), array(), filemtime(plugin_dir_path(__FILE__) . '../assets/css/pages/edit-shortcut.css'));
            wp_enqueue_style('shortcuts-hub-general-styles', plugins_url('../assets/css/general.css', __FILE__), array(), filemtime(plugin_dir_path(__FILE__) . '../assets/css/general.css'));
            
            wp_enqueue_script('wp-color-picker');
            
            // Create nonce once for this page
            $nonce = wp_create_nonce('shortcuts_hub_nonce');

            wp_enqueue_script('shortcuts-hub-shortcut-update', 
                plugins_url('../assets/js/shortcuts/shortcut-update.js', __FILE__), 
                array('jquery'), 
                filemtime(plugin_dir_path(__FILE__) . '../assets/js/shortcuts/shortcut-update.js')
            );

            // Page-specific scripts with dependencies
            wp_enqueue_script('shortcuts-hub-edit-shortcut', 
                plugins_url('../assets/js/pages/edit-shortcut.js', __FILE__), 
                array('jquery', 'wp-color-picker', 'shortcuts-hub-shortcut-update'), 
                filemtime(plugin_dir_path(__FILE__) . '../assets/js/pages/edit-shortcut.js')
            );

            // Localize scripts with their required data
            wp_localize_script('shortcuts-hub-shortcut-update', 'shortcutsHubData', $shortcuts_hub_data);
            
            // Get WordPress uploads directory info
            $upload_dir = wp_upload_dir();
            
            wp_localize_script('shortcuts-hub-edit-shortcut', 'shortcutsHubData', array_merge($shortcuts_hub_data, array(
                'sb_api_url' => get_shortcuts_hub_settings()['sb_url'],
                'uploads_url' => $upload_dir['baseurl'],
                'uploads_dir' => $upload_dir['basedir']
            )));
            break;

        case 'shortcuts-hub_page_add-shortcut':
            wp_enqueue_media();
            wp_enqueue_style('wp-color-picker');
            wp_enqueue_script('wp-color-picker');
            
            wp_enqueue_style('shortcuts-hub-add-shortcut', plugins_url('../assets/css/pages/add-shortcut.css', __FILE__), array(), filemtime(plugin_dir_path(__FILE__) . '../assets/css/pages/add-shortcut.css'));
            wp_enqueue_style('shortcuts-hub-general-styles', plugins_url('../assets/css/general.css', __FILE__), array(), filemtime(plugin_dir_path(__FILE__) . '../assets/css/general.css'));
            
            // Core scripts
            wp_enqueue_script('shortcuts-hub-shortcut-create', 
                plugins_url('../assets/js/shortcuts/shortcut-create.js', __FILE__), 
                array('jquery'), 
                filemtime(plugin_dir_path(__FILE__) . '../assets/js/shortcuts/shortcut-create.js')
            );

            // Load page-specific script with dependency on shortcut-create
            wp_enqueue_script('shortcuts-hub-add-shortcut', 
                plugins_url('../assets/js/pages/add-shortcut.js', __FILE__), 
                array('jquery', 'wp-color-picker', 'shortcuts-hub-shortcut-create'), 
                filemtime(plugin_dir_path(__FILE__) . '../assets/js/pages/add-shortcut.js')
            );

            // Localize script with ALL required data
            wp_localize_script('shortcuts-hub-shortcut-create', 'shortcutsHubData', array_merge($shortcuts_hub_data, array(
                'site_url' => get_site_url(),
                'sb_api_url' => get_shortcuts_hub_settings()['sb_url'],
                'uploads_url' => wp_upload_dir()['baseurl'],
                'uploads_dir' => wp_upload_dir()['basedir'],
                'security' => wp_create_nonce('shortcuts_hub_nonce')
            )));
            break;

        case 'shortcuts-hub_page_settings':
            wp_enqueue_style('shortcuts-hub-settings', plugins_url('../assets/css/settings.css', __FILE__), array(), filemtime(plugin_dir_path(__FILE__) . '../assets/css/settings.css'));
            wp_enqueue_style('shortcuts-hub-general-styles', plugins_url('../assets/css/general.css', __FILE__), array(), filemtime(plugin_dir_path(__FILE__) . '../assets/css/general.css'));
            break;

        case 'shortcuts-hub_page_actions-manager':
            // First load general styles
            wp_enqueue_style('shortcuts-hub-general', plugins_url('../assets/css/general.css', __FILE__), array(), filemtime(plugin_dir_path(__FILE__) . '../assets/css/general.css'));
            
            // Enqueue actions manager styles if on the actions manager page
            if ($screen->id === 'shortcuts-hub_page_actions-manager') {
                wp_enqueue_style('shortcuts-hub-actions-manager', 
                    plugins_url('../assets/css/pages/actions-manager.css', __FILE__), 
                    array(), 
                    filemtime(plugin_dir_path(__FILE__) . '../assets/css/pages/actions-manager.css')
                );
            }

            // Enqueue icon selector script first
            wp_enqueue_script('shortcuts-hub-icon-selector', 
                plugins_url('../assets/js/core/icon-selector.js', __FILE__), 
                array('jquery'), 
                filemtime(plugin_dir_path(__FILE__) . '../assets/js/core/icon-selector.js'),
                true
            );

            // Enqueue actions manager script
            wp_enqueue_script(
                'shortcuts-hub-actions-manager',
                plugins_url('../assets/js/pages/actions-manager.js', __FILE__),
                array('jquery', 'shortcuts-hub-icon-selector', 'sh-debug'),
                filemtime(plugin_dir_path(__FILE__) . '../assets/js/pages/actions-manager.js'),
                true
            );

            // Create security nonces for actions
            $security = array(
                'fetch_actions' => wp_create_nonce('shortcuts_hub_fetch_actions_nonce'),
                'create_action' => wp_create_nonce('shortcuts_hub_create_action_nonce'),
                'update_action' => wp_create_nonce('shortcuts_hub_update_action_nonce'),
                'delete_action' => wp_create_nonce('shortcuts_hub_delete_action_nonce')
            );

            // Localize script with ALL required data
            wp_localize_script('shortcuts-hub-actions-manager', 'shortcutsHubData', array_merge($shortcuts_hub_data, array(
                'security' => $security
            )));
            break;

        case 'shortcuts-hub_page_add-version':
            wp_enqueue_style('shortcuts-hub-add-version', plugins_url('../assets/css/pages/add-version.css', __FILE__), array(), filemtime(plugin_dir_path(__FILE__) . '../assets/css/pages/add-version.css'));
            wp_enqueue_script('shortcuts-hub-add-version', plugins_url('../assets/js/pages/add-version.js', __FILE__), array('jquery'), filemtime(plugin_dir_path(__FILE__) . '../assets/js/pages/add-version.js'), true);
            
            wp_enqueue_script('shortcuts-hub-versions-fetch', plugins_url('../assets/js/versions/versions-fetch.js', __FILE__), array('jquery'), filemtime(plugin_dir_path(__FILE__) . '../assets/js/versions/versions-fetch.js'), true);
            wp_localize_script('shortcuts-hub-versions-fetch', 'shortcutsHubData', $shortcuts_hub_data);
            wp_enqueue_script('shortcuts-hub-versions-render', plugins_url('../assets/js/versions/versions-render.js', __FILE__), array('jquery'), filemtime(plugin_dir_path(__FILE__) . '../assets/js/versions/versions-render.js'), true);
            
            wp_localize_script('shortcuts-hub-add-version', 'shortcutsHubData', $shortcuts_hub_data);
            break;

        case 'shortcuts-hub_page_edit-version':
            wp_enqueue_style('shortcuts-hub-edit-version', plugins_url('../assets/css/pages/edit-version.css', __FILE__), array(), filemtime(plugin_dir_path(__FILE__) . '../assets/css/pages/edit-version.css'));
            wp_enqueue_script('shortcuts-hub-edit-version', plugins_url('../assets/js/pages/edit-version.js', __FILE__), array('jquery'), filemtime(plugin_dir_path(__FILE__) . '../assets/js/pages/edit-version.js'), true);
            
            wp_enqueue_script('shortcuts-hub-versions-fetch', plugins_url('../assets/js/versions/versions-fetch.js', __FILE__), array('jquery'), filemtime(plugin_dir_path(__FILE__) . '../assets/js/versions/versions-fetch.js'), true);
            wp_localize_script('shortcuts-hub-versions-fetch', 'shortcutsHubData', $shortcuts_hub_data);
            wp_enqueue_script('shortcuts-hub-versions-render', plugins_url('../assets/js/versions/versions-render.js', __FILE__), array('jquery'), filemtime(plugin_dir_path(__FILE__) . '../assets/js/versions/versions-render.js'), true);
            
            wp_localize_script('shortcuts-hub-edit-version', 'shortcutsHubData', $shortcuts_hub_data);
            break;
    }

    // AJAX parameters for use across the site
    wp_localize_script('jquery', 'shortcutsHubData', $shortcuts_hub_data);
}

add_action('admin_enqueue_scripts', 'shortcuts_hub_enqueue_assets');
