<?php

if (!defined('ABSPATH')) {
    exit;
}

// Enqueue JavaScript and CSS with cache busting
function shortcuts_hub_enqueue_assets($hook) {
    if ('toplevel_page_shortcuts-hub' !== $hook) {
        return;
    }

    wp_enqueue_script('jquery');

    // Define paths for the CSS and JS files

    $shortcut_modal_css = plugin_dir_path(__FILE__) . '../assets/css/shortcuts/shortcut-modal.css';
    $shortcut_single_css = plugin_dir_path(__FILE__) . '../assets/css/shortcuts/shortcut-single.css';
    $shortcuts_display_css = plugin_dir_path(__FILE__) . '../assets/css/shortcuts/shortcuts-display.css';

    $version_modal_css = plugin_dir_path(__FILE__) . '../assets/css/versions/version-modal.css';
    $version_single_css = plugin_dir_path(__FILE__) . '../assets/css/versions/version-single.css';
    $versions_display_css = plugin_dir_path(__FILE__) . '../assets/css/versions/versions-display.css';

    $shortcuts_hub_js = plugin_dir_path(__FILE__) . '../assets/js/shortcuts-hub.js';

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
        'shortcuts-hub-script',
        plugins_url('../assets/js/shortcuts-hub.js', __FILE__),
        array('jquery'),
        filemtime($shortcuts_hub_js)
    );

    // Localize script for AJAX
    wp_localize_script('shortcuts-hub-script', 'shortcutsHubData', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'security' => wp_create_nonce('shortcuts_hub_nonce')
    ));
}

add_action('admin_enqueue_scripts', 'shortcuts_hub_enqueue_assets');