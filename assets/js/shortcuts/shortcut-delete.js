function deleteShortcut(postId, sbId, buttonElement, permanent = false, restore = false) {
    const $button = jQuery(buttonElement);
    const $shortcutItem = $button.closest('.shortcut-item');
    const $btnGroup = $shortcutItem.find('.btn-group');
    const $badgesContainer = $shortcutItem.find('.badge-container');
    
    // Store original button text for error recovery
    const originalText = $button.text();
    
    // Set loading state
    const loadingText = restore ? 'Restoring...' : (permanent ? 'Deleting Permanently...' : 'Moving to Trash...');
    $button.text(loadingText).prop('disabled', true);

    const requestData = {
        action: 'delete_shortcut',
        security: shortcutsHubData.security,
        post_id: postId,
        sb_id: sbId,
        permanent: permanent,
        restore: restore
    };

    jQuery.ajax({
        url: shortcutsHubData.ajax_url,
        type: 'POST',
        data: requestData,
        success: function(response) {
            // DEBUG: Log complete AJAX request and response details for shortcut deletion
            sh_debug_log('Shortcut Deletion AJAX Operation', {
                message: 'Complete AJAX request and response data for shortcut deletion',
                source: {
                    file: 'shortcut-delete.js',
                    line: 'deleteShortcut',
                    function: 'deleteShortcut.ajaxSuccess'
                },
                data: {
                    request: {
                        url: shortcutsHubData.ajax_url,
                        method: 'POST',
                        data: requestData,
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    },
                    response: {
                        raw: response,
                        success: response.success,
                        data: response.data,
                        status: 'success',
                        statusCode: 200
                    },
                    shortcut: {
                        post_id: postId,
                        sb_id: sbId,
                        operation: {
                            permanent: permanent,
                            restore: restore,
                            type: permanent ? 'permanent_delete' : (restore ? 'restore' : 'trash')
                        }
                    },
                    dom: {
                        buttonElement: buttonElement.outerHTML,
                        shortcutItem: $shortcutItem[0].outerHTML,
                        buttonGroup: $btnGroup[0].outerHTML
                    }
                },
                debug: true
            });

            // DEBUG: Log request and response details
            sh_debug_log('Shortcut Deletion Operation', {
                message: 'Processing shortcut deletion operation',
                source: {
                    file: 'shortcut-delete.js',
                    line: 'deleteShortcut',
                    function: 'deleteShortcut'
                },
                data: {
                    request: requestData,
                    response: response,
                    shortcut_element: {
                        post_id: postId,
                        sb_id: sbId,
                        permanent: permanent,
                        restore: restore
                    }
                },
                debug: true
            });

            if (response.success) {
                if (permanent) {
                    $shortcutItem.fadeOut(400, function() {
                        $shortcutItem.remove();
                        if (jQuery('.shortcut-item').length === 0) {
                            jQuery('#shortcuts-container').html('<p>No shortcuts found.</p>');
                        }
                    });
                } else if (restore) {
                    // Update buttons for restored state
                    $btnGroup.html(`
                        <button class="delete-button" data-post_id="${postId}" data-sb_id="${sbId}">Delete</button>
                        <button class="delete-dropdown-toggle">
                            <span class="dropdown-caret">▼</span>
                        </button>
                        <div class="delete-dropdown-content">
                            <button class="delete-permanently" data-post_id="${postId}" data-sb_id="${sbId}">Delete Permanently</button>
                        </div>
                    `);
                    // Update badges for restored state
                    $badgesContainer.find('.deleted, .published').remove();
                    if (!$badgesContainer.find('.draft').length) {
                        $badgesContainer.append('<span class="badge draft">Draft</span>');
                    }
                } else {
                    // Update buttons for deleted state
                    $btnGroup.html(`
                        <button class="restore-button" data-post_id="${postId}" data-sb_id="${sbId}">Restore</button>
                        <button class="delete-dropdown-toggle">
                            <span class="dropdown-caret">▼</span>
                        </button>
                        <div class="delete-dropdown-content">
                            <button class="delete-permanently" data-post_id="${postId}" data-sb_id="${sbId}">Delete Permanently</button>
                        </div>
                    `);
                    // Update badges for deleted state
                    $badgesContainer.find('.draft, .published').remove();
                    if (!$badgesContainer.find('.deleted').length) {
                        $badgesContainer.append('<span class="badge deleted">Deleted</span>');
                    }
                }
            } else {
                alert(response.data.message || 'Error updating shortcut status');
                $button.text(originalText).prop('disabled', false);
            }
        },
        error: function(xhr, status, error) {
            // DEBUG: Log AJAX error with request details
            sh_debug_log('Shortcut Deletion Operation', {
                message: 'AJAX request failed',
                source: {
                    file: 'shortcut-delete.js',
                    line: 'deleteShortcut.ajaxError',
                    function: 'deleteShortcut'
                },
                data: {
                    request: requestData,
                    error: {
                        xhr: xhr,
                        status: status,
                        error: error
                    }
                },
                debug: true
            });

            alert('Error updating shortcut status. Please try again later.');
            $button.text(originalText).prop('disabled', false);
        }
    });
}