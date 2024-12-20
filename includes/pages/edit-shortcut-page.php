<?php

if (!defined('ABSPATH')) {
    exit;
}

function shortcuts_hub_render_edit_shortcut_page() {
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
            $headline = get_post_meta($shortcut->ID, 'headline', true);
            $description = get_post_meta($shortcut->ID, 'description', true);
            $website = get_permalink($shortcut);
            $color = get_post_meta($shortcut->ID, 'color', true);
            $icon = get_post_meta($shortcut->ID, 'icon', true);
            $input = get_post_meta($shortcut->ID, 'input', true);
            $result = get_post_meta($shortcut->ID, 'result', true);
            $id = $sb_id;
            $is_published = $shortcut->post_status === 'publish';
        }
    }
    ?>
    <div id="edit-shortcut-page" class="wrap">
        <h1><?php esc_html_e('Edit Shortcut', 'shortcuts-hub'); ?></h1>
        <h2 id="shortcut-title"><?php echo esc_html($name); ?></h2>
        <form id="edit-shortcut-form" class="form-container" onsubmit="event.preventDefault(); return false;">
            <input type="hidden" id="shortcut-post-id" name="post_id" value="<?php echo esc_attr($post_id); ?>" data-post-status="<?php echo esc_attr(get_post_status($post_id)); ?>">
            <input type="hidden" id="shortcut-id" name="id" value="<?php echo esc_attr($id); ?>">
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
            
            <div class="form-group">
                <label for="shortcut-actions">Actions</label>
                <select id="shortcut-actions" name="actions[]" multiple>
                    <!-- Populate with existing actions if needed -->
                </select>
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
                    <button type="button" class="delete-button" data-post_id="<?php echo esc_attr($post_id); ?>" data-sb_id="<?php echo esc_attr($id); ?>">Delete</button>
                    <button type="button" class="delete-dropdown-toggle" data-post_id="<?php echo esc_attr($post_id); ?>" data-sb_id="<?php echo esc_attr($id); ?>">
                        <span class="dropdown-caret">▼</span>
                    </button>
                    <div class="delete-dropdown-content">
                        <button class="delete-permanently" data-post_id="<?php echo esc_attr($post_id); ?>" data-sb_id="<?php echo esc_attr($id); ?>">Delete Permanently</button>
                    </div>
                </div>
                <button type="button" class="cancel-button">Cancel</button>
            </div>
            <div id="feedback-message"></div>
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