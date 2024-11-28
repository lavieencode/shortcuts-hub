<?php
namespace ShortcutsHub\Elementor\Widgets;

use ElementorPro\Modules\Woocommerce\Widgets\My_Account as Elementor_My_Account;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Background;
use Elementor\Group_Control_Box_Shadow;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class My_Account_Widget extends Elementor_My_Account {

    public function __construct($data = [], $args = null) {
        parent::__construct($data, $args);
        
        // Register the shortcuts endpoint and menu item
        add_action('init', [$this, 'add_shortcuts_endpoint']);
        add_filter('woocommerce_get_query_vars', [$this, 'add_shortcuts_query_var']);
        add_filter('woocommerce_account_menu_items', [$this, 'add_shortcuts_menu_item']);
        add_action('woocommerce_account_shortcuts_endpoint', [$this, 'render_shortcuts_content']);
        
        // Register scripts and styles
        add_action('wp_enqueue_scripts', [$this, 'enqueue_widget_assets']);
    }

    public function get_name() {
        return 'shortcuts-hub-my-account';
    }

    public function get_title() {
        return esc_html__('My Account (Shortcuts Hub)', 'shortcuts-hub');
    }

    protected function register_controls() {
        parent::register_controls();

        // Add our shortcuts tab to the tabs control
        $tabs_control = $this->get_controls('tabs');
        if (isset($tabs_control['default'])) {
            $tabs_control['default'][] = [
                'field_key' => 'shortcuts',
                'field_label' => esc_html__('Shortcuts', 'shortcuts-hub'),
                'tab_name' => esc_html__('Shortcuts', 'shortcuts-hub'),
            ];

            $this->update_control('tabs', $tabs_control);
        }

        // Shortcuts Content Style Section
        $this->start_controls_section(
            'shortcuts_style',
            [
                'label' => esc_html__('Shortcuts Content', 'shortcuts-hub'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'shortcuts_background_color',
            [
                'label' => esc_html__('Background Color', 'shortcuts-hub'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .shortcuts-content' => 'background-color: {{VALUE}};',
                ],
                'default' => '#ffffff',
            ]
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name' => 'shortcuts_border',
                'selector' => '{{WRAPPER}} .shortcuts-content',
                'separator' => 'before',
            ]
        );

        $this->add_responsive_control(
            'shortcuts_border_radius',
            [
                'label' => esc_html__('Border Radius', 'shortcuts-hub'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%', 'em'],
                'selectors' => [
                    '{{WRAPPER}} .shortcuts-content' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'shortcuts_padding',
            [
                'label' => esc_html__('Padding', 'shortcuts-hub'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', '%'],
                'selectors' => [
                    '{{WRAPPER}} .shortcuts-content' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'shortcuts_box_shadow',
                'selector' => '{{WRAPPER}} .shortcuts-content',
            ]
        );

        $this->add_control(
            'shortcuts_title_heading',
            [
                'label' => esc_html__('Title', 'shortcuts-hub'),
                'type' => Controls_Manager::HEADING,
                'separator' => 'before',
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'shortcuts_title_typography',
                'selector' => '{{WRAPPER}} .woocommerce-shortcuts h2',
            ]
        );

        $this->add_control(
            'shortcuts_title_color',
            [
                'label' => esc_html__('Color', 'shortcuts-hub'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .woocommerce-shortcuts h2' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'shortcuts_title_spacing',
            [
                'label' => esc_html__('Spacing', 'shortcuts-hub'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', 'em'],
                'selectors' => [
                    '{{WRAPPER}} .woocommerce-shortcuts h2' => 'margin-bottom: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Get the list of account pages for the My Account widget
     */
    protected function get_account_pages() {
        // Get the parent class's account pages through WooCommerce's filter
        $items = wc_get_account_menu_items();
        
        // Insert shortcuts before the logout menu item
        $logout = false;
        if (isset($items['customer-logout'])) {
            $logout = $items['customer-logout'];
            unset($items['customer-logout']);
        }
        
        $items['shortcuts'] = esc_html__('Shortcuts', 'shortcuts-hub');
        
        if ($logout) {
            $items['customer-logout'] = $logout;
        }
        
        return $items;
    }

    protected function render() {
        $is_editor = \Elementor\Plugin::$instance->editor->is_edit_mode();
        
        if ($is_editor) {
            $this->render_html_editor();
        } else {
            $this->render_html_front_end();
        }
    }

    public function render_shortcuts_content() {
        // Create an instance of the Download Log widget with default settings
        $download_log = new Download_Log([
            'settings' => [
                'show_title' => 'yes',
                'download_log_title' => esc_html__('Download History', 'shortcuts-hub'),
                'show_date' => 'yes',
                'show_time' => 'yes',
                'show_status' => 'yes',
                'items_per_page' => 10,
            ]
        ], []);

        // Get the current endpoint to determine if this is the active tab
        $current_endpoint = $this->get_current_endpoint();
        $is_active = $current_endpoint === 'shortcuts';
        
        // Add the proper tab classes
        $classes = [
            'e-my-account-tab__shortcuts',
            'e-my-account-tab__content',
            'woocommerce-MyAccount-content'
        ];
        
        if ($is_active) {
            $classes[] = 'e-my-account-tab__content--active';
        }
        
        // Output the content with proper wrapper and classes
        echo '<div class="' . esc_attr(implode(' ', $classes)) . '" data-tab="shortcuts">';
        echo $download_log->render();
        echo '</div>';
    }

    /**
     * Get the current endpoint for the My Account page
     */
    protected function get_current_endpoint() {
        global $wp;
        
        $current = '';
        
        foreach ($this->get_account_pages() as $endpoint => $label) {
            if (isset($wp->query_vars[$endpoint])) {
                $current = $endpoint;
                break;
            }
        }
        
        // Default to dashboard if no endpoint is set
        return $current ? $current : 'dashboard';
    }

    protected function render_html_editor() {
        // Add wrapper class for the widget
        $this->add_render_attribute('my-account-wrapper', 'class', [
            'e-my-account-tab',
            'woocommerce',
            'e-my-account-tab__' . $this->get_current_endpoint(),
        ]);

        $pages = $this->get_account_pages();
        ?>
        <div <?php echo $this->get_render_attribute_string('my-account-wrapper'); ?>>
            <div class="woocommerce-MyAccount-navigation e-my-account-tab__nav">
                <ul>
                    <?php foreach ($pages as $page => $label) : ?>
                        <li class="<?php echo wc_get_account_menu_item_classes($page); ?>">
                            <a href="#" data-tab="<?php echo esc_attr($page); ?>"><?php echo esc_html($label); ?></a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <div class="woocommerce-MyAccount-content e-my-account-tab__content-wrapper">
                <?php foreach ($pages as $page => $label) : ?>
                    <div class="e-my-account-tab__<?php echo esc_attr($page); ?> e-my-account-tab__content <?php echo $page === 'dashboard' ? 'e-my-account-tab__content--active' : ''; ?>" data-tab="<?php echo esc_attr($page); ?>">
                        <?php
                        if ($page === 'shortcuts') {
                            $this->render_shortcuts_content();
                        } else {
                            do_action('woocommerce_account_' . $page . '_endpoint');
                        }
                        ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
    }

    protected function render_html_front_end() {
        parent::render_html_front_end();
    }

    public function enqueue_widget_assets() {
        if (is_account_page()) {
            wp_enqueue_style('shortcuts-hub-download-log');
            wp_enqueue_script('shortcuts-hub-download-log');
        }
    }

    /**
     * Register the shortcuts endpoint with WordPress and WooCommerce
     */
    public function add_shortcuts_endpoint() {
        add_rewrite_endpoint('shortcuts', EP_ROOT | EP_PAGES);
        
        // Flush rewrite rules only once
        if (!get_option('shortcuts_hub_flush_rewrite_rules', false)) {
            flush_rewrite_rules(false);
            update_option('shortcuts_hub_flush_rewrite_rules', true);
        }
    }

    /**
     * Add shortcuts to WooCommerce query vars
     */
    public function add_shortcuts_query_var($vars) {
        $vars['shortcuts'] = 'shortcuts';
        return $vars;
    }

    /**
     * Add shortcuts to the My Account menu
     */
    public function add_shortcuts_menu_item($items) {
        // Insert shortcuts before the logout menu item
        $logout = false;
        if (isset($items['customer-logout'])) {
            $logout = $items['customer-logout'];
            unset($items['customer-logout']);
        }
        
        $items['shortcuts'] = esc_html__('Shortcuts', 'shortcuts-hub');
        
        if ($logout) {
            $items['customer-logout'] = $logout;
        }
        
        return $items;
    }
}