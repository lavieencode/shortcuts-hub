<?php

if (!defined('ABSPATH')) {
    exit;
}

function shortcuts_hub_enqueue_core() {
    require_once SHORTCUTS_HUB_PATH . 'core/download-button.php';
    require_once SHORTCUTS_HUB_PATH . 'core/downloads-db.php';
    require_once SHORTCUTS_HUB_PATH . 'core/log-downloads.php';
    require_once SHORTCUTS_HUB_PATH . 'core/elementor-dynamic-fields.php';
}

add_action('elementor/widgets/widgets_registered', 'shortcuts_hub_enqueue_core');

function shortcuts_hub_enqueue_editor_assets() {
    // Enqueue styles and scripts specific to Elementor editor
    wp_enqueue_style('download-button-style', plugins_url('../assets/css/core/download-button.css', __FILE__), array(), filemtime(plugin_dir_path(__FILE__) . '../assets/css/core/download-button.css'));
    wp_enqueue_script('shortcuts-download-button', plugins_url('../assets/js/core/download-button.js', __FILE__), array('jquery'), filemtime(plugin_dir_path(__FILE__) . '../assets/js/core/download-button.js'), true);
}

add_action('elementor/editor/after_enqueue_scripts', 'shortcuts_hub_enqueue_editor_assets');
