<?php

if (!defined('ABSPATH')) {
    exit;
}

function fetch_shortcuts() {
    check_ajax_referer('shortcuts_hub_nonce', 'security');

    $filter = isset($_POST['filter']) ? sanitize_text_field($_POST['filter']) : '';
    $source = isset($_POST['source']) ? sanitize_text_field($_POST['source']) : 'WP'; // Default to WP

    if ($source === 'WP') {
        $args = array(
            'post_type' => 'shortcut',
            'numberposts' => -1,
        );

        if (!empty($filter)) {
            $args['s'] = $filter;
        }

        $shortcuts = get_posts($args);
        $data = array();

        foreach ($shortcuts as $shortcut) {
            $data[] = array(
                'id' => $shortcut->ID,
                'name' => $shortcut->post_title,
                'headline' => get_post_meta($shortcut->ID, 'headline', true),
                'description' => get_post_meta($shortcut->ID, 'description', true),
                'color' => get_post_meta($shortcut->ID, 'color', true),
                'icon' => get_post_meta($shortcut->ID, 'icon', true),
                'input' => get_post_meta($shortcut->ID, 'input', true),
                'result' => get_post_meta($shortcut->ID, 'result', true),
                'id' => get_post_meta($shortcut->ID, 'id', true),
                'post_date' => $shortcut->post_date,
                'deleted' => get_post_meta($shortcut->ID, 'deleted', true),
                'draft' => get_post_meta($shortcut->ID, 'draft', true),
            );
        }

        wp_send_json_success($data);
    } elseif ($source === 'SB') {
        $sb_response = sb_api_call('/shortcuts', 'GET', ['filter' => $filter]);
        if (is_wp_error($sb_response)) {
            wp_send_json_error(['message' => 'Error fetching from Switchblade: ' . $sb_response->get_error_message()]);
            return;
        }

        wp_send_json_success($sb_response);
    } else {
        wp_send_json_error(['message' => 'Invalid source specified.']);
    }
}

function fetch_shortcut() {
    check_ajax_referer('shortcuts_hub_nonce', 'security');

    $post_id = intval($_POST['post_id']);
    $source = isset($_POST['source']) ? sanitize_text_field($_POST['source']) : 'WP';

    if ($source === 'WP') {
        $shortcut = get_post($post_id);
        if ($shortcut) {
            $response = array(
                'success' => true,
                'data' => array(
                    'post_id' => $shortcut->id,
                    'name' => $shortcut->post_title,
                    'headline' => get_post_meta($post_id, 'headline', true),
                    'description' => get_post_meta($post_id, 'description', true),
                    'color' => get_post_meta($post_id, 'color', true),
                    'icon' => get_post_meta($post_id, 'icon', true),
                    'input' => get_post_meta($post_id, 'input', true),
                    'result' => get_post_meta($post_id, 'result', true),
                    'id' => get_post_meta($post_id, 'id', true),
                    'post_date' => $shortcut->post_date,
                    'deleted' => get_post_meta($post_id, 'deleted', true),
                    'draft' => get_post_meta($post_id, 'draft', true),
                )
            );
        } else {
            $response = array('success' => false, 'message' => 'Shortcut not found.');
        }
    } elseif ($source === 'SB') {
        $sb_response = sb_api_call($id);
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
    check_ajax_referer('shortcuts_hub_nonce', 'security');

    $shortcut_data = isset($_POST['shortcut_data']) ? $_POST['shortcut_data'] : [];

    if (empty($shortcut_data['name']) || empty($shortcut_data['headline'])) {
        wp_send_json_error(array('message' => 'Required fields are missing.'));
        return;
    }

    $state = isset($shortcut_data['state']) && $shortcut_data['state'] === 'draft' ? 1 : 0;

    $sb_data = array(
        'name' => sanitize_text_field($shortcut_data['name']),
        'headline' => sanitize_text_field($shortcut_data['headline']),
        'description' => isset($shortcut_data['description']) ? sanitize_textarea_field($shortcut_data['description']) : '',
        'state' => $state,
    );

    $endpoint = 'shortcuts';
    $api_url = SB_URL . '/' . ltrim($endpoint, '/');

    $sb_response = sb_api_call($endpoint, 'POST', [], $sb_data);
    
    if (is_wp_error($sb_response)) {
        wp_send_json_error(array('message' => 'Failed to create shortcut in Switchblade: ' . $sb_response->get_error_message()));
        return;
    }

    $id = isset($sb_response['shortcut']['id']) ? $sb_response['shortcut']['id'] : null;

    if (!$id) {
        wp_send_json_error(array('message' => 'Switchblade did not return a valid ID.'));
        return;
    }

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
        update_post_meta($post_id, 'id', sanitize_text_field($id));
        update_post_meta($post_id, 'name', sanitize_text_field($shortcut_data['name']));
        
        if (isset($shortcut_data['actions']) && is_array($shortcut_data['actions'])) {
            update_post_meta($post_id, 'actions', $shortcut_data['actions']);
        }

        wp_send_json_success(array('message' => 'Shortcut created successfully.', 'post_id' => $post_id, 'id' => $id));
    } else {
        wp_send_json_error(array('message' => 'Failed to create shortcut in WordPress.'));
    }
}

function update_shortcut() {
    check_ajax_referer('shortcuts_hub_nonce', 'security');

    $shortcut_data = $_POST['shortcut_data'];
    $post_id = intval($shortcut_data['id']);

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
        update_post_meta($updated_post_id, 'icon', sanitize_text_field($shortcut_data['icon']));
        update_post_meta($updated_post_id, 'input', sanitize_text_field($shortcut_data['input']));
        update_post_meta($updated_post_id, 'result', sanitize_text_field($shortcut_data['result']));
        update_post_meta($updated_post_id, 'id', sanitize_text_field($shortcut_data['id']));
        update_post_meta($updated_post_id, 'name', sanitize_text_field($shortcut_data['name']));

        wp_send_json_success(array('message' => 'Shortcut updated successfully.'));
    } else {
        wp_send_json_error(array('message' => 'Failed to update shortcut.'));
    }
}

function toggle_draft() {
    check_ajax_referer('shortcuts_hub_nonce', 'security');

    $post_id = intval($_POST['post_id']);
    $current_status = get_post_status($post_id);
    $new_status = $current_status === 'draft' ? 'publish' : 'draft';

    $post_data = array(
        'post_id' => $post_id,
        'post_status' => $new_status,
    );

    $updated_post_id = wp_update_post($post_data);

    if (is_wp_error($updated_post_id)) {
        wp_send_json_error(array('message' => $updated_post_id->get_error_message()));
        return;
    }

    $id = get_post_meta($post_id, 'id', true);
    $sb_response = sb_api_call('/shortcuts/' . $id , 'PATCH', [], array(
        'state' => $new_status
    ));

    if (is_wp_error($sb_response)) {
        wp_send_json_error(array('message' => $sb_response->get_error_message()));
        return;
    }

    wp_send_json_success(array('new_status' => $new_status));
}

function toggle_delete() {
    check_ajax_referer('shortcuts_hub_nonce', 'security');

    $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;

    if ($post_id > 0) {
        $deleted = wp_trash_post($post_id);

        $id = get_post_meta($id, 'id', true);
        wp_send_json_success(array('message' => 'Shortcut deleted successfully.', 'id' => $id));
    } else {
        wp_send_json_error(array('message' => 'Invalid shortcut ID.'));
    }
}

add_action('wp_ajax_fetch_shortcuts', 'fetch_shortcuts');
add_action('wp_ajax_fetch_shortcut', 'fetch_shortcut');
add_action('wp_ajax_create_shortcut', 'create_shortcut');
add_action('wp_ajax_update_shortcut', 'update_shortcut');
add_action('wp_ajax_toggle_draft', 'toggle_draft');
add_action('wp_ajax_toggle_delete', 'toggle_delete');
