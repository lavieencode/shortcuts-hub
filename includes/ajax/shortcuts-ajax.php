<?php
namespace ShortcutsHub;

if (!defined('ABSPATH')) {
    exit;
}

// Include required files
require_once dirname(__FILE__) . '/../settings.php';
require_once dirname(__FILE__) . '/../sb-api.php';
require_once dirname(dirname(dirname(__FILE__))) . '/sh-debug.php';

function fetch_shortcuts() {

    // Verify nonce regardless of login status
    if (!isset($_POST['security']) || !wp_verify_nonce($_POST['security'], 'shortcuts_hub_fetch_shortcuts_nonce')) {
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
        if (!empty($filter)) {
            $params['filter'] = $filter;
        }
        if ($status !== '' && $status !== 'any') {
            $params['draft'] = $status === '1';
        }
        if ($deleted !== null && $deleted !== 'any') {
            $params['deleted'] = $deleted === true || $deleted === 'true';
        }

        $sb_response = sb_api_call('shortcuts', 'GET', $params);
        
        if (is_wp_error($sb_response)) {
            wp_send_json_error(['message' => "Failed to fetch shortcuts from Switchblade: " . $sb_response->get_error_message()]);
            return;
        }

        wp_send_json_success($sb_response);
        return;
    }

    // WordPress handling
    $args = array(
        'post_type' => 'shortcut',
        'posts_per_page' => -1,
        'post_status' => array('publish', 'draft', 'trash')
    );

    if (!empty($filter)) {
        $args['s'] = $filter;
    }

    if ($deleted !== null && $deleted !== '' && $deleted !== 'any') {
        $args['post_status'] = ($deleted === true || $deleted === 'true') ? 'trash' : array('publish', 'draft');
    }

    if ($status !== '' && $status !== 'any') {
        $args['post_status'] = array($status);
        if ($deleted === null || $deleted === '' || $deleted === 'any' || $deleted === true || $deleted === 'true') {
            $args['post_status'][] = 'trash';
        }
    }

    $shortcuts = get_posts($args);
    $data = array();

    $wp_all_shortcuts = array();
    $args_all = array(
        'post_type' => 'shortcut',
        'posts_per_page' => -1,
        'post_status' => array('publish', 'draft')
    );
    $all_shortcuts = get_posts($args_all);
    foreach ($all_shortcuts as $shortcut) {
        // Process icon data
        $icon_data = get_post_meta($shortcut->ID, '_shortcut_icon', true);
        $icon = null;
        if ($icon_data) {
            $decoded_icon = json_decode($icon_data, true);
            if ($decoded_icon && isset($decoded_icon['name'])) {
                $icon = $decoded_icon;
            } else {
                // Legacy format: just the icon name
                $icon = array(
                    'type' => 'fontawesome',
                    'name' => $icon_data,
                    'url' => null
                );
            }
        }

        $wp_all_shortcuts[] = array(
            'post_id' => $shortcut->ID,
            'title' => $shortcut->post_title,
            'status' => $shortcut->post_status,
            'content' => $shortcut->post_content,
            'meta' => array(
                'sb_id' => get_post_meta($shortcut->ID, 'sb_id', true),
                'headline' => get_post_meta($shortcut->ID, '_shortcut_headline', true),
                'color' => get_post_meta($shortcut->ID, '_shortcut_color', true),
                'icon' => $icon,
                'input' => get_post_meta($shortcut->ID, '_shortcut_input', true),
                'result' => get_post_meta($shortcut->ID, '_shortcut_result', true),
                'website' => get_post_meta($shortcut->ID, '_shortcut_website', true)
            )
        );
    }

    $wp_deleted_shortcuts = array();
    $args_deleted = array(
        'post_type' => 'shortcut',
        'posts_per_page' => -1,
        'post_status' => array('trash')
    );
    $deleted_shortcuts = get_posts($args_deleted);
    foreach ($deleted_shortcuts as $shortcut) {
        // Process icon data
        $icon_data = get_post_meta($shortcut->ID, '_shortcut_icon', true);
        $icon = null;
        if ($icon_data) {
            $decoded_icon = json_decode($icon_data, true);
            if ($decoded_icon && isset($decoded_icon['name'])) {
                $icon = $decoded_icon;
            } else {
                // Legacy format: just the icon name
                $icon = array(
                    'type' => 'fontawesome',
                    'name' => $icon_data,
                    'url' => null
                );
            }
        }

        $wp_deleted_shortcuts[] = array(
            'post_id' => $shortcut->ID,
            'title' => $shortcut->post_title,
            'status' => $shortcut->post_status,
            'content' => $shortcut->post_content,
            'meta' => array(
                'sb_id' => get_post_meta($shortcut->ID, 'sb_id', true),
                'headline' => get_post_meta($shortcut->ID, '_shortcut_headline', true),
                'color' => get_post_meta($shortcut->ID, '_shortcut_color', true),
                'icon' => $icon,
                'input' => get_post_meta($shortcut->ID, '_shortcut_input', true),
                'result' => get_post_meta($shortcut->ID, '_shortcut_result', true),
                'website' => get_post_meta($shortcut->ID, '_shortcut_website', true)
            )
        );
    }

    // Get and log all Switchblade shortcuts
    $sb_response = sb_api_call('shortcuts', 'GET', array());

    foreach ($shortcuts as $post) {
        $is_trashed = $post->post_status === 'trash';
        $sb_id = get_post_meta($post->ID, 'sb_id', true);
        
        // Get post metadata
        $metadata = get_post_meta($post->ID);

        // Process icon data
        $icon_data = isset($metadata['_shortcut_icon']) ? $metadata['_shortcut_icon'][0] : null;
        $icon = null;
        if ($icon_data) {
            $decoded_icon = json_decode($icon_data, true);
            if ($decoded_icon && isset($decoded_icon['name'])) {
                $icon = $decoded_icon;
            } else {
                // Legacy format: just the icon name
                $icon = array(
                    'type' => 'fontawesome',
                    'name' => $icon_data,
                    'url' => null
                );
            }
        }
        
        // Build WordPress data
        $wordpress_data = array(
            'name' => $post->post_title,
            'description' => $post->post_content,
            'state' => $post->post_status,
            'deleted' => get_post_meta($post->ID, 'deleted', true) === 'true',
            'icon' => $icon,
            'color' => isset($metadata['_shortcut_color']) ? $metadata['_shortcut_color'][0] : null,
            'headline' => isset($metadata['_shortcut_headline']) ? $metadata['_shortcut_headline'][0] : null
        );

        $shortcut_data = array(
            'ID' => $post->ID,
            'wordpress' => $wordpress_data,
            'switchblade' => array(
                'headline' => get_post_meta($post->ID, '_shortcut_headline', true),
                'website' => get_permalink($post),
                'sb_id' => $sb_id
            )
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

        $raw_icon_meta = get_post_meta($post_id, '_shortcut_icon', true);

        // Get and process icon data
        $icon_data = get_post_meta($post_id, '_shortcut_icon', true);
        $icon = null;
        if ($icon_data) {
            $decoded_icon = json_decode($icon_data, true);
            if ($decoded_icon && isset($decoded_icon['name'])) {
                $icon = $decoded_icon;
            } else {
                // Legacy format: just the icon name
                $icon = array(
                    'type' => 'fontawesome',
                    'name' => $icon_data,
                    'url' => null
                );
            }
        }

        // Get all metadata in a structured format
        $data = array(
            'ID' => $shortcut->ID,
            'wordpress' => array(
                'name' => $shortcut->post_title,
                'description' => $shortcut->post_content,
                'color' => get_post_meta($post_id, '_shortcut_color', true),
                'icon' => $icon,
                'input' => get_post_meta($post_id, '_shortcut_input', true),
                'result' => get_post_meta($post_id, '_shortcut_result', true),
                'actions' => get_post_meta($post_id, '_shortcut_actions', true),
                'state' => $shortcut->post_status === 'publish' ? 'publish' : 'draft',
                'deleted' => $shortcut->post_status === 'trash'
            ),
            'switchblade' => array(
                'headline' => get_post_meta($post_id, '_shortcut_headline', true),
                'website' => $permalink,
                'sb_id' => get_post_meta($post_id, 'sb_id', true)
            )
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
    // Only verify nonce for logged-in users
    if (is_user_logged_in()) {
        if (!isset($_POST['security'])) {
            send_json_response(['message' => 'No security token provided for logged-in user'], false);
            return;
        }

        if (!wp_verify_nonce($_POST['security'], 'shortcuts_hub_nonce')) {
            send_json_response(['message' => 'Invalid security token'], false);
            return;
        }
    }

    $shortcut_data = isset($_POST['shortcut_data']) ? $_POST['shortcut_data'] : [];
    $wp_data = isset($_POST['wp_data']) ? $_POST['wp_data'] : [];
    
    if (empty($shortcut_data['name']) || empty($shortcut_data['headline'])) {
        send_json_response(['message' => 'Required fields are missing.'], false);
        return;
    }

    $state = isset($shortcut_data['state']) && $shortcut_data['state'] === 'draft' ? 1 : 0;

    $post_data = array(
        'post_title'   => sanitize_text_field($shortcut_data['name']),
        'post_content' => sanitize_textarea_field($shortcut_data['description']),
        'post_status'  => $state === 1 ? 'draft' : 'publish',
        'post_type'    => 'shortcut',
    );

    $post_id = wp_insert_post($post_data);

    if (!is_wp_error($post_id)) {
        // Save meta from shortcut_data
        update_post_meta($post_id, '_shortcut_headline', sanitize_text_field($shortcut_data['headline']));
        update_post_meta($post_id, '_shortcut_description', sanitize_textarea_field($shortcut_data['description']));
        
        // Save meta from wp_data
        if (!empty($wp_data)) {
            update_post_meta($post_id, '_shortcut_input', sanitize_text_field($wp_data['input']));
            update_post_meta($post_id, '_shortcut_result', sanitize_text_field($wp_data['result']));
            update_post_meta($post_id, '_shortcut_color', sanitize_hex_color($wp_data['color']));
            
            // Handle icon data
            if (isset($wp_data['icon'])) {
                $icon_data = json_decode(stripslashes($wp_data['icon']), true);
                if ($icon_data && isset($icon_data['name'])) {
                    $icon_to_save = array(
                        'type' => isset($icon_data['type']) ? $icon_data['type'] : 'fontawesome',
                        'name' => $icon_data['name'],
                        'url' => isset($icon_data['url']) ? $icon_data['url'] : null
                    );

                    update_post_meta($post_id, '_shortcut_icon', wp_slash(json_encode($icon_to_save)));
                }
            }
        }
        
        // Set the website URL to the WordPress edit page for this shortcut
        $website_url = get_site_url() . '/wp-admin/admin.php?page=edit-shortcut&id=' . $post_id;
        update_post_meta($post_id, '_shortcut_website', esc_url_raw($website_url));
        
        // Create the shortcut in Switchblade
        $sb_data = array(
            'name' => sanitize_text_field($shortcut_data['name']),
            'headline' => sanitize_text_field($shortcut_data['headline']),
            'description' => sanitize_textarea_field($shortcut_data['description']),
            'website' => $website_url,
            'state' => (int)$state
        );

        $settings = get_shortcuts_hub_settings();
        $sb_response = sb_api_call('shortcuts', 'POST', [], $sb_data);
        
        if (is_wp_error($sb_response)) {
            // Delete the WordPress post since Switchblade creation failed
            wp_delete_post($post_id, true);
            
            send_json_response([
                'message' => 'Failed to create shortcut in Switchblade: ' . $sb_response->get_error_message(),
                'error_code' => $sb_response->get_error_code(),
                'error_data' => $sb_response->get_error_data()
            ], false);
            return;
        }

        if (!isset($sb_response['shortcut']) || !isset($sb_response['shortcut']['id'])) {
            // Delete the WordPress post since we got an invalid response
            wp_delete_post($post_id, true);
            
            send_json_response([
                'message' => 'Switchblade returned an invalid response format.',
                'response' => $sb_response
            ], false);
            return;
        }

        $id = $sb_response['shortcut']['id'];
        update_post_meta($post_id, 'sb_id', sanitize_text_field($id));

        // Now that we have the post ID and it's saved, get the proper permalink
        $actual_website_url = get_permalink($post_id);
        
        // Update the website URL in Switchblade
        $update_data = array(
            'website' => $actual_website_url
        );
        
        $update_response = sb_api_call('shortcuts/' . $id, 'PATCH', [], $update_data);
        
        send_json_response(['message' => 'Shortcut created successfully.', 'post_id' => $post_id, 'sb_id' => $id]);
    } else {
        send_json_response(['message' => 'Failed to create shortcut in WordPress.'], false);
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
    
    // Handle icon data
    if (isset($shortcut_data['icon'])) {
        $icon_data = is_string($shortcut_data['icon']) ? json_decode($shortcut_data['icon'], true) : $shortcut_data['icon'];
        if ($icon_data && isset($icon_data['name'])) {
            $icon_to_save = array(
                'type' => isset($icon_data['type']) ? $icon_data['type'] : 'fontawesome',
                'name' => $icon_data['name'],
                'url' => isset($icon_data['url']) ? $icon_data['url'] : null
            );
            
            update_post_meta($post_id, '_shortcut_icon', wp_slash(json_encode($icon_to_save)));
        }
    }

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
        'state' => (int)$shortcut_data['state'] === 'publish' ? 0 : 1
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
            'state' => (int)$is_draft
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
                'state' => (int)($current_status === 'draft')
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
    $permanent = isset($_POST['permanent']) ? filter_var($_POST['permanent'], FILTER_VALIDATE_BOOLEAN) : false;
    $restore = isset($_POST['restore']) ? filter_var($_POST['restore'], FILTER_VALIDATE_BOOLEAN) : false;
    
    if (!$post_id) {
        wp_send_json_error(['message' => 'No shortcut ID provided']);
        return;
    }

    $shortcut = get_post($post_id);
    if (!$shortcut || $shortcut->post_type !== 'shortcut') {
        wp_send_json_error(['message' => 'Shortcut not found']);
        return;
    }

    // Get the Switchblade ID
    $sb_id = get_post_meta($post_id, 'sb_id', true);
    $sb_response = null;

    if ($permanent) {
        // If there's a Switchblade ID, delete from Switchblade first
        if ($sb_id) {
            $sb_response = sb_api_call('shortcuts/' . $sb_id, 'DELETE');
            if (is_wp_error($sb_response)) {
                wp_send_json_error(['message' => 'Failed to delete shortcut from Switchblade: ' . $sb_response->get_error_message()]);
                return;
            }
        }

        // Then delete from WordPress
        $result = wp_delete_post($post_id, true);
        if (!$result) {
            // If WordPress delete fails but Switchblade succeeded, we have inconsistent state
            wp_send_json_error(['message' => 'Failed to delete shortcut from WordPress']);
            return;
        }
    } else {
        // Status-level deletion/restoration
        if ($restore) {
            $result = wp_untrash_post($post_id);
            if ($sb_id) {
                // Update Switchblade deleted status to false
                $sb_response = sb_api_call('shortcuts/' . $sb_id, 'PATCH', [], ['deleted' => false]);
            }
        } else {
            $result = wp_trash_post($post_id);
            if ($sb_id) {
                // Update Switchblade deleted status to true
                $sb_response = sb_api_call('shortcuts/' . $sb_id, 'PATCH', [], ['deleted' => true]);
            }
        }

        if (!$result) {
            wp_send_json_error(['message' => $restore ? 'Failed to restore shortcut' : 'Failed to move shortcut to trash']);
            return;
        }
    }

    wp_send_json_success([
        'message' => $permanent ? 'Shortcut permanently deleted' : ($restore ? 'Shortcut restored' : 'Shortcut moved to trash'),
        'redirect' => $permanent ? admin_url('admin.php?page=shortcuts') : null
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

class Shortcuts_Ajax_Handler {
    private static $instance = null;

    public static function instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        if ($this->is_registered()) {
            return;
        }
        $this->register_handlers();
    }

    private function is_registered() {
        return did_action('init') || doing_action('init');
    }

    public function register_handlers() {
        // Shortcuts AJAX handlers
        add_action('wp_ajax_fetch_shortcuts', __NAMESPACE__ . '\\fetch_shortcuts');
        add_action('wp_ajax_nopriv_fetch_shortcuts', __NAMESPACE__ . '\\fetch_shortcuts');
        add_action('wp_ajax_create_shortcut', __NAMESPACE__ . '\\create_shortcut');
        add_action('wp_ajax_fetch_shortcut', __NAMESPACE__ . '\\fetch_shortcut');
        add_action('wp_ajax_update_shortcut', __NAMESPACE__ . '\\update_shortcut');
        add_action('wp_ajax_toggle_draft', __NAMESPACE__ . '\\toggle_draft');
        add_action('wp_ajax_delete_shortcut', __NAMESPACE__ . '\\delete_shortcut');
        add_action('wp_ajax_process_download_token', __NAMESPACE__ . '\\process_download_token');
    }
}

function send_json_response($response, $success = true) {
    // Ensure clean output
    if (ob_get_level() > 0) {
        ob_clean();
    }

    // Set JSON content type
    header('Content-Type: application/json');
    
    if ($success) {
        wp_send_json_success($response);
    } else {
        wp_send_json_error($response);
    }
}

function shortcuts_hub_fetch_actions() {
    check_ajax_referer('shortcuts_hub_nonce', 'security');

    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'Insufficient permissions'));
        return;
    }

    $args = array(
        'post_type' => 'action',
        'posts_per_page' => -1,
        'orderby' => 'title',
        'order' => 'ASC'
    );

    // Add search filter
    if (!empty($_POST['search'])) {
        $args['s'] = sanitize_text_field($_POST['search']);
    }

    // Add status filter
    if (!empty($_POST['status'])) {
        $args['post_status'] = sanitize_text_field($_POST['status']);
    }

    $actions = get_posts($args);
    $formatted_actions = array();

    foreach ($actions as $action) {
        $formatted_actions[] = array(
            'id' => $action->ID,
            'title' => $action->post_title,
            'description' => get_post_meta($action->ID, '_action_description', true),
            'icon' => get_post_meta($action->ID, '_action_icon', true),
            'status' => $action->post_status
        );
    }

    wp_send_json_success($formatted_actions);
}
add_action('wp_ajax_fetch_actions', 'shortcuts_hub_fetch_actions');

function shortcuts_hub_create_action() {
    check_ajax_referer('shortcuts_hub_nonce', 'security');

    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'Insufficient permissions'));
        return;
    }

    // Validate required fields
    if (empty($_POST['name'])) {
        wp_send_json_error(array('message' => 'Action name is required'));
        return;
    }

    // Create the action post
    $action_data = array(
        'post_title' => sanitize_text_field($_POST['name']),
        'post_type' => 'action',
        'post_status' => 'publish'
    );

    $action_id = wp_insert_post($action_data);

    if (is_wp_error($action_id)) {
        wp_send_json_error(array('message' => $action_id->get_error_message()));
        return;
    }

    // Save meta data
    if (!empty($_POST['description'])) {
        update_post_meta($action_id, '_action_description', sanitize_textarea_field($_POST['description']));
    }

    if (!empty($_POST['icon'])) {
        update_post_meta($action_id, '_action_icon', $_POST['icon']);
    }

    wp_send_json_success(array(
        'id' => $action_id,
        'message' => 'Action created successfully'
    ));
}
add_action('wp_ajax_create_action', 'shortcuts_hub_create_action');

add_action('wp_ajax_create_shortcut', __NAMESPACE__ . '\create_shortcut');
