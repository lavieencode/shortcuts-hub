jQuery(document).ready(function() {
    jQuery('#edit-version-modal .update-button').on('click', function(event) {
        event.preventDefault();
        updateVersion('save');
    });

    jQuery('#edit-version-modal .state-button').on('click', function(event) {
        event.preventDefault();
        const button = jQuery(this);
        button.text('Switching...').prop('disabled', true);

        const currentState = jQuery('#version_state').val();
        console.log('Current State:', currentState);
        const action = parseInt(currentState, 10) === 1 ? 'publish' : 'switch_to_draft';
        console.log('Determined Action:', action);

        updateVersion(action).always(function() {
            button.prop('disabled', false);
        });
    });
});

function handleVersionEditModal(id, versionId, latest = false) {
    jQuery.ajax({
        url: shortcutsHubData.ajax_url,
        method: 'POST',
        data: {
            action: 'fetch_version',
            security: shortcutsHubData.security,
            id: id,
            version_id: versionId,
            latest: latest
        },
        success: function(response) {
            if (response.success && response.data) {
                populateVersionEditModal(response.data).then(() => {
                    const version = response.data.version;
                    
                    // Set the hidden fields with the state and deleted status
                    jQuery('#version_state').val(version.state.value);
                    jQuery('#version_deleted').val(version.deleted ? 'true' : 'false');

                    // Reset button states
                    const updateButton = jQuery('.update-button');
                    const stateButton = jQuery('.state-button');

                    if (version.state.value === 1) { // Draft
                        updateButton.text('Save Draft').addClass('save-draft-button').removeClass('publish-button switch-to-draft-button');
                        stateButton.text('Publish').addClass('publish-button').removeClass('switch-to-draft-button save-draft-button');
                    } else if (version.state.value === 0) { // Published
                        updateButton.text('Update').removeClass('save-draft-button publish-button switch-to-draft-button');
                        stateButton.text('Switch to Draft').addClass('switch-to-draft-button').removeClass('publish-button save-draft-button');
                    }

                    jQuery('#edit-version-modal').addClass('active').show();
                    jQuery('body').addClass('modal-open');
                });
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
    return new Promise((resolve) => {
        const version = data.version || {};

        const urlParams = new URLSearchParams(window.location.search);
        const shortcutId = urlParams.get('id') || 'N/A';

        if (!shortcutId || shortcutId === 'N/A') {
            console.error('Shortcut ID is undefined or not available');
            return resolve();
        }

        jQuery('#edit-version-form #shortcut-id').val(shortcutId);
        jQuery('#edit-version-form #version-id').val(version.version || '');
        jQuery('#edit-version-form #version-notes').val(version.notes || '');
        jQuery('#edit-version-form #version-url').val(version.url || '');
        jQuery('#edit-version-form #version-ios').val(version.minimumiOS || '');
        jQuery('#edit-version-form #version-mac').val(version.minimumMac || '');
        jQuery('#edit-version-form #version-status').val(version.state ? version.state.value : '');
        jQuery('#edit-version-form #version-required').val(version.required ? 'true' : 'false');

        jQuery('#version-display').text(`${version.version || 'New Version'}`);

        resolve();
    });
}