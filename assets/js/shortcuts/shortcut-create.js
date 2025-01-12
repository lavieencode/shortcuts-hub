function createShortcut(shortcutData, status = 'publish') {
    // Debug: Initial shortcut creation request with data
    sh_debug_log('1. Create Shortcut - Initial Request', {
        shortcutData: shortcutData,
        status: status
    });

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

    // Debug: AJAX request being sent to WordPress
    sh_debug_log('2. Create Shortcut - AJAX Request', {
        endpoint: shortcutsHubData.ajax_url,
        requestData: data
    });

    return jQuery.ajax({
        url: shortcutsHubData.ajax_url,
        method: 'POST',
        data: data,
        success: function(response) {
            // Debug: AJAX response received
            sh_debug_log('3. Create Shortcut - AJAX Response', {
                success: response.success,
                responseData: response.data
            });

            if (response.success) {
                const websiteUrl = shortcutsHubData.site_url + '/wp-admin/admin.php?page=edit-shortcut&id=' + response.data.post_id;
                
                jQuery('#message')
                    .removeClass('error-message')
                    .addClass('success-message')
                    .text('Shortcut created successfully: ' + response.data.message)
                    .show();
                
                // Debug: Redirecting to edit page
                sh_debug_log('4. Create Shortcut - Redirecting', {
                    redirectUrl: websiteUrl,
                    post_id: response.data.post_id
                });

                setTimeout(function() {
                    window.location.href = websiteUrl;
                }, 2000);
            } else {
                // Debug: Error in response
                sh_debug_log('Error - Create Shortcut Failed', {
                    error: response.data ? response.data.message : 'Unknown error'
                });

                jQuery('#message')
                    .removeClass('success-message')
                    .addClass('error-message')
                    .text('Error creating shortcut: ' + (response.data ? response.data.message : 'Unknown error occurred.'))
                    .show();
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