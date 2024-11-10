<?php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

function handle_registration() {
    if (is_user_logged_in()) {
        $shortcut_id = isset($_GET['sb_id']) ? sanitize_text_field($_GET['sb_id']) : '';

        if (empty($shortcut_id)) {
            echo 'Shortcut ID is missing.';
            return;
        }

        echo "<script>console.log('User is logged in, ready to fetch latest version');</script>";
        return;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
        $username = sanitize_user($_POST['username']);
        $email = sanitize_email($_POST['email']);
        $password = $_POST['password'];

        if (username_exists($username) || email_exists($email)) {
            echo 'Username or email already exists.';
            return;
        }

        $user_id = wp_create_user($username, $password, $email);

        if (is_wp_error($user_id)) {
            echo 'Error creating user.';
            return;
        }

        $user = new WP_User($user_id);
        $user->set_role('shortcuts_user');

        $shortcut_id = isset($_GET['sb_id']) ? sanitize_text_field($_GET['sb_id']) : '';

        if (empty($shortcut_id)) {
            echo 'Shortcut ID is missing.';
            return;
        }

        echo "<script>console.log('Registration flow loaded for new registration');</script>";
        return;
    }
}

add_action('template_redirect', 'handle_registration');
