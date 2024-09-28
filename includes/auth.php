<?php
/**
 * Auth file for managing Switchblade API authentication
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

function initialize_sb_token() {
    if (!get_transient('SB_TOKEN')) {
        return get_sb_token();
    }
    return get_transient('SB_TOKEN');
}

add_action('init', 'initialize_sb_token');

// Function to request and store the Switchblade token
function get_sb_token() {
    error_log('get_sb_token function triggered.');
    // Retrieve the API credentials from the WordPress options
    $API_USERNAME = get_option('SB_USERNAME');
    $API_PASSWORD = get_option('SB_PASSWORD');
    $API_URL = get_option('SB_URL') . '/login';  // Adjust to point to the login endpoint

    // Make the request to the Switchblade login endpoint to get the token
    $response = wp_remote_post($API_URL, [
        'body' => json_encode([
            'username' => $API_USERNAME,
            'password' => $API_PASSWORD
        ]),
        'headers' => [
            'Content-Type' => 'application/json',
        ],
    ]);

    // Handle errors if the response failed
    if (is_wp_error($response)) {
        error_log('Error fetching SB token: ' . $response->get_error_message());
        return null;  // Return null if there was an error
    }

    // Parse the response body
    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);

    // If a token is returned, store it as a transient
    if (!empty($data['token'])) {
        // Store the token with an expiration time (1 hour)
        set_transient('SB_TOKEN', $data['token'], HOUR_IN_SECONDS);
        return $data['token'];  // Return the token
    }

    // Handle case where no token was returned
    error_log('No token received from SB API.');
    return null;
}

// Function to get the stored token or request a new one if expired
function get_sb_token_from_storage() {
    // Attempt to retrieve the stored token and expiration time from the database
    $stored_token_data = get_option('SB_TOKEN');
    
    // Check if data exists and is in the expected format
    if (!$stored_token_data || !isset($stored_token_data['token']) || !isset($stored_token_data['expires_at'])) {
        error_log('SB token missing or malformed in database.');
        return false;
    }

    // Check if the token is still valid based on the expiration time
    $current_time = time();
    if ($stored_token_data['expires_at'] <= $current_time) {
        error_log('SB token has expired. A new token will be requested.');
        return false;
    }

    // Token is valid, return the stored token
    return $stored_token_data['token'];
    error_log('Token retrieved: ' . $bearer_token);
    error_log('API URL: ' . $api_url);
    error_log('API Response: ' . print_r($response, true));
}

// Function to refresh the SB token
function refresh_sb_token() {
    // Make the API request to get a new token
    $response = wp_remote_post(SB_URL . '/login', array(
        'body' => json_encode(array(
            'username' => SB_USERNAME,
            'password' => SB_PASSWORD
        )),
        'headers' => array('Content-Type' => 'application/json')
    ));

    if (is_wp_error($response)) {
        error_log('Error refreshing SB token: ' . $response->get_error_message());
        return ''; // Handle error appropriately
    }

    $body = json_decode(wp_remote_retrieve_body($response), true);

    if (isset($body['token'])) {
        $new_token = $body['token'];
        $expiration = time() + 3600; // Token expires in 1 hour

        // Store the new token and expiration time in WordPress options
        update_option('SB_TOKEN', array(
            'token' => $new_token,
            'expires_at' => $expiration
        ));

        error_log('Switchblade token refreshed successfully.');
        return $new_token;
    }

    error_log('Failed to refresh SB token.');
    return ''; // Handle error appropriately
}

// Example function to use the token in an API call
function make_sb_api_call($endpoint, $params = []) {
    $bearer_token = get_sb_token_from_storage();

    if (!$token) {
        error_log('No valid token found');
        return new WP_Error('no_token', 'Unable to retrieve SB token.');
    }

    $api_url = get_option('SB_URL') . $endpoint;

    $response = wp_remote_get($api_url, [
        'headers' => [
            'Authorization' => 'Bearer ' . $bearer_token,
        ],
        'body' => $params,
    ]);

    // Log the entire response for debugging
    error_log('API URL: ' . $api_url);
    error_log('Response: ' . print_r($response, true));

    if (is_wp_error($response)) {
        error_log('API Error: ' . $response->get_error_message());
        return new WP_Error('api_error', 'Error in API request: ' . $response->get_error_message());
    }

    // Log the response body for further checks
    $response_body = wp_remote_retrieve_body($response);
    error_log('Response Body: ' . $response_body);

    return json_decode($response_body, true);
}

// Schedule the cron event when the plugin is activated
function sb_schedule_token_refresh() {
    if (!wp_next_scheduled('sb_refresh_token_event')) {
        wp_schedule_event(time(), 'hourly', 'sb_refresh_token_event');
    }
}
register_activation_hook(__FILE__, 'sb_schedule_token_refresh');

// Clear the cron event when the plugin is deactivated
function sb_clear_token_refresh_schedule() {
    $timestamp = wp_next_scheduled('sb_refresh_token_event');
    if ($timestamp) {
        wp_unschedule_event($timestamp, 'sb_refresh_token_event');
    }
}
register_deactivation_hook(__FILE__, 'sb_clear_token_refresh_schedule');

// Define the action to refresh the token
add_action('sb_refresh_token_event', 'refresh_sb_token');