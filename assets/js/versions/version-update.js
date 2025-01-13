jQuery(document).ready(function() {
});

function updateVersion(action) {
    const urlParams = new URLSearchParams(window.location.search);
    const shortcutId = urlParams.get('id');
    if (!shortcutId) {
        console.error('Shortcut ID is missing from the URL.');
        return;
    }

    const versionId = jQuery('#edit-version-form #version-id').val();
    const stateValue = jQuery('#version_state').val();

    console.log('Shortcut ID:', shortcutId);
    console.log('Version ID:', versionId);
    console.log('Action:', action);

    if (action === 'switch_to_draft' || action === 'publish') {
        const newState = action === 'switch_to_draft' ? 1 : 0;
        return jQuery.ajax({
            url: shortcutsHubData.ajax_url,
            method: 'POST',
            data: {
                action: 'version_toggle_draft',
                id: shortcutId,
                version_id: versionId,
                state: { value: newState },
                security: shortcutsHubData.versions_security
            },
            success: function(response) {
                if (response.success) {
                    jQuery('#version-feedback-message').text('Version state toggled successfully.').show();
                    setTimeout(function() {
                        jQuery('#version-feedback-message').hide();
                        jQuery('#edit-version-modal').removeClass('active').hide();
                        jQuery('body').removeClass('modal-open');
                        fetchVersions(shortcutId);
                    }, 1000);
                } else {
                    const errorMessage = response.data && response.data.message 
                        ? response.data.message 
                        : 'Error toggling version state';
                    jQuery('#version-feedback-message').text(errorMessage).show();
                }
            },
            error: function(xhr, status, error) {
                const errorMessage = xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message
                    ? xhr.responseJSON.data.message
                    : 'Error toggling version state';
                jQuery('#version-feedback-message').text(errorMessage).show();
            }
        });
    } else if (action === 'save') {
        const versionData = {
            version: jQuery('#version-display').text(),
            notes: jQuery('#version-notes').val(),
            url: jQuery('#version-url').val(),
            state: stateValue,
            minimumiOS: jQuery('#version-ios').val(),
            minimumMac: jQuery('#version-mac').val(),
            required: jQuery('#version-required').val() === 'true'
        };

        console.log('Sending version data:', versionData);

        jQuery.ajax({
            url: shortcutsHubData.ajax_url,
            method: 'POST',
            data: {
                action: 'update_version',
                id: shortcutId,
                version_id: versionId,
                version_data: versionData,
                security: shortcutsHubData.versions_security,
                _method: 'PATCH'
            },
            success: function(response) {
                if (response.success && response.data && response.data.version) {
                    jQuery('#version-feedback-message').text('Version updated successfully.').show();
                    setTimeout(function() {
                        jQuery('#version-feedback-message').hide();
                        jQuery('#edit-version-modal').removeClass('active').hide();
                        jQuery('body').removeClass('modal-open');
                        fetchVersions(shortcutId);
                    }, 1000);
                } else {
                    const errorMessage = response.data && response.data.message 
                        ? response.data.message 
                        : 'Error updating version';
                    jQuery('#version-feedback-message').text(errorMessage).show();
                }
            },
            error: function(xhr, status, error) {
                const errorMessage = xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message
                    ? xhr.responseJSON.data.message
                    : 'Error updating version';
                jQuery('#version-feedback-message').text(errorMessage).show();
            }
        });
    }
}