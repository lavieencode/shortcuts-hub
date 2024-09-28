<?php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Add admin page
function shortcuts_hub_add_admin_page() {
    add_menu_page(
        'Shortcuts Hub',
        'Shortcuts Hub',
        'manage_options',
        'shortcuts-hub',
        'shortcuts_hub_render_admin_page',
        'dashicons-admin-generic',
        6
    );
}

add_action('admin_menu', 'shortcuts_hub_add_admin_page');

// Render the admin page content
function shortcuts_hub_render_admin_page() {
    echo '
        <div id="shortcuts-hub-page">

            <!-- Main Header -->
            <h1 class="shortcuts-header">SHORTCUTS HUB</h1>
            
            <!-- Dynamic Shortcut Name (when viewing versions) -->
            <h2 id="shortcut-name-display" style="display: none;"></h2>

            <!-- Shortcuts Header (for Shortcuts Search/Filter) -->
            <div id="shortcuts-header">
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

            <!-- Shortcuts Container (Dynamic Content for Shortcuts) -->
            <div id="shortcuts-container"></div>

            <!-- Versions Filter/Search Bar -->
            <div id="versions-header-bar" style="display: none;">
                <div id="versions-filters">
                    <!-- Search Term -->
                    <input type="text" id="search-versions-input" class="versions-filters" placeholder="Search versions">

                    <!-- Status Dropdown (Published or Draft) -->
                    <select id="filter-version-status" class="version-filters">
                        <option value="">Any</option>
                        <option value="published">Published</option>
                        <option value="draft">Draft</option>
                    </select>

                    <!-- Deleted Dropdown -->
                    <select id="filter-version-deleted" class="version-filters">
                        <option value="">Any</option>
                        <option value="false">Not Deleted</option>
                        <option value="true">Deleted</option>
                    </select>

                    <!-- Required Update Dropdown -->
                    <select id="filter-required-update" class="version-filters">
                        <option value="">Any</option>
                        <option value="false">Optional</option>
                        <option value="true">Required</option>
                    </select>

                    <!-- Reset Filters Button -->
                    <button id="reset-version-filters" class="shortcuts-button">Reset filters</button>
                </div>
            </div>

            <!-- Versions Container -->
            <div id="versions-container" style="display: none;"></div>

            <!-- Modal for Editing Shortcut -->
            <div id="edit-modal" class="modal">
                <div class="modal-content">
                    <span class="close-button">&times;</span>
                    <h2>Edit Shortcut</h2>
                    <form id="edit-shortcut-form">
                        <input type="hidden" id="shortcut-id" name="id">
                        
                        <label for="shortcut-name">Shortcut Name</label>
                        <input type="text" id="shortcut-name" name="name" required>

                        <!-- Shortcut Headline -->
                        <label for="shortcut-headline">Headline</label>
                        <input type="text" id="shortcut-headline" name="headline">

                        <!-- Shortcut Description -->
                        <label for="shortcut-description">Description</label>
                        <textarea id="shortcut-description" name="description" required></textarea>

                        <!-- Shortcut Website -->
                        <label for="shortcut-website">Website</label>
                        <input type="url" id="shortcut-website" name="website">

                        <!-- Shortcut Status -->
                        <label for="shortcut-status">Status</label>
                        <select id="shortcut-status" name="state">
                            <option value="0">Published</option>
                            <option value="1">Draft</option>
                        </select>

                        <!-- Save and Cancel Buttons -->
                        <button type="submit" class="shortcuts-button">Save</button>
                        <button type="button" class="shortcuts-button close-button">Cancel</button>
                    </form>
                </div>
            </div>

            <!-- Modal for Editing Version -->
            <div id="edit-version-modal" class="modal" style="display: none;">
                <div class="modal-content">
                    <span class="close-button">&times;</span>
                    <h2>Edit Version</h2>
                    <form id="edit-version-form">
                        <input type="hidden" id="version-id" name="version_id">

                        <!-- Version Name -->
                        <label for="version-name">Version Name</label>
                        <input type="text" id="version-name" name="version_name" class="version-input" required>

                        <!-- Version Notes -->
                        <label for="version-notes">Release Notes</label>
                        <textarea id="version-notes" name="version_notes" class="version-textarea" required></textarea>

                        <!-- Version URL -->
                        <label for="version-url">Download URL</label>
                        <input type="url" id="version-url" name="version_url" class="version-input" required>

                        <!-- Status -->
                        <label for="version-status">Status</label>
                        <select id="version-status" name="version_status" class="version-select">
                            <option value="0">Published</option>
                            <option value="1">Draft</option>
                        </select>

                        <!-- Minimum iOS Version -->
                        <label for="version-ios">Minimum iOS Version</label>
                        <input type="text" id="version-ios" name="version_ios" class="version-input">

                        <!-- Minimum Mac Version -->
                        <label for="version-mac">Minimum macOS Version</label>
                        <input type="text" id="version-mac" name="version_mac" class="version-input">

                        <!-- Required Update -->
                        <label for="version-required">Required Update</label>
                        <select id="version-required" name="version_required" class="version-select">
                            <option value="false">Optional</option>
                            <option value="true">Required</option>
                        </select>

                        <!-- Save and Cancel Buttons -->
                        <button type="submit" class="shortcuts-button">Save Changes</button>
                        <button type="button" class="shortcuts-button close-button">Cancel</button>
                    </form>
                </div>
            </div>

        </div>';
}