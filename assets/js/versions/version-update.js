/**
 * Update a version's details
 * @param {string} action - The action to perform (save, publish, switch_to_draft)
 * @param {Object} versionData - The version data to update
 * @param {string} versionId - The ID of the version to update
 * @returns {Promise} - Resolves with the response from the server
 */
function updateVersion(action, versionData, versionId) {
    return new Promise((resolve, reject) => {
        // Get the shortcut ID from the URL
        const urlParams = new URLSearchParams(window.location.search);
        const shortcutId = urlParams.get('id');

        if (!shortcutId) {
            sh_debug_log('Update Version Error', {
                message: 'No shortcut ID found in URL',
                source: {
                    file: 'version-update.js',
                    line: 'updateVersion',
                    function: 'updateVersion'
                },
                data: {
                    action: action,
                    version_data: versionData,
                    version_id: versionId
                },
                debug: true
            });
            reject(new Error('No shortcut ID found'));
            return;
        }

        if (!versionId) {
            sh_debug_log('Update Version Error', {
                message: 'Cannot update version without version ID',
                source: {
                    file: 'version-update.js',
                    line: 'updateVersion',
                    function: 'updateVersion'
                },
                data: {
                    action: action,
                    version_data: versionData,
                    version_id: versionId,
                    version_id_field: jQuery('#edit-version-id').length,
                    version_id_value: jQuery('#edit-version-id').val()
                },
                debug: true
            });
            reject(new Error('Version ID is required for updates'));
            return;
        }

        // Prepare the request data
        const requestData = {
            action: 'update_version',
            security: shortcutsHubData.security.update_version,
            id: shortcutId,
            version_id: versionId,
            version_data: versionData
        };

        sh_debug_log('Update Version Request', {
            message: 'Sending version update request',
            source: {
                file: 'version-update.js',
                line: 'updateVersion',
                function: 'updateVersion'
            },
            data: {
                request_data: requestData
            },
            debug: true
        });

        // Send the AJAX request
        jQuery.ajax({
            url: shortcutsHubData.ajaxurl,
            method: 'POST',
            data: requestData,
            success: function(response) {
                sh_debug_log('Update Version Success', {
                    message: 'Version update successful',
                    source: {
                        file: 'version-update.js',
                        line: 'updateVersion',
                        function: 'updateVersion'
                    },
                    data: {
                        response: response
                    },
                    debug: true
                });
                resolve(response);
            },
            error: function(xhr, status, error) {
                sh_debug_log('Update Version Error', {
                    message: 'Version update failed',
                    source: {
                        file: 'version-update.js',
                        line: 'updateVersion',
                        function: 'updateVersion'
                    },
                    data: {
                        xhr: xhr,
                        status: status,
                        error: error
                    },
                    debug: true
                });
                reject(error);
            }
        });
    });
}

jQuery(document).ready(function() {
});