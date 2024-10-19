<?php

if (!defined('ABSPATH')) {
    exit;
}

function sb_fetch_shortcut_data_ajax() {
    check_ajax_referer('shortcuts_hub_nonce', 'security');

    $shortcut_id = isset($_POST['shortcut_id']) ? sanitize_text_field($_POST['shortcut_id']) : '';

    if (empty($shortcut_id)) {
        wp_send_json_error('Shortcut ID is missing');
        return;
    }

    $shortcut_data = sb_fetch_selected_shortcut($shortcut_id);

    if (!empty($shortcut_data)) {
        wp_send_json_success($shortcut_data);
    } else {
        wp_send_json_error('No shortcut data found');
    }
}

add_action('wp_ajax_sb_fetch_shortcut_data', 'sb_fetch_shortcut_data_ajax');
add_action('wp_ajax_nopriv_sb_fetch_shortcut_data', 'sb_fetch_shortcut_data_ajax');