<?php
namespace ShortcutsHub\Elementor;

use Elementor\Plugin;
use ShortcutsHub\Elementor\Widgets\Download_Button_Widget;
use ShortcutsHub\Elementor\Widgets\Download_Log;
use ShortcutsHub\Elementor\Widgets\My_Account_Widget;
use ShortcutsHub\Elementor\Widgets\Shortcuts_Icon_Widget;
use ShortcutsHub\Elementor\Dynamic_Tags;

class Elementor_Manager {
    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        // Load dynamic fields first
        require_once plugin_dir_path(dirname(__FILE__)) . 'elementor-dynamic-fields.php';

        try {
            // Try to register category immediately if Elementor is loaded
            if (did_action('elementor/loaded')) {
                $this->register_widget_category();
            } else {
                // If Elementor isn't loaded yet, hook into its initialization
                add_action('elementor/loaded', [$this, 'register_widget_category']);
            }
        } catch (\Exception $e) {
            // Silent fail - Elementor might not be active
        }

        // Load widget files
        $this->load_widget_files();
        
        // Register widgets after category (priority 10)
        add_action('elementor/widgets/register', [$this, 'register_widgets'], 10);
        
        // Register dynamic tags (priority 10)
        add_action('elementor/dynamic_tags/register', [$this, 'register_dynamic_tags'], 10);
        
        // Register frontend assets
        add_action('elementor/frontend/after_register_scripts', [$this, 'register_frontend_scripts']);
        add_action('elementor/frontend/after_register_styles', [$this, 'register_frontend_styles']);
    }

    public function load_widget_files() {
        $widget_files = [
            'download-button-widget.php',
            'download-log-widget.php',
            'my-account-widget.php',
            'icon-widget.php'
        ];

        foreach ($widget_files as $file) {
            $file_path = plugin_dir_path(dirname(__FILE__)) . 'widgets/' . $file;
            if (file_exists($file_path)) {
                require_once $file_path;
            }
        }
    }

    public function register_widgets($widgets_manager) {
        // Register all widgets
        $widgets = [
            'Download_Button' => Widgets\Download_Button_Widget::class,
            'Download_Log' => Widgets\Download_Log::class,
            'My_Account_Widget' => Widgets\My_Account_Widget::class,
            'Shortcuts_Icon_Widget' => Widgets\Shortcuts_Icon_Widget::class
        ];

        foreach ($widgets as $name => $widget_class) {
            try {
                if (class_exists($widget_class)) {
                    $widget = new $widget_class();
                    $widgets_manager->register($widget);
                }
            } catch (\Exception $e) {
                // Silent fail - individual widget registration failure shouldn't break the site
            }
        }
    }

    public function register_dynamic_tags($dynamic_tags_manager) {
        // Register Dynamic Tag Group
        try {
            $dynamic_tags_manager->register_group(
                'shortcut_fields',
                [
                    'title' => esc_html__('Shortcut Fields', 'shortcuts-hub')
                ]
            );
        } catch (\Exception $e) {
            // Silent fail - group registration failure shouldn't break the site
        }

        // Register all dynamic tags
        $dynamic_tags = [
            'ShortcutsHub\Elementor\DynamicTags\Name_Dynamic_Tag',
            'ShortcutsHub\Elementor\DynamicTags\Headline_Dynamic_Tag',
            'ShortcutsHub\Elementor\DynamicTags\Description_Dynamic_Tag',
            'ShortcutsHub\Elementor\DynamicTags\Color_Dynamic_Tag',
            'ShortcutsHub\Elementor\DynamicTags\Icon_Dynamic_Tag',
            'ShortcutsHub\Elementor\DynamicTags\Input_Dynamic_Tag',
            'ShortcutsHub\Elementor\DynamicTags\Result_Dynamic_Tag',
            'ShortcutsHub\Elementor\DynamicTags\Latest_Version_URL_Dynamic_Tag'
        ];

        foreach ($dynamic_tags as $tag) {
            try {
                if (class_exists($tag)) {
                    $tag_instance = new $tag();
                    $dynamic_tags_manager->register($tag_instance);
                }
            } catch (\Exception $e) {
                // Silent fail - individual tag registration failure shouldn't break the site
            }
        }
    }

    public function register_widget_category() {
        try {
            if (!class_exists('\Elementor\Plugin')) {
                return;
            }

            $elements_manager = \Elementor\Plugin::instance()->elements_manager;
            if (!$elements_manager) {
                return;
            }
            
            $category_args = [
                'title' => esc_html__('Shortcuts Hub', 'shortcuts-hub'),
                'icon' => 'fa fa-plug',
                'position' => 1,
            ];
            
            $elements_manager->add_category('shortcuts-hub', $category_args);
            
        } catch (\Exception $e) {
            // Silent fail - category registration failure shouldn't break the site
        }
    }

    public function register_frontend_scripts() {
        // Download Button scripts
        wp_register_script(
            'shortcuts-hub-download-button',
            plugins_url('assets/js/core/download-button.js', SHORTCUTS_HUB_PATH),
            ['jquery'],
            filemtime(SHORTCUTS_HUB_PATH . 'assets/js/core/download-button.js'),
            true
        );

        // Download Log scripts
        wp_register_script(
            'shortcuts-hub-download-log',
            plugins_url('assets/js/core/download-log.js', SHORTCUTS_HUB_PATH),
            ['jquery'],
            @filemtime(SHORTCUTS_HUB_PATH . 'assets/js/core/download-log.js'),
            true
        );

        wp_localize_script('shortcuts-hub-download-log', 'shortcuts_hub_params', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('shortcuts_hub_nonce')
        ]);
        
        // If in editor, enqueue immediately
        if (\Elementor\Plugin::$instance->editor->is_edit_mode()) {
            wp_enqueue_script('shortcuts-hub-download-button');
            wp_enqueue_script('shortcuts-hub-download-log');
        }
    }

    public function register_frontend_styles() {
        // Download Button styles
        wp_register_style(
            'shortcuts-hub-download-button',
            plugins_url('assets/css/core/download-button.css', SHORTCUTS_HUB_PATH),
            [],
            filemtime(SHORTCUTS_HUB_PATH . 'assets/css/core/download-button.css')
        );

        // Download Log styles
        wp_register_style(
            'shortcuts-hub-download-log',
            plugins_url('assets/css/core/download-log.css', SHORTCUTS_HUB_PATH),
            [],
            @filemtime(SHORTCUTS_HUB_PATH . 'assets/css/core/download-log.css')
        );
        
        // If in editor, enqueue immediately
        if (\Elementor\Plugin::$instance->editor->is_edit_mode()) {
            wp_enqueue_style('shortcuts-hub-download-button');
            wp_enqueue_style('shortcuts-hub-download-log');
        }
    }
}
