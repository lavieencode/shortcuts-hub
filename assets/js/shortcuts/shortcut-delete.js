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

    if (permanent && !confirm('Are you sure you want to PERMANENTLY delete this shortcut? This action cannot be undone.')) {
        $button.text(originalText).prop('disabled', false);
        return;
    }

    jQuery.ajax({
        url: shortcutsHubData.ajax_url,
        type: 'POST',
        data: {
            action: 'delete_shortcut',
            security: shortcutsHubData.security,
            post_id: postId,
            sb_id: sbId,
            permanent: permanent,
            restore: restore
        },
        success: function(response) {
            console.log('Delete/Restore Response:', response);
            if (response.success) {
                console.log('Operation successful, updating UI...');
                if (permanent) {
                    console.log('Removing item permanently');
                    $shortcutItem.fadeOut(400, function() {
                        $shortcutItem.remove();
                        if (jQuery('.shortcut-item').length === 0) {
                            jQuery('#shortcuts-container').html('<p>No shortcuts found.</p>');
                        }
                    });
                } else if (restore) {
                    console.log('Restoring item, updating badges');
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
                    console.log('Current badges:', $badgesContainer.html());
                    $badgesContainer.find('.deleted, .published').remove();
                    if (!$badgesContainer.find('.draft').length) {
                        $badgesContainer.append('<span class="badge draft">Draft</span>');
                    }
                    console.log('Updated badges:', $badgesContainer.html());
                } else {
                    console.log('Moving to trash, updating badges');
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
                    console.log('Current badges:', $badgesContainer.html());
                    $badgesContainer.find('.draft, .published').remove();
                    if (!$badgesContainer.find('.deleted').length) {
                        $badgesContainer.append('<span class="badge deleted">Deleted</span>');
                    }
                    console.log('Updated badges:', $badgesContainer.html());
                }
            } else {
                // Show error and reset button
                console.error('Operation failed:', response.data);
                alert(response.data.message || 'Error updating shortcut status');
                $button.text(originalText).prop('disabled', false);
            }
        },
        error: function(xhr, status, error) {
            console.error('AJAX error:', {xhr, status, error});
            alert('Error updating shortcut status. Please try again later.');
            $button.text(originalText).prop('disabled', false);
        }
    });
}