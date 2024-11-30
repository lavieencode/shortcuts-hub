<?php

namespace ShortcutsHub\Elementor\Widgets;

if (!defined('ABSPATH')) {
    exit;
}

// Make sure Elementor is loaded
if (!did_action('elementor/loaded')) {
    return;
}

// Add this line to properly import Widget_Base
use \Elementor\Widget_Base;
use \Elementor\Controls_Manager;
use \Elementor\Group_Control_Typography;
use \Elementor\Group_Control_Border;
use \Elementor\Group_Control_Background;
use \Elementor\Group_Control_Box_Shadow;

class Download_Log extends Widget_Base {
    public function get_name() {
        return 'shortcuts-download-log';
    }

    public function get_title() {
        return esc_html__('Download Log', 'shortcuts-hub');
    }

    public function get_icon() {
        return 'eicon-history';
    }

    public function get_categories() {
        return ['shortcuts-hub'];
    }

    public function get_style_depends() {
        return ['shortcuts-hub-download-log'];
    }

    public function get_script_depends() {
        return ['shortcuts-hub-download-log'];
    }

    protected function register_controls() {
        // Content Section
        $this->start_controls_section(
            'content_section',
            [
                'label' => esc_html__('Content', 'shortcuts-hub'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'download_log_title',
            [
                'label' => esc_html__('Title', 'shortcuts-hub'),
                'type' => Controls_Manager::TEXT,
                'default' => esc_html__('Download History', 'shortcuts-hub'),
                'label_block' => true,
            ]
        );

        $this->add_control(
            'show_title',
            [
                'label' => esc_html__('Show Title', 'shortcuts-hub'),
                'type' => Controls_Manager::SWITCHER,
                'default' => 'yes',
            ]
        );

        $this->end_controls_section();

        // Table Style Section
        $this->start_controls_section(
            'table_style_section',
            [
                'label' => esc_html__('Table Style', 'shortcuts-hub'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'table_background',
            [
                'label' => esc_html__('Table Background', 'shortcuts-hub'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .shortcuts-download-log-table' => 'background-color: {{VALUE}};',
                ],
                'default' => '#ffffff',
            ]
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name' => 'table_border',
                'selector' => '{{WRAPPER}} .shortcuts-download-log-table',
            ]
        );

        $this->add_control(
            'cell_padding',
            [
                'label' => esc_html__('Cell Padding', 'shortcuts-hub'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', '%'],
                'selectors' => [
                    '{{WRAPPER}} .shortcuts-download-log-table td, {{WRAPPER}} .shortcuts-download-log-table th' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
                'default' => [
                    'top' => '12',
                    'right' => '12',
                    'bottom' => '12',
                    'left' => '12',
                    'unit' => 'px',
                    'isLinked' => true,
                ],
            ]
        );

        $this->end_controls_section();

        // Header Style Section
        $this->start_controls_section(
            'header_style_section',
            [
                'label' => esc_html__('Header Style', 'shortcuts-hub'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'header_background',
            [
                'label' => esc_html__('Header Background', 'shortcuts-hub'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .shortcuts-download-log-table th' => 'background-color: {{VALUE}};',
                ],
                'default' => '#f5f5f5',
            ]
        );

        $this->add_control(
            'header_text_color',
            [
                'label' => esc_html__('Header Text Color', 'shortcuts-hub'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .shortcuts-download-log-table th' => 'color: {{VALUE}};',
                ],
                'default' => '#333333',
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'header_typography',
                'selector' => '{{WRAPPER}} .shortcuts-download-log-table th',
            ]
        );

        $this->end_controls_section();

        // Row Style Section
        $this->start_controls_section(
            'row_style_section',
            [
                'label' => esc_html__('Row Style', 'shortcuts-hub'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'row_background',
            [
                'label' => esc_html__('Row Background', 'shortcuts-hub'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .shortcuts-download-log-table td' => 'background-color: {{VALUE}};',
                ],
                'default' => '#ffffff',
            ]
        );

        $this->add_control(
            'alternate_row_background',
            [
                'label' => esc_html__('Alternate Row Background', 'shortcuts-hub'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .shortcuts-download-log-table tr:nth-child(even) td' => 'background-color: {{VALUE}};',
                ],
                'default' => '#fafafa',
            ]
        );

        $this->add_control(
            'row_text_color',
            [
                'label' => esc_html__('Row Text Color', 'shortcuts-hub'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .shortcuts-download-log-table td' => 'color: {{VALUE}};',
                ],
                'default' => '#333333',
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'row_typography',
                'selector' => '{{WRAPPER}} .shortcuts-download-log-table td',
            ]
        );

        $this->end_controls_section();

        // Button Style Section
        $this->start_controls_section(
            'button_style_section',
            [
                'label' => esc_html__('Button Style', 'shortcuts-hub'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->start_controls_tabs('button_style_tabs');

        $this->start_controls_tab(
            'button_normal_tab',
            [
                'label' => esc_html__('Normal', 'shortcuts-hub'),
            ]
        );

        $this->add_control(
            'button_background',
            [
                'label' => esc_html__('Button Background', 'shortcuts-hub'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .elementor-button' => 'background-color: {{VALUE}};',
                ],
                'default' => '#61ce70',
            ]
        );

        $this->add_control(
            'button_text_color',
            [
                'label' => esc_html__('Button Text Color', 'shortcuts-hub'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .elementor-button' => 'color: {{VALUE}};',
                ],
                'default' => '#ffffff',
            ]
        );

        $this->end_controls_tab();

        $this->start_controls_tab(
            'button_hover_tab',
            [
                'label' => esc_html__('Hover', 'shortcuts-hub'),
            ]
        );

        $this->add_control(
            'button_hover_background',
            [
                'label' => esc_html__('Button Background', 'shortcuts-hub'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .elementor-button:hover' => 'background-color: {{VALUE}};',
                ],
                'default' => '#3abd60',
            ]
        );

        $this->add_control(
            'button_hover_text_color',
            [
                'label' => esc_html__('Button Text Color', 'shortcuts-hub'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .elementor-button:hover' => 'color: {{VALUE}};',
                ],
                'default' => '#ffffff',
            ]
        );

        $this->end_controls_tab();

        $this->end_controls_tabs();

        $this->end_controls_section();
    }

    protected function render() {
        $settings = $this->get_settings_for_display();
        
        if ($settings['show_title'] === 'yes' && !empty($settings['download_log_title'])) {
            echo '<h2>' . esc_html($settings['download_log_title']) . '</h2>';
        }

        if (!is_user_logged_in()) {
            echo '<div class="elementor-alert elementor-alert-info">';
            echo esc_html__('Please log in to view your download history.', 'shortcuts-hub');
            echo '</div>';
            return;
        }

        global $wpdb;
        $user_id = get_current_user_id();
        $table_name = $wpdb->prefix . 'shortcutshub_downloads';
        
        // Get user's download history, grouped by shortcut
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
            echo '<div class="elementor-alert elementor-alert-info">';
            echo esc_html__('You haven\'t downloaded any shortcuts yet.', 'shortcuts-hub');
            echo '</div>';
            return;
        }

        echo '<table class="shortcuts-download-log-table">';
        echo '<thead><tr>';
        echo '<th>' . esc_html__('Shortcut', 'shortcuts-hub') . '</th>';
        echo '<th>' . esc_html__('Last Downloaded Version', 'shortcuts-hub') . '</th>';
        echo '<th>' . esc_html__('Last Download Date', 'shortcuts-hub') . '</th>';
        echo '<th>' . esc_html__('Actions', 'shortcuts-hub') . '</th>';
        echo '</tr></thead>';
        echo '<tbody>';

        foreach ($downloads as $download) {
            echo '<tr>';
            echo '<td>' . esc_html($download->shortcut_name) . '</td>';
            echo '<td>' . esc_html($download->latest_downloaded_version) . '</td>';
            echo '<td>' . esc_html(date_i18n(get_option('date_format'), strtotime($download->last_download_date))) . '</td>';
            echo '<td class="shortcut-actions">';
            echo '<a href="' . esc_url($download->post_url) . '" class="elementor-button elementor-size-sm">' . 
                 esc_html__('View', 'shortcuts-hub') . '</a>';
            echo '<button class="elementor-button elementor-size-sm download-latest" data-shortcut-id="' . esc_attr($download->shortcut_id) . '" data-post-id="' . esc_attr($download->post_id) . '">' . 
                 esc_html__('Download', 'shortcuts-hub') . '</button>';
            echo '</td>';
            echo '</tr>';
        }

        echo '</tbody></table>';
        echo '</div>';

        // Add nonce for AJAX calls
        wp_nonce_field('shortcuts_hub_nonce', 'shortcuts_hub_nonce');
    }
}
