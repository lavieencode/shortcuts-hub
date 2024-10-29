<?php

if (!defined('ABSPATH')) {
    exit;
}

function shortcuts_hub_render_edit_shortcut_page() {
    $shortcut_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    $name = '';
    $headline = '';
    $description = '';
    $website = '';
    $color = '';
    $icon = '';
    $input = '';
    $result = '';
    $switchblade_id = '';

    if ($shortcut_id) {
        $shortcut = get_post($shortcut_id);

        if ($shortcut) {
            $name = get_the_title($shortcut);
            $headline = get_post_meta($shortcut->ID, 'headline', true);
            $description = get_post_meta($shortcut->ID, 'description', true);
            $website = get_post_meta($shortcut->ID, 'website', true);
            $color = get_post_meta($shortcut->ID, 'color', true);
            $icon = get_post_meta($shortcut->ID, 'icon', true);
            $input = get_post_meta($shortcut->ID, 'input', true);
            $result = get_post_meta($shortcut->ID, 'result', true);
            $switchblade_id = get_post_meta($shortcut->ID, 'sb_id', true);
        }
    }
    ?>
    <div id="edit-shortcut-page" class="wrap">
        <h1><?php esc_html_e('Edit Shortcut', 'plugin-name'); ?></h1>
        <form id="edit-shortcut-form" class="form-container">
            <input type="hidden" id="shortcut-id" name="id" value="<?php echo esc_attr($shortcut_id); ?>">
            <div class="form-columns">
                <div class="form-column">
                    <div class="form-group">
                        <label for="shortcut-name">Name</label>
                        <input type="text" id="shortcut-name" name="name" value="<?php echo esc_attr($name); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="shortcut-headline">Headline</label>
                        <input type="text" id="shortcut-headline" name="headline" value="<?php echo esc_attr($headline); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="shortcut-description">Description</label>
                        <textarea id="shortcut-description" name="description" required><?php echo esc_textarea($description); ?></textarea>
                    </div>
                </div>
                
                <div class="form-column">
                    <div class="form-group">
                        <label for="shortcut-input">Input</label>
                        <input type="text" id="shortcut-input" name="input" value="<?php echo esc_attr($input); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="shortcut-result">Result</label>
                        <input type="text" id="shortcut-result" name="result" value="<?php echo esc_attr($result); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="shortcut-color">Color</label>
                        <input type="color" id="shortcut-color" name="color" value="<?php echo esc_attr($color); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="shortcut-icon">Icon</label>
                        <input type="text" id="shortcut-icon" name="icon" value="<?php echo esc_attr($icon); ?>" readonly>
                    </div>
                    
                    <div class="form-group">
                        <label for="sb-id">Switchblade ID</label>
                        <input type="text" id="sb-id" name="sb_id" value="<?php echo esc_attr($switchblade_id); ?>" readonly>
                    </div>
                </div>
            </div>
            
            <div class="form-group">
                <label for="shortcut-actions">Actions</label>
                <select id="shortcut-actions" name="actions[]" multiple>
                    <!-- Populate with existing actions if needed -->
                </select>
            </div>
            
            <div style="margin-top: 20px; display: flex; gap: 10px;">
                <button type="button" class="button delete-button" id="delete-shortcut">Delete</button>
                <button type="submit" class="button shortcuts-button" id="save-shortcut">Save</button>
            </div>
        </form>
        <div id="feedback-message"></div>
    </div>
    <script>
        jQuery(document).ready(function($) {
            $('#shortcut-icon').on('click', function(e) {
                e.preventDefault();
                var frame = wp.media({
                    title: 'Select or Upload Icon',
                    button: {
                        text: 'Use this icon'
                    },
                    multiple: false
                });

                frame.on('select', function() {
                    var attachment = frame.state().get('selection').first().toJSON();
                    $('#shortcut-icon').val(attachment.url);
                });

                frame.open();
            });

            $('#edit-shortcut-form').on('submit', function(e) {
                e.preventDefault();
                var formData = $(this).serialize();

                $.ajax({
                    url: shortcutsHubData.ajax_url,
                    method: 'POST',
                    data: {
                        action: 'save_shortcut_data',
                        security: shortcutsHubData.security,
                        form_data: formData
                    },
                    success: function(response) {
                        if (response.success) {
                            $('#feedback-message').text('Shortcut saved successfully.').css('color', 'green');
                        } else {
                            $('#feedback-message').text('Error saving shortcut: ' + response.data.message).css('color', 'red');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('AJAX error:', status, error);
                        console.error('Response Text:', xhr.responseText);
                        $('#feedback-message').text('Error saving shortcut.').css('color', 'red');
                    }
                });
            });

            $('#delete-shortcut').on('click', function() {
                if (confirm('Are you sure you want to delete this shortcut?')) {
                    var shortcutId = $('#shortcut-id').val();

                    $.ajax({
                        url: shortcutsHubData.ajax_url,
                        method: 'POST',
                        data: {
                            action: 'delete_shortcut',
                            security: shortcutsHubData.security,
                            shortcut_id: shortcutId
                        },
                        success: function(response) {
                            if (response.success) {
                                $('#feedback-message').text('Shortcut deleted successfully.').css('color', 'green');
                                window.location.href = '/path-to-redirect-after-deletion';
                            } else {
                                $('#feedback-message').text('Error deleting shortcut: ' + response.data.message).css('color', 'red');
                            }
                        },
                        error: function(xhr, status, error) {
                            console.error('AJAX error:', status, error);
                            console.error('Response Text:', xhr.responseText);
                            $('#feedback-message').text('Error deleting shortcut.').css('color', 'red');
                        }
                    });
                }
            });
        });
    </script>
    <?php
}

add_action('wp_ajax_save_shortcut_data', 'save_shortcut_data_callback');
add_action('wp_ajax_delete_shortcut', 'delete_shortcut_callback');

function save_shortcut_data_callback() {
    check_ajax_referer('shortcuts_hub_nonce', 'security');

    parse_str($_POST['form_data'], $form_data);
    $shortcut_id = intval($form_data['id']);

    if ($shortcut_id) {
        wp_update_post([
            'ID' => $shortcut_id,
            'post_title' => sanitize_text_field($form_data['name']),
        ]);

        update_post_meta($shortcut_id, 'headline', sanitize_text_field($form_data['headline']));
        update_post_meta($shortcut_id, 'description', sanitize_textarea_field($form_data['description']));
        update_post_meta($shortcut_id, 'website', esc_url_raw($form_data['website']));
        update_post_meta($shortcut_id, 'color', sanitize_hex_color($form_data['color']));
        update_post_meta($shortcut_id, 'icon', esc_url_raw($form_data['icon']));
        update_post_meta($shortcut_id, 'input', sanitize_text_field($form_data['input']));
        update_post_meta($shortcut_id, 'result', sanitize_text_field($form_data['result']));

        wp_send_json_success();
    } else {
        wp_send_json_error(['message' => 'Invalid shortcut ID']);
    }
}

function delete_shortcut_callback() {
    check_ajax_referer('shortcuts_hub_nonce', 'security');

    $shortcut_id = isset($_POST['shortcut_id']) ? intval($_POST['shortcut_id']) : 0;

    if ($shortcut_id && wp_delete_post($shortcut_id, true)) {
        wp_send_json_success();
    } else {
        wp_send_json_error(['message' => 'Error deleting shortcut']);
    }
}
