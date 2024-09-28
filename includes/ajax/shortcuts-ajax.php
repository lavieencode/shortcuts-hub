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

    // Make the API call using the token
    $shortcuts = make_sb_api_call('/shortcuts', $query_params);

    if (!$shortcuts) {
        wp_send_json_error('Error fetching shortcuts from the API.');
        return;
    }

    wp_send_json_success($shortcuts);
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

    // Sanitize the inputs
    $shortcut_id = isset($_POST['id']) ? sanitize_text_field($_POST['id']) : '';
    $name = isset($_POST['name']) ? sanitize_text_field($_POST['name']) : '';
    $headline = isset($_POST['headline']) ? sanitize_text_field($_POST['headline']) : '';
    $description = isset($_POST['description']) ? sanitize_text_field($_POST['description']) : '';
    $website = isset($_POST['website']) ? sanitize_text_field($_POST['website']) : '';
    $state = isset($_POST['state']) ? sanitize_text_field($_POST['state']) : '';

    // Log the received shortcut ID and details
    if (empty($shortcut_id)) {
        error_log('Error: Shortcut ID is missing.');
        wp_send_json_error('Shortcut ID is missing');
        return;
    }
    error_log("Updating shortcut ID: $shortcut_id with name: $name, headline: $headline, description: $description, website: $website, state: $state");

    // Prepare the payload for the API request
    $payload = array(
        'name' => $name,
        'headline' => $headline,
        'description' => $description,
        'website' => $website,
        'state' => $state,
    );

    // Log the payload being sent
    error_log('Payload for updating shortcut: ' . print_r($payload, true));

    // Construct the endpoint using the reusable API function
    $endpoint = '/shortcuts/' . $shortcut_id;

    // Make the API call using the reusable function
    $response = make_sb_api_call($endpoint, $payload, 'POST');

    // Log the response or error from the API call
    if (is_wp_error($response)) {
        error_log('Error updating shortcut in API: ' . $response->get_error_message());
        wp_send_json_error('Failed to update shortcut: ' . $response->get_error_message());
        return;
    }

    // Log the raw response data
    error_log('Raw response from update shortcut API: ' . print_r($response, true));

    // Decode the API response
    $updated_shortcut = json_decode(wp_remote_retrieve_body($response), true);

    // Check if the update was successful
    if (!empty($updated_shortcut)) {
        error_log('Shortcut updated successfully: ' . print_r($updated_shortcut, true));
        wp_send_json_success('Shortcut updated successfully!');
    } else {
        error_log('Failed to update shortcut: Invalid or empty response.');
        wp_send_json_error('Failed to update shortcut');
    }
}

add_action('wp_ajax_fetch_shortcuts', 'shortcuts_hub_fetch_shortcuts');
add_action('wp_ajax_fetch_single_shortcut', 'shortcuts_hub_fetch_single_shortcut');
add_action('wp_ajax_update_shortcut', 'shortcuts_hub_update_shortcut');