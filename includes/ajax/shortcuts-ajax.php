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
        
        // DEBUG: Log Switchblade API request and response for fetching shortcuts
        if (is_wp_error($sb_response)) {
            sh_debug_log('Switchblade API Error - Fetch Shortcuts', [
                'error' => [
                    'code' => $sb_response->get_error_code(),
                    'message' => $sb_response->get_error_message()
                ],
                'request' => [
                    'endpoint' => 'shortcuts',
                    'method' => 'GET',
                    'params' => $params
                ],
                'source' => [
                    'file' => __FILE__,
                    'line' => __LINE__,
                    'function' => __FUNCTION__
                ],
                'debug' => false
            ]);
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

    // DEBUG: Log all WordPress shortcuts (both draft and published) to verify post data and metadata
    $wp_all_shortcuts = array();
    $args_all = array(
        'post_type' => 'shortcut',
        'posts_per_page' => -1,
        'post_status' => array('publish', 'draft')
    );
    $all_shortcuts = get_posts($args_all);
    foreach ($all_shortcuts as $shortcut) {
        $wp_all_shortcuts[] = array(
            'post_id' => $shortcut->ID,
            'title' => $shortcut->post_title,
            'status' => $shortcut->post_status,
            'content' => $shortcut->post_content,
            'meta' => array(
                'sb_id' => get_post_meta($shortcut->ID, 'sb_id', true),
                'headline' => get_post_meta($shortcut->ID, '_shortcut_headline', true),
                'color' => get_post_meta($shortcut->ID, '_shortcut_color', true),
                'icon' => get_post_meta($shortcut->ID, '_shortcut_icon', true),
                'input' => get_post_meta($shortcut->ID, '_shortcut_input', true),
                'result' => get_post_meta($shortcut->ID, '_shortcut_result', true),
                'website' => get_post_meta($shortcut->ID, '_shortcut_website', true)
            )
        );
    }
    // DEBUG: Log all WordPress shortcuts (both draft and published) to verify post data and metadata
    sh_debug_log('All WordPress Shortcuts (Draft and Published)', array(
        'shortcuts' => $wp_all_shortcuts,
        'total_count' => count($wp_all_shortcuts),
        'source' => array(
            'file' => __FILE__,
            'line' => __LINE__,
            'function' => __FUNCTION__
        ),
        'debug' => false
    ));

    $wp_deleted_shortcuts = array();
    $args_deleted = array(
        'post_type' => 'shortcut',
        'posts_per_page' => -1,
        'post_status' => array('trash')
    );
    $deleted_shortcuts = get_posts($args_deleted);
    foreach ($deleted_shortcuts as $shortcut) {
        $wp_deleted_shortcuts[] = array(
            'post_id' => $shortcut->ID,
            'title' => $shortcut->post_title,
            'status' => $shortcut->post_status,
            'content' => $shortcut->post_content,
            'meta' => array(
                'sb_id' => get_post_meta($shortcut->ID, 'sb_id', true),
                'headline' => get_post_meta($shortcut->ID, '_shortcut_headline', true),
                'color' => get_post_meta($shortcut->ID, '_shortcut_color', true),
                'icon' => get_post_meta($shortcut->ID, '_shortcut_icon', true),
                'input' => get_post_meta($shortcut->ID, '_shortcut_input', true),
                'result' => get_post_meta($shortcut->ID, '_shortcut_result', true),
                'website' => get_post_meta($shortcut->ID, '_shortcut_website', true)
            )
        );
    }
    // DEBUG: Log all deleted WordPress shortcuts separately
    sh_debug_log('All Deleted WordPress Shortcuts', array(
        'shortcuts' => $wp_deleted_shortcuts,
        'total_count' => count($wp_deleted_shortcuts),
        'source' => array(
            'file' => __FILE__,
            'line' => __LINE__,
            'function' => __FUNCTION__
        ),
        'debug' => false
    ));

    // Get and log all Switchblade shortcuts
    $sb_response = sb_api_call('shortcuts', 'GET', array());
    if (!is_wp_error($sb_response)) {
        // DEBUG: Log all Switchblade shortcuts to compare with WordPress data and verify sync status
        sh_debug_log('All Switchblade Shortcuts', array(
            'shortcuts' => $sb_response,
            'source' => array(
                'file' => __FILE__,
                'line' => __LINE__,
                'function' => __FUNCTION__
            ),
            'debug' => false
        ));
    }

    foreach ($shortcuts as $shortcut) {
        $is_trashed = $shortcut->post_status === 'trash';
        $sb_id = get_post_meta($shortcut->ID, 'sb_id', true);
        
        $shortcut_data = array(
            'ID' => $shortcut->ID,
            'wordpress' => array(
                'name' => $shortcut->post_title,
                'description' => $shortcut->post_content,
                'color' => get_post_meta($shortcut->ID, '_shortcut_color', true),
                'icon' => get_post_meta($shortcut->ID, '_shortcut_icon', true),
                'input' => get_post_meta($shortcut->ID, '_shortcut_input', true),
                'result' => get_post_meta($shortcut->ID, '_shortcut_result', true),
                'actions' => get_post_meta($shortcut->ID, '_shortcut_actions', true),
                'state' => $shortcut->post_status === 'publish' ? 'publish' : 'draft',
                'deleted' => $shortcut->post_status === 'trash'
            ),
            'switchblade' => array(
                'headline' => get_post_meta($shortcut->ID, '_shortcut_headline', true),
                'website' => get_permalink($shortcut),
                'sb_id' => $sb_id
            )
        );

        $data[] = $shortcut_data;
    }

    // DEBUG: Log WordPress request and response for fetching shortcuts
    sh_debug_log('WordPress - Fetch Shortcuts', [
        'request' => [
            'filter' => $filter,
            'status' => $status,
            'deleted' => $deleted,
            'wp_query_args' => $args
        ],
        'response' => [
            'total_shortcuts' => count($shortcuts),
            'shortcuts' => $data
        ],
        'source' => [
            'file' => __FILE__,
            'line' => __LINE__,
            'function' => __FUNCTION__
        ],
        'debug' => false
    ]);

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

        // Get all metadata in a structured format
        $data = array(
            'ID' => $shortcut->ID,
            'wordpress' => array(
                'name' => $shortcut->post_title,
                'description' => $shortcut->post_content,
                'color' => get_post_meta($post_id, '_shortcut_color', true),
                'icon' => get_post_meta($post_id, '_shortcut_icon', true),
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

        // DEBUG: Log WordPress request and response for fetching shortcut
        sh_debug_log('WordPress - Fetch Shortcut', [
            'request' => [
                'post_id' => $post_id,
                'source' => $source
            ],
            'response' => $data,
            'source' => [
                'file' => __FILE__,
                'line' => __LINE__,
                'function' => __FUNCTION__
            ]
        ]);

        wp_send_json_success($data);
    } else if ($source === 'SB') {
        $response = sb_api_call('shortcuts/' . $post_id);
        
        // DEBUG: Log Switchblade API request and response for fetching shortcut
        if (is_wp_error($response)) {
            sh_debug_log('Switchblade API Error - Get Shortcut', [
                'error' => [
                    'code' => $response->get_error_code(),
                    'message' => $response->get_error_message()
                ],
                'request' => [
                    'endpoint' => 'shortcuts/' . $post_id,
                    'method' => 'GET'
                ],
                'source' => [
                    'file' => __FILE__,
                    'line' => __LINE__,
                    'function' => __FUNCTION__
                ]
            ]);
            wp_send_json_error(array('message' => "Failed to fetch shortcut $post_id from Switchblade: " . $response->get_error_message()));
            return;
        }

        wp_send_json_success($response);
    } else {
        wp_send_json_error(array('message' => "Invalid source '$source' specified. Must be either 'WP' or 'SB'."));
    }
}

function create_shortcut() {
    // DEBUG: Log create shortcut request data to verify all fields are being passed correctly
    sh_debug_log('Create Shortcut Request', [
        'post_data' => $_POST,
        'request_method' => $_SERVER['REQUEST_METHOD'],
        'action' => isset($_POST['action']) ? $_POST['action'] : 'not_set',
        'security' => isset($_POST['security']) ? substr($_POST['security'], 0, 8) . '...' : 'not_set',
        'user_logged_in' => is_user_logged_in(),
        'ajax_url' => admin_url('admin-ajax.php'),
        'wp_doing_ajax' => wp_doing_ajax(),
        'request_uri' => $_SERVER['REQUEST_URI'],
        'headers' => getallheaders(),
        'source' => [
            'file' => __FILE__,
            'line' => __LINE__,
            'function' => __FUNCTION__
        ]
    ]);
    
    // Only verify nonce for logged-in users
    if (is_user_logged_in()) {
        if (!isset($_POST['security'])) {
            // DEBUG: Log failed security token verification
            sh_debug_log('Create Shortcut Failed - No Security Token', [
                'error' => 'Security token missing',
                'user_logged_in' => true,
                'source' => [
                    'file' => __FILE__,
                    'line' => __LINE__,
                    'function' => __FUNCTION__
                ]
            ]);
            send_json_response(['message' => 'No security token provided for logged-in user'], false);
            return;
        }

        if (!wp_verify_nonce($_POST['security'], 'shortcuts_hub_nonce')) {
            // DEBUG: Log failed security token verification
            sh_debug_log('Create Shortcut Failed - Invalid Security Token', [
                'error' => 'Invalid security token',
                'provided_token' => $_POST['security'],
                'source' => [
                    'file' => __FILE__,
                    'line' => __LINE__,
                    'function' => __FUNCTION__
                ]
            ]);
            send_json_response(['message' => 'Invalid security token'], false);
            return;
        }
    }

    $shortcut_data = isset($_POST['shortcut_data']) ? $_POST['shortcut_data'] : [];
    $wp_data = isset($_POST['wp_data']) ? $_POST['wp_data'] : [];
    
    if (empty($shortcut_data['name']) || empty($shortcut_data['headline'])) {
        // DEBUG: Log failed validation of required fields
        sh_debug_log('Create Shortcut Failed - Missing Required Fields', [
            'error' => 'Required fields missing',
            'name_exists' => !empty($shortcut_data['name']),
            'headline_exists' => !empty($shortcut_data['headline']),
            'source' => [
                'file' => __FILE__,
                'line' => __LINE__,
                'function' => __FUNCTION__
            ]
        ]);
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
        // DEBUG: Log meta data being saved to verify all fields are present and formatted correctly
        sh_debug_log('Create Shortcut - Saving Meta Data', [
            'post_id' => $post_id,
            'shortcut_data' => $shortcut_data,
            'wp_data' => $wp_data,
            'source' => [
                'file' => __FILE__,
                'line' => __LINE__,
                'function' => __FUNCTION__
            ]
        ]);

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
                $icon_data = stripslashes($wp_data['icon']);
                update_post_meta($post_id, '_shortcut_icon', wp_slash($icon_data));
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

        // Get settings
        $settings = get_shortcuts_hub_settings();
        $sb_response = sb_api_call('shortcuts', 'POST', [], $sb_data);
        
        if (is_wp_error($sb_response)) {
            // DEBUG: Log failed Switchblade API call
            sh_debug_log('Create Shortcut Failed - Switchblade API Error', [
                'error' => $sb_response->get_error_message(),
                'error_code' => $sb_response->get_error_code(),
                'error_data' => $sb_response->get_error_data(),
                'request' => [
                    'endpoint' => 'shortcuts',
                    'method' => 'POST',
                    'data' => $sb_data
                ],
                'source' => [
                    'file' => __FILE__,
                    'line' => __LINE__,
                    'function' => __FUNCTION__
                ]
            ]);
            
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
            // DEBUG: Log invalid Switchblade response format
            sh_debug_log('Create Shortcut Failed - Invalid Response Format', [
                'error' => 'Invalid Switchblade response format',
                'response' => $sb_response,
                'source' => [
                    'file' => __FILE__,
                    'line' => __LINE__,
                    'function' => __FUNCTION__
                ]
            ]);
            
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
        
        // DEBUG: Log update attempt but don't fail if it doesn't work
        sh_debug_log('Update Shortcut Website URL', [
            'post_id' => $post_id,
            'sb_id' => $id,
            'website_url' => $actual_website_url,
            'response' => $update_response,
            'source' => [
                'file' => __FILE__,
                'line' => __LINE__,
                'function' => __FUNCTION__
            ]
        ]);
        
        // DEBUG: Log successful shortcut creation with both WordPress and Switchblade IDs
        sh_debug_log('Create Shortcut Success', [
            'post_id' => $post_id,
            'sb_id' => $id,
            'source' => [
                'file' => __FILE__,
                'line' => __LINE__,
                'function' => __FUNCTION__
            ]
        ]);
        send_json_response(['message' => 'Shortcut created successfully.', 'post_id' => $post_id, 'sb_id' => $id]);
    } else {
        // DEBUG: Log failed WordPress post creation
        sh_debug_log('Create Shortcut Failed - WordPress Error', [
            'error' => $post_id->get_error_message(),
            'error_code' => $post_id->get_error_code(),
            'error_data' => $post_id->get_error_data(),
            'source' => [
                'file' => __FILE__,
                'line' => __LINE__,
                'function' => __FUNCTION__
            ]
        ]);
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

    // DEBUG: Log WordPress and Switchblade update
    sh_debug_log('Update Shortcut - WordPress and Switchblade', [
        'request' => [
            'post_id' => $post_id,
            'sb_id' => $sb_id,
            'shortcut_data' => $shortcut_data
        ],
        'response' => [
            'wordpress' => [
                'post_id' => $post_id,
                'post_status' => $post_data['post_status']
            ],
            'switchblade' => $response
        ],
        'source' => [
            'file' => __FILE__,
            'line' => __LINE__,
            'function' => __FUNCTION__
        ]
    ]);

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

    // DEBUG: Log WordPress and Switchblade update
    sh_debug_log('Toggle Draft - WordPress and Switchblade', [
        'request' => [
            'post_id' => $post_id,
            'sb_id' => $sb_id,
            'new_status' => $new_status
        ],
        'response' => [
            'wordpress' => [
                'post_id' => $post_id,
                'post_status' => $new_status
            ],
            'switchblade' => !empty($sb_id) ? [
                'sb_id' => $sb_id,
                'state' => $is_draft ? 'Draft' : 'Published'
            ] : null
        ],
        'source' => [
            'file' => __FILE__,
            'line' => __LINE__,
            'function' => __FUNCTION__
        ]
    ]);

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

    // DEBUG: Log WordPress and Switchblade delete
    sh_debug_log('Delete Shortcut - WordPress and Switchblade', [
        'request' => [
            'post_id' => $post_id,
            'sb_id' => $sb_id,
            'permanent' => $permanent,
            'restore' => $restore
        ],
        'response' => [
            'wordpress' => [
                'post_id' => $post_id,
                'result' => $result
            ],
            'switchblade' => !empty($sb_id) ? [
                'sb_id' => $sb_id,
                'response' => is_wp_error($sb_response) ? [
                    'error' => true,
                    'code' => $sb_response->get_error_code(),
                    'message' => $sb_response->get_error_message()
                ] : $sb_response
            ] : null
        ],
        'source' => [
            'file' => __FILE__,
            'line' => __LINE__,
            'function' => __FUNCTION__
        ]
    ]);

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

    // DEBUG: Log download token processing
    sh_debug_log('Process Download Token', [
        'request' => [
            'download_token' => $download_token,
            'shortcut_id' => $shortcut_id,
            'version_id' => $version_id
        ],
        'response' => [
            'download_url' => $version_data['url'],
            'version_data' => $version_data
        ],
        'source' => [
            'file' => __FILE__,
            'line' => __LINE__,
            'function' => __FUNCTION__
        ]
    ]);

    // Return success with download URL and metadata
    wp_send_json_success([
        'download_url' => $version_data['url'],
        'shortcut_id' => $shortcut_id,
        'version_data' => $version_data
    ]);
}

class Shortcuts_Ajax_Handler {
    private static $instance = null;
    private static $registered = false;

    public static function instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        // Empty constructor
    }

    public function is_registered() {
        return self::$registered;
    }

    public function register_handlers() {
        if (self::$registered) {
            return;
        }

        self::$registered = true;  

        // Register AJAX handlers with namespace
        add_action('wp_ajax_create_shortcut', __NAMESPACE__ . '\\create_shortcut');
        add_action('wp_ajax_fetch_shortcuts', __NAMESPACE__ . '\\fetch_shortcuts');
        add_action('wp_ajax_fetch_shortcut', __NAMESPACE__ . '\\fetch_shortcut');
        add_action('wp_ajax_update_shortcut', __NAMESPACE__ . '\\update_shortcut');
        add_action('wp_ajax_toggle_draft', __NAMESPACE__ . '\\toggle_draft');
        add_action('wp_ajax_delete_shortcut', __NAMESPACE__ . '\\delete_shortcut');
        add_action('wp_ajax_process_download_token', __NAMESPACE__ . '\\process_download_token');
    }
}

function send_json_response($response, $success = true) {
    // DEBUG: Log AJAX response to verify correct data structure and headers
    sh_debug_log('Sending AJAX Response', [
        'success' => $success,
        'response' => $response,
        'headers_sent' => headers_sent(),
        'headers_list' => headers_list(),
        'content_type' => ini_get('default_mimetype'),
        'output_started' => ob_get_level() > 0,
        'buffer_contents' => ob_get_contents(),
        'source' => [
            'file' => __FILE__,
            'line' => __LINE__,
            'function' => __FUNCTION__
        ]
    ]);

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

    // DEBUG: Log the fetch actions request
    sh_debug_log('Fetch Actions Request', array(
        'message' => 'Fetching actions with filters',
        'source' => array(
            'file' => __FILE__,
            'line' => __LINE__,
            'function' => __FUNCTION__
        ),
        'data' => array(
            'search' => isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '',
            'status' => isset($_POST['status']) ? sanitize_text_field($_POST['status']) : ''
        ),
        'debug' => true
    ));

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

    // DEBUG: Log the fetch actions response
    sh_debug_log('Fetch Actions Response', array(
        'message' => 'Actions fetched successfully',
        'source' => array(
            'file' => __FILE__,
            'line' => __LINE__,
            'function' => __FUNCTION__
        ),
        'data' => array(
            'count' => count($formatted_actions),
            'actions' => $formatted_actions
        ),
        'debug' => true
    ));

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

    // DEBUG: Log the create action request
    sh_debug_log('Create Action Request', array(
        'message' => 'Creating new action',
        'source' => array(
            'file' => __FILE__,
            'line' => __LINE__,
            'function' => __FUNCTION__
        ),
        'data' => array(
            'name' => sanitize_text_field($_POST['name']),
            'description' => isset($_POST['description']) ? sanitize_textarea_field($_POST['description']) : '',
            'icon' => isset($_POST['icon']) ? $_POST['icon'] : ''
        ),
        'debug' => true
    ));

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

    // DEBUG: Log the create action response
    sh_debug_log('Create Action Response', array(
        'message' => 'Action created successfully',
        'source' => array(
            'file' => __FILE__,
            'line' => __LINE__,
            'function' => __FUNCTION__
        ),
        'data' => array(
            'action_id' => $action_id,
            'post_data' => $action_data
        ),
        'debug' => true
    ));

    wp_send_json_success(array(
        'id' => $action_id,
        'message' => 'Action created successfully'
    ));
}
add_action('wp_ajax_create_action', 'shortcuts_hub_create_action');
