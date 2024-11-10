function toggleVersionDeletion(shortcutId, versionId, isRestore) {
    const action = isRestore ? 'restore_version' : 'delete_version';
    const deletedState = isRestore ? false : true;

    const requestData = {
        action: 'update_version',
        shortcut_id: shortcutId,
        version_id: versionId,
        version_data: { deleted: deletedState },
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

                if (deletedState) {
                    button.text('Restore Version').removeClass('delete-version').addClass('restore-version');
                    if (badge.length === 0) {
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