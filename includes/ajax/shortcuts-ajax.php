<?php

if (!defined('ABSPATH')) {
    exit;
}

// Fetch all WordPress shortcuts with filtering options
function fetch_wp_shortcuts() {
    check_ajax_referer('shortcuts_hub_nonce', 'security');
    $args = array(
        'post_type' => 'shortcut',
        'post_status' => 'any',
        'numberposts' => -1
    );

    if (!empty($_POST['search'])) {
        $args['s'] = sanitize_text_field($_POST['search']);
    }

    $posts = get_posts($args);
    $shortcuts_data = array();

    foreach ($posts as $post) {
        $shortcuts_data[] = array(
            'id' => $post->ID,
            'name' => get_the_title($post),
            'headline' => get_post_meta($post->ID, 'headline', true),
            'description' => get_post_meta($post->ID, 'description', true),
            'input' => get_post_meta($post->ID, 'input', true),
            'result' => get_post_meta($post->ID, 'result', true),
            'color' => get_post_meta($post->ID, 'color', true),
            'icon' => get_post_meta($post->ID, 'icon', true),
            'actions' => get_post_meta($post->ID, 'actions', true),
            'deleted' => get_post_status($post->ID) === 'trash'
        );
    }

    wp_send_json_success($shortcuts_data);
}

// Fetch a specific WordPress shortcut
function fetch_wp_shortcut() {
    check_ajax_referer('shortcuts_hub_nonce', 'security');
    $shortcut_id = intval($_POST['shortcut_id']);
    $post = get_post($shortcut_id);

    if (!$post || $post->post_type !== 'shortcut') {
        wp_send_json_error('Shortcut not found');
        return;
    }

    $shortcut_data = array(
        'id' => $post->ID,
        'name' => get_the_title($post),
        'headline' => get_post_meta($post->ID, 'headline', true),
        'description' => get_post_meta($post->ID, 'description', true),
        'input' => get_post_meta($post->ID, 'input', true),
        'result' => get_post_meta($post->ID, 'result', true),
        'color' => get_post_meta($post->ID, 'color', true),
        'icon' => get_post_meta($post->ID, 'icon', true),
        'actions' => get_post_meta($post->ID, 'actions', true),
        'deleted' => get_post_status($post->ID) === 'trash'
    );

    wp_send_json_success($shortcut_data);
}

// Create a WordPress shortcut
function create_wp_shortcut() {
    check_ajax_referer('shortcuts_hub_nonce', 'security');

    $shortcut_data = $_POST['shortcut_data'];
    $post_data = array(
        'post_title'   => sanitize_text_field($shortcut_data['name']),
        'post_content' => sanitize_textarea_field($shortcut_data['description']),
        'post_status'  => $shortcut_data['state'] ? 'draft' : 'publish',
        'post_type'    => 'shortcut',
        'meta_input'   => array(
            'headline' => sanitize_text_field($shortcut_data['headline']),
            'input'    => sanitize_text_field($shortcut_data['input']),
            'result'   => sanitize_text_field($shortcut_data['result']),
            'color'    => sanitize_hex_color($shortcut_data['color']),
            'icon'     => esc_url_raw($shortcut_data['icon']),
            'actions'  => array_map('sanitize_text_field', $shortcut_data['actions']),
            'sb_id'    => sanitize_text_field($shortcut_data['sb_id'])
        )
    );

    $post_id = wp_insert_post($post_data);

    if (is_wp_error($post_id)) {
        wp_send_json_error(array('message' => $post_id->get_error_message()));
        return;
    }

    wp_send_json_success(array('post_id' => $post_id));
}

// Update a WordPress shortcut
function update_wp_shortcut() {
    check_ajax_referer('shortcuts_hub_nonce', 'security');

    $shortcut_id = intval($_POST['shortcut_id']);
    $shortcut_data = $_POST['shortcut_data'];

    $post_data = array(
        'ID'           => $shortcut_id,
        'post_title'   => sanitize_text_field($shortcut_data['name']),
        'post_content' => sanitize_textarea_field($shortcut_data['description']),
        'post_status'  => $shortcut_data['state'] ? 'draft' : 'publish',
    );

    $post_id = wp_update_post($post_data);

    if (is_wp_error($post_id)) {
        wp_send_json_error(array('message' => $post_id->get_error_message()));
        return;
    }

    update_post_meta($shortcut_id, 'headline', sanitize_text_field($shortcut_data['headline']));
    update_post_meta($shortcut_id, 'input', sanitize_text_field($shortcut_data['input']));
    update_post_meta($shortcut_id, 'result', sanitize_text_field($shortcut_data['result']));
    update_post_meta($shortcut_id, 'color', sanitize_hex_color($shortcut_data['color']));
    update_post_meta($shortcut_id, 'icon', esc_url_raw($shortcut_data['icon']));
    update_post_meta($shortcut_id, 'actions', array_map('sanitize_text_field', $shortcut_data['actions']));

    wp_send_json_success(array('post_id' => $post_id));
}

// Delete a WordPress shortcut
function delete_wp_shortcut() {
    check_ajax_referer('shortcuts_hub_nonce', 'security');
    $shortcut_id = intval($_POST['shortcut_id']);
    if (wp_trash_post($shortcut_id)) {
        wp_send_json_success();
    } else {
        wp_send_json_error('Failed to delete shortcut');
    }
}

// Restore a WordPress shortcut
function restore_wp_shortcut() {
    check_ajax_referer('shortcuts_hub_nonce', 'security');
    $shortcut_id = intval($_POST['shortcut_id']);
    if (wp_untrash_post($shortcut_id)) {
        wp_send_json_success();
    } else {
        wp_send_json_error('Failed to restore shortcut');
    }
}

// Fetch all Switchblade shortcuts
function fetch_sb_shortcuts() {
    $response = sb_fetch_shortcuts();
    if (is_wp_error($response)) {
        wp_send_json_error('Error fetching SB shortcuts');
    } else {
        wp_send_json_success($response);
    }
}

// Fetch a specific Switchblade shortcut
function fetch_sb_shortcut() {
    check_ajax_referer('shortcuts_hub_nonce', 'security');
    $shortcut_id = sanitize_text_field($_POST['shortcut_id']);
    $response = sb_fetch_single_shortcut($shortcut_id);

    if (is_wp_error($response)) {
        wp_send_json_error('Error fetching SB shortcut: ' . $response->get_error_message());
        return;
    }

    wp_send_json_success($response);
}

// Create a Switchblade shortcut
function create_sb_shortcut() {
    check_ajax_referer('shortcuts_hub_nonce', 'security');

    $shortcut_data = $_POST['shortcut_data'];
    $response = sb_api_call('/shortcuts', 'POST', [], $shortcut_data);

    if (is_wp_error($response)) {
        wp_send_json_error(array('message' => $response->get_error_message()));
        return;
    }

    wp_send_json_success($response);
}

// Update a Switchblade shortcut
function update_sb_shortcut() {
    check_ajax_referer('shortcuts_hub_nonce', 'security');

    $shortcut_id = sanitize_text_field($_POST['shortcut_id']);
    $shortcut_data = $_POST['shortcut_data'];
    $response = sb_update_shortcut($shortcut_id, $shortcut_data);

    if (is_wp_error($response)) {
        wp_send_json_error(array('message' => $response->get_error_message()));
        return;
    }

    wp_send_json_success($response);
}

// Delete a Switchblade shortcut
function delete_sb_shortcut() {
    check_ajax_referer('shortcuts_hub_nonce', 'security');

    $shortcut_id = sanitize_text_field($_POST['shortcut_id']);
    $response = sb_delete_shortcut($shortcut_id);

    if (is_wp_error($response)) {
        wp_send_json_error(array('message' => $response->get_error_message()));
        return;
    }

    wp_send_json_success(array('message' => 'Shortcut marked as deleted'));
}

// Restore a Switchblade shortcut
function restore_sb_shortcut() {
    check_ajax_referer('shortcuts_hub_nonce', 'security');

    $shortcut_id = sanitize_text_field($_POST['shortcut_id']);
    $response = sb_restore_shortcut($shortcut_id);

    if (is_wp_error($response)) {
        wp_send_json_error(array('message' => $response->get_error_message()));
        return;
    }

    wp_send_json_success(array('message' => 'Shortcut restored successfully'));
}

add_action('wp_ajax_fetch_wp_shortcuts', 'fetch_wp_shortcuts');
add_action('wp_ajax_fetch_wp_shortcut', 'fetch_wp_shortcut');
add_action('wp_ajax_create_wp_shortcut', 'create_wp_shortcut');
add_action('wp_ajax_update_wp_shortcut', 'update_wp_shortcut');
add_action('wp_ajax_delete_wp_shortcut', 'delete_wp_shortcut');
add_action('wp_ajax_restore_wp_shortcut', 'restore_wp_shortcut');

add_action('wp_ajax_fetch_sb_shortcuts', 'fetch_sb_shortcuts');
add_action('wp_ajax_fetch_sb_shortcut', 'fetch_sb_shortcut');
add_action('wp_ajax_create_sb_shortcut', 'create_sb_shortcut');
add_action('wp_ajax_update_sb_shortcut', 'update_sb_shortcut');
add_action('wp_ajax_delete_sb_shortcut', 'delete_sb_shortcut');
add_action('wp_ajax_restore_sb_shortcut', 'restore_sb_shortcut');
