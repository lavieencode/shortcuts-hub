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

    $response = sb_fetch_versions($shortcut_id, $query_params);
    if (is_wp_error($response)) {
        wp_send_json_error(['message' => 'Error fetching versions: ' . $response->get_error_message()]);
        return;
    }

    wp_send_json_success($response);
}

// Fetch a specific version
function fetch_version() {
    check_ajax_referer('shortcuts_hub_nonce', 'security');
    $shortcut_id = isset($_POST['shortcut_id']) ? sanitize_text_field($_POST['shortcut_id']) : '';
    $version_id = isset($_POST['version_id']) ? sanitize_text_field($_POST['version_id']) : '';

    if (empty($shortcut_id) || empty($version_id)) {
        wp_send_json_error(['message' => 'Shortcut ID or version ID is missing']);
        return;
    }

    $response = sb_fetch_version($shortcut_id, $version_id);

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
    $version_data = isset($_POST['version_data']) ? $_POST['version_data'] : array();

    if (empty($shortcut_id) || empty($version_data)) {
        wp_send_json_error(['message' => 'Shortcut ID or version data is missing']);
        return;
    }

    $response = sb_api_call('/shortcuts/' . $shortcut_id . '/versions', 'POST', [], $version_data);

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

    $response = sb_update_version($shortcut_id, $version_id, $version_data);
    if (is_wp_error($response)) {
        wp_send_json_error(['message' => 'Error updating version: ' . $response->get_error_message()]);
        return;
    }

    wp_send_json_success($response);
}

// Delete a version
function delete_version() {
    check_ajax_referer('shortcuts_hub_nonce', 'security');
    $shortcut_id = isset($_POST['shortcut_id']) ? sanitize_text_field($_POST['shortcut_id']) : '';
    $version_id = isset($_POST['version_id']) ? sanitize_text_field($_POST['version_id']) : '';

    if (empty($shortcut_id) || empty($version_id)) {
        wp_send_json_error(['message' => 'Shortcut ID or version ID is missing']);
        return;
    }

    $response = sb_api_call('/shortcuts/' . $shortcut_id . '/version/' . $version_id, 'PATCH', [], ['deleted' => true]);

    if (is_wp_error($response)) {
        wp_send_json_error(['message' => 'Error deleting version: ' . $response->get_error_message()]);
        return;
    }

    wp_send_json_success(['message' => 'Version marked as deleted']);
}

add_action('wp_ajax_fetch_versions', 'fetch_versions');
add_action('wp_ajax_fetch_version', 'fetch_version');
add_action('wp_ajax_create_version', 'create_version');
add_action('wp_ajax_update_version', 'update_version');
add_action('wp_ajax_delete_version', 'delete_version');
