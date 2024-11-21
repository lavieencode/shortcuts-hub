<?php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

$id = isset($_GET['id']) ? esc_attr($_GET['id']) : '';
$view = isset($_GET['view']) ? esc_attr($_GET['view']) : '';
$add_version_url = admin_url("admin.php?page=add-version&id={$id}");

function shortcuts_hub_render_shortcuts_list_page() {
    global $add_version_url, $view;
    ?>
    <div id="shortcuts-list-page" class="wrap">
        <?php if ($view !== 'versions') : ?>
        <h1 class="shortcuts-page-title">SHORTCUTS LIST</h1>
        <h2 id="shortcut-name-display" style="display: none;"></h2>

        <button id="back-to-shortcuts" style="display: none;">Back to Shortcuts</button>

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
            <button id="reset-filters" class="restore-button">Reset filters</button>
        </div>

        <div id="shortcuts-container" class="shortcuts-container"></div>
        <?php endif; ?>

        <?php if ($view === 'versions') : ?>
        <h1 class="versions-page-title">VERSIONS LIST</h1>
        <?php endif; ?>

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
                <a href="#" class="add-version-button">+</a>
            </div>
        </div>
        <div id="versions-container" class="versions-container"></div>
    </div>
    <div id="edit-version-modal" class="modal" style="display: none;">
        <h1>Edit Version</h1>
        <h2 id="version-display"></h2>
        <form id="edit-version-form">
            <input type="hidden" id="id" name="id">
            <input type="hidden" id="version-id" name="version_id">
            <input type="hidden" id="version_state" name="version_state">
            <input type="hidden" id="version_deleted" name="version_deleted">
            <div class="form-group">
                <label for="version-notes">Notes</label>
                <textarea id="version-notes" name="version_notes" required></textarea>
            </div>
            <div class="form-group">
                <label for="version-url">URL</label>
                <input type="text" id="version-url" name="version_url" required>
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
            <div class="button-container">
                <button type="button" class="update-button">Save</button>
                <button type="button" class="publish-button" style="display: none;">Publish</button>
                <button type="button" class="revert-button" style="display: none;">Revert to Draft</button>
                <button type="button" class="cancel-button">Cancel</button>
            </div>
        </form>
    </div>
    <div id="edit-shortcut-modal" class="modal" style="display: none;">
        <form id="edit-shortcut-form">
            <input type="hidden" id="id" name="id">
            <div class="form-group">
                <label for="shortcut-name">Name</label>
                <input type="text" id="shortcut-name" name="shortcut_name" required>
            </div>
            <div class="form-group">
                <label for="shortcut-headline">Headline</label>
                <input type="text" id="shortcut-headline" name="shortcut_headline" required>
            </div>
            <div class="form-group">
                <label for="shortcut-description">Description</label>
                <textarea id="shortcut-description" name="shortcut_description" required></textarea>
            </div>
            <div class="form-group">
                <label for="shortcut-website">Website</label>
                <input type="text" id="shortcut-website" name="shortcut_website">
            </div>
            <div class="button-container">
                <button type="submit" class="save-button">Save Shortcut</button>
                <button type="button" class="cancel-button">Cancel</button>
            </div>
        </form>
    </div>
    <div id="add-version-modal" class="modal" style="display: none;">
        <h1>ADD VERSION</h1>
        <h2 id="shortcut-name-display"></h2>
        <form id="add-version-form">
            <input type="hidden" id="id" name="id">
            <input type="hidden" id="version-id" name="version_id">
            <input type="hidden" id="version_state" name="version_state">
            <input type="hidden" id="version_deleted" name="version_deleted">
            <div class="form-group">
                <label for="version-name">Version Name</label>
                <input type="text" id="version-name" name="version_name" required>
            </div>
            <div class="form-group">
                <label for="version-notes">Notes</label>
                <textarea id="version-notes" name="version_notes" required></textarea>
            </div>
            <div class="form-group">
                <label for="version-url">URL</label>
                <input type="text" id="version-url" name="version_url" required>
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
            <div class="button-container">
                <button type="button" class="save-draft-button">Save as Draft</button>
                <button type="button" class="publish-button">Publish</button>
                <button type="button" class="cancel-button">Cancel</button>
            </div>
        </form>
    </div>
    <?php
}
