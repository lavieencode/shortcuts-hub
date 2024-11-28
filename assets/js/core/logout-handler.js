jQuery(document).ready(function($) {
    'use strict';
    
    // Verify we have our AJAX settings
    if (typeof shortcutsHubLogout === 'undefined') {
        console.error('Logout handler settings not found');
        return;
    }
    
    // Store the current URL before logout
    const currentPageUrl = window.location.href;
    
    // Handle logout link clicks
    $(document).on('click', '.bp-menu.bp-logout-nav a', function(e) {
        e.preventDefault();
        
        // Get the full logout URL and parse it
        const logoutUrl = $(this).attr('href');
        
        // Parse URL parameters
        const urlParams = new URLSearchParams(logoutUrl.split('?')[1]);
        const redirectTo = urlParams.get('redirect_to');
        
        // Store the current URL as a fallback
        const fallbackUrl = currentPageUrl;
        
        // Perform AJAX logout
        $.ajax({
            url: shortcutsHubLogout.ajaxurl,
            type: 'POST',
            data: {
                action: 'shortcuts_hub_ajax_logout',
                security: shortcutsHubLogout.nonce,
                redirect_url: redirectTo || fallbackUrl
            },
            success: function(response) {
                if (response.success) {
                    let targetUrl;
                    
                    if (response.data && response.data.redirect_url) {
                        targetUrl = response.data.redirect_url;
                    } else {
                        targetUrl = redirectTo || fallbackUrl;
                    }
                    
                    // Redirect to the target URL
                    window.location.href = targetUrl;
                }
            },
            error: function(xhr, status, error) {
                console.error('Logout failed:', error);
                // Fallback to default logout behavior
                window.location.href = logoutUrl;
            }
        });
    });
});
