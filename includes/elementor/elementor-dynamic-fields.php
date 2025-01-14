<?php
namespace ShortcutsHub\Elementor\DynamicTags;

use Elementor\Core\DynamicTags\Tag;
use Elementor\Modules\DynamicTags\Module;
use Elementor\Controls_Manager;
use Elementor\Widget_Base;

if (!defined('ABSPATH')) {
    exit;
}

require_once dirname(dirname(dirname(__FILE__))) . '/sh-debug.php';

if (!did_action('elementor/loaded')) {
    return;
}

abstract class Shortcut_Dynamic_Tag extends Tag {
    public function get_group() {
        return 'shortcut_fields';
    }

    protected function register_controls() {
        $this->add_control(
            'shortcut_id',
            [
                'label' => __('Shortcut', 'shortcuts-hub'),
                'type' => Controls_Manager::NUMBER,
                'default' => get_the_ID()
            ]
        );
    }

    protected function get_shortcut_id() {
        $settings = $this->get_settings();
        if (empty($settings['shortcut_id'])) {
            if (wp_doing_ajax() && isset($_REQUEST['editor_post_id'])) {
                return absint($_REQUEST['editor_post_id']);
            }
            return get_the_ID();
        }
        return $settings['shortcut_id'];
    }

    abstract protected function get_tag_value();

    public function get_value(array $options = []) {
        return ['value' => $this->get_tag_value()];
    }

    public function render() {
        $value = $this->get_tag_value();
        echo wp_kses_post($value);
    }
}

class Name_Dynamic_Tag extends Shortcut_Dynamic_Tag {
    public function get_name() {
        return 'name';
    }

    public function get_title() {
        return esc_html__('Name', 'shortcuts-hub');
    }

    public function get_categories() {
        return ['text'];
    }

    protected function get_tag_value() {
        $post_id = $this->get_shortcut_id();
        return get_the_title($post_id);
    }
}

class Headline_Dynamic_Tag extends Shortcut_Dynamic_Tag {
    public function get_name() {
        return 'headline';
    }

    public function get_title() {
        return esc_html__('Headline', 'shortcuts-hub');
    }

    public function get_categories() {
        return ['text'];
    }

    protected function get_tag_value() {
        $post_id = $this->get_shortcut_id();
        return get_post_meta($post_id, '_shortcut_headline', true);
    }
}

class Description_Dynamic_Tag extends Shortcut_Dynamic_Tag {
    public function get_name() {
        return 'description';
    }

    public function get_title() {
        return esc_html__('Description', 'shortcuts-hub');
    }

    public function get_categories() {
        return ['text'];
    }

    protected function get_tag_value() {
        $post_id = $this->get_shortcut_id();
        return get_post_meta($post_id, '_shortcut_description', true);
    }
}

class Color_Dynamic_Tag extends Shortcut_Dynamic_Tag {
    public function get_name() {
        return 'color';
    }

    public function get_title() {
        return esc_html__('Color', 'shortcuts-hub');
    }

    public function get_categories() {
        return ['color'];
    }

    protected function get_tag_value() {
        $post_id = $this->get_shortcut_id();
        return get_post_meta($post_id, '_shortcut_color', true);
    }
}

class Input_Dynamic_Tag extends Shortcut_Dynamic_Tag {
    public function get_name() {
        return 'input';
    }

    public function get_title() {
        return esc_html__('Input', 'shortcuts-hub');
    }

    public function get_categories() {
        return ['text'];
    }

    protected function get_tag_value() {
        $post_id = $this->get_shortcut_id();
        return get_post_meta($post_id, '_shortcut_input', true);
    }
}

class Result_Dynamic_Tag extends Shortcut_Dynamic_Tag {
    public function get_name() {
        return 'result';
    }

    public function get_title() {
        return esc_html__('Result', 'shortcuts-hub');
    }

    public function get_categories() {
        return ['text'];
    }

    protected function get_tag_value() {
        $post_id = $this->get_shortcut_id();
        return get_post_meta($post_id, '_shortcut_result', true);
    }
}

class Latest_Version_Dynamic_Tag extends Shortcut_Dynamic_Tag {
    public function get_name() {
        return 'latest_version';
    }

    public function get_title() {
        return esc_html__('Latest Version', 'shortcuts-hub');
    }

    public function get_categories() {
        return ['text'];
    }

    protected function get_tag_value() {
        $post_id = $this->get_shortcut_id();
        return get_post_meta($post_id, '_shortcut_latest_version', true);
    }
}

class Latest_Version_URL_Dynamic_Tag extends Shortcut_Dynamic_Tag {
    public function get_name() {
        return 'latest_version_url';
    }

    public function get_title() {
        return esc_html__('Latest Version URL', 'shortcuts-hub');
    }

    public function get_categories() {
        return ['url'];
    }

    protected function get_tag_value() {
        try {
            $post_id = $this->get_shortcut_id();
            if (!$post_id) {
                return '';
            }

            // Get the shortcut ID from post meta
            $shortcut_id = get_post_meta($post_id, '_shortcut_id', true);
            if (!$shortcut_id) {
                return '';
            }

            // Make API request to get latest version
            $response = wp_remote_get(SHORTCUTS_HUB_API_URL . '/shortcuts/' . $shortcut_id . '/latest-version');
            if (is_wp_error($response)) {
                return '';
            }

            $body = wp_remote_retrieve_body($response);
            $data = json_decode($body, true);

            // Check if we have a successful response with version URL
            if (!$data || !$data['success'] || !isset($data['data']['version']['url'])) {
                return '';
            }

            return $data['data']['version']['url'];
        } catch (\Exception $e) {
            return '';
        }
    }
}
