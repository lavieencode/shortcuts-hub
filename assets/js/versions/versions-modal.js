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
        
        sh_debug_log('Update Button Clicked', {
            message: 'User clicked update button',
            source: {
                file: 'versions-modal.js',
                line: 'updateButtonClick',
                function: 'updateButtonClick'
            },
            data: {
                button: this,
                modal_state: {
                    is_active: jQuery('#edit-version-modal').hasClass('active'),
                    is_visible: jQuery('#edit-version-modal').is(':visible')
                }
            },
            debug: true
        });
        
        // Get form values
        const versionId = jQuery('#edit-version-id').val();
        const stateValue = jQuery('#version_state').val();
        const requiredValue = jQuery('#version-required').val();
        
        // DEBUG: Log form values
        sh_debug_log('Form Values', {
            message: 'Retrieved form values before update',
            source: {
                file: 'versions-modal.js',
                line: 'updateButtonClick',
                function: 'updateButtonClick'
            },
            data: {
                version_id: versionId,
                state_value: stateValue,
                required_value: requiredValue,
                form_elements: {
                    version_id_field: jQuery('#edit-version-id').length,
                    version_id_value: jQuery('#edit-version-id').val(),
                    id_field: jQuery('#id').val(),
                    form_serialized: jQuery('#edit-version-form').serializeArray()
                }
            },
            debug: true
        });

        if (!versionId) {
            sh_debug_log('Update Error', {
                message: 'No version ID found in form',
                source: {
                    file: 'versions-modal.js',
                    line: 'updateButtonClick',
                    function: 'updateButtonClick'
                },
                data: {
                    form_elements: {
                        id_field: {
                            exists: jQuery('#id').length > 0,
                            value: jQuery('#id').val()
                        },
                        version_id_field: {
                            exists: jQuery('#edit-version-id').length > 0,
                            value: jQuery('#edit-version-id').val()
                        },
                        form_serialized: jQuery('#edit-version-form').serializeArray()
                    }
                },
                debug: true
            });
            console.error('No version ID found in form');
            return;
        }

        // Convert values to proper types
        const versionData = {
            version: jQuery('#version-display').text().trim(),
            notes: jQuery('#version-notes').val().trim(),
            url: jQuery('#version-url').val().trim(),
            minimumiOS: jQuery('#version-ios').val().trim(),
            minimumMac: jQuery('#version-mac').val().trim(),
            required: requiredValue === 'true',  // Convert to actual boolean
            state: parseInt(stateValue, 10) || 0,  // Convert to integer, default to 0
            deleted: jQuery('#version_deleted').val() === 'true'  // Convert to boolean
        };

        sh_debug_log('Processed Version Data', {
            message: 'Version data after type conversion',
            source: {
                file: 'versions-modal.js',
                line: 'updateButtonClick',
                function: 'updateButtonClick'
            },
            data: {
                version_id: versionId,
                version_data: versionData,
                raw_values: {
                    state: stateValue,
                    required: requiredValue
                },
                converted_values: {
                    state: versionData.state,
                    required: versionData.required
                }
            },
            debug: true
        });

        // Call the update function with version ID
        updateVersion('save', versionData, versionId)
            .then(function(response) {
                sh_debug_log('Version Update Response', {
                    message: 'Received response from version update',
                    source: {
                        file: 'versions-modal.js',
                        line: 'updateButtonClick',
                        function: 'updateButtonClick'
                    },
                    data: {
                        response: response,
                        sent_data: {
                            version_data: versionData,
                            version_id: versionId
                        }
                    },
                    debug: true
                });

                if (response.success) {
                    // Close the modal
                    jQuery('#edit-version-modal').removeClass('active');
                    jQuery('body').removeClass('modal-open');
                    
                    // Refresh the versions list
                    if (typeof fetchVersions === 'function') {
                        fetchVersions();
                    }
                }
            })
            .catch(function(error) {
                sh_debug_log('Version Update Error', {
                    message: 'Error updating version',
                    source: {
                        file: 'versions-modal.js',
                        line: 'updateButtonClick',
                        function: 'updateButtonClick'
                    },
                    data: {
                        error: error,
                        sent_data: {
                            version_data: versionData,
                            version_id: versionId
                        }
                    },
                    debug: true
                });
                
                console.error('Error updating version:', error);
            });
    });

    // State Button
    jQuery('#edit-version-modal .state-button').on('click', function(event) {
        event.preventDefault();
        const button = jQuery(this);
        button.text('Switching...').prop('disabled', true);

        // DEBUG: Log state button click
        sh_debug_log('Version State Button Clicked', {
            message: 'User clicked state toggle button in edit version modal',
            source: {
                file: 'versions-modal.js',
                line: 'stateButtonClick',
                function: 'stateButtonClick'
            },
            data: {
                current_state: jQuery('#version_state').val()
            },
            debug: true
        });

        const currentState = jQuery('#version_state').val();
        const action = parseInt(currentState, 10) === 1 ? 'publish' : 'switch_to_draft';

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
    sh_debug_log('Opening Version Edit Modal', {
        message: 'Starting to fetch version data',
        source: {
            file: 'versions-modal.js',
            line: 'handleVersionEditModal',
            function: 'handleVersionEditModal'
        },
        data: {
            shortcut_id: id,
            version_id: versionId,
            latest: latest
        },
        debug: true
    });

    jQuery.ajax({
        url: shortcutsHubData.ajaxurl,
        method: 'POST',
        data: {
            action: 'fetch_version',
            security: shortcutsHubData.security.fetch_version,
            id: id,
            version_id: versionId,
            latest: latest
        },
        success: function(response) {
            sh_debug_log('Version Data Fetched', {
                message: 'Successfully fetched version data',
                source: {
                    file: 'versions-modal.js',
                    line: 'handleVersionEditModal',
                    function: 'handleVersionEditModal'
                },
                data: {
                    response: response,
                    version_id: versionId
                },
                debug: true
            });

            if (response.success && response.data) {
                populateVersionEditModal(response.data).then(() => {
                    const version = response.data.version;
                    
                    // Set the hidden fields with version ID, state and deleted status
                    jQuery('#edit-version-id').val(version.id);  // Set version ID from response

                    sh_debug_log('Version ID Set', {
                        message: 'Setting version ID in form',
                        source: {
                            file: 'versions-modal.js',
                            line: 'handleVersionEditModal',
                            function: 'handleVersionEditModal'
                        },
                        data: {
                            version_id_from_response: version.id,
                            version_id_in_form: jQuery('#edit-version-id').val(),
                            form_field_exists: jQuery('#edit-version-id').length > 0
                        },
                        debug: true
                    });

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
        // DEBUG: Log modal population
        sh_debug_log('Populating Version Edit Modal', {
            message: 'Setting form values in version edit modal',
            source: {
                file: 'versions-modal.js',
                line: 'populateVersionEditModal',
                function: 'populateVersionEditModal'
            },
            data: {
                version_data: data
            },
            debug: true
        });

        const version = data.version;
        if (!version) {
            console.error('No version data available');
            return;
        }

        // DEBUG: Log raw version data
        sh_debug_log('Raw Version Data', {
            message: 'Raw version data before form population',
            source: {
                file: 'versions-modal.js',
                line: 'populateVersionEditModal',
                function: 'populateVersionEditModal'
            },
            data: {
                version: version,
                version_id: version.id,
                state: version.state,
                state_value: version.state ? version.state.value : null
            },
            debug: true
        });

        // Set form values
        jQuery('#id').val(version.id);  // Set the ID field
        jQuery('#edit-version-id').val(version.id);  // Set the version ID field
        jQuery('#version-display').text(version.version);
        jQuery('#version-notes').val(version.notes);
        jQuery('#version-url').val(version.url);
        jQuery('#version-ios').val(version.minimumiOS);
        jQuery('#version-mac').val(version.minimumMac);
        jQuery('#version-required').val(version.required.toString());
        jQuery('#version_state').val(version.state && version.state.value !== undefined ? version.state.value : '1');  // Default to draft if undefined
        jQuery('#version_deleted').val(version.deleted ? 'true' : 'false');

        // DEBUG: Log populated form values
        sh_debug_log('Populated Form Values', {
            message: 'Form values after population',
            source: {
                file: 'versions-modal.js',
                line: 'populateVersionEditModal',
                function: 'populateVersionEditModal'
            },
            data: {
                id_field: {
                    exists: jQuery('#id').length > 0,
                    value: jQuery('#id').val()
                },
                version_id_field: {
                    exists: jQuery('#edit-version-id').length > 0,
                    value: jQuery('#edit-version-id').val()
                },
                version_display: jQuery('#version-display').text(),
                version_notes: jQuery('#version-notes').val(),
                version_url: jQuery('#version-url').val(),
                version_ios: jQuery('#version-ios').val(),
                version_mac: jQuery('#version-mac').val(),
                version_required: jQuery('#version-required').val(),
                version_state: jQuery('#version_state').val(),
                version_deleted: jQuery('#version_deleted').val()
            },
            debug: true
        });

        // Show the modal
        jQuery('#edit-version-modal').addClass('active');
        jQuery('body').addClass('modal-open');

        resolve();
    });
}