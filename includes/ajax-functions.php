<?php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

function shortcuts_hub_fetch_versions() {
    check_ajax_referer('shortcuts_hub_nonce', 'security');

    // Sanitize and validate the input
    $shortcut_id = isset($_POST['shortcut_id']) ? sanitize_text_field($_POST['shortcut_id']) : '';
    $search_term = isset($_POST['search_term']) ? sanitize_text_field($_POST['search_term']) : '';
    $status = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : '';
    $deleted = isset($_POST['deleted']) ? sanitize_text_field($_POST['deleted']) : '';
    $required_update = isset($_POST['required_update']) ? sanitize_text_field($_POST['required_update']) : '';

    // Check if the shortcut ID is missing
    if (empty($shortcut_id)) {
        wp_send_json_error('Shortcut ID is missing');
        return;
    }

    // Log the filters to ensure they are being received
    error_log('Filters received: ' . print_r(compact('search_term', 'status', 'deleted', 'required_update'), true));

    // Bearer Token and API URL
    $bearer_token = get_sb_token_from_storage();
    $api_url = get_option('SB_URL') . '/shortcuts/' . $shortcut_id . '/history';

    // Build query parameters for the API call
    $query_params = [];
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

    // Append query parameters to the API URL if available
    if (!empty($query_params)) {
        $api_url .= '?' . http_build_query($query_params);
    }

    // Make the API request
    $response = wp_remote_get($api_url, array(
        'headers' => array(
            'Authorization' => 'Bearer ' . $bearer_token,
        ),
    ));

    // Handle API response errors
    if (is_wp_error($response)) {
        wp_send_json_error('Error fetching versions: ' . $response->get_error_message());
        return;
    }

    // Parse the API response
    $versions_data = json_decode(wp_remote_retrieve_body($response), true);

    // Handle empty or invalid response
    if (empty($versions_data)) {
        wp_send_json_error('No versions found or invalid data structure.');
        return;
    }

    // Send the version data as a success response
    wp_send_json_success(array('versions' => $versions_data));
}

function shortcuts_hub_fetch_single_version() {
    check_ajax_referer('shortcuts_hub_nonce', 'security');

    $shortcut_id = isset($_POST['shortcut_id']) ? sanitize_text_field($_POST['shortcut_id']) : '';
    $version_number = isset($_POST['version_number']) ? sanitize_text_field($_POST['version_number']) : '';

    if (!$shortcut_id || !$version_number) {
        wp_send_json_error('Shortcut ID or version number is missing');
        return;
    }

    // Bearer token and API URL
    $bearer_token = get_sb_token_from_storage();
    $api_url = get_option('SB_URL') . '/shortcuts/' . $shortcut_id . '/version/' . $version_number;

    // Make the API request
    $response = wp_remote_get($api_url, array(
        'headers' => array(
            'Authorization' => 'Bearer ' . $bearer_token,
        ),
    ));

    // Check if the request was successful
    if (is_wp_error($response)) {
        wp_send_json_error('Failed to fetch version details');
        return;
    }

    // Decode the response body
    $version_data = json_decode(wp_remote_retrieve_body($response), true);

    // Ensure that version data is not empty and send it as a success response
    if (!empty($version_data)) {
        wp_send_json_success(array('version' => $version_data));
    } else {
        wp_send_json_error('Version not found');
    }
}

function shortcuts_hub_edit_version() {
    check_ajax_referer('shortcuts_hub_nonce', 'security');

    $version_id = isset($_POST['version_id']) ? sanitize_text_field($_POST['version_id']) : '';
    $version_name = isset($_POST['version_name']) ? sanitize_text_field($_POST['version_name']) : '';
    $notes = isset($_POST['notes']) ? sanitize_text_field($_POST['notes']) : '';
    $url = isset($_POST['url']) ? esc_url_raw($_POST['url']) : '';
    $status = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : '';
    $minimum_ios = isset($_POST['minimum_ios']) ? sanitize_text_field($_POST['minimum_ios']) : '';
    $minimum_mac = isset($_POST['minimum_mac']) ? sanitize_text_field($_POST['minimum_mac']) : '';
    $required_update = isset($_POST['required_update']) ? sanitize_text_field($_POST['required_update']) : '';

    if (!$version_id) {
        wp_send_json_error('Version ID is missing');
        return;
    }

    $bearer_token = get_sb_token_from_storage();
    $api_url = get_option('SB_URL') . '/versions/' . $version_id;

    // Prepare the request payload
    $payload = array(
        'version_name' => $version_name,
        'notes' => $notes,
        'url' => $url,
        'status' => $status,
        'minimum_ios' => $minimum_ios,
        'minimum_mac' => $minimum_mac,
        'required_update' => $required_update,
    );

    $response = wp_remote_post($api_url, array(
        'method' => 'POST',
        'headers' => array(
            'Authorization' => 'Bearer ' . $bearer_token,
            'Content-Type' => 'application/json'
        ),
        'body' => json_encode($payload)
    ));

    if (is_wp_error($response)) {
        wp_send_json_error('Failed to update version: ' . $response->get_error_message());
        return;
    }

    $updated_version = json_decode(wp_remote_retrieve_body($response), true);

    if (!empty($updated_version)) {
        wp_send_json_success('Version updated successfully!');
    } else {
        wp_send_json_error('Failed to update version');
    }
}

add_action('wp_ajax_fetch_versions', 'shortcuts_hub_fetch_versions');
add_action('wp_ajax_fetch_single_version', 'shortcuts_hub_fetch_single_version');
add_action('wp_ajax_edit_version', 'shortcuts_hub_edit_version');

function shortcuts_hub_fetch_shortcuts() {
    check_ajax_referer('shortcuts_hub_nonce', 'security');

    // Get the filter values from the AJAX request
    $filter_status = isset($_POST['filter_status']) ? sanitize_text_field($_POST['filter_status']) : '';
    $filter_deleted = isset($_POST['filter_deleted']) ? sanitize_text_field($_POST['filter_deleted']) : '';
    $search_term = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';

    // Bearer token and API URL
    $bearer_token = get_sb_token_from_storage();
    $api_url = get_option('SB_URL') . '/shortcuts';

    // Build the query parameters based on filters and search
    $query_params = [];
    if (!empty($filter_status)) {
        $query_params['state'] = $filter_status;
    }
    if (!empty($filter_deleted)) {
        $query_params['deleted'] = $filter_deleted;
    }
    if (!empty($search_term)) {
        $query_params['search'] = $search_term;
    }

    // Construct API URL with query parameters
    if (!empty($query_params)) {
        $api_url .= '?' . http_build_query($query_params);
    }

    // Log the API URL for debugging
    error_log('API Request URL: ' . $api_url);

    // Make API request
    $response = wp_remote_get($api_url, array(
        'headers' => array(
            'Authorization' => 'Bearer ' . $bearer_token,
        )
    ));

    if (is_wp_error($response)) {
        wp_send_json_error('Error fetching shortcuts');
        return;
    }

    // Parse the API response
    $shortcuts_data = json_decode(wp_remote_retrieve_body($response), true);

    if (!empty($shortcuts_data)) {
        wp_send_json_success($shortcuts_data);
    } else {
        wp_send_json_error('No shortcuts found or invalid data structure.');
    }
}

function shortcuts_hub_fetch_single_shortcut() {
    check_ajax_referer('shortcuts_hub_nonce', 'security');
    $shortcut_id = isset($_POST['id']) ? sanitize_text_field($_POST['id']) : '';

    if (!$shortcut_id) {
        wp_send_json_error('Shortcut ID missing');
    }

    $bearer_token = get_sb_token_from_storage();

    $api_url = get_option('SB_URL') . '/shortcuts/' . $shortcut_id;
    $response = wp_remote_get($api_url, array(
        'headers' => array(
            'Authorization' => 'Bearer ' . $bearer_token
        )
    ));

    if (is_wp_error($response)) {
        wp_send_json_error('Failed to fetch shortcut');
    } else {
        $body = wp_remote_retrieve_body($response);
        $shortcut = json_decode($body, true);

        if (!empty($shortcut)) {
            wp_send_json_success($shortcut);
        } else {
            wp_send_json_error('Shortcut not found');
        }
    }
}

function shortcuts_hub_update_shortcut() {
    check_ajax_referer('shortcuts_hub_nonce', 'security');

    $shortcut_id = isset($_POST['id']) ? sanitize_text_field($_POST['id']) : '';
    $name = isset($_POST['name']) ? sanitize_text_field($_POST['name']) : '';
    $headline = isset($_POST['headline']) ? sanitize_text_field($_POST['headline']) : '';
    $description = isset($_POST['description']) ? sanitize_text_field($_POST['description']) : '';
    $website = isset($_POST['website']) ? sanitize_text_field($_POST['website']) : '';
    $state = isset($_POST['state']) ? sanitize_text_field($_POST['state']) : '';

    if (!$shortcut_id) {
        wp_send_json_error('Shortcut ID is missing');
    }

    $bearer_token = get_sb_token_from_storage();
    $api_url = get_option('SB_URL') . '/shortcuts/' . $shortcut_id;

    // Send update to the API
    $response = wp_remote_post($api_url, array(
        'headers' => array(
            'Authorization' => 'Bearer ' . $bearer_token,
            'Content-Type' => 'application/json',
        ),
        'body' => json_encode(array(
            'name' => $name,
            'headline' => $headline,
            'description' => $description,
            'website' => $website,
            'state' => $state,
        )),
    ));

    if (is_wp_error($response)) {
        wp_send_json_error('Failed to update shortcut');
    }

    $body = wp_remote_retrieve_body($response);
    $updated_shortcut = json_decode($body, true);

    if (!empty($updated_shortcut)) {
        wp_send_json_success('Shortcut updated successfully!');
    } else {
        wp_send_json_error('Failed to update shortcut');
    }
}

add_action('wp_ajax_fetch_shortcuts', 'shortcuts_hub_fetch_shortcuts');
add_action('wp_ajax_fetch_single_shortcut', 'shortcuts_hub_fetch_single_shortcut');
add_action('wp_ajax_update_shortcut', 'shortcuts_hub_update_shortcut');