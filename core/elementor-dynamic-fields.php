<?php
if ( ! defined( 'ELEMENTOR_VERSION' ) ) {
    return;
}

class Shortcut_Name_Field_Dynamic_Tag extends \Elementor\Core\DynamicTags\Tag {
    public function get_name() {
        return 'shortcut_name_field_dynamic_tag';
    }

    public function get_title() {
        return 'Shortcut Name';
    }

    public function get_group() {
        return 'shortcut_fields';
    }

    public function get_categories() {
        return [ \Elementor\Modules\DynamicTags\Module::TEXT_CATEGORY ];
    }

    protected function register_controls() {
        $this->end_controls_section();
        $this->start_controls_section(
            'settings',
            [
                'label' => 'Settings',
            ]
        );

        $this->add_control(
            'before',
            [
                'label' => 'Before',
                'type' => \Elementor\Controls_Manager::TEXT,
                'default' => '',
            ]
        );

        $this->add_control(
            'after',
            [
                'label' => 'After',
                'type' => \Elementor\Controls_Manager::TEXT,
                'default' => '',
            ]
        );

        $this->add_control(
            'fallback',
            [
                'label' => 'Fallback',
                'type' => \Elementor\Controls_Manager::TEXT,
                'default' => '',
            ]
        );

        $this->end_controls_section();
    }

    public function render() {
        $before = $this->get_settings('before');
        $after = $this->get_settings('after');
        $fallback = $this->get_settings('fallback');

        $value = get_post_meta( get_the_ID(), 'shortcut_name', true );

        if (!is_null($value)) {
            echo esc_html($before . $value . $after);
        } else {
            echo esc_html($fallback);
        }
    }
}

class Shortcut_Headline_Field_Dynamic_Tag extends \Elementor\Core\DynamicTags\Tag {
    public function get_name() {
        return 'shortcut_headline_field_dynamic_tag';
    }

    public function get_title() {
        return 'Shortcut Headline';
    }

    public function get_group() {
        return 'shortcut_fields';
    }

    public function get_categories() {
        return [ \Elementor\Modules\DynamicTags\Module::TEXT_CATEGORY ];
    }

    protected function register_controls() {
        $this->end_controls_section();
        $this->start_controls_section(
            'settings',
            [
                'label' => 'Settings',
            ]
        );

        $this->add_control(
            'before',
            [
                'label' => 'Before',
                'type' => \Elementor\Controls_Manager::TEXT,
                'default' => '',
            ]
        );

        $this->add_control(
            'after',
            [
                'label' => 'After',
                'type' => \Elementor\Controls_Manager::TEXT,
                'default' => '',
            ]
        );

        $this->add_control(
            'fallback',
            [
                'label' => 'Fallback',
                'type' => \Elementor\Controls_Manager::TEXT,
                'default' => '',
            ]
        );

        $this->end_controls_section();
    }

    public function render() {
        $before = $this->get_settings('before');
        $after = $this->get_settings('after');
        $fallback = $this->get_settings('fallback');

        $value = get_post_meta( get_the_ID(), 'headline', true );

        echo !empty($value) ? esc_html( $before . $value . $after ) : esc_html( $fallback );
    }
}

class Shortcut_Description_Field_Dynamic_Tag extends \Elementor\Core\DynamicTags\Tag {
    public function get_name() {
        return 'shortcut_description_field_dynamic_tag';
    }

    public function get_title() {
        return 'Shortcut Description';
    }

    public function get_group() {
        return 'shortcut_fields';
    }

    public function get_categories() {
        return [ \Elementor\Modules\DynamicTags\Module::TEXT_CATEGORY ];
    }

    protected function register_controls() {
        $this->end_controls_section();
        $this->start_controls_section(
            'settings',
            [
                'label' => 'Settings',
            ]
        );

        $this->add_control(
            'before',
            [
                'label' => 'Before',
                'type' => \Elementor\Controls_Manager::TEXT,
                'default' => '',
            ]
        );

        $this->add_control(
            'after',
            [
                'label' => 'After',
                'type' => \Elementor\Controls_Manager::TEXT,
                'default' => '',
            ]
        );

        $this->add_control(
            'fallback',
            [
                'label' => 'Fallback',
                'type' => \Elementor\Controls_Manager::TEXT,
                'default' => '',
            ]
        );

        $this->end_controls_section();
    }

    public function render() {
        $before = $this->get_settings('before');
        $after = $this->get_settings('after');
        $fallback = $this->get_settings('fallback');

        $value = get_post_meta( get_the_ID(), 'description', true );

        echo !empty($value) ? esc_html( $before . $value . $after ) : esc_html( $fallback );
    }
}

class Shortcut_Icon_Field_Dynamic_Tag extends \Elementor\Core\DynamicTags\Tag {
    public function get_name() {
        return 'shortcut_icon_field_dynamic_tag';
    }

    public function get_title() {
        return 'Shortcut Icon';
    }

    public function get_group() {
        return 'shortcut_fields';
    }

    public function get_categories() {
        return [ \Elementor\Modules\DynamicTags\Module::IMAGE_CATEGORY ];
    }

    protected function register_controls() {
        $this->end_controls_section();
        $this->start_controls_section(
            'settings',
            [
                'label' => 'Settings',
            ]
        );

        $this->add_control(
            'fallback',
            [
                'label' => 'Fallback',
                'type' => \Elementor\Controls_Manager::MEDIA,
                'default' => [
                    'url' => \Elementor\Utils::get_placeholder_image_src(),
                ],
            ]
        );

        $this->end_controls_section();
    }

    public function render() {
        $fallback = $this->get_settings('fallback');
        $value = get_post_meta( get_the_ID(), 'icon', true );

        if (!empty($value)) {
            echo esc_url($value);
        } else {
            echo esc_url($fallback['url']);
        }
    }
}

class Shortcut_Color_Field_Dynamic_Tag extends \Elementor\Core\DynamicTags\Tag {
    public function get_name() {
        return 'shortcut_color_field_dynamic_tag';
    }

    public function get_title() {
        return 'Shortcut Color';
    }

    public function get_group() {
        return 'shortcut_fields';
    }

    public function get_categories() {
        return [ \Elementor\Modules\DynamicTags\Module::TEXT_CATEGORY ];
    }

    protected function register_controls() {
        $this->end_controls_section();
        $this->start_controls_section(
            'settings',
            [
                'label' => 'Settings',
            ]
        );

        $this->add_control(
            'before',
            [
                'label' => 'Before',
                'type' => \Elementor\Controls_Manager::TEXT,
                'default' => '',
            ]
        );

        $this->add_control(
            'after',
            [
                'label' => 'After',
                'type' => \Elementor\Controls_Manager::TEXT,
                'default' => '',
            ]
        );

        $this->add_control(
            'fallback',
            [
                'label' => 'Fallback',
                'type' => \Elementor\Controls_Manager::TEXT,
                'default' => '',
            ]
        );

        $this->end_controls_section();
    }

    public function render() {
        $before = $this->get_settings('before');
        $after = $this->get_settings('after');
        $fallback = $this->get_settings('fallback');

        $value = get_post_meta( get_the_ID(), 'color', true );

        echo !empty($value) ? esc_html( $before . $value . $after ) : esc_html( $fallback );
    }
}

class Shortcut_Input_Field_Dynamic_Tag extends \Elementor\Core\DynamicTags\Tag {
    public function get_name() {
        return 'shortcut_input_field_dynamic_tag';
    }

    public function get_title() {
        return 'Shortcut Input';
    }

    public function get_group() {
        return 'shortcut_fields';
    }

    public function get_categories() {
        return [ \Elementor\Modules\DynamicTags\Module::TEXT_CATEGORY ];
    }

    protected function register_controls() {
        $this->end_controls_section();
        $this->start_controls_section(
            'settings',
            [
                'label' => 'Settings',
            ]
        );

        $this->add_control(
            'before',
            [
                'label' => 'Before',
                'type' => \Elementor\Controls_Manager::TEXT,
                'default' => '',
            ]
        );

        $this->add_control(
            'after',
            [
                'label' => 'After',
                'type' => \Elementor\Controls_Manager::TEXT,
                'default' => '',
            ]
        );

        $this->add_control(
            'fallback',
            [
                'label' => 'Fallback',
                'type' => \Elementor\Controls_Manager::TEXT,
                'default' => '',
            ]
        );

        $this->end_controls_section();
    }

    public function render() {
        $before = $this->get_settings('before');
        $after = $this->get_settings('after');
        $fallback = $this->get_settings('fallback');

        $value = get_post_meta( get_the_ID(), 'input', true );

        echo !empty($value) ? esc_html( $before . $value . $after ) : esc_html( $fallback );
    }
}

class Shortcut_Result_Field_Dynamic_Tag extends \Elementor\Core\DynamicTags\Tag {
    public function get_name() {
        return 'shortcut_result_field_dynamic_tag';
    }

    public function get_title() {
        return 'Shortcut Result';
    }

    public function get_group() {
        return 'shortcut_fields';
    }

    public function get_categories() {
        return [ \Elementor\Modules\DynamicTags\Module::TEXT_CATEGORY ];
    }

    protected function register_controls() {
        $this->end_controls_section();
        $this->start_controls_section(
            'settings',
            [
                'label' => 'Settings',
            ]
        );

        $this->add_control(
            'before',
            [
                'label' => 'Before',
                'type' => \Elementor\Controls_Manager::TEXT,
                'default' => '',
            ]
        );

        $this->add_control(
            'after',
            [
                'label' => 'After',
                'type' => \Elementor\Controls_Manager::TEXT,
                'default' => '',
            ]
        );

        $this->add_control(
            'fallback',
            [
                'label' => 'Fallback',
                'type' => \Elementor\Controls_Manager::TEXT,
                'default' => '',
            ]
        );

        $this->end_controls_section();
    }

    public function render() {
        $before = $this->get_settings('before');
        $after = $this->get_settings('after');
        $fallback = $this->get_settings('fallback');

        $value = get_post_meta( get_the_ID(), 'result', true );

        echo !empty($value) ? esc_html( $before . $value . $after ) : esc_html( $fallback );
    }
}

function register_shortcut_dynamic_tags( $dynamic_tags_manager ) {
    $dynamic_tags_manager->register( new \Shortcut_Name_Field_Dynamic_Tag() );
    $dynamic_tags_manager->register( new \Shortcut_Headline_Field_Dynamic_Tag() );
    $dynamic_tags_manager->register( new \Shortcut_Description_Field_Dynamic_Tag() );
    $dynamic_tags_manager->register( new \Shortcut_Icon_Field_Dynamic_Tag() );
    $dynamic_tags_manager->register( new \Shortcut_Color_Field_Dynamic_Tag() );
    $dynamic_tags_manager->register( new \Shortcut_Input_Field_Dynamic_Tag() );
    $dynamic_tags_manager->register( new \Shortcut_Result_Field_Dynamic_Tag() );
}

add_action( 'elementor/dynamic_tags/register', 'register_shortcut_dynamic_tags' );

add_action( 'elementor/dynamic_tags/register_tags', function( $dynamic_tags_manager ) {
    $dynamic_tags_manager->register_group( 'shortcut_fields', [
        'title' => 'Shortcut Fields',
    ]);
});