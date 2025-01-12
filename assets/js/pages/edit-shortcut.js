jQuery(document).ready(function(jQuery) {
    // Wait for IconSelector to be available
    if (typeof IconSelector === 'undefined') {
        return;
    }

    // Initialize icon selector
    const iconSelector = new IconSelector({
        container: document.getElementById('icon-selector-content'),
        inputField: document.getElementById('shortcut-icon'),
        previewContainer: document.querySelector('.icon-preview'),
        onChange: function(iconData) {
            jQuery('#shortcut-icon').val(JSON.stringify(iconData));
        }
    });

    // Store the IconSelector instance globally for access in other functions
    window.shortcutIconSelector = iconSelector;

    // Initialize color picker
    jQuery('#shortcut-color').wpColorPicker({
        change: function(event, ui) {
            jQuery(this).val(ui.color.toString());
        }
    });

    // Prevent default form submission
    jQuery('#edit-shortcut-form').on('submit', function(event) {
        event.preventDefault();
        return false;
    });

    // Handle state button click (publish/revert to draft)
    jQuery('#publish-shortcut').on('click', function(event) {
        event.preventDefault();
        const $button = jQuery(this);
        const currentStatus = jQuery('#shortcut-post-id').data('post-status');
        

        // Validate current status
        if (!currentStatus || (currentStatus !== 'draft' && currentStatus !== 'publish')) {
            return;
        }

        const newState = currentStatus === 'draft' ? 'publish' : 'draft';
        
        const formData = {
            post_id: jQuery('#shortcut-post-id').data('post-id') || jQuery('#shortcut-post-id').val(),
            sb_id: jQuery('#shortcut-id').data('sb-id') || jQuery('#shortcut-id').val(),
            name: jQuery('#shortcut-name').val(),
            headline: jQuery('#shortcut-headline').val(),
            description: jQuery('#shortcut-description').val(),
            website: jQuery('#shortcut-permalink').val(),
            color: jQuery('#shortcut-color').val(),
            icon: jQuery('#shortcut-icon').val(),
            input: jQuery('#shortcut-input').val(),
            result: jQuery('#shortcut-result').val(),
            state: newState
        };

        // Update both WordPress and Switchblade in a single call
        updateShortcut(formData, { buttonElement: $button[0] })
            .then((response) => {
                // Update UI elements
                const newButtonText = newState === 'draft' ? 'Publish' : 'Revert to Draft';
                
                // Only update the button text, don't change classes
                $button.text(newButtonText);
                
                // Update post status data attribute
                jQuery('#shortcut-post-id').data('post-status', newState);
                
                // Show success message
                jQuery('#feedback-message')
                    .removeClass('error')
                    .addClass('success')
                    .text('Shortcut status updated successfully')
                    .show();
                
                setTimeout(() => {
                    jQuery('#feedback-message').hide();
                }, 3000);
            });

    });

    // Save/Update button click handler
    jQuery('#save-draft').on('click', function(e) {
        e.preventDefault();
        const currentStatus = jQuery('#shortcut-post-id').data('post-status');
        
        const formData = {
            post_id: jQuery('#shortcut-post-id').data('post-id') || jQuery('#shortcut-post-id').val(),
            sb_id: jQuery('#shortcut-id').data('sb-id') || jQuery('#shortcut-id').val(),
            name: jQuery('#shortcut-name').val(),
            headline: jQuery('#shortcut-headline').val(),
            description: jQuery('#shortcut-description').val(),
            website: jQuery('#shortcut-permalink').val(),
            color: jQuery('#shortcut-color').val(),
            icon: jQuery('#shortcut-icon').val(),
            input: jQuery('#shortcut-input').val(),
            result: jQuery('#shortcut-result').val(),
            state: jQuery('#shortcut-post-id').data('post-status')
        };

        // Update both WordPress and Switchblade in a single call
        updateShortcut(formData, { buttonElement: this })
            .then(() => {
                // Show success message
                jQuery('#feedback-message')
                    .removeClass('error')
                    .addClass('success')
                    .text('Shortcut updated successfully')
                    .show();

                setTimeout(() => {
                    jQuery('#feedback-message').hide();
                }, 3000);

                // Re-enable the button and update text
                jQuery(this).prop('disabled', false).text('Save Changes');
            })
            .catch((error) => {
                // Show error message
                jQuery('#feedback-message')
                    .removeClass('success')
                    .addClass('error')
                    .text('Error updating shortcut: ' + error.message)
                    .show();

                // Re-enable the button
                jQuery(this).prop('disabled', false).text('Save Changes');
            });
    });

    // Function to load shortcut fields from API
    function loadShortcutFields(id) {
        const data = {
            action: 'fetch_shortcut',
            security: shortcutsHubData.security,
            post_id: id,
            source: 'WP'
        };

        jQuery.ajax({
            url: shortcutsHubData.ajax_url,
            type: 'POST',
            data: data,
            success: function(response) {
                if (response.success && response.data) {
                    const data = response.data;
                    const sb = data.switchblade;
                    const wp = data.wordpress;
                    
                    // Store these IDs in data attributes so they persist
                    const $postId = jQuery('#shortcut-post-id');
                    $postId.val(data.ID).data('post-id', data.ID);
                    jQuery('#shortcut-id').val(sb.sb_id).data('sb-id', sb.sb_id);
                    
                    // WordPress fields
                    jQuery('#shortcut-name').val(wp.name);
                    jQuery('#shortcut-description').val(wp.description);
                    jQuery('#shortcut-color').val(wp.color);
                    jQuery('#shortcut-icon').val(wp.icon);
                    jQuery('#shortcut-input').val(wp.input);
                    jQuery('#shortcut-result').val(wp.result);

                    // Switchblade fields
                    jQuery('#shortcut-headline').val(sb.headline);
                    jQuery('#shortcut-website').val(sb.website);
                    
                    jQuery('#publish-shortcut').text(sb.state === 0 ? 'Revert to Draft' : 'Publish');
                }
            },
            error: function(xhr, status, error) {
                jQuery('#feedback-message')
                    .removeClass('success')
                    .addClass('error')
                    .text('Error fetching shortcut data')
                    .show();
            }
        });
    }

    // Delete dropdown toggle functionality
    jQuery('.delete-dropdown-toggle').on('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        jQuery(this).next('.delete-dropdown-content').toggle();
    });

    // Close dropdown when clicking outside
    jQuery(document).on('click', function(e) {
        if (!jQuery(e.target).closest('.delete-dropdown').length) {
            jQuery('.delete-dropdown-content').hide();
        }
    });

    // Handle delete shortcut
    jQuery('#delete-shortcut').on('click', function(e) {
        e.preventDefault();
        
        if (!confirm('Are you sure you want to delete this shortcut? This action cannot be undone.')) {
            return;
        }

        const postId = jQuery('#shortcut-post-id').val();
        
        jQuery.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'delete_shortcut',
                security: jQuery('#shortcuts_hub_nonce').val(),
                post_id: postId
            },
            success: function(response) {
                if (response.success) {
                    window.location.href = response.data.redirect;
                } else {
                    jQuery('#feedback-message')
                        .removeClass('success')
                        .addClass('error')
                        .text(response.data.message || 'Error deleting shortcut')
                        .show();
                }
            },
            error: function() {
                jQuery('#feedback-message')
                    .removeClass('success')
                    .addClass('error')
                    .text('Network error while deleting shortcut')
                    .show();
            }
        });
    });

    // Load shortcut data if we have an ID
    const postId = jQuery('#shortcut-post-id').val();
    if (postId) {
        loadShortcutFields(postId);

        // Also fetch from Switchblade API for comparison
        const shortcutId = jQuery('#shortcut-id').val();
        jQuery.ajax({
            url: shortcutsHubData.ajax_url,
            method: 'POST',
            data: {
                action: 'fetch_shortcuts',
                security: shortcutsHubData.security,
                filter: '',
                source: 'SB',
                id: shortcutId
            },
            success: function(response) {
                if (response && response.data && response.data.shortcuts) {
                    const shortcut = response.data.shortcuts[0];
                    if (shortcut) {
                        // Update Switchblade-specific fields
                        jQuery('#shortcut-id').val(shortcut.id);
                        
                        // Store the Switchblade state and deleted status
                        jQuery('#shortcut-post-id').data('sb-state', shortcut.state);
                        jQuery('#shortcut-post-id').data('sb-deleted', shortcut.deleted);
                    }
                }
            },
            error: function(xhr, status, error) {
                jQuery('#feedback-message')
                    .removeClass('success')
                    .addClass('error')
                    .text('Error fetching shortcut data')
                    .show();
            }
        });
    }

    function updateShortcut(formData, options = {}) {
        const { buttonElement = null } = options;

        if (buttonElement) {
            jQuery(buttonElement).prop('disabled', true).text('Saving...');
        }

        return new Promise((resolve, reject) => {
            jQuery.ajax({
                url: shortcutsHubData.ajax_url,
                type: 'POST',
                data: {
                    action: 'update_shortcut',
                    security: shortcutsHubData.security,
                    shortcut_data: formData,
                    update_type: 'wordpress_and_switchblade'
                },
                success: function(response) {
                    if (response.success) {
                        resolve(response);
                    } else {
                        reject(new Error(response.data.message || 'Unknown error occurred'));
                    }
                },
                error: function(xhr, status, error) {
                    reject(new Error(error));
                }
            });
        });
    }
});
