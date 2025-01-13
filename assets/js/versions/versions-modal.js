jQuery(document).ready(function() {
    // Add version button click handler
    jQuery('.add-version-button').on('click', function(event) {
        event.preventDefault();
        openAddVersionModal();
    });

    // Close button click handler
    jQuery('#edit-version-modal .close-button, #add-version-modal .close-button').on('click', function(event) {
        event.preventDefault();
        closeVersionModal();
    });

    // Close on background click
    jQuery('#edit-version-modal, #add-version-modal').on('click', function(event) {
        if (event.target === this) {
            closeVersionModal();
        }
    });

    // Cancel button click handler
    jQuery('#edit-version-modal .cancel-button').on('click', function(event) {
        event.preventDefault();
        closeVersionModal();
    });

    // Update Version Button
    jQuery('#edit-version-modal .update-button').on('click', function(event) {
        event.preventDefault();
        updateVersion('save');
    });

    // State Button
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

function openAddVersionModal() {
    const sb_id = getShortcutId();
    if (!sb_id) {
        console.error('No shortcut ID available for adding version');
        return;
    }

    // Clear the form
    jQuery('#add-version-form')[0].reset();

    // Set the shortcut ID
    jQuery('#add-version-form #id').val(sb_id);

    // Show the modal
    jQuery('#add-version-modal').addClass('active');
    jQuery('body').addClass('modal-open');
}

function closeVersionModal() {
    jQuery('#add-version-modal, #edit-version-modal').removeClass('active');
    jQuery('body').removeClass('modal-open');
}

function getShortcutId() {
    // First try to get from shortcutsHubData
    if (typeof shortcutsHubData !== 'undefined' && shortcutsHubData.shortcutId) {
        return shortcutsHubData.shortcutId;
    }
    
    // If not in shortcutsHubData, try URL parameters
    const urlParams = new URLSearchParams(window.location.search);
    const urlId = urlParams.get('id');
    
    return urlId || null;
}

function handleVersionEditModal(id, versionId, latest = false) {
    jQuery.ajax({
        url: shortcutsHubData.ajax_url + '/shortcuts/' + id + '/version/' + versionId,
        method: 'POST',
        data: {
            action: 'fetch_version',
            security: shortcutsHubData.security,
            id: id,
            version_id: versionId,
            latest: latest
        },
        success: function(response) {
            console.log('Version data fetched:', response.data);
            if (response.success && response.data) {
                populateVersionEditModal(response.data).then(() => {
                    const version = response.data.version;
                    
                    // Set the hidden fields with the state and deleted status
                    jQuery('#version_state').val(version.state.value);
                    jQuery('#version_deleted').val(version.deleted ? 'true' : 'false');

                    // Ensure the state button text is set correctly based on the version state
                    const publishButton = jQuery('#edit-version-modal .publish-button');
                    const revertButton = jQuery('#edit-version-modal .revert-button');
                    const stateButton = jQuery('#edit-version-modal .state-button');
                    const updateButton = jQuery('.update-button');

                    if (version.state && version.state.value === 0) { // Published
                        publishButton.hide();
                        revertButton.show();
                        stateButton.text('Revert to Draft').show();
                        updateButton.text('Update').removeClass('save-draft-button publish-button switch-to-draft-button');
                    } else if (version.state && version.state.value === 1) { // Draft
                        publishButton.show();
                        revertButton.hide();
                        stateButton.text('Publish').show();
                        updateButton.text('Save Draft').addClass('save-draft-button').removeClass('publish-button switch-to-draft-button');
                    } else {
                        stateButton.text('Unknown State').show();
                        console.error('Unexpected version state:', version.state);
                    }

                    // Log the version object for debugging
                    console.log('Version object:', version);

                    // Show the modal after setting the state button text
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