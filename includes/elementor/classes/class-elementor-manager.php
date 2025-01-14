<?php
namespace ShortcutsHub\Elementor;

use Elementor\Plugin;
use ShortcutsHub\Elementor\Widgets\Download_Button_Widget;
use ShortcutsHub\Elementor\Widgets\Download_Log;
use ShortcutsHub\Elementor\Widgets\My_Account_Widget;
use ShortcutsHub\Elementor\Widgets\Shortcuts_Icon_Widget;
use ShortcutsHub\Elementor\DynamicTags;

if (!defined('ABSPATH')) {
    exit;
}

require_once dirname(dirname(dirname(dirname(__FILE__)))) . '/sh-debug.php';

class Elementor_Manager {
    private static $instance = null;
    private static $initialized = false;
    private static $widgets_registered = false;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        $this->init();
    }

    public function init() {
        // Wait for Elementor to be fully initialized
        if (!did_action('elementor/init')) {
            add_action('elementor/init', [$this, 'init_elementor']);
            return;
        }

        self::$initialized = true;

        // Register dynamic tags
        add_action('elementor/dynamic_tags/register', [$this, 'register_dynamic_tags']);
        
        // Register for AJAX
        add_action('elementor/ajax', [$this, 'register_dynamic_tags']);

        // Load widget files and register widgets
        add_action('elementor/widgets/register', [$this, 'load_widget_files_and_register']);

        // Register styles and scripts
        add_action('elementor/frontend/after_enqueue_styles', [$this, 'register_frontend_styles']);
        add_action('elementor/preview/enqueue_styles', [$this, 'register_frontend_styles']);
        add_action('elementor/editor/after_enqueue_styles', [$this, 'register_frontend_styles']);
        add_action('elementor/editor/before_enqueue_scripts', [$this, 'register_scripts']);
        add_action('wp_enqueue_scripts', [$this, 'register_scripts']);
        add_action('elementor/frontend/after_register_scripts', [$this, 'register_scripts']);
        add_action('elementor/preview/enqueue_scripts', [$this, 'register_scripts']);
    }

    public function init_elementor() {
        // Do nothing, initialization is now handled in the constructor
    }

    public function load_widget_files_and_register($widgets_manager) {
        if (self::$widgets_registered) {
            return;
        }
        self::$widgets_registered = true;

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
                // Silent fail - don't break the site if a widget fails
            }
        }
    }

    public function register_dynamic_tags($dynamic_tags_manager = null) {
        if (!class_exists('\Elementor\Plugin')) {
            return;
        }

        // If we're in an AJAX request, get the dynamic tags manager
        if (wp_doing_ajax() && !is_object($dynamic_tags_manager)) {
            $dynamic_tags_manager = \Elementor\Plugin::$instance->dynamic_tags;
        }

        // Register group
        $dynamic_tags_manager->register_group(
            'shortcut_fields',
            [
                'title' => esc_html__('Shortcut Fields', 'shortcuts-hub')
            ]
        );

        // Load tags file
        require_once dirname(dirname(__FILE__)) . '/elementor-dynamic-fields.php';

        // Register tags
        $tags = [
            '\ShortcutsHub\Elementor\DynamicTags\Name_Dynamic_Tag',
            '\ShortcutsHub\Elementor\DynamicTags\Headline_Dynamic_Tag',
            '\ShortcutsHub\Elementor\DynamicTags\Description_Dynamic_Tag',
            '\ShortcutsHub\Elementor\DynamicTags\Color_Dynamic_Tag',
            '\ShortcutsHub\Elementor\DynamicTags\Input_Dynamic_Tag',
            '\ShortcutsHub\Elementor\DynamicTags\Result_Dynamic_Tag',
            '\ShortcutsHub\Elementor\DynamicTags\Latest_Version_Dynamic_Tag',
            '\ShortcutsHub\Elementor\DynamicTags\Latest_Version_URL_Dynamic_Tag'
        ];

        foreach ($tags as $tag_class) {
            if (class_exists($tag_class)) {
                $dynamic_tags_manager->register(new $tag_class());
            }
        }
    }

    public function register_scripts() {
        $plugin_url = plugin_dir_url(SHORTCUTS_HUB_FILE);
        $version = defined('WP_DEBUG') && WP_DEBUG ? time() : SHORTCUTS_HUB_VERSION;

        // Register download button script
        wp_register_script(
            'shortcuts-hub-download-button',
            $plugin_url . 'assets/js/core/download-button.js',
            ['jquery'],
            $version,
            true
        );

        // Consolidated localization for download button
        wp_localize_script('shortcuts-hub-download-button', 'shortcuts_hub', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('shortcuts_hub_nonce'),
            'post_url' => get_permalink(),
            'logged_in_text' => __('Download', 'shortcuts-hub'),
            'logged_out_text' => __('Login to Download', 'shortcuts-hub'),
            'ajaxurl' => admin_url('admin-ajax.php'),
            'is_user_logged_in' => is_user_logged_in(),
            'loginUrl' => wp_login_url()
        ]);

        // Register download log script
        wp_register_script(
            'shortcuts-hub-download-log',
            $plugin_url . 'assets/js/widgets/download-log.js',
            ['jquery'],
            $version,
            true
        );
    }

    public function register_frontend_styles() {
        // No styles to register as they are handled by Elementor's dynamic styling
        return;
    }
}
