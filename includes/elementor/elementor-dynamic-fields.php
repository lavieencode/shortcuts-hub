<?php
namespace ShortcutsHub\Elementor\DynamicTags;

use Elementor\Core\DynamicTags\Tag;
use Elementor\Modules\DynamicTags\Module;
use Elementor\Controls_Manager;
use Elementor\Widget_Base;

if (!defined('ABSPATH')) {
    exit;
}

if (!function_exists('sh_debug_log')) {
    require_once dirname(dirname(dirname(__FILE__))) . '/sh-debug.php';
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
        global $post;
        
        $post_id = get_the_ID();
        
        if (!$post || get_post_type($post_id) !== 'shortcut') {
            return '';
        }
        
        $shortcut_name = get_post_meta($post_id, '_shortcut_name', true);
        echo esc_html($shortcut_name ?: get_the_title($post_id));
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
        global $post;
        
        $post_id = get_the_ID();
        
        if (!$post || get_post_type($post_id) !== 'shortcut') {
            return '';
        }
        
        $headline = get_post_meta($post_id, '_shortcut_headline', true);
        echo esc_html($headline);
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
        global $post;
        
        $post_id = get_the_ID();
        
        if (!$post || get_post_type($post_id) !== 'shortcut') {
            return '';
        }
        
        $description = get_post_meta($post_id, '_shortcut_description', true);
        echo esc_html($description);
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
        global $post;
        
        $post_id = get_the_ID();
        
        if (!$post || get_post_type($post_id) !== 'shortcut') {
            return '';
        }
        
        $color = get_post_meta($post_id, '_shortcut_color', true);
        echo esc_html($color);
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
        global $post;
        
        $post_id = get_the_ID();
        
        if (!$post || get_post_type($post_id) !== 'shortcut') {
            return '';
        }
        
        $input = get_post_meta($post_id, '_shortcut_input', true);
        echo esc_html($input);
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
        global $post;
        
        $post_id = get_the_ID();
        
        if (!$post || get_post_type($post_id) !== 'shortcut') {
            return '';
        }
        
        $result = get_post_meta($post_id, '_shortcut_result', true);
        echo esc_html($result);
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
        global $post;
        
        $post_id = get_the_ID();
        
        if (!$post || get_post_type($post_id) !== 'shortcut') {
            return '';
        }
        
        $latest_version = get_post_meta($post_id, '_shortcut_latest_version', true);
        echo esc_html($latest_version);
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
        global $post;
        
        $post_id = get_the_ID();
        
        if (!$post || get_post_type($post_id) !== 'shortcut') {
            return '';
        }

        $shortcut_id = get_post_meta($post_id, '_shortcut_id', true);
        if (!$shortcut_id) {
            return '';
        }

        $response = wp_remote_get(admin_url('admin-ajax.php'), [
            'body' => [
                'action' => 'get_latest_version',
                'shortcut_id' => $shortcut_id
            ]
        ]);

        if (is_wp_error($response)) {
            return '';
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (!$data || !isset($data['success']) || !$data['success'] || 
            !isset($data['data']) || !isset($data['data']['version']) || 
            !isset($data['data']['version']['url'])) {
            return '';
        }

        $url = $data['data']['version']['url'];
        echo esc_url($url);
    }
}
