<?php

ob_start(); 

if (!defined('ABSPATH')) {
    exit; 
}

// Add AJAX handler for logging
function shortcuts_hub_handle_log() {
    try {
        // Verify nonce first
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'shortcuts_hub_log')) {
            wp_send_json_error('Invalid nonce', 403);
            return;
        }
        
        $message = isset($_POST['message']) ? sanitize_text_field($_POST['message']) : '';
        $data = isset($_POST['data']) ? sanitize_text_field($_POST['data']) : '';
        
        if (empty($message)) {
            wp_send_json_error('No message provided', 400);
            return;
        }
        
        error_log($message . ($data ? ' ' . $data : ''));
        wp_send_json_success(array('logged' => true));
        
    } catch (Exception $e) {
        wp_send_json_error($e->getMessage(), 500);
    }
}

add_action('wp_ajax_shortcuts_hub_handle_log', 'shortcuts_hub_handle_log');
add_action('wp_ajax_nopriv_shortcuts_hub_handle_log', 'shortcuts_hub_handle_log');

// Helper function to determine if form is registration
function is_registration_form($form_name) {
    return $form_name === 'Shortcuts Gallery Registration';
}

// Handle form validation for both login and registration
function shortcuts_hub_validate_login($record, $ajax_handler) {
    $form_name = $record->get_form_settings('form_name');
    
    // Exit early if it's not one of our forms
    if (!in_array($form_name, ['Shortcuts Gallery Registration', 'Shortcuts Gallery Login'])) {
        return;
    }
    
    // Handle different form types
    if (is_registration_form($form_name)) {
        validate_registration_form($record, $ajax_handler);
    } else {
        validate_login_form($record, $ajax_handler);
    }
}

// Validate login form
function validate_login_form($record, $ajax_handler) {
    $fields = $record->get('fields');
    $form_name = $record->get_form_settings('form_name');
    
    // Check if required fields exist
    if (!isset($fields['login_download_token']) || !isset($fields['login_redirect_url'])) {
        $ajax_handler->add_error_message("Required fields are missing");
        return;
    }
    
    $download_token = $fields['login_download_token']['value'];
    $redirect_url = $fields['login_redirect_url']['value'];
    
    // Store the redirect URL in the session for after login
    if (!empty($redirect_url)) {
        $_SESSION['shortcuts_redirect_after_login'] = $redirect_url;
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

// Validate registration form
function validate_registration_form($record, $ajax_handler) {
    $fields = $record->get('fields');
    $form_name = $record->get_form_settings('form_name');
    
    // Check if required fields exist
    if (!isset($fields['reg_download_token']) || !isset($fields['reg_redirect_url'])) {
        $ajax_handler->add_error_message("Required fields are missing");
        return;
    }
    
    $download_token = $fields['reg_download_token']['value'];
    $redirect_url = $fields['reg_redirect_url']['value'];
    
    // Store the redirect URL in the session for after registration
    if (!empty($redirect_url)) {
        $_SESSION['shortcuts_redirect_after_reg'] = $redirect_url;
        
        // Log the download
        if (function_exists('log_download')) {
            $shortcut_name = basename($redirect_url);
            log_download($shortcut_name, [], $redirect_url);
        }
    }
    
    $fields = $record->get_formatted_data();
    
    // Get registration fields
    $email = isset($fields['email']) ? sanitize_email($fields['email']) : '';
    $username = isset($fields['username']) ? sanitize_user($fields['username']) : '';
    $password = isset($fields['password']) ? $fields['password'] : '';
    $password_confirm = isset($fields['password_confirm']) ? $fields['password_confirm'] : '';

    // Validate required fields
    if (empty($email) || empty($password)) {
        $ajax_handler->add_error_message('Email and password are required.');
        return;
    }

    // Validate email
    if (!is_email($email)) {
        $ajax_handler->add_error_message('Please enter a valid email address.');
        return;
    }

    // Check if email exists
    if (email_exists($email)) {
        $ajax_handler->add_error_message('This email is already registered.');
        return;
    }

    // Check if username exists
    if (!empty($username) && username_exists($username)) {
        $ajax_handler->add_error_message('This username is already taken.');
        return;
    }

    // Validate password confirmation
    if ($password !== $password_confirm) {
        $ajax_handler->add_error_message('Passwords do not match.');
        return;
    }

    // Store validated data for processing
    $record->set_form_settings('validated_data', [
        'email' => $email,
        'username' => $username ?: sanitize_user($email),
        'password' => $password
    ]);
}

// Handle form processing for both login and registration
function shortcuts_hub_process_login($record, $ajax_handler) {
    $form_name = $record->get_form_settings('form_name');
    
    // Get form fields
    $fields = $record->get('fields');
    
    // Determine form type and get appropriate field names
    $is_registration = is_registration_form($form_name);
    $token_field = $is_registration ? 'reg_download_token' : 'login_download_token';
    $redirect_field = $is_registration ? 'reg_redirect_url' : 'login_redirect_url';
    
    // Get download token and redirect URL from form fields
    $download_token = !empty($fields[$token_field]['value']) ? sanitize_text_field($fields[$token_field]['value']) : '';
    $redirect_url = !empty($fields[$redirect_field]['value']) ? esc_url_raw($fields[$redirect_field]['value']) : home_url();

    // Handle login or registration based on form type
    if ($is_registration) {
        process_registration($record, $ajax_handler);
    } else {
        process_login($record, $ajax_handler);
    }
    
    // Prepare response data
    $response_data = [
        'success' => true,
        'redirect_url' => $redirect_url
    ];

    // If we have a download token, process it
    if (!empty($download_token)) {
        // Get the download data from the token
        $download_data = get_transient('sh_download_' . $download_token);
        if ($download_data) {
            $response_data['download_data'] = $download_data;
        }
    }

    // Set the response data
    foreach ($response_data as $key => $value) {
        $ajax_handler->add_response_data($key, $value);
    }
}

// Process login
function process_login($record, $ajax_handler) {
    $user = $record->get_form_settings('validated_user');
    if (!$user) {
        return;
    }

    $fields = $record->get('fields');
    $remember = isset($fields['remember_me']['value']) && $fields['remember_me']['value'] === 'yes';

    // Log the user in
    wp_set_auth_cookie($user->ID, $remember);
    wp_set_current_user($user->ID);
}

// Process registration
function process_registration($record, $ajax_handler) {
    $validated_data = $record->get_form_settings('validated_data');
    if (!$validated_data) {
        return;
    }

    // Create user
    $user_id = wp_create_user(
        $validated_data['username'],
        $validated_data['password'],
        $validated_data['email']
    );

    if (is_wp_error($user_id)) {
        $ajax_handler->add_error_message($user_id->get_error_message());
        return;
    }

    // Set user role
    $user = new WP_User($user_id);
    $user->set_role('shortcuts_user');

    // Log the user in
    wp_set_current_user($user_id);
    wp_set_auth_cookie($user_id, true);
}

// Add hooks
add_action('elementor_pro/forms/validation', 'shortcuts_hub_validate_login', 10, 2);
add_action('elementor_pro/forms/new_record', 'shortcuts_hub_process_login', 10, 2);

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
    
    // Get and validate the data
    $shortcut_data = isset($_POST['shortcut_data']) ? json_decode(stripslashes($_POST['shortcut_data']), true) : null;
    $redirect_url = isset($_POST['redirect_url']) ? esc_url_raw($_POST['redirect_url']) : '';
    
    if (!$shortcut_data) {
        wp_send_json_error('No shortcut data provided');
        return;
    }
    
    // Start session if not already started
    if (!session_id()) {
        session_start();
    }
    
    // Store the data in session
    $_SESSION['shortcuts_hub_pending_download'] = $shortcut_data;
    $_SESSION['shortcuts_hub_redirect_url'] = $redirect_url;
    
    wp_send_json_success(array(
        'message' => 'Data stored successfully',
        'stored_url' => $redirect_url
    ));
}
add_action('wp_ajax_nopriv_shortcuts_hub_store_pending_download', 'shortcuts_hub_store_pending_download');