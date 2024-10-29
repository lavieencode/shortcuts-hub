<?php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

function shortcuts_hub_render_shortcuts_list_page() {
    echo '
    <div id="shortcuts-list-page" class="wrap">
        <h1 class="shortcuts-page-title">SHORTCUTS LIST</h1>
        <button id="back-to-shortcuts" style="display: none;">Back to Shortcuts</button>
        <h2 id="shortcut-name-display" style="display: none;"></h2>

        <div id="shortcuts-header-bar">
            <input type="text" id="search-input" placeholder="Search shortcuts">
            <select id="filter-status">
                <option value="">Any</option>
                <option value="0">Published</option>
                <option value="1">Draft</option>
            </select>
            <select id="filter-deleted">
                <option value="">Any</option>
                <option value="false">Not Deleted</option>
                <option value="true">Deleted</option>
            </select>
            <button id="reset-filters" class="shortcuts-button">Reset filters</button>
        </div>

        <h2 class="section-title">SWITCHBLADE</h2>
        <div id="switchblade-shortcuts-container" class="shortcuts-container"></div>

        <h2 class="section-title">WORDPRESS</h2>
        <div id="wp-shortcuts-container" class="shortcuts-container"></div>

        <div id="versions-header-bar" style="display: none;">
            <div id="versions-filters">
                <input type="text" id="search-versions-input" class="versions-filters" placeholder="Search versions">
                <select id="filter-version-status" class="version-filters">
                    <option value="">Any</option>
                    <option value="0">Published</option>
                    <option value="1">Draft</option>
                </select>
                <select id="filter-version-deleted" class="version-filters">
                    <option value="">Any</option>
                    <option value="true">Deleted</option>
                    <option value="false">Not Deleted</option>
                </select>
                <select id="filter-required-update" class="version-filters">
                    <option value="">Any</option>
                    <option value="true">Required</option>
                    <option value="false">Not Required</option>
                </select>
                <button id="reset-version-filters">Reset filters</button>
            </div>
        </div>

        <div id="versions-container" style="display: none;"></div>

        <div id="edit-modal" class="modal">
            <div class="modal-content">
                <span class="close-button">&times;</span>
                <h2>Edit Shortcut</h2>
                <form id="edit-shortcut-form">
                    <input type="hidden" id="shortcut-id" name="id">
                    <label for="shortcut-name">Shortcut Name</label>
                    <input type="text" id="shortcut-name" name="name" required>
                    <label for="shortcut-headline">Headline</label>
                    <input type="text" id="shortcut-headline" name="headline">
                    <label for="shortcut-description">Description</label>
                    <textarea id="shortcut-description" name="description" required></textarea>
                    <label for="shortcut-website">Website</label>
                    <input type="url" id="shortcut-website" name="website">
                    <label for="shortcut-status">Status</label>
                    <select id="shortcut-status" name="state">
                        <option value="0">Published</option>
                        <option value="1">Draft</option>
                    </select>
                    <button type="submit" class="shortcuts-button">Save</button>
                    <button type="button" class="shortcuts-button close-button">Cancel</button>
                </form>
            </div>
        </div>

        <div id="edit-version-modal" class="modal edit-version-modal" style="display: none;">
            <div class="modal-content">
                <span class="close-button">&times;</span>
                <h2>Edit Version</h2>
                <p id="version-display"></p>
                <form id="edit-version-form">
                    <input type="hidden" id="version-id" name="version_id">
                    <input type="hidden" id="shortcut-id" name="shortcut_id">
                    <div class="form-group">
                        <label for="version-notes">Notes</label>
                        <textarea id="version-notes" name="version_notes"></textarea>
                    </div>
                    <div class="form-group">
                        <label for="version-url">URL</label>
                        <input type="text" id="version-url" name="version_url">
                    </div>
                    <div class="form-group">
                        <label for="version-ios">Minimum iOS</label>
                        <input type="text" id="version-ios" name="version_ios">
                    </div>
                    <div class="form-group">
                        <label for="version-mac">Minimum Mac</label>
                        <input type="text" id="version-mac" name="version_mac">
                    </div>
                    <div class="form-group">
                        <label for="version-required">Required Update</label>
                        <select id="version-required" name="version_required">
                            <option value="false">No</option>
                            <option value="true">Yes</option>
                        </select>
                    </div>
                    <div id="version-feedback-message" style="display: none; color: #909CFE; margin-bottom: 10px;"></div>
                    <div class="button-container">
                        <button type="button" class="shortcuts-button draft-button" style="display: none;">Save as Draft</button>
                        <button type="button" class="shortcuts-button publish-button" style="display: none;">Publish</button>
                        <button type="button" class="shortcuts-button switch-to-draft-button" style="display: none;">Switch to Draft</button>
                        <button type="submit" class="shortcuts-button save-button">Save</button>
                        <button type="button" class="shortcuts-button delete-button">Delete</button>
                        <button type="button" class="shortcuts-button close-button">Cancel</button>
                    </div>
                </form>
            </div>
        </div>

    </div>';
}
