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
        
        if (!$post_id) {
            return '';
        }

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
        return [\Elementor\Modules\DynamicTags\Module::IMAGE_CATEGORY];
    }

    protected function register_controls() {
        $this->add_control(
            'override_icon',
            [
                'label' => esc_html__('Override Icon', 'shortcuts-hub'),
                'type' => \Elementor\Controls_Manager::ICONS,
                'default' => [
                    'value' => '',
                    'library' => 'fa-solid',
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
            ]
        );
    }

    public function get_value(array $options = []) {
        $settings = $this->get_settings();
        
        if (!empty($settings['override_icon']['value'])) {
            $size = !empty($settings['icon_size']['size']) ? $settings['icon_size']['size'] : 50;
            \Elementor\Icons_Manager::render_icon($settings['override_icon'], [
                'aria-hidden' => 'true',
                'style' => 'font-size: ' . $size . 'px;'
            ]);
            return '';
        }

        $post_id = get_the_ID();
        $icon_url = get_post_meta($post_id, 'icon', true);
        return !empty($icon_url) ? '<img src="' . esc_url($icon_url) . '" alt="' . esc_attr__('Icon', 'shortcuts-hub') . '">' : '';
    }

    public function render() {
        echo $this->get_value();
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
    require_once(__DIR__ . '/download-button.php');
    $widgets_manager->register(new \Shortcuts_Download_Button());
}

add_action('elementor/widgets/register', 'register_shortcut_widgets');
