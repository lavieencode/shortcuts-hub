<?php

ob_start(); 

if (!defined('ABSPATH')) {
    exit; 
}

// Handle login form validation
function shortcuts_hub_validate_login($record, $ajax_handler) {
    $form_name = $record->get_form_settings('form_name');
    if ('Shortcuts Gallery Login' !== $form_name) {
        return;
    }

    $fields = $record->get_formatted_data();
    
    // Get login fields
    $username_email = isset($fields['username_email']) ? sanitize_text_field($fields['username_email']) : '';
    $password = isset($fields['password']) ? $fields['password'] : '';

    // Validate required fields
    if (empty($username_email) || empty($password)) {
        $ajax_handler->add_error_message('Please enter both username/email and password.');
        return;
    }

    // Check if input is email
    if (is_email($username_email)) {
        $user = get_user_by('email', $username_email);
    } else {
        $user = get_user_by('login', $username_email);
    }

    if (!$user) {
        $ajax_handler->add_error_message('Invalid username or email.');
        return;
    }

    // Verify password
    if (!wp_check_password($password, $user->user_pass, $user->ID)) {
        $ajax_handler->add_error_message('Invalid password.');
        return;
    }

    // Store validated user data for processing
    $record->set_form_settings('validated_user', $user);
}

// Handle login form processing
function shortcuts_hub_process_login($record, $ajax_handler) {
    $form_name = $record->get_form_settings('form_name');
    if ('Shortcuts Gallery Login' !== $form_name) {
        return;
    }

    // Get the validated user
    $user = $record->get_form_settings('validated_user');
    if (!$user) {
        return;
    }

    $fields = $record->get_formatted_data();
    $remember = isset($fields['remember_me']) && $fields['remember_me'] === 'yes';

    // Log the user in
    $creds = array(
        'user_login'    => $user->user_login,
        'user_password' => $fields['password'],
        'remember'      => $remember
    );

    $login = wp_signon($creds, false);

    if (is_wp_error($login)) {
        $ajax_handler->add_error_message('Login failed. Please try again.');
        return;
    }

    // Set the user's auth cookie
    wp_set_auth_cookie($user->ID, $remember);

    // Get parameters from URL
    $redirect_url = isset($_GET['redirect_url']) ? urldecode($_GET['redirect_url']) : '';
    $shortcut_data = isset($_GET['shortcut_data']) ? json_decode(urldecode($_GET['shortcut_data']), true) : '';
    
    // Prepare response data
    $response_data = array();
    
    if ($shortcut_data) {
        $response_data['download_data'] = $shortcut_data;
    }
    
    if ($redirect_url) {
        $response_data['redirect_url'] = esc_url_raw($redirect_url);
    } else {
        $response_data['redirect_url'] = home_url();
    }

    $ajax_handler->add_response_data('success', true);
    $ajax_handler->add_response_data('data', $response_data);
}

// Add hooks
add_action('elementor_pro/forms/validation', 'shortcuts_hub_validate_login', 10, 2);
add_action('elementor_pro/forms/new_record', 'shortcuts_hub_process_login', 10, 2);

function shortcuts_hub_handle_login() {
    check_ajax_referer('shortcuts_hub_nonce', 'security');

    $username_email = isset($_POST['username_email']) ? sanitize_text_field($_POST['username_email']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $remember_me = isset($_POST['remember_me']) && $_POST['remember_me'] === 'true';
    $return_url = isset($_POST['return_url']) ? esc_url_raw($_POST['return_url']) : '';
    $download_url = isset($_POST['download_url']) ? esc_url_raw($_POST['download_url']) : '';
    
    // Validate required fields
    if (empty($username_email)) {
        wp_send_json_error(array(
            'message' => 'Please enter your email or username',
            'error_type' => 'empty_username'
        ));
        return;
    }
    
    if (empty($password)) {
        wp_send_json_error(array(
            'message' => 'Please enter your password',
            'error_type' => 'empty_password'
        ));
        return;
    }
    
    // Check if input is email and validate format
    if (is_email($username_email)) {
        if (!filter_var($username_email, FILTER_VALIDATE_EMAIL)) {
            wp_send_json_error(array(
                'message' => 'Invalid email format',
                'error_type' => 'invalid_email'
            ));
            return;
        }
        $user = get_user_by('email', $username_email);
    } else {
        $user = get_user_by('login', $username_email);
    }
    
    if (!$user) {
        wp_send_json_error(array(
            'message' => 'Invalid username or email address',
            'error_type' => 'invalid_username'
        ));
        return;
    }
    
    $credentials = array(
        'user_login' => $user->user_login,
        'user_password' => $password,
        'remember' => $remember_me
    );
    
    $user = wp_signon($credentials);
    
    if (is_wp_error($user)) {
        $error_code = $user->get_error_code();
        
        switch ($error_code) {
            case 'incorrect_password':
                $error_message = 'The password you entered is incorrect';
                $error_type = 'incorrect_password';
                break;
            case 'invalid_username':
                $error_message = 'Invalid username or email address';
                $error_type = 'invalid_username';
                break;
            case 'invalid_email':
                $error_message = 'Invalid email format';
                $error_type = 'invalid_email';
                break;
            default:
                $error_message = $user->get_error_message();
                $error_type = $error_code;
        }
        
        wp_send_json_error(array(
            'message' => $error_message,
            'error_type' => $error_type
        ));
        return;
    }
    
    // Store the URLs for this user
    if (!empty($return_url)) {
        update_user_meta($user->ID, 'shortcuts_hub_return_url', $return_url);
    }
    if (!empty($download_url)) {
        update_user_meta($user->ID, 'shortcuts_hub_download_url', $download_url);
    }
    
    // Use return URL if provided, otherwise fallback to admin URL
    $redirect_url = !empty($return_url) ? $return_url : admin_url();
    
    wp_send_json_success(array(
        'message' => 'Login successful',
        'redirect_url' => $redirect_url,
        'download_url' => $download_url
    ));
}

add_action('wp_ajax_nopriv_shortcuts_hub_login', 'shortcuts_hub_handle_login');
add_action('wp_ajax_shortcuts_hub_login', 'shortcuts_hub_handle_login');

// Handle storing pending downloads
function shortcuts_hub_store_pending_download() {
    check_ajax_referer('shortcuts_hub_ajax', 'security');
    
    error_log('[Login Flow] Starting store_pending_download');
    
    // Get and validate the data
    $shortcut_data = isset($_POST['shortcut_data']) ? json_decode(stripslashes($_POST['shortcut_data']), true) : null;
    $redirect_url = isset($_POST['redirect_url']) ? esc_url_raw($_POST['redirect_url']) : '';
    
    error_log('[Login Flow] Received shortcut data: ' . print_r($shortcut_data, true));
    error_log('[Login Flow] Received redirect URL: ' . $redirect_url);
    
    if (!$shortcut_data) {
        error_log('[Login Flow] Error: No shortcut data provided');
        wp_send_json_error('No shortcut data provided');
        return;
    }
    
    // Start session if not already started
    if (!session_id()) {
        session_start();
        error_log('[Login Flow] Started new session');
    }
    
    // Store the data in session
    $_SESSION['shortcuts_hub_pending_download'] = $shortcut_data;
    $_SESSION['shortcuts_hub_redirect_url'] = $redirect_url;
    
    error_log('[Login Flow] Successfully stored in session:');
    error_log('[Login Flow] - Pending download: ' . print_r($shortcut_data, true));
    error_log('[Login Flow] - Redirect URL: ' . $redirect_url);
    
    wp_send_json_success(array(
        'message' => 'Data stored successfully',
        'stored_url' => $redirect_url
    ));
}
add_action('wp_ajax_nopriv_shortcuts_hub_store_pending_download', 'shortcuts_hub_store_pending_download');

// Handle AJAX logout
function shortcuts_hub_handle_ajax_logout() {
    check_ajax_referer('shortcuts_hub_ajax_logout', 'security');
    
    // Get the redirect URL before logout
    $redirect_url = isset($_POST['redirect_url']) ? esc_url_raw($_POST['redirect_url']) : '';
    
    wp_logout();
    
    if (!empty($redirect_url)) {
        wp_send_json_success(array('redirect_url' => $redirect_url));
    } else {
        wp_send_json_success();
    }
}
add_action('wp_ajax_shortcuts_hub_ajax_logout', 'shortcuts_hub_handle_ajax_logout');