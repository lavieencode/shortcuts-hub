// Delete and restore button click handler
jQuery('#versions-view').on('click', '.delete-button, .restore-button, .delete-permanent-button', function() {
    const $button = jQuery(this);
    const shortcutId = $button.data('shortcut-id');
    const versionId = $button.data('version-id');
    const isRestore = $button.hasClass('restore-button');
    const isPermanent = $button.hasClass('delete-permanent-button');
    
    // Validate required data
    if (!shortcutId || !versionId) {
        if (typeof window.sh_debug_log === 'function') {
            window.sh_debug_log('Version delete/restore validation failed', 
                {
                    debug: true,
                    message: 'Missing required data for version operation',
                    shortcut_id: shortcutId,
                    version_id: versionId,
                    error: 'Missing required data',
                    button_data: {
                        from_button: {
                            shortcut_id: $button.data('shortcut-id'),
                            version_id: $button.data('version-id')
                        }
                    }
                },
                {
                    file: 'versions-delete.js',
                    line: '9',
                    function: 'buttonClickHandler'
                }
            );
        }
        return;
    }
    
    // For permanent deletion, show confirmation dialog
    if (isPermanent) {
        if (!confirm('Are you sure you want to permanently delete this version? This action cannot be undone!')) {
            return;
        }
    }
    
    // DEBUG: Log version delete/restore button click with associated data
    if (typeof window.sh_debug_log === 'function') {
        window.sh_debug_log('User clicked version delete/restore button', 
            {
                debug: true,
                message: 'User initiated version operation',
                shortcut_id: shortcutId,
                version_id: versionId,
                is_restore: isRestore,
                is_permanent: isPermanent,
                button_classes: $button.attr('class'),
                button_data: {
                    shortcut_id: shortcutId,
                    version_id: versionId
                }
            },
            {
                file: 'versions-delete.js',
                line: '28',
                function: 'buttonClickHandler'
            }
        );
    }

    deleteVersion(shortcutId, versionId, { 
        isRestore: isRestore,
        deleteType: isPermanent ? 'permanent' : 'toggle'
    });
});

function deleteVersion(shortcutId, versionId, options = {}) {
    const defaultOptions = {
        deleteType: 'toggle',  // 'toggle' or 'permanent'
        source: 'switchblade', // 'switchblade' or 'wp'
        isRestore: false      // Only used for toggle delete type
    };

    const settings = { ...defaultOptions, ...options };

    // DEBUG: Log version delete request details
    if (typeof window.sh_debug_log === 'function') {
        window.sh_debug_log('Sending version delete/restore request',
            {
                request: {
                    shortcut_id: shortcutId,
                    version_id: versionId,
                    options: settings
                },
                shortcutsHubData: {
                    available: typeof shortcutsHubData !== 'undefined',
                    security: typeof shortcutsHubData !== 'undefined' ? !!shortcutsHubData.security : false,
                    delete_version: typeof shortcutsHubData !== 'undefined' && shortcutsHubData.security ? !!shortcutsHubData.security.delete_version : false
                }
            },
            {
                file: 'versions-delete.js',
                line: 'deleteVersion',
                function: 'deleteVersion'
            }
        );
    }

    const data = {
        action: 'delete_version',
        security: shortcutsHubData.security.delete_version,
        shortcut_id: shortcutId,
        version_id: versionId,
        delete_type: settings.deleteType,
        source: settings.source,
        is_restore: settings.isRestore
    };

    jQuery.ajax({
        url: shortcutsHubData.ajaxurl,
        method: 'POST',
        data: data,
        success: function(response) {
            // DEBUG: Log AJAX response
            if (typeof window.sh_debug_log === 'function') {
                const errorMessage = response.data ? response.data.message : 'Unknown error';
                window.sh_debug_log(
                    response.success ? 'Version delete/restore successful' : 'Version delete/restore failed: ' + errorMessage,
                    {
                        response: response
                    },
                    {
                        file: 'versions-delete.js',
                        line: 'deleteVersion.success',
                        function: 'deleteVersion'
                    }
                );
            }

            if (response.success) {
                // Refresh the versions list after successful operation
                fetchVersions(shortcutId);
            } else {
                // DEBUG: Log error response
                if (typeof window.sh_debug_log === 'function') {
                    window.sh_debug_log('Version Delete Error', {
                        message: 'Error response from server',
                        source: {
                            file: 'versions-delete.js',
                            line: 'deleteVersion.success',
                            function: 'deleteVersion'
                        },
                        data: {
                            response: response
                        },
                        debug: true
                    });
                }
            }
        },
        error: function(xhr, status, error) {
            // DEBUG: Log AJAX error
            if (typeof window.sh_debug_log === 'function') {
                window.sh_debug_log('Version Delete Error', {
                    message: 'AJAX error during version delete/restore: ' + error,
                    source: {
                        file: 'versions-delete.js',
                        line: 'deleteVersion.error',
                        function: 'deleteVersion'
                    },
                    data: {
                        request: data,
                        error: {
                            status: status,
                            error: error,
                            response: xhr.responseText,
                            parsed_response: tryParseJSON(xhr.responseText)
                        }
                    },
                    debug: true
                });
            }
        }
    });
}

// Helper function to safely parse JSON
function tryParseJSON(str) {
    try {
        return JSON.parse(str);
    } catch (e) {
        return str;
    }
}

// Example usage:
// Toggle delete (soft delete):
// deleteVersion(shortcutId, versionId);
// 
// Permanent delete from Switchblade:
// deleteVersion(shortcutId, versionId, { deleteType: 'permanent' });
//
// Restore a deleted version:
// deleteVersion(shortcutId, versionId, { isRestore: true });
//
// WordPress version operations:
// deleteVersion(shortcutId, versionId, { source: 'wp', deleteType: 'permanent' });
// deleteVersion(shortcutId, versionId, { source: 'wp', isRestore: true });