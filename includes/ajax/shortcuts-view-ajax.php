<?php
/**
 * AJAX handlers for shortcut view toggling
 */

if (!defined('ABSPATH')) {
    exit;
}

function shortcuts_hub_toggle_grid_view() {
    // Verify nonce and user capabilities
    if (!check_ajax_referer('toggle_view_nonce', 'security', false)) {
        sh_debug_log('Toggle Grid View Security Error', array(
            'message' => 'Security check failed for grid view toggle',
            'source' => array(
                'file' => __FILE__,
                'line' => intval(__LINE__),
                'function' => __FUNCTION__
            ),
            'data' => array(
                'nonce' => isset($_POST['security']) ? $_POST['security'] : 'not_set',
                'user_id' => get_current_user_id()
            ),
            'debug' => true
        ));
        wp_send_json_error(array('message' => 'Security check failed'));
        return;
    }

    sh_debug_log('Toggle Grid View', array(
        'message' => 'Switching to grid view',
        'source' => array(
            'file' => __FILE__,
            'line' => intval(__LINE__),
            'function' => __FUNCTION__
        ),
        'data' => array(
            'request' => $_POST
        ),
        'debug' => true
    ));

    try {
        $shortcuts = get_shortcuts_data();
        ob_start();
        render_grid_view($shortcuts);
        $html = ob_get_clean();

        wp_send_json_success(array(
            'html' => $html
        ));
    } catch (Exception $e) {
        sh_debug_log('Grid View Error', array(
            'message' => 'Error rendering grid view',
            'source' => array(
                'file' => __FILE__,
                'line' => intval(__LINE__),
                'function' => __FUNCTION__
            ),
            'data' => array(
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ),
            'debug' => true
        ));
        wp_send_json_error(array('message' => 'Error rendering grid view'));
    }
}
add_action('wp_ajax_toggle_grid_view', 'shortcuts_hub_toggle_grid_view');

function shortcuts_hub_toggle_list_view() {
    // Verify nonce and user capabilities
    if (!check_ajax_referer('toggle_view_nonce', 'security', false)) {
        sh_debug_log('Toggle List View Security Error', array(
            'message' => 'Security check failed for list view toggle',
            'source' => array(
                'file' => __FILE__,
                'line' => intval(__LINE__),
                'function' => __FUNCTION__
            ),
            'data' => array(
                'nonce' => isset($_POST['security']) ? $_POST['security'] : 'not_set',
                'user_id' => get_current_user_id()
            ),
            'debug' => true
        ));
        wp_send_json_error(array('message' => 'Security check failed'));
        return;
    }

    sh_debug_log('Toggle List View', array(
        'message' => 'Switching to list view',
        'source' => array(
            'file' => __FILE__,
            'line' => intval(__LINE__),
            'function' => __FUNCTION__
        ),
        'data' => array(
            'request' => $_POST
        ),
        'debug' => true
    ));

    try {
        $shortcuts = get_shortcuts_data();
        ob_start();
        render_list_view($shortcuts);
        $html = ob_get_clean();

        wp_send_json_success(array(
            'html' => $html
        ));
    } catch (Exception $e) {
        sh_debug_log('List View Error', array(
            'message' => 'Error rendering list view',
            'source' => array(
                'file' => __FILE__,
                'line' => intval(__LINE__),
                'function' => __FUNCTION__
            ),
            'data' => array(
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ),
            'debug' => true
        ));
        wp_send_json_error(array('message' => 'Error rendering list view'));
    }
}
add_action('wp_ajax_toggle_list_view', 'shortcuts_hub_toggle_list_view');

function get_shortcuts_data() {
    global $wpdb;
    
    $status = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : '';
    $deleted = isset($_POST['deleted']) ? sanitize_text_field($_POST['deleted']) : '';
    $search = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';
    
    $args = array(
        'post_type' => 'shortcut',
        'posts_per_page' => -1,
        'orderby' => 'title',
        'order' => 'ASC',
        'post_status' => $status
    );

    if ($search) {
        $args['s'] = $search;
    }
    
    $shortcuts = get_posts($args);
    
    // Add action count to each shortcut
    foreach ($shortcuts as &$shortcut) {
        $table_name = $wpdb->prefix . 'shortcuts_hub_action_shortcut';
        $action_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE shortcut_id = %d",
            $shortcut->ID
        ));
        $shortcut->action_count = (int) $action_count;
    }
    
    return $formatted_shortcuts;
}
