<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class Shortcuts_Hub_Elementor {
    private static $_instance = null;

    public static function instance() {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    public function __construct() {
        add_action('elementor/elements/categories_registered', [$this, 'register_categories']);
        add_action('elementor/widgets/register', [$this, 'register_widgets']);
        add_action('elementor/dynamic_tags/register', [$this, 'register_dynamic_tags']);
    }

    public function register_categories($elements_manager) {
        $elements_manager->add_category(
            'shortcuts-hub',
            [
                'title' => esc_html__('Shortcuts Hub', 'shortcuts-hub'),
                'icon' => 'fa fa-plug',
            ]
        );
    }

    public function register_widgets($widgets_manager) {
        require_once(__DIR__ . '/download-button.php');
        $widgets_manager->register(new Shortcuts_Download_Button());
    }

    public function register_dynamic_tags($dynamic_tags_manager) {
        require_once(__DIR__ . '/elementor-dynamic-fields.php');
        
        // Register the dynamic tags group
        $dynamic_tags_manager->register_group(
            'shortcut_fields',
            [
                'title' => esc_html__('Shortcut Fields', 'shortcuts-hub'),
            ]
        );

        // Register all dynamic tags
        $dynamic_tags_manager->register(new Name_Dynamic_Tag());
        $dynamic_tags_manager->register(new Headline_Dynamic_Tag());
        $dynamic_tags_manager->register(new Description_Dynamic_Tag());
        $dynamic_tags_manager->register(new Color_Dynamic_Tag());
        $dynamic_tags_manager->register(new Icon_Dynamic_Tag());
        $dynamic_tags_manager->register(new Input_Dynamic_Tag());
        $dynamic_tags_manager->register(new Result_Dynamic_Tag());
        $dynamic_tags_manager->register(new Latest_Version_URL_Dynamic_Tag());
    }
}

// Initialize
Shortcuts_Hub_Elementor::instance();
