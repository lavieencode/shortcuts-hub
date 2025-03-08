<?php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

function render_actions_manager_page() {
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
            <button class="reset-filters" id="reset-action-filters">Reset</button>
            <button class="add-action" id="add-new-action">+</button>
        </div>
        <div id="actions-container" class="actions-container">
            <div id="actions-loading" class="actions-loading">
                <div class="spinner"></div>
            </div>
            <table class="actions-table">
                <thead>
                    <tr>
                        <th class="action-name-column">Name</th>
                        <th class="action-description-column">Description</th>
                        <th class="action-status-column">Status</th>
                        <th class="action-shortcuts-column">Shortcuts</th>
                        <th class="action-actions-column">Actions</th>
                    </tr>
                </thead>
                <tbody id="actions-list">
                    <!-- Actions will be loaded here via JavaScript -->
                </tbody>
            </table>
        </div>
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
                    <label for="action-input">Input</label>
                    <input type="text" id="action-input" name="input" placeholder="Input required for this action">
                </div>
                
                <div class="form-group">
                    <label for="action-result">Result</label>
                    <input type="text" id="action-result" name="result" placeholder="Result of this action">
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

                <div class="form-group">
                    <label for="action-shortcuts">Associated Shortcuts</label>
                    <div class="shortcuts-selector-container">
                        <div class="shortcuts-search-container">
                            <input type="text" id="shortcuts-search" placeholder="Search shortcuts...">
                        </div>
                        <div class="shortcuts-list-container">
                            <ul id="shortcuts-list" class="shortcuts-list"></ul>
                        </div>
                    </div>
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
                    <label for="edit-action-input">Input</label>
                    <input type="text" id="edit-action-input" name="input" placeholder="Input required for this action">
                </div>
                
                <div class="form-group">
                    <label for="edit-action-result">Result</label>
                    <input type="text" id="edit-action-result" name="result" placeholder="Result of this action">
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

                <div class="form-group">
                    <label for="edit-action-shortcuts">Associated Shortcuts</label>
                    <div class="shortcuts-selector-container">
                        <div class="shortcuts-search-container">
                            <input type="text" id="edit-shortcuts-search" placeholder="Search shortcuts...">
                        </div>
                        <div class="shortcuts-list-container">
                            <ul id="edit-shortcuts-list" class="shortcuts-list"></ul>
                        </div>
                    </div>
                </div>

                <div class="button-container">
                    <div class="primary-buttons">
                        <!-- For draft state: Show Publish and Save Draft -->
                        <button type="submit" class="save-button publish-action" data-status="publish">Publish</button>
                        <button type="submit" class="save-button save-draft" data-status="draft">Save Draft</button>
                        
                        <!-- For published state: Show Update and Revert to Draft -->
                        <button type="submit" class="save-button update-action" data-status="publish">Update</button>
                        <button type="submit" class="save-button revert-draft" data-status="draft">Revert to Draft</button>
                    </div>
                    <button type="button" class="cancel-button">Cancel</button>
                </div>
            </form>
        </div>
    </div>
    <?php
}