<?php

if (!defined('ABSPATH')) {
    exit;
}

// Fetch all versions with filtering options
function fetch_versions() {
    check_ajax_referer('shortcuts_hub_nonce', 'security');
    $shortcut_id = isset($_POST['shortcut_id']) ? sanitize_text_field($_POST['shortcut_id']) : '';
    $search_term = isset($_POST['search_term']) ? sanitize_text_field($_POST['search_term']) : '';
    $status = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : '';
    $deleted = isset($_POST['deleted']) ? filter_var($_POST['deleted'], FILTER_VALIDATE_BOOLEAN) : null;
    $required_update = isset($_POST['required_update']) ? filter_var($_POST['required_update'], FILTER_VALIDATE_BOOLEAN) : null;

    error_log("Fetching versions for shortcut ID: $shortcut_id with filters - search: $search_term, status: $status, deleted: $deleted, required_update: $required_update");

    if (empty($shortcut_id)) {
        wp_send_json_error(['message' => 'Shortcut ID is missing']);
        return;
    }

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

    $response = sb_api_call('/shortcuts/' . $shortcut_id . '/history', 'GET', $query_params);
    if (is_wp_error($response)) {
        error_log("Error fetching versions: " . $response->get_error_message());
        wp_send_json_error(['message' => 'Error fetching versions: ' . $response->get_error_message()]);
        return;
    }

    error_log("Successfully fetched versions: " . json_encode($response));
    wp_send_json_success($response);
}

function fetch_latest_version() {
    check_ajax_referer('shortcuts_hub_nonce', 'security');

    $shortcut_id = isset($_POST['shortcut_id']) ? sanitize_text_field($_POST['shortcut_id']) : '';

    if (empty($shortcut_id)) {
        wp_send_json_error(['message' => 'Shortcut ID is missing']);
        return;
    }

    $endpoint = "shortcuts/{$shortcut_id}/version/latest";
    $response = sb_api_call($endpoint, 'GET');

    if (is_wp_error($response)) {
        wp_send_json_error(['message' => 'Error fetching latest version: ' . $response->get_error_message()]);
        return;
    }

    wp_send_json_success($response);
}


// Fetch a specific version
function fetch_version() {
    check_ajax_referer('shortcuts_hub_nonce', 'security');
    $shortcut_id = isset($_POST['shortcut_id']) ? sanitize_text_field($_POST['shortcut_id']) : '';
    $version_id = isset($_POST['version_id']) ? sanitize_text_field($_POST['version_id']) : '';
    $latest = isset($_POST['latest']) ? filter_var($_POST['latest'], FILTER_VALIDATE_BOOLEAN) : false;

    if (empty($shortcut_id) || (!$latest && empty($version_id))) {
        wp_send_json_error(['message' => 'Shortcut ID or version ID is missing']);
        return;
    }

    if ($latest) {
        $endpoint = "shortcuts/{$shortcut_id}/version/latest";
    } else {
        $endpoint = "shortcuts/{$shortcut_id}/version/{$version_id}";
    }

    $response = sb_api_call($endpoint, 'GET');

    error_log("Fetch version response: " . print_r($response, true));

    if (is_wp_error($response)) {
        wp_send_json_error(['message' => 'Error fetching version: ' . $response->get_error_message()]);
        return;
    }

    wp_send_json_success($response);
}

// Create a new version
function create_version() {
    check_ajax_referer('shortcuts_hub_nonce', 'security');

    $shortcut_id = isset($_POST['shortcut_id']) ? sanitize_text_field($_POST['shortcut_id']) : '';
    $version = isset($_POST['version']) ? sanitize_text_field($_POST['version']) : '';
    $notes = isset($_POST['notes']) ? sanitize_textarea_field($_POST['notes']) : '';
    $url = isset($_POST['url']) ? esc_url_raw($_POST['url']) : '';
    $minimum_ios = isset($_POST['minimum_ios']) ? intval($_POST['minimum_ios']) : null;
    $minimum_mac = isset($_POST['minimum_mac']) ? intval($_POST['minimum_mac']) : null;
    $required = isset($_POST['required']) ? filter_var($_POST['required'], FILTER_VALIDATE_BOOLEAN) : false;

    if (empty($shortcut_id) || empty($version) || empty($url)) {
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

    $response = sb_api_call("/shortcuts/{$shortcut_id}/version", 'POST', [], $data);

    if (is_wp_error($response)) {
        wp_send_json_error(['message' => 'Error creating version: ' . $response->get_error_message()]);
        return;
    }

    wp_send_json_success($response);
}

// Update an existing version
function update_version() {
    check_ajax_referer('shortcuts_hub_nonce', 'security');
    $shortcut_id = isset($_POST['shortcut_id']) ? sanitize_text_field($_POST['shortcut_id']) : '';
    $version_id = isset($_POST['version_id']) ? sanitize_text_field($_POST['version_id']) : '';
    $version_data = isset($_POST['version_data']) ? $_POST['version_data'] : array();

    if (empty($shortcut_id) || empty($version_id) || empty($version_data)) {
        wp_send_json_error(['message' => 'Shortcut ID, version ID, or version data is missing']);
        return;
    }

    $response = sb_api_call('/shortcuts/' . $shortcut_id . '/version/' . $version_id, 'PATCH', [], $version_data);

    if (is_wp_error($response)) {
        wp_send_json_error(['message' => 'Error updating version: ' . $response->get_error_message()]);
        return;
    }

    wp_send_json_success($response);
}

// Toggle delete/restore a version
function version_toggle_delete() {
    check_ajax_referer('shortcuts_hub_nonce', 'security');
    $shortcut_id = isset($_POST['shortcut_id']) ? sanitize_text_field($_POST['shortcut_id']) : '';
    $version_id = isset($_POST['version_id']) ? sanitize_text_field($_POST['version_id']) : '';
    $is_restore = isset($_POST['is_restore']) ? filter_var($_POST['is_restore'], FILTER_VALIDATE_BOOLEAN) : false;

    if (empty($shortcut_id) || empty($version_id)) {
        wp_send_json_error(['message' => 'Shortcut ID or version ID is missing']);
        return;
    }

    $response = sb_api_call('/shortcuts/' . $shortcut_id . '/version/' . $version_id, 'PATCH', [], ['deleted' => !$is_restore]);

    if (is_wp_error($response)) {
        wp_send_json_error(['message' => 'Error toggling delete for version: ' . $response->get_error_message()]);
        return;
    }

    wp_send_json_success(['message' => 'Version toggled successfully']);
}

add_action('wp_ajax_version_toggle_delete', 'version_toggle_delete');

// Toggle version state (publish/draft)
function version_toggle_draft() {
    check_ajax_referer('shortcuts_hub_nonce', 'security');
    $shortcut_id = isset($_POST['shortcut_id']) ? sanitize_text_field($_POST['shortcut_id']) : '';
    $version_id = isset($_POST['version_id']) ? sanitize_text_field($_POST['version_id']) : '';
    $new_state = isset($_POST['new_state']) ? intval($_POST['new_state']) : null;

    if (empty($shortcut_id) || empty($version_id) || $new_state === null) {
        wp_send_json_error(['message' => 'Shortcut ID, version ID, or new state is missing']);
        return;
    }

    $response = sb_api_call('/shortcuts/' . $shortcut_id . '/version/' . $version_id, 'PATCH', [], ['state' => $new_state]);

    if (is_wp_error($response)) {
        wp_send_json_error(['message' => 'Error toggling version state: ' . $response->get_error_message()]);
        return;
    }

    wp_send_json_success(['message' => 'Version state toggled successfully']);
}

add_action('wp_ajax_version_toggle_draft', 'version_toggle_draft');

add_action('wp_ajax_fetch_versions', 'fetch_versions');
add_action('wp_ajax_fetch_latest_version', 'fetch_latest_version');
add_action('wp_ajax_fetch_version', 'fetch_version');
add_action('wp_ajax_create_version', 'create_version');
add_action('wp_ajax_update_version', 'update_version');
add_action('wp_ajax_version_toggle_delete', 'versions_toggle_delete');
add_action('wp_ajax_version_toggle_draft', 'versions_toggle_draft');