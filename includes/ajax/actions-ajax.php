<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * AJAX handler for fetching actions
 */
function shortcuts_hub_fetch_actions() {
    global $wpdb;
    check_ajax_referer('shortcuts_hub_fetch_actions_nonce', 'security');

    $search = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';
    $status = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : '';
    $trash = isset($_POST['trash']) ? sanitize_text_field($_POST['trash']) : '';

    // Build query args
    $args = array(
        'post_type' => 'action',
        'posts_per_page' => -1,
        'orderby' => 'title',
        'order' => 'ASC',
        'post_status' => isset($_POST['status']) ? sanitize_text_field($_POST['status']) : 'any'
    );

    // Handle trash filter
    if (!empty($trash)) {
        if ($trash === 'trash') {
            $args['post_status'] = 'trash';
        }
    } else {
        // Exclude trash by default
        if ($args['post_status'] === 'trash') {
            $args['post_status'] = 'publish';
        }
    }

    // Handle search
    if (!empty($search)) {
        $args['s'] = $search;
    }

    // Get posts
    $query = new WP_Query($args);
    $actions = array();

    if ($query->have_posts()) {
        $action_shortcut_table = $wpdb->prefix . 'shortcuts_hub_action_shortcut';
        
        while ($query->have_posts()) {
            $query->the_post();
            $post = get_post();
            
            // Add icon and color metadata to the post object
            $post->icon = get_post_meta($post->ID, '_action_icon', true);
            $post->color = get_post_meta($post->ID, '_action_color', true);
            
            // Get the count of associated shortcuts
            $shortcut_count = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $action_shortcut_table WHERE action_id = %d",
                $post->ID
            ));
            
            $post->shortcut_count = (int) $shortcut_count;
            $actions[] = $post;
        }
        wp_reset_postdata();
    }

    wp_send_json_success($actions);
}
add_action('wp_ajax_fetch_actions', 'shortcuts_hub_fetch_actions');

/**
 * AJAX handler for creating a new action
 */
function shortcuts_hub_create_action() {
    check_ajax_referer('shortcuts_hub_create_action_nonce', 'security');

    $form_data = isset($_POST['formData']) ? $_POST['formData'] : array();
    
    if (empty($form_data['name'])) {
        wp_send_json_error(array('message' => 'Action name is required.'));
        return;
    }

    $post_data = array(
        'post_title'   => sanitize_text_field($form_data['name']),
        'post_content' => wp_kses_post($form_data['description']),
        'post_type'    => 'action',
        'post_status'  => sanitize_text_field($form_data['status']),
        'meta_input'   => array(
            'icon' => isset($form_data['icon']) ? sanitize_text_field($form_data['icon']) : ''
        )
    );

    $post_id = wp_insert_post($post_data);

    if (is_wp_error($post_id)) {
        wp_send_json_error(array('message' => $post_id->get_error_message()));
        return;
    }

    wp_send_json_success(array('message' => 'Action created successfully.'));
}
add_action('wp_ajax_create_action', 'shortcuts_hub_create_action');

/**
 * AJAX handler for updating an action
 */
function shortcuts_hub_update_action() {
    check_ajax_referer('shortcuts_hub_update_action_nonce', 'security');

    $form_data = isset($_POST['formData']) ? $_POST['formData'] : array();
    
    if (empty($form_data['id'])) {
        wp_send_json_error(array('message' => 'Action ID is required.'));
        return;
    }

    $post_id = absint($form_data['id']);

    $post_data = array(
        'ID'           => $post_id,
        'post_title'   => sanitize_text_field($form_data['name']),
        'post_content' => wp_kses_post($form_data['description']),
        'post_status'  => sanitize_text_field($form_data['status'])
    );

    $updated = wp_update_post($post_data);

    if (is_wp_error($updated)) {
        wp_send_json_error(array('message' => $updated->get_error_message()));
        return;
    }

    // Update icon meta separately to ensure proper escaping
    if (isset($form_data['icon'])) {
        update_post_meta($post_id, '_action_icon', wp_slash($form_data['icon']));
    }

    wp_send_json_success(array('message' => 'Action updated successfully.'));
}
add_action('wp_ajax_update_action', 'shortcuts_hub_update_action');

/**
 * AJAX handler for deleting an action
 */
function shortcuts_hub_delete_action() {
    check_ajax_referer('shortcuts_hub_delete_action_nonce', 'security');

    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    $force = isset($_POST['force']) ? (bool)$_POST['force'] : false;

    if (empty($id)) {
        wp_send_json_error(array('message' => 'ID is required'));
        return;
    }

    // Check if the post exists and is of type 'action'
    $post = get_post($id);
    if (!$post || $post->post_type !== 'action') {
        wp_send_json_error(array('message' => 'Action not found'));
        return;
    }

    if ($force) {
        // Permanently delete the post and its meta
        $result = wp_delete_post($id, true);
    } else {
        // Move to trash
        $result = wp_trash_post($id);
    }

    if (!$result) {
        wp_send_json_error(array('message' => 'Failed to delete action'));
        return;
    }

    wp_send_json_success(array(
        'message' => $force ? 'Action permanently deleted' : 'Action moved to trash'
    ));
}
add_action('wp_ajax_delete_action', 'shortcuts_hub_delete_action');
