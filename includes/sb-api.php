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

    $api_url = rtrim($settings['sb_url'], '/') . '/' . ltrim($endpoint, '/');

    // Handle query parameters for GET requests
    if (!empty($query_params) && $method === 'GET') {
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

    // Try to get existing token first
    $bearer_token = get_transient('SB_TOKEN');
    if (!$bearer_token) {
        sh_debug_log('No existing token found, getting new one');
        $bearer_token = get_refresh_sb_token();
        if (!$bearer_token) {
            return new WP_Error('token_error', 'Failed to retrieve SB token.');
        }
    }

    // Make the API request
    $args = array(
        'method'  => $method,
        'headers' => array(
            'Authorization' => 'Bearer ' . $bearer_token,
            'Content-Type'  => 'application/json',
        ),
        'body'    => $body ? json_encode($body) : null,
    );

    // Log the request details
    sh_debug_log('Making API request', array(
        'url' => $api_url,
        'method' => $method,
        'headers' => $args['headers'],
        'query_params' => $query_params,
        'body' => $body
    ));
    
    $response = wp_remote_request($api_url, $args);

    if (is_wp_error($response)) {
        sh_debug_log('API request failed', array(
            'error' => $response->get_error_message()
        ));
        return $response;
    }

    $response_code = wp_remote_retrieve_response_code($response);
    $response_headers = wp_remote_retrieve_headers($response);
    $raw_body = wp_remote_retrieve_body($response);
    
    // Log the response details
    sh_debug_log('API response received', array(
        'status_code' => $response_code,
        'headers' => $response_headers,
        'body' => json_decode($raw_body, true)
    ));

    // If unauthorized, try refreshing token and retry request
    if ($response_code === 401) {
        sh_debug_log('Got 401, refreshing token and retrying request');
        delete_transient('SB_TOKEN');
        delete_transient('SB_TOKEN_AGE');
        
        $bearer_token = get_refresh_sb_token();
        if ($bearer_token) {
            $args['headers']['Authorization'] = 'Bearer ' . $bearer_token;
            
            sh_debug_log('Retrying request with new token', array(
                'url' => $api_url,
                'headers' => $args['headers']
            ));
            
            $response = wp_remote_request($api_url, $args);
            
            if (is_wp_error($response)) {
                sh_debug_log('Retry request failed', array(
                    'error' => $response->get_error_message()
                ));
                return $response;
            }
            
            $response_code = wp_remote_retrieve_response_code($response);
            $response_headers = wp_remote_retrieve_headers($response);
            $raw_body = wp_remote_retrieve_body($response);
            
            sh_debug_log('Retry response received', array(
                'status_code' => $response_code,
                'headers' => $response_headers,
                'body' => json_decode($raw_body, true)
            ));
        }
    } else if ($response_code === 429) {
        sh_debug_log('Rate limit exceeded', array(
            'retry_after' => wp_remote_retrieve_header($response, 'retry-after')
        ));
        set_transient('SB_RATE_LIMIT', true, HOUR_IN_SECONDS);
        return new WP_Error('rate_limit', 'Rate limit exceeded. Please try again in an hour.');
    }

    $decoded_body = json_decode($raw_body, true);

    if (empty($decoded_body) || !is_array($decoded_body)) {
        sh_debug_log('Invalid API response structure', array(
            'raw_body' => $raw_body
        ));
        return new WP_Error('invalid_response', 'Invalid response structure from API.');
    }

    return $decoded_body;
}
