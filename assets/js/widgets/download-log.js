jQuery(document).ready(function($) {
    $('.shortcuts-hub-download-log .download-latest').on('click', function(e) {
        e.preventDefault();
        const button = $(this);
        const shortcutId = button.data('shortcut-id');
        const postId = button.data('post-id');
        
        // Disable button and show loading state
        button.prop('disabled', true).text('Loading...');
        
        // First, fetch the latest version
        $.ajax({
            url: shortcuts_hub_params.ajax_url,
            type: 'POST',
            data: {
                action: 'fetch_latest_version',
                security: $('#shortcuts_hub_nonce').val(),
                id: shortcutId
            },
            success: function(response) {
                if (response.success && response.data) {
                    // Open download in new window
                    window.open(response.data.url, '_blank');
                    
                    // Log the download
                    $.ajax({
                        url: shortcuts_hub_params.ajax_url,
                        type: 'POST',
                        data: {
                            action: 'log_shortcut_download',
                            security: $('#shortcuts_hub_nonce').val(),
                            shortcut_id: shortcutId,
                            post_id: postId,
                            version_data: JSON.stringify(response.data)
                        }
                    });
                } else {
                    alert('Error fetching latest version');
                }
                // Reset button state
                button.prop('disabled', false).text('Download Latest');
            },
            error: function() {
                alert('Error fetching latest version');
                button.prop('disabled', false).text('Download Latest');
            }
        });
    });
});
