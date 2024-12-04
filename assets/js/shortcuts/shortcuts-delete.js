function toggleTrash(post_id, sb_id, isRestore, buttonElement) {
    const loadingText = isRestore ? 'Restoring...' : 'Moving to Trash...';
    jQuery(buttonElement).text(loadingText).prop('disabled', true);

    jQuery.ajax({
        url: shortcutsHubData.ajax_url,
        type: 'POST',
        data: {
            action: 'toggle_delete',
            security: shortcutsHubData.security,
            id: post_id,
            sb_id: sb_id,
            deleted: !isRestore // true when moving to trash, false when restoring
        },
        success: function(response) {
            if (response.success) {
                // Update the UI to reflect the new state
                const $shortcutItem = jQuery(buttonElement).closest('.shortcut-item');
                const newButtonText = !isRestore ? 'Restore' : 'Move to Trash';
                const newButtonClass = !isRestore ? 'restore-button' : 'trash-button';
                
                // Update button class and text
                jQuery(buttonElement)
                    .removeClass('restore-button trash-button')
                    .addClass(newButtonClass)
                    .text(newButtonText);

                // Update the deleted badge
                const $badgesContainer = $shortcutItem.find('.badges-container');
                if (!isRestore) {
                    if (!$badgesContainer.find('.deleted-badge').length) {
                        $badgesContainer.append('<span class="badge deleted-badge">Deleted</span>');
                    }
                } else {
                    $badgesContainer.find('.deleted-badge').remove();
                }

                // Refresh the shortcuts list to ensure everything is in sync
                fetchShortcuts();
            } else {
                alert('Error: ' + response.data.message);
            }
        },
        error: function(xhr, status, error) {
            console.error('AJAX error:', error);
            alert('Error updating shortcut status. Please try again later.');
        },
        complete: function() {
            jQuery(buttonElement).prop('disabled', false);
        }
    });
}

function deleteShortcut(postId, sbId, buttonElement) {
    if (buttonElement) {
        jQuery(buttonElement).prop('disabled', true);
    }

    // Confirm permanent deletion
    if (!confirm('Are you sure you want to PERMANENTLY delete this shortcut? This action cannot be undone and will remove the shortcut from both WordPress and Switchblade.')) {
        if (buttonElement) {
            jQuery(buttonElement).prop('disabled', false);
        }
        return;
    }

    jQuery.ajax({
        url: shortcutsHubData.ajax_url,
        type: 'POST',
        data: {
            action: 'delete_shortcut',
            security: shortcutsHubData.security,
            post_id: postId,
            sb_id: sbId
        },
        success: function(response) {
            if (response.success) {
                // Find and remove the shortcut item from DOM
                const $shortcutItem = jQuery(buttonElement).closest('.shortcut-item');
                $shortcutItem.fadeOut(400, function() {
                    $shortcutItem.remove();
                    // Check if there are no more shortcuts
                    if (jQuery('.shortcut-item').length === 0) {
                        jQuery('#shortcuts-container').html('<p>No shortcuts found.</p>');
                    }
                });
            } else {
                if (response.data.partial_success) {
                    alert('Partial deletion occurred: ' + response.data.message);
                    // Remove the item even on partial success since the WordPress post was deleted
                    const $shortcutItem = jQuery(buttonElement).closest('.shortcut-item');
                    $shortcutItem.fadeOut(400, function() {
                        $shortcutItem.remove();
                        // Check if there are no more shortcuts
                        if (jQuery('.shortcut-item').length === 0) {
                            jQuery('#shortcuts-container').html('<p>No shortcuts found.</p>');
                        }
                    });
                } else {
                    alert('Failed to delete shortcut: ' + response.data.message);
                }
            }
        },
        error: function(xhr, status, error) {
            console.error('Error deleting shortcut:', xhr.responseText);
            alert('Failed to delete shortcut. Please try again later.');
        },
        complete: function() {
            if (buttonElement) {
                jQuery(buttonElement).prop('disabled', false);
            }
        }
    });
}