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
 * Example function to make an authenticated API call using the SB token.
 * This is where we inject the token into the API request.
 *
 * @param string $endpoint The API endpoint to call.
 * @param array $params The parameters to send in the API request.
 * @return array|false The API response or false on failure.
 */
function make_sb_api_call($endpoint, $params = []) {
    // Get or refresh the token
    $token = get_refresh_sb_token();

    if (!$token) {
        error_log('Unable to retrieve a valid SB token for API call.');
        return false; // Fail if no token
    }

    // Construct the API URL
    $api_url = SB_URL . $endpoint;

    // Make the API request with the token
    $response = wp_remote_get($api_url, [
        'headers' => [
            'Authorization' => 'Bearer ' . $token,
        ],
        'body' => $params,
    ]);

    // Handle any errors in the response
    if (is_wp_error($response)) {
        error_log('API call failed: ' . $response->get_error_message());
        return false;
    }

    // Return the decoded response body
    return json_decode(wp_remote_retrieve_body($response), true);
}