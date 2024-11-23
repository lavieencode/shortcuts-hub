<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

add_action('elementor_pro/forms/process', function($record, $ajax_handler) {
    $form_name = $record->get_form_settings('form_name');
    if ('Shortcuts Gallery Registration' !== $form_name) {
        return;
    }

    $fields = $record->get('fields');
    
    // Get form fields with proper validation
    $email = !empty($fields['email']['value']) ? sanitize_email($fields['email']['value']) : '';
    $password = !empty($fields['password']['value']) ? $fields['password']['value'] : '';
    $username = !empty($fields['username']['value']) ? sanitize_user($fields['username']['value']) : sanitize_user($email);

    if (empty($email) || empty($password)) {
        $ajax_handler->add_error('form_fields', 'Email and password are required.');
        return;
    }

    // Create user
    $user_id = wp_create_user($username, $password, $email);
    if (is_wp_error($user_id)) {
        $ajax_handler->add_error('form_fields', $user_id->get_error_message());
        return;
    }

    // Set user role and log them in
    $user = new WP_User($user_id);
    $user->set_role('shortcuts_user');
    wp_set_current_user($user_id);
    wp_set_auth_cookie($user_id, true);

    // Get button data from cookie
    $button_data = isset($_COOKIE['shortcuts_hub_button_data']) ? stripslashes($_COOKIE['shortcuts_hub_button_data']) : '';
    $button_data = json_decode($button_data, true);

    error_log('Button data from cookie: ' . print_r($button_data, true));

    // Prepare the response data
    $response_data = [
        'success' => true,
        'registration_success' => true,
        'redirect_url' => !empty($button_data['redirect_url']) ? $button_data['redirect_url'] : home_url()
    ];

    if (!empty($button_data['shortcut_id'])) {
        $response_data['shortcut_id'] = $button_data['shortcut_id'];
        error_log('Registration: Setting shortcut_id for download: ' . $button_data['shortcut_id']);
    } else {
        error_log('Registration: No shortcut_id found in button data');
    }

    // Set the response data
    foreach ($response_data as $key => $value) {
        error_log('Setting response data: ' . $key . ' = ' . print_r($value, true));
        $ajax_handler->add_response_data($key, $value);
    }

    $ajax_handler->set_success(true);
}, 10, 2);

// Add form_submitted hook to ensure JavaScript event is triggered
add_action('elementor_pro/forms/form_submitted', function($record, $ajax_handler) {
    $form_name = $record->get_form_settings('form_name');
    if ('Shortcuts Gallery Registration' !== $form_name) {
        return;
    }
    
    error_log('Form submitted hook triggered for registration form');
}, 10, 2);