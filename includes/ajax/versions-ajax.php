<?php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Fetch versions for a shortcut
function shortcuts_hub_fetch_versions() {
    // Check the security nonce
    check_ajax_referer('shortcuts_hub_nonce', 'security');

    // Sanitize the input data
    $shortcut_id = isset($_POST['shortcut_id']) ? sanitize_text_field($_POST['shortcut_id']) : '';
    $search_term = isset($_POST['search_term']) ? sanitize_text_field($_POST['search_term']) : '';
    $status = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : '';
    $deleted = isset($_POST['deleted']) ? sanitize_text_field($_POST['deleted']) : '';
    $required_update = isset($_POST['required_update']) ? sanitize_text_field($_POST['required_update']) : '';

    // Check if the shortcut ID is provided
    if (empty($shortcut_id)) {
        wp_send_json_error('Shortcut ID is missing');
        return;
    }

    // Prepare query parameters for the API call
    $query_params = array();
    if (!empty($search_term)) {
        $query_params['term'] = $search_term;
    }
    if ($status !== '') {
        $query_params['status'] = $status;
    }
    if ($deleted !== '') {
        $query_params['deleted'] = $deleted;
    }
    if ($required_update !== '') {
        $query_params['required_update'] = $required_update;
    }

    // Get the API bearer token and construct the API URL
    $bearer_token = get_refresh_sb_token();
    $api_url = get_option('SB_URL') . '/shortcuts/' . $shortcut_id . '/history';

    // Append query parameters if needed
    if (!empty($query_params)) {
        $api_url .= '?' . http_build_query($query_params);
    }

    // Make the API request
    $response = wp_remote_get($api_url, array(
        'headers' => array(
            'Authorization' => 'Bearer ' . $bearer_token,
        ),
    ));

    // Handle errors in the API response
    if (is_wp_error($response)) {
        wp_send_json_error('Error fetching versions: ' . $response->get_error_message());
        return;
    }

    // Decode the response body
    $versions_data = json_decode(wp_remote_retrieve_body($response), true);

    // Ensure that valid data is returned
    if (empty($versions_data)) {
        wp_send_json_error('No versions found or invalid data structure.');
        return;
    }

    // Send the versions data as a success response
    wp_send_json_success(array('versions' => $versions_data));
}

// Fetch single version details
function shortcuts_hub_fetch_single_version() {
    check_ajax_referer('shortcuts_hub_nonce', 'security'); // Verify nonce

    // Sanitize and validate input
    $shortcut_id = isset($_POST['shortcut_id']) ? sanitize_text_field($_POST['shortcut_id']) : '';
    $version_id = isset($_POST['version_id']) ? sanitize_text_field($_POST['version_id']) : '';

    if (empty($shortcut_id) || empty($version_id)) {
        wp_send_json_error('Shortcut ID or version ID is missing');
        return;
    }

    // Get the bearer token and construct the API URL
    $bearer_token = get_refresh_sb_token();
    if (!$bearer_token) {
        wp_send_json_error('Failed to retrieve SB token');
        return;
    }

    $api_url = get_option('SB_URL') . '/shortcuts/' . $shortcut_id . '/version/' . $version_id;

    // Make the API request
    $response = wp_remote_get($api_url, array(
        'headers' => array(
            'Authorization' => 'Bearer ' . $bearer_token,
        ),
    ));

    // Handle API response errors
    if (is_wp_error($response)) {
        wp_send_json_error('Error fetching version details: ' . $response->get_error_message());
        return;
    }

    // Decode the response body
    $version_data = json_decode(wp_remote_retrieve_body($response), true);

    // Ensure valid data
    if (empty($version_data)) {
        wp_send_json_error('Version data not found or invalid response');
        return;
    }

    // Send the version data as success
    wp_send_json_success(array('version' => $version_data));
}

// Edit an existing version
function shortcuts_hub_edit_version() {
    check_ajax_referer('shortcuts_hub_nonce', 'security'); // Verify nonce

    // Sanitize and validate input fields
    $version_id = isset($_POST['version_id']) ? sanitize_text_field($_POST['version_id']) : '';
    $version_name = isset($_POST['version_name']) ? sanitize_text_field($_POST['version_name']) : '';
    $notes = isset($_POST['version_notes']) ? sanitize_text_field($_POST['version_notes']) : '';
    $url = isset($_POST['version_url']) ? esc_url_raw($_POST['version_url']) : '';
    $status = isset($_POST['version_status']) ? sanitize_text_field($_POST['version_status']) : '';
    $minimum_ios = isset($_POST['version_ios']) ? sanitize_text_field($_POST['version_ios']) : '';
    $minimum_mac = isset($_POST['version_mac']) ? sanitize_text_field($_POST['version_mac']) : '';
    $required_update = isset($_POST['version_required']) ? sanitize_text_field($_POST['version_required']) : '';

    if (!$version_id) {
        wp_send_json_error('Version ID is missing');
        return;
    }

    // Prepare payload
    $payload = array(
        'version_name' => $version_name,
        'notes' => $notes,
        'url' => $url,
        'status' => $status,
        'minimum_ios' => $minimum_ios,
        'minimum_mac' => $minimum_mac,
        'required_update' => $required_update,
    );

    // Get the bearer token and make API call
    $bearer_token = get_refresh_sb_token();
    if (!$bearer_token) {
        wp_send_json_error('Failed to retrieve SB token');
        return;
    }

    $api_url = get_option('SB_URL') . '/versions/' . $version_id;

    $response = wp_remote_post($api_url, array(
        'method' => 'POST',
        'headers' => array(
            'Authorization' => 'Bearer ' . $bearer_token,
            'Content-Type' => 'application/json',
        ),
        'body' => json_encode($payload),
    ));

    // Handle API response errors
    if (is_wp_error($response)) {
        wp_send_json_error('Error updating version: ' . $response->get_error_message());
        return;
    }

    // Decode the response body
    $updated_version = json_decode(wp_remote_retrieve_body($response), true);

    // Ensure valid response
    if (!empty($updated_version)) {
        wp_send_json_success('Version updated successfully');
    } else {
        wp_send_json_error('Failed to update version');
    }
}

// Register AJAX endpoints for versions
add_action('wp_ajax_fetch_versions', 'shortcuts_hub_fetch_versions');
add_action('wp_ajax_fetch_single_version', 'shortcuts_hub_fetch_single_version');
add_action('wp_ajax_edit_version', 'shortcuts_hub_edit_version');