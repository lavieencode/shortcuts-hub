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
            .elementor-widget-shortcuts_download_button[data-element_type="shortcuts_download_button.default"] .elementor-button {
                width: 100%;
            }
            .elementor-widget-shortcuts_download_button .elementor-button-wrapper a {
                width: 100%;
            }
        </style>
        <?php
    }

    public function get_default_settings() {
        return [
            'button_text' => __('Download', 'shortcuts-hub'),
            'button_icon' => 'fa fa-download',
            'button_color' => '#FFFFFF',
            'button_background_color' => '#909CFE',
            'border_radius' => [
                'size' => 0,
                'unit' => 'px',
            ],
            'icon_spacing' => [
                'size' => 10,
                'unit' => 'px',
            ],
            'button_position' => 'center',
            'typography_typography' => 'custom',
            'typography_font_size' => [
                'size' => 15,
                'unit' => 'px',
            ],
            'typography_font_weight' => '500',
            'padding' => [
                'top' => '15',
                'right' => '30',
                'bottom' => '15',
                'left' => '30',
                'unit' => 'px',
                'isLinked' => false,
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
                'label' => __('Button Text', 'shortcuts-hub'),
                'type' => \Elementor\Controls_Manager::TEXT,
                'default' => $this->get_default_settings()['button_text'],
            ]
        );

        $this->add_control(
            'button_icon',
            [
                'label' => __('Icon', 'shortcuts-hub'),
                'type' => \Elementor\Controls_Manager::ICON,
                'default' => $this->get_default_settings()['button_icon'],
            ]
        );

        $this->add_control(
            'button_color',
            [
                'label' => __('Text Color', 'shortcuts-hub'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => $this->get_default_settings()['button_color'],
                'selectors' => [
                    '{{WRAPPER}} .elementor-button' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'button_background_color',
            [
                'label' => __('Background Color', 'shortcuts-hub'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => $this->get_default_settings()['button_background_color'],
                'selectors' => [
                    '{{WRAPPER}} .elementor-button' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'icon_spacing',
            [
                'label' => __('Icon Spacing', 'shortcuts-hub'),
                'type' => \Elementor\Controls_Manager::SLIDER,
                'default' => $this->get_default_settings()['icon_spacing'],
                'range' => [
                    'px' => [
                        'min' => 0,
                        'max' => 50,
                    ],
                ],
                'selectors' => [
                    '{{WRAPPER}} .elementor-button i' => 'margin-right: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'border_radius',
            [
                'label' => __('Border Radius', 'shortcuts-hub'),
                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'default' => $this->get_default_settings()['border_radius'],
                'selectors' => [
                    '{{WRAPPER}} .elementor-button' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();

        $this->start_controls_section(
            'typography_section',
            [
                'label' => __('Typography', 'shortcuts-hub'),
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
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

        if (is_user_logged_in()) {
            if (!is_singular('shortcut')) {
                echo '<div class="elementor-alert elementor-alert-warning">This button can only be used on a shortcut post page.</div>';
                return;
            }

            $response = sb_api_call("shortcuts/{$sb_id}/version/latest", 'GET');
            if (is_wp_error($response)) {
                echo '<div class="elementor-alert elementor-alert-warning">Error fetching download link: ' . esc_html($response->get_error_message()) . '</div>';
                return;
            }

            $download_url = '';
            if (isset($response['version']['url'])) {
                $download_url = esc_url($response['version']['url']);
            }

            if (!empty($download_url)) {
                $transient_key = 'download_url_' . get_current_user_id();
                set_transient($transient_key, $download_url, HOUR_IN_SECONDS);
                
                $this->add_render_attribute('wrapper', 'class', 'elementor-button-wrapper');
                $this->add_render_attribute('button', [
                    'class' => 'elementor-button elementor-size-md shortcuts-download-btn',
                    'href' => 'javascript:void(0)',
                    'data-sb-id' => $sb_id,
                    'data-post-id' => $post_id,
                    'data-post-url' => get_permalink($post_id),
                    'data-download-url' => $download_url,
                    'data-nonce' => wp_create_nonce('shortcut_download'),
                    'data-shortcut-name' => isset($response['shortcut']['name']) ? esc_attr($response['shortcut']['name']) : '',
                    'data-version' => isset($response['version']) ? esc_attr($response['version']) : '',
                ]);

                ?>
                <div <?php echo $this->get_render_attribute_string('wrapper'); ?>>
                    <a <?php echo $this->get_render_attribute_string('button'); ?>>
                        <?php if (!empty($settings['button_icon'])) : ?>
                            <i class="<?php echo esc_attr($settings['button_icon']); ?>"></i>
                        <?php endif; ?>
                        <?php echo esc_html($settings['button_text']); ?>
                    </a>
                </div>

                <script>
                document.addEventListener('DOMContentLoaded', function() {
                    const downloadBtn = document.querySelector('.shortcuts-download-btn');
                    let downloadWindow = null;
                    let downloadStarted = false;
                    let downloadTimeout = null;
                    
                    function checkPopupStatus() {
                        if (downloadWindow && downloadWindow.closed) {
                            downloadWindow = null;
                            if (!downloadStarted) {
                                console.log('Download window closed without starting download');
                            }
                            if (downloadTimeout) {
                                clearTimeout(downloadTimeout);
                            }
                        }
                    }

                    function logDownload() {
                        if (downloadStarted) {
                            return;
                        }
                        
                        downloadStarted = true;
                        const shortcutId = downloadBtn.getAttribute('data-sb-id');
                        const postId = downloadBtn.getAttribute('data-post-id');
                        const nonce = downloadBtn.getAttribute('data-nonce');
                        const shortcutName = downloadBtn.getAttribute('data-shortcut-name');
                        const version = downloadBtn.getAttribute('data-version');

                        const data = new FormData();
                        data.append('action', 'log_shortcut_download');
                        data.append('shortcut_id', shortcutId);
                        data.append('post_id', postId);
                        data.append('shortcut_name', shortcutName);
                        data.append('version', version);
                        data.append('nonce', nonce);

                        fetch(ajaxurl, {
                            method: 'POST',
                            credentials: 'same-origin',
                            body: data
                        })
                        .then(response => response.json())
                        .then(data => {
                            console.log('Download logged successfully:', data);
                        })
                        .catch(error => {
                            console.error('Error logging download:', error);
                        });
                    }
                    
                    downloadBtn.addEventListener('click', function(e) {
                        e.preventDefault();
                        const downloadUrl = this.getAttribute('data-download-url');
                        
                        const width = 800;
                        const height = 600;
                        const left = (window.innerWidth - width) / 2;
                        const top = (window.innerHeight - height) / 2;
                        
                        downloadWindow = window.open(
                            downloadUrl,
                            'ShortcutDownload',
                            `width=${width},height=${height},left=${left},top=${top},menubar=no,toolbar=no,location=no,status=no`
                        );

                        const popupChecker = setInterval(() => {
                            if (downloadWindow && downloadWindow.closed) {
                                clearInterval(popupChecker);
                                downloadWindow = null;
                                if (!downloadStarted) {
                                    console.log('Download window closed without starting download');
                                }
                            }
                        }, 500);

                        downloadTimeout = setTimeout(() => {
                            if (downloadWindow && !downloadWindow.closed) {
                                logDownload();
                            }
                        }, 5000);

                        if (downloadWindow) {
                            downloadWindow.focus();
                        }
                    });
                });
                </script>
                <?php
            } else {
                echo '<div class="elementor-alert elementor-alert-warning">Download link not available. Please try again later.</div>';
            }
        } else {
            $login_url = 'https://debotchery.ai/shortcuts-gallery/login';
            
            $this->add_render_attribute('login_button', [
                'class' => 'elementor-button elementor-size-md',
                'href' => esc_url($login_url),
            ]);
            
            ?>
            <div class="elementor-button-wrapper">
                <a <?php echo $this->get_render_attribute_string('login_button'); ?>>
                    <?php if (!empty($settings['button_icon'])) : ?>
                        <i class="<?php echo esc_attr($settings['button_icon']); ?>"></i>
                    <?php endif; ?>
                    <?php echo esc_html__('Login to Download', 'shortcuts-hub'); ?>
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