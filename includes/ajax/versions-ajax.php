<?php

if (!defined('ABSPATH')) {
    exit;
}

// Include the API functions
require_once dirname(__FILE__) . '/../sb-api.php';

// Register AJAX actions
add_action('wp_ajax_fetch_versions', 'fetch_versions');
add_action('wp_ajax_fetch_latest_version', 'fetch_latest_version');
add_action('wp_ajax_nopriv_fetch_latest_version', 'fetch_latest_version');
add_action('wp_ajax_fetch_version', 'fetch_version');
add_action('wp_ajax_create_version', 'create_version');
add_action('wp_ajax_update_version', 'update_version');
add_action('wp_ajax_version_toggle_delete', 'version_toggle_delete');
add_action('wp_ajax_version_toggle_draft', 'version_toggle_draft');
add_action('wp_ajax_version_delete', 'version_delete');

// Fetch all versions with filtering options
function fetch_versions() {
    // Verify nonce
    $nonce = isset($_POST['security']) ? $_POST['security'] : '';
    $nonce_action = 'shortcuts_hub_fetch_versions_nonce';
    $verified = check_ajax_referer('shortcuts_hub_fetch_versions_nonce', 'security', false);

    if (!$verified) {
        wp_send_json_error(array('message' => 'Invalid security token'));
        return;
    }

    // Get shortcut ID
    $shortcut_id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    if (!$shortcut_id) {
        wp_send_json_error(array('message' => 'No shortcut ID provided'));
        return;
    }

    // Get filter parameters
    $search_term = isset($_POST['search_term']) ? sanitize_text_field($_POST['search_term']) : '';
    $status = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : '';
    $deleted = isset($_POST['deleted']) ? sanitize_text_field($_POST['deleted']) : '';
    $required_update = isset($_POST['required_update']) ? sanitize_text_field($_POST['required_update']) : null;

    // Build API endpoint and query parameters
    $endpoint = "/shortcuts/{$shortcut_id}/history";
    $query_params = array();

    // Add filter parameters if set
    if ($search_term) {
        $query_params['search_term'] = $search_term;
    }
    if ($status && $status !== 'any') {
        $query_params['status'] = $status;
    }
    if ($deleted && $deleted !== 'any') {
        $query_params['deleted'] = $deleted;
    }
    if ($required_update && $required_update !== 'any') {
        $query_params['required_update'] = $required_update;
    }

    // Make API request
    $response = sb_api_call($endpoint, 'GET', $query_params);

    // Validate response structure
    if (!is_array($response)) {
        wp_send_json_error(array('message' => 'Invalid response structure from API'));
        return;
    }

    // Extract shortcut and versions data
    $shortcut = isset($response['shortcut']) ? $response['shortcut'] : null;
    $versions = isset($response['versions']) ? $response['versions'] : array();

    // Validate shortcut data
    if (!$shortcut) {
        wp_send_json_error(array('message' => 'No shortcut data found'));
        return;
    }

    // Send response
    wp_send_json_success(array(
        'shortcut' => $shortcut,
        'versions' => $versions
    ));
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
        wp_send_json_error(array('message' => 'No security token provided'));
        return;
    }

    if (!wp_verify_nonce($_POST['security'], 'shortcuts_hub_create_version_nonce')) {
        wp_send_json_error(array('message' => 'Invalid security token'));
        return;
    }

    $id = isset($_POST['id']) ? sanitize_text_field($_POST['id']) : '';
    $version = isset($_POST['version']) ? sanitize_text_field($_POST['version']) : '';
    $notes = isset($_POST['notes']) ? sanitize_textarea_field($_POST['notes']) : '';
    $url = isset($_POST['url']) ? esc_url_raw($_POST['url']) : '';
    $minimum_ios = isset($_POST['minimum_ios']) ? sanitize_text_field($_POST['minimum_ios']) : '';
    $minimum_mac = isset($_POST['minimum_mac']) ? sanitize_text_field($_POST['minimum_mac']) : '';
    $required = isset($_POST['required']) ? filter_var($_POST['required'], FILTER_VALIDATE_BOOLEAN) : false;
    $version_state = isset($_POST['version_state']) ? sanitize_text_field($_POST['version_state']) : 'draft';

    if (empty($id) || empty($version) || empty($url)) {
        wp_send_json_error(array('message' => 'Required fields missing'));
        return;
    }

    // Prepare the data for the API call
    $data = array(
        'version' => $version,
        'notes' => $notes,
        'url' => $url,
        'minimumiOS' => $minimum_ios,
        'minimumMac' => $minimum_mac,
        'required' => $required,
        'state' => $version_state === 'published' ? 0 : 1 // 0 = published, 1 = draft
    );

    $response = sb_api_call("/shortcuts/{$id}/version", 'POST', array(), $data);

    if (is_wp_error($response)) {
        wp_send_json_error(array('message' => 'Error creating version: ' . $response->get_error_message()));
        return;
    }

    wp_send_json_success($response);
}

// Update an existing version
function update_version() {
    // Verify nonce
    if (!isset($_POST['security']) || !wp_verify_nonce($_POST['security'], 'shortcuts_hub_update_version_nonce')) {
        wp_send_json_error(['message' => 'Invalid security token']);
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

    // Ensure we have a properly formatted response
    if (isset($response['version'])) {
        wp_send_json_success(['version' => $response['version']]);
    } else {
        wp_send_json_error(['message' => 'Invalid response format from API']);
    }
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

// Permanently delete a version
function version_delete() {
    // Verify nonce and log the verification
    $nonce = isset($_POST['security']) ? $_POST['security'] : '';
    $nonce_action = 'shortcuts_hub_versions_nonce';
    $nonce_verified = wp_verify_nonce($nonce, $nonce_action);

    if (!$nonce_verified) {
        wp_send_json_error(array('message' => 'Invalid security token'));
        return;
    }

    // Get and sanitize parameters
    $shortcut_id = isset($_POST['shortcut_id']) ? sanitize_text_field($_POST['shortcut_id']) : '';
    $version_id = isset($_POST['version_id']) ? sanitize_text_field($_POST['version_id']) : '';

    if (empty($shortcut_id) || empty($version_id)) {
        wp_send_json_error(array('message' => 'Missing required parameters'));
        return;
    }

    // Construct the endpoint
    $endpoint = "/shortcuts/{$shortcut_id}/version/{$version_id}";

    // Make the API call
    $response = sb_api_call($endpoint, 'DELETE');

    if (is_wp_error($response)) {
        wp_send_json_error(array('message' => 'Error deleting version: ' . $response->get_error_message()));
        return;
    }

    wp_send_json_success(array('message' => 'Version deleted successfully'));
}