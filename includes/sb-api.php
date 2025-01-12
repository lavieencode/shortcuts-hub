<?php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Define the base URL for the Switchblade API
// Removed the definition of SB_URL as it's now retrieved from settings

// Include required files
require_once dirname(__FILE__) . '/settings.php';
require_once dirname(__FILE__) . '/auth.php';

function sb_api_call($endpoint, $method = 'GET', $query_params = [], $body_data = null) {
    $settings = get_shortcuts_hub_settings();
    $api_url = rtrim($settings['sb_url'], '/') . '/' . ltrim($endpoint, '/');
    
    if (!empty($query_params)) {
        $api_url .= '?' . http_build_query($query_params);
    }

    // Function to make the actual API request
    $make_request = function($token) use ($api_url, $method, $body_data) {
        $args = array(
            'method' => $method,
            'headers' => array(
                'Authorization' => 'Bearer ' . $token,
                'Content-Type' => 'application/json'
            ),
            'timeout' => 45
        );

        if ($body_data !== null) {
            $args['body'] = json_encode($body_data);
        }

        return wp_remote_request($api_url, $args);
    };

    // Get initial token
    $bearer_token = get_refresh_sb_token();
    if (is_wp_error($bearer_token)) {
        return $bearer_token;
    }

    // Make initial request
    $response = $make_request($bearer_token);
    if (is_wp_error($response)) {
        return $response;
    }

    $response_code = wp_remote_retrieve_response_code($response);
    
    // If unauthorized, try refreshing token and retry request
    if ($response_code === 401) {
        delete_transient('SB_TOKEN');
        $bearer_token = get_refresh_sb_token();
        if (is_wp_error($bearer_token)) {
            return $bearer_token;
        }
        
        // Retry the request with new token
        $response = $make_request($bearer_token);
        if (is_wp_error($response)) {
            return $response;
        }
        $response_code = wp_remote_retrieve_response_code($response);
    }

    if ($response_code !== 200) {
        $body = wp_remote_retrieve_body($response);
        $error_data = json_decode($body, true);
        $error_message = isset($error_data['message']) ? $error_data['message'] : 'API request failed with status ' . $response_code;
        return new WP_Error('api_error', $error_message);
    }

    $body = wp_remote_retrieve_body($response);
    return json_decode($body, true);
}
