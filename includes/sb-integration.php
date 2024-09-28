<?php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

function sb_allow_cors() {
    if (isset($_SERVER['HTTP_ORIGIN'])) {
        header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
        header("Access-Control-Allow-Credentials: true");
        header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
        header("Access-Control-Allow-Headers: Origin, Content-Type, Accept, Authorization, X-Requested-With");
    }

    if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
        if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD'])) {
            header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
        }
        if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS'])) {
            header("Access-Control-Allow-Headers: {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");
        }
        exit(0);
    }
}
add_action('init', 'sb_allow_cors');

function sb_fetch_switchblade_proxy() {
    $bearer_token = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJkZWJvdGNoZXJ5LXN3aXRjaGJsYWRlLWJjNmZhMWVlNGUwMS5oZXJva3VhcHAuY29tIiwiYXVkIjoiZGVib3RjaGVyeS1zd2l0Y2hibGFkZS1iYzZmYTFlZTRlMDEuaGVyb2t1YXBwLmNvbSIsImlhdCI6MTcyNzIzNTA3OCwibmJmIjoxNzI3MjM1MDc4LCJleHAiOjE3MjcyMzg2NzgsInR5cGUiOiJhdXRoIiwiZGF0YSI6eyJpZCI6MSwidXNlcm5hbWUiOiJuaWNvbGUifX0.47RG9-xSKhgikxN034d4JHkKbuSU8Z0DNF7_HE2Htjg';
    
    // Define the API endpoint
    $url = 'https://debotchery-switchblade-bc6fa1ee4e01.herokuapp.com/shortcuts';

    error_log('Attempting to fetch shortcuts from ' . $url); // Log the action

    // Make the GET request to the Switchblade API
    $response = wp_remote_get($url, array(
        'headers' => array(
            'Authorization' => 'Bearer ' . $bearer_token
        )
    ));

    // Check if the request returned any errors
    if (is_wp_error($response)) {
        error_log('Error fetching shortcuts: ' . $response->get_error_message()); // Log the error
        wp_send_json_error('Error fetching shortcuts.');
    }

    // Log and return the successfully fetched shortcuts
    $shortcuts = wp_remote_retrieve_body($response);
    error_log('Fetched shortcuts successfully: ' . print_r(json_decode($shortcuts, true), true)); // Log the fetched data
    
    // Send the data back to the frontend
    wp_send_json_success(json_decode($shortcuts, true));
}

function sb_fetch_shortcuts() {
    $bearer_token = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJkZWJvdGNoZXJ5LXN3aXRjaGJsYWRlLWJjNmZhMWVlNGUwMS5oZXJva3VhcHAuY29tIiwiYXVkIjoiZGVib3RjaGVyeS1zd2l0Y2hibGFkZS1iYzZmYTFlZTRlMDEuaGVyb2t1YXBwLmNvbSIsImlhdCI6MTcyNzIzNTA3OCwibmJmIjoxNzI3MjM1MDc4LCJleHAiOjE3MjcyMzg2NzgsInR5cGUiOiJhdXRoIiwiZGF0YSI6eyJpZCI6MSwidXNlcm5hbWUiOiJuaWNvbGUifX0.47RG9-xSKhgikxN034d4JHkKbuSU8Z0DNF7_HE2Htjg';

    // Fetch shortcuts from API
    $response = wp_remote_get('https://debotchery-switchblade-bc6fa1ee4e01.herokuapp.com/shortcuts', array(
        'headers' => array(
            'Authorization' => 'Bearer ' . $bearer_token
        )
    ));

    if (is_wp_error($response)) {
        error_log('Error fetching shortcuts: ' . $response->get_error_message());
        return [];
    }

    $shortcuts = json_decode(wp_remote_retrieve_body($response), true);
    error_log('Fetched shortcuts: ' . print_r($shortcuts, true));

    return $shortcuts;
}

// Load choices via AJAX for Select Field
function sb_load_shortcut_choices_ajax($field) {
    // Check if the request is being done via AJAX
    if (defined('DOING_AJAX') && DOING_AJAX) {
        // Fetch shortcuts from Switchblade API
        $shortcuts = sb_fetch_shortcuts();

        if (!empty($shortcuts['shortcuts'])) {
            // Populate choices with fetched shortcuts
            $field['choices'] = array();
            foreach ($shortcuts['shortcuts'] as $shortcut) {
                $field['choices'][$shortcut['id']] = $shortcut['name'];
            }
        } else {
            $field['choices'] = array('' => 'No shortcuts found');
        }
    }

    return $field;
}

// Attach filter for Select field with AJAX loading
add_filter('acf/load_field/name=switchblade_shortcut', 'sb_load_shortcut_choices_ajax');

// Function to fetch selected shortcut details from Switchblade API
function sb_fetch_selected_shortcut($shortcut_id) {
    $bearer_token = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJkZWJvdGNoZXJ5LXN3aXRjaGJsYWRlLWJjNmZhMWVlNGUwMS5oZXJva3VhcHAuY29tIiwiYXVkIjoiZGVib3RjaGVyeS1zd2l0Y2hibGFkZS1iYzZmYTFlZTRlMDEuaGVyb2t1YXBwLmNvbSIsImlhdCI6MTcyNzIzNTA3OCwibmJmIjoxNzI3MjM1MDc4LCJleHAiOjE3MjcyMzg2NzgsInR5cGUiOiJhdXRoIiwiZGF0YSI6eyJpZCI6MSwidXNlcm5hbWUiOiJuaWNvbGUifX0.47RG9-xSKhgikxN034d4JHkKbuSU8Z0DNF7_HE2Htjg';

    // Fetch the shortcut details
    $response = wp_remote_get("https://debotchery-switchblade-bc6fa1ee4e01.herokuapp.com/shortcuts/{$shortcut_id}", array(
        'headers' => array(
            'Authorization' => 'Bearer ' . $bearer_token
        )
    ));

    if (is_wp_error($response)) {
        return []; // Handle error
    }

    $shortcut = json_decode(wp_remote_retrieve_body($response), true);

    // Fetch the latest version details
    $version_response = wp_remote_get("https://debotchery-switchblade-bc6fa1ee4e01.herokuapp.com/shortcuts/{$shortcut_id}/versions", array(
        'headers' => array(
            'Authorization' => 'Bearer ' . $bearer_token
        )
    ));

    if (!is_wp_error($version_response)) {
        $versions = json_decode(wp_remote_retrieve_body($version_response), true);
        if (!empty($versions)) {
            $latest_version = reset($versions); // Get the latest version
            $shortcut['current_version'] = $latest_version['version'];
            $shortcut['download_link'] = $latest_version['icloud_url'];
        }
    }

    return $shortcut;
}

// Hook into the ACF update value action for the switchblade_shortcut field
add_filter('acf/update_value/name=switchblade_shortcut', 'update_switchblade_shortcut_value', 10, 3);

function update_switchblade_shortcut_value($value, $post_id, $field) {
    // Ensure this function runs only for the 'shortcut' post type
    if (get_post_type($post_id) !== 'shortcut') {
        return $value;
    }

    // Log the selected shortcut ID
    error_log('Updating selected Switchblade shortcut ID: ' . $value);

    // Update the switchblade_shortcut field with the selected value
    update_field('switchblade_shortcut', $value, $post_id);

    // Fetch the selected shortcut data from the API based on the selected ID
    $shortcut_data = sb_fetch_selected_shortcut($value);

    // Check if the API returned any shortcut data
    if (!empty($shortcut_data)) {
        // Update ACF fields with the returned shortcut data
        update_field('shortcut_data_shortcut_name', $shortcut_data['name'], $post_id);
        update_field('shortcut_data_shortcut_headline', $shortcut_data['headline'], $post_id);
        update_field('shortcut_data_shortcut_description', $shortcut_data['description'], $post_id);
        update_field('shortcut_data_download_link', $shortcut_data['download_link'], $post_id);
        update_field('shortcut_data_current_version', $shortcut_data['current_version'], $post_id);

        // Log the data that was updated
        error_log('Updated shortcut data: ' . print_r($shortcut_data, true));
    } else {
        error_log('No shortcut data found for ID: ' . $value);
    }

    // Return the updated value so ACF can save it
    return $value;
}

add_action('acf/save_post', 'sb_save_selected_shortcut_data', 5);
function sb_save_selected_shortcut_data($post_id) {
    if (get_post_type($post_id) !== 'shortcut') {
        return;
    }

    // Get the selected shortcut ID
    if (isset($_POST['acf']['switchblade_shortcut'])) {
        $selected_shortcut_id = $_POST['acf']['switchblade_shortcut'];

        // Save the selected shortcut ID into ACF field manually
        update_field('switchblade_shortcut', $selected_shortcut_id, $post_id);

        // Proceed with updating the other fields if necessary
        $shortcut_data = sb_fetch_selected_shortcut($selected_shortcut_id);
        update_field('shortcut_data_shortcut_name', $shortcut_data['name'], $post_id);
        update_field('shortcut_data_shortcut_headline', $shortcut_data['headline'], $post_id);
        update_field('shortcut_data_shortcut_description', $shortcut_data['description'], $post_id);
        update_field('shortcut_data_download_link', $shortcut_data['download_link'], $post_id);
        update_field('shortcut_data_current_version', $shortcut_data['current_version'], $post_id);
    }
}