function updateShortcut(formData, options = {}) {
    const {
        target = 'wp', // 'wp' or 'sb'
        baseUrl = shortcutsHubData.ajax_url,
        onSuccess,
        onError,
        onComplete,
        buttonElement
    } = options;

    // Debug: Log button state for testing - verifying button element and its attributes
    if (buttonElement) {
        const $button = jQuery(buttonElement);
        console.log('Button clicked:', {
            id: $button.attr('id'),
            classes: $button.attr('class'),
            text: $button.text(),
            currentStatus: jQuery('#shortcut-post-id').data('post-status'),
            formData: formData
        });
    }

    // Prepare data based on target
    let requestData = {};
    if (target === 'wp') {
        requestData = {
            action: 'update_shortcut',
            security: shortcutsHubData.security,
            shortcut_data: {
                post_id: formData.post_id,
                sb_id: formData.sb_id,
                name: formData.name,
                headline: formData.headline,
                description: formData.description,
                color: formData.color,
                icon: formData.icon,
                input: formData.input,
                result: formData.result,
                state: formData.state
            }
        };
    } else if (target === 'sb') {
        requestData = {
            name: formData.name,
            headline: formData.headline,
            description: formData.description,
            website: formData.website,
            state: formData.state === 'publish' ? 0 : 1 // Convert WP state to SB state (0=published, 1=draft)
        };
    }

    // Debug: Log API request for testing - verifying request URL, method, headers, and data structure
    console.log(`${target.toUpperCase()} API Request:`, {
        url: target === 'wp' ? baseUrl : `${baseUrl}/shortcuts/${formData.sb_id}`,
        method: target === 'wp' ? 'POST' : 'PATCH',
        headers: target === 'wp' ? {} : {
            'Content-Type': 'application/json'
        },
        data: requestData
    });

    // Set button loading state if provided
    if (buttonElement) {
        const $button = jQuery(buttonElement);
        const currentStatus = jQuery('#shortcut-post-id').data('post-status');
        const buttonText = currentStatus === 'publish' ? 'Updating...' : 'Saving...';
        $button.prop('disabled', true).text(buttonText);
    }

    // Make the request
    jQuery.ajax({
        url: target === 'wp' ? baseUrl : `${baseUrl}/shortcuts/${formData.sb_id}`,
        method: target === 'wp' ? 'POST' : 'PATCH',
        headers: target === 'wp' ? {} : {
            'Content-Type': 'application/json'
        },
        data: target === 'wp' ? requestData : JSON.stringify(requestData),
        beforeSend: function(xhr, settings) {
            // Debug: Verify API request details before sending - checking URL formation, headers, and request data structure
            sh_debug_log(
                `Making ${target.toUpperCase()} API request`,
                {
                    url: settings.url,
                    method: settings.type,
                    headers: settings.headers,
                    data: settings.data
                }
            );
        },
        success: function(response) {
            // Debug: Verify API response structure and success status - ensuring proper data format and error handling
            sh_debug_log(
                `${target.toUpperCase()} API response received`,
                {
                    success: true,
                    data: response
                }
            );
            
            // For WordPress, check response.success
            // For Switchblade, a 200 response is considered success (response might be empty for PATCH)
            const isSuccess = target === 'wp' ? response.success : true;
            
            if (isSuccess) {
                if (onSuccess) onSuccess(response);

                // Show success message if not handled by callback
                if (!onSuccess) {
                    jQuery('#feedback-message')
                        .removeClass('error')
                        .addClass('success')
                        .text(target === 'wp' ? response.data.message : 'Shortcut updated in Switchblade')
                        .show();
                    
                    setTimeout(() => {
                        jQuery('#feedback-message').hide();
                    }, 3000);
                }
            } else {
                const errorMessage = target === 'wp' ? 
                    (response.data ? response.data.message : 'Error updating shortcut in WordPress') : 
                    'Error updating shortcut in Switchblade';
                
                if (onError) onError(errorMessage);
                else {
                    jQuery('#feedback-message')
                        .removeClass('success')
                        .addClass('error')
                        .text(errorMessage)
                        .show();
                }
            }
        },
        error: function(xhr, status, error) {
            // Debug: Log API error for testing - verifying error status, response text, and error message
            console.log(`${target.toUpperCase()} API Error:`, {
                status: xhr.status,
                statusText: xhr.statusText,
                responseText: xhr.responseText,
                error: error
            });
            
            if (onError) onError(`Error updating shortcut in ${target.toUpperCase()}`);
            else {
                jQuery('#feedback-message')
                    .removeClass('success')
                    .addClass('error')
                    .text(`Error updating shortcut in ${target.toUpperCase()}`)
                    .show();
            }
        },
        complete: function() {
            if (buttonElement) {
                const $button = jQuery(buttonElement);
                const currentStatus = jQuery('#shortcut-post-id').data('post-status');
                $button.prop('disabled', false)
                    .text(currentStatus === 'publish' ? 'Update' : 'Save');
                
                // Debug: Log final button state for testing - verifying button state after request completion
                console.log('Button state after request:', {
                    id: $button.attr('id'),
                    classes: $button.attr('class'),
                    text: $button.text(),
                    currentStatus: currentStatus
                });
            }
            if (onComplete) onComplete();
        }
    });
}