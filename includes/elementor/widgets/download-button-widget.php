<?php
namespace ShortcutsHub\Elementor\Widgets;

if (!defined('ABSPATH')) {
    exit;
}

// Check if Elementor is loaded
if (!did_action('elementor/loaded')) {
    error_log('Elementor not loaded when trying to register Download_Button widget');
    return;
}

use \Elementor\Widget_Base;
use \Elementor\Controls_Manager;

class Download_Button_Widget extends Widget_Base {
    public function get_name() {
        return 'shortcuts-hub-download-button';
    }

    public function get_title() {
        return esc_html__('Download Button', 'shortcuts-hub');
    }

    public function get_icon() {
        return 'eicon-download-button';
    }

    public function get_categories() {
        return ['shortcuts-hub'];
    }

    public function get_script_depends() {
        return ['shortcuts-hub-download-button'];
    }

    protected function register_controls() {
        $this->start_controls_section(
            'section_button',
            [
                'label' => esc_html__('Button', 'shortcuts-hub'),
            ]
        );

        $this->add_control(
            'button_type',
            [
                'label' => esc_html__('Type', 'shortcuts-hub'),
                'type' => Controls_Manager::SELECT,
                'default' => 'info',
                'options' => [
                    'info' => esc_html__('Info', 'shortcuts-hub'),
                    'success' => esc_html__('Success', 'shortcuts-hub'),
                    'warning' => esc_html__('Warning', 'shortcuts-hub'),
                    'danger' => esc_html__('Danger', 'shortcuts-hub'),
                ],
            ]
        );

        $this->add_control(
            'text',
            [
                'label' => esc_html__('Text', 'shortcuts-hub'),
                'type' => Controls_Manager::TEXT,
                'default' => esc_html__('Download', 'shortcuts-hub'),
                'placeholder' => esc_html__('Download', 'shortcuts-hub'),
            ]
        );

        $this->end_controls_section();

        $this->start_controls_section(
            'section_download_settings',
            [
                'label' => esc_html__('Download Settings', 'shortcuts-hub'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );

        // Add your custom download controls here

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

        $download_url = get_post_meta($post->ID, '_shortcut_download_url', true);
        $version = get_post_meta($post->ID, '_shortcut_version', true);
        
        // Set default text based on login status if not set
        if (empty($settings['text'])) {
            $settings['text'] = is_user_logged_in() ? 
                esc_html__('Download', 'shortcuts-hub') : 
                esc_html__('Login to Download', 'shortcuts-hub');
        }

        // Add download-specific attributes
        $this->add_render_attribute('button', is_user_logged_in() ? [
            'data-download-url' => esc_url($download_url),
            'data-post-id' => $post->ID,
            'data-version' => esc_attr($version),
            'href' => '#',
        ] : [
            'href' => site_url('/shortcuts-gallery/login/'),
        ]);

        $this->add_render_attribute('button', 'class', [
            'elementor-button',
            'elementor-button-' . $settings['button_type'],
            'shortcuts-hub-download-button'
        ]);
        
        ?>
        <button <?php echo $this->get_render_attribute_string('button'); ?>>
            <span class="elementor-button-content-wrapper">
                <span class="elementor-button-icon">
                    <i class="eicon-download" aria-hidden="true"></i>
                </span>
                <span class="elementor-button-text"><?php echo esc_html($settings['text']); ?></span>
            </span>
        </button>
        <?php
    }
}

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

    // Get the post ID if not provided
    $post_id = empty($atts['post_id']) ? get_the_ID() : $atts['post_id'];
    
    // Get the download URL if not provided
    if (empty($atts['download_url'])) {
        $atts['download_url'] = get_post_meta($post_id, '_shortcut_download_url', true);
    }

    // Get the version if not provided
    if (empty($atts['version'])) {
        $atts['version'] = get_post_meta($post_id, '_shortcut_version', true);
    }

    // Set button text based on login status
    $button_text = !empty($atts['text']) ? $atts['text'] : (is_user_logged_in() ? esc_html__('Download', 'shortcuts-hub') : esc_html__('Login to Download', 'shortcuts-hub'));
    
    // Build button classes
    $classes = array('shortcut-download-button');
    if (!empty($atts['class'])) {
        $classes[] = $atts['class'];
    }
    
    // If user is not logged in, store the button data in a cookie for later use
    if (!is_user_logged_in()) {
        $button_data = array(
            'redirect_url' => $atts['redirect_url'],
            'shortcut_id' => $post_id,
            'version' => $atts['version']
        );
        setcookie('shortcuts_hub_button_data', json_encode($button_data), time() + 3600, '/');
        
        // Return login button that redirects to login page
        $login_url = site_url('/shortcuts-gallery/login/');
        return sprintf(
            '<a href="%s" class="%s">%s</a>',
            esc_url($login_url),
            esc_attr(implode(' ', $classes)),
            esc_html($button_text)
        );
    }

    // For logged-in users, create the download button
    $attributes = array(
        'class' => implode(' ', $classes),
        'href' => '#',
        'data-download-url' => esc_url($atts['download_url']),
        'data-redirect-url' => esc_url($atts['redirect_url']),
        'data-post-id' => esc_attr($post_id),
        'data-version' => esc_attr($atts['version'])
    );

    // Build HTML attributes string
    $html_attributes = '';
    foreach ($attributes as $key => $value) {
        $html_attributes .= sprintf(' %s="%s"', $key, $value);
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

// Remove the old registration hook as it's now handled by the Elementor Manager
remove_action('elementor/widgets/register', 'ShortcutsHub\Elementor\Widgets\register_download_button_widget');