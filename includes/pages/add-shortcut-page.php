<?php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

function shortcuts_hub_render_add_shortcut_page() {
    ?>
    <div id="add-shortcut-page" class="wrap">
        <h1><?php esc_html_e('Add New Shortcut', 'shortcuts-hub'); ?></h1>
        <form id="add-shortcut-form" class="form-container">
            <input type="hidden" id="shortcut-state" name="state" value="published">
            <input type="hidden" id="shortcut-sb-id" name="sb_id" value="">
            <div class="form-columns">
                <div class="form-column">
                    <div class="form-group">
                        <label for="shortcut-name">Name</label>
                        <input type="text" id="shortcut-name" name="name" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="shortcut-headline">Headline</label>
                        <input type="text" id="shortcut-headline" name="headline" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="shortcut-description">Description</label>
                        <textarea id="shortcut-description" name="description" required></textarea>
                    </div>
                </div>
                
                <div class="form-column">
                    <div class="form-group">
                        <label for="shortcut-input">Input</label>
                        <input type="text" id="shortcut-input" name="input">
                    </div>
                    
                    <div class="form-group">
                        <label for="shortcut-result">Result</label>
                        <input type="text" id="shortcut-result" name="result">
                    </div>
                    
                    <div class="form-group">
                        <label for="shortcut-color">Color</label>
                        <div class="color-input-wrapper">
                            <input type="text" id="shortcut-color" name="color" value="" class="color-picker" readonly />
                        </div>
                        <div id="color-picker-container"></div>
                    </div>
                    
                    <div class="form-group">
                        <label for="shortcut-icon">Icon</label>
                        <input type="text" id="shortcut-icon" name="icon" value="" readonly />
                    </div>
                </div>
            </div>
            
            <div class="form-group">
                <label for="shortcut-actions">Actions</label>
                <select id="shortcut-actions" name="actions[]" multiple>
                </select>
            </div>
            
            <div style="margin-top: 20px; display: flex; gap: 10px;">
                <button type="button" class="button draft-button" id="save-draft">Save as Draft</button>
                <button type="submit" class="button shortcuts-button" id="add-shortcut">Add Shortcut</button>
            </div>
        </form>
        <div id="feedback-message"></div>
    </div>
    <?php
}
