<?php
namespace ShortcutsHub;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Shortcuts_Hub {
    /**
     * Single instance of this class
     */
    private static $instance = null;

    /**
     * AJAX Handler instance
     */
    private $ajax_handler = null;

    /**
     * Flag to prevent multiple initializations in the same request
     */
    private static $initialized = false;
    private static $is_activating = false;
    private static $init_lock_key = 'shortcuts_hub_initializing';
    private static $init_flag_key = 'shortcuts_hub_initialized';

    /**
     * Get the singleton instance - does NOT initialize
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Protected constructor - does NOT initialize
     */
    protected function __construct() {
        // Initialize the plugin during plugins_loaded
        add_action('plugins_loaded', [$this, 'initialize']);
    }

    /**
     * Prevent cloning of the instance
     */
    protected function __clone() {}

    /**
     * Core initialization during plugins_loaded
     * This sets up the basic plugin framework
     */
    public function initialize() {
        if (self::$initialized) {
            return;
        }

        try {
            self::$initialized = true;
            
            // Load all dependencies first
            $this->load_dependencies();
            
            // Now that dependencies are loaded, check Elementor
            if (did_action('elementor/loaded')) {
                \ShortcutsHub\Elementor\Elementor_Manager::get_instance();
            } else {
                add_action('elementor/loaded', function() {
                    \ShortcutsHub\Elementor\Elementor_Manager::get_instance();
                });
            }
            
            // Register core WordPress hooks
            $this->register_core_hooks();
            
            // Initialize AJAX handlers
            $this->init_ajax_handlers();
            
        } catch (Exception $e) {
            // DEBUG: Logging initialization error with detailed information
            sh_debug_log('Plugin Initialization Error', array(
                'message' => 'Error during plugin initialization',
                'source' => array(
                    'file' => __FILE__,
                    'line' => __LINE__,
                    'function' => __FUNCTION__
                ),
                'data' => array(
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ),
                'debug' => false
            ));
        }
    }

    /**
     * Initialize core functionality during 'init' hook
     * This runs after WordPress core is loaded
     */
    public function init_core() {
        // Register post types
        $this->register_post_type();

        // Set up menu structure
        add_action('admin_menu', [$this, 'register_shortcuts_menu']);

        // Any other core functionality that needs WordPress loaded
        do_action('shortcuts_hub_init');
    }

    /**
     * Initialize admin-specific functionality
     */
    public function init_admin() {
        // Register settings
        register_setting('shortcuts_hub_options', 'shortcuts_hub_api_key');
        register_setting('shortcuts_hub_options', 'shortcuts_hub_api_secret');
        
        // Localize scripts for admin
        add_action('admin_enqueue_scripts', function($hook) {
            if (strpos($hook, 'shortcuts-hub') !== false) {
                wp_localize_script('shortcuts-hub-versions-handlers', 'shortcutsHubData', array(
                    'ajaxurl' => admin_url('admin-ajax.php'),
                    'nonce' => wp_create_nonce('fetch_versions_nonce'),
                    'view' => isset($_GET['view']) ? sanitize_text_field($_GET['view']) : '',
                    'shortcutId' => isset($_GET['id']) ? sanitize_text_field($_GET['id']) : '',
                    'initialView' => isset($_GET['view']) && $_GET['view'] === 'versions' ? 'versions' : 'shortcuts'
                ));
            }
        });
        
        // Add settings page
        $this->register_settings_page();
    }

    /**
     * Load required dependencies
     */
    private function load_dependencies() {
        // Core files
        require_once SHORTCUTS_HUB_PATH . 'core/database.php';
        require_once SHORTCUTS_HUB_PATH . 'core/enqueue-core.php';
        require_once SHORTCUTS_HUB_PATH . 'sh-debug.php';
        require_once SHORTCUTS_HUB_PATH . 'includes/sb-api.php';
        require_once SHORTCUTS_HUB_PATH . 'includes/security.php';
        require_once SHORTCUTS_HUB_PATH . 'includes/auth.php';
        require_once SHORTCUTS_HUB_PATH . 'includes/settings.php';
        require_once SHORTCUTS_HUB_PATH . 'includes/enqueue-assets.php';
        
        // Load Elementor integration
        require_once SHORTCUTS_HUB_PATH . 'includes/elementor/classes/class-elementor-manager.php';
        
        // AJAX handlers
        require_once SHORTCUTS_HUB_PATH . 'includes/ajax/shortcuts-ajax.php';
        require_once SHORTCUTS_HUB_PATH . 'includes/ajax/versions-ajax.php';
        
        // Pages - load these after core files
        require_once SHORTCUTS_HUB_PATH . 'includes/pages/shortcuts-list-page.php';
        require_once SHORTCUTS_HUB_PATH . 'includes/pages/add-shortcut-page.php';
        require_once SHORTCUTS_HUB_PATH . 'includes/pages/edit-shortcut-page.php';
        require_once SHORTCUTS_HUB_PATH . 'includes/pages/settings.php';
    }

    /**
     * Initialize AJAX handlers
     */
    private function init_ajax_handlers() {
        static $ajax_initialized = false;
        
        if (!$ajax_initialized) {
            $ajax_initialized = true;
            if ($this->ajax_handler === null) {
                $this->ajax_handler = \ShortcutsHub\Shortcuts_Ajax_Handler::instance();
                $this->ajax_handler->register_handlers();
            }
        }
    }

    /**
     * Get status of AJAX handlers
     */
    private function get_ajax_handler_status() {
        $status = [
            'has_create_shortcut' => has_action('wp_ajax_create_shortcut'),
            'has_fetch_shortcuts' => has_action('wp_ajax_fetch_shortcuts'),
            'has_fetch_shortcut' => has_action('wp_ajax_fetch_shortcut'),
            'has_update_shortcut' => has_action('wp_ajax_update_shortcut'),
            'has_toggle_draft' => has_action('wp_ajax_toggle_draft'),
            'has_delete_shortcut' => has_action('wp_ajax_delete_shortcut'),
            'has_process_download_token' => has_action('wp_ajax_process_download_token')
        ];

        // Add handler instance status if available
        if ($this->ajax_handler !== null) {
            $status['handler_registered'] = $this->ajax_handler->is_registered();
        }

        return $status;
    }

    /**
     * Register the post type - public because it's hooked
     */
    public function register_post_type() {
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

    /**
     * Register menu - public because it's hooked
     */
    public function register_shortcuts_menu() {
        // Main menu that links to shortcuts-list
        add_menu_page(
            'Shortcuts Hub',
            'Shortcuts Hub',
            'manage_options',
            'shortcuts-list', 
            'shortcuts_hub_render_shortcuts_list_page',
            'dashicons-list-view',
            6
        );

        // Add Shortcuts List as first submenu to match main menu
        add_submenu_page(
            'shortcuts-list',
            'Shortcuts List',
            'Shortcuts List',
            'manage_options',
            'shortcuts-list',
            'shortcuts_hub_render_shortcuts_list_page'
        );

        add_submenu_page(
            'shortcuts-list',
            'Add Shortcut',
            'Add Shortcut',
            'manage_options',
            'add-shortcut',
            'shortcuts_hub_render_add_shortcut_page'
        );

        add_submenu_page(
            'shortcuts-list',
            'Edit Shortcut',
            'Edit Shortcut',
            'manage_options',
            'edit-shortcut',
            'shortcuts_hub_render_edit_shortcut_page'
        );
    }

    /**
     * Register settings page
     */
    private function register_settings_page() {
        add_options_page(
            'Shortcuts Hub Settings',
            'Shortcuts Hub',
            'manage_options',
            'shortcuts_hub_settings',
            'shortcuts_hub_settings_page'
        );
    }

    /**
     * Add settings page under Settings menu
     */
    public function add_settings_page() {
        // Removed
    }

    /**
     * Filter body class - public because it's hooked
     */
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
                }
            }
        }
        
        return $classes;
    }

    /**
     * Plugin activation
     * Sets up everything needed for first-time use
     */
    public function activate() {
        self::$is_activating = true;
        try {
            // Load dependencies first
            $this->load_dependencies();

            // Register post type immediately - needed for rewrite rules
            $this->register_post_type();

            // Initialize AJAX handlers - they should be available immediately
            $this->init_ajax_handlers();

            // Create/upgrade database tables if needed
            $this->maybe_create_tables();

            // Set default options
            $this->set_default_options();

            // Schedule any necessary cron jobs
            $this->schedule_cron_jobs();

            // Flag that we need to flush rewrite rules
            update_option('shortcuts_hub_flush_rewrite_rules', true);

            // DEBUG: Logging successful plugin activation with version and timestamp
            sh_debug_log('Plugin Activated', array(
                'message' => 'Shortcuts Hub plugin has been activated',
                'source' => array(
                    'file' => __FILE__,
                    'line' => __LINE__,
                    'function' => __FUNCTION__
                ),
                'data' => array(
                    'timestamp' => time(),
                    'version' => SHORTCUTS_HUB_VERSION
                ),
                'debug' => true
            ));
        } finally {
            self::$is_activating = false;
        }
    }

    /**
     * Create or upgrade database tables
     */
    private function maybe_create_tables() {
        global $wpdb;
        
        // Add your table creation logic here
        // Example:
        // $table_name = $wpdb->prefix . 'shortcuts_hub_downloads';
        // if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
        //     // Create table
        // }
    }

    /**
     * Set default options
     */
    private function set_default_options() {
        // Add any default options your plugin needs
        add_option('shortcuts_hub_version', SHORTCUTS_HUB_VERSION);
        add_option('shortcuts_hub_installed', time());
    }

    /**
     * Schedule cron jobs
     */
    private function schedule_cron_jobs() {
        // Schedule any necessary cron jobs
        // Example:
        // if (!wp_next_scheduled('shortcuts_hub_daily_cleanup')) {
        //     wp_schedule_event(time(), 'daily', 'shortcuts_hub_daily_cleanup');
        // }
    }

    /**
     * Plugin deactivation
     */
    public function deactivate() {
        // Clear any scheduled cron jobs
        // wp_clear_scheduled_hook('shortcuts_hub_daily_cleanup');

        // Clean up rewrite rules
        flush_rewrite_rules();

        // DEBUG: Logging plugin deactivation with version and timestamp
        sh_debug_log('Plugin Deactivated', array(
            'message' => 'Shortcuts Hub plugin has been deactivated',
            'source' => array(
                'file' => __FILE__,
                'line' => __LINE__,
                'function' => __FUNCTION__
            ),
            'data' => array(
                'timestamp' => time(),
                'version' => defined('SHORTCUTS_HUB_VERSION') ? SHORTCUTS_HUB_VERSION : '1.0.0'
            ),
            'debug' => true
        ));
    }

    /**
     * Register core WordPress hooks
     */
    private function register_core_hooks() {
        // Core initialization
        add_action('init', [$this, 'init_core']);

        // Admin-specific initialization
        if (is_admin()) {
            add_action('init', [$this, 'init_admin']);
            add_filter('admin_body_class', [$this, 'admin_body_class']);
        }
    }
}
