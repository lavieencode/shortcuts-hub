jQuery(document).on('click', '.delete-version', function() {
    const $button = jQuery(this);
    const shortcutId = $button.data('shortcut-id');
    const versionId = $button.data('version-id');
    deleteVersion(shortcutId, versionId);
});

function deleteVersion(shortcutId, versionId) {
    const data = {
        action: 'version_delete',
        security: shortcutsHubData.security.delete_version,
        id: shortcutId,
        version_id: versionId
    };

    jQuery.ajax({
        url: shortcutsHubData.ajax_url,
        method: 'POST',
        data: data,
        success: function(response) {
            if (response.success) {
                // Refresh the versions list after successful deletion
                fetchVersions(shortcutId);
            }
        },
        error: function(xhr, status, error) {
            // DEBUG: Log error response
            sh_debug_log('Version delete error', {
                message: 'Error during delete request',
                source: {
                    file: 'versions-delete.js',
                    line: 113,
                    function: 'deleteVersion.error'
                },
                data: {
                    xhr_status: status,
                    error: error,
                    response_text: xhr.responseText
                },
                debug: true
            });
        }
    });
}