<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * AJAX handler for fetching actions
 */
function fetch_actions() {
    global $wpdb;
    check_ajax_referer('fetch_actions_nonce', 'security');
    
    $search = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';
    $status = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : 'publish';
    $trash = isset($_POST['trash']) ? (bool)$_POST['trash'] : false;
    
    $args = array(
        'post_type' => 'action',
        'posts_per_page' => -1,
        'orderby' => 'title',
        'order' => 'ASC',
        'post_status' => $status
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

    // Add search if provided
    if (!empty($search)) {
        $args['s'] = $search;
    }
    
    $query = new WP_Query($args);
    $actions = array();
    
    if ($query->have_posts()) {
        $action_shortcut_table = $wpdb->prefix . 'shortcuts_hub_action_shortcut';
        
        while ($query->have_posts()) {
            $query->the_post();
            $post = get_post();
            
            // Get shortcut count
            $shortcut_count = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $action_shortcut_table WHERE action_id = %d",
                $post->ID
            ));

            // Get icon data and ensure it's valid JSON
            $icon = get_post_meta($post->ID, 'action_icon', true);
            if ($icon) {
                // Try to decode to validate JSON
                $decoded = json_decode($icon);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    // If not valid JSON, try to fix common issues
                    $icon = wp_slash($icon); // Ensure proper escaping
                }
            }
            
            $actions[] = array(
                'ID' => $post->ID,
                'post_title' => $post->post_title,
                'post_content' => $post->post_content,
                'post_status' => $post->post_status,
                'shortcut_count' => $shortcut_count,
                'icon' => $icon
            );
        }
        wp_reset_postdata();
    }
    
    wp_send_json_success($actions);
}
add_action('wp_ajax_fetch_actions', 'fetch_actions');

/**
 * AJAX handler for creating a new action
 */
function create_action() {
    check_ajax_referer('create_action_nonce', 'security');

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
            'action_icon' => isset($form_data['icon']) ? sanitize_text_field($form_data['icon']) : ''
        )
    );

    $post_id = wp_insert_post($post_data);

    if (is_wp_error($post_id)) {
        wp_send_json_error(array('message' => $post_id->get_error_message()));
        return;
    }

    wp_send_json_success(array('message' => 'Action created successfully.'));
}
add_action('wp_ajax_create_action', 'create_action');

/**
 * AJAX handler for updating an action
 */
function update_action() {
    check_ajax_referer('update_action_nonce', 'security');

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
        // DEBUG: Log icon update
        sh_debug_log('Action Icon Update', array(
            'message' => 'Updating action icon',
            'source' => array(
                'file' => 'actions-ajax.php',
                'line' => __LINE__,
                'function' => __FUNCTION__
            ),
            'data' => array(
                'action_id' => $post_id,
                'icon_data' => $form_data['icon']
            ),
            'debug' => true
        ));
        update_post_meta($post_id, 'action_icon', wp_slash($form_data['icon']));
    }

    wp_send_json_success(array('message' => 'Action updated successfully.'));
}
add_action('wp_ajax_update_action', 'update_action');

/**
 * AJAX handler for deleting an action
 */
function delete_action() {
    check_ajax_referer('delete_action_nonce', 'security');

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
add_action('wp_ajax_delete_action', 'delete_action');
