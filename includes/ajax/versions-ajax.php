<?php

if (!defined('ABSPATH')) {
    exit;
}

// Register AJAX actions
add_action('wp_ajax_fetch_versions', 'fetch_versions');
add_action('wp_ajax_fetch_latest_version', 'fetch_latest_version');
add_action('wp_ajax_nopriv_fetch_latest_version', 'fetch_latest_version');
add_action('wp_ajax_fetch_version', 'fetch_version');
add_action('wp_ajax_create_version', 'create_version');
add_action('wp_ajax_update_version', 'update_version');
add_action('wp_ajax_version_toggle_delete', 'version_toggle_delete');
add_action('wp_ajax_version_toggle_draft', 'version_toggle_draft');

// Fetch all versions with filtering options
function fetch_versions() {
    if (!isset($_POST['security'])) {
        wp_send_json_error(['message' => 'No security token provided']);
        return;
    }

    if (!wp_verify_nonce($_POST['security'], 'shortcuts_hub_nonce')) {
        wp_send_json_error(['message' => 'Invalid security token']);
        return;
    }

    $id = isset($_POST['id']) ? sanitize_text_field($_POST['id']) : '';
    if (empty($id)) {
        wp_send_json_error(['message' => 'Shortcut ID is missing']);
        return;
    }

    // Get the post title for the shortcut
    $args = array(
        'post_type' => 'shortcut',
        'meta_key' => 'sb_id',
        'meta_value' => $id,
        'posts_per_page' => 1
    );
    $query = new WP_Query($args);
    $shortcut_name = '';
    
    if ($query->have_posts()) {
        $post = $query->posts[0];
        $shortcut_name = $post->post_title;
    }

    $search_term = isset($_POST['search_term']) ? sanitize_text_field($_POST['search_term']) : '';
    $status = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : '';
    $deleted = isset($_POST['deleted']) && $_POST['deleted'] !== '' ? filter_var($_POST['deleted'], FILTER_VALIDATE_BOOLEAN) : null;
    $required_update = isset($_POST['required_update']) && $_POST['required_update'] !== '' ? filter_var($_POST['required_update'], FILTER_VALIDATE_BOOLEAN) : null;

    $query_params = array();
    if (!empty($search_term)) {
        $query_params['search'] = $search_term;
    }
    if ($status !== '') {
        $query_params['status'] = $status;
    }
    if ($deleted !== null) {
        $query_params['deleted'] = $deleted;
    }
    if ($required_update !== null) {
        $query_params['required_update'] = $required_update;
    }

    // Make the API call directly with the shortcut ID
    // DEBUG: Making API call to fetch versions
    sh_debug_log('Making versions API call', [
        'endpoint' => '/shortcuts/' . $id . '/history',
        'query_params' => $query_params,
        'shortcut_id' => $id,
        'source' => [
            'file' => __FILE__,
            'line' => __LINE__,
            'function' => __FUNCTION__
        ],
        'debug' => true
    ]);

    $response = sb_api_call('/shortcuts/' . $id . '/history', 'GET', $query_params);
    if (is_wp_error($response)) {
        // DEBUG: Error from versions API call
        sh_debug_log('Error from versions API call', [
            'error' => $response->get_error_message(),
            'shortcut_id' => $id,
            'source' => [
                'file' => __FILE__,
                'line' => __LINE__,
                'function' => __FUNCTION__
            ],
            'debug' => true
        ]);
        wp_send_json_error(['message' => 'Error fetching versions: ' . $response->get_error_message()]);
        return;
    }

    // DEBUG: Successful versions API response
    sh_debug_log('Successful versions API response', [
        'shortcut_id' => $id,
        'response' => $response,
        'source' => [
            'file' => __FILE__,
            'line' => __LINE__,
            'function' => __FUNCTION__
        ],
        'debug' => true
    ]);

    // Structure the response to match what the frontend expects
    $formatted_response = [
        'shortcut' => array_merge(
            isset($response['shortcut']) ? $response['shortcut'] : [],
            ['name' => $shortcut_name] // Add the shortcut name from WordPress
        ),
        'versions' => isset($response['versions']) ? $response['versions'] : []
    ];

    wp_send_json_success($formatted_response);
}

function fetch_latest_version() {
    // Only verify nonce for logged-in users
    if (is_user_logged_in()) {
        if (!isset($_POST['nonce'])) {
            wp_send_json_error(['message' => 'No nonce provided']);
            return;
        }

        if (!wp_verify_nonce($_POST['nonce'], 'shortcuts_hub_nonce')) {
            wp_send_json_error(['message' => 'Invalid nonce']);
            return;
        }
    }

    $id = isset($_POST['id']) ? sanitize_text_field($_POST['id']) : '';

    if (empty($id)) {
        wp_send_json_error(['message' => 'Shortcut ID is missing']);
        return;
    }

    $endpoint = "shortcuts/{$id}/version/latest";
    
    $response = sb_api_call($endpoint, 'GET');

    if (is_wp_error($response)) {
        wp_send_json_error(['message' => 'Error fetching latest version: ' . $response->get_error_message()]);
        return;
    }

    // Ensure we have a valid version object with a URL
    if (!isset($response['version']) || !isset($response['version']['url'])) {
        wp_send_json_error(['message' => 'Invalid version response structure']);
        return;
    }

    wp_send_json_success($response);
}

// Fetch a specific version
function fetch_version() {
    if (!isset($_POST['nonce'])) {
        wp_send_json_error(['message' => 'No nonce provided']);
        return;
    }

    if (!wp_verify_nonce($_POST['nonce'], 'shortcuts_hub_nonce')) {
        wp_send_json_error(['message' => 'Invalid nonce']);
        return;
    }

    $id = isset($_POST['id']) ? sanitize_text_field($_POST['id']) : '';
    $version_id = isset($_POST['version_id']) ? sanitize_text_field($_POST['version_id']) : '';
    $latest = isset($_POST['latest']) ? filter_var($_POST['latest'], FILTER_VALIDATE_BOOLEAN) : false;

    if (empty($id) || (!$latest && empty($version_id))) {
        wp_send_json_error(['message' => 'Shortcut ID or version ID is missing']);
        return;
    }

    if ($latest) {
        $endpoint = "shortcuts/{$id}/version/latest";
    } else {
        $endpoint = "shortcuts/{$id}/version/{$version_id}";
    }

    $response = sb_api_call($endpoint, 'GET');

    if (is_wp_error($response)) {
        wp_send_json_error(['message' => 'Error fetching version: ' . $response->get_error_message()]);
        return;
    }

    wp_send_json_success($response);
}

// Create a new version
function create_version() {
    if (!isset($_POST['security'])) {
        wp_send_json_error(['message' => 'No security token provided']);
        return;
    }

    if (!wp_verify_nonce($_POST['security'], 'shortcuts_hub_nonce')) {
        wp_send_json_error(['message' => 'Invalid security token']);
        return;
    }

    $id = isset($_POST['id']) ? sanitize_text_field($_POST['id']) : '';
    $version = isset($_POST['version']) ? sanitize_text_field($_POST['version']) : '';
    $notes = isset($_POST['notes']) ? sanitize_textarea_field($_POST['notes']) : '';
    $url = isset($_POST['url']) ? esc_url_raw($_POST['url']) : '';
    $minimum_ios = isset($_POST['minimum_ios']) ? intval($_POST['minimum_ios']) : null;
    $minimum_mac = isset($_POST['minimum_mac']) ? intval($_POST['minimum_mac']) : null;
    $required = isset($_POST['required']) ? filter_var($_POST['required'], FILTER_VALIDATE_BOOLEAN) : false;

    if (empty($id) || empty($version) || empty($url)) {
        wp_send_json_error(['message' => 'Shortcut ID, version, or URL is missing']);
        return;
    }

    // Prepare the data for the API call
    $data = [
        'version' => $version,
        'notes' => $notes,
        'url' => $url,
        'minimumiOS' => $minimum_ios,
        'minimumMac' => $minimum_mac,
        'required' => $required,
    ];

    $response = sb_api_call("/shortcuts/{$id}/version", 'POST', [], $data);

    if (is_wp_error($response)) {
        wp_send_json_error(['message' => 'Error creating version: ' . $response->get_error_message()]);
        return;
    }

    wp_send_json_success($response);
}

// Update an existing version
function update_version() {
    if (!isset($_POST['nonce'])) {
        wp_send_json_error(['message' => 'No nonce provided']);
        return;
    }

    if (!wp_verify_nonce($_POST['nonce'], 'shortcuts_hub_nonce')) {
        wp_send_json_error(['message' => 'Invalid nonce']);
        return;
    }

    $id = isset($_POST['id']) ? sanitize_text_field($_POST['id']) : '';
    $version_id = isset($_POST['version_id']) ? sanitize_text_field($_POST['version_id']) : '';
    $version_data = isset($_POST['version_data']) ? $_POST['version_data'] : array();

    if (empty($id) || empty($version_id) || empty($version_data)) {
        wp_send_json_error(['message' => 'Shortcut ID, version ID, or version data is missing']);
        return;
    }

    $response = sb_api_call('/shortcuts/' . $id . '/version/' . $version_id, 'PATCH', [], $version_data);

    if (is_wp_error($response)) {
        wp_send_json_error(['message' => 'Error updating version: ' . $response->get_error_message()]);
        return;
    }

    wp_send_json_success($response);
}

// Toggle delete/restore a version
function version_toggle_delete() {
    if (!isset($_POST['nonce'])) {
        wp_send_json_error(['message' => 'No nonce provided']);
        return;
    }

    if (!wp_verify_nonce($_POST['nonce'], 'shortcuts_hub_nonce')) {
        wp_send_json_error(['message' => 'Invalid nonce']);
        return;
    }

    $id = isset($_POST['id']) ? sanitize_text_field($_POST['id']) : '';
    $version_id = isset($_POST['version_id']) ? sanitize_text_field($_POST['version_id']) : '';
    $is_restore = isset($_POST['is_restore']) ? filter_var($_POST['is_restore'], FILTER_VALIDATE_BOOLEAN) : false;

    if (empty($id) || empty($version_id)) {
        wp_send_json_error(['message' => 'Shortcut ID or version ID is missing']);
        return;
    }

    $response = sb_api_call('/shortcuts/' . $id . '/version/' . $version_id, 'PATCH', [], ['deleted' => !$is_restore]);

    if (is_wp_error($response)) {
        wp_send_json_error(['message' => 'Error toggling delete for version: ' . $response->get_error_message()]);
        return;
    }

    wp_send_json_success(['message' => 'Version toggled successfully']);
}

// Toggle version state (publish/draft)
function version_toggle_draft() {
    if (!isset($_POST['nonce'])) {
        wp_send_json_error(['message' => 'No nonce provided']);
        return;
    }

    if (!wp_verify_nonce($_POST['nonce'], 'shortcuts_hub_nonce')) {
        wp_send_json_error(['message' => 'Invalid nonce']);
        return;
    }

    $id = isset($_POST['id']) ? sanitize_text_field($_POST['id']) : '';
    $version_id = isset($_POST['version_id']) ? sanitize_text_field($_POST['version_id']) : '';
    $new_state = isset($_POST['state']['value']) ? intval($_POST['state']['value']) : null;

    if (empty($id) || empty($version_id) || $new_state === null) {
        wp_send_json_error(['message' => 'Shortcut ID, version ID, or new state is missing']);
        return;
    }

    $response = sb_api_call('/shortcuts/' . $id . '/version/' . $version_id, 'PATCH', [], ['state' => $new_state]);

    if (is_wp_error($response)) {
        wp_send_json_error(['message' => 'Error toggling version state: ' . $response->get_error_message()]);
        return;
    }

    wp_send_json_success(['message' => 'Version state toggled successfully']);
}