<?php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

if (!defined('SHORTCUTS_HUB_PLUGIN_DIR')) {
    define('SHORTCUTS_HUB_PLUGIN_DIR', plugin_dir_path(dirname(dirname(__FILE__))));
}

function render_shortcuts_list_page() {
    // Get URL parameters
    $view = isset($_GET['view']) ? sanitize_text_field($_GET['view']) : 'shortcuts';
    $id = isset($_GET['id']) ? sanitize_text_field($_GET['id']) : '';
    $add_version_url = admin_url("admin.php?page=add-version&id={$id}");

    // Determine initial visibility based on URL parameters
    $show_shortcuts = $view !== 'versions';
    $show_versions = $view === 'versions';

    ?>
    <div id="shortcuts-list-page" class="wrap">
        <div id="shortcuts-view" style="display: <?php echo $show_shortcuts ? 'block' : 'none'; ?>">
            <h1 class="shortcuts-page-title">SHORTCUTS</h1>
            <div id="shortcuts-header-bar">
                <input type="text" id="search-input" placeholder="Search shortcuts...">
                <select id="filter-status">
                    <option value="">All Status</option>
                    <option value="publish">Published</option>
                    <option value="draft">Draft</option>
                </select>
                <select id="filter-deleted">
                    <option value="">Not Deleted</option>
                    <option value="trash">Deleted</option>
                </select>
                <button id="reset-filters">Reset Filters</button>
                <a href="<?php echo admin_url('admin.php?page=add-shortcut'); ?>" class="add-shortcut-button">+</a>
                <div class="view-toggle-container">
                    <button id="view-toggle" class="view-toggle-button" data-view="grid">
                        <div class="toggle-slider">
                            <i class="fas fa-th-large grid-icon"></i>
                            <i class="fas fa-list list-icon"></i>
                            <span class="slider-thumb"></span>
                        </div>
                    </button>
                </div>
            </div>
            <div id="shortcuts-container">
                <div id="shortcuts-grid-container" class="active">
                    <div class="shortcuts-grid"></div>
                </div>
                <div id="shortcuts-table-container">
                    <table class="shortcuts-table">
                        <thead>
                            <tr>
                                <th class="name-column">Name</th>
                                <th class="headline-column">Headline</th>
                                <th class="status-column">Status</th>
                                <th class="actions-count-column">Actions</th>
                                <th class="actions-column">Actions</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>

        <div id="versions-view" style="display: <?php echo $show_versions ? 'block' : 'none'; ?>">
            <div class="versions-header">
                <h1 class="versions-page-title">VERSIONS</h1>
                <div class="versions-header-buttons">
                    <button id="back-to-shortcuts">Back to Shortcuts</button>
                    <button id="versions-trash-view">Trash</button>
                </div>
            </div>
            <h2 id="shortcut-name-display"></h2>

            <div id="versions-header-bar">
                <div id="versions-filters">
                    <input type="text" id="search-versions-input" class="versions-filters" placeholder="Search versions">
                    <select id="filter-version-status" class="version-filters">
                        <option value="any">Any</option>
                        <option value="0">Published</option>
                        <option value="1">Draft</option>
                    </select>
                    <select id="filter-required-update" class="version-filters">
                        <option value="any">Any</option>
                        <option value="true">Required</option>
                        <option value="false">Not Required</option>
                    </select>
                    <button id="reset-version-filters">Reset filters</button>
                    <a href="#" class="add-version-button">+</a>
                </div>
            </div>
            <div id="versions-container" class="versions-container"></div>
        </div>
    </div>
    <div id="edit-version-modal" class="modal">
        <h1>Edit Version</h1>
        
        <h2 id="version-display"></h2>
        <form id="edit-version-form">
            <input type="hidden" id="id" name="id">
            <input type="hidden" id="edit-version-id" name="version_id">
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
        <h1>Edit Shortcut</h1>
        <h2 id="edit-shortcut-name-display"></h2>
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
                <label for="shortcut-icon-type">Icon</label>
                <div class="icon-selector-container">
                    <div class="icon-input-row">
                        <select id="shortcut-icon-type" class="icon-type-selector">
                            <option value="" disabled selected>Select a new icon...</option>
                            <option value="fontawesome">Font Awesome Icon</option>
                            <option value="custom">Custom Upload</option>
                        </select>
                        <div class="icon-preview empty">
                            <i class="fas fa-image"></i>
                        </div>
                    </div>
                    <div id="icon-selector" class="icon-selector" style="display: none;">
                        <div class="selector-popup">
                            <div class="selector-controls">
                                <input type="text" class="search-input" placeholder="Search icons...">
                                <select class="category-select">
                                    <option value="fas">Solid</option>
                                    <option value="far">Regular</option>
                                    <option value="fab">Brands</option>
                                </select>
                            </div>
                            <div class="icons-grid"></div>
                        </div>
                    </div>
                    <div id="custom-icon-upload" class="custom-upload" style="display: none;">
                        <button type="button" class="upload-button">Upload Icon</button>
                        <div class="upload-preview"></div>
                    </div>
                </div>
                <input type="hidden" id="shortcut-icon" name="icon" value="">
            </div>

            <div class="form-group">
                <label for="shortcut-color">Color</label>
                <div class="color-selector-container">
                    <input type="text" class="color-value" value="Select a color..." readonly>
                    <input type="color" class="color-picker" value="#909CFE">
                </div>
                <input type="hidden" id="shortcut-color" name="color" value="#909CFE">
            </div>

            <div class="form-group">
                <label for="shortcut-input">Input</label>
                <input type="text" id="shortcut-input" name="input" required>
            </div>

            <div class="form-group">
                <label for="shortcut-result">Result</label>
                <input type="text" id="shortcut-result" name="result" required>
            </div>

            <div class="button-container">
                <div class="primary-buttons">
                    <!-- Published state buttons -->
                    <button type="submit" class="save-button update-shortcut publish-button" data-status="publish">Update</button>
                    <button type="submit" class="save-button revert-draft revert-button" data-status="draft">Revert to Draft</button>
                    
                    <!-- Draft state buttons -->
                    <button type="submit" class="save-button publish-shortcut publish-button" data-status="publish">Publish</button>
                    <button type="submit" class="save-button save-draft save-draft-button" data-status="draft">Save as Draft</button>
                </div>
                <button type="button" class="cancel-button">Cancel</button>
            </div>
        </form>
    </div>
    <div id="add-shortcut-modal" class="modal" style="display: none;">
        <h1>Add New Shortcut</h1>
        <form id="add-shortcut-form" method="post">
            <?php wp_nonce_field('add_shortcut_nonce', 'shortcut_nonce'); ?>

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
                <label for="shortcut-input">Input</label>
                <input type="text" id="shortcut-input" name="input" required>
            </div>

            <div class="form-group">
                <label for="shortcut-result">Result</label>
                <input type="text" id="shortcut-result" name="result" required>
            </div>

            <div class="form-group">
                <label for="shortcut-color">Color</label>
                <div class="color-selector-container">
                    <input type="text" class="color-value" value="Select a color..." readonly>
                    <input type="color" class="color-picker" value="#909CFE">
                </div>
                <input type="hidden" id="shortcut-color" name="color" value="#909CFE">
            </div>

            <div class="form-group">
                <label for="shortcut-icon">Icon</label>
                <div class="icon-selector-container">
                    <div class="icon-input-row">
                        <select id="shortcut-icon-type" class="icon-type-selector">
                            <option value="" disabled selected>Select a new icon...</option>
                            <option value="fontawesome">Font Awesome Icon</option>
                            <option value="custom">Custom Upload</option>
                        </select>
                        <div class="icon-preview empty">
                            <i class="fas fa-image"></i>
                        </div>
                    </div>
                    <div id="icon-selector" class="icon-selector" style="display: none;">
                        <div class="selector-popup">
                            <div class="selector-controls">
                                <input type="text" class="search-input" placeholder="Search icons...">
                                <button type="button" class="reset-button">Reset</button>
                            </div>
                            <div class="icons-grid"></div>
                        </div>
                    </div>
                </div>
                <input type="hidden" id="shortcut-icon" name="icon" value="">
            </div>

            <div class="button-container">
                <button type="submit" class="button button-primary">Add Shortcut</button>
                <button type="button" class="button button-secondary cancel-button">Cancel</button>
            </div>
        </form>
    </div>
    <div id="add-version-modal" class="modal">
        <h1>Add New Version</h1>
        <h2 id="shortcut-name-display"></h2>
        <form id="add-version-form">
            <input type="hidden" id="id" name="id">
            <input type="hidden" id="add-version-id" name="version_id">
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

function render_grid_view($shortcuts) {
    echo '<div class="shortcuts-container">';
    foreach ($shortcuts as $shortcut) {
        $headline = get_post_meta($shortcut->ID, '_shortcut_headline', true);
        $status = $shortcut->post_status;
        ?>
        <div class="shortcut-card">
            <div class="shortcut-header">
                <h3 class="shortcut-title"><?php echo esc_html($shortcut->post_title); ?></h3>
                <span class="shortcut-status <?php echo esc_attr($status); ?>"><?php echo esc_html(ucfirst($status)); ?></span>
            </div>
            <div class="shortcut-content">
                <p class="shortcut-headline"><?php echo esc_html($headline); ?></p>
                <span class="action-count"><?php echo esc_html($shortcut->action_count); ?></span>
            </div>
            <div class="shortcut-actions">
                <a href="<?php echo admin_url('admin.php?page=edit-shortcut&id=' . $shortcut->ID); ?>" class="edit-shortcut">Edit</a>
                <a href="#" class="delete-shortcut" data-id="<?php echo esc_attr($shortcut->ID); ?>">Delete</a>
                <a href="#" class="duplicate-shortcut" data-id="<?php echo esc_attr($shortcut->ID); ?>">Duplicate</a>
            </div>
        </div>
        <?php
    }
    echo '</div>';
}

function render_list_view($shortcuts) {
    ?>
    <table class="shortcuts-table">
        <thead>
            <tr>
                <th class="name-column">Name</th>
                <th class="headline-column">Headline</th>
                <th class="status-column">Status</th>
                <th class="actions-count-column">Actions</th>
                <th class="actions-column">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($shortcuts as $shortcut): ?>
                <?php 
                $headline = get_post_meta($shortcut->ID, '_shortcut_headline', true);
                $status = $shortcut->post_status;
                ?>
                <tr>
                    <td class="name-column"><?php echo esc_html($shortcut->post_title); ?></td>
                    <td class="headline-column"><?php echo esc_html($headline); ?></td>
                    <td class="status-column"><?php echo esc_html(ucfirst($status)); ?></td>
                    <td class="actions-count-column">
                        <span class="action-count"><?php echo esc_html($shortcut->action_count); ?></span>
                    </td>
                    <td class="actions-column">
                        <div class="shortcut-actions">
                            <a href="<?php echo admin_url('admin.php?page=edit-shortcut&id=' . $shortcut->ID); ?>" class="edit-shortcut">Edit</a>
                            <a href="#" class="delete-shortcut" data-id="<?php echo esc_attr($shortcut->ID); ?>">Delete</a>
                            <a href="#" class="duplicate-shortcut" data-id="<?php echo esc_attr($shortcut->ID); ?>">Duplicate</a>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php
}
