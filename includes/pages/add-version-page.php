<?php
if (!defined('ABSPATH')) {
    exit;
}

function shortcuts_hub_render_add_version_page() {
    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.'));
    }

    $id = isset($_GET['id']) ? esc_attr($_GET['id']) : '';
    // Removed logging for debugging purposes

    $response = sb_api_call("/shortcuts/{$id}", 'GET');
    // Removed logging for debugging purposes

    if (is_wp_error($response) || empty($response['name'])) {
        $shortcut_name = 'Shortcut not found';
    } else {
        $shortcut_name = $response['name'];
        // Removed logging for debugging purposes
    }

    ?>
    <div class="wrap">
        <h1>Add New Version</h1>
        <h2 id="shortcut-name-display"><?php echo esc_html($shortcut_name); ?></h2>
        <form id="add-version-form">
            <input type="hidden" id="id" name="id" value="<?php echo esc_attr($id); ?>">
            
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
            <div class="button-container">
                <button type="submit" id="save-version" name="action" value="save">Save Draft</button>
                <button type="submit" id="publish-version" class="publish-button" name="action" value="publish">Publish</button>
                <button type="button" class="cancel-button" onclick="window.history.back();">Cancel</button>
            </div>
        </form>
    </div>
    <?php
}
