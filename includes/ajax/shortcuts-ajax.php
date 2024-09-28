<?php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

function shortcuts_hub_fetch_shortcuts() {
    check_ajax_referer('shortcuts_hub_nonce', 'security');

    // Fetch the SB token
    $token = get_refresh_sb_token();

    if (!$token) {
        wp_send_json_error('Failed to retrieve SB token.');
        return;
    }

    // Get the filter values from the AJAX request
    $filter_status = isset($_POST['filter_status']) ? sanitize_text_field($_POST['filter_status']) : '';
    $filter_deleted = isset($_POST['filter_deleted']) ? sanitize_text_field($_POST['filter_deleted']) : '';
    $search_term = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';

    // Build the query parameters for the API call
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

    // Log the query parameters being sent
    error_log('Query parameters for fetching shortcuts: ' . print_r($query_params, true));

    // Make the API call using the token
    $shortcuts = make_sb_api_call('/shortcuts', 'GET', $query_params);

    // Handle the case where no shortcuts are returned or there's an error
    if (is_wp_error($shortcuts)) {
        error_log('Error fetching shortcuts: ' . $shortcuts->get_error_message());
        wp_send_json_error('Error fetching shortcuts from the API.');
        return;
    }

    if (empty($shortcuts)) {
        error_log('No shortcuts found in the response.');
        wp_send_json_error('No shortcuts found.');
        return;
    }

    // Ensure that the response has the correct structure
    if (isset($shortcuts['shortcuts'])) {
        error_log('Successfully retrieved shortcuts: ' . print_r($shortcuts['shortcuts'], true));
        wp_send_json_success($shortcuts['shortcuts']);
    } else {
        error_log('Invalid response format from the API.');
        wp_send_json_error('Invalid response format from the API.');
    }
}

function shortcuts_hub_fetch_single_shortcut() {
    check_ajax_referer('shortcuts_hub_nonce', 'security');

    // Retrieve and sanitize the shortcut ID
    $shortcut_id = isset($_POST['id']) ? sanitize_text_field($_POST['id']) : '';

    // Log the received shortcut ID
    if (empty($shortcut_id)) {
        error_log('Error: Shortcut ID is missing in the request.');
        wp_send_json_error('Shortcut ID is missing');
        return;
    }
    error_log('Received Shortcut ID: ' . $shortcut_id);

    // Construct API endpoint for fetching single shortcut
    $endpoint = '/shortcuts/' . $shortcut_id;

    // Log the endpoint being used
    error_log('Using API Endpoint: ' . $endpoint);

    // Make the API call using the reusable function
    $shortcut = make_sb_api_call($endpoint);

    // Log the response or error from the API call
    if (is_wp_error($shortcut)) {
        error_log('Error fetching shortcut from API: ' . $shortcut->get_error_message());
        wp_send_json_error($shortcut->get_error_message());
        return;
    }

    // Log the success or failure of fetching the shortcut
    if (!empty($shortcut)) {
        error_log('Successfully fetched shortcut data: ' . print_r($shortcut, true));
        wp_send_json_success($shortcut);
    } else {
        error_log('Error: Shortcut data is empty or invalid.');
        wp_send_json_error('Shortcut not found');
    }
}

function shortcuts_hub_update_shortcut() {
    check_ajax_referer('shortcuts_hub_nonce', 'security');

    // Sanitize and validate input
    $shortcut_id = isset($_POST['id']) ? sanitize_text_field($_POST['id']) : '';
    $name = isset($_POST['name']) ? sanitize_text_field($_POST['name']) : '';
    $headline = isset($_POST['headline']) ? sanitize_text_field($_POST['headline']) : '';
    $description = isset($_POST['description']) ? sanitize_text_field($_POST['description']) : '';
    $website = isset($_POST['website']) ? sanitize_text_field($_POST['website']) : ''; // Add website
    $deleted = isset($_POST['deleted']) ? sanitize_text_field($_POST['deleted']) : '';
    $state = isset($_POST['state']) ? sanitize_text_field($_POST['state']) : '';

    // Log incoming data for debugging
    error_log('Update request received. Data: ' . print_r(compact('shortcut_id', 'name', 'headline', 'description', 'website', 'deleted', 'state'), true));

    // Check if shortcut ID is missing
    if (!$shortcut_id) {
        error_log('Shortcut ID is missing.');
        wp_send_json_error('Shortcut ID is missing');
        return;
    }

    // Prepare the payload for the update request, including the website
    $payload = array(
        'name'        => $name,
        'headline'    => $headline,
        'description' => $description,
        'website'     => $website,  // Include the website in the payload
        'deleted'     => $deleted,
        'state'       => $state,
    );

    // Log the payload
    error_log('Payload being sent: ' . print_r($payload, true));

    // Construct the API endpoint path
    $api_path = '/shortcuts/' . $shortcut_id;

    // Log the API path
    error_log('API Path: ' . $api_path);

    // Make the API call using the PATCH method
    $response = make_sb_api_call($api_path, 'PATCH', [], $payload);

    // Log the response or error from the API call
    if (is_wp_error($response)) {
        error_log('Error updating shortcut: ' . $response->get_error_message());
        wp_send_json_error('Failed to update shortcut: ' . $response->get_error_message());
        return;
    }

    // Parse the response body
    $updated_shortcut = json_decode(wp_remote_retrieve_body($response), true);

    // Log the response from the API
    error_log('API Response: ' . print_r($updated_shortcut, true));

    // Check if the API response contains valid data
    if (!empty($updated_shortcut)) {
        wp_send_json_success('Shortcut updated successfully!');
    } else {
        error_log('Failed to update shortcut - invalid response from API.');
        wp_send_json_error('Failed to update shortcut');
    }
}

add_action('wp_ajax_fetch_shortcuts', 'shortcuts_hub_fetch_shortcuts');
add_action('wp_ajax_fetch_single_shortcut', 'shortcuts_hub_fetch_single_shortcut');
add_action('wp_ajax_update_shortcut', 'shortcuts_hub_update_shortcut');