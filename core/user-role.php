<?php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

function add_shortcuts_user_role() {
    add_role(
        'shortcuts_user',
        __('Shortcuts User', 'shortcuts-hub'),
        array(
            'read' => true,
            'edit_posts' => false,
            'delete_posts' => false,
        )
    );
}

add_action('init', 'add_shortcuts_user_role');
