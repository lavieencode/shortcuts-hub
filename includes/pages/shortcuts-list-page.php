<?php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

$shortcut_id = isset($_GET['id']) ? esc_attr($_GET['id']) : '';
$add_version_url = admin_url("admin.php?page=add-version&id={$shortcut_id}");

function shortcuts_hub_render_shortcuts_list_page() {
    global $add_version_url;
    ?>
    <div id="shortcuts-list-page" class="wrap">
        <h1 class="shortcuts-page-title">SHORTCUTS LIST</h1>
        <h1 class="versions-page-title" style="display: none;">VERSIONS LIST</h1>
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
            <button id="reset-filters" class="shortcuts-button">Reset filters</button>
        </div>

        <div id="shortcuts-container" class="shortcuts-container"></div>

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
                <a href="<?php echo esc_url($add_version_url); ?>" class="add-version-button">+</a>
            </div>
        </div>
        <div id="versions-container" class="versions-container"></div>
    </div>
    <?php
}
