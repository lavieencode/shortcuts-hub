<?php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Check security nonce before any action
function shortcuts_hub_verify_nonce($nonce, $action) {
    if (!wp_verify_nonce($nonce, $action)) {
        wp_send_json_error('Invalid nonce');
        exit;
    }
}

// Sanitize and validate input fields
function shortcuts_hub_sanitize_input($input, $type = 'text') {
    switch ($type) {
        case 'text':
            return sanitize_text_field($input);
        case 'email':
            return sanitize_email($input);
        case 'url':
            return esc_url_raw($input);
        case 'int':
            return intval($input);
        case 'bool':
            return filter_var($input, FILTER_VALIDATE_BOOLEAN);
        case 'array':
            return is_array($input) ? array_map('sanitize_text_field', $input) : sanitize_text_field($input);
        default:
            return sanitize_text_field($input);
    }
}

// Restrict access to admin users only
function shortcuts_hub_check_admin_permissions() {
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Permission denied');
        exit;
    }
}