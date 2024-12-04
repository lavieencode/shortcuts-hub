<?php
namespace ShortcutsHub\Elementor\Widgets;

if (!defined('ABSPATH')) {
    exit;
}

if (!did_action('elementor/loaded')) {
    return;
}

use \Elementor\Widget_Button;
use \Elementor\Controls_Manager;
use \Elementor\Core\Kits\Documents\Tabs\Global_Colors;
use \Elementor\Core\Kits\Documents\Tabs\Global_Typography;

class Download_Button_Widget extends Widget_Button {
    public function get_name() {
        return 'download_button';
    }

    public function get_title() {
        return esc_html__('Download Button', 'shortcuts-hub');
    }

    public function get_icon() {
        return 'eicon-download-button';
    }

    public function get_categories() {
        return ['shortcuts-hub'];
    }

    public function get_script_depends() {
        return ['shortcuts-hub-download-button'];
    }

    public function get_style_depends() {
        return ['elementor-icons-fa-solid', 'shortcuts-hub-download-button'];
    }

    protected function register_controls() {
        parent::register_controls();

        // Hidden Controls Section
        $this->start_controls_section(
            'section_hidden_controls',
            [
                'label' => esc_html__('Hidden Controls', 'shortcuts-hub'),
                'condition' => [
                    'should_show' => 'never',
                ],
            ]
        );

        // Add hidden control first to control visibility
        $this->add_control(
            'should_show',
            [
                'label' => esc_html__('Show Section', 'shortcuts-hub'),
                'type' => Controls_Manager::HIDDEN,
                'default' => '',
            ]
        );

        // Hide parent controls
        $this->update_control(
            'text',
            [
                'condition' => [
                    'should_show' => 'never',
                ],
                'default' => 'Download Shortcut',
            ]
        );

        $this->update_control(
            'link',
            [
                'condition' => [
                    'should_show' => 'never',
                ],
            ]
        );

        // Set default icon
        $this->update_control(
            'selected_icon',
            [
                'default' => [
                    'value' => 'fas fa-download',
                    'library' => 'fa-solid',
                ],
            ]
        );

        $this->end_controls_section();

        $this->remove_control('button_type');

        $this->update_responsive_control(
            'align',
            [
                'default' => 'justify',
            ]
        );

        // Button Text Section
        $this->start_controls_section(
            'section_download_text',
            [
                'label' => esc_html__('Button Text', 'shortcuts-hub'),
            ]
        );

        $this->add_control(
            'logged_in_text',
            [
                'label' => __('Logged In Text', 'shortcuts-hub'),
                'type' => Controls_Manager::TEXT,
                'default' => __('Download Shortcut', 'shortcuts-hub'),
                'placeholder' => __('Download Shortcut', 'shortcuts-hub'),
            ]
        );

        $this->add_control(
            'logged_out_text',
            [
                'label' => __('Logged Out Text', 'shortcuts-hub'),
                'type' => Controls_Manager::TEXT,
                'default' => __('Login to Download', 'shortcuts-hub'),
                'placeholder' => __('Login to Download', 'shortcuts-hub'),
            ]
        );

        $this->end_controls_section();

        // Button Section
        $this->start_controls_section(
            'section_button_style_updates',
            [
                'label' => esc_html__('Button', 'shortcuts-hub'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->update_control(
            'icon_align',
            [
                'default' => 'left',
            ]
        );

        $this->update_control(
            'typography_typography',
            [
                'default' => 'custom',
            ]
        );

        $this->update_control(
            'typography_font_weight',
            [
                'default' => 'bold',
            ]
        );

        $this->update_control(
            'button_text_color',
            [
                'default' => 'var(--e-global-color-secondary)',
            ]
        );

        $this->end_controls_section();

        // Hidden Fields Section
        $this->start_controls_section(
            'section_hidden_fields',
            [
                'label' => esc_html__('Hidden Fields', 'shortcuts-hub'),
                'tab' => Controls_Manager::TAB_CONTENT,
                'condition' => [
                    'should_show' => 'never',
                ],
            ]
        );

        $this->add_control(
            'shortcut_id',
            [
                'type' => Controls_Manager::HIDDEN,
                'default' => '',
            ]
        );

        $this->add_control(
            'shortcut_version',
            [
                'type' => Controls_Manager::HIDDEN,
                'default' => '',
            ]
        );

        $this->add_control(
            'shortcut_data',
            [
                'type' => Controls_Manager::HIDDEN,
                'default' => '',
            ]
        );

        $this->add_control(
            'is_user_logged_in',
            [
                'type' => Controls_Manager::HIDDEN,
                'default' => is_user_logged_in() ? 'yes' : 'no'
            ]
        );

        $this->end_controls_section();

        // Download States Section
        $this->start_controls_section(
            'section_download_style',
            [
                'label' => esc_html__('Download States', 'shortcuts-hub'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        // Logged In State Styles
        $this->add_control(
            'heading_logged_in_style',
            [
                'label' => esc_html__('Logged In State', 'shortcuts-hub'),
                'type' => Controls_Manager::HEADING,
                'separator' => 'before',
            ]
        );

        $this->add_control(
            'logged_in_background_color',
            [
                'label' => esc_html__('Background Color', 'shortcuts-hub'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .elementor-button.logged-in' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'logged_in_text_color',
            [
                'label' => esc_html__('Text Color', 'shortcuts-hub'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .elementor-button.logged-in' => 'color: {{VALUE}};',
                    '{{WRAPPER}} .elementor-button.logged-in svg' => 'fill: {{VALUE}};',
                ],
            ]
        );

        // Logged Out State Styles
        $this->add_control(
            'heading_logged_out_style',
            [
                'label' => esc_html__('Logged Out State', 'shortcuts-hub'),
                'type' => Controls_Manager::HEADING,
                'separator' => 'before',
            ]
        );

        $this->add_control(
            'logged_out_background_color',
            [
                'label' => esc_html__('Background Color', 'shortcuts-hub'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .elementor-button.logged-out' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'logged_out_text_color',
            [
                'label' => esc_html__('Text Color', 'shortcuts-hub'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .elementor-button.logged-out' => 'color: {{VALUE}};',
                    '{{WRAPPER}} .elementor-button.logged-out svg' => 'fill: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_section();

        $this->start_controls_section(
            'section_icon_style',
            [
                'label' => esc_html__('Icon', 'shortcuts-hub'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'icon_color',
            [
                'label' => esc_html__('Color', 'shortcuts-hub'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .elementor-button .elementor-button-icon i' => 'color: {{VALUE}} !important;',
                    '{{WRAPPER}} .elementor-button.logged-in .elementor-button-icon i' => 'color: {{VALUE}} !important;',
                    '{{WRAPPER}} .elementor-button.logged-out .elementor-button-icon i' => 'color: {{VALUE}} !important;',
                    '{{WRAPPER}} .elementor-button .elementor-button-icon svg' => 'fill: {{VALUE}} !important;',
                    '{{WRAPPER}} .elementor-button.logged-in .elementor-button-icon svg' => 'fill: {{VALUE}} !important;',
                    '{{WRAPPER}} .elementor-button.logged-out .elementor-button-icon svg' => 'fill: {{VALUE}} !important;',
                ],
                'global' => [
                    'default' => Global_Colors::COLOR_PRIMARY,
                ],
            ]
        );

        $this->end_controls_section();
    }

    public function get_settings_for_display($setting_key = null) {
        $settings = parent::get_settings_for_display($setting_key);
        
        if ($setting_key === null) {
            $is_logged_in = is_user_logged_in();
            $download_url = $this->get_download_url();
            $has_download = !empty($download_url);
            
            $settings['text'] = $this->get_button_text($settings, $is_logged_in, $has_download);
        } else if ($setting_key === 'text') {
            $is_logged_in = is_user_logged_in();
            $download_url = $this->get_download_url();
            $has_download = !empty($download_url);
            
            return $this->get_button_text($settings, $is_logged_in, $has_download);
        }
        
        return $settings;
    }

    protected function get_button_text($settings, $is_logged_in, $has_download) {
        if (!$is_logged_in) {
            return isset($settings['logged_out_text']) ? $settings['logged_out_text'] : __('Login to Download', 'shortcuts-hub');
        }
        
        if ($has_download) {
            return isset($settings['logged_in_text']) ? $settings['logged_in_text'] : __('Download Shortcut', 'shortcuts-hub');
        }
        
        return __('Shortcut Not Available', 'shortcuts-hub');
    }

    protected function get_button_url($is_logged_in, $download_url, $redirect_url, $download_token = '') {
        if (!$is_logged_in) {
            $login_url = 'https://debotchery.ai/shortcuts-gallery/login';
            $params = [];
            
            if (!empty($redirect_url)) {
                $params['redirect_url'] = $redirect_url;
            }
            
            if (!empty($download_token)) {
                $params['download_token'] = $download_token;
            }
            
            if (!empty($params)) {
                $login_url .= '?' . http_build_query($params);
            }
            
            return $login_url;
        }
        
        return $download_url;
    }

    protected function get_button_classes($is_logged_in, $has_download) {
        $classes = [$is_logged_in ? 'logged-in' : 'logged-out'];
        if ($is_logged_in && !$has_download) {
            $classes[] = 'not-available';
        }
        return $classes;
    }

    protected function get_download_url() {
        if (!is_singular('shortcut')) {
            return '';
        }
        
        $shortcut_id = get_post_meta(get_the_ID(), 'sb_id', true);
        if (empty($shortcut_id)) {
            return '';
        }

        // Make AJAX call to fetch_latest_version
        $response = wp_remote_post(admin_url('admin-ajax.php'), [
            'body' => [
                'action' => 'fetch_latest_version',
                'nonce' => wp_get_current_user()->ID ? wp_create_nonce('shortcuts_hub_nonce') : '',
                'id' => $shortcut_id
            ]
        ]);

        if (is_wp_error($response)) {
            return '';
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if (!$body || !isset($body['success']) || !$body['success']) {
            return '';
        }

        if (!isset($body['data']['version']) || !isset($body['data']['version']['url'])) {
            return '';
        }

        $download_url = $body['data']['version']['url'];
        return $download_url;
    }

    protected function get_redirect_url() {
        $redirect_url = get_permalink();
        return $redirect_url;
    }

    protected function prepare_button_attributes($settings) {
        $is_logged_in = is_user_logged_in();
        $download_url = $this->get_download_url();
        $has_download = !empty($download_url);
        $redirect_url = get_permalink();
        
        // Generate and store download token if not logged in
        $download_token = '';
        if (!$is_logged_in && $has_download) {
            $download_token = wp_generate_password(32, false);
            
            // Get shortcut and version IDs
            $shortcut_id = get_post_meta(get_the_ID(), 'sb_id', true);
            $version_id = get_post_meta(get_the_ID(), 'latest_version_id', true);
            
            $download_data = [
                'shortcut_id' => $shortcut_id,
                'version_id' => $version_id,
                'download_url' => $download_url,
                'redirect_url' => $redirect_url
            ];
            set_transient('sh_download_' . $download_token, $download_data, HOUR_IN_SECONDS);
        }
        
        return [
            'classes' => $this->get_button_classes($is_logged_in, $has_download),
            'text' => $this->get_button_text($settings, $is_logged_in, $has_download),
            'url' => $this->get_button_url($is_logged_in, $download_url, $redirect_url, $download_token),
            'download_url' => $download_url,
            'redirect_url' => $redirect_url,
            'download_token' => $download_token
        ];
    }

    protected function save_version_url() {
        if (is_singular('shortcut')) {
            global $post;
            
            // Get the version URL
            $version_url = get_post_meta($post->ID, 'version_url', true);
            
            if ($version_url) {
                // Save it as the download URL
                update_post_meta($post->ID, 'download_url', $version_url);
            }
        }
    }

    protected function render() {
        $settings = $this->get_settings_for_display();
        $button_attributes = $this->prepare_button_attributes($settings);
        
        $this->add_render_attribute('button', 'class', implode(' ', $button_attributes['classes']));
        $this->add_render_attribute('button', 'href', $button_attributes['url']);
        $this->add_render_attribute('button', 'data-download-url', $button_attributes['download_url']);
        $this->add_render_attribute('button', 'data-redirect-url', $button_attributes['redirect_url']);
        $this->add_render_attribute('button', 'data-download-token', $button_attributes['download_token'] ?? '');
        
        if ($is_logged_in = is_user_logged_in()) {
            $this->add_render_attribute('button', 'data-is-logged-in', '1');
        } else {
            $this->add_render_attribute('button', 'data-is-logged-in', '0');
        }
        
        $this->add_render_attribute('button', 'disabled', $is_logged_in && !$button_attributes['download_url'] ? 'disabled' : '');
        
        // Force the text value in settings
        $this->set_settings('text', $button_attributes['text']);
        $this->set_settings('link', ['url' => $button_attributes['url']]);
        
        parent::render();
    }

    protected function content_template() {
        parent::content_template();
    }
}