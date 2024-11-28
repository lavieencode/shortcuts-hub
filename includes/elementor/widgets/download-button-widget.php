<?php

namespace ShortcutsHub\Elementor\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;

if (!defined('ABSPATH')) {
    exit;
}

class Download_Button extends Widget_Base {
    public function get_name() {
        return 'shortcuts-download-button';
    }

    public function get_title() {
        return esc_html__('Shortcuts Download Button', 'shortcuts-hub');
    }

    public function get_icon() {
        return 'eicon-download-button';
    }

    public function get_categories() {
        return ['shortcuts-hub'];
    }

    protected function register_controls() {
        $this->start_controls_section(
            'content_section',
            [
                'label' => esc_html__('Content', 'shortcuts-hub'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'button_text',
            [
                'label' => esc_html__('Button Text', 'shortcuts-hub'),
                'type' => Controls_Manager::TEXT,
                'default' => esc_html__('Download Shortcut', 'shortcuts-hub'),
            ]
        );

        $this->end_controls_section();
    }

    protected function render() {
        $settings = $this->get_settings_for_display();
        
        global $post;
        if (!$post || $post->post_type !== 'shortcut') {
            echo '<div class="elementor-alert elementor-alert-warning">';
            echo esc_html__('This widget can only be used on a Shortcut page.', 'shortcuts-hub');
            echo '</div>';
            return;
        }

        echo do_shortcode('[shortcut_download_button text="' . esc_attr($settings['button_text']) . '"]');
    }
}

// Register widget
function register_download_button_widget($widgets_manager) {
    $widgets_manager->register(new Download_Button());
}
add_action('elementor/widgets/register', 'ShortcutsHub\Elementor\Widgets\register_download_button_widget');

function shortcuts_hub_download_button($atts) {
    $atts = shortcode_atts(array(
        'download_url' => '',
        'redirect_url' => get_permalink(),
        'sb_id' => '',
        'post_id' => get_the_ID(),
        'version' => '',
        'class' => '',
        'text' => ''
    ), $atts, 'shortcut_download_button');

    if (empty($atts['download_url'])) {
        return '';
    }

    // Set button text based on login status
    $button_text = !empty($atts['text']) ? $atts['text'] : (is_user_logged_in() ? 'Download' : 'Login to Download');
    
    // Build button classes
    $classes = array('shortcut-download-button');
    if (!empty($atts['class'])) {
        $classes[] = $atts['class'];
    }
    
    // Build button attributes
    $attributes = array(
        'class' => implode(' ', $classes),
        'href' => '#',
        'data-download-url' => esc_url($atts['download_url']),
        'data-redirect-url' => esc_url($atts['redirect_url']),
        'data-sb-id' => esc_attr($atts['sb_id']),
        'data-post-id' => esc_attr($atts['post_id']),
        'data-version' => esc_attr($atts['version'])
    );
    
    // Build HTML attributes string
    $html_attributes = '';
    foreach ($attributes as $key => $value) {
        $html_attributes .= sprintf(' %s="%s"', esc_attr($key), esc_attr($value));
    }
    
    return sprintf('<a%s>%s</a>', $html_attributes, esc_html($button_text));
}
add_shortcode('shortcut_download_button', 'shortcuts_hub_download_button');

// AJAX handler for storing download URLs
function store_download_urls() {
    check_ajax_referer('shortcuts_hub_nonce', '_wpnonce');

    $download_url = sanitize_url($_POST['download_url']);
    $redirect_url = sanitize_url($_POST['redirect_url']);

    // Generate a unique key for this download session
    $session_key = wp_generate_password(32, false);
    
    // Store URLs in transient with 15-minute expiry
    set_transient('sh_download_' . $session_key, array(
        'download_url' => $download_url,
        'redirect_url' => $redirect_url
    ), 15 * MINUTE_IN_SECONDS);

    // Set cookie with session key
    setcookie('sh_download_session', $session_key, time() + (15 * MINUTE_IN_SECONDS), '/');

    wp_send_json_success(array(
        'message' => 'URLs stored successfully',
        'session_key' => $session_key
    ));
}
add_action('wp_ajax_store_download_urls', 'store_download_urls');

// AJAX handler for storing download data
add_action('wp_ajax_log_shortcut_download', 'sh_log_shortcut_download');

function sh_log_shortcut_download() {
    // Ensure user is logged in
    if (!is_user_logged_in()) {
        error_log('[Shortcuts Hub] Download attempt by non-logged-in user');
        wp_send_json_error('User must be logged in to track downloads');
        return;
    }

    // Verify nonce
    if (!check_ajax_referer('shortcuts_hub_nonce', 'security', false)) {
        error_log('[Shortcuts Hub] Download logging failed: Invalid nonce');
        wp_send_json_error('Security check failed');
        return;
    }

    // Ensure the downloads table exists
    ensure_downloads_table_exists();

    $shortcut_id = isset($_POST['shortcut_id']) ? sanitize_text_field($_POST['shortcut_id']) : '';
    $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
    $version_data = isset($_POST['version_data']) ? $_POST['version_data'] : array();
    
    // If version_data is a string (JSON), decode it
    if (is_string($version_data)) {
        $version_data = json_decode(stripslashes($version_data), true);
    }

    if (empty($shortcut_id) || empty($post_id)) {
        error_log('[Shortcuts Hub] Download logging failed: Missing required parameters');
        wp_send_json_error('Missing required parameters');
        return;
    }

    $user_id = get_current_user_id();
    $logged = sh_log_shortcut_download_to_db($shortcut_id, $post_id, $user_id, $version_data);

    if ($logged) {
        wp_send_json_success('Download logged successfully');
    } else {
        error_log('[Shortcuts Hub] Failed to log download to database');
        wp_send_json_error('Failed to log download');
    }
}

function sh_log_shortcut_download_to_db($shortcut_id, $post_id, $user_id, $version_data) {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'shortcutshub_downloads';

    // Get IP address
    $ip_address = $_SERVER['REMOTE_ADDR'];
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip_address = $_SERVER['HTTP_X_FORWARDED_FOR'];
    }

    // Ensure version_data is an array
    if (!is_array($version_data)) {
        $version_data = array();
    }

    $data = array(
        'user_id' => $user_id,
        'shortcut_id' => $shortcut_id,
        'post_id' => $post_id,
        'post_url' => get_permalink($post_id),
        'shortcut_name' => get_the_title($post_id),
        'version' => isset($version_data['version']) ? sanitize_text_field($version_data['version']) : '',
        'version_notes' => isset($version_data['notes']) ? sanitize_text_field($version_data['notes']) : '',
        'minimum_ios' => isset($version_data['minimumiOS']) ? sanitize_text_field($version_data['minimumiOS']) : '',
        'minimum_mac' => isset($version_data['minimumMac']) ? sanitize_text_field($version_data['minimumMac']) : '',
        'download_url' => isset($version_data['url']) ? esc_url_raw($version_data['url']) : '',
        'ip_address' => sanitize_text_field($ip_address),
        'is_required' => isset($version_data['required']) ? (bool)$version_data['required'] : false,
        'download_date' => current_time('mysql')
    );
    
    $format = array(
        '%d', // user_id
        '%s', // shortcut_id
        '%d', // post_id
        '%s', // post_url
        '%s', // shortcut_name
        '%s', // version
        '%s', // version_notes
        '%s', // minimum_ios
        '%s', // minimum_mac
        '%s', // download_url
        '%s', // ip_address
        '%d', // is_required
        '%s'  // download_date
    );

    $result = $wpdb->insert($table_name, $data, $format);

    if ($result === false) {
        error_log('[Shortcuts Hub] Database error while logging download: ' . $wpdb->last_error);
        return false;
    }
    
    return true;
}