<?php

if (!defined('ABSPATH')) {
    exit;
}

if (!defined('SB_URL') || !defined('SB_USERNAME') || !defined('SB_PASSWORD')) {
    error_log('Switchblade API credentials are not defined.', 0);
    return new WP_Error('missing_credentials', 'API credentials are not defined.');
}

function get_refresh_sb_token() {
    $cached_token = get_transient('SB_TOKEN');
    if ($cached_token) {
        return $cached_token;
    }
    
    $api_url = SB_URL . '/login';

    $request_body = json_encode([
        'username' => SB_USERNAME,
        'password' => SB_PASSWORD
    ]);

    $response = wp_remote_post($api_url, [
        'body' => $request_body,
        'headers' => [
            'Content-Type' => 'application/json',
        ],
        'timeout' => 15,
    ]);

    if (is_wp_error($response)) {
        error_log('Error during login request: ' . $response->get_error_message(), 0);
        return false;
    }

    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);

    if (!empty($data['token'])) {
        set_transient('SB_TOKEN', $data['token'], HOUR_IN_SECONDS);
        return $data['token'];
    }

    error_log('Failed to retrieve token. Response: ' . $body, 0);
    return false;
}

