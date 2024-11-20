<?php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

function shortcuts_hub_render_add_shortcut_page() {
    ?>
    <div id="add-shortcut-page" class="wrap">
        <h1><?php esc_html_e('Add New Shortcut', 'shortcuts-hub'); ?></h1>
        <form id="add-shortcut-form" class="form-container">
            <input type="hidden" id="state" name="state" value="published">
            <input type="hidden" id="id" name="id" value="">
            <div class="form-columns">
                <div class="form-column">
                    <div class="form-group">
                        <label for="name">Name</label>
                        <input type="text" id="name" name="name" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="headline">Headline</label>
                        <input type="text" id="headline" name="headline" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="shortcut-description">Description</label>
                        <textarea id="description" name="description" required></textarea>
                    </div>
                </div>
                
                <div class="form-column">
                    <div class="form-group">
                        <label for="input">Input</label>
                        <input type="text" id="input" name="input">
                    </div>
                    
                    <div class="form-group">
                        <label for="result">Result</label>
                        <input type="text" id="result" name="result">
                    </div>
                    
                    <div class="form-group">
                        <label for="color">Color</label>
                        <div class="color-input-wrapper">
                            <input type="text" id="color" name="color" value="" class="color-picker" readonly />
                        </div>
                        <div id="color-picker-container"></div>
                    </div>
                    
                    <div class="form-group">
                        <label for="icon">Icon</label>
                        <input type="text" id="icon" name="icon" value="" readonly />
                    </div>
                </div>
            </div>
            
            <div class="form-group">
                <label for="actions">Actions</label>
                <select id="actions" name="actions[]" multiple>
                </select>
            </div>
            
            <div class="button-container">
                <button type="button" class="draft-button" id="save-draft">Save as Draft</button>
                <button type="submit" id="add-shortcut">Add Shortcut</button>
            </div>
        </form>
        <div id="feedback-message"></div>
    </div>
    <?php
}
