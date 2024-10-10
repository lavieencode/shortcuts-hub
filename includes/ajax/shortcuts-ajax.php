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
    if ($filter_status !== '') {
        $query_params['state'] = $filter_status;
    }
    if ($filter_deleted !== '') {
        $query_params['deleted'] = $filter_deleted;
    }
    if ($search_term !== '') {
        $query_params['search'] = $search_term;
    }

    // Log the query parameters being sent
    error_log('Query parameters for fetching shortcuts: ' . print_r($query_params, true));

    // Make the API call using the token
    $shortcuts = make_sb_api_call('/shortcuts', 'GET', $query_params);

    // Handle the case where no shortcuts are returned or there's an error
    if (is_wp_error($shortcuts)) {
        wp_send_json_error('Error fetching shortcuts from the API.');
        return;
    }

    if (empty($shortcuts)) {
        wp_send_json_error('No shortcuts found.');
        return;
    }

    // Ensure that the response has the correct structure
    if (isset($shortcuts['shortcuts'])) {
        wp_send_json_success($shortcuts['shortcuts']);
    } else {
        wp_send_json_error('Invalid response format from the API.');
    }
}

function shortcuts_hub_fetch_single_shortcut() {
    check_ajax_referer('shortcuts_hub_nonce', 'security');

    // Retrieve and sanitize the shortcut ID
    $shortcut_id = isset($_POST['id']) ? sanitize_text_field($_POST['id']) : '';

    if (empty($shortcut_id)) {
        wp_send_json_error('Shortcut ID is missing');
        return;
    }

    // Make the API call using the reusable function
    $shortcut = make_sb_api_call('/shortcuts/' . $shortcut_id);

    if (is_wp_error($shortcut)) {
        wp_send_json_error($shortcut->get_error_message());
        return;
    }

    if (!empty($shortcut)) {
        wp_send_json_success($shortcut);
    } else {
        wp_send_json_error('Shortcut not found');
    }
}

function shortcuts_hub_update_shortcut() {
    check_ajax_referer('shortcuts_hub_nonce', 'security');

    $shortcut_id = isset($_POST['id']) ? sanitize_text_field($_POST['id']) : '';
    $name = isset($_POST['name']) ? sanitize_text_field($_POST['name']) : '';
    $headline = isset($_POST['headline']) ? sanitize_text_field($_POST['headline']) : '';
    $description = isset($_POST['description']) ? sanitize_text_field($_POST['description']) : '';
    $deleted = isset($_POST['deleted']) ? sanitize_text_field($_POST['deleted']) : '';
    $state = isset($_POST['state']) ? sanitize_text_field($_POST['state']) : '';
    $website = isset($_POST['website']) ? sanitize_text_field($_POST['website']) : '';

    if (!$shortcut_id) {
        wp_send_json_error('Shortcut ID is missing');
        return;
    }

    // Payload for the API request
    $payload = array(
        'name' => $name,
        'headline' => $headline,
        'description' => $description,
        'deleted' => $deleted,
        'state' => $state,
        'website' => $website,
    );

    // Make the API call using PATCH
    $response = make_sb_api_call('/shortcuts/' . $shortcut_id, 'PATCH', [], $payload);

    if (is_wp_error($response)) {
        wp_send_json_error('Failed to update shortcut: ' . $response->get_error_message());
    }

    if (isset($response['shortcut'])) {
        wp_send_json_success($response['shortcut']);
    } else {
        wp_send_json_error('Failed to update shortcut - invalid response structure');
    }
}

add_action('wp_ajax_fetch_shortcuts', 'shortcuts_hub_fetch_shortcuts');
add_action('wp_ajax_fetch_single_shortcut', 'shortcuts_hub_fetch_single_shortcut');
add_action('wp_ajax_update_shortcut', 'shortcuts_hub_update_shortcut');