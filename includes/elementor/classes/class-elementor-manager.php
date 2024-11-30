<?php

namespace ShortcutsHub\Elementor;

use Elementor\Plugin;
use ShortcutsHub\Elementor\Widgets\Download_Button;
use ShortcutsHub\Elementor\Widgets\Download_Log;
use ShortcutsHub\Elementor\Widgets\My_Account_Widget;

class Elementor_Manager {
    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        // Load widget files
        $this->load_widget_files();
        
        // Register widgets using Elementor's registration hook
        add_action('elementor/widgets/register', [$this, 'register_widgets']);
        
        // Register scripts for both frontend and editor
        add_action('elementor/frontend/after_register_scripts', [$this, 'register_frontend_scripts']);
        add_action('elementor/frontend/after_register_styles', [$this, 'register_frontend_styles']);
    }

    private function load_widget_files() {
        $widget_files = [
            'download-button-widget.php',
            'download-log-widget.php',
            'my-account-widget.php'
        ];

        foreach ($widget_files as $file) {
            $file_path = plugin_dir_path(dirname(__FILE__)) . 'widgets/' . $file;
            if (file_exists($file_path)) {
                require_once $file_path;
            }
        }
    }

    public function register_widgets($widgets_manager) {
        // Ensure our category exists
        $categories = \Elementor\Plugin::$instance->elements_manager->get_categories();
        if (!isset($categories['shortcuts-hub'])) {
            $this->register_widget_category(\Elementor\Plugin::$instance->elements_manager);
        }

        // Only register My Account widget if Elementor Pro is active
        if (class_exists('\ElementorPro\Plugin')) {
            $widgets = [
                Download_Button::class,
                Download_Log::class,
                My_Account_Widget::class,
            ];
        } else {
            $widgets = [
                Download_Button::class,
                Download_Log::class,
            ];
        }

        foreach ($widgets as $widget_class) {
            try {
                $widgets_manager->register(new $widget_class());
            } catch (\Exception $e) {
                error_log("Failed to register widget $widget_class: " . $e->getMessage());
            }
        }
    }

    public function register_frontend_scripts() {
        // Download Log scripts
        wp_register_script(
            'shortcuts-hub-download-log',
            plugins_url('/assets/js/widgets/download-log.js', dirname(dirname(dirname(__FILE__)))),
            ['jquery'],
            filemtime(plugin_dir_path(dirname(dirname(dirname(__FILE__)))) . 'assets/js/widgets/download-log.js'),
            true
        );

        wp_localize_script('shortcuts-hub-download-log', 'shortcuts_hub_params', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('shortcuts_hub_nonce')
        ]);

        // If in editor, enqueue immediately
        if (\Elementor\Plugin::$instance->editor->is_edit_mode()) {
            // Currently using only parent widget scripts
        }
    }

    public function register_frontend_styles() {
        // Download Log styles
        wp_register_style(
            'shortcuts-hub-download-log',
            plugins_url('/assets/css/widgets/download-log.css', dirname(dirname(dirname(__FILE__)))),
            [],
            filemtime(plugin_dir_path(dirname(dirname(dirname(__FILE__)))) . 'assets/css/widgets/download-log.css')
        );
    }

    public function register_widget_category($elements_manager) {
        $elements_manager->add_category(
            'shortcuts-hub',
            [
                'title' => esc_html__('Shortcuts Hub', 'shortcuts-hub'),
                'icon' => 'fa fa-plug',
                'active' => true,
                'priority' => 1, // Lower number = higher in the list
            ]
        );
    }
}
