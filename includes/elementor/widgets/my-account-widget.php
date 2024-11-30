<?php
namespace ShortcutsHub\Elementor\Widgets;
use Elementor\Plugin;
use ElementorPro\Modules\Woocommerce\Widgets\My_Account as Elementor_My_Account;
use Elementor\Controls_Manager;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class My_Account_Widget extends Elementor_My_Account {

    public function __construct($data = [], $args = []) {
        parent::__construct($data, $args);
    }

    public function get_html_wrapper_class() {
        $classes = parent::get_html_wrapper_class();
        
        if (is_array($classes)) {
            $classes = array_diff($classes, ['elementor-widget-empty']);
        }
        
        return $classes;
    }

    protected function render() {
        // Add filters and actions before rendering
        $this->add_render_hooks();
        
        // Display our Widget
        if (!\Elementor\Plugin::$instance->editor->is_edit_mode()) {
            $this->render_html_front_end();
        } else {
            $this->render_html_editor();
        }

        // Remove filters and actions after rendering
        $this->remove_render_hooks();
    }

    protected function add_render_hooks() {
        // Add our filter before rendering
        add_filter('woocommerce_account_menu_items', [$this, 'add_shortcuts_to_menu'], 10, 1);
        add_action('woocommerce_account_shortcuts_endpoint', [$this, 'shortcuts_endpoint_content']);

        // Add parent widget's actions & filters
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

    protected function remove_render_hooks() {
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

        // Remove our filters
        remove_filter('woocommerce_account_menu_items', [$this, 'add_shortcuts_to_menu'], 10);
        remove_action('woocommerce_account_shortcuts_endpoint', [$this, 'shortcuts_endpoint_content']);
    }

    protected function render_html_front_end() {
        $current_endpoint = $this->get_current_endpoint();
        $custom_dashboard_class = '';
        if ('dashboard' === $current_endpoint && $this->has_custom_template() && is_user_logged_in()) {
            $custom_dashboard_class = 'e-my-account-tab__dashboard--custom';
        }
        echo '<div class="e-my-account-tab e-my-account-tab__' . sanitize_html_class($current_endpoint) . ' ' . $custom_dashboard_class . '">';
        echo do_shortcode('[woocommerce_my_account]');
        echo '</div>';
    }

    protected function render_html_editor() {
        $settings = $this->get_settings_for_display();
        $custom_dashboard_class = '';
        if ($this->has_custom_template() && is_user_logged_in()) {
            $custom_dashboard_class = 'e-my-account-tab__dashboard--custom';
        }
        
        // Add custom styles for layout
        ?>
        <style>
            .e-my-account-tab .woocommerce {
                display: flex;
                gap: 30px;
                width: 100%;
            }
            .e-my-account-tab .woocommerce-MyAccount-navigation {
                flex: 0 0 260px;
                margin-right: 0;
            }
            .e-my-account-tab .woocommerce-MyAccount-content-wrapper {
                flex: 1;
                min-width: 0;
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
                display: block;
                padding: 12px 16px;
                text-decoration: none;
                color: inherit;
                border-left: 2px solid transparent;
            }
            .e-my-account-tab .woocommerce-MyAccount-navigation ul li.is-active a {
                background: rgba(0, 0, 0, 0.05);
                border-left-color: var(--e-p-border-global);
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
        
        echo '<div class="e-my-account-tab e-my-account-tab__dashboard ' . $custom_dashboard_class . '">';
        ?>
            <div class="woocommerce <?php echo 'horizontal' === $settings['tabs_layout'] ? 'horizontal-tabs' : ''; ?>">
                <?php
                if ('horizontal' === $settings['tabs_layout']) {
                    ?>
                    <div class="e-wc-account-tabs-nav">
                        <?php wc_get_template('myaccount/navigation.php'); ?>
                    </div>
                    <?php
                } else {
                    wc_get_template('myaccount/navigation.php');
                }
                ?>

                <div class="woocommerce-MyAccount-content-wrapper">
                    <?php
                    // In the editor, output all the tabs but only show dashboard by default
                    $pages = $this->get_account_pages();
                    $first = true;

                    foreach ($pages as $page => $page_value) {
                        $wrapper_class = $this->get_account_content_wrapper(['context' => 'editor']);
                        $wrapper_class .= $first ? ' e-my-account-tab__content--active' : '';
                        
                        echo '<div class="' . esc_attr($wrapper_class) . '" data-content="' . esc_attr($page) . '" style="' . ($first ? '' : 'display: none;') . '">';
                        
                        // Clear query vars to prevent interference
                        global $wp_query;
                        $wp_query->query_vars = array();
                        $wp_query->query_vars[$page] = $page_value;
                        
                        if ($page === 'shortcuts') {
                            $this->shortcuts_endpoint_content();
                        } else {
                            do_action('woocommerce_account_' . $page . '_endpoint', $page_value);
                        }
                        
                        echo '</div>';
                        $first = false;
                    }
                    ?>
                </div>
            </div>
        </div>

        <script>
        jQuery(document).ready(function($) {
            // Set initial active state
            $('.woocommerce-MyAccount-navigation-link--dashboard').addClass('is-active');
            
            $('.woocommerce-MyAccount-navigation-link a').on('click', function(e) {
                e.preventDefault();
                var target = $(this).closest('li').attr('class').match(/woocommerce-MyAccount-navigation-link--([^\s]+)/)[1];
                
                // Hide all content divs
                $('.e-my-account-tab__content').hide().removeClass('e-my-account-tab__content--active');
                
                // Show the target content div
                $('[data-content="' + target + '"]')
                    .show()
                    .addClass('e-my-account-tab__content--active');
                
                // Update navigation classes
                $('.woocommerce-MyAccount-navigation-link').removeClass('is-active');
                $(this).closest('li').addClass('is-active');
            });
        });
        </script>
        <?php
    }

    protected function get_account_content_wrapper($args = []) {
        $context = isset($args['context']) ? $args['context'] : 'frontend';
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
    }

    protected function should_print_empty() {
        return false;
    }

    private function get_available_endpoints_html() {
        $endpoints = wc_get_account_menu_items();
        $html = '';
        foreach ($endpoints as $endpoint => $label) {
            $classes = wc_get_account_menu_item_classes($endpoint);
            $class_attr = is_array($classes) ? implode(' ', $classes) : $classes;
            $html .= '<li class="' . esc_attr($class_attr) . '">
                <a href="#">' . esc_html($label) . '</a>
            </li>';
        }
        return $html;
    }

    public function add_shortcuts_to_menu($items) {
        $settings = $this->get_settings_for_display();
        
        if (\Elementor\Plugin::$instance->editor->is_edit_mode() && empty($settings['new_tabs'])) {
            $default_items = [
                'dashboard' => __('Dashboard', 'woocommerce'),
                'orders' => __('Orders', 'woocommerce'),
                'downloads' => __('Downloads', 'woocommerce'),
                'edit-account' => __('Account details', 'woocommerce'),
                'customer-logout' => __('Logout', 'woocommerce')
            ];
            return $default_items;
        }
        
        if (!empty($settings['new_tabs'])) {
            $new_items = [];
            foreach ($settings['new_tabs'] as $tab) {
                $endpoint = !empty($tab['field_key']) ? $tab['field_key'] : '';
                if (!empty($endpoint) && !empty($tab['is_visible']) && $tab['is_visible'] === 'yes') {
                    $new_items[$endpoint] = $tab['tab_name'];
                }
            }
            
            if (!isset($new_items['customer-logout'])) {
                $new_items['customer-logout'] = __('Logout', 'woocommerce');
            }
            
            return $new_items;
        }
        
        return $items;
    }

    public function add_endpoints() {
        add_rewrite_endpoint('shortcuts', EP_ROOT | EP_PAGES);
        
        if (!get_option('shortcuts_hub_flush_rewrite_rules', false)) {
            flush_rewrite_rules(false);
            update_option('shortcuts_hub_flush_rewrite_rules', true);
        }
    }

    public function shortcuts_endpoint_content() {
        $current_endpoint = $this->get_current_endpoint();
        $wrapper_class = $this->get_account_content_wrapper();
        
        echo '<div class="' . esc_attr($wrapper_class) . '" data-endpoint="shortcuts">';
        echo '<div class="woocommerce-shortcuts">';
        
        echo '<h2>' . esc_html__('My Shortcuts', 'shortcuts-hub') . '</h2>';
        
        global $wpdb;
        $user_id = get_current_user_id();
        $table_name = $wpdb->prefix . 'shortcutshub_downloads';
        
        $downloads = $wpdb->get_results($wpdb->prepare(
            "SELECT 
                shortcut_id,
                shortcut_name, 
                MAX(version) as latest_downloaded_version,
                MAX(download_date) as last_download_date,
                post_url,
                post_id
             FROM {$table_name} 
             WHERE user_id = %d 
             GROUP BY shortcut_id
             ORDER BY last_download_date DESC",
            $user_id
        ));

        if (empty($downloads)) {
            echo '<div class="woocommerce-message woocommerce-message--info woocommerce-Message woocommerce-Message--info">';
            echo '<p>' . esc_html__('You haven\'t downloaded any shortcuts yet.', 'shortcuts-hub') . '</p>';
            echo '</div>';
        } else {
            echo '<div class="woocommerce-shortcuts-table-wrapper">';
            echo '<table class="woocommerce-orders-table woocommerce-MyAccount-orders shop_table shop_table_responsive">';
            echo '<thead><tr>';
            echo '<th class="woocommerce-orders-table__header">' . esc_html__('Shortcut', 'shortcuts-hub') . '</th>';
            echo '<th class="woocommerce-orders-table__header">' . esc_html__('Last Downloaded Version', 'shortcuts-hub') . '</th>';
            echo '<th class="woocommerce-orders-table__header">' . esc_html__('Last Download Date', 'shortcuts-hub') . '</th>';
            echo '<th class="woocommerce-orders-table__header">' . esc_html__('Actions', 'shortcuts-hub') . '</th>';
            echo '</tr></thead>';
            echo '<tbody>';
            
            foreach ($downloads as $download) {
                echo '<tr class="woocommerce-orders-table__row">';
                echo '<td class="woocommerce-orders-table__cell" data-title="' . esc_attr__('Shortcut', 'shortcuts-hub') . '">';
                if (!empty($download->post_url)) {
                    echo '<a href="' . esc_url($download->post_url) . '">' . esc_html($download->shortcut_name) . '</a>';
                } else {
                    echo esc_html($download->shortcut_name);
                }
                echo '</td>';
                echo '<td class="woocommerce-orders-table__cell" data-title="' . esc_attr__('Version', 'shortcuts-hub') . '">' . esc_html($download->latest_downloaded_version) . '</td>';
                echo '<td class="woocommerce-orders-table__cell" data-title="' . esc_attr__('Date', 'shortcuts-hub') . '">' . esc_html(date_i18n(get_option('date_format'), strtotime($download->last_download_date))) . '</td>';
                echo '<td class="woocommerce-orders-table__cell" data-title="' . esc_attr__('Actions', 'shortcuts-hub') . '">';
                if (!empty($download->post_url)) {
                    echo '<a href="' . esc_url($download->post_url) . '" class="woocommerce-button button view">' . esc_html__('View', 'shortcuts-hub') . '</a>';
                }
                echo '</td>';
                echo '</tr>';
            }
            
            echo '</tbody></table>';
            echo '</div>';
        }
        
        echo '</div></div>';
    }

    public function get_available_endpoints() {
        $available_endpoints = array();
        
        $endpoints = WC()->query->get_query_vars();
        $endpoint_labels = WC()->query->get_endpoints_mask();
        
        $available_endpoints[] = [
            'endpoint_key' => 'shortcuts',
            'tab_name' => esc_html__('Shortcuts', 'shortcuts-hub'),
        ];
        
        foreach ($endpoints as $key => $endpoint) {
            if ($key === 'customer-logout') {
                continue;
            }
            
            $endpoint_label = ucwords(str_replace('-', ' ', $key));
            
            $available_endpoints[] = [
                'endpoint_key' => $key,
                'tab_name' => $endpoint_label,
            ];
        }

        $available_endpoints[] = [
            'endpoint_key' => 'customer-logout',
            'tab_name' => esc_html__('Logout', 'woocommerce'),
        ];

        return $available_endpoints;
    }

    public function get_name() {
        return 'woocommerce-my-account';
    }

    public function get_title() {
        return esc_html__('My Account', 'shortcuts-hub');
    }

    public function get_categories() {
        return ['shortcuts-hub'];
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
        
        return $styles;
    }

    public function get_script_depends(): array {
        return ['woocommerce-my-account'];
    }

    public function enqueue_editor_scripts() {
        wp_enqueue_script('woocommerce');
        wp_enqueue_script('elementor-frontend');
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

        $pages = $this->get_account_pages();
        foreach ($pages as $endpoint => $label) {
            if (isset($wp_query->query[$endpoint])) {
                $current = $endpoint;
                break;
            }
        }

        if (empty($current)) {
            $current = 'dashboard';
        }

        return $current;
    }

    protected function has_data() {
        return !empty($this->get_settings_for_display());
    }

    protected function register_controls() {
        // Load parent controls first
        parent::register_controls();

        // Remove the parent's tabs control
        $this->remove_control('tabs');

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

        $default_endpoints = [];
        $available_endpoints = $this->get_available_endpoints();
        foreach ($available_endpoints as $endpoint) {
            $default_endpoints[] = [
                'is_visible' => 'yes',
                'field_key' => $endpoint['endpoint_key'],
                'tab_name' => $endpoint['tab_name']
            ];
        }

        $this->add_control(
            'new_tabs',
            [
                'label' => esc_html__('Account Endpoints', 'shortcuts-hub'),
                'type' => Controls_Manager::REPEATER,
                'fields' => $repeater->get_controls(),
                'default' => $default_endpoints,
                'title_field' => '{{{ tab_name }}}',
            ]
        );

        $this->end_controls_section();
    }
}