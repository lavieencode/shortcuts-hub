<?php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Add the role on plugin activation
function add_shortcuts_user_role() {
    static $role_added = false;
    if ($role_added) {
        return;
    }
    
    add_role(
        'shortcuts_user',
        __('Shortcuts User', 'shortcuts-hub'),
        array(
            'read' => true,
            'edit_posts' => false,
            'delete_posts' => false,
        )
    );
    $role_added = true;
}

add_action('init', 'add_shortcuts_user_role');
