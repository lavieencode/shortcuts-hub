<?php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Fetch versions for a shortcut
function shortcuts_hub_fetch_versions() {
    check_ajax_referer('shortcuts_hub_nonce', 'security');

    $shortcut_id = isset($_POST['shortcut_id']) ? sanitize_text_field($_POST['shortcut_id']) : '';
    $search_term = isset($_POST['search_term']) ? sanitize_text_field($_POST['search_term']) : '';
    $status = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : '';
    $deleted = isset($_POST['deleted']) ? sanitize_text_field($_POST['deleted']) : '';
    $required_update = isset($_POST['required_update']) ? filter_var($_POST['required_update'], FILTER_VALIDATE_BOOLEAN) : null;

    if (empty($shortcut_id)) {
        wp_send_json_error('Shortcut ID is missing');
        return;
    }

    $query_params = array();
    if (!empty($search_term)) {
        $query_params['search'] = $search_term;
    }
    if ($status !== '') {
        $query_params['status'] = $status;
    }
    if ($deleted !== '') {
        $query_params['deleted'] = filter_var($deleted, FILTER_VALIDATE_BOOLEAN);
    }
    if ($required_update !== null) {
        $query_params['required_update'] = $required_update;
    }

    $sb_url = defined('SB_URL') ? SB_URL : null;
    if (!$sb_url) {
        wp_send_json_error('Server URL not configured.');
        return;
    }

    $bearer_token = get_refresh_sb_token();
    if (!$bearer_token) {
        wp_send_json_error('Failed to retrieve SB token');
        return;
    }

    $api_url = $sb_url . '/shortcuts/' . $shortcut_id . '/history';
    if (!empty($query_params)) {
        $api_url .= '?' . http_build_query($query_params);
    }

    $response = wp_remote_get($api_url, array(
        'headers' => array(
            'Authorization' => 'Bearer ' . $bearer_token,
        ),
    ));

    if (is_wp_error($response)) {
        wp_send_json_error('Error fetching versions: ' . $response->get_error_message());
        return;
    }

    $versions_data = json_decode(wp_remote_retrieve_body($response), true);

    if (empty($versions_data)) {
        wp_send_json_error('No versions found or invalid data structure.');
        return;
    }

    wp_send_json_success(array('versions' => $versions_data));
}

// Fetch single version details
function shortcuts_hub_fetch_single_version() {
    check_ajax_referer('shortcuts_hub_nonce', 'security');

    $shortcut_id = isset($_POST['shortcut_id']) ? sanitize_text_field($_POST['shortcut_id']) : '';
    $version_id = isset($_POST['version_id']) ? sanitize_text_field($_POST['version_id']) : '';

    if (empty($shortcut_id) || empty($version_id)) {
        wp_send_json_error('Shortcut ID or version ID is missing');
        return;
    }

    $api_url = SB_URL . '/shortcuts/' . $shortcut_id . '/version/' . $version_id;
    $bearer_token = get_refresh_sb_token();

    $response = wp_remote_get($api_url, array(
        'headers' => array(
            'Authorization' => 'Bearer ' . $bearer_token,
        ),
    ));

    if (is_wp_error($response)) {
        wp_send_json_error('Error fetching version details: ' . $response->get_error_message());
        return;
    }

    $version_data = json_decode(wp_remote_retrieve_body($response), true);

    if (empty($version_data)) {
        wp_send_json_error('Version data not found or invalid response');
        return;
    }

    wp_send_json_success(array('version' => $version_data));
}

// Edit an existing version
function shortcuts_hub_edit_version() {
    check_ajax_referer('shortcuts_hub_nonce', 'security');

    $shortcut_id = isset($_POST['shortcut_id']) ? sanitize_text_field($_POST['shortcut_id']) : '';
    $version_id = isset($_POST['version_id']) ? sanitize_text_field($_POST['version_id']) : '';
    $notes = isset($_POST['version_notes']) ? sanitize_text_field($_POST['version_notes']) : '';
    $url = isset($_POST['version_url']) ? esc_url_raw($_POST['version_url']) : '';
    $status = isset($_POST['version_status']) ? (int) sanitize_text_field($_POST['version_status']) : 0;
    $minimum_ios = isset($_POST['version_ios']) ? sanitize_text_field($_POST['version_ios']) : '';
    $minimum_mac = isset($_POST['version_mac']) ? sanitize_text_field($_POST['version_mac']) : '';
    $required_update = isset($_POST['version_required']) ? filter_var($_POST['version_required'], FILTER_VALIDATE_BOOLEAN) : false;

    $payload = array(
        'notes' => $notes,
        'url' => $url,
        'state' => $status,
        'minimumiOS' => $minimum_ios,
        'minimumMac' => $minimum_mac,
        'required' => $required_update
    );

    $bearer_token = get_refresh_sb_token();
    if (!$bearer_token) {
        wp_send_json_error(array('message' => 'Failed to retrieve SB token', 'submitted_payload' => $payload));
        return;
    }

    $api_url = SB_URL . '/shortcuts/' . $shortcut_id . '/version/' . $version_id;

    $response = wp_remote_request($api_url, array(
        'method' => 'PATCH',
        'headers' => array(
            'Authorization' => 'Bearer ' . $bearer_token,
            'Content-Type' => 'application/json',
        ),
        'body' => json_encode($payload),
    ));

    if (is_wp_error($response)) {
        wp_send_json_error(array('message' => 'Error updating version', 'error' => $response->get_error_message(), 'submitted_payload' => $payload));
        return;
    }

    $raw_response_body = wp_remote_retrieve_body($response);
    $updated_version = json_decode($raw_response_body, true);

    if (isset($updated_version['success']) && $updated_version['success']) {
        wp_send_json_success(array('message' => 'Version updated successfully', 'version' => $updated_version));
    } else {
        wp_send_json_error(array('message' => 'Failed to update version', 'server_response' => $raw_response_body, 'submitted_payload' => $payload));
    }
}

// Register AJAX endpoints for versions
add_action('wp_ajax_fetch_versions', 'shortcuts_hub_fetch_versions');
add_action('wp_ajax_fetch_single_version', 'shortcuts_hub_fetch_single_version');
add_action('wp_ajax_edit_version', 'shortcuts_hub_edit_version');
