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
        
        // Register scripts and styles for both Elementor and standard WordPress
        add_action('wp_enqueue_scripts', [$this, 'register_frontend_scripts']);
        add_action('elementor/frontend/after_register_scripts', [$this, 'register_frontend_scripts']);
        add_action('elementor/preview/enqueue_scripts', [$this, 'register_frontend_scripts']);
        add_action('elementor/editor/after_enqueue_scripts', [$this, 'register_frontend_scripts']);
        
        // Register styles
        add_action('wp_enqueue_scripts', [$this, 'register_frontend_styles']);
        add_action('elementor/frontend/after_register_styles', [$this, 'register_frontend_styles']);
        add_action('elementor/preview/enqueue_styles', [$this, 'register_frontend_styles']);
        add_action('elementor/editor/after_enqueue_styles', [$this, 'register_frontend_styles']);
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
        // Prevent double registration
        if (wp_script_is('shortcuts-hub-download-button', 'registered')) {
            return;
        }

        $plugin_url = plugins_url('', dirname(dirname(dirname(__FILE__))));
        $version = defined('SHORTCUTS_HUB_VERSION') ? SHORTCUTS_HUB_VERSION : '1.0.0';

        // Register download button script
        wp_register_script(
            'shortcuts-hub-download-button',
            $plugin_url . '/assets/js/core/download-button.js',
            ['jquery'],
            $version,
            true
        );

        // Register download log script
        wp_register_script(
            'shortcuts-hub-download-log',
            $plugin_url . '/assets/js/core/download-log.js',
            ['jquery'],
            $version,
            true
        );

        // Get the appropriate login URL based on context
        $login_url = wp_login_url();
        if (!Plugin::$instance->editor->is_edit_mode() && function_exists('get_permalink')) {
            $login_url = wp_login_url(get_permalink());
        }

        // Localize the script with necessary data
        wp_localize_script('shortcuts-hub-download-button', 'shortcutsHubDownload', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('shortcuts_hub_download'),
            'loginUrl' => $login_url,
            'isUserLoggedIn' => is_user_logged_in()
        ]);

        // If in editor or preview, enqueue scripts immediately
        if (Plugin::$instance->editor->is_edit_mode() || Plugin::$instance->preview->is_preview_mode()) {
            wp_enqueue_script('shortcuts-hub-download-button');
            wp_enqueue_script('shortcuts-hub-download-log');
        }
    }

    public function register_frontend_styles() {
        // Prevent double registration
        if (wp_style_is('shortcuts-hub-download-button', 'registered')) {
            return;
        }

        $plugin_url = plugins_url('', dirname(dirname(dirname(__FILE__))));
        $version = defined('SHORTCUTS_HUB_VERSION') ? SHORTCUTS_HUB_VERSION : '1.0.0';

        wp_register_style(
            'shortcuts-hub-download-button',
            $plugin_url . '/assets/css/core/download-button.css',
            [],
            $version
        );
    }
}
