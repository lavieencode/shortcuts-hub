jQuery(document).on('click', '.delete-version, .restore-version', function() {
    const shortcutId = jQuery(this).data('shortcut-id');
    const versionId = jQuery(this).data('version-id');
    const isRestore = jQuery(this).hasClass('restore-version');

    toggleVersionDelete(shortcutId, versionId, isRestore);
});

function toggleVersionDelete(id, versionId, isRestore) {
    if (!id || !versionId) {
        console.error('Shortcut ID or version ID is missing');
        return;
    }

    const action = 'version_toggle_delete';
    const requestData = {
        action: action,
        id: id,
        version_id: versionId,
        is_restore: isRestore,
        security: shortcutsHubData.security,
        _method: 'PATCH'
    };

    jQuery.ajax({
        url: shortcutsHubData.ajax_url,
        method: 'POST',
        data: requestData,
        success: function(response) {
            if (response.success) {
                const versionElement = jQuery(`.version-item[data-version-id="${versionId}"]`);
                const button = versionElement.find('.delete-version, .restore-version');
                const badge = versionElement.find('.badge');

                if (!isRestore) {
                    button.text('Restore Version').removeClass('delete-version').addClass('restore-version');
                    if (badge.length === 0 || !badge.hasClass('deleted-badge')) {
                        versionElement.find('.version-header').append('<span class="badge deleted-badge">Deleted</span>');
                    }
                } else {
                    button.text('Delete Version').removeClass('restore-version').addClass('delete-version');
                    badge.remove();
                }

                fetchVersions(shortcutId);
            } else {
                console.error('Error updating version:', response.data ? response.data.message : 'No data');
            }
        },
        error: function(xhr, status, error) {
            console.error('AJAX error:', status, error);
            console.error('Response Text:', xhr.responseText);
        }
    });
}