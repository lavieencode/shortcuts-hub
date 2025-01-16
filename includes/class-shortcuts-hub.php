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
            
            // Register post types on init
            add_action('init', array($this, 'register_post_type'));
            add_action('init', array($this, 'register_actions_post_type'));
            
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
        require_once plugin_dir_path(dirname(__FILE__)) . 'core/database.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'core/enqueue-core.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'sh-debug.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/sb-api.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/security.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/auth.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/settings.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/enqueue-assets.php';
        
        // Load Elementor integration
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/elementor/classes/class-elementor-manager.php';
        
        // AJAX handlers
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/ajax/shortcuts-ajax.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/ajax/versions-ajax.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/ajax/actions-ajax.php';
        
        // Pages
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/pages/shortcuts-list-page.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/pages/add-shortcut-page.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/pages/edit-shortcut-page.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/pages/settings.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/pages/actions-manager-page.php';
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
     * Register the Actions post type - public because it's hooked
     * Actions can be associated with multiple shortcuts and vice versa
     */
    public function register_actions_post_type() {
        $args = array(
            'labels' => array(
                'name' => __('Actions', 'shortcuts-hub'),
                'singular_name' => __('Action', 'shortcuts-hub'),
            ),
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => false,
            'capability_type' => 'post',
            'hierarchical' => false,
            'rewrite' => false,
            'supports' => array('title', 'editor', 'custom-fields'),
            'has_archive' => false,
            'show_in_rest' => true
        );

        register_post_type('action', $args);
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
            'dashicons-admin-generic',
            30
        );

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
            'Actions Manager',
            'Actions Manager',
            'manage_options',
            'actions-manager',
            'shortcuts_hub_render_actions_manager_page'
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
        try {
            self::$is_activating = true;

            // Create/upgrade database tables if needed
            $this->maybe_create_tables();

            // Set default options
            $this->set_default_options();

            // Schedule any necessary cron jobs
            $this->schedule_cron_jobs();

            // Flag that we need to flush rewrite rules
            update_option('shortcuts_hub_flush_rewrite_rules', true);
        } catch (\Exception $e) {
            // Log any errors during activation
            sh_debug_log('Activation Error', array(
                'message' => 'Error during plugin activation',
                'source' => array(
                    'file' => __FILE__,
                    'line' => __LINE__,
                    'function' => __FUNCTION__
                ),
                'data' => array(
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ),
                'debug' => true
            ));
        }
    }

    /**
     * Create or upgrade database tables if they don't exist or need updating.
     * Uses WordPress dbDelta for safe table creation/updates.
     *
     * @since 1.0.0
     * @access private
     * @global wpdb $wpdb WordPress database abstraction object.
     * @return void
     */
    private function maybe_create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        // Debug log for table creation start
        sh_debug_log('Creating database tables', array(
            'message' => 'Starting database table creation/update process',
            'source' => array(
                'file' => __FILE__,
                'line' => __LINE__,
                'function' => __FUNCTION__
            ),
            'data' => array(),
            'debug' => true
        ));

        // Define table names
        $shortcuts_table = $wpdb->prefix . 'shortcuts_hub_shortcuts';
        $action_shortcut_table = $wpdb->prefix . 'shortcut_action_relationships';
        $downloads_table = $wpdb->prefix . 'shortcutshub_downloads';

        // Define table schemas
        $tables = array(
            // Shortcuts table
            $shortcuts_table => "CREATE TABLE $shortcuts_table (
                id bigint(20) NOT NULL AUTO_INCREMENT,
                title varchar(255) NOT NULL,
                description text,
                icon text,
                color varchar(20),
                command text NOT NULL,
                created_at datetime DEFAULT CURRENT_TIMESTAMP,
                updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY  (id)
            ) $charset_collate;",

            // Action-Shortcut relationships table (many-to-many)
            $action_shortcut_table => "CREATE TABLE $action_shortcut_table (
                id bigint(20) NOT NULL AUTO_INCREMENT,
                shortcut_id bigint(20) NOT NULL,
                action_id bigint(20) NOT NULL,
                created_at datetime DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY  (id),
                UNIQUE KEY unique_relationship (shortcut_id,action_id),
                KEY shortcut_id (shortcut_id),
                KEY action_id (action_id)
            ) $charset_collate;",

            // Downloads tracking table
            $downloads_table => "CREATE TABLE $downloads_table (
                id bigint(20) NOT NULL AUTO_INCREMENT,
                user_id bigint(20) NOT NULL,
                shortcut_id varchar(255) NOT NULL,
                post_id bigint(20) NOT NULL,
                post_url text NOT NULL,
                shortcut_name varchar(255) NOT NULL,
                version varchar(50) NOT NULL,
                version_notes text,
                minimum_ios varchar(50),
                minimum_mac varchar(50),
                download_url text NOT NULL,
                ip_address varchar(45) NOT NULL,
                is_required tinyint(1) NOT NULL DEFAULT 0,
                created_at datetime DEFAULT CURRENT_TIMESTAMP,
                download_date datetime DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY  (id),
                KEY user_id (user_id),
                KEY shortcut_id (shortcut_id),
                KEY post_id (post_id),
                KEY download_date (download_date),
                KEY ip_address (ip_address)
            ) $charset_collate;"
        );

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        // Create/update tables and track results
        $results = array();
        foreach ($tables as $table_name => $sql) {
            $results[$table_name] = array(
                'sql' => $sql,
                'result' => dbDelta($sql),
                'exists' => (bool)$wpdb->get_var("SHOW TABLES LIKE '$table_name'")
            );
        }

        // Check for any failures
        $failures = array_filter($results, function($result) {
            return !$result['exists'];
        });

        if (!empty($failures)) {
            // Log detailed error information for failed tables
            sh_debug_log('Database Table Creation Failed', array(
                'message' => 'One or more tables failed to create',
                'source' => array(
                    'file' => __FILE__,
                    'line' => __LINE__,
                    'function' => __FUNCTION__
                ),
                'data' => array(
                    'failed_tables' => $failures,
                    'wpdb_error' => $wpdb->last_error,
                    'wpdb_query' => $wpdb->last_query,
                    'wpdb_ready' => $wpdb->ready,
                    'wpdb_has_connected' => $wpdb->has_connected,
                    'wpdb_dbname' => $wpdb->dbname,
                    'wpdb_dbuser' => $wpdb->dbuser,
                    'wpdb_dbhost' => $wpdb->dbhost,
                    'wpdb_prefix' => $wpdb->prefix,
                    'wpdb_charset' => $wpdb->charset,
                    'wpdb_collate' => $wpdb->collate
                ),
                'debug' => true
            ));
        } else {
            // Log successful table creation
            sh_debug_log('Database tables created successfully', array(
                'message' => 'All tables were created/updated successfully',
                'source' => array(
                    'file' => __FILE__,
                    'line' => __LINE__,
                    'function' => __FUNCTION__
                ),
                'data' => array(
                    'tables' => array_keys($tables)
                ),
                'debug' => true
            ));
        }

        // Store the current database version
        update_option('shortcuts_hub_db_version', SHORTCUTS_HUB_VERSION);
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
    }

    /**
     * Register core WordPress hooks
     * 
     * @since 1.0.0
     * @access private
     * @return void
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
