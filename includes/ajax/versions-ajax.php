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
add_action('wp_ajax_delete_version', 'delete_version');

// Fetch all versions with filtering options
function fetch_versions() {
    // Verify nonce
    $nonce = isset($_POST['security']) ? $_POST['security'] : '';
    $nonce_action = 'fetch_versions_nonce';
    $verified = check_ajax_referer('fetch_versions_nonce', 'security', false);

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

        if (!wp_verify_nonce($_POST['nonce'], 'fetch_latest_version_nonce')) {
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
    // DEBUG: Log fetch version request
    sh_debug_log('Fetch Version Request', array(
        'message' => 'Processing fetch version request',
        'source' => array(
            'file' => 'versions-ajax.php',
            'line' => __LINE__,
            'function' => 'fetch_version'
        ),
        'data' => array(
            'post_data' => $_POST
        ),
        'debug' => true
    ));

    if (!isset($_POST['security'])) {
        wp_send_json_error(array('message' => 'No security token provided'));
        return;
    }

    if (!check_ajax_referer('fetch_version_nonce', 'security', false)) {
        wp_send_json_error(array('message' => 'Invalid security token'));
        return;
    }

    $id = isset($_POST['id']) ? sanitize_text_field($_POST['id']) : '';
    $version_id = isset($_POST['version_id']) ? sanitize_text_field($_POST['version_id']) : '';
    $latest = isset($_POST['latest']) ? filter_var($_POST['latest'], FILTER_VALIDATE_BOOLEAN) : false;

    if (empty($id) || (!$latest && empty($version_id))) {
        wp_send_json_error(array('message' => 'Shortcut ID or version ID is missing'));
        return;
    }

    if ($latest) {
        $endpoint = "shortcuts/{$id}/version/latest";
    } else {
        $endpoint = "shortcuts/{$id}/version/{$version_id}";
    }

    $response = sb_api_call($endpoint, 'GET');

    if (is_wp_error($response)) {
        wp_send_json_error(array('message' => 'Error fetching version: ' . $response->get_error_message()));
        return;
    }

    // DEBUG: Log successful response
    sh_debug_log('Fetch Version Response', array(
        'message' => 'Successfully fetched version data',
        'source' => array(
            'file' => 'versions-ajax.php',
            'line' => __LINE__,
            'function' => 'fetch_version'
        ),
        'data' => array(
            'response' => $response
        ),
        'debug' => true
    ));

    wp_send_json_success($response);
}

// Create a new version
function create_version() {
    if (!isset($_POST['security'])) {
        wp_send_json_error(array('message' => 'No security token provided'));
        return;
    }

    if (!wp_verify_nonce($_POST['security'], 'create_version_nonce')) {
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
    // DEBUG: Log update version request
    sh_debug_log('Update Version Request', array(
        'message' => 'Processing version update request',
        'source' => array(
            'file' => 'versions-ajax.php',
            'line' => __LINE__,
            'function' => 'update_version'
        ),
        'data' => array(
            'post_data' => $_POST,
            'raw_input' => file_get_contents('php://input')
        ),
        'debug' => true
    ));

    // Verify nonce
    if (!isset($_POST['security']) || !wp_verify_nonce($_POST['security'], 'update_version_nonce')) {
        wp_send_json_error(array('message' => 'Invalid security token'));
        return;
    }

    // Get and validate required parameters
    $id = isset($_POST['id']) ? sanitize_text_field($_POST['id']) : '';
    $version_id = isset($_POST['version_id']) ? sanitize_text_field($_POST['version_id']) : '';
    $version_data = isset($_POST['version_data']) ? $_POST['version_data'] : array();

    // DEBUG: Log sanitized input
    sh_debug_log('Update Version Parameters', array(
        'message' => 'Sanitized update parameters',
        'source' => array(
            'file' => 'versions-ajax.php',
            'line' => __LINE__,
            'function' => 'update_version'
        ),
        'data' => array(
            'id' => $id,
            'version_id' => $version_id,
            'version_data' => $version_data
        ),
        'debug' => true
    ));

    if (empty($id)) {
        wp_send_json_error(array('message' => 'Missing shortcut ID'));
        return;
    }

    if (empty($version_id)) {
        wp_send_json_error(array('message' => 'Missing version ID'));
        return;
    }

    // Validate version data
    if (!is_array($version_data) || empty($version_data)) {
        wp_send_json_error(array('message' => 'Invalid or missing version data'));
        return;
    }

    // Prepare data for API call
    $data = array(
        'version' => isset($version_data['version']) ? sanitize_text_field($version_data['version']) : '',
        'notes' => isset($version_data['notes']) ? sanitize_text_field($version_data['notes']) : '',
        'url' => isset($version_data['url']) ? esc_url_raw($version_data['url']) : '',
        'minimumiOS' => isset($version_data['minimumiOS']) ? sanitize_text_field($version_data['minimumiOS']) : '',
        'minimumMac' => isset($version_data['minimumMac']) ? sanitize_text_field($version_data['minimumMac']) : '',
        'required' => isset($version_data['required']) ? filter_var($version_data['required'], FILTER_VALIDATE_BOOLEAN) : false,
        'state' => isset($version_data['state']) ? intval($version_data['state']) : 1,
        'deleted' => isset($version_data['deleted']) ? filter_var($version_data['deleted'], FILTER_VALIDATE_BOOLEAN) : false
    );

    // Set endpoint for update
    $endpoint = "/shortcuts/{$id}/version/{$version_id}";

    // DEBUG: Log API request details
    sh_debug_log('Update Version API Request', array(
        'message' => 'Preparing API request data',
        'source' => array(
            'file' => 'versions-ajax.php',
            'line' => 'update_version',
            'function' => 'update_version'
        ),
        'data' => array(
            'endpoint' => $endpoint,
            'method' => 'PATCH',
            'sanitized_data' => $data,
            'original_data' => $version_data
        ),
        'debug' => true
    ));

    // Make API call
    $response = sb_api_call($endpoint, 'PATCH', array(), $data);

    // DEBUG: Log API response
    sh_debug_log('Update Version API Response', array(
        'message' => 'Response from API call',
        'source' => array(
            'file' => 'versions-ajax.php',
            'line' => 'update_version',
            'function' => 'update_version'
        ),
        'data' => array(
            'response' => $response,
            'is_wp_error' => is_wp_error($response),
            'error_message' => is_wp_error($response) ? $response->get_error_message() : null,
            'request_data' => array(
                'endpoint' => $endpoint,
                'method' => 'PATCH',
                'data' => $data
            )
        ),
        'debug' => true
    ));

    if (is_wp_error($response)) {
        wp_send_json_error(array('message' => 'Error updating version: ' . $response->get_error_message()));
        return;
    }

    // Ensure we have a properly formatted response
    if (isset($response['version'])) {
        wp_send_json_success(array('version' => $response['version']));
    } else {
        wp_send_json_error(array('message' => 'Invalid response format from API'));
    }
}

// Delete or toggle delete state of a version
function delete_version() {
    // Verify nonce and log the verification
    $nonce = isset($_POST['security']) ? $_POST['security'] : '';
    $nonce_action = 'delete_version_nonce';
    $nonce_verified = wp_verify_nonce($nonce, $nonce_action);

    if (!$nonce_verified) {
        // DEBUG: Log nonce verification failure
        sh_debug_log('Version Delete Nonce Error', 'Nonce verification failed for version delete', array(
            'file' => 'versions-ajax.php',
            'line' => __FUNCTION__,
            'function' => __FUNCTION__
        ), array(
            'nonce' => $nonce,
            'nonce_action' => $nonce_action
        ));
        wp_send_json_error(array('message' => 'Security check failed'));
        return;
    }

    // Get and validate required parameters
    $shortcut_id = isset($_POST['shortcut_id']) ? sanitize_text_field($_POST['shortcut_id']) : '';
    $version_number = isset($_POST['version_id']) ? sanitize_text_field($_POST['version_id']) : '';
    $delete_type = isset($_POST['delete_type']) ? sanitize_text_field($_POST['delete_type']) : 'toggle';
    $source = isset($_POST['source']) ? sanitize_text_field($_POST['source']) : 'switchblade';
    $is_restore = isset($_POST['is_restore']) ? filter_var($_POST['is_restore'], FILTER_VALIDATE_BOOLEAN) : false;

    // DEBUG: Log received parameters
    sh_debug_log('Version Delete Parameters', 'Parameters received for version delete', array(
        'file' => 'versions-ajax.php',
        'line' => __FUNCTION__,
        'function' => __FUNCTION__
    ), array(
        'shortcut_id' => $shortcut_id,
        'version_number' => $version_number,
        'delete_type' => $delete_type,
        'source' => $source,
        'is_restore' => $is_restore,
        'raw_post' => $_POST
    ));

    // Validate required parameters
    if (empty($shortcut_id) || empty($version_number)) {
        // DEBUG: Log missing parameters
        sh_debug_log('Version Delete Parameter Error', 'Missing required parameters for version delete', array(
            'file' => 'versions-ajax.php',
            'line' => __FUNCTION__,
            'function' => __FUNCTION__
        ), array(
            'shortcut_id' => $shortcut_id,
            'version_number' => $version_number,
            'raw_post' => $_POST
        ));
        wp_send_json_error(array('message' => 'Missing required parameters'));
        return;
    }

    // Handle different deletion types
    switch ($delete_type) {
        case 'permanent':
            // Handle Switchblade permanent deletion
            $endpoint = "/shortcuts/{$shortcut_id}/version/{$version_number}";
            $response = sb_api_call($endpoint, 'DELETE');
            
            // DEBUG: Log API response for permanent deletion
            sh_debug_log('Version Delete API Response', 'Response from permanent delete API call', array(
                'file' => 'versions-ajax.php',
                'line' => __FUNCTION__,
                'function' => __FUNCTION__
            ), array(
                'endpoint' => $endpoint,
                'method' => 'DELETE',
                'response' => $response
            ));
            
            if (is_wp_error($response)) {
                wp_send_json_error(array('message' => 'Error permanently deleting version: ' . $response->get_error_message()));
                return;
            }
            break;

        case 'toggle':
            // Handle Switchblade toggle
            $endpoint = "/shortcuts/{$shortcut_id}/version/{$version_number}";
            $response = sb_api_call($endpoint, 'PATCH', [], ['deleted' => !$is_restore]);
            
            // DEBUG: Log API response for toggle
            sh_debug_log('Version Delete API Response', 'Response from toggle delete API call', array(
                'file' => 'versions-ajax.php',
                'line' => __FUNCTION__,
                'function' => __FUNCTION__
            ), array(
                'endpoint' => $endpoint,
                'method' => 'PATCH',
                'params' => ['deleted' => !$is_restore],
                'response' => $response,
                'raw_response' => is_wp_error($response) ? $response->get_error_message() : $response
            ));
            
            if (is_wp_error($response)) {
                wp_send_json_error(array('message' => 'Error toggling version state: ' . $response->get_error_message()));
                return;
            }
            break;

        default:
            wp_send_json_error(array('message' => 'Invalid delete type specified'));
            return;
    }

    wp_send_json_success(array('message' => 'Version operation completed successfully'));
}