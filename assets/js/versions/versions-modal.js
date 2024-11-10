jQuery(document).ready(function() {
});

function openVersionEditModal(shortcutId, versionId, latest = false) {
    jQuery.ajax({
        url: shortcutsHubData.ajax_url,
        method: 'POST',
        data: {
            action: 'fetch_version',
            security: shortcutsHubData.security,
            shortcut_id: shortcutId,
            version_id: versionId,
            latest: latest
        },
        success: function(response) {
            if (response.success && response.data) {
                populateVersionEditModal(response.data);
                jQuery('#edit-version-modal').addClass('active').show();
                jQuery('body').addClass('modal-open');
            } else {
                console.error('Error loading version data:', response.data ? response.data.message : 'No data');
            }
        },
        error: function(xhr, status, error) {
            console.error('AJAX error:', status, error);
            console.error('Response Text:', xhr.responseText);
        }
    });
}

function populateVersionEditModal(data) {
    const version = data.version;

    jQuery('#edit-version-form #shortcut-id').val(data.shortcut.id || '');
    jQuery('#edit-version-form #version-id').val(version.version || '');
    jQuery('#edit-version-form #version-notes').val(version.notes || '');
    jQuery('#edit-version-form #version-url').val(version.url || '');
    jQuery('#edit-version-form #version-ios').val(version.minimumiOS || '');
    jQuery('#edit-version-form #version-mac').val(version.minimumMac || '');
    jQuery('#edit-version-form #version-status').val(version.state && version.state.value ? version.state.value : '');
    jQuery('#edit-version-form #version-required').val(version.required ? 'true' : 'false');

    if (version.state && version.state.value === 1) { // Draft
        jQuery('.publish-button').show();
        jQuery('.switch-to-draft-button').hide();
    } else if (version.state && version.state.value === 0) { // Published
        jQuery('.publish-button').hide();
        jQuery('.switch-to-draft-button').show();
    }

    jQuery('#edit-version-modal').show();
}

function updateVersion(action) {
    const shortcutId = jQuery('#edit-version-form #shortcut-id').val();
    const versionId = jQuery('#edit-version-form #version-id').val();
    const versionState = jQuery('#version-status').val();
    const stateValue = versionState === '0' ? 0 : 1;
    const requiredValue = jQuery('#version-required').val() === 'true';

    const versionData = {
        version: jQuery('#version-display').text(),
        notes: jQuery('#version-notes').val(),
        url: jQuery('#version-url').val(),
        state: stateValue,
        minimumiOS: jQuery('#version-ios').val(),
        minimumMac: jQuery('#version-mac').val(),
        required: requiredValue
    };

    if (action === 'publish') {
        versionData.state = 0;
    } else if (action === 'draft' || action === 'switch_to_draft') {
        versionData.state = 1;
    }

    console.log('Sending data:', {
        shortcut_id: shortcutId,
        version_id: versionId,
        version_data: versionData
    });

    jQuery.ajax({
        url: shortcutsHubData.ajax_url,
        method: 'POST',
        data: {
            action: 'update_version',
            shortcut_id: shortcutId,
            version_id: versionId,
            version_data: versionData,
            security: shortcutsHubData.security,
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