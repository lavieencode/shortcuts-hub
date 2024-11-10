<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

// Register the custom widget category first
function register_shortcuts_hub_category( $elements_manager ) {
    $elements_manager->add_category(
        'shortcuts-hub',
        [
            'title' => esc_html__( 'Shortcuts Hub', 'shortcuts-hub' ),
            'icon'  => 'eicon-elementor',
        ]
    );
}
add_action( 'elementor/elements/categories_registered', 'register_shortcuts_hub_category' );

// Define the Shortcuts Download Button widget
class Shortcuts_Download_Button extends \Elementor\Widget_Base {

    public function get_name() {
        return 'shortcuts_download_button';
    }

    public function get_title() {
        return __('Shortcuts Download Button', 'shortcuts-hub');
    }

    public function get_icon() {
        return 'eicon-download';
    }

    public function get_categories() {
        return ['shortcuts-hub'];
    }

    public function get_keywords() {
        return [ 'shortcuts', 'download', 'button' ];
    }

    protected function register_controls() {
        $this->start_controls_section(
            'content_section',
            [
                'label' => __( 'Content', 'shortcuts-hub' ),
                'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'button_text',
            [
                'label'   => __( 'Button Text', 'shortcuts-hub' ),
                'type'    => \Elementor\Controls_Manager::TEXT,
                'default' => __( 'Download', 'shortcuts-hub' ),
            ]
        );

        $this->add_control(
            'file_url',
            [
                'label'       => __( 'File URL', 'shortcuts-hub' ),
                'type'        => \Elementor\Controls_Manager::URL,
                'placeholder' => __( 'https://your-file-link.com', 'shortcuts-hub' ),
                'default'     => [
                    'url' => '',
                ],
            ]
        );

        $this->end_controls_section();

        // Style Tab
        $this->start_controls_section(
            'style_section',
            [
                'label' => __( 'Button', 'shortcuts-hub' ),
                'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'button_color',
            [
                'label'     => __( 'Text Color', 'shortcuts-hub' ),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .elementor-button' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'button_background_color',
            [
                'label'     => __( 'Background Color', 'shortcuts-hub' ),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .elementor-button' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name'     => 'typography',
                'label'    => __( 'Typography', 'shortcuts-hub' ),
                'selector' => '{{WRAPPER}} .elementor-button',
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Border::get_type(),
            [
                'name'     => 'border',
                'label'    => __( 'Border', 'shortcuts-hub' ),
                'selector' => '{{WRAPPER}} .elementor-button',
            ]
        );

        $this->add_responsive_control(
            'button_padding',
            [
                'label'      => __( 'Padding', 'shortcuts-hub' ),
                'type'       => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => [ 'px', 'em', '%' ],
                'selectors'  => [
                    '{{WRAPPER}} .elementor-button' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    protected function render() {
        $settings = $this->get_settings_for_display();

        $post_id = get_the_ID();
        $shortcut_id = get_post_meta($post_id, 'sb_id', true);

        if (is_user_logged_in()) {
            $download_url = '';

            if (!empty($shortcut_id)) {
                $response = sb_api_call("shortcuts/{$shortcut_id}/version/latest", 'GET');

                // Log the API response for debugging
                error_log('API Response: ' . print_r($response, true));

                if (!is_wp_error($response) && isset($response['version']['url'])) {
                    $download_url = esc_url($response['version']['url']);
                } else {
                    error_log('Error fetching latest version URL or URL not set.');
                }
            } else {
                error_log('Shortcut ID is empty.');
            }

            if (!empty($download_url)) {
                echo '<a href="' . $download_url . '" download class="elementor-button elementor-size-md">';
                echo esc_html($settings['button_text']);
                echo '</a>';
            } else {
                echo '<div class="elementor-alert elementor-alert-warning">Download link not available.</div>';
            }
        } else {
            $login_url = add_query_arg('sb_id', $shortcut_id, 'https://debotchery.ai/shortcuts-gallery/login');
            echo '<a href="' . esc_url($login_url) . '" class="elementor-button elementor-size-md">';
            echo esc_html__('Login to Download', 'shortcuts-hub');
            echo '</a>';
        }
    }
}

// Register the Shortcuts Download Button widget
function register_shortcuts_download_button_widget( $widgets_manager ) {
    if (class_exists('Shortcuts_Download_Button')) {
        $widgets_manager->register(new Shortcuts_Download_Button());
    }
}
add_action( 'elementor/widgets/register', 'register_shortcuts_download_button_widget' );

// Define the Shortcuts Test Widget
class Shortcuts_Test_Widget extends \Elementor\Widget_Base {

    public function get_name() {
        return 'shortcuts_test_widget';
    }

    public function get_title() {
        return esc_html__( 'Shortcuts Test Widget', 'shortcuts-hub' );
    }

    public function get_icon() {
        return 'eicon-button';
    }

    public function get_categories() {
        return [ 'shortcuts-hub' ];
    }

    public function get_keywords() {
        return [ 'shortcuts', 'test', 'widget' ];
    }

    protected function render() {
        echo '<div class="elementor-text-editor">This is a test widget</div>';
    }
}

// Register the Shortcuts Test Widget
function register_shortcuts_test_widget( $widgets_manager ) {
    $widgets_manager->register( new Shortcuts_Test_Widget() );
}
add_action( 'elementor/widgets/register', 'register_shortcuts_test_widget' );