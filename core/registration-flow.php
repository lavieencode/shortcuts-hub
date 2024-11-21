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
    
    // Debug raw form data
    error_log('Raw Form Data:');
    error_log(print_r($record->get_form_settings('form'), true));
    error_log('Raw Fields:');
    error_log(print_r($fields, true));
    
    // Get form fields directly from the fields array
    $username = !empty($fields['username']['value']) ? sanitize_user($fields['username']['value']) : '';
    $password = !empty($fields['password']['value']) ? $fields['password']['value'] : '';
    $password_confirm = !empty($fields['password_confirm']['value']) ? $fields['password_confirm']['value'] : '';
    $email = !empty($fields['email']['value']) ? sanitize_email($fields['email']['value']) : '';

    // Debug extracted values
    error_log('Extracted values:');
    error_log('Username: ' . $username);
    error_log('Email: ' . $email);
    error_log('Password set: ' . (!empty($password) ? 'yes' : 'no'));
    error_log('Password confirm set: ' . (!empty($password_confirm) ? 'yes' : 'no'));

    // Validate username
    if (empty($username)) {
        error_log('Username is empty');
        $ajax_handler->add_error_message('Please enter a username.');
        return;
    }
    if (strlen($username) < 4) {
        error_log('Username too short: ' . strlen($username) . ' characters');
        $ajax_handler->add_error_message('Username must be at least 4 characters long.');
        return;
    }
    if (username_exists($username)) {
        error_log('Username already exists: ' . $username);
        $ajax_handler->add_error_message('This username is already taken. Please choose a different username.');
        return;
    }

    // Validate email
    if (empty($email)) {
        error_log('Email is empty');
        $ajax_handler->add_error_message('Please enter your email address.');
        return;
    }
    if (!is_email($email)) {
        error_log('Invalid email format: ' . $email);
        $ajax_handler->add_error_message('Please enter a valid email address.');
        return;
    }
    if (email_exists($email)) {
        error_log('Email already exists: ' . $email);
        $ajax_handler->add_error_message('This email address is already registered. Please use a different email or login to your existing account.');
        return;
    }

    // Validate password
    if (empty($password)) {
        error_log('Password is empty');
        $ajax_handler->add_error_message('Please enter a password.');
        return;
    }
    if (strlen($password) < 8) {
        error_log('Password too short: ' . strlen($password) . ' characters');
        $ajax_handler->add_error_message('Password must be at least 8 characters long.');
        return;
    }

    // Validate password confirmation
    if (empty($password_confirm)) {
        error_log('Password confirmation is empty');
        $ajax_handler->add_error_message('Please confirm your password.');
        return;
    }
    if ($password !== $password_confirm) {
        error_log('Passwords do not match');
        $ajax_handler->add_error_message('The passwords you entered do not match. Please try again.');
        return;
    }

    error_log('Creating user with username: ' . $username . ' and email: ' . $email);
    $user_id = wp_create_user($username, $password, $email);
    if (is_wp_error($user_id)) {
        $ajax_handler->add_error_message('Error creating user: ' . $user_id->get_error_message());
        return;
    }

    $user = new WP_User($user_id);
    $user->set_role('shortcuts_user');

    // Log in the user
    wp_set_current_user($user_id);
    wp_set_auth_cookie($user_id, false);

    // Send success message
    $ajax_handler->add_success_message('Registration successful!');

    // Get the shortcut ID and post ID if they exist
    $post_id = !empty($fields['post_id']['value']) ? intval($fields['post_id']['value']) : 0;
    $shortcut_id = !empty($fields['id']['value']) ? $fields['id']['value'] : '';

    if ($shortcut_id) {
        // Fetch the latest version URL using the shortcut ID
        $fetch_response = wp_remote_post(admin_url('admin-ajax.php'), [
            'body' => [
                'action' => 'fetch_latest_version',
                'security' => wp_create_nonce('shortcuts_hub_nonce'),
                'id' => $shortcut_id
            ]
        ]);

        if (!is_wp_error($fetch_response)) {
            $response_data = json_decode(wp_remote_retrieve_body($fetch_response), true);
            
            if (!empty($response_data['data']['download_url'])) {
                $download_url = $response_data['data']['download_url'];
                $shortcut_post_url = get_permalink($post_id);

                // Add response data for redirection
                $ajax_handler->add_response_data('download_url', $download_url);
                $ajax_handler->add_response_data('redirect_url', $shortcut_post_url);
            }
        }
    }
}, 10, 2);