<?php

class Name_Dynamic_Tag extends \Elementor\Core\DynamicTags\Tag {
    public function get_name() {
        return 'name';
    }

    public function get_title() {
        return __('Name', 'shortcuts-hub');
    }

    public function get_group() {
        return 'shortcut_fields';
    }

    public function get_categories() {
        return [\Elementor\Modules\DynamicTags\Module::TEXT_CATEGORY];
    }

    protected function register_controls() {
        $this->add_control(
            'before',
            [
                'label' => esc_html__('Before', 'shortcuts-hub'),
                'type' => \Elementor\Controls_Manager::TEXT,
            ]
        );

        $this->add_control(
            'after',
            [
                'label' => esc_html__('After', 'shortcuts-hub'),
                'type' => \Elementor\Controls_Manager::TEXT,
            ]
        );

        $this->add_control(
            'fallback',
            [
                'label' => esc_html__('Fallback', 'shortcuts-hub'),
                'type' => \Elementor\Controls_Manager::TEXT,
            ]
        );
    }

    public function get_value(array $options = []) {
        $post_id = get_the_ID();
        $name = get_post_meta($post_id, 'name', true);
        
        if (empty($name)) {
            $name = get_the_title($post_id);
        }
        
        return !empty($name) ? esc_html($name) : '';
    }

    public function get_content(array $options = []) {
        $settings = $this->get_settings();
        $value = $this->get_value();

        if (empty($value)) {
            $value = $settings['fallback'];
        }

        if (empty($value)) {
            return '';
        }

        return $settings['before'] . $value . $settings['after'];
    }

    public function render() {
        echo $this->get_content();
    }
}

class Headline_Dynamic_Tag extends \Elementor\Core\DynamicTags\Tag {
    public function get_name() {
        return 'headline';
    }

    public function get_title() {
        return __('Headline', 'shortcuts-hub');
    }

    public function get_group() {
        return 'shortcut_fields';
    }

    public function get_categories() {
        return [\Elementor\Modules\DynamicTags\Module::TEXT_CATEGORY];
    }

    protected function register_controls() {
        $this->add_control(
            'before',
            [
                'label' => esc_html__('Before', 'shortcuts-hub'),
                'type' => \Elementor\Controls_Manager::TEXT,
            ]
        );

        $this->add_control(
            'after',
            [
                'label' => esc_html__('After', 'shortcuts-hub'),
                'type' => \Elementor\Controls_Manager::TEXT,
            ]
        );

        $this->add_control(
            'fallback',
            [
                'label' => esc_html__('Fallback', 'shortcuts-hub'),
                'type' => \Elementor\Controls_Manager::TEXT,
            ]
        );
    }

    public function get_value(array $options = []) {
        $post_id = get_the_ID();
        $headline = get_post_meta($post_id, 'headline', true);
        return !empty($headline) ? esc_html($headline) : '';
    }

    public function get_content(array $options = []) {
        $settings = $this->get_settings();
        $value = $this->get_value();

        if (empty($value)) {
            $value = $settings['fallback'];
        }

        if (empty($value)) {
            return '';
        }

        return $settings['before'] . $value . $settings['after'];
    }

    public function render() {
        echo $this->get_content();
    }
}

class Description_Dynamic_Tag extends \Elementor\Core\DynamicTags\Tag {
    public function get_name() {
        return 'description';
    }

    public function get_title() {
        return __('Description', 'shortcuts-hub');
    }

    public function get_group() {
        return 'shortcut_fields';
    }

    public function get_categories() {
        return [\Elementor\Modules\DynamicTags\Module::TEXT_CATEGORY];
    }

    protected function register_controls() {
        $this->add_control(
            'before',
            [
                'label' => esc_html__('Before', 'shortcuts-hub'),
                'type' => \Elementor\Controls_Manager::TEXT,
            ]
        );

        $this->add_control(
            'after',
            [
                'label' => esc_html__('After', 'shortcuts-hub'),
                'type' => \Elementor\Controls_Manager::TEXT,
            ]
        );

        $this->add_control(
            'fallback',
            [
                'label' => esc_html__('Fallback', 'shortcuts-hub'),
                'type' => \Elementor\Controls_Manager::TEXT,
            ]
        );
    }

    public function get_value(array $options = []) {
        $post_id = get_the_ID();
        $description = get_post_meta($post_id, 'description', true);
        return !empty($description) ? esc_html($description) : '';
    }

    public function get_content(array $options = []) {
        $settings = $this->get_settings();
        $value = $this->get_value();

        if (empty($value)) {
            $value = $settings['fallback'];
        }

        if (empty($value)) {
            return '';
        }

        return $settings['before'] . $value . $settings['after'];
    }

    public function render() {
        echo $this->get_content();
    }
}

class Color_Dynamic_Tag extends \Elementor\Core\DynamicTags\Tag {
    public function get_name() {
        return 'color';
    }

    public function get_title() {
        return __('Color', 'shortcuts-hub');
    }

    public function get_group() {
        return 'shortcut_fields';
    }

    public function get_categories() {
        return [\Elementor\Modules\DynamicTags\Module::COLOR_CATEGORY];
    }

    public function get_value(array $options = []) {
        $post_id = get_the_ID();
        $color = get_post_meta($post_id, 'color', true);
        return !empty($color) ? esc_html($color) : '';
    }

    public function render() {
        echo $this->get_value();
    }
}

class Icon_Dynamic_Tag extends \Elementor\Core\DynamicTags\Tag {
    public function get_name() {
        return 'icon';
    }

    public function get_title() {
        return __('Icon', 'shortcuts-hub');
    }

    public function get_group() {
        return 'shortcut_fields';
    }

    public function get_categories() {
        return [\Elementor\Modules\DynamicTags\Module::TEXT_CATEGORY];
    }

    protected function register_controls() {
        $this->add_control(
            'before',
            [
                'label' => esc_html__('Before', 'shortcuts-hub'),
                'type' => \Elementor\Controls_Manager::TEXT,
            ]
        );

        $this->add_control(
            'after',
            [
                'label' => esc_html__('After', 'shortcuts-hub'),
                'type' => \Elementor\Controls_Manager::TEXT,
            ]
        );

        $this->add_control(
            'fallback',
            [
                'label' => esc_html__('Fallback', 'shortcuts-hub'),
                'type' => \Elementor\Controls_Manager::TEXT,
            ]
        );
    }

    public function get_value(array $options = []) {
        $post_id = get_the_ID();
        
        $icon_data = get_post_meta($post_id, 'icon', true);
        
        if (!empty($icon_data)) {
            $decoded = json_decode($icon_data, true);
            if ($decoded && isset($decoded['type'])) {
                if ($decoded['type'] === 'fontawesome' && isset($decoded['name'])) {
                    return [
                        'value' => $decoded['name'],
                        'library' => $decoded['library'] ?? 'fa-solid'
                    ];
                } elseif ($decoded['type'] === 'svg' && isset($decoded['url'])) {
                    return [
                        'value' => [
                            'url' => $decoded['url'],
                            'id' => $decoded['id'] ?? ''
                        ],
                        'library' => 'svg'
                    ];
                }
            }
            
            // Legacy format handling
            if (strpos($icon_data, 'fa-') !== false) {
                $library = 'fa-solid';
                if (strpos($icon_data, 'fab ') === 0) {
                    $library = 'fa-brands';
                } elseif (strpos($icon_data, 'far ') === 0) {
                    $library = 'fa-regular';
                }
                return [
                    'value' => $icon_data,
                    'library' => $library
                ];
            }
        }
        
        return [
            'value' => 'fas fa-mobile-alt',
            'library' => 'fa-solid'
        ];
    }

    public function get_content(array $options = []) {
        $settings = $this->get_settings();
        $value = $this->get_value();

        if (empty($value)) {
            $value = $settings['fallback'];
        }

        if (empty($value)) {
            return '';
        }

        return $settings['before'] . $value . $settings['after'];
    }

    public function render() {
        echo $this->get_content();
    }
}

class Shortcuts_Icon_Widget extends \Elementor\Widget_Base {
    public function __construct($data = [], $args = null) {
        parent::__construct($data, $args);
    }

    public function get_name() {
        return 'shortcut_icon';
    }

    public function get_title() {
        return esc_html__('Shortcut Icon', 'shortcuts-hub');
    }

    public function get_icon() {
        return 'eicon-favorite';
    }

    public function get_categories() {
        return ['shortcuts-hub'];
    }

    public function get_keywords() {
        return ['shortcuts', 'icon', 'shortcut'];
    }

    protected function register_controls() {
        $this->start_controls_section(
            'section_icon',
            [
                'label' => esc_html__('Icon', 'shortcuts-hub'),
            ]
        );

        $this->add_control(
            'icon_source',
            [
                'label' => esc_html__('Icon Source', 'shortcuts-hub'),
                'type' => \Elementor\Controls_Manager::SELECT,
                'default' => 'shortcut',
                'options' => [
                    'shortcut' => esc_html__('Shortcut Icon', 'shortcuts-hub'),
                    'custom' => esc_html__('Custom Icon', 'shortcuts-hub'),
                ],
            ]
        );

        $this->add_control(
            'icon_size',
            [
                'label' => esc_html__('Icon Size', 'shortcuts-hub'),
                'type' => \Elementor\Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => [
                        'min' => 6,
                        'max' => 300,
                    ],
                ],
                'default' => [
                    'unit' => 'px',
                    'size' => 50,
                ],
                'selectors' => [
                    '{{WRAPPER}} .elementor-icon img' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
                    '{{WRAPPER}} .elementor-icon i' => 'font-size: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'selected_icon',
            [
                'label' => esc_html__('Custom Icon', 'shortcuts-hub'),
                'type' => \Elementor\Controls_Manager::ICONS,
                'default' => [
                    'value' => 'fas fa-mobile-alt',
                    'library' => 'fa-solid',
                ],
                'recommended' => [
                    'fa-solid' => [
                        'mobile-alt',
                        'tablet-alt',
                        'laptop',
                        'desktop',
                        'cog',
                        'tools',
                    ],
                ],
                'condition' => [
                    'icon_source' => 'custom',
                ],
            ]
        );

        $this->add_control(
            'view',
            [
                'label' => esc_html__('View', 'shortcuts-hub'),
                'type' => \Elementor\Controls_Manager::SELECT,
                'options' => [
                    'default' => esc_html__('Default', 'shortcuts-hub'),
                    'stacked' => esc_html__('Stacked', 'shortcuts-hub'),
                    'framed' => esc_html__('Framed', 'shortcuts-hub'),
                ],
                'default' => 'default',
                'prefix_class' => 'elementor-view-',
            ]
        );

        $this->add_control(
            'shape',
            [
                'label' => esc_html__('Shape', 'shortcuts-hub'),
                'type' => \Elementor\Controls_Manager::SELECT,
                'options' => [
                    'circle' => esc_html__('Circle', 'shortcuts-hub'),
                    'square' => esc_html__('Square', 'shortcuts-hub'),
                ],
                'default' => 'circle',
                'condition' => [
                    'view!' => 'default',
                ],
                'prefix_class' => 'elementor-shape-',
            ]
        );

        $this->end_controls_section();

        $this->start_controls_section(
            'section_style_icon',
            [
                'label' => esc_html__('Icon', 'shortcuts-hub'),
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'primary_color',
            [
                'label' => esc_html__('Primary Color', 'shortcuts-hub'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '',
                'selectors' => [
                    '{{WRAPPER}}.elementor-view-stacked .elementor-icon' => 'background-color: {{VALUE}};',
                    '{{WRAPPER}}.elementor-view-framed .elementor-icon, {{WRAPPER}}.elementor-view-default .elementor-icon' => 'color: {{VALUE}}; border-color: {{VALUE}};',
                    '{{WRAPPER}}.elementor-view-framed .elementor-icon, {{WRAPPER}}.elementor-view-default .elementor-icon svg' => 'fill: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'secondary_color',
            [
                'label' => esc_html__('Secondary Color', 'shortcuts-hub'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '',
                'condition' => [
                    'view!' => 'default',
                ],
                'selectors' => [
                    '{{WRAPPER}}.elementor-view-framed .elementor-icon' => 'background-color: {{VALUE}};',
                    '{{WRAPPER}}.elementor-view-stacked .elementor-icon' => 'color: {{VALUE}};',
                    '{{WRAPPER}}.elementor-view-stacked .elementor-icon svg' => 'fill: {{VALUE}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'size',
            [
                'label' => esc_html__('Size', 'shortcuts-hub'),
                'type' => \Elementor\Controls_Manager::SLIDER,
                'range' => [
                    'px' => [
                        'min' => 6,
                        'max' => 300,
                    ],
                ],
                'selectors' => [
                    '{{WRAPPER}} .elementor-icon' => 'font-size: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'hover_animation',
            [
                'label' => esc_html__('Hover Animation', 'shortcuts-hub'),
                'type' => \Elementor\Controls_Manager::HOVER_ANIMATION,
            ]
        );

        $this->end_controls_section();
    }

    protected function render() {
        $settings = $this->get_settings_for_display();

        $this->add_render_attribute('wrapper', [
            'class' => 'elementor-icon-wrapper',
            'style' => 'display: inline-block; line-height: 0;'
        ]);
        
        $this->add_render_attribute('icon-wrapper', [
            'class' => 'elementor-icon',
            'style' => 'display: inline-block;'
        ]);

        if (!empty($settings['hover_animation'])) {
            $this->add_render_attribute('icon-wrapper', 'class', 'elementor-animation-' . $settings['hover_animation']);
        }

        $icon_html = '';
        if ($settings['icon_source'] === 'shortcut') {
            $post_id = get_the_ID();
            $icon_data = get_post_meta($post_id, 'icon', true);
            
            if (!empty($icon_data)) {
                $decoded = json_decode($icon_data, true);
                if ($decoded && isset($decoded['type'])) {
                    if ($decoded['type'] === 'fontawesome' && isset($decoded['name'])) {
                        ob_start();
                        \Elementor\Icons_Manager::render_icon(
                            [
                                'value' => $decoded['name'],
                                'library' => $decoded['library'] ?? 'fa-solid'
                            ],
                            ['aria-hidden' => 'true']
                        );
                        $icon_html = ob_get_clean();
                    } elseif ($decoded['type'] === 'svg' && isset($decoded['url'])) {
                        ob_start();
                        \Elementor\Icons_Manager::render_icon(
                            [
                                'value' => [
                                    'url' => $decoded['url'],
                                    'id' => $decoded['id'] ?? ''
                                ],
                                'library' => 'svg'
                            ],
                            ['aria-hidden' => 'true']
                        );
                        $icon_html = ob_get_clean();
                    }
                } else {
                    // Legacy format handling
                    if (strpos($icon_data, 'fa-') !== false) {
                        $library = 'fa-solid';
                        if (strpos($icon_data, 'fab ') === 0) {
                            $library = 'fa-brands';
                        } elseif (strpos($icon_data, 'far ') === 0) {
                            $library = 'fa-regular';
                        }
                        ob_start();
                        \Elementor\Icons_Manager::render_icon(
                            [
                                'value' => $icon_data,
                                'library' => $library
                            ],
                            ['aria-hidden' => 'true']
                        );
                        $icon_html = ob_get_clean();
                    }
                }
            }
        } else if (!empty($settings['selected_icon']['value'])) {
            // Custom icon selected in widget settings
            ob_start();
            \Elementor\Icons_Manager::render_icon($settings['selected_icon'], ['aria-hidden' => 'true']);
            $icon_html = ob_get_clean();
        }

        // Fallback to default icon if nothing else worked
        if (empty($icon_html)) {
            ob_start();
            \Elementor\Icons_Manager::render_icon(
                [
                    'value' => 'fas fa-mobile-alt',
                    'library' => 'fa-solid'
                ],
                ['aria-hidden' => 'true']
            );
            $icon_html = ob_get_clean();
        }

        ?>
        <div <?php echo $this->get_render_attribute_string('wrapper'); ?>>
            <div <?php echo $this->get_render_attribute_string('icon-wrapper'); ?>>
                <?php echo $icon_html; ?>
            </div>
        </div>
        <?php
    }
}

class Input_Dynamic_Tag extends \Elementor\Core\DynamicTags\Tag {
    public function get_name() {
        return 'input';
    }

    public function get_title() {
        return __('Input', 'shortcuts-hub');
    }

    public function get_group() {
        return 'shortcut_fields';
    }

    public function get_categories() {
        return [\Elementor\Modules\DynamicTags\Module::TEXT_CATEGORY];
    }

    protected function register_controls() {
        $this->add_control(
            'before',
            [
                'label' => esc_html__('Before', 'shortcuts-hub'),
                'type' => \Elementor\Controls_Manager::TEXT,
            ]
        );

        $this->add_control(
            'after',
            [
                'label' => esc_html__('After', 'shortcuts-hub'),
                'type' => \Elementor\Controls_Manager::TEXT,
            ]
        );

        $this->add_control(
            'fallback',
            [
                'label' => esc_html__('Fallback', 'shortcuts-hub'),
                'type' => \Elementor\Controls_Manager::TEXT,
            ]
        );
    }

    public function get_value(array $options = []) {
        $post_id = get_the_ID();
        $input = get_post_meta($post_id, 'input', true);
        return !empty($input) ? esc_html($input) : '';
    }

    public function get_content(array $options = []) {
        $settings = $this->get_settings();
        $value = $this->get_value();

        if (empty($value)) {
            $value = $settings['fallback'];
        }

        if (empty($value)) {
            return '';
        }

        return $settings['before'] . $value . $settings['after'];
    }

    public function render() {
        echo $this->get_content();
    }
}

class Result_Dynamic_Tag extends \Elementor\Core\DynamicTags\Tag {
    public function get_name() {
        return 'result';
    }

    public function get_title() {
        return __('Result', 'shortcuts-hub');
    }

    public function get_group() {
        return 'shortcut_fields';
    }

    public function get_categories() {
        return [\Elementor\Modules\DynamicTags\Module::TEXT_CATEGORY];
    }

    protected function register_controls() {
        $this->add_control(
            'before',
            [
                'label' => esc_html__('Before', 'shortcuts-hub'),
                'type' => \Elementor\Controls_Manager::TEXT,
            ]
        );

        $this->add_control(
            'after',
            [
                'label' => esc_html__('After', 'shortcuts-hub'),
                'type' => \Elementor\Controls_Manager::TEXT,
            ]
        );

        $this->add_control(
            'fallback',
            [
                'label' => esc_html__('Fallback', 'shortcuts-hub'),
                'type' => \Elementor\Controls_Manager::TEXT,
            ]
        );
    }

    public function get_value(array $options = []) {
        $post_id = get_the_ID();
        $result = get_post_meta($post_id, 'result', true);
        return !empty($result) ? esc_html($result) : '';
    }

    public function get_content(array $options = []) {
        $settings = $this->get_settings();
        $value = $this->get_value();

        if (empty($value)) {
            $value = $settings['fallback'];
        }

        if (empty($value)) {
            return '';
        }

        return $settings['before'] . $value . $settings['after'];
    }

    public function render() {
        echo $this->get_content();
    }
}

class Latest_Version_URL_Dynamic_Tag extends \Elementor\Core\DynamicTags\Tag {
    public function get_name() {
        return 'latest_version_url';
    }

    public function get_title() {
        return __('Latest Version URL', 'shortcuts-hub');
    }

    public function get_group() {
        return 'shortcut_fields';
    }

    public function get_categories() {
        return [\Elementor\Modules\DynamicTags\Module::URL_CATEGORY];
    }

    public function get_value(array $options = []) {
        $post_id = get_the_ID();
        $id = get_post_meta($post_id, 'id', true);

        if (!empty($id)) {
            $response = sb_api_call("shortcuts/{$id}/version/latest", 'GET');
            return !empty($response['download_url']) ? $response['download_url'] : '';
        }
        
        return '';
    }

    public function render() {
        echo $this->get_value();
    }
}

function register_shortcut_widgets($widgets_manager) {
    $widgets_manager->register(new \Shortcuts_Icon_Widget());
}
add_action('elementor/widgets/register', 'register_shortcut_widgets');
