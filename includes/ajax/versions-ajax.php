<?php

if (!defined('ABSPATH')) {
    exit;
}

// Include the API functions
require_once dirname(__FILE__) . '/../sb-api.php';

function register_versions_ajax_handlers() {
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
}
add_action('init', 'register_versions_ajax_handlers');

// Fetch all versions with filtering options
function fetch_versions() {
    // DEBUG: Log incoming request
    sh_debug_log('Versions AJAX request received', array(
        'message' => 'Processing versions AJAX request',
        'source' => array(
            'file' => __FILE__,
            'line' => __LINE__,
            'function' => __FUNCTION__
        ),
        'data' => array(
            'post_data' => $_POST,
            'security' => isset($_POST['security']) ? $_POST['security'] : 'not set',
            'request_time' => time(),
            'request_uri' => $_SERVER['REQUEST_URI'],
            'http_referer' => isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'not set'
        ),
        'debug' => false
    ));

    // Verify nonce and log the verification
    $nonce = isset($_POST['security']) ? $_POST['security'] : '';
    $nonce_action = 'shortcuts_hub_versions_nonce';
    $nonce_verified = wp_verify_nonce($nonce, $nonce_action);
    
    sh_debug_log('Detailed nonce verification', array(
        'message' => 'Verifying nonce for versions AJAX',
        'source' => array(
            'file' => __FILE__,
            'line' => __LINE__,
            'function' => __FUNCTION__
        ),
        'data' => array(
            'nonce' => $nonce,
            'action' => $nonce_action,
            'verified' => $nonce_verified,
            'current_time' => time(),
            'user_id' => get_current_user_id()
        ),
        'debug' => false
    ));

    if (!$nonce_verified) {
        wp_send_json_error(array(
            'message' => 'Invalid security token',
            'debug_info' => array(
                'nonce' => $nonce,
                'action' => $nonce_action,
                'time' => time()
            )
        ));
        return;
    }

    // Check for either id or shortcut_id
    $shortcut_id = '';
    if (isset($_POST['id'])) {
        $shortcut_id = sanitize_text_field($_POST['id']);
    } elseif (isset($_POST['shortcut_id'])) {
        $shortcut_id = sanitize_text_field($_POST['shortcut_id']);
    }
    
    if (empty($shortcut_id)) {
        wp_send_json_error(array('message' => 'No shortcut ID provided'));
        return;
    }

    $endpoint = "/shortcuts/{$shortcut_id}/history";
    
    // Build query parameters for filtering
    $query_params = array();
    
    // Handle status filter
    if (isset($_POST['status']) && $_POST['status'] !== 'any' && $_POST['status'] !== '') {
        $query_params['state'] = sanitize_text_field($_POST['status']);
    }
    
    // Handle deleted filter
    if (isset($_POST['deleted']) && $_POST['deleted'] !== 'any' && $_POST['deleted'] !== '') {
        $query_params['deleted'] = sanitize_text_field($_POST['deleted']) === 'true' ? 1 : 0;
    }
    
    // Handle required update filter
    if (isset($_POST['required_update']) && $_POST['required_update'] !== 'any' && $_POST['required_update'] !== '') {
        $query_params['required'] = sanitize_text_field($_POST['required_update']) === 'true' ? 1 : 0;
    }
    
    // Handle search term
    if (isset($_POST['search_term']) && !empty($_POST['search_term'])) {
        $query_params['search'] = sanitize_text_field($_POST['search_term']);
    }
    
    // DEBUG: Track versions API call with filters
    sh_debug_log('Making filtered versions API call', array(
        'message' => 'Making versions API call with filters',
        'source' => array(
            'file' => __FILE__,
            'line' => __LINE__,
            'function' => __FUNCTION__
        ),
        'data' => array(
            'endpoint' => $endpoint,
            'query_params' => $query_params,
            'shortcut_id' => $shortcut_id,
            'raw_post_data' => array(
                'status' => isset($_POST['status']) ? $_POST['status'] : null,
                'deleted' => isset($_POST['deleted']) ? $_POST['deleted'] : null,
                'required_update' => isset($_POST['required_update']) ? $_POST['required_update'] : null,
                'search_term' => isset($_POST['search_term']) ? $_POST['search_term'] : null
            )
        ),
        'debug' => true
    ));

    $response = sb_api_call($endpoint, 'GET', $query_params);
    
    if (is_wp_error($response)) {
        wp_send_json_error(array('message' => $response->get_error_message()));
        return;
    }
    
    // DEBUG: Track successful API response
    sh_debug_log('Successful versions API response', array(
        'message' => 'Successfully received versions API response',
        'source' => array(
            'file' => __FILE__,
            'line' => __LINE__,
            'function' => __FUNCTION__
        ),
        'data' => array(
            'shortcut_id' => $shortcut_id,
            'response' => $response
        ),
        'debug' => false
    ));

    wp_send_json_success($response);
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
    // DEBUG: Log the create version request
    sh_debug_log('Create Version Request', array(
        'message' => 'Processing create version request',
        'source' => array(
            'file' => __FILE__,
            'line' => __LINE__,
            'function' => __FUNCTION__
        ),
        'data' => array(
            'post_data' => $_POST,
            'request_time' => time(),
            'request_uri' => $_SERVER['REQUEST_URI'],
            'http_referer' => isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'not set'
        ),
        'debug' => true
    ));

    if (!isset($_POST['security'])) {
        sh_debug_log('Create Version Security Error', array(
            'message' => 'No security token provided',
            'source' => array(
                'file' => __FILE__,
                'line' => __LINE__,
                'function' => __FUNCTION__
            ),
            'data' => array(
                'post_data' => $_POST
            ),
            'debug' => true
        ));
        wp_send_json_error(array('message' => 'No security token provided'));
        return;
    }

    if (!wp_verify_nonce($_POST['security'], 'shortcuts_hub_create_version_nonce')) {
        sh_debug_log('Create Version Nonce Error', array(
            'message' => 'Invalid security token',
            'source' => array(
                'file' => __FILE__,
                'line' => __LINE__,
                'function' => __FUNCTION__
            ),
            'data' => array(
                'provided_nonce' => $_POST['security'],
                'expected_action' => 'shortcuts_hub_create_version_nonce'
            ),
            'debug' => true
        ));
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

    // Debug log the sanitized data
    sh_debug_log('Create Version Sanitized Data', array(
        'message' => 'Sanitized version data before API call',
        'source' => array(
            'file' => __FILE__,
            'line' => __LINE__,
            'function' => __FUNCTION__
        ),
        'data' => array(
            'id' => $id,
            'version' => $version,
            'notes' => $notes,
            'url' => $url,
            'minimum_ios' => $minimum_ios,
            'minimum_mac' => $minimum_mac,
            'required' => $required,
            'version_state' => $version_state
        ),
        'debug' => true
    ));

    if (empty($id) || empty($version) || empty($url)) {
        sh_debug_log('Create Version Validation Error', array(
            'message' => 'Required fields missing',
            'source' => array(
                'file' => __FILE__,
                'line' => __LINE__,
                'function' => __FUNCTION__
            ),
            'data' => array(
                'id' => $id,
                'version' => $version,
                'url' => $url
            ),
            'debug' => true
        ));
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

    // Debug log the API call
    sh_debug_log('Create Version API Call', array(
        'message' => 'Making create version API call',
        'source' => array(
            'file' => __FILE__,
            'line' => __LINE__,
            'function' => __FUNCTION__
        ),
        'data' => array(
            'endpoint' => "/shortcuts/{$id}/version",
            'method' => 'POST',
            'request_data' => $data
        ),
        'debug' => true
    ));

    $response = sb_api_call("/shortcuts/{$id}/version", 'POST', array(), $data);

    if (is_wp_error($response)) {
        sh_debug_log('Create Version API Error', array(
            'message' => 'Error from Switchblade API',
            'source' => array(
                'file' => __FILE__,
                'line' => __LINE__,
                'function' => __FUNCTION__
            ),
            'data' => array(
                'error_code' => $response->get_error_code(),
                'error_message' => $response->get_error_message(),
                'error_data' => $response->get_error_data()
            ),
            'debug' => true
        ));
        wp_send_json_error(array('message' => 'Error creating version: ' . $response->get_error_message()));
        return;
    }

    // Debug log successful response
    sh_debug_log('Create Version Success', array(
        'message' => 'Successfully created version',
        'source' => array(
            'file' => __FILE__,
            'line' => __LINE__,
            'function' => __FUNCTION__
        ),
        'data' => array(
            'api_response' => $response
        ),
        'debug' => true
    ));

    wp_send_json_success($response);
}

// Update an existing version
function update_version() {
    if (!isset($_POST['security'])) {
        wp_send_json_error(['message' => 'No security token provided']);
        return;
    }

    if (!wp_verify_nonce($_POST['security'], 'shortcuts_hub_versions_nonce')) {
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
    // DEBUG: Log incoming request
    sh_debug_log('Version delete request received', array(
        'message' => 'Processing version delete request',
        'source' => array(
            'file' => __FILE__,
            'line' => __LINE__,
            'function' => __FUNCTION__
        ),
        'data' => array(
            'request_method' => $_SERVER['REQUEST_METHOD'],
            'post_data' => $_POST,
            'get_data' => $_GET,
            'security' => isset($_POST['security']) ? $_POST['security'] : 'not set',
            'request_time' => time(),
            'request_uri' => $_SERVER['REQUEST_URI'],
            'http_referer' => isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'not set',
            'raw_input' => file_get_contents('php://input')
        ),
        'debug' => false
    ));

    // Verify nonce and log the verification
    $nonce = isset($_POST['security']) ? $_POST['security'] : '';
    $nonce_action = 'shortcuts_hub_versions_nonce';
    $nonce_verified = wp_verify_nonce($nonce, $nonce_action);
    
    sh_debug_log('Verifying nonce', array(
        'message' => 'Checking security nonce',
        'source' => array(
            'file' => __FILE__,
            'line' => __LINE__,
            'function' => __FUNCTION__
        ),
        'data' => array(
            'provided_nonce' => $nonce,
            'nonce_key' => $nonce_action,
            'post_keys' => array_keys($_POST)
        ),
        'debug' => false
    ));

    if (!$nonce_verified) {
        wp_send_json_error(array('message' => 'Invalid security token'));
        return;
    }

    // Get and sanitize parameters
    $shortcut_id = isset($_POST['shortcut_id']) ? sanitize_text_field($_POST['shortcut_id']) : '';
    $version_id = isset($_POST['version_id']) ? sanitize_text_field($_POST['version_id']) : '';

    sh_debug_log('Sanitized request parameters', array(
        'message' => 'Checking request parameters after sanitization',
        'source' => array(
            'file' => __FILE__,
            'line' => __LINE__,
            'function' => __FUNCTION__
        ),
        'data' => array(
            'raw_shortcut_id' => isset($_POST['shortcut_id']) ? $_POST['shortcut_id'] : null,
            'raw_version_id' => isset($_POST['version_id']) ? $_POST['version_id'] : null,
            'sanitized_shortcut_id' => $shortcut_id,
            'sanitized_version_id' => $version_id,
            'post_data_types' => array(
                'shortcut_id' => gettype($shortcut_id),
                'version_id' => gettype($version_id)
            )
        ),
        'debug' => false
    ));

    if (empty($shortcut_id) || empty($version_id)) {
        wp_send_json_error(array('message' => 'Missing required parameters'));
        return;
    }

    // Construct the endpoint
    $endpoint = "/shortcuts/{$shortcut_id}/version/{$version_id}";

    sh_debug_log('Making version delete API call', array(
        'message' => 'Calling version delete endpoint',
        'source' => array(
            'file' => __FILE__,
            'line' => __LINE__,
            'function' => __FUNCTION__
        ),
        'data' => array(
            'shortcut_id' => $shortcut_id,
            'version_id' => $version_id,
            'endpoint' => $endpoint,
            'method' => 'DELETE'
        ),
        'debug' => false
    ));

    // Make the API call
    $response = sb_api_call($endpoint, 'DELETE');

    sh_debug_log('API response received', array(
        'message' => 'Received response from delete endpoint',
        'source' => array(
            'file' => __FILE__,
            'line' => __LINE__,
            'function' => __FUNCTION__
        ),
        'data' => array(
            'response' => $response,
            'is_wp_error' => is_wp_error($response),
            'error_message' => is_wp_error($response) ? $response->get_error_message() : null
        ),
        'debug' => false
    ));

    if (is_wp_error($response)) {
        wp_send_json_error(array('message' => 'Error deleting version: ' . $response->get_error_message()));
        return;
    }

    wp_send_json_success(array('message' => 'Version deleted successfully'));
}