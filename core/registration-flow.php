<?php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

// Handle Registration: Process registration and respond to webhook
function handle_registration() {
    check_ajax_referer('shortcuts_hub_nonce', 'security');

    $fields = $_POST['fields'] ?? [];
    $username = isset($fields['username']) ? sanitize_user($fields['username']) : '';
    $email = isset($fields['email']) ? sanitize_email($fields['email']) : '';
    $password = isset($fields['password']) ? $fields['password'] : '';
    $password_confirm = isset($fields['password_confirm']) ? $fields['password_confirm'] : '';
    $sb_id = sanitize_text_field($_POST['sb_id'] ?? '');
    $post_id = intval($_POST['post_id'] ?? 0);

    // Custom validation
    if (empty($username)) {
        wp_send_json_error(['message' => 'Username is required.']);
        return;
    }

    if (empty($email) || !is_email($email)) {
        wp_send_json_error(['message' => 'A valid email is required.']);
        return;
    }

    if (empty($password)) {
        wp_send_json_error(['message' => 'Password is required.']);
        return;
    }

    if ($password !== $password_confirm) {
        wp_send_json_error(['message' => 'Passwords do not match.']);
        return;
    }

    if (username_exists($username)) {
        wp_send_json_error(['message' => 'Username already exists.']);
        return;
    }

    if (email_exists($email)) {
        wp_send_json_error(['message' => 'Email already exists.']);
        return;
    }

    $user_id = wp_create_user($username, $password, $email);
    if (is_wp_error($user_id)) {
        wp_send_json_error(['message' => 'Error creating user: ' . $user_id->get_error_message()]);
        return;
    }

    $user = new WP_User($user_id);
    $user->set_role('shortcuts_user');

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
            }
        } else {
            wp_send_json_error(['message' => 'Failed to fetch the latest version URL: ' . $fetch_response->get_error_message()]);
            return;
        }
    }

    // Send success response with redirect URL
    wp_send_json_success([
        'message' => 'User registered successfully.',
        'redirect_url' => $redirect_url
    ]);
}
add_action('wp_ajax_handle_registration', 'handle_registration');
add_action('wp_ajax_nopriv_handle_registration', 'handle_registration');