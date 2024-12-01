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

    // Get download token and redirect URL from form fields first
    $download_token = '';
    $redirect_url = home_url();

    if (!empty($fields['reg_download_token']['value'])) {
        $download_token = sanitize_text_field($fields['reg_download_token']['value']);
    }

    if (!empty($fields['reg_redirect_url']['value'])) {
        $redirect_url = esc_url_raw($fields['reg_redirect_url']['value']);
    }

    // Prepare the response data
    $response_data = [
        'success' => true,
        'registration_success' => true,
        'redirect_url' => esc_url_raw($redirect_url)
    ];

    // If we have a download token, process it
    if (!empty($download_token)) {
        // Get the download data from the token
        $download_data = get_transient('sh_download_' . $download_token);
        if ($download_data) {
            // Add download data to response
            $response_data['download_data'] = $download_data;
            
            // Clean up the transient
            delete_transient('sh_download_' . $download_token);
        }
    }

    // Set the response data
    foreach ($response_data as $key => $value) {
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
}, 10, 2);