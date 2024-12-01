<?php
namespace ShortcutsHub\Elementor\DynamicTags;

use Elementor\Core\DynamicTags\Tag;
use Elementor\Modules\DynamicTags\Module;
use Elementor\Controls_Manager;
use Elementor\Widget_Base;

if (!defined('ABSPATH')) {
    exit;
}

if (!did_action('elementor/loaded')) {
    return;
}

class Name_Dynamic_Tag extends Tag {
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
        return [Module::TEXT_CATEGORY];
    }

    public function render() {
        $post_id = get_the_ID();
        echo esc_html(get_post_meta($post_id, 'name', true) ?: get_the_title($post_id));
    }
}

class Headline_Dynamic_Tag extends Tag {
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
        return [Module::TEXT_CATEGORY];
    }

    public function render() {
        echo esc_html(get_post_meta(get_the_ID(), 'headline', true));
    }
}

class Description_Dynamic_Tag extends Tag {
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
        return [Module::TEXT_CATEGORY];
    }

    public function render() {
        echo esc_html(get_post_meta(get_the_ID(), 'description', true));
    }
}

class Color_Dynamic_Tag extends Tag {
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
        return [Module::COLOR_CATEGORY];
    }

    public function render() {
        $color = get_post_meta(get_the_ID(), 'color', true);
        echo !empty($color) ? esc_html($color) : '';
    }
}

class Input_Dynamic_Tag extends Tag {
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
        return [Module::TEXT_CATEGORY];
    }

    public function render() {
        echo esc_html(get_post_meta(get_the_ID(), 'input', true));
    }
}

class Result_Dynamic_Tag extends Tag {
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
        return [Module::TEXT_CATEGORY];
    }

    public function render() {
        echo esc_html(get_post_meta(get_the_ID(), 'result', true));
    }
}

class Latest_Version_Dynamic_Tag extends Tag {
    public function get_name() {
        return 'latest_version';  
    }

    public function get_title() {
        return __('Latest Version', 'shortcuts-hub');
    }

    public function get_group() {
        return 'shortcut_fields';
    }

    public function get_categories() {
        return [Module::TEXT_CATEGORY];
    }

    public function render() {
        echo esc_html(get_post_meta(get_the_ID(), 'latest_version', true));
    }
}

class Latest_Version_URL_Dynamic_Tag extends Tag {
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
        return [Module::URL_CATEGORY];
    }

    public function render() {
        $post_id = get_the_ID();
        if (!$post_id) {
            error_log('[Shortcuts Hub] Dynamic Tag: No post ID found');
            return '';
        }

        // Get the shortcut ID from post meta
        $shortcut_id = get_post_meta($post_id, '_shortcut_id', true);
        if (!$shortcut_id) {
            error_log('[Shortcuts Hub] Dynamic Tag: No shortcut ID found for post ' . $post_id);
            return '';
        }

        error_log('[Shortcuts Hub] Dynamic Tag: Fetching latest version for shortcut ' . $shortcut_id);

        // Make AJAX call to fetch latest version
        $response = wp_remote_post(admin_url('admin-ajax.php'), [
            'body' => [
                'action' => 'fetch_latest_version',
                'security' => wp_create_nonce('shortcuts_hub_nonce'),
                'id' => $shortcut_id
            ]
        ]);

        if (is_wp_error($response)) {
            error_log('[Shortcuts Hub] Dynamic Tag: Error fetching version - ' . $response->get_error_message());
            return '';
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        error_log('[Shortcuts Hub] Dynamic Tag: API Response - ' . print_r($data, true));

        // Check if we have a successful response with version URL
        if (!$data || !$data['success'] || !isset($data['data']['version']['url'])) {
            error_log('[Shortcuts Hub] Dynamic Tag: Invalid response structure');
            return '';
        }

        $url = $data['data']['version']['url'];
        error_log('[Shortcuts Hub] Dynamic Tag: Successfully retrieved URL - ' . $url);
        
        echo esc_url($url);
    }
}
