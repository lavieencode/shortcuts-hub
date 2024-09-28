<?php

if (!defined('ABSPATH')) {
    exit;
}

// Enqueue JavaScript and CSS with cache busting
function shortcuts_hub_enqueue_assets($hook) {
    if ('toplevel_page_shortcuts-hub' !== $hook) {
        return;
    }

    // Enqueue jQuery
    wp_enqueue_script('jquery');

    // Define paths for the CSS and JS files

    // Shortcuts CSS Files
    $shortcut_modal_css = plugin_dir_path(__FILE__) . '../assets/css/shortcuts/shortcut-modal.css';
    $shortcut_single_css = plugin_dir_path(__FILE__) . '../assets/css/shortcuts/shortcut-single.css';
    $shortcuts_display_css = plugin_dir_path(__FILE__) . '../assets/css/shortcuts/shortcuts-display.css';

    // Versions CSS Files
    $version_modal_css = plugin_dir_path(__FILE__) . '../assets/css/versions/version-modal.css';
    $version_single_css = plugin_dir_path(__FILE__) . '../assets/css/versions/version-single.css';
    $versions_display_css = plugin_dir_path(__FILE__) . '../assets/css/versions/versions-display.css';

    // Shortcuts JS Files
    $fetch_display_shortcuts_js = plugin_dir_path(__FILE__) . '../assets/js/shortcuts/fetch-display-shortcuts.js';
    $shortcuts_modal_js = plugin_dir_path(__FILE__) . '../assets/js/shortcuts/shortcut-modal.js';

    // Versions JS Files
    $fetch_display_versions_js = plugin_dir_path(__FILE__) . '../assets/js/versions/fetch-display-versions.js';
    $versions_modal_js = plugin_dir_path(__FILE__) . '../assets/js/versions/version-modal.js';

    // Enqueue Shortcuts CSS with cache busting
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

    // Enqueue Versions CSS with cache busting
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

    // Enqueue Shortcuts JavaScript files with cache busting
    wp_enqueue_script(
        'fetch-display-shortcuts-script',
        plugins_url('../assets/js/shortcuts/fetch-display-shortcuts.js', __FILE__),
        array('jquery'),
    );
    wp_enqueue_script(
        'shortcut-modal-script',
        plugins_url('../assets/js/shortcuts/shortcut-modal.js', __FILE__),
        array('jquery'),
    );

    // Enqueue Versions JavaScript files with cache busting
    wp_enqueue_script(
        'fetch-display-versions-script',
        plugins_url('../assets/js/versions/fetch-display-versions.js', __FILE__),
        array('jquery'),
    );
    wp_enqueue_script(
        'version-modal-script',
        plugins_url('../assets/js/versions/version-modal.js', __FILE__),
        array('jquery'),
    );

    // Localize script for AJAX
    wp_localize_script('fetch-display-shortcuts-script', 'shortcutsHubData', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'security' => wp_create_nonce('shortcuts_hub_nonce')
    ));
}

add_action('admin_enqueue_scripts', 'shortcuts_hub_enqueue_assets');