jQuery(document).ready(function(jQuery) {
    // Wait for IconSelector to be available
    if (typeof IconSelector === 'undefined') {
        console.error('IconSelector not loaded yet. Make sure icon-selector.js is loaded before edit-shortcut.js');
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
        
        // Debug: Log button state for testing to verify current status and button text
        console.log('Current button state:', {
            currentStatus: currentStatus,
            buttonText: $button.text(),
            hasPublishClass: $button.hasClass('publish-button'),
            hasRevertClass: $button.hasClass('revert-button')
        });

        // Validate current status
        if (!currentStatus || (currentStatus !== 'draft' && currentStatus !== 'publish')) {
            return;
        }

        const newState = currentStatus === 'draft' ? 'publish' : 'draft';
        
        const formData = {
            post_id: jQuery('#shortcut-post-id').val(),
            sb_id: jQuery('#shortcut-id').val(),
            name: jQuery('#shortcut-name').val(),
            headline: jQuery('#shortcut-headline').val(),
            description: jQuery('#shortcut-description').val(),
            website: jQuery('#shortcut-website').val(),
            color: jQuery('#shortcut-color').val(),
            icon: jQuery('#shortcut-icon').val(),
            input: jQuery('#shortcut-input').val(),
            result: jQuery('#shortcut-result').val(),
            state: newState
        };

        // Update both WordPress and Switchblade
        updateShortcut(formData, { target: 'wp', buttonElement: $button[0] })
            .then(() => {
                return updateShortcut(formData, { target: 'sb', buttonElement: $button[0] });
            })
            .then(() => {
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
        
        console.log('Save button clicked:', {
            currentStatus: currentStatus,
            buttonText: jQuery(this).text()
        });

        const formData = {
            post_id: jQuery('#shortcut-post-id').val(),
            sb_id: jQuery('#shortcut-id').val(),
            name: jQuery('#shortcut-name').val(),
            headline: jQuery('#shortcut-headline').val(),
            description: jQuery('#shortcut-description').val(),
            website: jQuery('#shortcut-website').val(),
            color: jQuery('#shortcut-color').val(),
            icon: jQuery('#shortcut-icon').val(),
            input: jQuery('#shortcut-input').val(),
            result: jQuery('#shortcut-result').val(),
            state: jQuery('#shortcut-post-id').data('post-status')
        };

        // Update both WordPress and Switchblade
        updateShortcut(formData, { target: 'wp', buttonElement: this })
            .then(() => {
                return updateShortcut(formData, { target: 'sb', buttonElement: this });
            })
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
        jQuery.ajax({
            url: shortcutsHubData.ajax_url,
            method: 'POST',
            data: {
                action: 'fetch_shortcut',
                security: shortcutsHubData.security,
                post_id: id,
                source: 'WP'
            },
            beforeSend: function(xhr, settings) {
                // Debug: Log the API request to verify proper URL formation, method, and data structure
                sh_debug_log(
                    'Making WordPress API request to fetch shortcut',
                    {
                        url: settings.url,
                        method: settings.type,
                        data: settings.data
                    }
                );
            },
            success: function(response) {
                // Debug: Verify API response structure and data format for proper field population
                sh_debug_log(
                    'Received WordPress API response',
                    {
                        success: response.success,
                        data: response
                    }
                );

                if (!response.success) {
                    jQuery('#feedback-message')
                        .removeClass('success')
                        .addClass('error')
                        .text(response.data ? response.data.message : 'Unknown error occurred')
                        .show();
                    return;
                }

                const data = response.data;
                const isPublished = data.state === 0;

                // Fill in form fields
                jQuery('#shortcut-id').val(data.id);
                jQuery('#shortcut-name').val(data.name);
                jQuery('#shortcut-headline').val(data.headline);
                jQuery('#shortcut-description').val(data.description);
                jQuery('#shortcut-website').val(data.website);
                jQuery('#shortcut-color').val(data.color);
                jQuery('#shortcut-icon').val(data.icon);
                jQuery('#shortcut-input').val(data.input);
                jQuery('#shortcut-result').val(data.result);

                // Update button text
                jQuery('#publish-shortcut').text(isPublished ? 'Revert to Draft' : 'Publish');
                
                // Update status data attribute to match WordPress format
                jQuery('#shortcut-post-id').data('post-status', isPublished ? 'publish' : 'draft');
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

    // Handle delete confirmation
    jQuery('.delete-confirm').on('click', function(e) {
        e.preventDefault();
        const postId = jQuery(this).data('post_id');
        const sbId = jQuery(this).data('sb_id');
        
        if (confirm('Are you sure you want to permanently delete this shortcut? This action cannot be undone.')) {
            jQuery.ajax({
                url: shortcutsHubData.ajax_url,
                method: 'POST',
                data: {
                    action: 'delete_shortcut',
                    post_id: postId,
                    sb_id: sbId,
                    permanent: true,
                    security: shortcutsHubData.security
                },
                success: function(response) {
                    if (response.success) {
                        window.location.href = response.data.redirect_url;
                    } else {
                        jQuery('#feedback-message')
                            .removeClass('success')
                            .addClass('error')
                            .text(response.data.message)
                            .show();
                    }
                }
            });
        }
    });

    // Load shortcut data if we have an ID
    const shortcutId = jQuery('#shortcut-id').val();
    if (shortcutId) {
        loadShortcutFields(shortcutId);

        // Also fetch from Switchblade API for comparison
        jQuery.ajax({
            url: `${shortcutsHubData.sb_api_url}/shortcuts/${shortcutId}`,
            method: 'GET',
            headers: {
                'Content-Type': 'application/json'
            },
            beforeSend: function(xhr, settings) {
                // Debug: Log the API request to verify proper URL formation, method, and headers
                sh_debug_log(
                    'Fetching shortcut data from Switchblade API',
                    {
                        url: settings.url,
                        method: settings.type,
                        headers: settings.headers
                    }
                );
            },
            success: function(response) {
                // Debug: Verify API response structure and data format for comparison
                sh_debug_log(
                    'Received shortcut data from Switchblade API',
                    {
                        success: true,
                        data: response
                    }
                );
            },
            error: function(xhr, status, error) {
                // Debug: Log error details for troubleshooting
                sh_debug_log(
                    'Error fetching shortcut data from Switchblade API',
                    {
                        status: xhr.status,
                        statusText: xhr.statusText,
                        error: error
                    }
                );
            }
        });
    }
});
