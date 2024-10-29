<?php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

function shortcuts_hub_render_add_shortcut_page() {
    ?>
    <div id="add-shortcut-page" class="wrap">
        <h1><?php esc_html_e('Add New Shortcut', 'plugin-name'); ?></h1>
        <form id="add-shortcut-form" class="form-container">
            <input type="hidden" id="shortcut-state" name="state" value="published">
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
                        <input type="color" id="shortcut-color" name="color">
                    </div>
                    
                    <div class="form-group">
                        <label for="shortcut-icon">Icon</label>
                        <input type="text" id="shortcut-icon" name="icon" readonly>
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
        });
    </script>
    <?php
}
