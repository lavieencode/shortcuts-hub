<?php

if (!defined('ABSPATH')) {
    exit;
}

// Include required files
require_once dirname(__FILE__) . '/../settings.php';
require_once dirname(__FILE__) . '/../sb-api.php';

function fetch_shortcuts() {
    if (is_user_logged_in() && (!isset($_POST['security']) || !wp_verify_nonce($_POST['security'], 'shortcuts_hub_nonce'))) {
        wp_send_json_error(['message' => 'Invalid security token']);
        return;
    }

    $filter = isset($_POST['filter']) ? sanitize_text_field($_POST['filter']) : '';
    $source = isset($_POST['source']) ? sanitize_text_field($_POST['source']) : 'WP';
    $status = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : '';
    $deleted = isset($_POST['deleted']) ? $_POST['deleted'] : null;

    sh_debug_log('Fetch Shortcuts - Request Parameters', [
        'filter' => $filter,
        'source' => $source,
        'status' => $status,
        'deleted' => $deleted
    ]);

    // Handle Switchblade source
    if ($source === 'SB') {
        $params = array();
        
        // Add filter if present
        if (!empty($filter)) {
            $params['filter'] = $filter;
        }

        // Add status filter for drafts
        if ($status !== '' && $status !== 'any') {
            $params['draft'] = $status === '1';
        }

        // Add deleted filter
        if ($deleted !== null && $deleted !== 'any') {
            $params['deleted'] = $deleted === true || $deleted === 'true';
        }

        sh_debug_log('Fetch Shortcuts - Switchblade API Request', [
            'params' => $params
        ]);

        $sb_response = sb_api_call('shortcuts', 'GET', $params);
        
        sh_debug_log('Fetch Shortcuts - Switchblade API Response', [
            'response' => $sb_response
        ]);

        if (is_wp_error($sb_response)) {
            $error_code = $sb_response->get_error_code();
            $error_message = $sb_response->get_error_message();
            
            sh_debug_log('Fetch Shortcuts - Switchblade API Error', [
                'error_code' => $error_code,
                'error_message' => $error_message
            ]);

            // Handle specific error cases
            switch ($error_code) {
                case 'auth_failed':
                case 'token_request_failed':
                case 'login_failed':
                    wp_send_json_error([
                        'message' => 'Authentication failed. Please try refreshing the page.',
                        'error' => $error_message
                    ]);
                    break;
                case 'rate_limit':
                    wp_send_json_error([
                        'message' => 'Rate limit exceeded. Please try again later.',
                        'error' => $error_message
                    ]);
                    break;
                default:
                    wp_send_json_error([
                        'message' => 'Error fetching from Switchblade: ' . $error_message,
                        'error' => $error_code
                    ]);
            }
            return;
        }

        // If we got a token refresh message, try the call again
        if (isset($sb_response['message']) && strpos($sb_response['message'], 'Starting token refresh') !== false) {
            $sb_response = sb_api_call('shortcuts', 'GET', $params);
            if (is_wp_error($sb_response)) {
                wp_send_json_error(['message' => 'Error fetching from Switchblade after token refresh']);
                return;
            }
        }

        wp_send_json_success($sb_response);
        return;
    }

    // Default WordPress handling
    $args = array(
        'post_type' => 'shortcut',
        'posts_per_page' => -1,
        'post_status' => array('publish', 'draft', 'trash')
    );

    if (!empty($filter)) {
        $args['s'] = $filter;
    }

    // Handle deleted filter using post status
    if ($deleted !== null && $deleted !== '' && $deleted !== 'any') {
        $args['post_status'] = ($deleted === true || $deleted === 'true') ? 'trash' : array('publish', 'draft');
    }

    // Handle draft filter using post status
    if ($status !== '' && $status !== 'any') {
        $args['post_status'] = array($status);
        // If we're not specifically filtering for non-deleted items, include trash
        if ($deleted === null || $deleted === '' || $deleted === 'any' || $deleted === true || $deleted === 'true') {
            $args['post_status'][] = 'trash';
        }
    }

    sh_debug_log('Fetch Shortcuts - WordPress Query', [
        'args' => $args
    ]);

    $shortcuts = get_posts($args);

    sh_debug_log('Fetch Shortcuts - WordPress Results', [
        'shortcuts' => $shortcuts
    ]);

    $data = array();

    foreach ($shortcuts as $shortcut) {
        $is_trashed = $shortcut->post_status === 'trash';
        $sb_id = get_post_meta($shortcut->ID, 'sb_id', true);
        
        $shortcut_data = array(
            'post_id' => $shortcut->ID,
            'headline' => get_post_meta($shortcut->ID, '_shortcut_headline', true),
            'color' => get_post_meta($shortcut->ID, '_shortcut_color', true),
            'icon' => get_post_meta($shortcut->ID, '_shortcut_icon', true),
            'input' => get_post_meta($shortcut->ID, '_shortcut_input', true),
            'result' => get_post_meta($shortcut->ID, '_shortcut_result', true),
            'sb_id' => $sb_id,
            'post_date' => $shortcut->post_date,
            'post_status' => $shortcut->post_status
        );

        $data[] = $shortcut_data;
    }

    wp_send_json_success($data);
}

function fetch_shortcut() {
    check_ajax_referer('shortcuts_hub_nonce', 'security');
    
    $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
    $source = isset($_POST['source']) ? sanitize_text_field($_POST['source']) : 'WP';

    if ($source === 'WP') {
        $shortcut = get_post($post_id);
        if (!$shortcut || $shortcut->post_type !== 'shortcut') {
            wp_send_json_error(array('message' => "Shortcut with ID $post_id not found in WordPress."));
            return;
        }

        // Get current permalink
        $permalink = get_permalink($shortcut);

        // Get all metadata in a flat structure
        $data = array(
            'post_id' => $shortcut->ID,
            'name' => $shortcut->post_title,
            'description' => $shortcut->post_content,
            'headline' => get_post_meta($post_id, '_shortcut_headline', true),
            'website' => $permalink,
            'sb_id' => get_post_meta($post_id, 'sb_id', true),
            'color' => get_post_meta($post_id, '_shortcut_color', true),
            'icon' => get_post_meta($post_id, '_shortcut_icon', true),
            'input' => get_post_meta($post_id, '_shortcut_input', true),
            'result' => get_post_meta($post_id, '_shortcut_result', true),
            'actions' => get_post_meta($post_id, '_shortcut_actions', true),
            'state' => $shortcut->post_status === 'publish' ? 'publish' : 'draft',
            'deleted' => $shortcut->post_status === 'trash'
        );

        wp_send_json_success($data);
    } else if ($source === 'SB') {
        $response = sb_api_call('shortcuts/' . $post_id);
        
        if (is_wp_error($response)) {
            wp_send_json_error(array('message' => "Failed to fetch shortcut $post_id from Switchblade: " . $response->get_error_message()));
            return;
        }

        wp_send_json_success($response);
    } else {
        wp_send_json_error(array('message' => "Invalid source '$source' specified. Must be either 'WP' or 'SB'."));
    }
}

function create_shortcut() {
    // Debug: Initial AJAX request received
    sh_debug_log('5. Create Shortcut - Server Request Received', [
        'post_data' => $_POST,
        'request_method' => $_SERVER['REQUEST_METHOD']
    ]);

    // Only verify nonce for logged-in users
    if (is_user_logged_in()) {
        if (!isset($_POST['security'])) {
            // Debug: Security token missing
            sh_debug_log('Error - Security Token Missing', [
                'user_logged_in' => true
            ]);
            wp_send_json_error(['message' => 'No security token provided for logged-in user']);
            return;
        }

        if (!wp_verify_nonce($_POST['security'], 'shortcuts_hub_nonce')) {
            // Debug: Invalid security token
            sh_debug_log('Error - Invalid Security Token', [
                'provided_token' => $_POST['security']
            ]);
            wp_send_json_error(['message' => 'Invalid security token']);
            return;
        }
    }

    $shortcut_data = isset($_POST['shortcut_data']) ? $_POST['shortcut_data'] : [];

    // Debug: Validate required fields
    sh_debug_log('6. Create Shortcut - Validating Fields', [
        'shortcut_data' => $shortcut_data
    ]);

    if (empty($shortcut_data['name']) || empty($shortcut_data['headline'])) {
        // Debug: Required fields missing
        sh_debug_log('Error - Required Fields Missing', [
            'name_exists' => !empty($shortcut_data['name']),
            'headline_exists' => !empty($shortcut_data['headline'])
        ]);
        wp_send_json_error(array('message' => 'Required fields are missing.'));
        return;
    }

    $state = isset($shortcut_data['state']) && $shortcut_data['state'] === 'draft' ? 1 : 0;

    // Debug: Preparing WordPress post data
    sh_debug_log('7. Create Shortcut - Preparing WP Post', [
        'post_title' => $shortcut_data['name'],
        'post_status' => $state === 1 ? 'draft' : 'publish'
    ]);

    $post_data = array(
        'post_title'   => sanitize_text_field($shortcut_data['name']),
        'post_content' => sanitize_textarea_field($shortcut_data['description']),
        'post_status'  => $state === 1 ? 'draft' : 'publish',
        'post_type'    => 'shortcut',
    );

    $post_id = wp_insert_post($post_data);

    if (!is_wp_error($post_id)) {
        // Debug: WordPress post created successfully
        sh_debug_log('8. Create Shortcut - WP Post Created', [
            'post_id' => $post_id
        ]);

        update_post_meta($post_id, '_shortcut_headline', sanitize_text_field($shortcut_data['headline']));
        update_post_meta($post_id, '_shortcut_color', isset($shortcut_data['color']) ? sanitize_hex_color($shortcut_data['color']) : '');
        update_post_meta($post_id, '_shortcut_icon', isset($shortcut_data['icon']) ? sanitize_text_field($shortcut_data['icon']) : '');
        update_post_meta($post_id, '_shortcut_input', isset($shortcut_data['input']) ? sanitize_text_field($shortcut_data['input']) : '');
        update_post_meta($post_id, '_shortcut_result', isset($shortcut_data['result']) ? sanitize_text_field($shortcut_data['result']) : '');
        
        // Set the website URL to the WordPress edit page for this shortcut
        $website_url = get_site_url() . '/wp-admin/admin.php?page=edit-shortcut&id=' . $post_id;
        update_post_meta($post_id, '_shortcut_website', esc_url_raw($website_url));
        
        // Debug: Meta fields updated
        sh_debug_log('9. Create Shortcut - WP Meta Updated', [
            'post_id' => $post_id,
            'meta_fields' => [
                'headline' => get_post_meta($post_id, '_shortcut_headline', true),
                'color' => get_post_meta($post_id, '_shortcut_color', true),
                'icon' => get_post_meta($post_id, '_shortcut_icon', true),
                'input' => get_post_meta($post_id, '_shortcut_input', true),
                'result' => get_post_meta($post_id, '_shortcut_result', true),
                'website' => get_post_meta($post_id, '_shortcut_website', true)
            ]
        ]);
        
        // Create the shortcut in Switchblade
        $sb_data = array(
            'name' => sanitize_text_field($shortcut_data['name']),
            'headline' => sanitize_text_field($shortcut_data['headline']),
            'description' => sanitize_textarea_field($shortcut_data['description']),
            'input' => isset($shortcut_data['input']) ? sanitize_text_field($shortcut_data['input']) : '',
            'result' => isset($shortcut_data['result']) ? sanitize_text_field($shortcut_data['result']) : '',
            'state' => $state,
            'website' => $website_url
        );

        // Debug: Creating shortcut in Switchblade
        sh_debug_log('10. Create Shortcut - Switchblade Request', [
            'sb_data' => $sb_data
        ]);

        // Get settings
        $settings = get_shortcuts_hub_settings();
        $sb_response = sb_api_call('shortcuts', 'POST', [], $sb_data);
        
        // Debug: Switchblade response received
        sh_debug_log('11. Create Shortcut - Switchblade Response', [
            'response' => $sb_response
        ]);
        
        if (is_wp_error($sb_response)) {
            // Debug: Switchblade error
            sh_debug_log('Error - Switchblade Creation Failed', [
                'error' => $sb_response->get_error_message()
            ]);
            wp_send_json_error(array('message' => 'Failed to create shortcut in Switchblade: ' . $sb_response->get_error_message()));
            return;
        }

        $id = isset($sb_response['shortcut']['id']) ? $sb_response['shortcut']['id'] : null;

        if (!$id) {
            // Debug: No Switchblade ID returned
            sh_debug_log('Error - No Switchblade ID', [
                'response' => $sb_response
            ]);
            wp_send_json_error(array('message' => 'Switchblade did not return a valid ID.'));
            return;
        }

        update_post_meta($post_id, 'sb_id', sanitize_text_field($id));

        // Debug: Creation completed successfully
        sh_debug_log('12. Create Shortcut - Complete', [
            'post_id' => $post_id,
            'sb_id' => $id,
            'website_url' => $website_url
        ]);

        wp_send_json_success(array('message' => 'Shortcut created successfully.', 'post_id' => $post_id, 'sb_id' => $id));
    } else {
        // Debug: WordPress post creation failed
        sh_debug_log('Error - WP Post Creation Failed', [
            'error' => $post_id->get_error_message()
        ]);
        wp_send_json_error(array('message' => 'Failed to create shortcut in WordPress.'));
    }
}

function update_shortcut() {
    // Security check
    check_ajax_referer('shortcuts_hub_nonce', 'security');
    if (!current_user_can('edit_posts')) {
        $error_response = ['message' => 'Permission denied'];
        wp_send_json_error($error_response);
        return;
    }

    $shortcut_data = $_POST['shortcut_data'];
    $post_id = !empty($shortcut_data['post_id']) ? intval($shortcut_data['post_id']) : 0;
    $sb_id = !empty($shortcut_data['sb_id']) ? sanitize_text_field($shortcut_data['sb_id']) : '';
    
    if (empty($shortcut_data['name'])) {
        $error_response = ['message' => 'Name is required'];
        wp_send_json_error($error_response);
        return;
    }

    $post_data = array(
        'ID' => $post_id,
        'post_title' => sanitize_text_field($shortcut_data['name']),
        'post_content' => wp_kses_post($shortcut_data['description']),
        'post_status' => $shortcut_data['state'] === 'publish' ? 'publish' : 'draft'
    );

    $updated_post = wp_update_post($post_data, true);
    if (is_wp_error($updated_post)) {
        $error_response = ['message' => 'Error updating WordPress post: ' . $updated_post->get_error_message()];
        wp_send_json_error($error_response);
        return;
    }

    update_post_meta($post_id, '_shortcut_headline', sanitize_text_field($shortcut_data['headline']));
    update_post_meta($post_id, '_shortcut_color', sanitize_text_field($shortcut_data['color']));
    update_post_meta($post_id, '_shortcut_icon', sanitize_text_field($shortcut_data['icon']));
    update_post_meta($post_id, '_shortcut_input', wp_kses_post($shortcut_data['input']));
    update_post_meta($post_id, '_shortcut_result', wp_kses_post($shortcut_data['result']));

    // Store Switchblade ID in WordPress meta
    if (!empty($sb_id)) {
        update_post_meta($post_id, 'sb_id', $sb_id);
    }
    
    // Get stored Switchblade ID if not provided
    if (empty($sb_id)) {
        $sb_id = get_post_meta($post_id, 'sb_id', true);
    }

    $permalink = get_permalink($post_id);
    $sb_data = array(
        'name' => sanitize_text_field($shortcut_data['name']),
        'headline' => sanitize_text_field($shortcut_data['headline']),
        'description' => wp_kses_post($shortcut_data['description']),
        'website' => $permalink,
        'state' => $shortcut_data['state'] === 'publish' ? 0 : 1
    );

    $response = sb_api_call('shortcuts/' . $sb_id, 'PATCH', [], $sb_data);

    if (is_wp_error($response)) {
        $error_response = ['message' => 'Error updating Switchblade: ' . $response->get_error_message()];
        wp_send_json_error($error_response);
        return;
    }

    if (isset($response['error'])) {
        $error_response = ['message' => 'Switchblade API error: ' . $response['error']];
        wp_send_json_error($error_response);
        return;
    }

    $success_response = [
        'message' => 'Shortcut updated successfully',
        'post_id' => $post_id,
        'sb_id' => $sb_id,
        'permalink' => $permalink
    ];

    wp_send_json_success($success_response);
}

function toggle_draft() {
    if (!is_user_logged_in() || !check_ajax_referer('shortcuts_hub_nonce', 'security', false)) {
        wp_send_json_error(['message' => 'Invalid security token']);
        return;
    }

    $post_id = intval($_POST['post_id']);
    $shortcut_data = isset($_POST['shortcut_data']) ? $_POST['shortcut_data'] : null;
    $sb_id = get_post_meta($post_id, 'sb_id', true);
    $current_status = get_post_status($post_id);
    $new_status = $current_status === 'draft' ? 'publish' : 'draft';

    // Try to update Switchblade first
    if (!empty($sb_id)) {
        $is_draft = $new_status === 'draft';
        
        // Prepare Switchblade data with state and other required fields
        $sb_data = array(
            'name' => sanitize_text_field($shortcut_data['name']),
            'headline' => sanitize_text_field($shortcut_data['headline']),
            'description' => sanitize_textarea_field($shortcut_data['description']),
            'website' => get_permalink($post_id),
            'state' => $is_draft ? 1 : 0,  // 1 for draft, 0 for published
            'deleted' => false
        );
        
        // First get auth token
        $settings = get_shortcuts_hub_settings();
        $sb_api_url = $settings['sb_url'];
        $auth_response = wp_remote_post(
            trailingslashit($sb_api_url) . 'auth/login',
            array(
                'headers' => array('Content-Type' => 'application/json'),
                'body' => json_encode(array(
                    'username' => $settings['sb_username'],
                    'password' => $settings['sb_password']
                )),
                'timeout' => 30
            )
        );

        if (is_wp_error($auth_response)) {
            wp_send_json_error(['message' => 'Error authenticating with Switchblade: ' . $auth_response->get_error_message()]);
            return;
        }

        $auth_code = wp_remote_retrieve_response_code($auth_response);
        $auth_body = json_decode(wp_remote_retrieve_body($auth_response), true);

        if ($auth_code !== 200 || !isset($auth_body['token'])) {
            wp_send_json_error(['message' => 'Failed to authenticate with Switchblade']);
            return;
        }

        $token = $auth_body['token'];

        // Now make the update request with the token
        $sb_response = wp_remote_request(
            trailingslashit($sb_api_url) . 'shortcuts/' . $sb_id,
            array(
                'method' => 'PATCH',
                'headers' => array(
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $token
                ),
                'body' => json_encode($sb_data),
                'timeout' => 30
            )
        );

        if (is_wp_error($sb_response) || isset($sb_response['error'])) {
            wp_send_json_error(['message' => 'Failed to update draft status in Switchblade: ' . $sb_response->get_error_message()]);
            return;
        }
    }

    // If Switchblade update succeeded or there was no Switchblade ID, update WordPress
    $post_data = [
        'ID' => $post_id,
        'post_status' => $new_status,
    ];

    // Add title if we have shortcut data
    if ($shortcut_data) {
        $post_data['post_title'] = sanitize_text_field($shortcut_data['name']);
    }

    $updated_post_id = wp_update_post($post_data);

    if (is_wp_error($updated_post_id)) {
        // If WordPress update fails, try to revert Switchblade
        if (!empty($sb_id)) {
            $revert_data = array(
                'name' => sanitize_text_field($shortcut_data['name']),
                'headline' => sanitize_text_field($shortcut_data['headline']),
                'description' => sanitize_textarea_field($shortcut_data['description']),
                'website' => get_permalink($post_id),
                'state' => $current_status === 'draft' ? 1 : 0,
                'deleted' => false
            );
            sb_api_call('shortcuts/' . $sb_id, 'PATCH', [], $revert_data);
        }
        wp_send_json_error(['message' => 'Failed to update draft status in WordPress: ' . $updated_post_id->get_error_message()]);
        return;
    }

    // Update post meta if we have shortcut data
    if ($shortcut_data) {
        update_post_meta($updated_post_id, '_shortcut_name', sanitize_text_field($shortcut_data['name']));
        update_post_meta($updated_post_id, '_shortcut_headline', sanitize_text_field($shortcut_data['headline']));
        update_post_meta($updated_post_id, '_shortcut_description', sanitize_textarea_field($shortcut_data['description']));
        update_post_meta($updated_post_id, '_shortcut_color', sanitize_hex_color($shortcut_data['color']));
        
        // Handle icon data
        if (!empty($shortcut_data['icon'])) {
            $icon_data = wp_unslash($shortcut_data['icon']);
            $decoded = json_decode($icon_data, true);
            
            if ($decoded && (
                (isset($decoded['type']) && $decoded['type'] === 'fontawesome' && isset($decoded['name'])) ||
                (isset($decoded['type']) && $decoded['type'] === 'custom' && isset($decoded['url']))
            )) {
                update_post_meta($updated_post_id, '_shortcut_icon', wp_slash(wp_json_encode($decoded)));
            }
        }
        
        update_post_meta($updated_post_id, '_shortcut_input', sanitize_text_field($shortcut_data['input']));
        update_post_meta($updated_post_id, '_shortcut_result', sanitize_text_field($shortcut_data['result']));
    }

    wp_send_json_success(['new_status' => $new_status]);
}

function delete_shortcut() {
    check_ajax_referer('shortcuts_hub_nonce', 'security');
    
    $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
    
    if (!$post_id) {
        wp_send_json_error(['message' => 'No shortcut ID provided']);
        return;
    }

    $shortcut = get_post($post_id);
    if (!$shortcut || $shortcut->post_type !== 'shortcut') {
        wp_send_json_error(['message' => 'Shortcut not found']);
        return;
    }

    // Get the Switchblade ID before deleting
    $sb_id = get_post_meta($post_id, 'sb_id', true);

    // Delete from WordPress
    $result = wp_delete_post($post_id, true);
    if (!$result) {
        wp_send_json_error(['message' => 'Failed to delete shortcut from WordPress']);
        return;
    }

    // If there's a Switchblade ID, delete from Switchblade too
    if ($sb_id) {
        $sb_response = sb_api_call('shortcuts/' . $sb_id, 'DELETE');
        if (is_wp_error($sb_response)) {
            // Log the error but don't fail - the WordPress delete was successful
        }
    }

    wp_send_json_success([
        'message' => 'Shortcut deleted successfully',
        'redirect' => admin_url('admin.php?page=shortcuts')
    ]);
}

function process_download_token() {
    // Verify nonce
    if (!isset($_POST['security']) || !wp_verify_nonce($_POST['security'], 'shortcuts_hub_nonce')) {
        wp_send_json_error(['message' => 'Invalid security token']);
        return;
    }

    // Get and validate download token
    $download_token = isset($_POST['download_token']) ? sanitize_text_field($_POST['download_token']) : '';
    if (empty($download_token)) {
        wp_send_json_error(['message' => 'No download token provided']);
        return;
    }

    // Get the download URL from transient
    $download_data = get_transient('sh_download_' . $download_token);
    if (!$download_data) {
        wp_send_json_error(['message' => 'Invalid or expired download token']);
        return;
    }

    // Delete the transient as it's one-time use
    delete_transient('sh_download_' . $download_token);

    // Get shortcut and version data
    $shortcut_id = isset($download_data['shortcut_id']) ? intval($download_data['shortcut_id']) : 0;
    $version_id = isset($download_data['version_id']) ? intval($download_data['version_id']) : 0;

    if (!$shortcut_id || !$version_id) {
        wp_send_json_error(['message' => 'Invalid download data']);
        return;
    }

    // Get version data
    $version_data = get_post_meta($version_id, 'version_data', true);
    if (!$version_data || !isset($version_data['url'])) {
        wp_send_json_error(['message' => 'Version data not found']);
        return;
    }

    // Return success with download URL and metadata
    wp_send_json_success([
        'download_url' => $version_data['url'],
        'shortcut_id' => $shortcut_id,
        'version_data' => $version_data
    ]);
}

add_action('init', function() {
    add_action('wp_ajax_fetch_shortcuts', 'fetch_shortcuts');
    add_action('wp_ajax_fetch_shortcut', 'fetch_shortcut');
    add_action('wp_ajax_create_shortcut', 'create_shortcut');
    add_action('wp_ajax_update_shortcut', 'update_shortcut');
    add_action('wp_ajax_toggle_draft', 'toggle_draft');
    add_action('wp_ajax_delete_shortcut', 'delete_shortcut');
    add_action('wp_ajax_process_download_token', 'process_download_token');
    add_action('wp_ajax_nopriv_process_download_token', 'process_download_token');
});
