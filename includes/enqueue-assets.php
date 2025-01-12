<?php

if (!defined('ABSPATH')) {
    exit;
}

function shortcuts_hub_enqueue_assets($hook) {
    wp_enqueue_script('jquery');

    // Check if we're in the admin area
    $is_admin_page = in_array($hook, [
        'shortcuts-hub_page_shortcuts-list',
        'shortcuts-hub_page_add-shortcut',
        'shortcuts-hub_page_edit-shortcut',
        'shortcuts-hub_page_shortcuts-settings',
        'shortcuts-hub_page_add-version',
        'shortcuts-hub_page_edit-version'
    ]);

    // Continue with existing admin page-specific enqueues
    if ($is_admin_page) {
        wp_enqueue_style(
            'general-styles',
            plugins_url('../assets/css/general.css', __FILE__),
            array(),
            filemtime(plugin_dir_path(__FILE__) . '../assets/css/general.css')
        );

        // Add debug script for admin pages only
        wp_enqueue_script('sh-debug', 
            plugins_url('../assets/js/sh-debug.js', __FILE__), 
            array('jquery'), 
            filemtime(plugin_dir_path(__FILE__) . '../assets/js/sh-debug.js')
        );
        
        // Create nonce for debug script
        $nonce = wp_create_nonce('shortcuts_hub_nonce');
        wp_localize_script('sh-debug', 'shDebugData', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'security' => $nonce,
            'isElementorActive' => defined('ELEMENTOR_VERSION'),
            'isWooCommerceActive' => class_exists('WooCommerce')
        ));
    }

    switch ($hook) {
        case 'shortcuts-hub_page_shortcuts-list':
            wp_enqueue_style('shortcuts-display-style', plugins_url('../assets/css/shortcuts/shortcuts-display.css', __FILE__), array(), filemtime(plugin_dir_path(__FILE__) . '../assets/css/shortcuts/shortcuts-display.css'));
            wp_enqueue_style('shortcut-modal-style', plugins_url('../assets/css/shortcuts/shortcut-modal.css', __FILE__), array(), filemtime(plugin_dir_path(__FILE__) . '../assets/css/shortcuts/shortcut-modal.css'));
            wp_enqueue_style('shortcut-single-style', plugins_url('../assets/css/shortcuts/shortcut-single.css', __FILE__), array(), filemtime(plugin_dir_path(__FILE__) . '../assets/css/shortcuts/shortcut-single.css'));
            wp_enqueue_style('version-modal-style', plugins_url('../assets/css/versions/version-modal.css', __FILE__), array(), filemtime(plugin_dir_path(__FILE__) . '../assets/css/versions/version-modal.css'));
            wp_enqueue_style('version-single-style', plugins_url('../assets/css/versions/version-single.css', __FILE__), array(), filemtime(plugin_dir_path(__FILE__) . '../assets/css/versions/version-single.css'));
            wp_enqueue_style('versions-display-style', plugins_url('../assets/css/versions/versions-display.css', __FILE__), array(), filemtime(plugin_dir_path(__FILE__) . '../assets/css/versions/versions-display.css'));

            wp_enqueue_script('shortcuts-handlers-script', plugins_url('../assets/js/shortcuts/shortcuts-handlers.js', __FILE__), array('jquery'), filemtime(plugin_dir_path(__FILE__) . '../assets/js/shortcuts/shortcuts-handlers.js'));
            wp_enqueue_script('shortcuts-render-script', plugins_url('../assets/js/shortcuts/shortcuts-render.js', __FILE__), array('jquery'), filemtime(plugin_dir_path(__FILE__) . '../assets/js/shortcuts/shortcuts-render.js'));
            wp_enqueue_script('shortcuts-fetch-script', plugins_url('../assets/js/shortcuts/shortcuts-fetch.js', __FILE__), array('jquery'), filemtime(plugin_dir_path(__FILE__) . '../assets/js/shortcuts/shortcuts-fetch.js'));
            
            // Localize script with required data
            wp_localize_script('shortcuts-fetch-script', 'shortcutsHubData', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'security' => wp_create_nonce('shortcuts_hub_nonce')
            ));

            wp_enqueue_script('shortcuts-filters-script', plugins_url('../assets/js/shortcuts/shortcuts-filters.js', __FILE__), array('jquery'), filemtime(plugin_dir_path(__FILE__) . '../assets/js/shortcuts/shortcuts-filters.js'));
            wp_enqueue_script('shortcuts-modal-script', plugins_url('../assets/js/shortcuts/shortcuts-modal.js', __FILE__), array('jquery'), filemtime(plugin_dir_path(__FILE__) . '../assets/js/shortcuts/shortcuts-modal.js'));

            // Create nonce once
            $nonce = wp_create_nonce('shortcuts_hub_nonce');
            
            // Enqueue delete script with its dependencies
            wp_enqueue_script('shortcut-delete-script', 
                plugins_url('../assets/js/shortcuts/shortcut-delete.js', __FILE__), 
                array('jquery'), 
                filemtime(plugin_dir_path(__FILE__) . '../assets/js/shortcuts/shortcut-delete.js')
            );

            // Localize script with required data
            wp_localize_script('shortcut-delete-script', 'shortcutsHubData', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'security' => $nonce
            ));

            wp_enqueue_script('shortcuts-versions-view-script', plugins_url('../assets/js/shortcuts/shortcuts-versions-view.js', __FILE__), array('jquery'), filemtime(plugin_dir_path(__FILE__) . '../assets/js/shortcuts/shortcuts-versions-view.js'));
            wp_enqueue_script('versions-handlers-script', plugins_url('../assets/js/versions/versions-handlers.js', __FILE__), array('jquery'), filemtime(plugin_dir_path(__FILE__) . '../assets/js/versions/versions-handlers.js'));
            wp_enqueue_script('versions-fetch-script', plugins_url('../assets/js/versions/versions-fetch.js', __FILE__), array('jquery'), filemtime(plugin_dir_path(__FILE__) . '../assets/js/versions/versions-fetch.js'));
            wp_localize_script('versions-fetch-script', 'shortcuts_hub_params', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => $nonce,
                'site_url' => get_site_url()
            ));
            wp_enqueue_script('versions-render-script', plugins_url('../assets/js/versions/versions-render.js', __FILE__), array('jquery'), filemtime(plugin_dir_path(__FILE__) . '../assets/js/versions/versions-render.js'));
            wp_enqueue_script('versions-filters-script', plugins_url('../assets/js/versions/versions-filters.js', __FILE__), array('jquery'), filemtime(plugin_dir_path(__FILE__) . '../assets/js/versions/versions-filters.js'));
            wp_enqueue_script('versions-modal-script', plugins_url('../assets/js/versions/versions-modal.js', __FILE__), array('jquery'), filemtime(plugin_dir_path(__FILE__) . '../assets/js/versions/versions-modal.js'));
            wp_enqueue_script('versions-delete-script', plugins_url('../assets/js/versions/versions-delete.js', __FILE__), array('jquery'), filemtime(plugin_dir_path(__FILE__) . '../assets/js/versions/versions-delete.js'));
            wp_enqueue_script('version-update-script', plugins_url('../assets/js/versions/version-update.js', __FILE__), array('jquery'), filemtime(plugin_dir_path(__FILE__) . '../assets/js/versions/version-update.js'));
            wp_enqueue_script('version-create-script', plugins_url('../assets/js/versions/version-create.js', __FILE__), array('jquery'), filemtime(plugin_dir_path(__FILE__) . '../assets/js/versions/version-create.js'));

            wp_localize_script('shortcuts-handlers-script', 'shortcutsHubData', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'security' => $nonce,
                'site_url' => get_site_url(),
                'post_id' => get_the_ID()
            ));

            wp_localize_script('shortcuts-render-script', 'shortcutsHubData', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'security' => $nonce,
                'site_url' => get_site_url(),
                'post_id' => get_the_ID()
            ));
            break;

        case 'shortcuts-hub_page_edit-shortcut':
            wp_enqueue_media();
            wp_enqueue_style('wp-color-picker');
            wp_enqueue_script('wp-color-picker');
            wp_enqueue_style('font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css');
            
            wp_enqueue_style('edit-shortcut-style', plugins_url('../assets/css/pages/edit-shortcut.css', __FILE__), array(), filemtime(plugin_dir_path(__FILE__) . '../assets/css/pages/edit-shortcut.css'));
            wp_enqueue_style('icon-selector-style', plugins_url('../assets/css/core/icon-selector.css', __FILE__), array(), filemtime(plugin_dir_path(__FILE__) . '../assets/css/core/icon-selector.css'));
            
            wp_enqueue_script('wp-color-picker');
            
            // Core scripts
            wp_enqueue_script('icon-selector', 
                plugins_url('../assets/js/core/icon-selector.js', __FILE__), 
                array('jquery'), 
                filemtime(plugin_dir_path(__FILE__) . '../assets/js/core/icon-selector.js')
            );

            // Create nonce once for this page
            $nonce = wp_create_nonce('shortcuts_hub_nonce');

            wp_enqueue_script('shortcut-update', 
                plugins_url('../assets/js/shortcuts/shortcut-update.js', __FILE__), 
                array('jquery'), 
                filemtime(plugin_dir_path(__FILE__) . '../assets/js/shortcuts/shortcut-update.js')
            );

            // Page-specific scripts with dependencies
            wp_enqueue_script('edit-shortcut', 
                plugins_url('../assets/js/pages/edit-shortcut.js', __FILE__), 
                array('jquery', 'wp-color-picker', 'icon-selector', 'shortcut-update'), 
                filemtime(plugin_dir_path(__FILE__) . '../assets/js/pages/edit-shortcut.js')
            );

            // Localize scripts with their required data
            wp_localize_script('shortcut-update', 'shortcutsHubData', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'security' => $nonce,
                'sb_api_url' => get_shortcuts_hub_settings()['sb_url']
            ));
            
            // Get WordPress uploads directory info
            $upload_dir = wp_upload_dir();
            
            wp_localize_script('edit-shortcut', 'shortcutsHubData', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'security' => $nonce,
                'sb_api_url' => get_shortcuts_hub_settings()['sb_url'],
                'uploads_url' => $upload_dir['baseurl'],
                'uploads_dir' => $upload_dir['basedir']
            ));
            break;

        case 'shortcuts-hub_page_add-shortcut':
            wp_enqueue_media();
            wp_enqueue_style('wp-color-picker');
            wp_enqueue_script('wp-color-picker');
            wp_enqueue_style('font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css');
            
            wp_enqueue_style('add-shortcut-style', plugins_url('../assets/css/pages/add-shortcut.css', __FILE__), array(), filemtime(plugin_dir_path(__FILE__) . '../assets/css/pages/add-shortcut.css'));
            wp_enqueue_style('icon-selector-style', plugins_url('../assets/css/core/icon-selector.css', __FILE__), array(), filemtime(plugin_dir_path(__FILE__) . '../assets/css/core/icon-selector.css'));
            
            wp_enqueue_script('icon-selector', plugins_url('../assets/js/core/icon-selector.js', __FILE__), array('jquery'), filemtime(plugin_dir_path(__FILE__) . '../assets/js/core/icon-selector.js'));
            wp_enqueue_script('shortcut-create', 
                plugins_url('../assets/js/shortcuts/shortcut-create.js', __FILE__), 
                array('jquery'), 
                filemtime(plugin_dir_path(__FILE__) . '../assets/js/shortcuts/shortcut-create.js')
            );

            // Localize script with required data
            wp_localize_script('shortcut-create', 'shortcutsHubData', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'security' => wp_create_nonce('shortcuts_hub_nonce'),
                'site_url' => get_site_url()
            ));

            wp_enqueue_script('add-shortcut', plugins_url('../assets/js/pages/add-shortcut.js', __FILE__), array('jquery', 'wp-color-picker', 'icon-selector', 'shortcut-create'), filemtime(plugin_dir_path(__FILE__) . '../assets/js/pages/add-shortcut.js'));
            
            // Get WordPress uploads directory info
            $upload_dir = wp_upload_dir();
            
            wp_localize_script('add-shortcut', 'shortcutsHubData', array(
                'uploads_url' => $upload_dir['baseurl'],
                'uploads_dir' => $upload_dir['basedir'],
                'site_url' => get_site_url()
            ));
            break;

        case 'shortcuts-hub_page_shortcuts-settings':
            wp_enqueue_style('settings-style', plugins_url('../assets/css/settings.css', __FILE__), array(), filemtime(plugin_dir_path(__FILE__) . '../assets/css/settings.css'));
            break;

        case 'shortcuts-hub_page_add-version':
            wp_enqueue_style('add-version-style', plugins_url('../assets/css/pages/add-version.css', __FILE__), array(), filemtime(plugin_dir_path(__FILE__) . '../assets/css/pages/add-version.css'));
            wp_enqueue_script('add-version-script', plugins_url('../assets/js/pages/add-version.js', __FILE__), array('jquery'), filemtime(plugin_dir_path(__FILE__) . '../assets/js/pages/add-version.js'), true);
            
            wp_enqueue_script('versions-fetch-script', plugins_url('../assets/js/versions/versions-fetch.js', __FILE__), array('jquery'), filemtime(plugin_dir_path(__FILE__) . '../assets/js/versions/versions-fetch.js'), true);
            wp_localize_script('versions-fetch-script', 'shortcuts_hub_params', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('shortcuts_hub_nonce'),
                'site_url' => get_site_url()
            ));
            wp_enqueue_script('versions-render-script', plugins_url('../assets/js/versions/versions-render.js', __FILE__), array('jquery'), filemtime(plugin_dir_path(__FILE__) . '../assets/js/versions/versions-render.js'), true);
            
            wp_localize_script('add-version-script', 'shortcutsHubData', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'security' => wp_create_nonce('shortcuts_hub_nonce'),
                'site_url' => get_site_url()
            ));
            break;

        case 'shortcuts-hub_page_edit-version':
            wp_enqueue_style('edit-version-style', plugins_url('../assets/css/pages/edit-version.css', __FILE__), array(), filemtime(plugin_dir_path(__FILE__) . '../assets/css/pages/edit-version.css'));
            wp_enqueue_script('edit-version-script', plugins_url('../assets/js/pages/edit-version.js', __FILE__), array('jquery'), filemtime(plugin_dir_path(__FILE__) . '../assets/js/pages/edit-version.js'), true);
            wp_enqueue_script('versions-fetch-script', plugins_url('../assets/js/versions/versions-fetch.js', __FILE__), array('jquery', 'edit-version-script'), filemtime(plugin_dir_path(__FILE__) . '../assets/js/versions/versions-fetch.js'), true);
            wp_localize_script('versions-fetch-script', 'shortcuts_hub_params', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('shortcuts_hub_nonce'),
                'site_url' => get_site_url()
            ));
            wp_enqueue_script('version-update-script', plugins_url('../assets/js/versions/version-update.js', __FILE__), array('jquery'), filemtime(plugin_dir_path(__FILE__) . '../assets/js/versions/version-update.js'), true);

            wp_localize_script('edit-version-script', 'shortcutsHubData', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'security' => wp_create_nonce('shortcuts_hub_nonce'),
                'site_url' => get_site_url()
            ));
            break;
    }

    // AJAX parameters for use across the site
    wp_localize_script('jquery', 'shortcuts_hub_params', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('shortcuts_hub_nonce')
    ));
}

add_action('admin_enqueue_scripts', 'shortcuts_hub_enqueue_assets');
