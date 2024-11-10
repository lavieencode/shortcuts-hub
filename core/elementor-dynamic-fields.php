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

    public function get_content(array $options = []) {
        $post_id = get_the_ID();
        $name = get_post_meta($post_id, 'shortcut_name', true);

        return !empty($name) ? esc_html($name) : '';
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

    public function get_content(array $options = []) {
        $post_id = get_the_ID();
        $headline = get_post_meta($post_id, 'headline', true);

        return !empty($headline) ? esc_html($headline) : '';
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

    public function get_content(array $options = []) {
        $post_id = get_the_ID();
        $description = get_post_meta($post_id, 'description', true);

        return !empty($description) ? esc_html($description) : '';
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
        return [\Elementor\Modules\DynamicTags\Module::TEXT_CATEGORY];
    }

    public function get_content(array $options = []) {
        $post_id = get_the_ID();
        $color = get_post_meta($post_id, 'color', true);

        return !empty($color) ? esc_html($color) : '';
    }

    public function render() {
        echo $this->get_content();
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

    public function get_content(array $options = []) {
        $post_id = get_the_ID();
        $icon_url = get_post_meta($post_id, 'icon', true);

        return !empty($icon_url) ? '<img src="' . esc_url($icon_url) . '" alt="' . esc_attr__('Icon', 'shortcuts-hub') . '">' : '';
    }

    public function render() {
        echo $this->get_content();
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

    public function get_content(array $options = []) {
        $post_id = get_the_ID();
        $input = get_post_meta($post_id, 'input', true);

        return !empty($input) ? esc_html($input) : '';
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

    public function get_content(array $options = []) {
        $post_id = get_the_ID();
        $result = get_post_meta($post_id, 'result', true);

        return !empty($result) ? esc_html($result) : '';
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

    public function get_content(array $options = []) {
        $post_id = get_the_ID();
        $shortcut_id = get_post_meta($post_id, 'sb_id', true);

        if (!empty($shortcut_id)) {
            $response = sb_api_call("shortcuts/{$shortcut_id}/version/latest", 'GET');
            return (!is_wp_error($response) && isset($response['url'])) ? esc_url($response['url']) : '';
        }

        return '';
    }

    public function render() {
        echo $this->get_content();
    }
}

function register_shortcut_dynamic_tags($dynamic_tags_manager) {
    if (class_exists('Name_Dynamic_Tag')) {
        $dynamic_tags_manager->register(new Name_Dynamic_Tag());
    }
    if (class_exists('Headline_Dynamic_Tag')) {
        $dynamic_tags_manager->register(new Headline_Dynamic_Tag());
    }
    if (class_exists('Description_Dynamic_Tag')) {
        $dynamic_tags_manager->register(new Description_Dynamic_Tag());
    }
    if (class_exists('Color_Dynamic_Tag')) {
        $dynamic_tags_manager->register(new Color_Dynamic_Tag());
    }
    if (class_exists('Icon_Dynamic_Tag')) {
        $dynamic_tags_manager->register(new Icon_Dynamic_Tag());
    }
    if (class_exists('Input_Dynamic_Tag')) {
        $dynamic_tags_manager->register(new Input_Dynamic_Tag());
    }
    if (class_exists('Result_Dynamic_Tag')) {
        $dynamic_tags_manager->register(new Result_Dynamic_Tag());
    }
    if (class_exists('Latest_Version_URL_Dynamic_Tag')) {
        $dynamic_tags_manager->register(new Latest_Version_URL_Dynamic_Tag());
    }
}

add_action('elementor/dynamic_tags/register', 'register_shortcut_dynamic_tags');

add_action('elementor/dynamic_tags/register', function($dynamic_tags_manager) {
    $dynamic_tags_manager->register_group(
        'shortcut_fields',
        [
            'title' => esc_html__('Shortcut Fields', 'shortcuts-hub'),
        ]
    );
});
