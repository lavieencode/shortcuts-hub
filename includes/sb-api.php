<?php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Define the base URL for the Switchblade API
if (!defined('SB_URL')) {
    define('SB_URL', 'https://debotchery-switchblade-bc6fa1ee4e01.herokuapp.com');
}

require_once 'auth.php';

// Switchblade API Functions

function sb_api_call($endpoint, $method = 'GET', $query_params = array(), $body = null) {
    if (!defined('SB_URL')) {
        return new WP_Error('missing_url', 'API base URL is not defined.');
    }

    $api_url = SB_URL . $endpoint;

    if (!empty($query_params) && $method === 'GET') {
        $api_url .= '?' . http_build_query($query_params);
    }

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
        'body'    => $body ? json_encode($body) : null,
    );

    if ($method === 'PATCH') {
        error_log('--- SB API Call Details ---');
        error_log('API URL: ' . $api_url);
        error_log('Request Method: ' . $method);
        error_log('Request Headers: ' . print_r($args['headers'], true));
        if ($body) {
            error_log('Request Body: ' . print_r($body, true));
        }
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

    if ($method === 'PATCH') {
        error_log('API Response: ' . print_r($decoded_body, true));
    }

    return $decoded_body;
}

// Fetches all shortcuts from the Switchblade API
function sb_fetch_shortcuts($query_params = []) {
    return sb_api_call('/shortcuts', 'GET', $query_params);
}

// Fetches a single shortcut by ID from the Switchblade API
function sb_fetch_single_shortcut($shortcut_id) {
    return sb_api_call('/shortcuts/' . $shortcut_id, 'GET');
}

// Updates a shortcut by ID in the Switchblade API
function sb_update_shortcut($shortcut_id, $data) {
    return sb_api_call('/shortcuts/' . $shortcut_id, 'PATCH', [], $data);
}

// Marks a shortcut as deleted in the Switchblade API
function sb_delete_shortcut($shortcut_id) {
    return sb_api_call('/shortcuts/' . $shortcut_id, 'PATCH', [], ['deleted' => true]);
}

// Restores a shortcut by setting deleted to false in the Switchblade API
function sb_restore_shortcut($shortcut_id) {
    return sb_api_call('/shortcuts/' . $shortcut_id, 'PATCH', [], ['deleted' => false]);
}

// Fetches all versions of a shortcut from the Switchblade API
function sb_fetch_versions($shortcut_id, $query_params = []) {
    $response = sb_api_call('/shortcuts/' . $shortcut_id . '/history', 'GET', $query_params);

    if (is_wp_error($response)) {
        return $response;
    }

    // Log the response to check its structure
    error_log('API Response: ' . print_r($response, true));

    // Adjust this based on the actual structure of the response
    $shortcut_name = $response['shortcut']['name'] ?? 'Unnamed Shortcut';

    return [
        'versions' => $response['versions'],
        'shortcut_name' => $shortcut_name
    ];
}

// Fetches a single version of a shortcut by version ID from the Switchblade API
function sb_fetch_version($shortcut_id, $version_id) {
    $endpoint = '/shortcuts/' . $shortcut_id . '/version/' . $version_id;
    return sb_api_call($endpoint, 'GET');
}

// Updates a version of a shortcut by version ID in the Switchblade API
function sb_update_version($shortcut_id, $version_id, $version_data) {
    error_log('sb_update_version called with:');
    error_log('Shortcut ID: ' . $shortcut_id);
    error_log('Version ID: ' . $version_id);
    error_log('Version Data: ' . print_r($version_data, true));

    // Ensure 'required' is explicitly set as a boolean
    if (isset($version_data['required'])) {
        $version_data['required'] = filter_var($version_data['required'], FILTER_VALIDATE_BOOLEAN);
    }

    $endpoint = '/shortcuts/' . $shortcut_id . '/version/' . $version_id;
    $response = sb_api_call($endpoint, 'PATCH', [], $version_data);

    if (is_wp_error($response)) {
        error_log('API request error: ' . $response->get_error_message(), 0);
        return $response;
    }

    error_log('sb_update_version successful');
    return $response;
}

// WordPress Functions

// Fetches all WordPress shortcuts
function wp_fetch_shortcuts() {
    check_ajax_referer('shortcuts_hub_nonce', 'security');

    $args = array(
        'post_type' => 'shortcut',
        'post_status' => 'publish',
        'numberposts' => -1
    );

    $posts = get_posts($args);

    if (empty($posts)) {
        wp_send_json_error('No WP shortcuts found');
        return;
    }

    $shortcuts = array();
    foreach ($posts as $post) {
        $shortcuts[] = array(
            'id' => $post->ID,
            'name' => get_the_title($post),
            'description' => get_the_excerpt($post),
            'sb_data' => get_post_meta($post->ID, 'sb_data', true)
        );
    }

    wp_send_json_success($shortcuts);
}

// Creates a new WordPress shortcut
function wp_create_shortcut($shortcut_data) {
    $post_data = array(
        'post_title'   => sanitize_text_field($shortcut_data['name']),
        'post_content' => sanitize_textarea_field($shortcut_data['description']),
        'post_status'  => $shortcut_data['state'] ? 'draft' : 'publish',
        'post_type'    => 'shortcut',
        'meta_input'   => array(
            'headline' => sanitize_text_field($shortcut_data['headline']),
            'input'    => sanitize_text_field($shortcut_data['input']),
            'result'   => sanitize_text_field($shortcut_data['result']),
            'color'    => sanitize_hex_color($shortcut_data['color']),
            'icon'     => esc_url_raw($shortcut_data['icon']),
            'actions'  => array_map('sanitize_text_field', $shortcut_data['actions']),
            'sb_id'    => sanitize_text_field($shortcut_data['sb_id'])
        )
    );

    $post_id = wp_insert_post($post_data);

    if (is_wp_error($post_id)) {
        return $post_id;
    }

    return $post_id;
}

// Updates an existing WordPress shortcut
function wp_update_shortcut($shortcut_id, $shortcut_data) {
    $post_data = array(
        'ID'           => $shortcut_id,
        'post_title'   => sanitize_text_field($shortcut_data['name']),
        'post_content' => sanitize_textarea_field($shortcut_data['description']),
        'post_status'  => $shortcut_data['state'] ? 'draft' : 'publish',
    );

    $post_id = wp_update_post($post_data);

    if (is_wp_error($post_id)) {
        return $post_id;
    }

    update_post_meta($shortcut_id, 'headline', sanitize_text_field($shortcut_data['headline']));
    update_post_meta($shortcut_id, 'input', sanitize_text_field($shortcut_data['input']));
    update_post_meta($shortcut_id, 'result', sanitize_text_field($shortcut_data['result']));
    update_post_meta($shortcut_id, 'color', sanitize_hex_color($shortcut_data['color']));
    update_post_meta($shortcut_id, 'icon', esc_url_raw($shortcut_data['icon']));
    update_post_meta($shortcut_id, 'actions', array_map('sanitize_text_field', $shortcut_data['actions']));

    return $post_id;
}

// Deletes a WordPress shortcut
function wp_delete_shortcut($shortcut_id) {
    return wp_trash_post($shortcut_id);
}

// Restores a WordPress shortcut
function wp_restore_shortcut($shortcut_id) {
    return wp_untrash_post($shortcut_id);
}

// Stores Switchblade shortcuts in WordPress as custom posts
function store_sb_shortcuts($shortcuts) {
    foreach ($shortcuts as $shortcut) {
        $existing_post = get_posts(array(
            'post_type' => 'shortcut',
            'meta_key' => 'sb_id',
            'meta_value' => $shortcut['id'],
            'numberposts' => 1
        ));

        if (empty($existing_post)) {
            wp_insert_post(array(
                'post_title'   => sanitize_text_field($shortcut['name']),
                'post_content' => sanitize_textarea_field($shortcut['description']),
                'post_status'  => 'publish',
                'post_type'    => 'shortcut',
                'meta_input'   => array(
                    'sb_id' => sanitize_text_field($shortcut['id']),
                    'sb_data' => maybe_serialize($shortcut)
                )
            ));
        }
    }
}

add_action('wp_ajax_sb_fetch_shortcuts', 'sb_fetch_shortcuts');
add_action('wp_ajax_sb_fetch_single_shortcut', 'sb_fetch_single_shortcut');
add_action('wp_ajax_sb_update_shortcut', 'sb_update_shortcut');
add_action('wp_ajax_sb_delete_shortcut', 'sb_delete_shortcut');
add_action('wp_ajax_sb_restore_shortcut', 'sb_restore_shortcut');
add_action('wp_ajax_sb_fetch_versions', 'sb_fetch_versions');
add_action('wp_ajax_sb_fetch_version', 'sb_fetch_version');
add_action('wp_ajax_sb_update_version', 'sb_update_version');
add_action('wp_ajax_wp_fetch_shortcuts', 'wp_fetch_shortcuts');
add_action('wp_ajax_wp_create_shortcut', 'wp_create_shortcut');
add_action('wp_ajax_wp_update_shortcut', 'wp_update_shortcut');
add_action('wp_ajax_wp_delete_shortcut', 'wp_delete_shortcut');
add_action('wp_ajax_wp_restore_shortcut', 'wp_restore_shortcut');
add_action('wp_ajax_store_sb_shortcuts', 'store_sb_shortcuts');
