<?php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

// Process form submission and create user
add_action('elementor_pro/forms/process', function($record, $ajax_handler) {
    $form_name = $record->get_form_settings('form_name');
    if ('Shortcuts Gallery Registration' !== $form_name) {
        return;
    }

    $fields = $record->get('fields');
    $username = sanitize_user($fields['username']['value']);
    $email = sanitize_email($fields['email']['value']);
    $password = $fields['password']['value'];
    $password_confirm = $fields['password_confirm']['value'];
    $sb_id = sanitize_text_field($fields['sb_id']['value'] ?? '');
    $post_id = intval($fields['post_id']['value'] ?? 0);

    // Custom validation
    if (empty($username)) {
        $ajax_handler->add_error_message('Username is required.');
        return;
    }

    if (empty($email) || !is_email($email)) {
        $ajax_handler->add_error_message('A valid email is required.');
        return;
    }

    if (empty($password)) {
        $ajax_handler->add_error_message('Password is required.');
        return;
    }

    if ($password !== $password_confirm) {
        $ajax_handler->add_error_message('Passwords do not match.');
        return;
    }

    if (username_exists($username)) {
        $ajax_handler->add_error_message('Username already exists.');
        return;
    }

    if (email_exists($email)) {
        $ajax_handler->add_error_message('Email already exists.');
        return;
    }

    $user_id = wp_create_user($username, $password, $email);
    if (is_wp_error($user_id)) {
        $ajax_handler->add_error_message('Error creating user: ' . $user_id->get_error_message());
        return;
    }

    $user = new WP_User($user_id);
    $user->set_role('shortcuts_user');

    // Log in the user
    $creds = [
        'user_login'    => $username,
        'user_password' => $password,
        'remember'      => true
    ];

    $signon = wp_signon($creds, false);
    if (is_wp_error($signon)) {
        $ajax_handler->add_error_message('Error logging in: ' . $signon->get_error_message());
        return;
    }

    // Determine the redirect URL
    $redirect_url = home_url('/my-account'); // Default redirect to My Account page

    if (!empty($sb_id)) {
        // Fetch the latest version URL if sb_id is present
        $fetch_response = wp_remote_post(admin_url('admin-ajax.php'), [
            'body' => [
                'action' => 'fetch_latest_version',
                'security' => wp_create_nonce('shortcuts_hub_nonce'),
                'shortcut_id' => $sb_id
            ]
        ]);

        if (!is_wp_error($fetch_response)) {
            $response_body = wp_remote_retrieve_body($fetch_response);
            $response_data = json_decode($response_body, true);

            if (is_array($response_data) && isset($response_data['version']['url'])) {
                $redirect_url = $response_data['version']['url'];
            } else {
                $ajax_handler->add_error_message('Failed to retrieve the latest version URL.');
                return;
            }
        } else {
            $ajax_handler->add_error_message('Failed to fetch the latest version URL: ' . $fetch_response->get_error_message());
            return;
        }
    }

    // Send success message with redirect URL
    $ajax_handler->add_success_message('User registered and logged in successfully.');
    $ajax_handler->add_response_data('redirect_url', $redirect_url);
}, 10, 2);