<?php
/**
 * Asset Management for Shortcuts Hub
 * Handles all script and style enqueuing for the plugin
 * 
 * !!! IMPORTANT - LOCALIZATION REQUIREMENTS !!!
 * The following localization pattern MUST be followed exactly for AJAX functionality to work:
 * 
 * 1. Script Dependencies and Order:
 *    - jQuery MUST be enqueued first
 *    - sh-debug.js MUST be enqueued second with jQuery dependency
 *    - All other scripts MUST depend on both jQuery and sh-debug
 * 
 * 2. Data Structure:
 *    shortcutsHubData = {
 *        debug: true,
 *        ajaxurl: admin_url('admin-ajax.php'),  // MUST use admin-ajax.php, not admin.php
 *        initialView: $view,
 *        shortcutId: $id,  // Required for version management
 *        security: {
 *            debug_log: wp_create_nonce('debug_log_nonce'),
 *            fetch_versions: wp_create_nonce('fetch_versions_nonce'),
 *            fetch_version: wp_create_nonce('fetch_version_nonce'),
 *            update_version: wp_create_nonce('update_version_nonce'),
 *            delete_version: wp_create_nonce('delete_version_nonce'),
 *            create_version: wp_create_nonce('create_version_nonce')
 *        }
 *    }
 * 
 * 3. Security Requirements:
 *    - ALWAYS use 'security' as the nonce parameter name in AJAX calls
 *    - ALWAYS verify nonces with check_ajax_referer('action_nonce', 'security')
 *    - NEVER use 'nonce' as a parameter name
 *    - Nonce actions MUST NOT include "shortcuts_hub_" as a prefix. They should just be named as they are, i.e., "update_action" versus "shortcuts_hub_update_action".
 * 
 * 4. AJAX Implementation:
 *    jQuery.ajax({
 *        url: shortcutsHubData.ajaxurl,  // MUST use this exact property
 *        method: 'POST',
 *        data: {
 *            action: 'your_action',
 *            security: shortcutsHubData.security.your_action
 *        }
 *    });
 * 
 * !!! DO NOT MODIFY THIS PATTERN UNLESS ABSOLUTELY NECESSARY !!!
 * The above pattern has been tested and verified to work correctly with all version management
 * functionality. Any deviations may cause AJAX or security issues.
 */

if (!defined('ABSPATH')) {
    exit;
}

// Core dependencies and debug script
add_action('wp_enqueue_scripts', 'shortcuts_hub_enqueue_core_scripts', 5);
add_action('admin_enqueue_scripts', 'shortcuts_hub_enqueue_core_scripts', 5);

function shortcuts_hub_enqueue_core_scripts() {
    static $core_scripts_loaded = false;
    
    // Prevent double loading
    if ($core_scripts_loaded) {
        return;
    }
    
    // Get view and ID from URL parameters for debug data
    $view = isset($_GET['view']) ? sanitize_text_field($_GET['view']) : 'shortcuts';
    $id = isset($_GET['id']) ? sanitize_text_field($_GET['id']) : '';

    wp_enqueue_script('jquery');
    wp_enqueue_script('sh-debug', 
        plugins_url('../assets/js/sh-debug.js', __FILE__), 
        array('jquery'), 
        SHORTCUTS_HUB_VERSION,
        true
    );

    // Initialize script data with debug info
    $script_data = array(
        'debug' => true,
        'ajaxurl' => admin_url('admin-ajax.php'),
        'initialView' => $view,
        'shortcutId' => $view === 'versions' ? $id : '',
        'security' => array(
            'debug_log' => wp_create_nonce('debug_log_nonce'),
            'fetch_versions' => wp_create_nonce('fetch_versions_nonce'),
            'fetch_version' => wp_create_nonce('fetch_version_nonce'),
            'update_version' => wp_create_nonce('update_version_nonce'),
            'delete_version' => wp_create_nonce('delete_version_nonce'),
            'create_version' => wp_create_nonce('create_version_nonce'),
            'fetch_actions' => wp_create_nonce('fetch_actions_nonce'),
            'add_action' => wp_create_nonce('add_action_nonce'),
            'update_action' => wp_create_nonce('update_action_nonce'),
            'delete_action' => wp_create_nonce('delete_action_nonce'),
            'fetch_shortcuts_for_action' => wp_create_nonce('fetch_shortcuts_for_action_nonce'),
            'update_action_shortcuts' => wp_create_nonce('update_action_shortcuts_nonce')
        )
    );

    // Localize script data
    wp_localize_script('sh-debug', 'shortcutsHubData', $script_data);
    
    $core_scripts_loaded = true;
}

function shortcuts_hub_enqueue_assets($hook) {
    // Enqueue core scripts first
    shortcuts_hub_enqueue_core_scripts();

    // Get the current screen
    $screen = get_current_screen();
    
    // Define allowed admin pages
    $allowed_screens = array(
        'toplevel_page_shortcuts-list',
        'shortcuts-hub_page_add-shortcut',
        'shortcuts-hub_page_edit-shortcut',
        'shortcuts-hub_page_add-version',
        'shortcuts-hub_page_settings',
        'shortcuts-hub_page_actions-manager',
        'shortcuts-hub_page_edit-version'
    );

    // Only load assets on our plugin pages
    if (!in_array($screen->id, $allowed_screens)) {
        return;
    }

    // Get view and ID from URL parameters
    $view = isset($_GET['view']) ? sanitize_text_field($_GET['view']) : 'shortcuts';
    $id = isset($_GET['id']) ? sanitize_text_field($_GET['id']) : '';

    // Font Awesome for icons
    wp_enqueue_style(
        'shortcuts-hub-fontawesome',
        'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css',
        array(),
        '5.15.4'
    );

    // Add preload for Font Awesome
    add_action('admin_head', function() {
        echo '<link rel="preload" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" as="style">';
        echo '<link rel="preload" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/webfonts/fa-solid-900.woff2" as="font" type="font/woff2" crossorigin>';
        echo '<link rel="preload" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/webfonts/fa-regular-400.woff2" as="font" type="font/woff2" crossorigin>';
        echo '<link rel="preload" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/webfonts/fa-brands-400.woff2" as="font" type="font/woff2" crossorigin>';
    });

    // Page-specific assets
    if ($screen->id === 'toplevel_page_shortcuts-list') {
        // Core styles
        wp_enqueue_style(
            'shortcuts-hub-general',
            plugins_url('../assets/css/general.css', __FILE__),
            array('shortcuts-hub-fontawesome'),
            SHORTCUTS_HUB_VERSION . '.' . time()
        );

        // Shortcuts styles
        wp_enqueue_style(
            'shortcuts-hub-display',
            plugins_url('../assets/css/shortcuts/shortcuts-display.css', __FILE__),
            array('shortcuts-hub-general', 'shortcuts-hub-fontawesome'),
            SHORTCUTS_HUB_VERSION . '.' . time()
        );

        wp_enqueue_style(
            'shortcuts-hub-single',
            plugins_url('../assets/css/shortcuts/shortcut-single.css', __FILE__),
            array('shortcuts-hub-general', 'shortcuts-hub-fontawesome'),
            SHORTCUTS_HUB_VERSION . '.' . time(),
            'all'
        );

        wp_enqueue_style(
            'shortcuts-hub-modal',
            plugins_url('../assets/css/shortcuts/shortcut-modal.css', __FILE__),
            array('shortcuts-hub-general'),
            SHORTCUTS_HUB_VERSION . '.' . time()
        );

        wp_enqueue_style(
            'shortcuts-hub-icon-selector',
            plugins_url('../assets/css/core/icon-selector.css', __FILE__),
            array('shortcuts-hub-general'),
            SHORTCUTS_HUB_VERSION . '.' . time()
        );

        wp_enqueue_style(
            'shortcuts-hub-color-selector',
            plugins_url('../assets/css/core/color-selector.css', __FILE__),
            array('shortcuts-hub-general'),
            SHORTCUTS_HUB_VERSION . '.' . time()
        );

        // Versions styles
        wp_enqueue_style(
            'shortcuts-hub-versions-display',
            plugins_url('../assets/css/versions/versions-display.css', __FILE__),
            array(),
            SHORTCUTS_HUB_VERSION . '.' . time()
        );

        wp_enqueue_style(
            'shortcuts-hub-version-single',
            plugins_url('../assets/css/versions/version-single.css', __FILE__),
            array(),
            SHORTCUTS_HUB_VERSION . '.' . time()
        );

        wp_enqueue_style(
            'shortcuts-hub-version-modal',
            plugins_url('../assets/css/versions/version-modal.css', __FILE__),
            array(),
            SHORTCUTS_HUB_VERSION . '.' . time()
        );

        // Core scripts
        // sh-debug.js is already registered at the top level

        // Create script data array
        $script_data = array(
            'initialView' => $view,
            'shortcutId' => $view === 'versions' ? $id : '',
            'ajaxurl' => admin_url('admin-ajax.php'),
            'security' => array(
                'debug_log' => wp_create_nonce('debug_log_nonce'),
                'fetch_versions' => wp_create_nonce('fetch_versions_nonce'),
                'fetch_version' => wp_create_nonce('fetch_version_nonce'),
                'update_version' => wp_create_nonce('update_version_nonce'),
                'delete_version' => wp_create_nonce('delete_version_nonce'),
                'create_version' => wp_create_nonce('create_version_nonce'),
                'fetch_actions' => wp_create_nonce('fetch_actions_nonce'),
                'add_action' => wp_create_nonce('add_action_nonce'),
                'update_action' => wp_create_nonce('update_action_nonce'),
                'delete_action' => wp_create_nonce('delete_action_nonce'),
                'fetch_shortcuts_for_action' => wp_create_nonce('fetch_shortcuts_for_action_nonce'),
                'update_action_shortcuts' => wp_create_nonce('update_action_shortcuts_nonce')
            )
        );

        // Localize to sh-debug first to ensure it's available globally
        wp_localize_script('sh-debug', 'shortcutsHubData', $script_data);

        // Versions scripts - Core functionality first
        wp_enqueue_script(
            'shortcuts-hub-versions-render',
            plugins_url('../assets/js/versions/versions-render.js', __FILE__),
            array('jquery', 'sh-debug'),
            SHORTCUTS_HUB_VERSION . '.' . time(),
            true
        );

        wp_enqueue_script(
            'shortcuts-hub-versions-fetch',
            plugins_url('../assets/js/versions/versions-fetch.js', __FILE__),
            array('jquery', 'sh-debug', 'shortcuts-hub-versions-render'),
            SHORTCUTS_HUB_VERSION . '.' . time(),
            true
        );

        wp_enqueue_script(
            'shortcuts-hub-versions-handlers',
            plugins_url('../assets/js/versions/versions-handlers.js', __FILE__),
            array('jquery', 'sh-debug', 'shortcuts-hub-versions-render', 'shortcuts-hub-versions-fetch'),
            SHORTCUTS_HUB_VERSION . '.' . time(),
            true
        );

        wp_enqueue_script(
            'shortcuts-hub-versions-view',
            plugins_url('../assets/js/shortcuts/shortcuts-versions-view.js', __FILE__),
            array('jquery', 'sh-debug', 'shortcuts-hub-versions-handlers'),
            SHORTCUTS_HUB_VERSION . '.' . time(),
            true
        );

        wp_enqueue_script(
            'shortcuts-hub-version-update',
            plugins_url('../assets/js/versions/version-update.js', __FILE__),
            array('jquery', 'sh-debug', 'shortcuts-hub-versions-handlers'),
            SHORTCUTS_HUB_VERSION . '.' . time(),
            true
        );

        wp_enqueue_script(
            'shortcuts-hub-versions-delete',
            plugins_url('../assets/js/versions/versions-delete.js', __FILE__),
            array('jquery', 'sh-debug', 'shortcuts-hub-versions-handlers'),
            SHORTCUTS_HUB_VERSION . '.' . time(),
            true
        );

        wp_enqueue_script(
            'shortcuts-hub-versions-modal',
            plugins_url('../assets/js/versions/versions-modal.js', __FILE__),
            array('jquery', 'sh-debug', 'shortcuts-hub-versions-handlers'),
            SHORTCUTS_HUB_VERSION . '.' . time(),
            true
        );

        wp_enqueue_script(
            'shortcuts-hub-version-create',
            plugins_url('../assets/js/versions/version-create.js', __FILE__),
            array('jquery', 'sh-debug', 'shortcuts-hub-versions-modal'),
            SHORTCUTS_HUB_VERSION . '.' . time(),
            true
        );

        wp_enqueue_script(
            'shortcuts-hub-versions-filters',
            plugins_url('../assets/js/versions/versions-filters.js', __FILE__),
            array('jquery', 'sh-debug', 'shortcuts-hub-versions-handlers'),
            SHORTCUTS_HUB_VERSION . '.' . time(),
            true
        );

        // Shortcuts scripts
        wp_enqueue_script('shortcuts-hub-shortcuts-modal', 
            plugins_url('../assets/js/shortcuts/shortcuts-modal.js', __FILE__), 
            array('jquery', 'sh-debug'), 
            SHORTCUTS_HUB_VERSION . '.' . time(),
            true
        );

        wp_enqueue_script('shortcuts-hub-icon-selector', 
            plugins_url('../assets/js/core/icon-selector.js', __FILE__), 
            array('jquery', 'sh-debug'), 
            SHORTCUTS_HUB_VERSION . '.' . time(),
            true
        );

        wp_enqueue_script('shortcuts-hub-color-selector', 
            plugins_url('../assets/js/core/color-selector.js', __FILE__), 
            array('jquery', 'sh-debug'), 
            SHORTCUTS_HUB_VERSION . '.' . time(),
            true
        );

        wp_enqueue_script('shortcuts-hub-shortcut-create', 
            plugins_url('../assets/js/shortcuts/shortcut-create.js', __FILE__), 
            array('jquery', 'sh-debug'), 
            SHORTCUTS_HUB_VERSION . '.' . time(),
            true
        );

        wp_enqueue_script('shortcuts-hub-shortcut-delete', 
            plugins_url('../assets/js/shortcuts/shortcut-delete.js', __FILE__), 
            array('jquery', 'sh-debug'), 
            SHORTCUTS_HUB_VERSION . '.' . time(),
            true
        );

        wp_enqueue_script('shortcuts-hub-shortcuts-handlers', 
            plugins_url('../assets/js/shortcuts/shortcuts-handlers.js', __FILE__), 
            array('jquery', 'sh-debug', 'shortcuts-hub-icon-selector', 'shortcuts-hub-shortcut-delete'), 
            SHORTCUTS_HUB_VERSION . '.' . time(),
            true
        );

        wp_enqueue_script('shortcuts-hub-shortcuts-render', 
            plugins_url('../assets/js/shortcuts/shortcuts-render.js', __FILE__), 
            array('jquery', 'sh-debug', 'shortcuts-hub-shortcuts-handlers'), 
            SHORTCUTS_HUB_VERSION . '.' . time(),
            true
        );

        wp_enqueue_script('shortcuts-hub-shortcuts-fetch', 
            plugins_url('../assets/js/shortcuts/shortcuts-fetch.js', __FILE__), 
            array('jquery', 'sh-debug', 'shortcuts-hub-shortcuts-render'), 
            SHORTCUTS_HUB_VERSION . '.' . time(),
            true
        );

        wp_enqueue_script('shortcuts-hub-shortcuts-filters', 
            plugins_url('../assets/js/shortcuts/shortcuts-filters.js', __FILE__), 
            array('jquery', 'sh-debug', 'shortcuts-hub-shortcuts-fetch'), 
            SHORTCUTS_HUB_VERSION . '.' . time(),
            true
        );

        wp_enqueue_script('shortcuts-hub-shortcuts-versions-view', 
            plugins_url('../assets/js/shortcuts/shortcuts-versions-view.js', __FILE__), 
            array('jquery', 'sh-debug', 'shortcuts-hub-versions-handlers'), 
            SHORTCUTS_HUB_VERSION . '.' . time(),
            true
        );

        wp_enqueue_script('shortcuts-hub-list-page', 
            plugins_url('../assets/js/pages/shortcuts-list.js', __FILE__), 
            array('jquery', 'sh-debug', 'shortcuts-hub-shortcuts-fetch', 'shortcuts-hub-shortcuts-render', 'shortcuts-hub-versions-fetch'), 
            SHORTCUTS_HUB_VERSION . '.' . time(),
            true
        );

        // Security nonces
        $security = array(
            'toggle_view' => wp_create_nonce('toggle_view_nonce'),
            'delete_shortcut' => wp_create_nonce('delete_shortcut_nonce'),
            'duplicate_shortcut' => wp_create_nonce('duplicate_shortcut_nonce'),
            'fetch_shortcuts' => wp_create_nonce('fetch_shortcuts_nonce'),
            'filter_shortcuts' => wp_create_nonce('filter_shortcuts_nonce'),
            'fetch_versions' => wp_create_nonce('fetch_versions_nonce'),
            'fetch_version' => wp_create_nonce('fetch_version_nonce'),
            'delete_version' => wp_create_nonce('delete_version_nonce'),
            'update_version' => wp_create_nonce('update_version_nonce'),
            'create_version' => wp_create_nonce('create_version_nonce'),
            'create_shortcut' => wp_create_nonce('create_shortcut_nonce'),
            'fetch_shortcuts_for_action' => wp_create_nonce('fetch_shortcuts_for_action_nonce'),
            'update_action_shortcuts' => wp_create_nonce('update_action_shortcuts_nonce')
        );

        // Localize script with all required data
        $shortcuts_hub_data = array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'security' => array_merge((array) $script_data['security'], (array) $security),
            'site_url' => get_site_url(),
            'isElementorActive' => defined('ELEMENTOR_VERSION'),
            'isWooCommerceActive' => class_exists('WooCommerce'),
            'view' => $view,
            'shortcutId' => $id,
            'initialView' => $view
        );

        // Localize script data
        wp_localize_script('shortcuts-hub-list-page', 'shortcutsHubData', $shortcuts_hub_data);
    } elseif ($screen->id === 'shortcuts-hub_page_add-shortcut' || $screen->id === 'shortcuts-hub_page_edit-shortcut') {
        // Scripts
        wp_enqueue_script(
            'shortcuts-hub-edit',
            plugins_url('../assets/js/pages/shortcut-edit.js', __FILE__),
            array('jquery', 'sh-debug'),
            SHORTCUTS_HUB_VERSION . '.' . time(),
            true
        );

        // Icon selector assets
        wp_enqueue_style(
            'shortcuts-hub-icon-selector',
            plugins_url('../assets/css/core/icon-selector.css', __FILE__),
            array(),
            SHORTCUTS_HUB_VERSION . '.' . time()
        );

        wp_enqueue_script(
            'shortcuts-hub-icon-selector',
            plugins_url('../assets/js/core/icon-selector.js', __FILE__),
            array('jquery'),
            SHORTCUTS_HUB_VERSION . '.' . time(),
            true
        );

        // Get existing shortcutsHubData
        $existing_data = wp_scripts()->get_data('sh-debug', 'data');
        
        // Initialize script data with all necessary nonces
        $script_data = array(
            'debug_log' => wp_create_nonce('debug_log_nonce'),
            'create_action' => wp_create_nonce('create_action_nonce'),
            'update_action' => wp_create_nonce('update_action_nonce'),
            'delete_action' => wp_create_nonce('delete_action_nonce'),
            'fetch_actions' => wp_create_nonce('fetch_actions_nonce'),
            'fetch_shortcuts_for_action' => wp_create_nonce('fetch_shortcuts_for_action_nonce'),
            'update_action_shortcuts' => wp_create_nonce('update_action_shortcuts_nonce')
        );

        if ($existing_data) {
            // Extract existing data
            $existing_data = trim(str_replace('var shortcutsHubData = ', '', rtrim($existing_data, ';')));
            $existing_data = json_decode($existing_data, true);
            
            // Preserve security tokens
            if (isset($existing_data['security'])) {
                $script_data = array_merge(
                    (array) $existing_data['security'],
                    $script_data
                );
            }
        }

        // Debug script data
        $debug_data = array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'security' => $script_data
        );
        wp_localize_script('sh-debug', 'shortcutsHubData', $debug_data);

        // Actions script data
        $actions_data = array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'security' => $script_data,
            'site_url' => get_site_url(),
            'isElementorActive' => defined('ELEMENTOR_VERSION'),
            'isWooCommerceActive' => class_exists('WooCommerce'),
            'view' => $view,
            'shortcutId' => $id,
            'initialView' => $view
        );
        wp_localize_script('shortcuts-hub-edit', 'shortcutsHubData', $actions_data);
    } elseif ($screen->id === 'shortcuts-hub_page_actions-manager') {
        // Styles
        wp_enqueue_style(
            'shortcuts-hub-general',
            plugins_url('../assets/css/general.css', __FILE__),
            array('shortcuts-hub-fontawesome'),
            SHORTCUTS_HUB_VERSION . '.' . time()
        );

        wp_enqueue_style(
            'shortcuts-hub-icon-selector',
            plugins_url('../assets/css/core/icon-selector.css', __FILE__),
            array('shortcuts-hub-general'),
            SHORTCUTS_HUB_VERSION . '.' . time()
        );

        wp_enqueue_style(
            'shortcuts-hub-actions',
            plugins_url('../assets/css/pages/actions-manager.css', __FILE__),
            array('shortcuts-hub-general', 'shortcuts-hub-icon-selector'),
            SHORTCUTS_HUB_VERSION . '.' . time()
        );

        // Enqueue WordPress Media Library scripts
        wp_enqueue_media();
        
        // Scripts
        wp_enqueue_script(
            'shortcuts-hub-icon-selector',
            plugins_url('../assets/js/core/icon-selector.js', __FILE__),
            array('jquery', 'sh-debug'),
            SHORTCUTS_HUB_VERSION . '.' . time(),
            true
        );

        wp_enqueue_script(
            'shortcuts-hub-actions',
            plugins_url('../assets/js/pages/actions-manager.js', __FILE__),
            array('jquery', 'sh-debug', 'shortcuts-hub-icon-selector'),
            SHORTCUTS_HUB_VERSION . '.' . time(),
            true
        );

        // Get existing shortcutsHubData
        $existing_data = wp_scripts()->get_data('sh-debug', 'data');
        
        // Initialize script data with all necessary nonces
        $script_data = array(
            'debug_log' => wp_create_nonce('debug_log_nonce'),
            'create_action' => wp_create_nonce('create_action_nonce'),
            'update_action' => wp_create_nonce('update_action_nonce'),
            'delete_action' => wp_create_nonce('delete_action_nonce'),
            'fetch_actions' => wp_create_nonce('fetch_actions_nonce'),
            'fetch_shortcuts_for_action' => wp_create_nonce('fetch_shortcuts_for_action_nonce'),
            'update_action_shortcuts' => wp_create_nonce('update_action_shortcuts_nonce')
        );

        if ($existing_data) {
            // Extract existing data
            $existing_data = trim(str_replace('var shortcutsHubData = ', '', rtrim($existing_data, ';')));
            $existing_data = json_decode($existing_data, true);
            
            // Preserve security tokens
            if (isset($existing_data['security'])) {
                $script_data = array_merge(
                    (array) $existing_data['security'],
                    $script_data
                );
            }
        }

        // Debug script data
        $debug_data = array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'security' => $script_data
        );
        wp_localize_script('sh-debug', 'shortcutsHubData', $debug_data);

        // Actions script data
        $actions_data = array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'security' => $script_data,
            'site_url' => get_site_url(),
            'admin_url' => admin_url('admin.php'),
            'isElementorActive' => defined('ELEMENTOR_VERSION'),
            'isWooCommerceActive' => class_exists('WooCommerce'),
            'view' => $view,
            'shortcutId' => $id,
            'initialView' => $view
        );
        wp_localize_script('shortcuts-hub-actions', 'shortcutsHubData', $actions_data);
    } elseif ($screen->id === 'shortcuts-hub_page_settings') {
        // Scripts
        wp_enqueue_script(
            'shortcuts-hub-settings',
            plugins_url('../assets/js/pages/settings.js', __FILE__),
            array('jquery', 'sh-debug'),
            SHORTCUTS_HUB_VERSION . '.' . time(),
            true
        );

        // Security nonces for settings page
        $security = array(
            'save_settings' => wp_create_nonce('save_settings_nonce')
        );

        // Localize script
        wp_localize_script('shortcuts-hub-settings', 'shortcutsHubData', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'security' => $security
        ));

        // Styles
        wp_enqueue_style(
            'shortcuts-hub-settings',
            plugins_url('../assets/css/core/settings.css', __FILE__),
            array(),
            SHORTCUTS_HUB_VERSION . '.' . time()
        );
    }
}
add_action('admin_enqueue_scripts', 'shortcuts_hub_enqueue_assets');
