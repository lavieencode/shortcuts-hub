function createShortcut(shortcutData, status = 'publish') {
    // First create in Switchblade
    const data = {
        action: 'create_shortcut',
        security: shortcutsHubData.security,
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
            icon: shortcutData.icon,
            post_status: status
        }
    };

    // Track request/response for debug logging
    let debugData = {
        request: {
            shortcut: shortcutData,
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
                const websiteUrl = shortcutsHubData.site_url + '/wp-admin/admin.php?page=edit-shortcut&id=' + response.data.post_id;
                
                jQuery('#message')
                    .removeClass('error-message')
                    .addClass('success-message')
                    .text('Shortcut created successfully: ' + response.data.message)
                    .show();

                // Log successful creation
                sh_debug_log('Create Shortcut', debugData);

                // Debug: Redirecting to edit page
                sh_debug_log('4. Create Shortcut - Redirecting', {
                    redirectUrl: websiteUrl,
                    post_id: response.data.post_id
                });

                setTimeout(function() {
                    window.location.href = websiteUrl;
                }, 2000);
            } else {
                jQuery('#message')
                    .removeClass('success-message')
                    .addClass('error-message')
                    .text('Error creating shortcut: ' + (response.data?.message || 'Unknown error'))
                    .show();

                // Log failed creation
                sh_debug_log('Error - Create Shortcut Failed', {
                    error: response.data?.message || 'Unknown error',
                    debug: debugData
                });
            }
        },
        error: function(xhr, status, error) {
            // Debug: AJAX error occurred
            sh_debug_log('Error - AJAX Failed', {
                status: status,
                error: error,
                response: xhr.responseText
            });

            jQuery('#message')
                .removeClass('success-message')
                .addClass('error-message')
                .text('AJAX error creating shortcut: ' + xhr.responseText)
                .show();
        }
    });
}