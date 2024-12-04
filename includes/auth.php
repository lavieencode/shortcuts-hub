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
        error_log('Rate limited. Cannot get new token.');
        return false;
    }

    // Get settings
    $settings = get_shortcuts_hub_settings();
    if (empty($settings['sb_username']) || empty($settings['sb_password'])) {
        return false;
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
        error_log('Error getting SB token: ' . $response->get_error_message());
        return false;
    }

    $response_code = wp_remote_retrieve_response_code($response);
    $body = wp_remote_retrieve_body($response);
    error_log('Login response code: ' . $response_code);
    error_log('Login response body: ' . $body);

    if ($response_code !== 200) {
        // Handle specific error cases
        if (strpos($body, 'viewAnyDraftShortcut') !== false) {
            error_log('Permission check error during login. Clearing token cache.');
            delete_transient('SB_TOKEN');
            delete_transient('SB_TOKEN_AGE');
            delete_transient('SB_RATE_LIMIT');
        }
        else if ($response_code === 429 || (strpos($body, 'Too many login attempts') !== false)) {
            set_transient('SB_RATE_LIMIT', true, HOUR_IN_SECONDS);
            error_log('Rate limit hit. Cooling down for an hour.');
        }
        return false;
    }

    $data = json_decode($body, true);
    
    if (!empty($data['token'])) {
        set_transient('SB_TOKEN', $data['token'], HOUR_IN_SECONDS);
        set_transient('SB_TOKEN_AGE', time(), HOUR_IN_SECONDS); // Track when we got the token
        return $data['token'];
    }

    error_log('No token found in response');
    return false;
}
