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
function make_sb_api_call($endpoint, $query_params = array()) {
    // Check if SB_URL is defined
    if (!defined('SB_URL')) {
        error_log('Error: SB_URL is not defined');
        return new WP_Error('missing_url', 'API base URL is not defined.');
    }

    // Construct the full API URL
    $api_url = SB_URL . $endpoint;

    // Append query parameters if available
    if (!empty($query_params)) {
        $api_url .= '?' . http_build_query($query_params);
    }

    // Log the constructed API URL
    error_log('Constructed API URL: ' . $api_url);

    // Get the bearer token
    $bearer_token = get_refresh_sb_token();

    // Log the bearer token
    error_log('Bearer Token Used: ' . $bearer_token);

    // Make the API request
    $response = wp_remote_get($api_url, array(
        'headers' => array(
            'Authorization' => 'Bearer ' . $bearer_token,
        ),
    ));

    // Log the full response for debugging
    error_log('Full API Response: ' . print_r($response, true));

    // Handle potential errors
    if (is_wp_error($response)) {
        error_log('Error Message from API Request: ' . $response->get_error_message());
        return $response;
    }

    // Retrieve and decode the body of the response
    $body = wp_remote_retrieve_body($response);
    $decoded_body = json_decode($body, true);

    // Log the raw and decoded response body for debugging
    error_log('Raw API Response Body: ' . $body);
    error_log('Decoded API Response: ' . print_r($decoded_body, true));

    return $decoded_body;
}