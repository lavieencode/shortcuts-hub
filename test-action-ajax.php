<?php
/**
 * Test script for Actions AJAX functionality
 * 
 * This script should be run from the browser to test the AJAX functionality
 * Make sure you're logged in as an admin user before running this script
 */

// Load WordPress
require_once('../../../wp-load.php');

// Check if user is logged in and has sufficient permissions
if (!is_user_logged_in() || !current_user_can('manage_options')) {
    wp_die('You do not have sufficient permissions to access this page.');
}

// Include the debug logging function if not already included
if (!function_exists('sh_debug_log')) {
    require_once('includes/utils/debug.php');
}

// Clear the debug log
file_put_contents(plugin_dir_path(__FILE__) . 'sh-debug.log', '');

// Get the first action from the database
$action_query = new WP_Query([
    'post_type' => 'action',
    'posts_per_page' => 1,
    'post_status' => 'publish'
]);

$action_id = 0;
if ($action_query->have_posts()) {
    $action_query->the_post();
    $action_id = get_the_ID();
    wp_reset_postdata();
}

// Get the first few shortcuts from the database
$shortcut_query = new WP_Query([
    'post_type' => 'shortcut',
    'posts_per_page' => 3,
    'post_status' => 'publish'
]);

$shortcut_ids = [];
if ($shortcut_query->have_posts()) {
    while ($shortcut_query->have_posts()) {
        $shortcut_query->the_post();
        $shortcut_ids[] = get_the_ID();
    }
    wp_reset_postdata();
}

// Create nonces for the AJAX requests
$fetch_actions_nonce = wp_create_nonce('fetch_actions_nonce');
$update_action_nonce = wp_create_nonce('update_action_nonce');
$fetch_shortcuts_nonce = wp_create_nonce('fetch_shortcuts_for_action_nonce');
$update_shortcuts_nonce = wp_create_nonce('update_action_shortcuts_nonce');

// Output the HTML
?>
<!DOCTYPE html>
<html>
<head>
    <title>Test Actions AJAX</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            line-height: 1.6;
        }
        h1, h2 {
            color: #333;
        }
        button {
            padding: 8px 16px;
            margin: 5px;
            background-color: #0073aa;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        button:hover {
            background-color: #005177;
        }
        pre {
            background-color: #f5f5f5;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            overflow: auto;
            max-height: 400px;
        }
        .test-section {
            margin-bottom: 30px;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .result {
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <h1>Actions AJAX Test</h1>
    
    <div class="test-section">
        <h2>Test Data</h2>
        <p><strong>Action ID:</strong> <?php echo $action_id; ?></p>
        <p><strong>Shortcut IDs:</strong> <?php echo implode(', ', $shortcut_ids); ?></p>
    </div>
    
    <div class="test-section">
        <h2>Test 1: Fetch Actions</h2>
        <button id="test-fetch-actions">Fetch Actions</button>
        <div id="fetch-actions-result" class="result">
            <pre>Results will appear here...</pre>
        </div>
    </div>
    
    <div class="test-section">
        <h2>Test 2: Fetch Shortcuts for Action</h2>
        <button id="test-fetch-shortcuts">Fetch Shortcuts</button>
        <div id="fetch-shortcuts-result" class="result">
            <pre>Results will appear here...</pre>
        </div>
    </div>
    
    <div class="test-section">
        <h2>Test 3: Update Action with Shortcuts</h2>
        <button id="test-update-action">Update Action</button>
        <div id="update-action-result" class="result">
            <pre>Results will appear here...</pre>
        </div>
    </div>
    
    <div class="test-section">
        <h2>Test 4: Update Action Shortcuts</h2>
        <button id="test-update-shortcuts">Update Shortcuts</button>
        <div id="update-shortcuts-result" class="result">
            <pre>Results will appear here...</pre>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            const ajaxUrl = '<?php echo admin_url('admin-ajax.php'); ?>';
            const actionId = <?php echo $action_id; ?>;
            const shortcutIds = <?php echo json_encode($shortcut_ids); ?>;
            
            // Test 1: Fetch Actions
            $('#test-fetch-actions').click(function() {
                $.ajax({
                    url: ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'fetch_actions',
                        security: '<?php echo $fetch_actions_nonce; ?>'
                    },
                    success: function(response) {
                        $('#fetch-actions-result pre').text(JSON.stringify(response, null, 2));
                        console.log('Fetch Actions Response:', response);
                    },
                    error: function(xhr, status, error) {
                        $('#fetch-actions-result pre').text('Error: ' + error);
                        console.error('AJAX error:', error);
                    }
                });
            });
            
            // Test 2: Fetch Shortcuts for Action
            $('#test-fetch-shortcuts').click(function() {
                $.ajax({
                    url: ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'fetch_shortcuts_for_action',
                        security: '<?php echo $fetch_shortcuts_nonce; ?>',
                        actionId: actionId
                    },
                    success: function(response) {
                        $('#fetch-shortcuts-result pre').text(JSON.stringify(response, null, 2));
                        console.log('Fetch Shortcuts Response:', response);
                    },
                    error: function(xhr, status, error) {
                        $('#fetch-shortcuts-result pre').text('Error: ' + error);
                        console.error('AJAX error:', error);
                    }
                });
            });
            
            // Test 3: Update Action with Shortcuts
            $('#test-update-action').click(function() {
                // Get the action data from the fetch_actions response
                $.ajax({
                    url: ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'fetch_actions',
                        security: '<?php echo $fetch_actions_nonce; ?>'
                    },
                    success: function(response) {
                        if (response.success && response.data.length > 0) {
                            const actionData = response.data.find(a => parseInt(a.ID) === actionId);
                            if (actionData) {
                                // Now update the action with the shortcut IDs
                                updateAction(actionData);
                            } else {
                                $('#update-action-result pre').text('Action not found in response');
                            }
                        } else {
                            $('#update-action-result pre').text('No actions found in response');
                        }
                    },
                    error: function(xhr, status, error) {
                        $('#update-action-result pre').text('Error fetching action: ' + error);
                        console.error('AJAX error:', error);
                    }
                });
                
                function updateAction(actionData) {
                    $.ajax({
                        url: ajaxUrl,
                        type: 'POST',
                        data: {
                            action: 'update_action',
                            security: '<?php echo $update_action_nonce; ?>',
                            action_id: actionId,
                            name: actionData.post_title,
                            description: actionData.post_content,
                            icon: actionData.icon,
                            input: actionData.input || '',
                            result: actionData.result || '',
                            shortcuts: JSON.stringify(shortcutIds),
                            status: actionData.post_status
                        },
                        success: function(response) {
                            $('#update-action-result pre').text(JSON.stringify(response, null, 2));
                            console.log('Update Action Response:', response);
                        },
                        error: function(xhr, status, error) {
                            $('#update-action-result pre').text('Error: ' + error);
                            console.error('AJAX error:', error);
                        }
                    });
                }
            });
            
            // Test 4: Update Action Shortcuts
            $('#test-update-shortcuts').click(function() {
                $.ajax({
                    url: ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'update_action_shortcuts',
                        security: '<?php echo $update_shortcuts_nonce; ?>',
                        action_id: actionId,
                        shortcut_ids: JSON.stringify(shortcutIds)
                    },
                    success: function(response) {
                        $('#update-shortcuts-result pre').text(JSON.stringify(response, null, 2));
                        console.log('Update Shortcuts Response:', response);
                    },
                    error: function(xhr, status, error) {
                        $('#update-shortcuts-result pre').text('Error: ' + error);
                        console.error('AJAX error:', error);
                    }
                });
            });
        });
    </script>
</body>
</html>
