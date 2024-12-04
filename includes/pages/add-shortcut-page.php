<?php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

function shortcuts_hub_render_add_shortcut_page() {
    ?>
    <div id="add-shortcut-page" class="wrap">
        <h1><?php esc_html_e('Add New Shortcut', 'shortcuts-hub'); ?></h1>
        <?php 
            wp_enqueue_style('font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css', array(), '6.4.0');
            wp_nonce_field('shortcuts_hub_nonce', 'shortcuts_hub_nonce');
        ?>
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
                        <label for="description">Description</label>
                        <textarea id="description" name="description" required></textarea>
                    </div>

                    <input type="hidden" id="website" name="website" value="">
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
                            <input type="text" id="color" name="color" value="#909CFE" class="color-picker" />
                        </div>
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
                        <input type="hidden" id="shortcut-icon" name="icon" value="">
                    </div>
                </div>
            </div>
            
            <div class="form-group">
                <label for="actions">Actions</label>
                <select id="actions" name="actions[]" multiple></select>
            </div>
            
            <div class="button-container">
                <button type="button" class="draft-button" id="save-draft">Save as Draft</button>
                <button type="button" class="publish-button" id="add-shortcut">Add Shortcut</button>
            </div>
        </form>
        <div id="feedback-message"></div>
    </div>
    <?php
    // Initialize IconSelector
    add_action('admin_footer', function() {
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
