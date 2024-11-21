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
                security: shortcutsHubData.security
            },
            success: function(response) {
                console.log('Response:', response);
                if (response.success) {
                    jQuery('#version-feedback-message').text('Version state toggled successfully.').show();
                    setTimeout(function() {
                        jQuery('#version-feedback-message').hide();
                        jQuery('#edit-version-modal').removeClass('active').hide();
                        jQuery('body').removeClass('modal-open');
                        fetchVersions(shortcutId);
                    }, 1000);
                } else {
                    console.error('Unexpected response format:', response);
                    jQuery('#version-feedback-message').text('Unexpected response format.').show();
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX error:', status, error);
                jQuery('#version-feedback-message').text('Error toggling version state.').show();
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
                security: shortcutsHubData.security,
                _method: 'PATCH'
            },
            success: function(response) {
                console.log('Response:', response);
                if (response.success && response.data && response.data.version) {
                    jQuery('#version-feedback-message').text('Version updated successfully.').show();
                    setTimeout(function() {
                        jQuery('#version-feedback-message').hide();
                        jQuery('#edit-version-modal').removeClass('active').hide();
                        jQuery('body').removeClass('modal-open');
                        fetchVersions(shortcutId);
                    }, 1000);
                } else {
                    console.error('Unexpected response format:', response);
                    jQuery('#version-feedback-message').text('Unexpected response format.').show();
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX error:', status, error);
                jQuery('#version-feedback-message').text('Error updating version.').show();
            }
        });
    }
}