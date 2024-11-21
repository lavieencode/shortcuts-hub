<?php

ob_start(); 

if (!defined('ABSPATH')) {
    exit; 
}

// Custom login validation for Elementor Pro Forms
add_action('elementor_pro/forms/process', function($record, $handler) {
    // Ensure this is the login form
    $form_name = $record->get_form_settings('form_name');
    if ('Shortcuts Gallery Login' !== $form_name) {
        return;
    }

    // Get form fields
    $fields = $record->get('fields');
    $username_email = sanitize_user($fields['username_email']['value']);
    $password = $fields['password']['value'];

    // Validate username/email field
    if (empty($username_email)) {
        $handler->add_error_message('Username or email is required.');
        return;
    }

    // Validate password field
    if (empty($password)) {
        $handler->add_error_message('Password is required.');
        return;
    }

    // Attempt to authenticate the user
    $user = wp_authenticate($username_email, $password);
    if (is_wp_error($user)) {
        $handler->add_error_message('Invalid login credentials.');
        return;
    }

    // Set current user and authentication cookie
    wp_set_current_user($user->ID);
    wp_set_auth_cookie($user->ID, false);

    // Retrieve the download URL from transients
    $user_id = get_current_user_id();
    $download_url = get_transient('download_url_' . $user_id);
    if (!$download_url) {
        $handler->add_error_message('Download URL is missing.');
        return;
    }

    // Retrieve the shortcut post URL from transients
    $post_id = get_transient('download_post_id_' . $user_id);
    if (!$post_id) {
        $handler->add_error_message('Shortcut post URL is missing.');
        return;
    }
    $shortcut_post_url = get_permalink($post_id);

    // Get the referrer URL (where user clicked the download button)
    $referrer = wp_get_referer();
    
    // Prepare the response data
    $response_data = [
        'login_status' => 'success',
        'redirect_url' => $referrer ?: home_url(),
        'download_url' => $download_url,
        'open_in_new_tab' => true
    ];

    // Add JavaScript to handle redirection and new tab
    add_action('wp_footer', function() use ($response_data) {
        ?>
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Open download URL in new tab
            window.open('<?php echo esc_js($response_data['download_url']); ?>', '_blank');
            
            // Redirect current page back to referrer
            window.location.href = '<?php echo esc_js($response_data['redirect_url']); ?>';
        });
        </script>
        <?php
    });

    // Send JSON response
    wp_send_json_success(['message' => 'Login successful! Redirecting...', 'data' => $response_data]);

}, 10, 2);


function handle_ajax_login() {
    // Ensure this is a POST request
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        wp_send_json_error(['message' => 'Invalid request method.']);
        return;
    }

    // Check for required fields
    if (empty($_POST['username_email']) || empty($_POST['password'])) {
        wp_send_json_error(['message' => 'Username/email and password are required.']);
        return;
    }

    $username_email = sanitize_user($_POST['username_email']);
    $password = $_POST['password'];

    // Check if 'remember me' is selected
    $remember = isset($_POST['remember_me']) ? true : false;

    // Attempt to authenticate the user
    $user = wp_authenticate($username_email, $password);
    if (is_wp_error($user)) {
        wp_send_json_error(['message' => 'Invalid login credentials.']);
        return;
    }

    // Set current user and authentication cookie
    wp_set_current_user($user->ID);
    wp_set_auth_cookie($user->ID, $remember);

    // Retrieve the download URL from transients
    $user_id = get_current_user_id();
    $download_url = get_transient('download_url_' . $user_id);
    if (!$download_url) {
        wp_send_json_error(['message' => 'Download URL is missing.']);
        return;
    }

    // Send success response
    wp_send_json_success(['download_url' => $download_url, 'post_url' => home_url()]);
}

// Handle AJAX login request
add_action('wp_ajax_nopriv_elementor_pro_forms_ajax_handler', 'handle_ajax_login');
add_action('wp_ajax_elementor_pro_forms_ajax_handler', 'handle_ajax_login');