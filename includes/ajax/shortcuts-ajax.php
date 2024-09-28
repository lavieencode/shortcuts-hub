<?php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

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