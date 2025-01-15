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

// Ensure debug functionality is available
if (!function_exists('sh_debug_log')) {
    require_once dirname(dirname(dirname(__FILE__))) . '/sh-debug.php';
}

class Elementor_Manager {
    private static $instance = null;
    private static $initialized = false;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    protected function __construct() {
        // Register category first
        add_action('elementor/elements/categories_registered', array($this, 'register_category'));
        
        // Then register widgets and dynamic tags
        add_action('elementor/widgets/register', array($this, 'register_widgets'));
        add_action('elementor/dynamic_tags/register', array($this, 'register_dynamic_tags'));
    }

    public function register_category($elements_manager) {
        $elements_manager->add_category(
            'shortcuts-hub',
            [
                'title' => __('Shortcuts Hub', 'shortcuts-hub'),
                'icon' => 'fa fa-plug',
            ]
        );
    }

    public function init() {
        if (self::$initialized) {
            return;
        }

        self::$initialized = true;
    }

    public function register_widgets($widgets_manager) {
        $widget_files = [
            'download-button-widget.php',
            'download-log-widget.php',
            'my-account-widget.php',
            'icon-widget.php'
        ];

        foreach ($widget_files as $file) {
            $widget_file = plugin_dir_path(__FILE__) . '../widgets/' . $file;
            
            if (file_exists($widget_file)) {
                require_once $widget_file;
                
                // Handle special cases for class names
                $base_name = str_replace('.php', '', $file);
                $widget_class = '\\ShortcutsHub\\Elementor\\Widgets\\';
                
                // Map widget file names to their class names
                $class_map = array(
                    'icon-widget' => 'Shortcuts_Icon_Widget',
                    'download-button-widget' => 'Download_Button_Widget',
                    'download-log-widget' => 'Download_Log_Widget',
                    'my-account-widget' => 'My_Account_Widget'
                );
                
                $widget_class .= isset($class_map[$base_name]) ? $class_map[$base_name] : str_replace('-', '_', $base_name);
                
                if (class_exists($widget_class)) {
                    $widgets_manager->register(new $widget_class());
                }
            }
        }
    }

    public function register_dynamic_tags($dynamic_tags_manager) {
        // Register dynamic tags module
        \Elementor\Plugin::$instance->dynamic_tags->register_group('shortcut_fields', [
            'title' => 'Shortcut Fields'
        ]);

        // Include dynamic tags file
        require_once plugin_dir_path(__FILE__) . '../elementor-dynamic-fields.php';

        // Register all dynamic tags
        $tags = [
            new \ShortcutsHub\Elementor\DynamicTags\Name_Dynamic_Tag(),
            new \ShortcutsHub\Elementor\DynamicTags\Headline_Dynamic_Tag(),
            new \ShortcutsHub\Elementor\DynamicTags\Description_Dynamic_Tag(),
            new \ShortcutsHub\Elementor\DynamicTags\Color_Dynamic_Tag(),
            new \ShortcutsHub\Elementor\DynamicTags\Input_Dynamic_Tag(),
            new \ShortcutsHub\Elementor\DynamicTags\Result_Dynamic_Tag(),
            new \ShortcutsHub\Elementor\DynamicTags\Latest_Version_Dynamic_Tag(),
            new \ShortcutsHub\Elementor\DynamicTags\Latest_Version_URL_Dynamic_Tag()
        ];

        foreach ($tags as $tag) {
            try {
                $dynamic_tags_manager->register($tag);
            } catch (\Exception $e) {
                // Handle exception silently
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
