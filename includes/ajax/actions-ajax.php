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
    $status = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : '';
    $trash = isset($_POST['trash']) ? sanitize_text_field($_POST['trash']) : '';
    
    $args = array(
        'post_type' => 'action',
        'posts_per_page' => -1,
        'orderby' => 'title',
        'order' => 'ASC'
    );
    
    // Add status filter if provided
    if (!empty($status)) {
        $args['post_status'] = $status;
    }
    
    // Handle trash filter
    if (!empty($trash)) {
        if ($trash === 'trash') {
            $args['post_status'] = 'trash';
        } else if ($trash === 'active') {
            $args['post_status'] = array('publish', 'draft');
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
            
            // Get meta fields
            $icon = get_post_meta($post->ID, 'action_icon', true);
            $input = get_post_meta($post->ID, 'action_input', true);
            $result = get_post_meta($post->ID, 'action_result', true);
            
            // Get associated shortcuts - only return the IDs
            $shortcuts = array();
            $shortcut_ids = $wpdb->get_col($wpdb->prepare(
                "SELECT shortcut_id FROM $action_shortcut_table WHERE action_id = %d",
                $post->ID
            ));
            
            // Convert shortcut_ids to integers and use them directly
            if (!empty($shortcut_ids)) {
                $shortcuts = array_map('intval', $shortcut_ids);
            }
            
            // Create the permalink for the action using the format: site_url/actions/action-name-with-dashes
            $action_slug = sanitize_title($post->post_title); // Convert title to slug format
            $permalink = trailingslashit(site_url()) . 'actions/' . $action_slug . '/'; 
            
            $actions[] = array(
                'ID' => $post->ID,
                'post_title' => $post->post_title,
                'post_content' => $post->post_content,
                'post_status' => $post->post_status,
                'icon' => $icon,
                'input' => $input,
                'result' => $result,
                'shortcuts' => $shortcuts,
                'permalink' => $permalink
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
            'action_icon' => isset($form_data['icon']) ? sanitize_text_field($form_data['icon']) : '',
            'action_input' => isset($form_data['input']) ? sanitize_text_field($form_data['input']) : '',
            'action_result' => isset($form_data['result']) ? sanitize_text_field($form_data['result']) : ''
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
    global $wpdb;
    check_ajax_referer('update_action_nonce', 'security');
    
    $action_id = isset($_POST['action_id']) ? intval($_POST['action_id']) : 0;
    $name = isset($_POST['name']) ? sanitize_text_field($_POST['name']) : '';
    $description = isset($_POST['description']) ? wp_kses_post($_POST['description']) : '';
    $icon = isset($_POST['icon']) ? sanitize_text_field($_POST['icon']) : '';
    $input = isset($_POST['input']) ? sanitize_text_field($_POST['input']) : '';
    $result = isset($_POST['result']) ? sanitize_text_field($_POST['result']) : '';
    $shortcuts = isset($_POST['shortcuts']) ? $_POST['shortcuts'] : array();
    $status = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : 'publish';
    
    // Process shortcuts array - ensure we have integers
    if (!empty($shortcuts)) {
        // If shortcuts is a string, try to decode it as JSON
        if (is_string($shortcuts)) {
            // Check if it's a JSON array
            if (strpos($shortcuts, '[') === 0) {
                $decoded = json_decode($shortcuts, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $shortcuts = $decoded;
                } else {
                    $shortcuts = array();
                }
            } else {
                // It's a string but not JSON, try to convert it directly
                $shortcuts = array($shortcuts);
            }
        }
        
        // Ensure we have an array
        if (!is_array($shortcuts)) {
            $shortcuts = array($shortcuts);
        }
        
        // Convert all values to integers and filter out invalid values
        $shortcuts = array_filter(array_map(function($val) {
            // If it's already an integer or numeric string
            if (is_numeric($val)) {
                return intval($val);
            }
            // If it's an array/object with an ID property
            if (is_array($val) && isset($val['ID'])) {
                return intval($val['ID']);
            }
            if (is_array($val) && isset($val['id'])) {
                return intval($val['id']);
            }
            // If it's something else we can't handle
            return false;
        }, $shortcuts));
    } else {
        $shortcuts = array();
    }
    
    if (empty($action_id)) {
        wp_send_json_error(array('message' => 'Action ID is required.'));
        return;
    }
    
    if (empty($name)) {
        wp_send_json_error(array('message' => 'Action name is required.'));
        return;
    }
    
    // Verify action exists
    $action = get_post($action_id);
    if (!$action || $action->post_type !== 'action') {
        wp_send_json_error(array('message' => 'Invalid action ID.'));
        return;
    }
    
    // Begin transaction
    $wpdb->query('START TRANSACTION');
    
    try {
        // Update the action post
        $post_data = array(
            'ID'           => $action_id,
            'post_title'   => $name,
            'post_content' => $description,
            'post_status'  => $status
        );
        
        $updated = wp_update_post($post_data);
        
        if (is_wp_error($updated)) {
            throw new Exception($updated->get_error_message());
        }
        
        // Update meta fields
        update_post_meta($action_id, 'action_icon', $icon);
        update_post_meta($action_id, 'action_input', $input);
        update_post_meta($action_id, 'action_result', $result);
        
        // Update shortcut associations
        $table_name = $wpdb->prefix . 'shortcuts_hub_action_shortcut';
        
        // Delete all existing associations
        $wpdb->delete(
            $table_name,
            array('action_id' => $action_id),
            array('%d')
        );
        
        // Add new associations
        foreach ($shortcuts as $shortcut_id) {
            // Verify shortcut exists
            $shortcut = get_post($shortcut_id);
            
            if ($shortcut && $shortcut->post_type === 'shortcut') {
                $result = $wpdb->insert(
                    $table_name,
                    array(
                        'action_id' => $action_id,
                        'shortcut_id' => $shortcut_id,
                        'created_at' => current_time('mysql')
                    ),
                    array('%d', '%d', '%s')
                );
            }
        }
        
        // Commit transaction
        $wpdb->query('COMMIT');
        
        wp_send_json_success(array(
            'message' => 'Action updated successfully.'
        ));
    } catch (Exception $e) {
        // Rollback on error
        $wpdb->query('ROLLBACK');
        
        wp_send_json_error(array(
            'message' => 'Failed to update action: ' . $e->getMessage()
        ));
    }
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

/**
 * AJAX handler for fetching shortcuts for action association
 */
function fetch_shortcuts_for_action() {
    global $wpdb;
    check_ajax_referer('fetch_shortcuts_for_action_nonce', 'security');
    
    $search = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';
    // Handle both parameter names for backward compatibility
    $action_id = 0;
    if (isset($_POST['action_id'])) {
        $action_id = intval($_POST['action_id']);
    } elseif (isset($_POST['actionId'])) {
        $action_id = intval($_POST['actionId']);
    }
    
    // Get the associated shortcut IDs for this action if action_id is provided
    $associated_shortcut_ids = array();
    if ($action_id > 0) {
        $action_shortcut_table = $wpdb->prefix . 'shortcuts_hub_action_shortcut';
        
        $associated_shortcut_ids = $wpdb->get_col($wpdb->prepare(
            "SELECT shortcut_id FROM $action_shortcut_table WHERE action_id = %d",
            $action_id
        ));
        
        // Convert to integers
        $associated_shortcut_ids = array_map('intval', $associated_shortcut_ids);
    }
    
    // Get shortcuts with pagination to improve performance
    $args = array(
        'post_type' => 'shortcut',
        'posts_per_page' => 100, // Limit to 100 shortcuts per page for better performance
        'orderby' => 'title',
        'order' => 'ASC',
        'post_status' => 'publish',
        'paged' => 1 // Start with page 1
    );
    
    // Add search if provided
    if (!empty($search)) {
        $args['s'] = $search;
    }
    
    $query = new WP_Query($args);
    $shortcuts = array();
    
    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $post = get_post();
            $shortcut_id = (int) $post->ID;
            
            // Check if this shortcut is associated with the action
            $is_associated = in_array($shortcut_id, $associated_shortcut_ids);
            
            // Get shortcut key if available
            $key = get_post_meta($shortcut_id, 'shortcut_key', true);
            
            // Get additional shortcut metadata
            $shortcut_type = get_post_meta($shortcut_id, 'shortcut_type', true);
            $shortcut_platform = get_post_meta($shortcut_id, 'shortcut_platform', true);
            $shortcut_description = get_post_meta($shortcut_id, 'shortcut_description', true);
            
            // Create a more complete shortcut object
            $shortcuts[] = array(
                'ID' => $shortcut_id,
                'id' => $shortcut_id, // For compatibility with JavaScript expecting lowercase
                'post_title' => $post->post_title,
                'name' => $post->post_title, // For backward compatibility
                'key' => $key,
                'type' => $shortcut_type,
                'platform' => $shortcut_platform,
                'description' => $shortcut_description,
                'is_associated' => $is_associated // Flag to indicate if this shortcut is associated with the action
            );
        }
        wp_reset_postdata();
    }
    
    wp_send_json_success($shortcuts);
}
add_action('wp_ajax_fetch_shortcuts_for_action', 'fetch_shortcuts_for_action');

/**
 * AJAX handler for updating action-shortcut associations
 */
function update_action_shortcuts() {
    global $wpdb;
    check_ajax_referer('update_action_shortcuts_nonce', 'security');
    
    $action_id = isset($_POST['action_id']) ? intval($_POST['action_id']) : 0;
    
    if (empty($action_id)) {
        wp_send_json_error(array('message' => 'Action ID is required.'));
        return;
    }
    
    // Get shortcuts from POST data
    $shortcuts = isset($_POST['shortcuts']) ? $_POST['shortcuts'] : array();
    
    // Ensure shortcuts is an array of integers
    if (!empty($shortcuts)) {
        if (is_string($shortcuts)) {
            $decoded = json_decode($shortcuts, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $shortcuts = $decoded;
            } else {
                $shortcuts = array();
            }
        }
        
        if (!is_array($shortcuts)) {
            $shortcuts = array($shortcuts);
        }
        
        $shortcuts = array_filter(array_map('intval', $shortcuts));
    } else {
        $shortcuts = array();
    }
    
    // Get the action-shortcut table name
    $action_shortcut_table = $wpdb->prefix . 'shortcuts_hub_action_shortcut';
    
    // First, remove all existing associations for this action
    $wpdb->delete(
        $action_shortcut_table,
        array('action_id' => $action_id),
        array('%d')
    );
    
    // Then add the new associations
    if (!empty($shortcuts)) {
        foreach ($shortcuts as $shortcut_id) {
            $wpdb->insert(
                $action_shortcut_table,
                array(
                    'action_id' => $action_id,
                    'shortcut_id' => $shortcut_id
                ),
                array('%d', '%d')
            );
        }
    }
    
    wp_send_json_success(array('message' => 'Action shortcuts updated successfully.'));
}
add_action('wp_ajax_update_action_shortcuts', 'update_action_shortcuts');
