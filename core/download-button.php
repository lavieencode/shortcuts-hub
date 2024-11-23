<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function register_shortcuts_hub_category($elements_manager) {
    $elements_manager->add_category(
        'shortcuts-hub',
        [
            'title' => __('Shortcuts Hub', 'shortcuts-hub'),
            'icon' => 'fa fa-plug',
        ]
    );
}
add_action('elementor/elements/categories_registered', 'register_shortcuts_hub_category');

function register_shortcuts_hub_widgets($widgets_manager) {
    $widgets_manager->register(new Shortcuts_Download_Button());
    $widgets_manager->register(new Shortcuts_Test_Widget());
}
add_action('elementor/widgets/register', 'register_shortcuts_hub_widgets');

class Shortcuts_Download_Button extends \Elementor\Widget_Base {
    public function get_name() {
        return 'shortcuts_download_button';
    }

    public function get_title() {
        return __('Shortcuts Download Button', 'shortcuts-hub');
    }

    public function get_icon() {
        return 'eicon-download-button';
    }

    public function get_categories() {
        return ['shortcuts-hub'];
    }

    public function get_keywords() {
        return ['shortcuts', 'download', 'button'];
    }

    public function __construct($data = [], $args = null) {
        parent::__construct($data, $args);
        $this->_settings = $this->get_default_settings();
        add_action('wp_head', array($this, 'add_custom_css'));
    }

    public function add_custom_css() {
        ?>
        <style>
            .elementor-widget-shortcuts_download_button .elementor-button-wrapper {
                display: flex;
                width: 100%;
            }
            .elementor-widget-shortcuts_download_button .elementor-button {
                width: 100%;
                display: inline-flex;
                justify-content: center;
                align-items: center;
            }
            .elementor-widget-shortcuts_download_button .elementor-button-content-wrapper {
                display: inline-flex;
                align-items: center;
            }
            .elementor-widget-shortcuts_download_button form {
                width: 100%;
            }
            .elementor-widget-shortcuts_download_button button.elementor-button {
                width: 100%;
                display: inline-flex;
                justify-content: center;
                align-items: center;
            }
        </style>
        <?php
    }

    public function get_default_settings() {
        return [
            'button_text' => __('Download', 'shortcuts-hub'),
            'button_text_logged_out' => __('Log in to Download', 'shortcuts-hub'),
            'button_icon' => [
                'value' => 'fas fa-download',
                'library' => 'fa-solid',
            ],
            'button_size' => 'md',
            'button_text_color' => '',
            'button_icon_color' => '',
            'button_background_color' => '',
            'button_hover_text_color' => '',
            'button_hover_icon_color' => '',
            'button_hover_background_color' => '',
            'button_padding' => [
                'top' => '15',
                'right' => '30',
                'bottom' => '15',
                'left' => '30',
                'unit' => 'px',
                'isLinked' => false,
            ],
            'typography_typography' => 'custom',
            'typography_font_size' => [
                'size' => 15,
                'unit' => 'px',
            ],
            'typography_font_weight' => '500',
            'shortcut_id' => '',
            'shortcut_name' => '',
            'version' => '',
            'download_url' => '',
            'icon_size' => [
                'unit' => 'px',
                'size' => 50,
            ],
            'icon_spacing' => [
                'unit' => 'px',
                'size' => 8,
            ],
        ];
    }

    protected function _register_controls() {
        $this->start_controls_section(
            'content_section',
            [
                'label' => __('Content', 'shortcuts-hub'),
                'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'button_text',
            [
                'label' => __('Button Text (Logged In)', 'shortcuts-hub'),
                'type' => \Elementor\Controls_Manager::TEXT,
                'default' => $this->get_default_settings()['button_text'],
            ]
        );

        $this->add_control(
            'button_text_logged_out',
            [
                'label' => __('Button Text (Logged Out)', 'shortcuts-hub'),
                'type' => \Elementor\Controls_Manager::TEXT,
                'default' => $this->get_default_settings()['button_text_logged_out'],
            ]
        );

        $this->add_control(
            'button_icon',
            [
                'label' => __('Icon', 'shortcuts-hub'),
                'type' => \Elementor\Controls_Manager::ICONS,
                'default' => $this->get_default_settings()['button_icon'],
            ]
        );

        $this->end_controls_section();

        $this->start_controls_section(
            'style_section',
            [
                'label' => __('Button', 'shortcuts-hub'),
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'icon_size',
            [
                'label' => __('Icon Size', 'shortcuts-hub'),
                'type' => \Elementor\Controls_Manager::SLIDER,
                'size_units' => ['px', 'em', 'rem', '%', 'vw'],
                'range' => [
                    'px' => [
                        'min' => 1,
                        'max' => 200,
                        'step' => 1,
                    ],
                    'em' => [
                        'min' => 0.1,
                        'max' => 10,
                        'step' => 0.1,
                    ],
                    'rem' => [
                        'min' => 0.1,
                        'max' => 10,
                        'step' => 0.1,
                    ],
                    '%' => [
                        'min' => 1,
                        'max' => 100,
                        'step' => 1,
                    ],
                    'vw' => [
                        'min' => 1,
                        'max' => 100,
                        'step' => 1,
                    ],
                ],
                'default' => $this->get_default_settings()['icon_size'],
                'selectors' => [
                    '{{WRAPPER}} .elementor-button-icon i' => 'font-size: {{SIZE}}{{UNIT}};',
                    '{{WRAPPER}} .elementor-button-icon svg' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'icon_spacing',
            [
                'label' => __('Icon Spacing', 'shortcuts-hub'),
                'type' => \Elementor\Controls_Manager::SLIDER,
                'size_units' => ['px', 'em'],
                'range' => [
                    'px' => [
                        'min' => 0,
                        'max' => 50,
                        'step' => 1,
                    ],
                    'em' => [
                        'min' => 0,
                        'max' => 4,
                        'step' => 0.1,
                    ],
                ],
                'default' => [
                    'unit' => 'px',
                    'size' => 8,
                ],
                'selectors' => [
                    '{{WRAPPER}} .elementor-button .elementor-button-icon' => 'margin-right: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name' => 'typography',
                'selector' => '{{WRAPPER}} .elementor-button',
                'default' => $this->get_default_settings()['typography_typography'],
            ]
        );

        $this->start_controls_tabs('button_style_tabs');

        $this->start_controls_tab(
            'button_style_normal',
            [
                'label' => __('Normal', 'shortcuts-hub'),
            ]
        );

        $this->add_control(
            'button_text_color',
            [
                'label' => __('Text Color', 'shortcuts-hub'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '',
                'selectors' => [
                    '{{WRAPPER}} .elementor-button .elementor-button-text' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'button_icon_color',
            [
                'label' => __('Icon Color', 'shortcuts-hub'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '',
                'selectors' => [
                    '{{WRAPPER}} .elementor-button .elementor-button-icon i' => 'color: {{VALUE}};',
                    '{{WRAPPER}} .elementor-button .elementor-button-icon svg' => 'fill: {{VALUE}};',
                ],
                'separator' => 'after',
            ]
        );

        $this->add_control(
            'button_background_color',
            [
                'label' => __('Background Color', 'shortcuts-hub'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '',
                'selectors' => [
                    '{{WRAPPER}} .elementor-button' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_tab();

        $this->start_controls_tab(
            'button_style_hover',
            [
                'label' => __('Hover', 'shortcuts-hub'),
            ]
        );

        $this->add_control(
            'button_hover_text_color',
            [
                'label' => __('Text Color', 'shortcuts-hub'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '',
                'selectors' => [
                    '{{WRAPPER}} .elementor-button:hover .elementor-button-text' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'button_hover_icon_color',
            [
                'label' => __('Icon Color', 'shortcuts-hub'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '',
                'selectors' => [
                    '{{WRAPPER}} .elementor-button:hover .elementor-button-icon i' => 'color: {{VALUE}};',
                    '{{WRAPPER}} .elementor-button:hover .elementor-button-icon svg' => 'fill: {{VALUE}};',
                ],
                'separator' => 'after',
            ]
        );

        $this->add_control(
            'button_hover_background_color',
            [
                'label' => __('Background Color', 'shortcuts-hub'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '',
                'selectors' => [
                    '{{WRAPPER}} .elementor-button:hover' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_tab();

        $this->end_controls_tabs();

        $this->add_control(
            'button_padding',
            [
                'label' => __('Padding', 'shortcuts-hub'),
                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', '%'],
                'selectors' => [
                    '{{WRAPPER}} .elementor-button' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
                'default' => $this->get_default_settings()['button_padding'],
            ]
        );

        $this->end_controls_section();

        $this->start_controls_section(
            'advanced_section',
            [
                'label' => __('Advanced', 'shortcuts-hub'),
                'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'shortcut_id',
            [
                'label' => __('Shortcut ID', 'shortcuts-hub'),
                'type' => \Elementor\Controls_Manager::TEXT,
                'default' => $this->get_default_settings()['shortcut_id'],
            ]
        );

        $this->add_control(
            'shortcut_name',
            [
                'label' => __('Shortcut Name', 'shortcuts-hub'),
                'type' => \Elementor\Controls_Manager::TEXT,
                'default' => $this->get_default_settings()['shortcut_name'],
            ]
        );

        $this->add_control(
            'version',
            [
                'label' => __('Version', 'shortcuts-hub'),
                'type' => \Elementor\Controls_Manager::TEXT,
                'default' => $this->get_default_settings()['version'],
            ]
        );

        $this->add_control(
            'download_url',
            [
                'label' => __('Download URL', 'shortcuts-hub'),
                'type' => \Elementor\Controls_Manager::TEXT,
                'default' => $this->get_default_settings()['download_url'],
            ]
        );

        $this->end_controls_section();
    }

    protected function render() {
        $settings = $this->get_settings_for_display();
        
        if (empty($settings)) {
            $settings = $this->get_default_settings();
        }
        
        $settings = wp_parse_args($settings, $this->get_default_settings());
        
        $post_id = get_the_ID();
        if (!$post_id) {
            echo '<div class="elementor-alert elementor-alert-warning">This button must be used on a post page.</div>';
            return;
        }
        
        $sb_id = get_post_meta($post_id, 'sb_id', true);
        if (!$sb_id) {
            echo '<div class="elementor-alert elementor-alert-warning">Shortcut ID not found for this post.</div>';
            return;
        }

        // Get the current page URL for redirect
        $current_url = get_permalink($post_id);

        if (is_user_logged_in()) {
            if (!is_singular('shortcut')) {
                echo '<div class="elementor-alert elementor-alert-warning">This button can only be used on a shortcut post page.</div>';
                return;
            }

            $response = sb_api_call("shortcuts/{$sb_id}/version/latest", 'GET');
            if (is_wp_error($response)) {
                error_log('Shortcuts Hub API Error: ' . $response->get_error_message());
                echo '<div class="elementor-alert elementor-alert-warning">Error fetching download link: ' . esc_html($response->get_error_message()) . '</div>';
                return;
            }

            error_log('Shortcuts Hub API Response: ' . print_r($response, true));

            // Extract download URL from the correct path in the response
            $download_url = '';
            if (isset($response['version']) && isset($response['version']['url'])) {
                $download_url = esc_url($response['version']['url']);
            } else {
                error_log('Shortcuts Hub: No download URL in response');
                return;
            }

            // Render the button
            $this->add_render_attribute('wrapper', 'class', 'elementor-button-wrapper');
            $this->add_render_attribute('button', [
                'class' => ['elementor-button', 'shortcut-download-btn', $settings['button_size']],
                'href' => 'javascript:void(0)',
                'data-download-url' => $download_url,
                'data-redirect-url' => $current_url,
                'data-sb-id' => $sb_id,
                'data-post-id' => $post_id,
                'data-version' => wp_json_encode($response)  // Store complete response for logging
            ]);
            
            ?>
            <div <?php $this->print_render_attribute_string('wrapper'); ?>>
                <a <?php $this->print_render_attribute_string('button'); ?>>
                    <span class="elementor-button-content-wrapper">
                        <?php if (!empty($settings['button_icon']['value'])) : ?>
                            <span class="elementor-button-icon">
                                <?php \Elementor\Icons_Manager::render_icon($settings['button_icon'], ['aria-hidden' => 'true']); ?>
                            </span>
                        <?php endif; ?>
                        <span class="elementor-button-text">
                            <?php echo esc_html(is_user_logged_in() ? $settings['button_text'] : $settings['button_text_logged_out']); ?>
                        </span>
                    </span>
                </a>
            </div>
            <?php
        } else {
            // For non-logged in users, render button that will redirect to login
            $this->add_render_attribute('wrapper', 'class', 'elementor-button-wrapper');
            
            // Get version data even for non-logged in users
            $response = sb_api_call("shortcuts/{$sb_id}/version/latest", 'GET');
            if (is_wp_error($response)) {
                error_log('Shortcuts Hub API Error (non-logged in): ' . $response->get_error_message());
            }
            
            $button_attrs = [
                'class' => ['elementor-button', 'shortcut-download-btn', $settings['button_size']],
                'href' => 'javascript:void(0)',
                'data-redirect-url' => $current_url,
                'data-sb-id' => $sb_id,
                'data-post-id' => $post_id
            ];
            
            if (!is_wp_error($response) && isset($response['version']) && isset($response['version']['url'])) {
                $button_attrs['data-download-url'] = esc_url($response['version']['url']);
                $button_attrs['data-version'] = wp_json_encode($response);
            }
            
            $this->add_render_attribute('button', $button_attrs);
            
            ?>
            <div <?php $this->print_render_attribute_string('wrapper'); ?>>
                <a <?php $this->print_render_attribute_string('button'); ?>>
                    <span class="elementor-button-content-wrapper">
                        <?php if (!empty($settings['button_icon']['value'])) : ?>
                            <span class="elementor-button-icon">
                                <?php \Elementor\Icons_Manager::render_icon($settings['button_icon'], ['aria-hidden' => 'true']); ?>
                            </span>
                        <?php endif; ?>
                        <span class="elementor-button-text">
                            <?php echo esc_html(is_user_logged_in() ? $settings['button_text'] : $settings['button_text_logged_out']); ?>
                        </span>
                    </span>
                </a>
            </div>
            <?php
        }
    }
}

class Shortcuts_Test_Widget extends \Elementor\Widget_Base {
    public function get_name() {
        return 'shortcuts_test_widget';
    }

    public function get_title() {
        return __('Shortcuts Test Widget', 'shortcuts-hub');
    }

    public function get_icon() {
        return 'eicon-code';
    }

    public function get_categories() {
        return ['shortcuts-hub'];
    }

    public function get_keywords() {
        return ['shortcuts', 'test'];
    }

    protected function render() {
        echo 'Hello, World!';
    }
}

function shortcuts_hub_download_button($atts) {
    $atts = shortcode_atts(array(
        'download_url' => '',
        'redirect_url' => get_permalink(),
        'sb_id' => '',
        'post_id' => get_the_ID(),
        'version' => '',
        'class' => '',
        'text' => ''
    ), $atts, 'shortcut_download_button');

    if (empty($atts['download_url'])) {
        return '';
    }

    // Set button text based on login status
    $button_text = !empty($atts['text']) ? $atts['text'] : (is_user_logged_in() ? 'Download' : 'Login to Download');
    
    // Build button classes
    $classes = array('shortcut-download-button');
    if (!empty($atts['class'])) {
        $classes[] = $atts['class'];
    }
    
    // Build button attributes
    $attributes = array(
        'class' => implode(' ', $classes),
        'href' => '#',
        'data-download-url' => esc_url($atts['download_url']),
        'data-redirect-url' => esc_url($atts['redirect_url']),
        'data-sb-id' => esc_attr($atts['sb_id']),
        'data-post-id' => esc_attr($atts['post_id']),
        'data-version' => esc_attr($atts['version'])
    );
    
    // Build HTML attributes string
    $html_attributes = '';
    foreach ($attributes as $key => $value) {
        $html_attributes .= sprintf(' %s="%s"', esc_attr($key), esc_attr($value));
    }
    
    return sprintf('<a%s>%s</a>', $html_attributes, esc_html($button_text));
}
add_shortcode('shortcut_download_button', 'shortcuts_hub_download_button');

// AJAX handler for storing download URLs
function store_download_urls() {
    check_ajax_referer('shortcuts_hub_nonce', '_wpnonce');

    $download_url = sanitize_url($_POST['download_url']);
    $redirect_url = sanitize_url($_POST['redirect_url']);

    // Generate a unique key for this download session
    $session_key = wp_generate_password(32, false);
    
    // Store URLs in transient with 15-minute expiry
    set_transient('sh_download_' . $session_key, array(
        'download_url' => $download_url,
        'redirect_url' => $redirect_url
    ), 15 * MINUTE_IN_SECONDS);

    // Set cookie with session key
    setcookie('sh_download_session', $session_key, time() + (15 * MINUTE_IN_SECONDS), '/');

    wp_send_json_success(array(
        'message' => 'URLs stored successfully',
        'session_key' => $session_key
    ));
}
add_action('wp_ajax_store_download_urls', 'store_download_urls');

// AJAX handler for storing download data
add_action('wp_ajax_log_shortcut_download', 'sh_log_shortcut_download');

function sh_log_shortcut_download() {
    // Ensure user is logged in
    if (!is_user_logged_in()) {
        error_log('[Shortcuts Hub] Download attempt by non-logged-in user');
        wp_send_json_error('User must be logged in to track downloads');
        return;
    }

    // Verify nonce
    if (!check_ajax_referer('shortcuts_hub_nonce', 'security', false)) {
        error_log('[Shortcuts Hub] Download logging failed: Invalid nonce');
        wp_send_json_error('Security check failed');
        return;
    }

    // Ensure the downloads table exists
    ensure_downloads_table_exists();

    $shortcut_id = isset($_POST['shortcut_id']) ? sanitize_text_field($_POST['shortcut_id']) : '';
    $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
    $version_data = isset($_POST['version_data']) ? $_POST['version_data'] : array();
    
    // If version_data is a string (JSON), decode it
    if (is_string($version_data)) {
        $version_data = json_decode(stripslashes($version_data), true);
    }

    if (empty($shortcut_id) || empty($post_id)) {
        error_log('[Shortcuts Hub] Download logging failed: Missing required parameters');
        wp_send_json_error('Missing required parameters');
        return;
    }

    $user_id = get_current_user_id();
    $logged = sh_log_shortcut_download_to_db($shortcut_id, $post_id, $user_id, $version_data);

    if ($logged) {
        wp_send_json_success('Download logged successfully');
    } else {
        error_log('[Shortcuts Hub] Failed to log download to database');
        wp_send_json_error('Failed to log download');
    }
}

function sh_log_shortcut_download_to_db($shortcut_id, $post_id, $user_id, $version_data) {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'shortcutshub_downloads';

    // Get IP address
    $ip_address = $_SERVER['REMOTE_ADDR'];
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip_address = $_SERVER['HTTP_X_FORWARDED_FOR'];
    }

    // Ensure version_data is an array
    if (!is_array($version_data)) {
        $version_data = array();
    }

    $data = array(
        'user_id' => $user_id,
        'shortcut_id' => $shortcut_id,
        'post_id' => $post_id,
        'post_url' => get_permalink($post_id),
        'shortcut_name' => get_the_title($post_id),
        'version' => isset($version_data['version']) ? sanitize_text_field($version_data['version']) : '',
        'version_notes' => isset($version_data['notes']) ? sanitize_text_field($version_data['notes']) : '',
        'minimum_ios' => isset($version_data['minimumiOS']) ? sanitize_text_field($version_data['minimumiOS']) : '',
        'minimum_mac' => isset($version_data['minimumMac']) ? sanitize_text_field($version_data['minimumMac']) : '',
        'download_url' => isset($version_data['url']) ? esc_url_raw($version_data['url']) : '',
        'ip_address' => sanitize_text_field($ip_address),
        'is_required' => isset($version_data['required']) ? (bool)$version_data['required'] : false,
        'download_date' => current_time('mysql')
    );
    
    $format = array(
        '%d', // user_id
        '%s', // shortcut_id
        '%d', // post_id
        '%s', // post_url
        '%s', // shortcut_name
        '%s', // version
        '%s', // version_notes
        '%s', // minimum_ios
        '%s', // minimum_mac
        '%s', // download_url
        '%s', // ip_address
        '%d', // is_required
        '%s'  // download_date
    );

    $result = $wpdb->insert($table_name, $data, $format);

    if ($result === false) {
        error_log('[Shortcuts Hub] Database error while logging download: ' . $wpdb->last_error);
        return false;
    }
    
    return true;
}