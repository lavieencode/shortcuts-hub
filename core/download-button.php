<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

// Ensure Elementor is active and loaded
if ( did_action( 'elementor/loaded' ) ) {

    // Register Widget Class
    class Shortcuts_Download_Button extends \Elementor\Widget_Base {

        public function get_name() {
            return 'shortcuts_download_button';
        }

        public function get_title() {
            return __( 'Shortcuts Download Button', 'shortcuts-hub' );
        }

        public function get_icon() {
            return 'eicon-button';
        }

        public function get_categories() {
            return [ 'shortcuts-hub' ];
        }

        protected function _register_controls() {
            $this->start_controls_section(
                'content_section',
                [
                    'label' => __( 'Content', 'shortcuts-hub' ),
                    'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
                ]
            );

            $this->add_control(
                'button_text',
                [
                    'label' => __( 'Button Text', 'shortcuts-hub' ),
                    'type' => \Elementor\Controls_Manager::TEXT,
                    'default' => __( 'Download Shortcut', 'shortcuts-hub' ),
                ]
            );

            $this->add_control(
                'redirect_url',
                [
                    'label' => __( 'Redirect URL', 'shortcuts-hub' ),
                    'type' => \Elementor\Controls_Manager::URL,
                    'placeholder' => __( 'https://your-link.com', 'shortcuts-hub' ),
                    'default' => [
                        'url' => '',
                    ],
                ]
            );

            $this->end_controls_section();
        }

        protected function render() {
            $settings = $this->get_settings_for_display();
            echo '<a id="download-button" href="' . esc_url( $settings['redirect_url']['url'] ) . '" class="elementor-button elementor-size-md">';
            echo esc_html( $settings['button_text'] );
            echo '</a>';
        }
    }

    // Register Widget
    function register_shortcuts_download_button_widget( $widgets_manager ) {
        require_once( __FILE__ );
        $widgets_manager->register( new \Shortcuts_Download_Button() );
    }
    add_action( 'elementor/widgets/register', 'register_shortcuts_download_button_widget' );

    // Register Custom Category
    function add_elementor_widget_categories( $elements_manager ) {
        $elements_manager->add_category(
            'shortcuts-hub',
            [
                'title' => esc_html__( 'Shortcuts Hub', 'shortcuts-hub' ),
                'icon' => 'fa fa-plug',
            ]
        );
    }
    add_action( 'elementor/elements/categories_registered', 'add_elementor_widget_categories' );

} else {
    // Elementor is not active
    add_action( 'admin_notices', function() {
        echo '<div class="notice notice-warning"><p>' . __( 'The "Shortcuts Hub" plugin requires Elementor to be active.', 'shortcuts-hub' ) . '</p></div>';
    } );
}