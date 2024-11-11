<?php
if (!defined('ABSPATH')) {
    exit;
}

function shortcuts_hub_render_add_version_page() {
    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.'));
    }

    $shortcut_id = isset($_GET['id']) ? esc_attr($_GET['id']) : '';
    $sb_id = isset($_GET['sb_id']) ? esc_attr($_GET['sb_id']) : '';

    global $wpdb;
    $table_name = $wpdb->prefix . 'shortcuts';

    $shortcut = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE ID = %d", $shortcut_id), ARRAY_A);

    if ($shortcut === null) {
        error_log("No shortcut found for WordPress ID: $shortcut_id");
        $shortcut_name = 'Shortcut not found';
    } else {
        if ($shortcut['sb_id'] !== $sb_id) {
            error_log("SB_ID mismatch: expected {$shortcut['sb_id']}, got $sb_id");
        }
        $shortcut_name = isset($shortcut['name']) ? $shortcut['name'] : 'Shortcut not found';
    }

    ?>
    <div class="wrap">
        <h1>Add New Version</h1>
        <h2 id="shortcut-name-display" class="shortcuts-page-title"><?php echo esc_html($shortcut_name); ?></h2>
        <form id="add-version-form">
            <input type="hidden" id="shortcut-id" name="shortcut_id" value="<?php echo esc_attr($shortcut_id); ?>">
            <input type="hidden" id="sb-id" name="sb_id" value="<?php echo esc_attr($sb_id); ?>">
            
            <div class="form-group">
                <label for="version">Version</label>
                <input type="text" id="version" name="version" required>
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
                    <option value="false">Not Required</option>
                    <option value="true">Required</option>
                </select>
            </div>
            <div class="button-container" style="display: flex; gap: 10px; align-items: center;">
                <button type="submit" id="save-version" name="action" value="save_draft">Save Draft</button>
                <button type="submit" id="publish-version" class="publish-button" name="action" value="publish">Publish</button>
                <button type="button" class="cancel-button" onclick="window.history.back();">Cancel</button>
            </div>
        </form>
    </div>
    <?php
}
