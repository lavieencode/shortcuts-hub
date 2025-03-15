<?php
namespace ShortcutsHub\Elementor\Widgets;

use Elementor\Plugin;
use ElementorPro\Modules\Woocommerce\Widgets\My_Account as Elementor_My_Account;
use Elementor\Controls_Manager;

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

class My_Account_Widget extends Elementor_My_Account {
    // Property to track if controls have been registered
    private $controls_registered = false;

    public function get_name() {
        return 'shortcuts-hub-my-account';
    }

    public function __construct($data = [], $args = null) {
        parent::__construct($data, $args);
        
        // Register the stylesheet for My Account widget
        wp_register_style(
            'shortcuts-hub-my-account',
            plugins_url('/assets/css/widgets/my-account-widget.css', SHORTCUTS_HUB_FILE),
            [],
            SHORTCUTS_HUB_VERSION
        );
        
        // Register endpoints on init
        add_action('init', [$this, 'add_endpoints']);
        
        // Handle the shortcuts endpoint template
        add_action('template_redirect', [$this, 'handle_shortcuts_endpoint']);
    }

    public function get_categories() {
        return ['shortcuts-hub'];
    }

    public function get_html_wrapper_class() {
        $classes = parent::get_html_wrapper_class();
        
        if (is_array($classes)) {
            $classes = array_diff($classes, ['elementor-widget-empty']);
        }
        
        return $classes;
    }

    protected function render() {
        // Add our hooks before rendering
        $this->add_render_hooks();
        
        // Display our Widget
        if (!Plugin::$instance->editor->is_edit_mode()) {
            $this->render_html_front_end();
        } else {
            $this->render_html_editor();
        }

        // Remove filters and actions after rendering
        $this->remove_render_hooks();
    }
    
    /**
     * Content template for the editor
     * This is used by Elementor to render the widget in the editor using JS
     */
    protected function content_template() {
        ?>
        <div class="e-my-account-tab e-my-account-tab__dashboard">
            <div class="woocommerce">
                <nav class="woocommerce-MyAccount-navigation">
                    <ul>
                        <li class="woocommerce-MyAccount-navigation-link woocommerce-MyAccount-navigation-link--dashboard is-active">
                            <a href="#"><i class="fas fa-tachometer-alt" style="margin-right: 10px; width: 1.25em; text-align: center;"></i>Dashboard</a>
                        </li>
                        <li class="woocommerce-MyAccount-navigation-link woocommerce-MyAccount-navigation-link--orders">
                            <a href="#"><i class="fas fa-shopping-cart" style="margin-right: 10px; width: 1.25em; text-align: center;"></i>Orders</a>
                        </li>
                        <li class="woocommerce-MyAccount-navigation-link woocommerce-MyAccount-navigation-link--downloads">
                            <a href="#"><i class="fas fa-download" style="margin-right: 10px; width: 1.25em; text-align: center;"></i>Downloads</a>
                        </li>
                        <li class="woocommerce-MyAccount-navigation-link woocommerce-MyAccount-navigation-link--edit-address">
                            <a href="#"><i class="fas fa-map-marker-alt" style="margin-right: 10px; width: 1.25em; text-align: center;"></i>Addresses</a>
                        </li>
                        <li class="woocommerce-MyAccount-navigation-link woocommerce-MyAccount-navigation-link--edit-account">
                            <a href="#"><i class="fas fa-user" style="margin-right: 10px; width: 1.25em; text-align: center;"></i>Account details</a>
                        </li>
                        <li class="woocommerce-MyAccount-navigation-link woocommerce-MyAccount-navigation-link--shortcuts">
                            <a href="#"><i class="fas fa-save" style="margin-right: 10px; width: 1.25em; text-align: center;"></i>Shortcuts</a>
                        </li>
                        <li class="woocommerce-MyAccount-navigation-link woocommerce-MyAccount-navigation-link--customer-logout">
                            <a href="#"><i class="fas fa-sign-out-alt" style="margin-right: 10px; width: 1.25em; text-align: center;"></i>Logout</a>
                        </li>
                    </ul>
                </nav>
                <div class="woocommerce-MyAccount-content-wrapper">
                    <div class="woocommerce-MyAccount-content">
                        <p>Hello <strong>User</strong> (not <strong>User</strong>? <a href="#">Log out</a>)</p>
                        <p>From your account dashboard you can view your recent orders, manage your shipping and billing addresses, and edit your password and account details.</p>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    protected function add_render_hooks() {
        // Add our filter before rendering
        add_filter('woocommerce_account_menu_items', [$this, 'add_shortcuts_to_menu'], 10, 1);
        add_action('woocommerce_account_shortcuts_endpoint', [$this, 'shortcuts_endpoint_content']);

        // Add parent widget's actions & filters
        if (Plugin::$instance->editor->is_edit_mode()) {
            // In editor mode, we need to be careful not to override global hooks
            // that might affect other widgets
            return;
        }
        
        // Override the navigation template completely to add our icon
        remove_action('woocommerce_account_navigation', 'woocommerce_account_navigation');
        add_action('woocommerce_account_navigation', [$this, 'woocommerce_account_navigation'], 1);
        
        add_filter('woocommerce_account_menu_items', [$this, 'modify_menu_items'], 10, 2);
        add_action('woocommerce_account_content', [$this, 'before_account_content'], 2);
        add_action('woocommerce_account_content', [$this, 'after_account_content'], 95);
        add_filter('woocommerce_get_myaccount_page_permalink', [$this, 'woocommerce_get_myaccount_page_permalink'], 10, 1);
        add_filter('woocommerce_logout_default_redirect_url', [$this, 'woocommerce_logout_default_redirect_url'], 10, 1);
        add_filter('woocommerce_is_account_page', '__return_true');

        if ($this->has_custom_template() && 'dashboard' === $this->get_current_endpoint()) {
            remove_action('woocommerce_account_content', 'woocommerce_account_content', 10);
            add_action('woocommerce_account_content', [$this, 'display_custom_template'], 10);
        }
    }

    /**
     * Custom implementation of the WooCommerce account navigation
     * This allows us to directly add the icon to the Shortcuts menu item
     */
    public function woocommerce_account_navigation() {
        // Get the settings from Elementor
        $settings = $this->get_settings_for_display();
        $current = is_wc_endpoint_url() ? WC()->query->get_current_endpoint() : 'dashboard';
        
        // Define icon map for all menu items
        $icon_map = [
            'dashboard' => 'fa-tachometer-alt',
            'orders' => 'fa-shopping-cart',
            'downloads' => 'fa-download',
            'edit-address' => 'fa-map-marker-alt',
            'edit-account' => 'fa-user',
            'shortcuts' => 'fa-save',
            'customer-logout' => 'fa-sign-out-alt'
        ];
        
        echo '<nav class="woocommerce-MyAccount-navigation">';
        echo '<ul>';
        
        // Get default items
        $default_items = wc_get_account_menu_items();
        
        // Use the ordered tabs from Elementor settings if available
        if (!empty($settings['tabs'])) {
            // Build ordered items based on Elementor settings
            foreach ($settings['tabs'] as $tab) {
                if (!empty($tab['field_key']) && !empty($tab['is_visible']) && $tab['is_visible'] === 'yes') {
                    $endpoint = $tab['field_key'];
                    $label = !empty($tab['tab_name']) ? $tab['tab_name'] : (isset($default_items[$endpoint]) ? $default_items[$endpoint] : $endpoint);
                    
                    $classes = wc_get_account_menu_item_classes($endpoint);
                    
                    // Handle case where $classes might be a string instead of an array
                    $class_attr = is_array($classes) ? implode(' ', $classes) : $classes;
                    
                    echo '<li class="' . $class_attr . '">';
                    
                    // Add icon to all menu items for consistent styling
                    $icon = isset($icon_map[$endpoint]) ? '<i class="fas ' . $icon_map[$endpoint] . '" style="margin-right: 10px; width: 1.25em; text-align: center;"></i>' : '';
                    echo '<a href="' . esc_url(wc_get_account_endpoint_url($endpoint)) . '">' . $icon . esc_html($label) . '</a>';
                    
                    echo '</li>';
                }
            }
        } else {
            // Fallback to default WooCommerce order if no Elementor settings
            foreach ($default_items as $endpoint => $label) {
                $classes = wc_get_account_menu_item_classes($endpoint);
                
                // Handle case where $classes might be a string instead of an array
                $class_attr = is_array($classes) ? implode(' ', $classes) : $classes;
                
                echo '<li class="' . $class_attr . '">';
                
                // Add icon to all menu items for consistent styling
                $icon = isset($icon_map[$endpoint]) ? '<i class="fas ' . $icon_map[$endpoint] . '" style="margin-right: 10px; width: 1.25em; text-align: center;"></i>' : '';
                echo '<a href="' . esc_url(wc_get_account_endpoint_url($endpoint)) . '">' . $icon . esc_html($label) . '</a>';
                
                echo '</li>';
            }
        }
        
        echo '</ul>';
        echo '</nav>';
    }
    
    protected function remove_render_hooks() {
        // Remove our filters
        remove_filter('woocommerce_account_menu_items', [$this, 'add_shortcuts_to_menu'], 10);
        
        if (Plugin::$instance->editor->is_edit_mode()) {
            // In editor mode, we didn't add these hooks, so don't try to remove them
            return;
        }
        
        // Remove parent widget's actions & filters
        remove_action('woocommerce_account_navigation', [$this, 'woocommerce_account_navigation'], 1);
        remove_filter('woocommerce_account_menu_items', [$this, 'modify_menu_items'], 10);
        remove_action('woocommerce_account_content', [$this, 'before_account_content'], 2);
        remove_action('woocommerce_account_content', [$this, 'after_account_content'], 95);
        remove_filter('woocommerce_get_myaccount_page_permalink', [$this, 'woocommerce_get_myaccount_page_permalink'], 10);
        remove_filter('woocommerce_logout_default_redirect_url', [$this, 'woocommerce_logout_default_redirect_url'], 10);
        remove_filter('woocommerce_is_account_page', '__return_true');

        if ($this->has_custom_template() && 'dashboard' === $this->get_current_endpoint()) {
            remove_action('woocommerce_account_content', [$this, 'display_custom_template'], 10);
            add_action('woocommerce_account_content', 'woocommerce_account_content', 10);
        }
        
        // Restore standard WooCommerce navigation if we removed it
        if (!has_action('woocommerce_account_navigation', 'woocommerce_account_navigation')) {
            add_action('woocommerce_account_navigation', 'woocommerce_account_navigation');
        }
    }

    protected function render_html_front_end() {
        // Ensure Font Awesome is loaded
        wp_enqueue_style('font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css');
        
        // Get settings
        $settings = $this->get_settings_for_display();
        
        // Get the current endpoint
        $current_endpoint = $this->get_current_endpoint();
        $custom_dashboard_class = '';
        if ('dashboard' === $current_endpoint && $this->has_custom_template() && is_user_logged_in()) {
            $custom_dashboard_class = 'e-my-account-tab__dashboard--custom';
        }
        
        // Debug: Log the current endpoint and menu items
        error_log('ShortcutsHub Debug - Current endpoint: ' . $current_endpoint);
        
        // Get menu items without modifying global hooks
        $menu_items = wc_get_account_menu_items();
        error_log('ShortcutsHub Debug - Menu items: ' . print_r($menu_items, true));
        
        // Add a wrapper class that includes the current endpoint
        echo '<div class="e-my-account-tab e-my-account-tab__' . sanitize_html_class($current_endpoint) . ' ' . $custom_dashboard_class . '">';
        
        // Add custom styles for layout
        ?>
        <style>
            /* Standard WooCommerce My Account layout */
            .e-my-account-tab .woocommerce {
                width: 100%;
                display: flex;
                flex-wrap: wrap;
            }
            .e-my-account-tab .woocommerce-MyAccount-navigation {
                float: left;
                width: 21%;
                margin-right: 4%;
            }
            .e-my-account-tab .woocommerce-MyAccount-content,
            .e-my-account-tab .woocommerce-MyAccount-content-wrapper {
                float: left;
                width: 75%;
                overflow: visible;
            }
            /* Clear floats */
            .e-my-account-tab .woocommerce::after {
                content: "";
                display: table;
                clear: both;
            }
            .e-my-account-tab .woocommerce-MyAccount-navigation ul {
                margin: 0;
                padding: 0;
                list-style: none;
            }
            .e-my-account-tab .woocommerce-MyAccount-navigation ul li {
                margin: 0;
                padding: 0;
            }
            .e-my-account-tab .woocommerce-MyAccount-navigation ul li a {
                display: flex;
                align-items: center;
                padding: 10px 15px;
                text-decoration: none;
                color: inherit;
                border-left: 2px solid transparent;
                font-size: 14px;
                line-height: 1.5;
            }
            .e-my-account-tab .woocommerce-MyAccount-navigation ul li.is-active a {
                background: rgba(0, 0, 0, 0.05);
                border-left-color: var(--e-p-border-global);
            }
            .e-my-account-tab .woocommerce-MyAccount-navigation ul li a i {
                width: 1.25em;
                text-align: center;
                margin-right: 10px;
                display: inline-block;
                vertical-align: middle;
            }
            .e-my-account-tab .e-wc-account-tabs-nav {
                margin-bottom: 0;
            }
            .e-my-account-tab .e-wc-account-tabs-nav .woocommerce-MyAccount-navigation {
                margin-bottom: 0;
            }
            .e-my-account-tab .woocommerce-MyAccount-content-wrapper > div {
                margin: 0;
            }
            /* Horizontal layout styles */
            .e-my-account-tab .woocommerce.horizontal-tabs {
                flex-direction: column;
            }
            .e-my-account-tab .woocommerce.horizontal-tabs .e-wc-account-tabs-nav {
                margin-bottom: 30px;
            }
            .e-my-account-tab .woocommerce.horizontal-tabs .woocommerce-MyAccount-navigation ul {
                display: flex;
                flex-wrap: wrap;
                gap: 10px;
            }
            .e-my-account-tab .woocommerce.horizontal-tabs .woocommerce-MyAccount-navigation ul li a {
                border-left: none;
                border-bottom: 2px solid transparent;
            }
            .e-my-account-tab .woocommerce.horizontal-tabs .woocommerce-MyAccount-navigation ul li.is-active a {
                border-bottom-color: var(--e-p-border-global);
            }
        </style>
        <?php
        
        // Use the standard WooCommerce shortcode to render the My Account page
        // This will handle all endpoints including our custom shortcuts endpoint
        echo '<div class="woocommerce ' . (isset($settings['tabs_layout']) && 'horizontal' === $settings['tabs_layout'] ? 'horizontal-tabs' : '') . '">';
        echo do_shortcode('[woocommerce_my_account]');
        echo '</div>';
        
        // Add script to ensure icons are displayed
        ?>
        <script>
        jQuery(document).ready(function($) {
            // Add icons to navigation links if they don't have them
            if ($('.woocommerce-MyAccount-navigation-link--shortcuts a i').length === 0) {
                $('.woocommerce-MyAccount-navigation-link--shortcuts a').prepend('<i class="fas fa-save" style="margin-right: 10px; width: 1.25em; text-align: center;"></i>');
            }
        });
        </script>
        <?php
        
        echo '</div>';
    }
    
    /**
     * Horizontal navigation for My Account tabs
     */
    public function woocommerce_account_navigation_horizontal() {
        ?>
        <div class="e-wc-account-tabs-nav">
            <?php wc_get_template('myaccount/navigation.php'); ?>
        </div>
        <?php
    }

    protected function render_html_editor() {
        $settings = $this->get_settings_for_display();
        $custom_dashboard_class = '';
        if ($this->has_custom_template() && is_user_logged_in()) {
            $custom_dashboard_class = 'e-my-account-tab__dashboard--custom';
        }
        
        // Ensure Font Awesome is loaded in the editor
        wp_enqueue_style('font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css');
        
        // Ensure the editor script has access to the AJAX variables
        if (!wp_script_is('elementor-editor', 'done')) {
            wp_enqueue_script('elementor-editor');
        }
        
        // Add the editor variables if not already added
        wp_localize_script('elementor-editor', 'shortcuts_hub_editor', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('shortcuts_hub_editor_nonce')
        ]);
        
        // Preload all endpoint content
        $endpoints = array('dashboard', 'orders', 'downloads', 'edit-address', 'edit-account', 'shortcuts', 'customer-logout');
        $endpoint_content = array();
        
        // Get content for each endpoint
        foreach ($endpoints as $endpoint) {
            ob_start();
            $this->get_endpoint_content_for_editor($endpoint);
            $endpoint_content[$endpoint] = ob_get_clean();
        }
        
        // Add inline script to ensure icons are properly displayed in the editor
        ?>
        <script>
        // Preload all endpoint content
        window.shortcutsHubEndpointContent = <?php echo json_encode($endpoint_content); ?>;
        
        jQuery(document).ready(function($) {
            // Function to add icons to navigation links
            function addIconsToNavLinks() {
                // Remove any existing icons first to prevent duplicates
                $(".woocommerce-MyAccount-navigation-link a i").remove();
                
                // Add icons to navigation links
                $(".woocommerce-MyAccount-navigation-link--dashboard a").prepend('<i class="fas fa-tachometer-alt" style="margin-right: 10px; width: 1.25em; text-align: center;"></i>');
                $(".woocommerce-MyAccount-navigation-link--orders a").prepend('<i class="fas fa-shopping-cart" style="margin-right: 10px; width: 1.25em; text-align: center;"></i>');
                $(".woocommerce-MyAccount-navigation-link--downloads a").prepend('<i class="fas fa-download" style="margin-right: 10px; width: 1.25em; text-align: center;"></i>');
                $(".woocommerce-MyAccount-navigation-link--edit-address a").prepend('<i class="fas fa-map-marker-alt" style="margin-right: 10px; width: 1.25em; text-align: center;"></i>');
                $(".woocommerce-MyAccount-navigation-link--edit-account a").prepend('<i class="fas fa-user" style="margin-right: 10px; width: 1.25em; text-align: center;"></i>');
                $(".woocommerce-MyAccount-navigation-link--shortcuts a").prepend('<i class="fas fa-save" style="margin-right: 10px; width: 1.25em; text-align: center;"></i>');
                $(".woocommerce-MyAccount-navigation-link--customer-logout a").prepend('<i class="fas fa-sign-out-alt" style="margin-right: 10px; width: 1.25em; text-align: center;"></i>');
            }
            
            // Run immediately
            addIconsToNavLinks();
            
            // Also run after a short delay to ensure it works after Elementor renders
            setTimeout(addIconsToNavLinks, 500);
        });
        </script>
        <?php
        
        // Add script to ensure icons appear in the editor
        ?>
        <script type="text/javascript">
        // Define the shortcuts_hub_editor object directly in the page
        var shortcuts_hub_editor = {
            ajaxurl: '<?php echo esc_url(admin_url('admin-ajax.php')); ?>',
            nonce: '<?php echo wp_create_nonce('shortcuts_hub_editor_nonce'); ?>'
        };
        
        jQuery(document).ready(function($) {
            // Force add the icon to the shortcuts link in editor
            setTimeout(function() {
                // First, remove any existing icons to prevent duplicates
                $('.woocommerce-MyAccount-navigation-link a i.fas').remove();
                
                // Add icons to all menu items to ensure consistent styling
                var iconMap = {
                    'dashboard': 'fa-tachometer-alt',
                    'orders': 'fa-shopping-cart',
                    'downloads': 'fa-download',
                    'edit-address': 'fa-map-marker-alt',
                    'edit-account': 'fa-user',
                    'shortcuts': 'fa-save',
                    'customer-logout': 'fa-sign-out-alt'
                };
                
                // Add icons to all menu items
                $('.woocommerce-MyAccount-navigation-link').each(function() {
                    var $item = $(this);
                    var $link = $item.find('a');
                    var classes = $item.attr('class');
                    var endpoint = '';
                    
                    // Extract the endpoint from class name
                    for (var key in iconMap) {
                        if (classes.indexOf('--' + key) > -1) {
                            endpoint = key;
                            break;
                        }
                    }
                    
                    if (endpoint && iconMap[endpoint]) {
                        $link.prepend('<i class="fas ' + iconMap[endpoint] + '" style="margin-right: 10px; width: 1.25em; text-align: center;"></i>');
                    }
                });
            }, 100);
        });
        </script>
        <?php
        
        // Add custom styles for layout
        ?>
        <style>
            /* Standard WooCommerce My Account layout */
            .e-my-account-tab .woocommerce {
                width: 100%;
                display: flex;
                flex-wrap: wrap;
            }
            .e-my-account-tab .woocommerce-MyAccount-navigation {
                float: left;
                width: 18%;
                margin-right: 2%;
            }
            .e-my-account-tab .woocommerce-MyAccount-content,
            .e-my-account-tab .woocommerce-MyAccount-content-wrapper {
                float: left;
                width: 80%;
                overflow: visible;
                box-sizing: border-box;
                max-width: 100%;
                padding-right: 0;
            }
            /* Full width content wrapper */
            .e-my-account-tab .woocommerce-MyAccount-content-wrapper.shortcuts-content {
                width: 100%;
            }
            /* Ensure tables don't overflow */
            .e-my-account-tab table {
                width: 100%;
                table-layout: auto;
                word-wrap: break-word;
            }
            /* Responsive tables */
            .e-my-account-tab .woocommerce-orders-table,
            .e-my-account-tab .shortcuts-table {
                width: 100%;
                margin-bottom: 20px;
            }
            /* Table columns sizing */
            .e-my-account-tab .shortcuts-table .shortcut-name-column {
                width: 40%;
            }
            .e-my-account-tab .shortcuts-table .shortcut-version-column {
                width: 15%;
            }
            .e-my-account-tab .shortcuts-table .shortcut-date-column {
                width: 20%;
            }
            .e-my-account-tab .shortcuts-table .shortcut-actions-column {
                width: 25%;
            }
            .e-my-account-tab .shortcuts-table-container {
                width: 100%;
                overflow-x: visible;
            }
            /* Clear floats */
            .e-my-account-tab .woocommerce::after {
                content: "";
                display: table;
                clear: both;
            }
            .e-my-account-tab .woocommerce-MyAccount-navigation ul {
                margin: 0;
                padding: 0;
                list-style: none;
            }
            .e-my-account-tab .woocommerce-MyAccount-navigation ul li {
                margin: 0;
                padding: 0;
            }
            .e-my-account-tab .woocommerce-MyAccount-navigation ul li a {
                display: flex;
                align-items: center;
                padding: 10px 15px;
                text-decoration: none;
                color: inherit;
                border-left: 2px solid transparent;
                font-size: 14px;
                line-height: 1.5;
            }
            .e-my-account-tab .woocommerce-MyAccount-navigation ul li.is-active a {
                background: rgba(0, 0, 0, 0.05);
                border-left-color: var(--e-p-border-global);
            }
            .e-my-account-tab .woocommerce-MyAccount-navigation ul li a i {
                width: 1.25em;
                text-align: center;
                margin-right: 10px;
                display: inline-block;
                vertical-align: middle;
            }
            .e-my-account-tab .e-wc-account-tabs-nav {
                margin-bottom: 0;
            }
            .e-my-account-tab .e-wc-account-tabs-nav .woocommerce-MyAccount-navigation {
                margin-bottom: 0;
            }
            .e-my-account-tab .woocommerce-MyAccount-content-wrapper > div {
                margin: 0;
            }
            /* Horizontal layout styles */
            .e-my-account-tab .woocommerce.horizontal-tabs {
                flex-direction: column;
            }
            .e-my-account-tab .woocommerce.horizontal-tabs .e-wc-account-tabs-nav {
                margin-bottom: 30px;
            }
            .e-my-account-tab .woocommerce.horizontal-tabs .woocommerce-MyAccount-navigation ul {
                display: flex;
                flex-wrap: wrap;
                gap: 10px;
            }
            .e-my-account-tab .woocommerce.horizontal-tabs .woocommerce-MyAccount-navigation ul li a {
                border-left: none;
                border-bottom: 2px solid transparent;
            }
            .e-my-account-tab .woocommerce.horizontal-tabs .woocommerce-MyAccount-navigation ul li.is-active a {
                border-bottom-color: var(--e-p-border-global);
            }
            /* Horizontal layout - make content full width */
            .e-my-account-tab .woocommerce.horizontal-tabs .woocommerce-MyAccount-content,
            .e-my-account-tab .woocommerce.horizontal-tabs .woocommerce-MyAccount-content-wrapper {
                width: 100%;
                float: none;
                padding-right: 0;
            }
            /* Responsive styles */
            @media (max-width: 768px) {
                .e-my-account-tab .woocommerce-MyAccount-navigation,
                .e-my-account-tab .woocommerce-MyAccount-content,
                .e-my-account-tab .woocommerce-MyAccount-content-wrapper {
                    width: 100%;
                    float: none;
                    margin-right: 0;
                }
                .e-my-account-tab .woocommerce-MyAccount-navigation {
                    margin-bottom: 20px;
                }
            }
        </style>
        <?php
        
        echo '<div class="e-my-account-tab e-my-account-tab__dashboard ' . $custom_dashboard_class . '">';
        ?>
            <div class="woocommerce <?php echo isset($settings['tabs_layout']) && 'horizontal' === $settings['tabs_layout'] ? 'horizontal-tabs' : ''; ?>">
                <?php
                if (isset($settings['tabs_layout']) && 'horizontal' === $settings['tabs_layout']) {
                    ?>
                    <div class="e-wc-account-tabs-nav">
                        <?php wc_get_template('myaccount/navigation.php'); ?>
                    </div>
                    <?php
                } else {
                    wc_get_template('myaccount/navigation.php');
                }
                ?>

                <div class="woocommerce-MyAccount-content">
                    <?php
                    /**
                     * Let WooCommerce handle the endpoint content
                     * This will automatically display the correct content based on the current endpoint
                     * We now use this for both editor and frontend
                     */
                    do_action('woocommerce_account_content');
                    ?>
                </div>
                </div>
            </div>
        </div>

        <script>
        jQuery(document).ready(function($) {
            // Clean up any duplicate icons that might exist
            $('.woocommerce-MyAccount-navigation-link a').each(function() {
                var $icons = $(this).find('i');
                if ($icons.length > 1) {
                    $icons.not(':first').remove();
                }
            });
            
            // Apply styling in editor mode
            if (window.elementorFrontend && elementorFrontend.isEditMode()) {
                $('.e-my-account-tab .woocommerce').css('overflow', 'hidden');
                $('.e-my-account-tab .woocommerce-MyAccount-navigation').css({
                    'float': 'left',
                    'width': '21%',
                    'margin-right': '4%'
                });
                $('.e-my-account-tab .woocommerce-MyAccount-content').css({
                    'float': 'left',
                    'width': '75%'
                });
                
                // Handle tab clicks in the editor
                $('.woocommerce-MyAccount-navigation-link a').on('click', function(e) {
                    e.preventDefault();
                    
                    // Remove active class from all tabs
                    $('.woocommerce-MyAccount-navigation-link').removeClass('is-active');
                    
                    // Add active class to clicked tab
                    $(this).parent().addClass('is-active');
                    
                    // Get the endpoint from the class
                    var classes = $(this).parent().attr('class');
                    var endpoint = '';
                    
                    // Extract the endpoint from class name
                    var iconMap = {
                        'dashboard': 'fa-tachometer-alt',
                        'orders': 'fa-shopping-cart',
                        'downloads': 'fa-download',
                        'edit-address': 'fa-map-marker-alt',
                        'edit-account': 'fa-user',
                        'shortcuts': 'fa-save',
                        'customer-logout': 'fa-sign-out-alt'
                    };
                    
                    for (var key in iconMap) {
                        if (classes.indexOf('--' + key) > -1) {
                            endpoint = key;
                            break;
                        }
                    }
                    
                    // Show appropriate content based on endpoint
                    showEndpointContent(endpoint);
                });
                
                // Function to show content based on endpoint
                function showEndpointContent(endpoint) {
                    var $content = $('.woocommerce-MyAccount-content');
                    
                    // Clear existing content
                    $content.empty();
                    
                    // Show loading indicator
                    $content.html('<p>Loading content...</p>');
                    
                    // Use the preloaded endpoint content instead of making AJAX requests
                    if (window.shortcutsHubEndpointContent && window.shortcutsHubEndpointContent[endpoint]) {
                        // Use the cached content
                        $content.html(window.shortcutsHubEndpointContent[endpoint]);
                    } else {
                        // Fallback to a simple message if content isn't available
                        $content.html('<p>Loading ' + endpoint + ' content...</p>');
                    }
                }
                
                // Set dashboard as active by default if no tab is active
                if ($('.woocommerce-MyAccount-navigation-link.is-active').length === 0) {
                    $('.woocommerce-MyAccount-navigation-link--dashboard').addClass('is-active');
                    showEndpointContent('dashboard');
                }
            }
            
            // Make sure navigation links work with the default WooCommerce behavior
            $('.woocommerce-MyAccount-navigation-link a').each(function() {
                var $link = $(this);
                // Keep the original href which points to the correct endpoint URL
                // This allows WooCommerce's default navigation to work
            });
        });
        </script>
        <?php
    }

    protected function get_account_content_wrapper($args = []) {
        $context = isset($args['context']) ? $args['context'] : 'frontend';
        
        try {
            $current_endpoint = $this->get_current_endpoint();
            
            $classes = [
                'woocommerce-MyAccount-content-wrapper',
                'e-my-account-tab__' . sanitize_html_class($current_endpoint)
            ];

            if ($current_endpoint === 'dashboard' && $this->has_custom_template() && is_user_logged_in()) {
                $classes[] = 'e-my-account-tab__dashboard--custom';
            }

            if ($context === 'editor') {
                $classes[] = 'e-my-account-tab__content';
            }

            return implode(' ', array_unique($classes));
        } catch (\Exception $e) {
            // If there's an error, return a default class
            return 'woocommerce-MyAccount-content-wrapper e-my-account-tab__shortcuts';
        }
    }

    protected function should_print_empty() {
        return false;
    }

    /**
     * Generate endpoint content for the editor
     * 
     * @param string $endpoint The endpoint to generate content for
     */
    protected function get_endpoint_content_for_editor($endpoint) {
        // Simulate being on the my-account page with the specified endpoint
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
                // Get customer orders
                $customer_orders = wc_get_orders(array(
                    'customer_id' => get_current_user_id(),
                    'limit' => 10,
                    'paginate' => true
                ));
                
                // Set has_orders variable to avoid warnings
                $has_orders = 0 < $customer_orders->total;
                
                // Set template variables
                wc_get_template(
                    'myaccount/orders.php',
                    array(
                        'current_user'    => get_user_by('id', get_current_user_id()),
                        'customer_orders' => $customer_orders,  // This is what was missing
                        'has_orders'      => $has_orders,
                        'order_count'     => wc_get_customer_order_count(get_current_user_id()),
                    )
                );
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
                // Load the actual shortcuts template
                $template_path = SHORTCUTS_HUB_PLUGIN_DIR . 'templates/myaccount/shortcuts.php';
                if (file_exists($template_path)) {
                    // Buffer the output
                    ob_start();
                    include $template_path;
                    $content = ob_get_clean();
                    echo $content;
                } else {
                    // Fallback if template doesn't exist
                    echo '<h3>' . __('Shortcuts', 'shortcuts-hub') . '</h3>';
                    echo '<p>' . __('You don\'t have any shortcuts yet. Browse the site and save shortcuts to access them quickly later.', 'shortcuts-hub') . '</p>';
                }
                break;
                
            case 'customer-logout':
                echo '<p>' . __('You will be logged out and redirected to the homepage.', 'shortcuts-hub') . '</p>';
                break;
                
            default:
                // For any other endpoint, try to load the template
                do_action('woocommerce_account_' . $endpoint . '_endpoint');
                break;
        }
        
        // Restore the original WP object
        $wp = $old_wp;
    }
    
    private function get_available_endpoints_html() {
        $endpoints = wc_get_account_menu_items();
        $html = '';
        
        // Define icon map for all menu items
        $icon_map = [
            'dashboard' => 'fa-tachometer-alt',
            'orders' => 'fa-shopping-cart',
            'downloads' => 'fa-download',
            'edit-address' => 'fa-map-marker-alt',
            'edit-account' => 'fa-user',
            'shortcuts' => 'fa-save',
            'customer-logout' => 'fa-sign-out-alt'
        ];
        
        foreach ($endpoints as $endpoint => $label) {
            $classes = wc_get_account_menu_item_classes($endpoint);
            $class_attr = is_array($classes) ? implode(' ', $classes) : $classes;
            
            // Add class to ensure proper icon handling in JavaScript
            $class_attr .= ' woocommerce-MyAccount-navigation-link--' . $endpoint;
            
            // Add the icon directly in the HTML - consistent with frontend
            $icon_html = '';
            if (isset($icon_map[$endpoint])) {
                $icon_html = '<i class="fas ' . $icon_map[$endpoint] . '"></i> ';
            }
            
            $html .= '<li class="' . esc_attr($class_attr) . '">
                <a href="#">' . $icon_html . esc_html($label) . '</a>
            </li>';
        }
        return $html;
    }

    public function add_shortcuts_to_menu($items) {
        $settings = $this->get_settings_for_display();
        
        // If we're in the editor and have tabs settings, use those
        if (Plugin::$instance->editor->is_edit_mode() && !empty($settings['tabs'])) {
            $new_items = [];
            foreach ($settings['tabs'] as $tab) {
                if (!empty($tab['field_key']) && !empty($tab['is_visible']) && $tab['is_visible'] === 'yes') {
                    $new_items[$tab['field_key']] = $tab['tab_name'];
                }
            }
            return $new_items;
        }
        
        // For frontend, insert our shortcuts endpoint before the logout item
        $logout = false;
        if (isset($items['customer-logout'])) {
            $logout = $items['customer-logout'];
            unset($items['customer-logout']);
        }
        
        // Add shortcuts endpoint
        $items['shortcuts'] = __('Shortcuts', 'shortcuts-hub');
        
        // Add logout back at the end if it existed
        if ($logout) {
            $items['customer-logout'] = $logout;
        }
        
        // Make sure the endpoint is properly registered with WooCommerce
        if (!has_action('woocommerce_account_shortcuts_endpoint', [$this, 'shortcuts_endpoint_content'])) {
            add_action('woocommerce_account_shortcuts_endpoint', [$this, 'shortcuts_endpoint_content']);
        }
        
        // Force add the class directly to the menu items
        add_filter('woocommerce_account_menu_item_classes', function($classes, $endpoint) {
            if ($endpoint === 'shortcuts') {
                // Ensure the class is added for the shortcuts endpoint
                if (!in_array('woocommerce-MyAccount-navigation-link--shortcuts', $classes)) {
                    $classes[] = 'woocommerce-MyAccount-navigation-link--shortcuts';
                }
            }
            return $classes;
        }, 10, 2);
        
        return $items;
    }

    public function add_endpoints() {
        // Register the shortcuts endpoint
        add_rewrite_endpoint('shortcuts', EP_ROOT | EP_PAGES);
        
        // Only flush rewrite rules if the flag is set
        if (get_option('shortcuts_hub_flush_rewrite_rules')) {
            flush_rewrite_rules(false);
            delete_option('shortcuts_hub_flush_rewrite_rules');
        }
    }
    
    /**
     * Make sure the Shortcuts endpoint is registered during plugin activation
     */
    public static function register_on_activation() {
        // Add the endpoint
        add_rewrite_endpoint('shortcuts', EP_ROOT | EP_PAGES);
        
        // Set the flag to flush rewrite rules on next init
        update_option('shortcuts_hub_flush_rewrite_rules', true);
    }
    
    /**
     * Handle the Shortcuts endpoint content
     */
    public function handle_shortcuts_endpoint() {
        // Make sure the shortcuts endpoint is properly registered with WooCommerce
        if (!has_action('woocommerce_account_shortcuts_endpoint')) {
            // The content is handled by the woocommerce_account_shortcuts_endpoint hook in shortcuts-hub.php
            // We don't need to add our own handler
        }
        return;
    }
    


    // The shortcuts_endpoint_content method has been removed
    // The content is now handled by the woocommerce_account_shortcuts_endpoint hook in shortcuts-hub.php

    public function get_available_endpoints() {
        // This method is only used for the initial setup of the repeater control
        // We're using standard WooCommerce endpoints now, so we don't need this anymore
        return [];
    }

    public function get_title() {
        return esc_html__('My Account', 'shortcuts-hub');
    }

    public function get_style_depends(): array {
        // Get parent styles
        $styles = parent::get_style_depends();
        
        // Add WooCommerce styles
        if (wp_style_is('woocommerce-general', 'registered')) {
            $styles[] = 'woocommerce-general';
        }
        
        // Add theme styles if they exist
        $theme_style = wp_get_theme()->get_stylesheet();
        if (wp_style_is($theme_style . '-woocommerce-style', 'registered')) {
            $styles[] = $theme_style . '-woocommerce-style';
        }
        
        // Add our custom styles
        $styles[] = 'shortcuts-hub-my-account';
        $styles[] = 'elementor-icons-fa-solid';
        
        return $styles;
    }

    public function get_script_depends(): array {
        return ['woocommerce-my-account'];
    }

    public function enqueue_editor_scripts() {
        wp_enqueue_script('woocommerce');
        wp_enqueue_script('elementor-frontend');
        
        // Add AJAX nonce for secure endpoint content loading
        wp_localize_script('elementor-frontend', 'shortcuts_hub_editor', [
            'nonce' => wp_create_nonce('shortcuts_hub_editor_nonce'),
            'ajaxurl' => admin_url('admin-ajax.php')
        ]);
        
        // Register AJAX handler for endpoint content
        add_action('wp_ajax_shortcuts_hub_get_endpoint_content', [$this, 'ajax_get_endpoint_content']);
    }
    
    /**
     * AJAX handler to get endpoint content
     */
    public function ajax_get_endpoint_content() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'shortcuts_hub_editor_nonce')) {
            wp_send_json_error('Security check failed');
        }
        
        // Get the endpoint
        $endpoint = isset($_POST['endpoint']) ? sanitize_text_field($_POST['endpoint']) : 'dashboard';
        
        // Start output buffering to capture the template output
        ob_start();
        
        // Render the appropriate template based on endpoint
        switch ($endpoint) {
            case 'dashboard':
                wc_get_template('myaccount/dashboard.php', [
                    'current_user' => wp_get_current_user(),
                ]);
                break;
            case 'orders':
                wc_get_template('myaccount/orders.php', [
                    'current_user' => wp_get_current_user(),
                    'customer_orders' => [],
                    'has_orders' => false,
                ]);
                break;
            case 'downloads':
                wc_get_template('myaccount/downloads.php', [
                    'downloads' => [],
                    'has_downloads' => false,
                ]);
                break;
            case 'edit-address':
                wc_get_template('myaccount/my-address.php', [
                    'current_user' => wp_get_current_user(),
                ]);
                break;
            case 'edit-account':
                wc_get_template('myaccount/form-edit-account.php', [
                    'user' => wp_get_current_user(),
                ]);
                break;
            case 'shortcuts':
                // Use our custom shortcuts template
                include_once(SHORTCUTS_HUB_PATH . 'templates/myaccount/shortcuts.php');
                break;
            case 'customer-logout':
                echo '<p>You will be logged out and redirected to the homepage.</p>';
                break;
            default:
                wc_get_template('myaccount/dashboard.php', [
                    'current_user' => wp_get_current_user(),
                ]);
                break;
        }
        
        // Get the output and clean the buffer
        $content = ob_get_clean();
        
        // Send the response
        wp_send_json_success($content);
    }

    private function get_account_wrapper_class($page) {
        $class = 'woocommerce-MyAccount-content-wrapper';
        
        if ($page === 'dashboard') {
            $class .= ' e-my-account-tab__dashboard';
            
            if ($this->has_custom_template() && is_user_logged_in()) {
                $class .= '--custom';
            }
        }
        
        return $class;
    }

    protected function get_account_pages() {
        // First check if we're in a context where Elementor settings are available
        try {
            $settings = $this->get_settings_for_display();
            
            // If we're in the editor and have tabs settings, use those
            if (class_exists('\Elementor\Plugin') && 
                \Elementor\Plugin::$instance->editor->is_edit_mode() && 
                !empty($settings) && 
                !empty($settings['tabs'])) {
                
                $pages = [];
                foreach ($settings['tabs'] as $tab) {
                    if (!empty($tab['field_key']) && !empty($tab['is_visible']) && $tab['is_visible'] === 'yes') {
                        $pages[$tab['field_key']] = '';
                    }
                }
                return $pages;
            }
        } catch (\Exception $e) {
            // If there's an error getting settings, continue to use standard WooCommerce pages
        }
        
        // Get standard WooCommerce account pages
        $items = wc_get_account_menu_items();
        $pages = [];
        
        // Get all available endpoints
        foreach ($items as $endpoint => $label) {
            $pages[$endpoint] = '';
        }
        
        // Add our shortcuts endpoint if not already present
        if (!isset($pages['shortcuts'])) {
            $pages['shortcuts'] = '';
        }
        
        return $pages;
    }

    protected function get_current_endpoint() {
        global $wp_query;
        $current = '';
        
        try {
            $pages = $this->get_account_pages();
            
            if (is_array($pages)) {
                foreach ($pages as $endpoint => $label) {
                    if (isset($wp_query->query[$endpoint])) {
                        $current = $endpoint;
                        break;
                    }
                }
            }
        } catch (\Exception $e) {
            // If there's an error getting pages, continue with default endpoint
        }

        if (empty($current)) {
            $current = 'dashboard';
        }

        return $current;
    }

    protected function has_data() {
        return !empty($this->get_settings_for_display());
    }
    
    /**
     * Override the parent method to handle undefined array key "customize_dashboard_check"
     * Check if the My Account dashboard intro content is replaced with a custom Elementor template
     *
     * @return boolean
     */
    public function has_custom_template() {
        $settings = $this->get_settings_for_display();
        
        // Check if the key exists before accessing it
        if (isset($settings['customize_dashboard_check']) && 'yes' === $settings['customize_dashboard_check']) {
            return isset($settings['customize_dashboard_select']) && !empty($settings['customize_dashboard_select']);
        }
        
        return false;
    }
    
    /**
     * Override the parent method to handle undefined array key "customize_dashboard_check"
     * Get the template_id for the dashboard intro section if a custom template should be displayed
     *
     * @return int
     */
    public function get_dashboard_template_id() {
        $settings = $this->get_settings_for_display();
        
        // Check if the key exists before accessing it
        if (isset($settings['customize_dashboard_check']) && 'yes' === $settings['customize_dashboard_check']) {
            return isset($settings['customize_dashboard_select']) ? intval($settings['customize_dashboard_select']) : 0;
        }
        
        return 0;
    }

    protected function register_controls() {
        // Check if controls have already been registered for this instance
        if ($this->controls_registered) {
            return;
        }
        $this->controls_registered = true;
        
        // Get a unique ID for this widget instance
        $widget_id = $this->get_id();
        $instance_id = spl_object_hash($this);
        
        // Use transient with a unique key for this specific widget instance
        $transient_key = 'sh_controls_registered_' . $widget_id . '_' . $instance_id;
        if (get_transient($transient_key)) {
            return;
        }
        set_transient($transient_key, true, MINUTE_IN_SECONDS * 5); // Set for 5 minutes
        
        // Load parent controls first
        parent::register_controls();

        // Remove the parent's tabs control to replace with our own
        $this->remove_control('tabs');

        // Add our custom endpoints section with drag-and-drop functionality
        $this->start_controls_section(
            'section_endpoints',
            [
                'label' => esc_html__('Endpoints', 'shortcuts-hub'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );

        $repeater = new \Elementor\Repeater();

        $repeater->add_control(
            'is_visible',
            [
                'label' => esc_html__('Show Endpoint', 'shortcuts-hub'),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => esc_html__('Show', 'shortcuts-hub'),
                'label_off' => esc_html__('Hide', 'shortcuts-hub'),
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );

        $repeater->add_control(
            'field_key',
            [
                'label' => esc_html__('Field Key', 'shortcuts-hub'),
                'type' => Controls_Manager::TEXT,
                'dynamic' => [
                    'active' => true,
                ],
            ]
        );

        $repeater->add_control(
            'tab_name',
            [
                'label' => esc_html__('Tab Name', 'shortcuts-hub'),
                'type' => Controls_Manager::TEXT,
                'dynamic' => [
                    'active' => true,
                ],
            ]
        );

        // Get standard WooCommerce endpoints plus our Shortcuts endpoint
        $default_endpoints = [
            [
                'is_visible' => 'yes',
                'field_key' => 'dashboard',
                'tab_name' => esc_html__('Dashboard', 'woocommerce')
            ],
            [
                'is_visible' => 'yes',
                'field_key' => 'orders',
                'tab_name' => esc_html__('Orders', 'woocommerce')
            ],
            [
                'is_visible' => 'yes',
                'field_key' => 'downloads',
                'tab_name' => esc_html__('Downloads', 'woocommerce')
            ],
            [
                'is_visible' => 'yes',
                'field_key' => 'edit-address',
                'tab_name' => esc_html__('Addresses', 'woocommerce')
            ],
            [
                'is_visible' => 'yes',
                'field_key' => 'edit-account',
                'tab_name' => esc_html__('Account Details', 'woocommerce')
            ],
            [
                'is_visible' => 'yes',
                'field_key' => 'shortcuts',
                'tab_name' => esc_html__('Shortcuts', 'shortcuts-hub')
            ],
            [
                'is_visible' => 'yes',
                'field_key' => 'customer-logout',
                'tab_name' => esc_html__('Logout', 'woocommerce')
            ],
        ];

        $this->add_control(
            'tabs',
            [
                'label' => esc_html__('Account Endpoints', 'shortcuts-hub'),
                'type' => Controls_Manager::REPEATER,
                'fields' => $repeater->get_controls(),
                'default' => $default_endpoints,
                'title_field' => '{{{ tab_name }}}',
            ]
        );

        $this->end_controls_section();
        
        // Add custom style section for tables
        $this->start_controls_section(
            'section_table_style',
            [
                'label' => esc_html__('Tables', 'shortcuts-hub'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );
        
        $this->add_control(
            'table_heading_style',
            [
                'label' => esc_html__('Table Headers', 'shortcuts-hub'),
                'type' => Controls_Manager::HEADING,
                'separator' => 'before',
            ]
        );
        
        $this->add_control(
            'table_header_color',
            [
                'label' => esc_html__('Header Color', 'shortcuts-hub'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .shortcuts-table thead th' => 'color: {{VALUE}};',
                ],
                'default' => '#909CFE',
            ]
        );
        
        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name' => 'table_header_typography',
                'selector' => '{{WRAPPER}} .shortcuts-table thead th',
            ]
        );
        
        $this->add_control(
            'table_content_style',
            [
                'label' => esc_html__('Table Content', 'shortcuts-hub'),
                'type' => Controls_Manager::HEADING,
                'separator' => 'before',
            ]
        );
        
        $this->add_control(
            'table_text_color',
            [
                'label' => esc_html__('Text Color', 'shortcuts-hub'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .shortcuts-table td' => 'color: {{VALUE}};',
                    '{{WRAPPER}} .shortcut-name-text' => 'color: {{VALUE}};',
                    '{{WRAPPER}} .shortcut-name-text a' => 'color: {{VALUE}};',
                ],
                'default' => '#CACACA',
            ]
        );
        
        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name' => 'table_content_typography',
                'selector' => '{{WRAPPER}} .shortcuts-table td, {{WRAPPER}} .shortcut-name-text',
            ]
        );
        
        $this->add_control(
            'table_icon_style',
            [
                'label' => esc_html__('Table Icons', 'shortcuts-hub'),
                'type' => Controls_Manager::HEADING,
                'separator' => 'before',
            ]
        );
        
        $this->add_control(
            'table_icon_color',
            [
                'label' => esc_html__('Icon Color', 'shortcuts-hub'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .shortcut-action-button i' => 'color: {{VALUE}};',
                ],
                'default' => '#909CFE',
            ]
        );
        
        $this->add_control(
            'table_icon_hover_color',
            [
                'label' => esc_html__('Icon Hover Color', 'shortcuts-hub'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .shortcut-action-button:hover i' => 'color: {{VALUE}};',
                ],
                'default' => '#FFFFFF',
            ]
        );
        
        $this->end_controls_section();
        
        // Add tabs style section to override parent's tab styling
        $this->start_controls_section(
            'section_tabs_style',
            [
                'label' => esc_html__('Tabs', 'shortcuts-hub'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );
        
        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name' => 'tabs_typography',
                'selector' => '{{WRAPPER}} .e-my-account-tab .woocommerce .woocommerce-MyAccount-navigation ul li a',
            ]
        );
        
        $this->start_controls_tabs('tabs_section');
        
        $this->start_controls_tab('tabs_normal', ['label' => esc_html__('Normal', 'shortcuts-hub')]);
        
        $this->add_control(
            'tabs_normal_color',
            [
                'label' => esc_html__('Color', 'shortcuts-hub'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .e-my-account-tab .woocommerce .woocommerce-MyAccount-navigation ul li a' => 'color: {{VALUE}};',
                    '{{WRAPPER}}' => '--tabs-normal-color: {{VALUE}};',
                ],
            ]
        );
        
        $this->end_controls_tab();
        
        $this->start_controls_tab('tabs_hover', ['label' => esc_html__('Hover', 'shortcuts-hub')]);
        
        $this->add_control(
            'tabs_hover_color',
            [
                'label' => esc_html__('Color', 'shortcuts-hub'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .e-my-account-tab .woocommerce .woocommerce-MyAccount-navigation ul li a:hover' => 'color: {{VALUE}} !important;',
                    '{{WRAPPER}}' => '--tabs-hover-color: {{VALUE}};',
                ],
            ]
        );
        
        $this->end_controls_tab();
        
        $this->start_controls_tab('tabs_active', ['label' => esc_html__('Active', 'shortcuts-hub')]);
        
        $this->add_control(
            'tabs_active_color',
            [
                'label' => esc_html__('Color', 'shortcuts-hub'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .e-my-account-tab .woocommerce .woocommerce-MyAccount-navigation ul li.is-active a' => 'color: {{VALUE}};',
                    '{{WRAPPER}}' => '--tabs-active-color: {{VALUE}};',
                ],
            ]
        );
        
        $this->end_controls_tab();
        
        $this->end_controls_tabs();
        
        $this->add_control(
            'tabs_icon_heading',
            [
                'label' => esc_html__('Icons', 'shortcuts-hub'),
                'type' => Controls_Manager::HEADING,
                'separator' => 'before',
            ]
        );
        
        $this->add_control(
            'tabs_icon_color',
            [
                'label' => esc_html__('Icon Color', 'shortcuts-hub'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .e-my-account-tab .woocommerce .woocommerce-MyAccount-navigation ul li a:before' => 'color: {{VALUE}};',
                ],
            ]
        );
        
        $this->add_control(
            'tabs_icon_hover_color',
            [
                'label' => esc_html__('Icon Hover Color', 'shortcuts-hub'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .e-my-account-tab .woocommerce .woocommerce-MyAccount-navigation ul li a:hover:before' => 'color: {{VALUE}} !important;',
                ],
            ]
        );
        
        $this->add_control(
            'tabs_icon_active_color',
            [
                'label' => esc_html__('Icon Active Color', 'shortcuts-hub'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .e-my-account-tab .woocommerce .woocommerce-MyAccount-navigation ul li.is-active a:before' => 'color: {{VALUE}};',
                ],
            ]
        );
        
        $this->end_controls_section();
    }
}