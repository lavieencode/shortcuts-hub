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
        return 'name';  // Revert to original name
    }

    public function get_title() {
        return __('Name', 'shortcuts-hub');  // Revert to original title
    }

    public function get_group() {
        return 'shortcut_fields';
    }

    public function get_categories() {
        return [Module::TEXT_CATEGORY];
    }

    protected function register_controls() {
        $this->add_control(
            'before',
            [
                'label' => esc_html__('Before', 'shortcuts-hub'),
                'type' => Controls_Manager::TEXT,
            ]
        );

        $this->add_control(
            'after',
            [
                'label' => esc_html__('After', 'shortcuts-hub'),
                'type' => Controls_Manager::TEXT,
            ]
        );

        $this->add_control(
            'fallback',
            [
                'label' => esc_html__('Fallback', 'shortcuts-hub'),
                'type' => Controls_Manager::TEXT,
            ]
        );
    }

    public function render() {
        $settings = $this->get_settings();
        
        $post_id = get_the_ID();
        $name = get_post_meta($post_id, 'name', true);
        
        if (empty($name)) {
            $name = get_the_title($post_id);
        }
        
        if (empty($name) && !empty($settings['fallback'])) {
            $name = $settings['fallback'];
        }

        if (!empty($name)) {
            echo $settings['before'] . esc_html($name) . $settings['after'];
        }
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

    protected function register_controls() {
        $this->add_control(
            'before',
            [
                'label' => esc_html__('Before', 'shortcuts-hub'),
                'type' => Controls_Manager::TEXT,
            ]
        );

        $this->add_control(
            'after',
            [
                'label' => esc_html__('After', 'shortcuts-hub'),
                'type' => Controls_Manager::TEXT,
            ]
        );

        $this->add_control(
            'fallback',
            [
                'label' => esc_html__('Fallback', 'shortcuts-hub'),
                'type' => Controls_Manager::TEXT,
            ]
        );
    }

    public function render() {
        $settings = $this->get_settings();
        
        $post_id = get_the_ID();
        $headline = get_post_meta($post_id, 'headline', true);
        
        if (empty($headline) && !empty($settings['fallback'])) {
            $headline = $settings['fallback'];
        }

        if (!empty($headline)) {
            echo $settings['before'] . esc_html($headline) . $settings['after'];
        }
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

    protected function register_controls() {
        $this->add_control(
            'before',
            [
                'label' => esc_html__('Before', 'shortcuts-hub'),
                'type' => Controls_Manager::TEXT,
            ]
        );

        $this->add_control(
            'after',
            [
                'label' => esc_html__('After', 'shortcuts-hub'),
                'type' => Controls_Manager::TEXT,
            ]
        );

        $this->add_control(
            'fallback',
            [
                'label' => esc_html__('Fallback', 'shortcuts-hub'),
                'type' => Controls_Manager::TEXT,
            ]
        );
    }

    public function render() {
        $settings = $this->get_settings();
        
        $post_id = get_the_ID();
        $description = get_post_meta($post_id, 'description', true);
        
        if (empty($description) && !empty($settings['fallback'])) {
            $description = $settings['fallback'];
        }

        if (!empty($description)) {
            echo $settings['before'] . esc_html($description) . $settings['after'];
        }
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
        $post_id = get_the_ID();
        $color = get_post_meta($post_id, 'color', true);
        echo !empty($color) ? esc_html($color) : '';
    }
}

class Icon_Dynamic_Tag extends Tag {
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
        return [Module::TEXT_CATEGORY];
    }

    protected function register_controls() {
        $this->add_control(
            'before',
            [
                'label' => esc_html__('Before', 'shortcuts-hub'),
                'type' => Controls_Manager::TEXT,
            ]
        );

        $this->add_control(
            'after',
            [
                'label' => esc_html__('After', 'shortcuts-hub'),
                'type' => Controls_Manager::TEXT,
            ]
        );

        $this->add_control(
            'fallback',
            [
                'label' => esc_html__('Fallback', 'shortcuts-hub'),
                'type' => Controls_Manager::TEXT,
            ]
        );
    }

    public function render() {
        $settings = $this->get_settings();
        
        $post_id = get_the_ID();
        
        $icon_data = get_post_meta($post_id, 'icon', true);
        
        if (!empty($icon_data)) {
            $decoded = json_decode($icon_data, true);
            if ($decoded && isset($decoded['type'])) {
                if ($decoded['type'] === 'fontawesome' && isset($decoded['name'])) {
                    $icon_html = \Elementor\Icons_Manager::render_icon(
                        [
                            'value' => $decoded['name'],
                            'library' => $decoded['library'] ?? 'fa-solid'
                        ],
                        ['aria-hidden' => 'true']
                    );
                } elseif ($decoded['type'] === 'svg' && isset($decoded['url'])) {
                    $icon_html = \Elementor\Icons_Manager::render_icon(
                        [
                            'value' => [
                                'url' => $decoded['url'],
                                'id' => $decoded['id'] ?? ''
                            ],
                            'library' => 'svg'
                        ],
                        ['aria-hidden' => 'true']
                    );
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
                    $icon_html = \Elementor\Icons_Manager::render_icon(
                        [
                            'value' => $icon_data,
                            'library' => $library
                        ],
                        ['aria-hidden' => 'true']
                    );
                }
            }
        }
        
        if (empty($icon_html)) {
            $icon_html = \Elementor\Icons_Manager::render_icon(
                [
                    'value' => 'fas fa-mobile-alt',
                    'library' => 'fa-solid'
                ],
                ['aria-hidden' => 'true']
            );
        }

        if (!empty($icon_html)) {
            echo $settings['before'] . $icon_html . $settings['after'];
        }
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

    protected function register_controls() {
        $this->add_control(
            'before',
            [
                'label' => esc_html__('Before', 'shortcuts-hub'),
                'type' => Controls_Manager::TEXT,
            ]
        );

        $this->add_control(
            'after',
            [
                'label' => esc_html__('After', 'shortcuts-hub'),
                'type' => Controls_Manager::TEXT,
            ]
        );

        $this->add_control(
            'fallback',
            [
                'label' => esc_html__('Fallback', 'shortcuts-hub'),
                'type' => Controls_Manager::TEXT,
            ]
        );
    }

    public function render() {
        $settings = $this->get_settings();
        
        $post_id = get_the_ID();
        $input = get_post_meta($post_id, 'input', true);
        
        if (empty($input) && !empty($settings['fallback'])) {
            $input = $settings['fallback'];
        }

        if (!empty($input)) {
            echo esc_html($input);
        }
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

    protected function register_controls() {
        $this->add_control(
            'before',
            [
                'label' => esc_html__('Before', 'shortcuts-hub'),
                'type' => Controls_Manager::TEXT,
            ]
        );

        $this->add_control(
            'after',
            [
                'label' => esc_html__('After', 'shortcuts-hub'),
                'type' => Controls_Manager::TEXT,
            ]
        );

        $this->add_control(
            'fallback',
            [
                'label' => esc_html__('Fallback', 'shortcuts-hub'),
                'type' => Controls_Manager::TEXT,
            ]
        );
    }

    public function render() {
        $settings = $this->get_settings();
        
        $post_id = get_the_ID();
        $result = get_post_meta($post_id, 'result', true);
        
        if (empty($result) && !empty($settings['fallback'])) {
            $result = $settings['fallback'];
        }

        if (!empty($result)) {
            echo esc_html($result);
        }
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

    protected function register_controls() {
        $this->add_control(
            'fallback',
            [
                'label' => esc_html__('Fallback', 'shortcuts-hub'),
                'type' => Controls_Manager::TEXT,
            ]
        );
    }

    public function render() {
        $settings = $this->get_settings();
        
        $post_id = get_the_ID();
        $shortcut_id = get_post_meta($post_id, 'sb_id', true);
        
        if ($shortcut_id) {
            $endpoint = "shortcuts/{$shortcut_id}/version/latest";
            $response = sb_api_call($endpoint, 'GET');
            
            if (isset($response['version']['version'])) {
                echo esc_html($response['version']['version']);
                return;
            }
        }
        
        if (!empty($settings['fallback'])) {
            echo esc_html($settings['fallback']);
        }
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
