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
            console.log('Icon changed:', iconData);
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

    // Handle publish button click
    jQuery('#publish-shortcut').on('click', function(event) {
        event.preventDefault();
        const $button = jQuery(this);
        $button.prop('disabled', true).text('Publishing...');
        
        // Get icon data from the input field
        let iconData = jQuery('#shortcut-icon').val();
        console.log('Raw icon data from input:', iconData);

        const shortcutData = {
            post_id: jQuery('#shortcut-post-id').val(), // Use the WordPress post ID
            sb_id: jQuery('#shortcut-id').val(), // This is the internal shortcut ID
            name: jQuery('#shortcut-name').val(),
            headline: jQuery('#shortcut-headline').val(),
            description: jQuery('#shortcut-description').val(),
            color: jQuery('#shortcut-color').val(),
            icon: iconData, // Send the raw JSON string directly
            input: jQuery('#shortcut-input').val(),
            result: jQuery('#shortcut-result').val(),
            state: 'publish'
        };

        console.log('Sending shortcut data:', shortcutData);

        jQuery.ajax({
            url: shortcutsHubData.ajax_url,
            method: 'POST',
            data: {
                action: 'update_shortcut',
                shortcut_data: shortcutData,
                security: shortcutsHubData.security
            },
            success: function(response) {
                console.log('Update response:', response);
                $button.prop('disabled', false).text('Publish');
                
                if (response.success) {
                    jQuery('#feedback-message').html('<div class="success-message">Shortcut saved successfully!</div>');
                    // Refresh the page after a short delay to show the success message
                    setTimeout(function() {
                        const urlParams = new URLSearchParams(window.location.search);
                        const currentPostId = urlParams.get('post_id');
                        
                        // If we have a post_id in the response and it's different from the current one,
                        // update the URL before reloading
                        if (response.data && response.data.post_id && response.data.post_id !== currentPostId) {
                            urlParams.set('post_id', response.data.post_id);
                            const newUrl = window.location.pathname + '?' + urlParams.toString();
                            window.history.replaceState({}, '', newUrl);
                        }
                        
                        window.location.reload();
                    }, 1000);
                } else {
                    const errorMessage = response.data?.message || 'Unknown error';
                    jQuery('#feedback-message').html('<div class="error-message">Error saving shortcut: ' + errorMessage + '</div>');
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX error:', status, error);
                $button.prop('disabled', false).text('Publish');
                jQuery('#feedback-message').html('<div class="error-message">Error saving shortcut. Please check the console for details.</div>');
            }
        });
    });

    // Load shortcut data if we have an ID
    const urlParams = new URLSearchParams(window.location.search);
    const id = urlParams.get('post_id');
    if (id) {
        loadShortcutFields(id);
    }
});

function loadShortcutFields(id) {
    console.log('Loading shortcut fields for ID:', id);
    jQuery.ajax({
        url: shortcutsHubData.ajax_url,
        method: 'POST',
        data: {
            action: 'fetch_shortcut',
            post_id: id,
            security: shortcutsHubData.security
        },
        success: function(response) {
            console.log('=== Shortcut Load Response ===');
            console.log('Full response:', response);
            if (response && response.success && response.data) {
                const shortcutData = response.data;
                console.log('Shortcut data received:', shortcutData);
                console.log('Current icon data:', shortcutData.icon);
                
                jQuery('#shortcut-name').val(shortcutData.name || '');
                jQuery('#shortcut-headline').val(shortcutData.headline || '');
                jQuery('#shortcut-description').val(shortcutData.description || '');
                jQuery('#shortcut-color').val(shortcutData.color || '');
                jQuery('#shortcut-input').val(shortcutData.input || '');
                jQuery('#shortcut-result').val(shortcutData.result || '');
                jQuery('#shortcut-post-id').val(shortcutData.post_id || ''); // Update the WordPress post ID field
                jQuery('#shortcut-id').val(shortcutData.sb_id || '');

                // Handle icon data
                if (shortcutData.icon) {
                    let iconData = shortcutData.icon;
                    console.log('Raw icon data from server:', iconData);
                    
                    // If it's a string, try to parse it as JSON
                    if (typeof iconData === 'string') {
                        try {
                            iconData = JSON.parse(iconData);
                            console.log('Parsed icon data:', iconData);
                        } catch (e) {
                            console.error('Failed to parse icon data:', e);
                            // If parsing fails, try to handle it as a legacy format
                            iconData = {
                                type: iconData.includes('/') ? 'custom' : 'fontawesome',
                                name: iconData.includes('/') ? `image-${Date.now()}` : iconData.replace(/\+/g, ' '),
                                url: iconData.includes('/') ? iconData : null
                            };
                            console.log('Converted to legacy format:', iconData);
                        }
                    }
                    
                    // Validate icon data structure
                    if (iconData && iconData.type && (
                        (iconData.type === 'fontawesome' && iconData.name) ||
                        (iconData.type === 'custom' && iconData.url)
                    )) {
                        // Store the icon data in the input field
                        const iconJson = JSON.stringify(iconData);
                        console.log('Storing icon data in input field:', iconJson);
                        jQuery('#shortcut-icon').val(iconJson);
                        
                        // Update the icon selector
                        if (window.shortcutIconSelector) {
                            console.log('Updating icon selector with:', iconData);
                            window.shortcutIconSelector.currentValue = iconData;
                            window.shortcutIconSelector.updatePreview();
                            
                            // Update type selector and show appropriate UI
                            if (window.shortcutIconSelector.typeSelect) {
                                window.shortcutIconSelector.typeSelect.value = iconData.type;
                                if (iconData.type === 'custom') {
                                    window.shortcutIconSelector.showCustomUpload();
                                } else {
                                    window.shortcutIconSelector.showFontAwesomePicker();
                                }
                            }
                        } else {
                            console.error('Icon selector not initialized');
                        }
                    } else {
                        console.error('Invalid icon data structure:', iconData);
                    }
                } else {
                    console.log('No icon data found in shortcut data');
                }
            }
        },
        error: function(xhr, status, error) {
            console.error('AJAX error:', status, error);
            jQuery('#feedback-message').html('<div class="error-message">Error loading shortcut data. Please check the console for details.</div>');
        }
    });
}
