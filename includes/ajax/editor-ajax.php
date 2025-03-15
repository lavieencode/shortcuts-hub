<?php
namespace ShortcutsHub;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

/**
 * Editor AJAX Handler
 * 
 * Handles AJAX requests for the Elementor editor
 */
class Editor_Ajax_Handler {
    /**
     * Singleton instance
     */
    private static $instance = null;
    
    /**
     * Get the singleton instance
     */
    public static function instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        // Private constructor to prevent direct instantiation
    }
    
    /**
     * Register AJAX handlers
     */
    public function register_handlers() {
        add_action('wp_ajax_shortcuts_hub_get_endpoint_content', [$this, 'get_endpoint_content']);
        add_action('wp_ajax_nopriv_shortcuts_hub_get_endpoint_content', [$this, 'get_endpoint_content']);
    }
    
    /**
     * Get endpoint content for the Elementor editor
     */
    public function get_endpoint_content() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'shortcuts_hub_editor_nonce')) {
            wp_send_json_error('Invalid security token');
            return;
        }
        
        // Get the endpoint
        $endpoint = isset($_POST['endpoint']) ? sanitize_text_field($_POST['endpoint']) : 'dashboard';
        
        // Buffer the output
        ob_start();
        
        // Set up the environment to simulate the endpoint
        global $wp;
        $old_wp = $wp;
        
        // Create a new WP object to avoid modifying the global one
        $wp = new \WP();
        $wp->query_vars['pagename'] = 'my-account';
        
        // Set the endpoint in the query vars
        if ($endpoint && $endpoint !== 'dashboard') {
            $wp->query_vars['wc-api'] = $endpoint;
            $wp->query_vars[$endpoint] = '';
        }
        
        // Simulate being on the my-account page
        set_query_var('pagename', 'my-account');
        if ($endpoint && $endpoint !== 'dashboard') {
            set_query_var($endpoint, '');
        }
        
        // Simulate the endpoint content
        switch ($endpoint) {
            case 'dashboard':
                wc_get_template('myaccount/dashboard.php', array(
                    'current_user' => get_user_by('id', get_current_user_id()),
                ));
                break;
                
            case 'orders':
                wc_get_template('myaccount/orders.php', array(
                    'current_user' => get_user_by('id', get_current_user_id()),
                    'order_count' => wc_get_customer_order_count(get_current_user_id()),
                ));
                break;
                
            case 'downloads':
                wc_get_template('myaccount/downloads.php');
                break;
                
            case 'edit-address':
                wc_get_template('myaccount/my-address.php', array(
                    'current_user' => get_user_by('id', get_current_user_id()),
                ));
                break;
                
            case 'edit-account':
                wc_get_template('myaccount/form-edit-account.php', array(
                    'user' => get_user_by('id', get_current_user_id()),
                ));
                break;
                
            case 'shortcuts':
                // Get the shortcuts for the current user
                $user_id = get_current_user_id();
                $shortcuts = get_posts(array(
                    'post_type' => 'shortcut',
                    'posts_per_page' => -1,
                    'meta_query' => array(
                        array(
                            'key' => '_shortcut_user_id',
                            'value' => $user_id,
                            'compare' => '='
                        )
                    )
                ));
                
                if (empty($shortcuts)) {
                    echo '<h3>' . __('Shortcuts', 'shortcuts-hub') . '</h3>';
                    echo '<p>' . __('You don\'t have any shortcuts yet. Browse the site and save shortcuts to access them quickly later.', 'shortcuts-hub') . '</p>';
                } else {
                    echo '<h3>' . __('Your Shortcuts', 'shortcuts-hub') . '</h3>';
                    echo '<ul class="shortcuts-list">';
                    foreach ($shortcuts as $shortcut) {
                        $url = get_post_meta($shortcut->ID, '_shortcut_url', true);
                        echo '<li><a href="' . esc_url($url) . '">' . esc_html($shortcut->post_title) . '</a></li>';
                    }
                    echo '</ul>';
                }
                break;
                
            default:
                // For any other endpoint, try to load the template
                do_action('woocommerce_account_' . $endpoint . '_endpoint');
                break;
        }
        
        // Get the output
        $content = ob_get_clean();
        
        // Restore the original WP object
        $wp = $old_wp;
        
        // Send the response
        wp_send_json_success($content);
    }
}
