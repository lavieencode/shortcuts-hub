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

    $shortcuts = get_posts($args);

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

    $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
    $source = isset($_POST['source']) ? sanitize_text_field($_POST['source']) : 'WP';

    if ($source === 'WP' && $post_id > 0) {
        $shortcut = get_post($post_id);
        if ($shortcut) {
            // Get the icon data and ensure it's properly formatted
            $icon_data = get_post_meta($post_id, 'icon', true);
            
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
                } else {
                    // Re-encode to ensure consistent format
                    $icon_data = wp_json_encode($decoded);
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
    check_ajax_referer('shortcuts_hub_nonce', 'security');
    
    if (!current_user_can('edit_posts')) {
        wp_send_json_error(['message' => 'Permission denied']);
        return;
    }

    $shortcut_data = $_POST['shortcut_data'];
    $post_id = intval($shortcut_data['post_id']);
    $sb_id = sanitize_text_field($shortcut_data['sb_id']);
    
    if (!$post_id || !$sb_id) {
        wp_send_json_error(['message' => 'Invalid shortcut data']);
        return;
    }

    // Update WordPress post
    $post_data = array(
        'ID' => $post_id,
        'post_title' => sanitize_text_field($shortcut_data['name']),
        'post_content' => wp_kses_post($shortcut_data['description']),
        'post_status' => $shortcut_data['state'] === 'publish' ? 'publish' : 'draft'
    );

    $updated_post = wp_update_post($post_data, true);
    if (is_wp_error($updated_post)) {
        wp_send_json_error(['message' => 'Error updating WordPress post: ' . $updated_post->get_error_message()]);
        return;
    }

    // Update post meta
    update_post_meta($post_id, '_shortcut_headline', sanitize_text_field($shortcut_data['headline']));
    update_post_meta($post_id, '_shortcut_color', sanitize_text_field($shortcut_data['color']));
    update_post_meta($post_id, '_shortcut_icon', sanitize_text_field($shortcut_data['icon']));
    update_post_meta($post_id, '_shortcut_input', wp_kses_post($shortcut_data['input']));
    update_post_meta($post_id, '_shortcut_result', wp_kses_post($shortcut_data['result']));
    
    // Get the permalink for the website field
    $permalink = get_permalink($post_id);

    // Prepare Switchblade data
    $sb_data = array(
        'name' => sanitize_text_field($shortcut_data['name']),
        'headline' => sanitize_text_field($shortcut_data['headline']),
        'description' => wp_kses_post($shortcut_data['description']),
        'website' => $permalink,
        'state' => $shortcut_data['state'] === 'publish' ? 0 : 1  // 0 for published, 1 for draft in Switchblade
    );

    // Update Switchblade
    $settings = get_shortcuts_hub_settings();
    $sb_api_url = $settings['sb_url'];
    
    if (!$sb_api_url) {
        wp_send_json_error(['message' => 'Switchblade API URL not configured']);
        return;
    }

    $response = sb_api_call('shortcuts/' . $sb_id, 'PATCH', [], $sb_data);

    if (is_wp_error($response)) {
        wp_send_json_error(['message' => 'Error updating Switchblade: ' . $response->get_error_message()]);
        return;
    }

    if (isset($response['error'])) {
        wp_send_json_error(['message' => 'Switchblade API error: ' . $response['error']]);
        return;
    }

    wp_send_json_success([
        'message' => 'Shortcut updated successfully',
        'post_id' => $post_id,
        'sb_id' => $sb_id
    ]);
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
        update_post_meta($updated_post_id, 'name', sanitize_text_field($shortcut_data['name']));
        update_post_meta($updated_post_id, 'headline', sanitize_text_field($shortcut_data['headline']));
        update_post_meta($updated_post_id, 'description', sanitize_textarea_field($shortcut_data['description']));
        update_post_meta($updated_post_id, 'color', sanitize_hex_color($shortcut_data['color']));
        
        // Handle icon data
        if (!empty($shortcut_data['icon'])) {
            $icon_data = wp_unslash($shortcut_data['icon']);
            $decoded = json_decode($icon_data, true);
            
            if ($decoded && (
                (isset($decoded['type']) && $decoded['type'] === 'fontawesome' && isset($decoded['name'])) ||
                (isset($decoded['type']) && $decoded['type'] === 'custom' && isset($decoded['url']))
            )) {
                update_post_meta($updated_post_id, 'icon', wp_slash(wp_json_encode($decoded)));
            }
        }
        
        update_post_meta($updated_post_id, 'input', sanitize_text_field($shortcut_data['input']));
        update_post_meta($updated_post_id, 'result', sanitize_text_field($shortcut_data['result']));
    }

    wp_send_json_success(['new_status' => $new_status]);
}

function delete_shortcut() {
    if (!is_user_logged_in() || !check_ajax_referer('shortcuts_hub_nonce', 'security', false)) {
        wp_send_json_error(['message' => 'Invalid security token']);
        return;
    }

    $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
    $sb_id = isset($_POST['sb_id']) ? sanitize_text_field($_POST['sb_id']) : '';
    $permanent = isset($_POST['permanent']) ? filter_var($_POST['permanent'], FILTER_VALIDATE_BOOLEAN) : false;
    $restore = isset($_POST['restore']) ? filter_var($_POST['restore'], FILTER_VALIDATE_BOOLEAN) : false;

    if ($restore) {
        if (!empty($sb_id)) {
            $sb_data = [
                'deleted' => false
            ];
            
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
                wp_send_json_error(['message' => 'Error in Switchblade']);
                return;
            }
        }

        if ($post_id > 0) {
            $result = wp_untrash_post($post_id);
            if ($result) {
                wp_update_post([
                    'ID' => $post_id,
                    'post_status' => 'draft'
                ]);
            } else {
                wp_send_json_error(['message' => 'Failed to restore WordPress post']);
                return;
            }
        }
    } else {
        if ($post_id > 0) {
            if ($permanent) {
                $result = wp_delete_post($post_id, true);
            } else {
                $result = wp_trash_post($post_id);
            }

            if (!$result) {
                wp_send_json_error(['message' => 'Failed to delete WordPress post']);
                return;
            }
        }

        if (!empty($sb_id)) {
            $sb_data = [
                'deleted' => true
            ];
            
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
                wp_send_json_error(['message' => 'Error in Switchblade']);
                return;
            }
        }
    }

    wp_send_json_success(['message' => 'Shortcut successfully ' . ($restore ? 'restored' : 'deleted')]);
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
