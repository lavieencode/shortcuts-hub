function createShortcut(shortcutData, status = 'publish') {
    // Process icon data
    let iconData;
    try {
        if (typeof shortcutData.icon === 'string') {
            // Try to parse if it's already a JSON string
            try {
                iconData = JSON.parse(shortcutData.icon);
            } catch {
                // If not a JSON string, assume it's a legacy format with just the name
                iconData = {
                    type: 'fontawesome',
                    name: shortcutData.icon,
                    url: null
                };
            }
        } else if (typeof shortcutData.icon === 'object') {
            // If it's already an object, ensure it has all required fields
            iconData = {
                type: shortcutData.icon.type || 'fontawesome',
                name: shortcutData.icon.name || '',
                url: shortcutData.icon.url || null
            };
        } else {
            // Default empty icon data
            iconData = {
                type: 'fontawesome',
                name: '',
                url: null
            };
        }
    } catch (error) {
        // Use default icon data on error
        iconData = {
            type: 'fontawesome',
            name: '',
            url: null
        };
    }

    // First create in Switchblade
    const data = {
        action: 'create_shortcut',
        security: shortcutsHubData.security.create_shortcut,
        shortcut_data: {
            name: shortcutData.name,
            headline: shortcutData.headline,
            description: shortcutData.description,
            state: status === 'draft' ? 1 : 0
        },
        wp_data: {
            input: shortcutData.input,
            result: shortcutData.result,
            color: shortcutData.color,
            icon: JSON.stringify(iconData),
            post_status: status
        }
    };

    // Track request/response for debug logging
    let debugData = {
        request: {
            shortcut: shortcutData,
            processedIcon: iconData,
            ajax: data
        }
    };

    return jQuery.ajax({
        url: shortcutsHubData.ajax_url,
        method: 'POST',
        data: data,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        },
        success: function(response) {
            debugData.response = response;
            
            if (response.success) {
                jQuery('#message')
                    .removeClass('error-message')
                    .addClass('success-message')
                    .text('Shortcut created successfully')
                    .show();

                // Hide message after 3 seconds
                setTimeout(function() {
                    jQuery('#message').fadeOut();
                }, 3000);
            } else {
                jQuery('#message')
                    .removeClass('success-message')
                    .addClass('error-message')
                    .text('Error creating shortcut: ' + (response.data?.message || 'Unknown error'))
                    .show();
            }
        },
        error: function(xhr, status, error) {
            jQuery('#message')
                .removeClass('success-message')
                .addClass('error-message')
                .text('AJAX error creating shortcut: ' + xhr.responseText)
                .show();
        }
    });
}