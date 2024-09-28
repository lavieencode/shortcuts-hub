<?php

if (!defined('ABSPATH')) {
    exit;
}

function get_refresh_sb_token() {
    $cached_token = get_transient('SB_TOKEN');
    
    if ($cached_token) {
        return $cached_token;
    }

    $api_url = SB_URL . '/login';
    $response = wp_remote_post($api_url, [
        'body' => json_encode([
            'username' => SB_USERNAME,
            'password' => SB_PASSWORD
        ]),
        'headers' => [
            'Content-Type' => 'application/json',
        ],
    ]);

    if (is_wp_error($response)) {
        return false;
    }

    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);

    if (!empty($data['token'])) {
        set_transient('SB_TOKEN', $data['token'], HOUR_IN_SECONDS);
        return $data['token'];
    }

    return false;
}

function make_sb_api_call($endpoint, $method = 'GET', $query_params = array(), $body = null) {
    if (!defined('SB_URL')) {
        return new WP_Error('missing_url', 'API base URL is not defined.');
    }

    $api_url = SB_URL . $endpoint;

    if (!empty($query_params) && $method === 'GET') {
        $api_url .= '?' . http_build_query($query_params);
    }

    $bearer_token = get_refresh_sb_token();
    if (is_wp_error($bearer_token)) {
        return $bearer_token;
    }

    $args = array(
        'method'  => $method,
        'headers' => array(
            'Authorization' => 'Bearer ' . $bearer_token,
            'Content-Type'  => 'application/json',
        ),
        'timeout' => 15,
    );

    if ($method !== 'GET' && !empty($body)) {
        $args['body'] = json_encode($body);
    }

    $response = wp_remote_request($api_url, $args);

    if (is_wp_error($response)) {
        return $response;
    }

    $raw_body = wp_remote_retrieve_body($response);
    $decoded_body = json_decode($raw_body, true);

    if (empty($decoded_body) || !is_array($decoded_body)) {
        return new WP_Error('invalid_response', 'Invalid response structure from API.');
    }

    return $decoded_body;
}