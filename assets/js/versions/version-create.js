jQuery(document).ready(function() {
    // Add version modal handlers
    jQuery('#add-version-modal .save-draft-button').on('click', function(event) {
        event.preventDefault();
        createVersion('draft');
    });

    jQuery('#add-version-modal .publish-button').on('click', function(event) {
        event.preventDefault();
        createVersion('published');
    });

    jQuery('#add-version-modal .cancel-button').on('click', function(event) {
        event.preventDefault();
        closeVersionModal();
    });
});

/**
 * Gets the shortcut ID from either shortcutsHubData or URL parameters
 * @returns {string|null} The shortcut ID or null if not found
 */
function getShortcutId() {
    // First try to get from shortcutsHubData
    if (typeof shortcutsHubData !== 'undefined' && shortcutsHubData.shortcutId) {
        return shortcutsHubData.shortcutId;
    }
    
    // If not in shortcutsHubData, try URL parameters
    const urlParams = new URLSearchParams(window.location.search);
    const urlId = urlParams.get('id');
    
    // Return whichever ID we found, or null if none found
    return urlId || null;
}

function createVersion(state) {
    const sb_id = getShortcutId();
    if (!sb_id) {
        console.error('No shortcut ID available for creating version');
        return;
    }

    // Get form data
    const version = jQuery('#add-version-form #version-name').val().trim();
    const notes = jQuery('#add-version-form #version-notes').val();
    const url = jQuery('#add-version-form #version-url').val();
    const minimumiOS = jQuery('#add-version-form #version-ios').val();
    const minimumMac = jQuery('#add-version-form #version-mac').val();
    const required = jQuery('#add-version-form #version-required').val() === 'true';

    // Validate version number format (e.g., "1.0", "2.1.3")
    const versionRegex = /^\d+(\.\d+)*$/;
    if (!versionRegex.test(version)) {
        jQuery('#version-feedback-message').text('Invalid version number format. Please use numbers and single dots (e.g., 1.0, 2.1.3)').show();
        return;
    }

    // Debug log the form data and nonce
    sh_debug_log('Creating version', {
        message: 'Attempting to create version',
        source: {
            file: 'version-create.js',
            line: 'createVersion',
            function: 'createVersion'
        },
        data: {
            shortcutId: sb_id,
            formData: {
                version: version,
                notes: notes,
                url: url,
                minimumIos: minimumiOS,
                minimumMac: minimumMac,
                required: required,
                state: state
            },
            security: shortcutsHubData.security ? shortcutsHubData.security.create_version : 'nonce not found'
        },
        debug: true
    });

    // Validate nonce
    if (!shortcutsHubData.security || !shortcutsHubData.security.create_version) {
        console.error('Security nonce not found');
        jQuery('#version-feedback-message').text('Error: Security token not found').show();
        return;
    }

    // Validate required fields
    if (!version || !url) {
        jQuery('#version-feedback-message').text('Version number and URL are required').show();
        return;
    }

    jQuery.ajax({
        url: shortcutsHubData.ajaxurl,
        type: 'POST',
        data: {
            action: 'create_version',
            security: shortcutsHubData.security.create_version,
            id: sb_id,
            version: version,
            notes: notes,
            url: url,
            minimum_ios: minimumiOS,
            minimum_mac: minimumMac,
            required: required,
            version_state: state
        },
        success: function(response) {
            // Debug log the API response
            sh_debug_log('Version creation response', {
                message: 'Received response from create version AJAX call',
                source: {
                    file: 'version-create.js',
                    line: 'createVersion.success',
                    function: 'createVersion.success'
                },
                data: {
                    response: response,
                    shortcutId: sb_id
                },
                debug: true
            });

            if (response.success) {
                jQuery('#version-feedback-message').text('Version created successfully.').show();
                setTimeout(function() {
                    closeVersionModal();
                    if (typeof window.fetchVersions === 'function') {
                        fetchVersions(sb_id);
                    }
                }, 2000);
            } else {
                jQuery('#version-feedback-message').text('Error creating version: ' + (response.data ? response.data.message : 'Unknown error')).show();
            }
        },
        error: function(xhr, status, error) {
            // Debug log the error
            sh_debug_log('Version creation error', {
                message: 'Error in create version AJAX call',
                source: {
                    file: 'version-create.js',
                    line: 'createVersion.error',
                    function: 'createVersion.error'
                },
                data: {
                    xhr: xhr.responseText,
                    status: status,
                    error: error
                },
                debug: true
            });

            console.error('AJAX error:', status, error);
            console.error('Response Text:', xhr.responseText);
            jQuery('#version-feedback-message').text('Error creating version. Please try again.').show();
        }
    });
}

function closeVersionModal() {
    jQuery('#add-version-modal').removeClass('active');
    jQuery('body').removeClass('modal-open');
}
