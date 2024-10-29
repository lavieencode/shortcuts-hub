<?php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

function shortcuts_hub_enqueue_assets($hook) {
    // Define paths for the CSS and JS files
    $general_css = plugin_dir_path(__FILE__) . '../assets/css/general.css';
    $shortcut_modal_css = plugin_dir_path(__FILE__) . '../assets/css/shortcuts/shortcut-modal.css';
    $shortcut_single_css = plugin_dir_path(__FILE__) . '../assets/css/shortcuts/shortcut-single.css';
    $shortcuts_display_css = plugin_dir_path(__FILE__) . '../assets/css/shortcuts/shortcuts-display.css';
    $edit_shortcut_css = plugin_dir_path(__FILE__) . '../assets/css/shortcuts/edit-shortcut.css';
    $add_shortcut_css = plugin_dir_path(__FILE__) . '../assets/css/shortcuts/add-shortcut.css';
    $settings_css = plugin_dir_path(__FILE__) . '../assets/css/settings.css';

    // Define paths for the version CSS files
    $version_modal_css = plugin_dir_path(__FILE__) . '../assets/css/versions/version-modal.css';
    $version_single_css = plugin_dir_path(__FILE__) . '../assets/css/versions/version-single.css';
    $versions_display_css = plugin_dir_path(__FILE__) . '../assets/css/versions/versions-display.css';

    $shortcuts_list_js = plugin_dir_path(__FILE__) . '../assets/js/shortcuts-list.js';
    $add_shortcut_js = plugin_dir_path(__FILE__) . '../assets/js/add-shortcut.js';
    $edit_shortcut_js = plugin_dir_path(__FILE__) . '../assets/js/edit-shortcut.js';
    $wp_shortcuts_list_js = plugin_dir_path(__FILE__) . '../assets/js/wp-shortcuts-list.js';

    // Enqueue styles and scripts based on the current admin page
    if (in_array($hook, ['shortcuts-hub_page_shortcuts-list', 'shortcuts-hub_page_add-shortcut', 'shortcuts-hub_page_edit-shortcut', 'shortcuts-hub_page_shortcuts-settings'])) {
        // Enqueue general styles for all Shortcuts Hub pages
        wp_enqueue_style(
            'general-styles',
            plugins_url('../assets/css/general.css', __FILE__),
            array(),
            filemtime($general_css)
        );
    }

    switch ($hook) {
        case 'shortcuts-hub_page_shortcuts-list':
            wp_enqueue_style(
                'shortcut-modal-style',
                plugins_url('../assets/css/shortcuts/shortcut-modal.css', __FILE__),
                array(),
                filemtime($shortcut_modal_css)
            );
            wp_enqueue_style(
                'shortcut-single-style',
                plugins_url('../assets/css/shortcuts/shortcut-single.css', __FILE__),
                array(),
                filemtime($shortcut_single_css)
            );
            wp_enqueue_style(
                'shortcuts-display-style',
                plugins_url('../assets/css/shortcuts/shortcuts-display.css', __FILE__),
                array(),
                filemtime($shortcuts_display_css)
            );

            // Enqueue version CSS files
            wp_enqueue_style(
                'version-modal-style',
                plugins_url('../assets/css/versions/version-modal.css', __FILE__),
                array(),
                filemtime($version_modal_css)
            );
            wp_enqueue_style(
                'version-single-style',
                plugins_url('../assets/css/versions/version-single.css', __FILE__),
                array(),
                filemtime($version_single_css)
            );
            wp_enqueue_style(
                'versions-display-style',
                plugins_url('../assets/css/versions/versions-display.css', __FILE__),
                array(),
                filemtime($versions_display_css)
            );

            wp_enqueue_script(
                'shortcuts-list-script',
                plugins_url('../assets/js/shortcuts-list.js', __FILE__),
                array('jquery'),
                filemtime($shortcuts_list_js)
            );
            wp_enqueue_script(
                'wp-shortcuts-list-script',
                plugins_url('../assets/js/wp-shortcuts-list.js', __FILE__),
                array('jquery'),
                filemtime($wp_shortcuts_list_js)
            );
            wp_localize_script('shortcuts-list-script', 'shortcutsHubData', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'security' => wp_create_nonce('shortcuts_hub_nonce'),
                'sb_url' => SB_URL,
                'token' => get_refresh_sb_token(),
                'site_url' => get_site_url()
            ));
            break;

        case 'shortcuts-hub_page_add-shortcut':
            wp_enqueue_style(
                'add-shortcut-style',
                plugins_url('../assets/css/shortcuts/add-shortcut.css', __FILE__),
                array(),
                filemtime($add_shortcut_css)
            );

            wp_enqueue_script(
                'add-shortcut-script',
                plugins_url('../assets/js/add-shortcut.js', __FILE__),
                array('jquery'),
                filemtime($add_shortcut_js)
            );

            wp_localize_script('add-shortcut-script', 'shortcutsHubData', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'security' => wp_create_nonce('shortcuts_hub_nonce'),
                'sb_url' => SB_URL,
                'token' => get_refresh_sb_token(),
                'site_url' => get_site_url()
            ));
            break;

        case 'shortcuts-hub_page_edit-shortcut':
            wp_enqueue_style(
                'edit-shortcut-style',
                plugins_url('../assets/css/shortcuts/edit-shortcut.css', __FILE__),
                array(),
                filemtime($edit_shortcut_css)
            );

            wp_enqueue_script(
                'edit-shortcut-script',
                plugins_url('../assets/js/edit-shortcut.js', __FILE__),
                array('jquery'),
                filemtime($edit_shortcut_js)
            );

            wp_localize_script('edit-shortcut-script', 'shortcutsHubData', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'security' => wp_create_nonce('shortcuts_hub_nonce'),
                'sb_url' => SB_URL,
                'token' => get_refresh_sb_token(),
                'site_url' => get_site_url()
            ));
            break;

        case 'shortcuts-hub_page_shortcuts-settings':
            wp_enqueue_style(
                'settings-style',
                plugins_url('../assets/css/settings.css', __FILE__),
                array(),
                filemtime($settings_css)
            );
            break;
    }
}

add_action('admin_enqueue_scripts', 'shortcuts_hub_enqueue_assets');
