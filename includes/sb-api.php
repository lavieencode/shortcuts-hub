<?php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Define the base URL for the Switchblade API
if (!defined('SB_URL')) {
    define('SB_URL', 'https://debotchery-switchblade-bc6fa1ee4e01.herokuapp.com');
}

require_once 'auth.php';

function sb_api_call($endpoint, $method = 'GET', $query_params = array(), $body = null) {
    // Ensure the endpoint is a string
    if (!is_string($endpoint)) {
        return new WP_Error('invalid_endpoint', 'The endpoint must be a string.');
    }

    $api_url = SB_URL . '/' . ltrim($endpoint, '/'); // Ensure there's a single slash

    // Handle query parameters for GET requests
    if (!empty($query_params) && $method === 'GET') {
        // Ensure query_params is an array
        if (!is_array($query_params)) {
            return new WP_Error('invalid_query_params', 'Query parameters must be an array.');
        }
        $api_url .= '?' . http_build_query($query_params);
    }

    $bearer_token = get_refresh_sb_token();
    if (!$bearer_token) {
        error_log('Failed to retrieve SB token.');
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
        return $response;
    }

    $response_code = wp_remote_retrieve_response_code($response);
    $raw_body = wp_remote_retrieve_body($response);

    $decoded_body = json_decode($raw_body, true);

    if (empty($decoded_body) || !is_array($decoded_body)) {
        return new WP_Error('invalid_response', 'Invalid response structure from API.');
    }

    return $decoded_body;
}
