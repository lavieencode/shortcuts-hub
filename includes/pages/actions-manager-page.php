<?php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

function shortcuts_hub_render_actions_manager_page() {
    ?>
    <div id="actions-manager-page" class="wrap">
        <h1 class="actions-page-title">ACTIONS MANAGER</h1>
        <div class="actions-header-bar">
            <input type="text" id="search-actions-input" placeholder="Search actions">
            <select id="filter-action-status">
                <option value="">Any Status</option>
                <option value="publish">Published</option>
                <option value="draft">Draft</option>
            </select>
            <select id="filter-action-trash">
                <option value="">Any Trash</option>
                <option value="active">Not In Trash</option>
                <option value="trash">In Trash</option>
            </select>
            <button class="reset-filters" id="reset-action-filters">Reset filters</button>
            <button class="add-action" id="add-new-action">+</button>
        </div>
        <div id="actions-container" class="actions-container"></div>
    </div>

    <!-- Add Action Modal -->
    <div id="add-action-modal" class="modal">
        <div class="modal-content">
            <h2>Add New Action</h2>
            <form id="add-action-form">
                <div class="form-group">
                    <label for="action-name">Name</label>
                    <input type="text" id="action-name" name="name" required>
                </div>
                
                <div class="form-group">
                    <label for="action-description">Description</label>
                    <textarea id="action-description" name="description" required></textarea>
                </div>
                
                <div class="form-group">
                    <label for="action-icon-type">Icon</label>
                    <div class="icon-selector-container">
                        <div class="icon-input-row">
                            <select id="action-icon-type" class="icon-type-selector">
                                <option value="fontawesome">Font Awesome Icon</option>
                                <option value="custom">Custom Upload</option>
                            </select>
                            <div class="icon-preview"></div>
                        </div>
                        <div id="icon-selector" class="icon-selector">
                            <div class="selector-popup">
                                <input type="text" class="search-input" placeholder="Search icons...">
                                <select class="category-select">
                                    <option value="fas">Solid</option>
                                    <option value="far">Regular</option>
                                    <option value="fab">Brands</option>
                                </select>
                                <div class="icons-container"></div>
                                <div class="selector-pagination">
                                    <button class="prev-page">←</button>
                                    <span class="page-info">1/10</span>
                                    <button class="next-page">→</button>
                                </div>
                            </div>
                        </div>
                        <div id="custom-icon-upload" class="custom-upload" style="display: none;">
                            <button type="button" class="upload-button">Upload Icon</button>
                            <div class="upload-preview"></div>
                        </div>
                    </div>
                    <input type="hidden" id="action-icon" name="icon" value="">
                </div>

                <div class="button-container">
                    <div class="primary-buttons">
                        <button type="submit" class="save-button publish-action" data-status="publish">Publish</button>
                        <button type="submit" class="save-button save-draft" data-status="draft">Save as Draft</button>
                    </div>
                    <button type="button" class="cancel-button">Cancel</button>
                </div>
            </form>
        </div>
    </div>
   <!-- Edit Action Modal -->
   <div id="edit-action-modal" class="modal">
        <div class="modal-content">
            <h2>Edit Action</h2>
            <form id="edit-action-form">
                <input type="hidden" id="edit-action-id" name="id">
                <div class="form-group">
                    <label for="edit-action-name">Name</label>
                    <input type="text" id="edit-action-name" name="name" required>
                </div>
                
                <div class="form-group">
                    <label for="edit-action-description">Description</label>
                    <textarea id="edit-action-description" name="description" required></textarea>
                </div>
                
                <div class="form-group">
                    <label for="edit-action-icon-type">Icon</label>
                    <div class="icon-selector-container">
                        <div class="icon-input-row">
                            <select id="edit-action-icon-type" class="icon-type-selector">
                                <option value="fontawesome">Font Awesome Icon</option>
                                <option value="custom">Custom Upload</option>
                            </select>
                            <div class="icon-preview"></div>
                        </div>
                        <div id="edit-icon-selector" class="icon-selector">
                            <div class="selector-popup">
                                <input type="text" class="search-input" placeholder="Search icons...">
                                <select class="category-select">
                                    <option value="fas">Solid</option>
                                    <option value="far">Regular</option>
                                    <option value="fab">Brands</option>
                                </select>
                                <div class="icons-container"></div>
                                <div class="selector-pagination">
                                    <button class="prev-page">←</button>
                                    <span class="page-info">1/10</span>
                                    <button class="next-page">→</button>
                                </div>
                            </div>
                        </div>
                        <div id="edit-custom-icon-upload" class="custom-upload" style="display: none;">
                            <button type="button" class="upload-button">Upload Icon</button>
                            <div class="upload-preview"></div>
                        </div>
                    </div>
                    <input type="hidden" id="edit-action-icon" name="icon" value="">
                </div>

                <div class="button-container">
                    <div class="primary-buttons">
                        <!-- These buttons will be shown/hidden based on current status -->
                        <button type="submit" class="save-button update-action publish-button" data-status="publish">Update</button>
                        <button type="submit" class="save-button publish-action publish-button" data-status="publish">Publish</button>
                        <button type="submit" class="save-button save-draft save-draft-button" data-status="draft">Save as Draft</button>
                        <button type="submit" class="save-button revert-draft revert-button" data-status="draft">Revert to Draft</button>
                    </div>
                    <button type="button" class="cancel-button">Cancel</button>
                </div>
            </form>
        </div>
    </div>
    <?php
}