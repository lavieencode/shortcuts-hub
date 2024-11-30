jQuery(document).ready(function($) {
    'use strict';
    
    // Helper function to log to both console and PHP
    function logRedirect(message, data = null) {
        console.log(message, data);
        $.ajax({
            url: shortcutsHubAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'shortcuts_hub_log',
                message: message,
                data: JSON.stringify(data),
                nonce: shortcutsHubAjax.nonce
            }
        });
    }
    
    // Handle form submissions
    $(document).on('elementor-pro/forms/submit_success', function(event, response) {
        logRedirect('[Login Redirect] Form submission success, full response:', response);
        
        if (!response.data) {
            logRedirect('[Login Redirect] Error: No response data found');
            return;
        }
        
        logRedirect('[Login Redirect] Processing response data:', response.data);
        
        // Handle download data if present
        if (response.data.download_data) {
            logRedirect('[Login Redirect] Found download data:', response.data.download_data);
            
            $.ajax({
                url: shortcutsHubAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'log_shortcut_download',
                    security: shortcutsHubAjax.nonce,
                    shortcut_id: response.data.download_data.shortcut.id,
                    version_data: JSON.stringify(response.data.download_data.version)
                },
                success: function(downloadResponse) {
                    logRedirect('[Login Redirect] Download request success:', downloadResponse);
                    
                    if (downloadResponse.success && response.data.download_data.version.url) {
                        logRedirect('[Login Redirect] Opening download popup for URL:', response.data.download_data.version.url);
                        const popup = window.open(response.data.download_data.version.url, '_blank', 'width=800,height=600');
                        if (popup) {
                            popup.focus();
                        }
                    }
                    
                    // Handle redirect after download processing
                    if (response.data.redirect_url) {
                        logRedirect('[Login Redirect] Will redirect to URL after delay:', response.data.redirect_url);
                        setTimeout(() => {
                            logRedirect('[Login Redirect] Executing redirect to:', response.data.redirect_url);
                            window.location.href = response.data.redirect_url;
                        }, 500);
                    } else {
                        logRedirect('[Login Redirect] No redirect URL found in response');
                    }
                },
                error: function(xhr, status, error) {
                    logRedirect('[Login Redirect] Download request failed:', { status, error });
                    // Still redirect on error
                    if (response.data.redirect_url) {
                        logRedirect('[Login Redirect] Redirecting after error to:', response.data.redirect_url);
                        window.location.href = response.data.redirect_url;
                    }
                }
            });
        } else if (response.data.redirect_url) {
            // Direct redirect if no download data
            logRedirect('[Login Redirect] No download data, direct redirect to:', response.data.redirect_url);
            window.location.href = response.data.redirect_url;
        } else {
            logRedirect('[Login Redirect] No redirect URL or download data found in response');
        }
    });
});