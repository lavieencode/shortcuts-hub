function createShortcut(shortcutData, status = 'publish') {
    // First create in Switchblade
    const data = {
        action: 'create_shortcut',
        security: jQuery('#shortcuts_hub_nonce').val(),
        shortcut_data: {
            name: shortcutData.name,
            headline: shortcutData.headline,
            description: shortcutData.description,
            state: status === 'draft' ? 1 : 0
        }
    };

    return jQuery.ajax({
        url: shortcutsHubData.ajax_url,
        method: 'POST',
        data: data,
        success: function(response) {
            if (response.success) {
                const websiteUrl = shortcutsHubData.site_url + '/wp-admin/admin.php?page=edit-shortcut&id=' + response.data.post_id;
                
                jQuery('#message')
                    .removeClass('error-message')
                    .addClass('success-message')
                    .text('Shortcut created successfully: ' + response.data.message)
                    .show();
                
                setTimeout(function() {
                    window.location.href = websiteUrl;
                }, 2000);
            } else {
                jQuery('#message')
                    .removeClass('success-message')
                    .addClass('error-message')
                    .text('Error creating shortcut: ' + (response.data ? response.data.message : 'Unknown error occurred.'))
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