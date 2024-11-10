<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

function shortcuts_hub_render_edit_version_page() {
    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.'));
    }

    $id = isset($_GET['id']) ? esc_attr($_GET['id']) : '';
    $version_id = isset($_GET['version_id']) ? esc_attr($_GET['version_id']) : '';

    ?>
    <div class="wrap">
        <h1>Edit Version</h1>
        <form id="edit-version-form">
            <input type="hidden" id="shortcut-id" name="shortcut_id" value="<?php echo esc_attr($id); ?>">
            <input type="hidden" id="version-id" name="version_id" value="<?php echo esc_attr($version_id); ?>">
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
                <button type="submit" class="save-button">Update Version</button>
                <button type="button" class="cancel-button" onclick="window.history.back();">Cancel</button>
            </div>
        </form>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const id = '<?php echo esc_js($id); ?>';
            const versionId = '<?php echo esc_js($version_id); ?>';
            fetchVersion(id, versionId);
        });
    </script>
    <?php
} 