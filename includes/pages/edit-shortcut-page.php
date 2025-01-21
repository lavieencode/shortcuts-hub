<?php

if (!defined('ABSPATH')) {
    exit;
}

function render_edit_shortcut_page() {
    $post_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    $name = '';
    $headline = '';
    $description = '';
    $website = '';
    $color = '';
    $icon = '';
    $input = '';
    $result = '';
    $id = '';
    $shortcut = null;
    $is_published = false;

    if ($post_id) {
        $shortcut = get_post($post_id);

        if ($shortcut) {
            $sb_id = get_post_meta($shortcut->ID, 'sb_id', true);
            
            $name = get_the_title($shortcut);
            $headline = get_post_meta($shortcut->ID, '_shortcut_headline', true);
            $description = get_post_meta($shortcut->ID, '_shortcut_description', true);
            $website = get_permalink($shortcut);
            $color = get_post_meta($shortcut->ID, '_shortcut_color', true);
            $icon = get_post_meta($shortcut->ID, '_shortcut_icon', true);
            $input = get_post_meta($shortcut->ID, '_shortcut_input', true);
            $result = get_post_meta($shortcut->ID, '_shortcut_result', true);
            $id = $sb_id;
            $is_published = $shortcut->post_status === 'publish';

            // Log all shortcut data
            sh_debug_log('Edit Shortcut Page - Loaded Shortcut Data', [
                'source' => [
                    'file' => __FILE__,
                    'line' => __LINE__,
                    'function' => __FUNCTION__
                ],
                'post' => [
                    'ID' => $shortcut->ID,
                    'post_title' => $name,
                    'post_status' => $shortcut->post_status,
                    'post_type' => $shortcut->post_type,
                    'post_content' => $shortcut->post_content
                ],
                'meta' => [
                    'sb_id' => $sb_id,
                    'headline' => $headline,
                    'description' => $description,
                    'website' => $website,
                    'color' => $color,
                    'icon' => $icon,
                    'input' => $input,
                    'result' => $result
                ],
                'all_meta' => get_post_meta($shortcut->ID)
            ]);
        }
    }
    ?>
    <div id="edit-shortcut-page" class="wrap">
        <h1><?php esc_html_e('Edit Shortcut', 'shortcuts-hub'); ?></h1>
        <h2 id="shortcut-title"><?php echo esc_html($name); ?></h2>
        <form id="edit-shortcut-form" class="form-container" onsubmit="event.preventDefault(); return false;">
            <input type="hidden" id="shortcut-post-id" name="post_id" value="<?php echo esc_attr($post_id); ?>" data-post-status="<?php echo esc_attr(get_post_status($post_id)); ?>">
            <input type="hidden" id="shortcut-id" name="sb_id" value="<?php echo esc_attr($id); ?>">
            <input type="hidden" id="shortcut-website" name="website" value="<?php echo esc_url($website); ?>">
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
                    
                    <div class="form-group color-input-wrapper">
                        <label for="shortcut-color">Color</label>
                        <input type="text" id="shortcut-color" class="color-picker" name="color" value="<?php echo esc_attr($color); ?>">
                        <div id="color-picker-container"></div>
                    </div>
                    
                    <div class="form-group icon-field-wrapper">
                        <label for="icon-type-selector">Icon</label>
                        <div class="icon-selector-container">
                            <div class="icon-input-row">
                                <select id="icon-type-selector" class="icon-type-selector">
                                    <option value="fontawesome">Font Awesome Icon</option>
                                    <option value="custom">Custom Upload</option>
                                </select>
                                <div class="icon-preview"></div>
                            </div>
                            <div id="icon-selector-content"></div>
                        </div>
                        <input type="hidden" id="shortcut-icon" name="icon" value="<?php echo esc_attr($icon); ?>">
                    </div>
                </div>
            </div>
            
            <div class="form-group actions-selector-group">
                <label for="shortcut-actions">Actions</label>
                <div class="actions-selector-container">
                    <div class="actions-list-container">
                        <h4>Available Actions</h4>
                        <select id="available-actions" class="actions-list" multiple>
                            <?php
                            // Get all actions
                            $actions = get_posts(array(
                                'post_type' => 'action',
                                'posts_per_page' => -1,
                                'orderby' => 'title',
                                'order' => 'ASC'
                            ));

                            // Get currently selected actions
                            $selected_action_ids = array();
                            if ($post_id) {
                                global $wpdb;
                                $table_name = $wpdb->prefix . 'shortcut_action_relationships';
                                $selected_action_ids = $wpdb->get_col($wpdb->prepare(
                                    "SELECT action_id FROM $table_name WHERE shortcut_id = %d",
                                    $post_id
                                ));
                            }

                            foreach ($actions as $action) {
                                if (!in_array($action->ID, $selected_action_ids)) {
                                    echo '<option value="' . esc_attr($action->ID) . '">' . esc_html($action->post_title) . '</option>';
                                }
                            }
                            ?>
                        </select>
                    </div>
                    
                    <div class="actions-buttons">
                        <button type="button" id="add-actions" class="action-button">&rarr;</button>
                        <button type="button" id="remove-actions" class="action-button">&larr;</button>
                    </div>
                    
                    <div class="actions-list-container">
                        <h4>Selected Actions</h4>
                        <select id="selected-actions" name="actions[]" class="actions-list" multiple>
                            <?php
                            if (!empty($selected_action_ids)) {
                                $selected_actions = get_posts(array(
                                    'post_type' => 'action',
                                    'include' => $selected_action_ids,
                                    'orderby' => 'title',
                                    'order' => 'ASC'
                                ));

                                foreach ($selected_actions as $action) {
                                    echo '<option value="' . esc_attr($action->ID) . '">' . esc_html($action->post_title) . '</option>';
                                }
                            }
                            ?>
                        </select>
                    </div>
                </div>
            </div>
            
            <input type="hidden" id="sb-id" name="sb_id" value="<?php echo esc_attr($id); ?>">
            
            <div class="button-container">
                <?php 
                    $post_status = get_post_status($post_id);
                    $button_class = $is_published ? 'revert-button' : 'publish-button';
                    $button_text = $is_published ? 'Revert to Draft' : 'Publish';
                    $save_text = $is_published ? 'Update' : 'Save';
                ?>
                <button type="button" id="publish-shortcut" class="<?php echo esc_attr($button_class); ?>">
                    <?php echo esc_html($button_text); ?>
                </button>
                <button type="button" id="save-draft" class="save-draft-button">
                    <?php echo esc_html($save_text); ?>
                </button>
                <div class="btn-group">
                    <button type="button" id="delete-shortcut" class="delete-button" data-post_id="<?php echo esc_attr($post_id); ?>" data-sb_id="<?php echo esc_attr($id); ?>">Delete</button>
                    <button type="button" class="delete-dropdown-toggle">
                        <span class="dropdown-caret">▼</span>
                    </button>
                    <div class="delete-dropdown-content">
                        <button type="button" class="delete-permanently" data-post_id="<?php echo esc_attr($post_id); ?>" data-sb_id="<?php echo esc_attr($id); ?>">Delete Permanently</button>
                    </div>
                </div>
                <button type="button" class="cancel-button">Cancel</button>
                <div id="feedback-message"></div>
            </div>
        </form>
    </div>
    <?php
    // Add initialization script inline after all scripts are loaded
    add_action('admin_footer', function() use ($icon) {
        ?>
        <script>
        jQuery(document).ready(function($) {
            // Initialize icon selector only once
            if (typeof IconSelector !== 'undefined' && !window.iconSelector) {
                window.iconSelector = new IconSelector({
                    container: document.getElementById('icon-selector-content'),
                    inputField: document.getElementById('shortcut-icon'),
                    previewContainer: document.querySelector('.icon-preview'),
                    onChange: function(value) {
                        console.log('Icon changed:', value);
                    }
                });
            } else if (!IconSelector) {
                console.error('IconSelector not loaded');
            }
        });
        </script>
        <?php
    });
}