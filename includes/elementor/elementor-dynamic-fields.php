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
        
        // Get the current post
        $post_id = get_the_ID();
        
        // DEBUG: Log post type check
        sh_debug_log('Post Type Check', array(
            'message' => 'Checking if post is a shortcut',
            'source' => array(
                'file' => __FILE__,
                'line' => __LINE__,
                'function' => __FUNCTION__
            ),
            'data' => array(
                'post_id' => $post_id,
                'post_type' => get_post_type($post_id)
            ),
            'debug' => true
        ));

        // Only proceed if this is a shortcut post type
        if (!$post || get_post_type($post_id) !== 'shortcut') {
            sh_debug_log('Not a shortcut post type', array(
                'message' => 'Current post is not a shortcut post type',
                'source' => array(
                    'file' => __FILE__,
                    'line' => __LINE__,
                    'function' => __FUNCTION__
                ),
                'data' => array(
                    'post_id' => $post_id,
                    'post_type' => get_post_type($post_id)
                ),
                'debug' => true
            ));
            return '';
        }
        
        // Get the shortcut name, fallback to post title if empty
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
        
        // Get the current post
        $post_id = get_the_ID();
        
        // DEBUG: Log post type check
        sh_debug_log('Post Type Check', array(
            'message' => 'Checking if post is a shortcut',
            'source' => array(
                'file' => __FILE__,
                'line' => __LINE__,
                'function' => __FUNCTION__
            ),
            'data' => array(
                'post_id' => $post_id,
                'post_type' => get_post_type($post_id)
            ),
            'debug' => true
        ));

        // Only proceed if this is a shortcut post type
        if (!$post || get_post_type($post_id) !== 'shortcut') {
            sh_debug_log('Not a shortcut post type', array(
                'message' => 'Current post is not a shortcut post type',
                'source' => array(
                    'file' => __FILE__,
                    'line' => __LINE__,
                    'function' => __FUNCTION__
                ),
                'data' => array(
                    'post_id' => $post_id,
                    'post_type' => get_post_type($post_id)
                ),
                'debug' => true
            ));
            return '';
        }
        
        // Get the shortcut headline
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
        
        // Get the current post
        $post_id = get_the_ID();
        
        // DEBUG: Log post type check
        sh_debug_log('Post Type Check', array(
            'message' => 'Checking if post is a shortcut',
            'source' => array(
                'file' => __FILE__,
                'line' => __LINE__,
                'function' => __FUNCTION__
            ),
            'data' => array(
                'post_id' => $post_id,
                'post_type' => get_post_type($post_id)
            ),
            'debug' => true
        ));

        // Only proceed if this is a shortcut post type
        if (!$post || get_post_type($post_id) !== 'shortcut') {
            sh_debug_log('Not a shortcut post type', array(
                'message' => 'Current post is not a shortcut post type',
                'source' => array(
                    'file' => __FILE__,
                    'line' => __LINE__,
                    'function' => __FUNCTION__
                ),
                'data' => array(
                    'post_id' => $post_id,
                    'post_type' => get_post_type($post_id)
                ),
                'debug' => true
            ));
            return '';
        }
        
        // Get the shortcut description
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
        
        // Get the current post
        $post_id = get_the_ID();
        
        // DEBUG: Log post type check
        sh_debug_log('Post Type Check', array(
            'message' => 'Checking if post is a shortcut',
            'source' => array(
                'file' => __FILE__,
                'line' => __LINE__,
                'function' => __FUNCTION__
            ),
            'data' => array(
                'post_id' => $post_id,
                'post_type' => get_post_type($post_id)
            ),
            'debug' => true
        ));

        // Only proceed if this is a shortcut post type
        if (!$post || get_post_type($post_id) !== 'shortcut') {
            sh_debug_log('Not a shortcut post type', array(
                'message' => 'Current post is not a shortcut post type',
                'source' => array(
                    'file' => __FILE__,
                    'line' => __LINE__,
                    'function' => __FUNCTION__
                ),
                'data' => array(
                    'post_id' => $post_id,
                    'post_type' => get_post_type($post_id)
                ),
                'debug' => true
            ));
            return '';
        }
        
        // Get the shortcut color
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
        
        // Get the current post
        $post_id = get_the_ID();
        
        // DEBUG: Log post type check
        sh_debug_log('Post Type Check', array(
            'message' => 'Checking if post is a shortcut',
            'source' => array(
                'file' => __FILE__,
                'line' => __LINE__,
                'function' => __FUNCTION__
            ),
            'data' => array(
                'post_id' => $post_id,
                'post_type' => get_post_type($post_id)
            ),
            'debug' => true
        ));

        // Only proceed if this is a shortcut post type
        if (!$post || get_post_type($post_id) !== 'shortcut') {
            sh_debug_log('Not a shortcut post type', array(
                'message' => 'Current post is not a shortcut post type',
                'source' => array(
                    'file' => __FILE__,
                    'line' => __LINE__,
                    'function' => __FUNCTION__
                ),
                'data' => array(
                    'post_id' => $post_id,
                    'post_type' => get_post_type($post_id)
                ),
                'debug' => true
            ));
            return '';
        }
        
        // Get the shortcut input
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
        
        // Get the current post
        $post_id = get_the_ID();
        
        // DEBUG: Log post type check
        sh_debug_log('Post Type Check', array(
            'message' => 'Checking if post is a shortcut',
            'source' => array(
                'file' => __FILE__,
                'line' => __LINE__,
                'function' => __FUNCTION__
            ),
            'data' => array(
                'post_id' => $post_id,
                'post_type' => get_post_type($post_id)
            ),
            'debug' => true
        ));

        // Only proceed if this is a shortcut post type
        if (!$post || get_post_type($post_id) !== 'shortcut') {
            sh_debug_log('Not a shortcut post type', array(
                'message' => 'Current post is not a shortcut post type',
                'source' => array(
                    'file' => __FILE__,
                    'line' => __LINE__,
                    'function' => __FUNCTION__
                ),
                'data' => array(
                    'post_id' => $post_id,
                    'post_type' => get_post_type($post_id)
                ),
                'debug' => true
            ));
            return '';
        }
        
        // Get the shortcut result
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
        
        // Get the current post
        $post_id = get_the_ID();
        
        // DEBUG: Log post type check
        sh_debug_log('Post Type Check', array(
            'message' => 'Checking if post is a shortcut',
            'source' => array(
                'file' => __FILE__,
                'line' => __LINE__,
                'function' => __FUNCTION__
            ),
            'data' => array(
                'post_id' => $post_id,
                'post_type' => get_post_type($post_id)
            ),
            'debug' => true
        ));

        // Only proceed if this is a shortcut post type
        if (!$post || get_post_type($post_id) !== 'shortcut') {
            sh_debug_log('Not a shortcut post type', array(
                'message' => 'Current post is not a shortcut post type',
                'source' => array(
                    'file' => __FILE__,
                    'line' => __LINE__,
                    'function' => __FUNCTION__
                ),
                'data' => array(
                    'post_id' => $post_id,
                    'post_type' => get_post_type($post_id)
                ),
                'debug' => true
            ));
            return '';
        }
        
        // Get the shortcut latest version
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
        
        // Get the current post
        $post_id = get_the_ID();
        
        // DEBUG: Log post type check
        sh_debug_log('Post Type Check', array(
            'message' => 'Checking if post is a shortcut',
            'source' => array(
                'file' => __FILE__,
                'line' => __LINE__,
                'function' => __FUNCTION__
            ),
            'data' => array(
                'post_id' => $post_id,
                'post_type' => get_post_type($post_id)
            ),
            'debug' => true
        ));

        // Only proceed if this is a shortcut post type
        if (!$post || get_post_type($post_id) !== 'shortcut') {
            sh_debug_log('Not a shortcut post type', array(
                'message' => 'Current post is not a shortcut post type',
                'source' => array(
                    'file' => __FILE__,
                    'line' => __LINE__,
                    'function' => __FUNCTION__
                ),
                'data' => array(
                    'post_id' => $post_id,
                    'post_type' => get_post_type($post_id)
                ),
                'debug' => true
            ));
            return '';
        }

        // Get the shortcut ID from post meta
        $shortcut_id = get_post_meta($post_id, '_shortcut_id', true);
        if (!$shortcut_id) {
            sh_debug_log('No shortcut ID found for post', array(
                'message' => 'No shortcut ID found for post',
                'source' => array(
                    'file' => __FILE__,
                    'line' => __LINE__,
                    'function' => __FUNCTION__
                ),
                'data' => array(
                    'post_id' => $post_id,
                    'shortcut_id' => $shortcut_id
                ),
                'debug' => true
            ));
            return '';
        }

        // Make AJAX call to fetch latest version URL
        $response = wp_remote_get(admin_url('admin-ajax.php'), [
            'body' => [
                'action' => 'get_latest_version',
                'shortcut_id' => $shortcut_id
            ]
        ]);

        if (is_wp_error($response)) {
            sh_debug_log('Error fetching version URL', array(
                'message' => 'Error fetching version URL',
                'source' => array(
                    'file' => __FILE__,
                    'line' => __LINE__,
                    'function' => __FUNCTION__
                ),
                'data' => array(
                    'error' => $response->get_error_message()
                ),
                'debug' => true
            ));
            return '';
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        // Check if we have a successful response with version URL
        if (!$data || !isset($data['success']) || !$data['success'] || 
            !isset($data['data']) || !isset($data['data']['version']) || 
            !isset($data['data']['version']['url'])) {
            sh_debug_log('Invalid version URL response', array(
                'message' => 'Invalid version URL response structure',
                'source' => array(
                    'file' => __FILE__,
                    'line' => __LINE__,
                    'function' => __FUNCTION__
                ),
                'data' => array(
                    'response' => $data
                ),
                'debug' => true
            ));
            return '';
        }

        $url = $data['data']['version']['url'];
        echo esc_url($url);
    }
}
