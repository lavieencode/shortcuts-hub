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
        add_action('init', [$this, 'init'], 0); // Priority 0 to run early
        add_action('plugins_loaded', [$this, 'plugins_loaded']);
        add_action('admin_menu', [$this, 'register_shortcuts_menu']);
        add_filter('admin_body_class', [$this, 'admin_body_class']);
    }

    public function init() {
        // Register post type first
        $this->register_post_type();
        
        // Then handle rewrite rules
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
                require_once SHORTCUTS_HUB_PATH . 'includes/elementor/classes/class-elementor-manager.php';
                \ShortcutsHub\Elementor\Elementor_Manager::get_instance();
            });
        }
    }

    public function register_shortcuts_menu() {
        remove_menu_page('shortcuts-hub');

        add_menu_page(
            'Shortcuts Hub',
            'Shortcuts Hub',
            'manage_options',
            'shortcuts-hub', 
            'shortcuts_hub_render_shortcuts_list_page',
            'dashicons-list-view',
            6
        );

        add_submenu_page(
            'shortcuts-hub',
            'Shortcuts List',
            'Shortcuts List',
            'manage_options',
            'shortcuts-list',
            'shortcuts_hub_render_shortcuts_list_page'
        );

        add_submenu_page(
            'shortcuts-hub',
            'Add Shortcut',
            'Add Shortcut',
            'manage_options',
            'add-shortcut',
            'shortcuts_hub_render_add_shortcut_page'
        );

        add_submenu_page(
            'shortcuts-hub',
            'Edit Shortcut',
            'Edit Shortcut',
            'manage_options',
            'edit-shortcut',
            'shortcuts_hub_render_edit_shortcut_page'
        );

        add_submenu_page(
            'shortcuts-hub',
            'Add Version',
            'Add Version',
            'manage_options',
            'add-version',
            'shortcuts_hub_render_add_version_page'
        );

        add_submenu_page(
            'shortcuts-hub',
            'Edit Version',
            'Edit Version',
            'manage_options',
            'edit-version',
            'shortcuts_hub_render_edit_version_page'
        );

        global $submenu;
        unset($submenu['shortcuts-hub'][0]);
    }

    public function admin_body_class($classes) {
        $screen = get_current_screen();
        
        if ($screen) {
            $page_slug = $screen->id;
            
            if (strpos($page_slug, 'shortcuts-hub') !== false) {
                $classes .= ' shortcuts-hub-admin';
                
                if (strpos($page_slug, 'add-shortcut') !== false) {
                    $classes .= ' shortcuts-hub-add-shortcut';
                } elseif (strpos($page_slug, 'edit-shortcut') !== false) {
                    $classes .= ' shortcuts-hub-edit-shortcut';
                } elseif (strpos($page_slug, 'add-version') !== false) {
                    $classes .= ' shortcuts-hub-add-version';
                } elseif (strpos($page_slug, 'edit-version') !== false) {
                    $classes .= ' shortcuts-hub-edit-version';
                }
            }
        }
        
        return $classes;
    }

    private function register_post_type() {
        $labels = array(
            'name' => 'Shortcuts',
            'singular_name' => 'Shortcut',
            'menu_name' => 'Shortcuts',
            'add_new' => 'Add New',
            'add_new_item' => 'Add New Shortcut',
            'edit_item' => 'Edit Shortcut',
            'new_item' => 'New Shortcut',
            'view_item' => 'View Shortcut',
            'search_items' => 'Search Shortcuts',
            'not_found' => 'No shortcuts found',
            'not_found_in_trash' => 'No shortcuts found in trash',
            'parent_item_colon' => '',
            'all_items' => 'All Shortcuts'
        );

        $args = array(
            'labels' => $labels,
            'public' => true,
            'publicly_queryable' => true,
            'show_ui' => true,
            'show_in_menu' => false,
            'query_var' => true,
            'rewrite' => array(
                'slug' => 'shortcut',
                'with_front' => false
            ),
            'capability_type' => 'post',
            'has_archive' => true,
            'hierarchical' => false,
            'menu_position' => null,
            'supports' => array('title', 'editor', 'thumbnail')
        );

        register_post_type('shortcut', $args);
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
