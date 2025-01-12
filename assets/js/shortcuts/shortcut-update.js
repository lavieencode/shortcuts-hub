function updateShortcut(formData, options = {}) {
    const {
        target = 'wp', // 'wp' or 'sb'
        baseUrl = shortcutsHubData.ajax_url,
        onSuccess,
        onError,
        onComplete,
        buttonElement
    } = options;

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

    if (buttonElement) {
        const $button = jQuery(buttonElement);
        const currentStatus = jQuery('#shortcut-post-id').data('post-status');
        const buttonText = currentStatus === 'publish' ? 'Updating...' : 'Saving...';
        $button.prop('disabled', true).text(buttonText);
    }

    jQuery.ajax({
        url: target === 'wp' ? baseUrl : `${baseUrl}/shortcuts/${formData.sb_id}`,
        method: target === 'wp' ? 'POST' : 'PATCH',
        headers: target === 'wp' ? {} : {
            'Content-Type': 'application/json'
        },
        data: target === 'wp' ? requestData : JSON.stringify(requestData),
        beforeSend: function(xhr, settings) {
        },
        success: function(response) {
            const isSuccess = target === 'wp' ? response.success : true;
            
            if (isSuccess) {
                if (onSuccess) onSuccess(response);

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
            }
            if (onComplete) onComplete();
        }
    });
}