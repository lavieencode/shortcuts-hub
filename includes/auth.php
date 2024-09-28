<?php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Function to get or refresh the SB token
 * It first checks if the token exists in the transient, and if not, it requests a new one.
 *
 * @return string|false The token if successful, false otherwise.
 */
function get_refresh_sb_token() {
    // Check if a valid token is stored in the transient
    $cached_token = get_transient('SB_TOKEN');
    
    if ($cached_token) {
        // Token exists and is valid, return it
        return $cached_token;
    }

    // If no valid token, make an API request to fetch a new one
    $api_url = SB_URL . '/login'; // SB_URL is set in wp-config.php
    $response = wp_remote_post($api_url, [
        'body' => json_encode([
            'username' => SB_USERNAME,  // SB_USERNAME is set in wp-config.php
            'password' => SB_PASSWORD   // SB_PASSWORD is set in wp-config.php
        ]),
        'headers' => [
            'Content-Type' => 'application/json',
        ],
    ]);

    // Handle errors in the response
    if (is_wp_error($response)) {
        error_log('Error fetching SB token: ' . $response->get_error_message());
        return false;
    }

    // Parse the response body
    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);

    // Check if a token is returned and store it in the transient
    if (!empty($data['token'])) {
        // Store the token in a transient with a 1-hour expiration
        set_transient('SB_TOKEN', $data['token'], HOUR_IN_SECONDS);
        
        // Log for debugging
        error_log('New SB token fetched and stored: ' . $data['token']);
        
        return $data['token'];  // Return the new token
    } else {
        error_log('Failed to retrieve SB token: ' . print_r($data, true));
        return false;
    }
}

/**
 * Function to make a SwitchBlade API call
 *
 * @param string $endpoint - API endpoint path (e.g., '/shortcuts')
 * @param array $query_params - Optional query parameters
 * @return mixed - Decoded response body or WP_Error on failure
 */
function make_sb_api_call($endpoint, $method = 'GET', $query_params = array(), $body = null) {
    // Check if SB_URL is defined
    if (!defined('SB_URL')) {
        error_log('Error: SB_URL is not defined');
        return new WP_Error('missing_url', 'API base URL is not defined.');
    }

    // Construct the full API URL
    $api_url = SB_URL . $endpoint;

    // Append query parameters for GET requests
    if (!empty($query_params) && $method === 'GET') {
        $api_url .= '?' . http_build_query($query_params);
    }

    // Log the API URL
    error_log('Constructed API URL: ' . $api_url);

    // Get the bearer token
    $bearer_token = get_refresh_sb_token();
    if (is_wp_error($bearer_token)) {
        error_log('Error fetching bearer token: ' . $bearer_token->get_error_message());
        return $bearer_token;
    }

    // Log the bearer token (masked for security reasons)
    error_log('Bearer Token Used (masked): ' . substr($bearer_token, 0, 6) . '********');

    // Set up the request arguments
    $args = array(
        'method'  => $method,
        'headers' => array(
            'Authorization' => 'Bearer ' . $bearer_token,
            'Content-Type'  => 'application/json',
        ),
        'timeout' => 15, // Set a timeout for the request
    );

    // Log the request method and body
    error_log('Request Method: ' . $method);

    // Include the request body for PATCH or POST
    if ($method !== 'GET' && !empty($body)) {
        $args['body'] = json_encode($body);
        error_log('Request Body: ' . print_r($args['body'], true));
    }

    // Perform the API request
    $response = wp_remote_request($api_url, $args);

    // Log any errors
    if (is_wp_error($response)) {
        error_log('Error Message from API Request: ' . $response->get_error_message());
        return $response;
    }

    // Retrieve and log the raw response body
    $body = wp_remote_retrieve_body($response);
    error_log('Raw API Response Body: ' . $body);

    // Decode the response body and log it
    $decoded_body = json_decode($body, true);
    error_log('Decoded API Response: ' . print_r($decoded_body, true));

    return $decoded_body;
}