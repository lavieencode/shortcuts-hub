<?php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Define the base URL for the Switchblade API
// Removed the definition of SB_URL as it's now retrieved from settings

// Include required files
require_once dirname(__FILE__) . '/settings.php';
require_once dirname(__FILE__) . '/auth.php';

function sb_api_call($endpoint, $method = 'GET', $query_params = array(), $body = null) {
    // Get settings first
    $settings = get_shortcuts_hub_settings();
    
    // If credentials aren't configured, return early
    if (empty($settings['sb_username']) || empty($settings['sb_password'])) {
        return new WP_Error('credentials_missing', 'API credentials not configured');
    }

    // Ensure the endpoint is a string
    if (!is_string($endpoint)) {
        return new WP_Error('invalid_endpoint', 'The endpoint must be a string.');
    }

    $api_url = $settings['sb_url'] . '/' . ltrim($endpoint, '/'); // Ensure there's a single slash

    // Handle query parameters for GET requests
    if (!empty($query_params) && $method === 'GET') {
        // Ensure query_params is an array
        if (!is_array($query_params)) {
            return new WP_Error('invalid_query_params', 'Query parameters must be an array.');
        }
        $api_url .= '?' . http_build_query($query_params);
    }

    // Check if we're rate limited
    $rate_limit = get_transient('SB_RATE_LIMIT');
    if ($rate_limit) {
        return new WP_Error('rate_limit', 'Rate limited. Please try again later.');
    }

    // Get the token - no retries, just one attempt
    $bearer_token = get_refresh_sb_token();
    if (!$bearer_token) {
        return new WP_Error('token_error', 'Failed to retrieve SB token.');
    }

    $args = array(
        'method'  => $method,
        'headers' => array(
            'Authorization' => 'Bearer ' . $bearer_token,
            'Content-Type'  => 'application/json',
        ),
        'body'    => $body ? json_encode($body, JSON_PRETTY_PRINT) : null,
    );

    $response = wp_remote_request($api_url, $args);

    if (is_wp_error($response)) {
        error_log("API Error: " . $response->get_error_message());
        return $response;
    }

    $response_code = wp_remote_retrieve_response_code($response);
    if ($response_code === 401) {
        // Only try to refresh if we didn't just get a new token
        $token_age = get_transient('SB_TOKEN_AGE');
        if ($token_age) {
            delete_transient('SB_TOKEN');
            delete_transient('SB_TOKEN_AGE');
            $bearer_token = get_refresh_sb_token();
            if ($bearer_token) {
                $args['headers']['Authorization'] = 'Bearer ' . $bearer_token;
                $response = wp_remote_request($api_url, $args);
                if (is_wp_error($response)) {
                    error_log("API Error after token refresh: " . $response->get_error_message());
                    return $response;
                }
            }
        }
    } else if ($response_code === 429) { // Rate limit hit
        // Set a rate limit cooldown for 1 hour
        set_transient('SB_RATE_LIMIT', true, HOUR_IN_SECONDS);
        return new WP_Error('rate_limit', 'Rate limit exceeded. Please try again in an hour.');
    }

    $raw_body = wp_remote_retrieve_body($response);
    // Debug: Log raw API response
    error_log("!! Debug - Raw Switchblade API Response: " . print_r($raw_body, true));
    $decoded_body = json_decode($raw_body, true);

    if (empty($decoded_body) || !is_array($decoded_body)) {
        error_log("Invalid API response: " . $raw_body);
        return new WP_Error('invalid_response', 'Invalid response structure from API.');
    }

    return $decoded_body;
}
