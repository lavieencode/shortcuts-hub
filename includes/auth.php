<?php

if (!defined('ABSPATH')) {
    exit;
}

// Include settings functionality
require_once dirname(__FILE__) . '/settings.php';

function get_refresh_sb_token() {
    // First check if we already have a valid token
    $existing_token = get_transient('SB_TOKEN');
    if ($existing_token) {
        return $existing_token;
    }

    // Check for rate limiting
    $rate_limit = get_transient('SB_RATE_LIMIT');
    if ($rate_limit) {
        return new WP_Error('rate_limit', 'Rate limited. Cannot get new token.');
    }

    // Get settings
    $settings = get_shortcuts_hub_settings();
    if (empty($settings['sb_username']) || empty($settings['sb_password'])) {
        return new WP_Error('missing_credentials', 'Missing Switchblade credentials');
    }

    $api_url = $settings['sb_url'] . '/login';
    
    $request_body = json_encode([
        'username' => $settings['sb_username'],
        'password' => $settings['sb_password']
    ]);

    $response = wp_remote_post($api_url, [
        'body' => $request_body,
        'headers' => [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json'
        ],
        'timeout' => 30,
    ]);

    if (is_wp_error($response)) {
        return new WP_Error('token_request_failed', 'Failed to get token: ' . $response->get_error_message());
    }

    $response_code = wp_remote_retrieve_response_code($response);
    $body = wp_remote_retrieve_body($response);
    
    if ($response_code !== 200) {
        // Handle specific error cases
        if (strpos($body, 'viewAnyDraftShortcut') !== false) {
            delete_transient('SB_TOKEN');
            delete_transient('SB_TOKEN_AGE');
            delete_transient('SB_RATE_LIMIT');
            return new WP_Error('permission_error', 'Permission check failed during login');
        }
        else if ($response_code === 429 || (strpos($body, 'Too many login attempts') !== false)) {
            set_transient('SB_RATE_LIMIT', true, HOUR_IN_SECONDS);
            return new WP_Error('rate_limit', 'Too many login attempts. Please try again later.');
        }
        return new WP_Error('login_failed', 'Login failed with status code: ' . $response_code);
    }

    $data = json_decode($body, true);
    
    if (!empty($data['token'])) {
        set_transient('SB_TOKEN', $data['token'], HOUR_IN_SECONDS);
        set_transient('SB_TOKEN_AGE', time(), HOUR_IN_SECONDS);
        return $data['token'];
    }

    return new WP_Error('no_token', 'No token found in response');
}
