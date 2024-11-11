<?php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

// Register the x-callback endpoint
function register_x_callback_endpoint() {
    add_rewrite_rule('^x-callback/?$', 'index.php?x_callback=1', 'top');
}
add_action('init', 'register_x_callback_endpoint');

// Add x_callback to query vars
function add_x_callback_query_vars($vars) {
    $vars[] = 'x_callback';
    return $vars;
}
add_filter('query_vars', 'add_x_callback_query_vars');

// Handle the x-callback request
function handle_x_callback_request() {
    if (get_query_var('x_callback')) {
        error_log('x-callback endpoint triggered');

        // Respond to the request
        header('Content-Type: application/json');
        echo json_encode(['status' => 'success', 'message' => 'Webhook received']);
        exit;
    }
}
add_action('template_redirect', 'handle_x_callback_request');

// Flush rewrite rules on activation
function flush_rewrite_rules_on_activation() {
    register_x_callback_endpoint();
    flush_rewrite_rules();
}
register_activation_hook(__FILE__, 'flush_rewrite_rules_on_activation');

// Handle Webhook: Process form submission and send data to AJAX endpoint
function handle_webhook($record, $handler) {
    $form_name = $record->get_form_settings('form_name');
    if ('Shortcuts Gallery Registration' !== $form_name) {
        return;
    }

    $fields = [];
    foreach ($record->get('fields') as $id => $field) {
        $fields[$id] = $field['value'];
    }

    $sb_id = sanitize_text_field($fields['sb_id'] ?? '');
    $post_id = intval($fields['post_id'] ?? 0);

    // Log the fields for debugging
    error_log('Webhook fields: ' . print_r($fields, true));

    $response = wp_remote_post(admin_url('admin-ajax.php'), [
        'body' => [
            'action' => 'handle_registration',
            'fields' => $fields,
            'security' => wp_create_nonce('shortcuts_hub_nonce'),
            'sb_id' => $sb_id,
            'post_id' => $post_id,
        ]
    ]);

    if (is_wp_error($response)) {
        $handler->add_error_message('Error sending data to the server.');
        error_log('Error sending data to the server: ' . $response->get_error_message());
    }
}
add_action('elementor_pro/forms/new_record', 'handle_webhook', 10, 2);