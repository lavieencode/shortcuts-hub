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
        <h1><?php esc_html_e('Edit Shortcut', 'shortcuts-hub'); ?></h1>
        <h2 id="shortcut-title"><?php echo esc_html($name); ?></h2>
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
                        <div class="color-input-wrapper">
                            <input type="text" id="shortcut-color" name="color" value="<?php echo esc_attr($color); ?>" class="color-picker" readonly />
                            <div id="color-picker-container"></div>
                        </div>
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
                <button type="button" class="delete-button" id="delete-shortcut">Delete</button>
                <button type="submit" id="save-shortcut">Save</button>
            </div>
        </form>
        <div id="feedback-message"></div>
    </div>
    <?php
}