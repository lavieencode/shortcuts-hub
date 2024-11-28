<?php

namespace ShortcutsHub\Elementor;

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
        
        add_action('elementor/elements/categories_registered', [$this, 'register_widget_categories']);
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

    public function register_frontend_scripts() {
        wp_register_script(
            'shortcuts-hub-download-log',
            plugins_url('/assets/js/widgets/download-log.js', dirname(dirname(__FILE__))),
            ['jquery'],
            filemtime(plugin_dir_path(dirname(dirname(__FILE__))) . 'assets/js/widgets/download-log.js'),
            true
        );

        wp_register_script(
            'shortcuts-hub-my-account',
            plugins_url('/assets/js/widgets/my-account.js', dirname(dirname(__FILE__))),
            ['jquery', 'elementor-frontend'],
            filemtime(plugin_dir_path(dirname(dirname(__FILE__))) . 'assets/js/widgets/my-account.js'),
            true
        );

        wp_localize_script('shortcuts-hub-download-log', 'shortcuts_hub_params', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('shortcuts_hub_nonce')
        ]);

        // If in editor, enqueue immediately
        if (\Elementor\Plugin::$instance->editor->is_edit_mode()) {
            wp_enqueue_script('shortcuts-hub-my-account');
        }
    }

    public function register_frontend_styles() {
        wp_register_style(
            'shortcuts-hub-download-log',
            plugins_url('/assets/css/widgets/download-log.css', dirname(dirname(__FILE__))),
            [],
            filemtime(plugin_dir_path(dirname(dirname(__FILE__))) . 'assets/css/widgets/download-log.css')
        );
    }

    public function register_widget_categories($elements_manager) {
        $elements_manager->add_category(
            'shortcuts-hub',
            [
                'title' => esc_html__('Shortcuts Hub', 'shortcuts-hub'),
                'icon' => 'fa fa-plug',
            ]
        );
    }

    public function register_widgets($widgets_manager) {
        error_log('Attempting to register Shortcuts Hub widgets...');
        
        if (!did_action('elementor/loaded')) {
            error_log('Elementor not loaded yet, aborting widget registration');
            return;
        }

        $widgets = [
            Download_Button::class,
            Download_Log::class,
            My_Account_Widget::class,
        ];

        foreach ($widgets as $widget_class) {
            try {
                $widgets_manager->register(new $widget_class());
                error_log("Successfully registered widget: $widget_class");
            } catch (\Exception $e) {
                error_log("Failed to register widget $widget_class: " . $e->getMessage());
            }
        }
    }
}
