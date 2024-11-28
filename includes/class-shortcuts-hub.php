<?php
namespace ShortcutsHub;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Shortcuts_Hub {

    private static $_instance = null;

    public static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    public function __construct() {
        add_action('init', [$this, 'init']);
        add_action('plugins_loaded', [$this, 'plugins_loaded']);
    }

    public function init() {
        // Register the shortcuts endpoint
        add_rewrite_endpoint('shortcuts', EP_ROOT | EP_PAGES);

        // Check if we need to flush rewrite rules
        if (get_option('shortcuts_hub_flush_rewrite_rules', false)) {
            flush_rewrite_rules();
            delete_option('shortcuts_hub_flush_rewrite_rules');
        }
    }

    public function plugins_loaded() {
        // Load text domain
        load_plugin_textdomain('shortcuts-hub', false, dirname(plugin_basename(SHORTCUTS_HUB_PATH)) . '/languages');

        // Initialize Elementor integration
        if (did_action('elementor/loaded')) {
            add_action('elementor/init', function() {
                error_log('Shortcuts Hub: Loading Elementor Manager');
                require_once SHORTCUTS_HUB_PATH . 'includes/elementor/classes/class-elementor-manager.php';
                \ShortcutsHub\Elementor\Elementor_Manager::get_instance();
                error_log('Shortcuts Hub: Elementor Manager loaded');
            });
        }
    }

    public function activate() {
        // Set flag to flush rewrite rules on next init
        update_option('shortcuts_hub_flush_rewrite_rules', true);
    }

    public function deactivate() {
        // Clean up rewrite rules
        flush_rewrite_rules();
    }
}
