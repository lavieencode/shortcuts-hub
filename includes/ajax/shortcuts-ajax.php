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

    error_log('Shortcuts Hub - Fetch Request Parameters:');
    error_log('Filter: ' . print_r($filter, true));
    error_log('Source: ' . print_r($source, true));
    error_log('Status: ' . print_r($status, true));
    error_log('Deleted: ' . print_r($deleted, true));

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

        error_log('Shortcuts Hub - Switchblade Query Params:');
        error_log(print_r($params, true));

        $sb_response = sb_api_call('shortcuts', 'GET', $params);
        if (is_wp_error($sb_response)) {
            wp_send_json_error(['message' => 'Error fetching from Switchblade: ' . $sb_response->get_error_message()]);
            return;
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

    error_log('Shortcuts Hub - WP Query Args:');
    error_log(print_r($args, true));

    $shortcuts = get_posts($args);
    error_log('Shortcuts Hub - Number of shortcuts found: ' . count($shortcuts));

    $data = array();

    foreach ($shortcuts as $shortcut) {
        $is_trashed = $shortcut->post_status === 'trash';
        $sb_id = get_post_meta($shortcut->ID, 'sb_id', true);
        
        $shortcut_data = array(
            'post_id' => $shortcut->ID,
            'name' => $shortcut->post_title,
            'headline' => get_post_meta($shortcut->ID, 'headline', true),
            'description' => get_post_meta($shortcut->ID, 'description', true),
            'color' => get_post_meta($shortcut->ID, 'color', true),
            'icon' => get_post_meta($shortcut->ID, 'icon', true),
            'input' => get_post_meta($shortcut->ID, 'input', true),
            'result' => get_post_meta($shortcut->ID, 'result', true),
            'sb_id' => $sb_id,
            'post_date' => $shortcut->post_date,
            'post_status' => $shortcut->post_status
        );

        error_log('Shortcut data for ID ' . $shortcut->ID . ':');
        error_log(print_r($shortcut_data, true));

        $data[] = $shortcut_data;
    }

    wp_send_json_success($data);
}

function fetch_shortcut() {
    // Only verify nonce for logged-in users
    if (is_user_logged_in()) {
        if (!isset($_POST['security'])) {
            wp_send_json_error(['message' => 'No security token provided for logged-in user']);
            return;
        }

        if (!wp_verify_nonce($_POST['security'], 'shortcuts_hub_nonce')) {
            wp_send_json_error(['message' => 'Invalid security token']);
            return;
        }
    }

    $post_id = intval($_POST['post_id']);
    $source = isset($_POST['source']) ? sanitize_text_field($_POST['source']) : 'WP';

    if ($source === 'WP') {
        $shortcut = get_post($post_id);
        if ($shortcut) {
            // Get the icon data and ensure it's properly formatted
            $icon_data = get_post_meta($post_id, 'icon', true);
            error_log('Raw icon data from DB: ' . print_r($icon_data, true));
            
            if (!empty($icon_data)) {
                // If it's already a JSON string, validate it
                $decoded = json_decode($icon_data, true);
                if ($decoded === null) {
                    // Not valid JSON, try to convert legacy format
                    if (filter_var($icon_data, FILTER_VALIDATE_URL)) {
                        $icon_data = wp_json_encode([
                            'type' => 'custom',
                            'url' => esc_url_raw($icon_data)
                        ]);
                    } else {
                        $icon_data = wp_json_encode([
                            'type' => 'fontawesome',
                            'name' => sanitize_text_field($icon_data)
                        ]);
                    }
                    error_log('Converted legacy format to: ' . print_r($icon_data, true));
                } else {
                    // Re-encode to ensure consistent format
                    $icon_data = wp_json_encode($decoded);
                    error_log('Re-encoded valid JSON: ' . print_r($icon_data, true));
                }
            }
            
            $response = array(
                'success' => true,
                'data' => array(
                    'post_id' => $shortcut->ID,
                    'name' => $shortcut->post_title,
                    'headline' => get_post_meta($post_id, 'headline', true),
                    'description' => get_post_meta($post_id, 'description', true),
                    'color' => get_post_meta($post_id, 'color', true),
                    'icon' => $icon_data,
                    'input' => get_post_meta($post_id, 'input', true),
                    'result' => get_post_meta($post_id, 'result', true),
                    'sb_id' => get_post_meta($post_id, 'sb_id', true),
                    'post_date' => $shortcut->post_date,
                    'post_status' => $shortcut->post_status
                )
            );
            error_log('Sending response: ' . print_r($response, true));
        } else {
            $response = array('success' => false, 'message' => 'Shortcut not found.');
        }
    } elseif ($source === 'SB') {
        // Get settings
        $settings = get_shortcuts_hub_settings();
        $sb_response = sb_api_call('shortcuts/' . $post_id);
        if (is_wp_error($sb_response)) {
            $response = array('success' => false, 'message' => 'Error fetching from Switchblade: ' . $sb_response->get_error_message());
        } else {
            $response = array('success' => true, 'data' => $sb_response);
        }
    } else {
        $response = array('success' => false, 'message' => 'Invalid source specified.');
    }

    wp_send_json($response);
}

function create_shortcut() {
    // Only verify nonce for logged-in users
    if (is_user_logged_in()) {
        if (!isset($_POST['security'])) {
            wp_send_json_error(['message' => 'No security token provided for logged-in user']);
            return;
        }

        if (!wp_verify_nonce($_POST['security'], 'shortcuts_hub_nonce')) {
            wp_send_json_error(['message' => 'Invalid security token']);
            return;
        }
    }

    $shortcut_data = isset($_POST['shortcut_data']) ? $_POST['shortcut_data'] : [];

    if (empty($shortcut_data['name']) || empty($shortcut_data['headline'])) {
        wp_send_json_error(array('message' => 'Required fields are missing.'));
        return;
    }

    $state = isset($shortcut_data['state']) && $shortcut_data['state'] === 'draft' ? 1 : 0;

    $post_data = array(
        'post_title'   => sanitize_text_field($shortcut_data['name']),
        'post_content' => '',
        'post_status'  => $state === 1 ? 'draft' : 'publish',
        'post_type'    => 'shortcut',
    );

    $post_id = wp_insert_post($post_data);

    if (!is_wp_error($post_id)) {
        update_post_meta($post_id, 'headline', sanitize_text_field($shortcut_data['headline']));
        update_post_meta($post_id, 'description', isset($shortcut_data['description']) ? sanitize_textarea_field($shortcut_data['description']) : '');
        update_post_meta($post_id, 'color', isset($shortcut_data['color']) ? sanitize_hex_color($shortcut_data['color']) : '');
        update_post_meta($post_id, 'icon', isset($shortcut_data['icon']) ? sanitize_text_field($shortcut_data['icon']) : '');
        update_post_meta($post_id, 'input', isset($shortcut_data['input']) ? sanitize_text_field($shortcut_data['input']) : '');
        update_post_meta($post_id, 'result', isset($shortcut_data['result']) ? sanitize_text_field($shortcut_data['result']) : '');
        
        // Set the website URL to the WordPress edit page for this shortcut
        $website_url = get_site_url() . '/wp-admin/admin.php?page=edit-shortcut&id=' . $post_id;
        update_post_meta($post_id, 'website', esc_url_raw($website_url));
        
        // Create the shortcut in Switchblade
        $sb_data = array(
            'name' => sanitize_text_field($shortcut_data['name']),
            'headline' => sanitize_text_field($shortcut_data['headline']),
            'description' => isset($shortcut_data['description']) ? sanitize_textarea_field($shortcut_data['description']) : '',
            'input' => isset($shortcut_data['input']) ? sanitize_text_field($shortcut_data['input']) : '',
            'result' => isset($shortcut_data['result']) ? sanitize_text_field($shortcut_data['result']) : '',
            'state' => $state,
            'website' => $website_url
        );
        // Get settings
        $settings = get_shortcuts_hub_settings();
        $sb_response = sb_api_call('shortcuts', 'POST', [], $sb_data);
        
        if (is_wp_error($sb_response)) {
            wp_send_json_error(array('message' => 'Failed to create shortcut in Switchblade: ' . $sb_response->get_error_message()));
            return;
        }

        $id = isset($sb_response['shortcut']['id']) ? $sb_response['shortcut']['id'] : null;

        if (!$id) {
            wp_send_json_error(array('message' => 'Switchblade did not return a valid ID.'));
            return;
        }

        update_post_meta($post_id, 'sb_id', sanitize_text_field($id));

        wp_send_json_success(array('message' => 'Shortcut created successfully.', 'post_id' => $post_id, 'sb_id' => $id));
    } else {
        wp_send_json_error(array('message' => 'Failed to create shortcut in WordPress.'));
    }
}

function update_shortcut() {
    // Only verify nonce for logged-in users
    if (is_user_logged_in()) {
        if (!isset($_POST['security'])) {
            wp_send_json_error(['message' => 'No security token provided for logged-in user']);
            return;
        }

        if (!wp_verify_nonce($_POST['security'], 'shortcuts_hub_nonce')) {
            wp_send_json_error(['message' => 'Invalid security token']);
            return;
        }
    }

    $shortcut_data = $_POST['shortcut_data'];
    $post_id = intval($shortcut_data['post_id']);

    if (!isset($shortcut_data['state'])) {
        wp_send_json_error(array('message' => 'State is required.'));
        return;
    }

    $state = $shortcut_data['state'] === 'draft' ? 1 : 0;

    $post_data = array(
        'ID' => $post_id,
        'post_title' => sanitize_text_field($shortcut_data['name']),
        'post_status' => $state === 1 ? 'draft' : 'publish',
    );

    $updated_post_id = wp_update_post($post_data);

    if (!is_wp_error($updated_post_id)) {
        update_post_meta($updated_post_id, 'name', sanitize_text_field($shortcut_data['name']));
        update_post_meta($updated_post_id, 'headline', sanitize_text_field($shortcut_data['headline']));
        update_post_meta($updated_post_id, 'description', sanitize_textarea_field($shortcut_data['description']));
        update_post_meta($updated_post_id, 'color', sanitize_hex_color($shortcut_data['color']));
        
        // Handle icon data properly
        if (isset($shortcut_data['icon']) && !empty($shortcut_data['icon'])) {
            error_log('Raw icon data received: ' . print_r($shortcut_data['icon'], true));
            
            // Ensure we have a clean, unslashed JSON string
            $icon_data = wp_unslash($shortcut_data['icon']);
            error_log('After unslash: ' . print_r($icon_data, true));
            
            // Decode to validate structure
            $decoded = json_decode($icon_data, true);
            error_log('Decoded from JSON string: ' . print_r($decoded, true));
            
            // Validate the icon data structure
            if ($decoded && (
                // Valid if it's a FontAwesome icon with type and name
                (isset($decoded['type']) && $decoded['type'] === 'fontawesome' && isset($decoded['name'])) ||
                // Or if it's a custom icon with type and url
                (isset($decoded['type']) && $decoded['type'] === 'custom' && isset($decoded['url']))
            )) {
                // Re-encode to ensure consistent format
                $icon_json = wp_json_encode($decoded);
                error_log('Re-encoded valid icon data: ' . print_r($icon_json, true));
                update_post_meta($updated_post_id, 'icon', wp_slash($icon_json));
                
                // Verify saved data
                $saved_data = get_post_meta($updated_post_id, 'icon', true);
                error_log('Verified saved data: ' . print_r($saved_data, true));
            } else {
                error_log('Invalid icon data structure: ' . print_r($decoded, true));
                // If invalid structure, try to convert legacy format
                if (is_string($icon_data)) {
                    if (filter_var($icon_data, FILTER_VALIDATE_URL)) {
                        $legacy_data = [
                            'type' => 'custom',
                            'url' => esc_url_raw($icon_data)
                        ];
                    } else {
                        $legacy_data = [
                            'type' => 'fontawesome',
                            'name' => sanitize_text_field($icon_data)
                        ];
                    }
                    $legacy_json = wp_json_encode($legacy_data);
                    error_log('Converting to legacy format: ' . print_r($legacy_json, true));
                    update_post_meta($updated_post_id, 'icon', wp_slash($legacy_json));
                }
            }
        } else {
            error_log('No icon data received in shortcut_data');
        }
        
        update_post_meta($updated_post_id, 'input', sanitize_text_field($shortcut_data['input']));
        update_post_meta($updated_post_id, 'result', sanitize_text_field($shortcut_data['result']));
        update_post_meta($updated_post_id, 'sb_id', sanitize_text_field($shortcut_data['sb_id']));

        // Get settings
        $settings = get_shortcuts_hub_settings();
        $sb_response = sb_api_call('shortcuts/' . $shortcut_data['sb_id'], 'PUT', [], $shortcut_data);
        if (is_wp_error($sb_response)) {
            wp_send_json_error(array('message' => 'Error updating Switchblade: ' . $sb_response->get_error_message()));
            return;
        }

        wp_send_json_success(array(
            'message' => 'Shortcut updated successfully.',
            'post_id' => $updated_post_id
        ));
    } else {
        wp_send_json_error(array('message' => 'Failed to update shortcut.'));
    }
}

function toggle_draft() {
    if (!is_user_logged_in() || !check_ajax_referer('shortcuts_hub_nonce', 'security', false)) {
        wp_send_json_error(['message' => 'Invalid security token']);
        return;
    }

    $post_id = intval($_POST['post_id']);
    $sb_id = get_post_meta($post_id, 'sb_id', true);
    $current_status = get_post_status($post_id);
    $new_status = $current_status === 'draft' ? 'publish' : 'draft';

    // Try to update Switchblade first
    if (!empty($sb_id)) {
        $sb_response = sb_api_call('shortcuts/' . $sb_id, 'PATCH', array(
            'draft' => $new_status === 'draft'
        ));

        if (is_wp_error($sb_response)) {
            wp_send_json_error(array(
                'message' => 'Failed to update draft status in Switchblade: ' . $sb_response->get_error_message()
            ));
            return;
        }
    }

    // If Switchblade update succeeded or there was no Switchblade ID, update WordPress
    $updated_post_id = wp_update_post(array(
        'ID' => $post_id,
        'post_status' => $new_status,
    ));

    if (is_wp_error($updated_post_id)) {
        // If WordPress update fails, try to revert Switchblade
        if (!empty($sb_id)) {
            sb_api_call('shortcuts/' . $sb_id, 'PATCH', array(
                'draft' => $current_status === 'draft'
            ));
        }
        wp_send_json_error(array('message' => 'Failed to update draft status in WordPress: ' . $updated_post_id->get_error_message()));
        return;
    }

    wp_send_json_success(array('new_status' => $new_status));
}

function toggle_delete() {
    if (!is_user_logged_in() || !check_ajax_referer('shortcuts_hub_nonce', 'security', false)) {
        wp_send_json_error(['message' => 'Invalid security token']);
        return;
    }

    $post_id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    $sb_id = isset($_POST['sb_id']) ? sanitize_text_field($_POST['sb_id']) : '';
    $is_deleted = isset($_POST['deleted']) ? filter_var($_POST['deleted'], FILTER_VALIDATE_BOOLEAN) : null;

    // Try to update Switchblade first if we have an ID
    if (!empty($sb_id)) {
        $sb_response = sb_api_call('shortcuts/' . $sb_id, 'PATCH', array(
            'deleted' => $is_deleted
        ));

        if (is_wp_error($sb_response)) {
            wp_send_json_error(array(
                'message' => 'Failed to update delete status in Switchblade: ' . $sb_response->get_error_message()
            ));
            return;
        }
    }

    // If Switchblade update succeeded or there was no Switchblade ID, update WordPress
    if ($is_deleted) {
        $result = wp_trash_post($post_id);
        $action_text = 'moved to trash';
    } else {
        $result = wp_untrash_post($post_id);
        $action_text = 'restored from trash';
    }

    if (!$result) {
        // If WordPress update fails, try to revert Switchblade
        if (!empty($sb_id)) {
            sb_api_call('shortcuts/' . $sb_id, 'PATCH', array(
                'deleted' => !$is_deleted
            ));
        }
        wp_send_json_error(array(
            'message' => 'Failed to ' . $action_text . ' in WordPress'
        ));
        return;
    }

    wp_send_json_success(array(
        'message' => 'Shortcut successfully ' . $action_text,
        'id' => $post_id
    ));
}

function delete_shortcut() {
    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => 'User must be logged in']);
        return;
    }

    // Debug logging
    error_log('POST data received: ' . print_r($_POST, true));
    
    // Check for either 'nonce' or 'security' parameter
    $nonce = isset($_POST['security']) ? $_POST['security'] : '';
    error_log('Nonce received: ' . $nonce);
    
    if (empty($nonce)) {
        wp_send_json_error(['message' => 'No security token provided']);
        return;
    }

    if (!wp_verify_nonce($nonce, 'shortcuts_hub_nonce')) {
        wp_send_json_error(['message' => 'Invalid security token']);
        return;
    }

    $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
    $sb_id = isset($_POST['sb_id']) ? sanitize_text_field($_POST['sb_id']) : '';

    $errors = [];
    $success_messages = [];

    // Handle WordPress deletion if post_id exists
    if ($post_id > 0) {
        $deleted = wp_delete_post($post_id, true);
        if (!$deleted) {
            $errors[] = 'Failed to delete WordPress post';
        } else {
            $success_messages[] = 'WordPress post deleted';
        }
    }

    // Handle Switchblade deletion if sb_id exists
    if (!empty($sb_id)) {
        // Get settings
        $settings = get_shortcuts_hub_settings();
        $sb_response = sb_api_call('shortcuts/' . $sb_id, 'DELETE');
        if (is_wp_error($sb_response)) {
            $errors[] = 'Error deleting from Switchblade: ' . $sb_response->get_error_message();
        } else {
            $success_messages[] = 'Switchblade shortcut deleted';
        }
    }

    // Handle response based on results
    if (!empty($errors)) {
        wp_send_json_error([
            'message' => implode('; ', $errors),
            'partial_success' => !empty($success_messages)
        ]);
    } else {
        wp_send_json_success([
            'message' => implode('; ', $success_messages)
        ]);
    }
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
    add_action('wp_ajax_toggle_delete', 'toggle_delete');
    add_action('wp_ajax_delete_shortcut', 'delete_shortcut');
    add_action('wp_ajax_process_download_token', 'process_download_token');
    add_action('wp_ajax_nopriv_process_download_token', 'process_download_token');
});
