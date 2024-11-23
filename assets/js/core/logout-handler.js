jQuery(document).ready(function($) {
    'use strict';
    
    console.log('Logout handler initialized');
    
    // Verify we have our AJAX settings
    if (typeof shortcutsHubLogout === 'undefined') {
        console.error('Logout handler settings not found');
        return;
    }
    
    // Store the current URL before logout
    const currentPageUrl = window.location.href;
    console.log('Current page URL:', currentPageUrl);
    
    // Handle logout link clicks
    $(document).on('click', '.bp-menu.bp-logout-nav a', function(e) {
        e.preventDefault();
        
        // Get the full logout URL and parse it
        const logoutUrl = $(this).attr('href');
        console.log('Raw logout URL:', logoutUrl);
        
        // Parse URL parameters
        const urlParams = new URLSearchParams(logoutUrl.split('?')[1]);
        const redirectTo = urlParams.get('redirect_to');
        
        // Store the current URL as a fallback
        const fallbackUrl = currentPageUrl;
        
        console.log('Parsed values:', {
            redirectTo: redirectTo,
            fallbackUrl: fallbackUrl,
            currentLocation: window.location.href,
            decodedRedirect: redirectTo ? decodeURIComponent(redirectTo) : null
        });
        
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
                console.log('Logout response:', response);
                
                if (response.success) {
                    let targetUrl;
                    
                    if (response.data && response.data.redirect_url) {
                        // Use server-provided redirect URL
                        targetUrl = response.data.redirect_url;
                    } else if (redirectTo) {
                        // Use URL from logout link
                        targetUrl = decodeURIComponent(redirectTo);
                    } else {
                        // Fallback to current page
                        targetUrl = fallbackUrl;
                    }
                    
                    console.log('Redirecting to:', targetUrl);
                    window.location.replace(targetUrl);
                } else {
                    console.error('Logout failed:', response);
                    // If AJAX logout fails, use the original logout URL
                    window.location.href = logoutUrl;
                }
            },
            error: function(xhr, status, error) {
                console.error('Logout AJAX error:', {
                    status: status,
                    error: error,
                    response: xhr.responseText
                });
                // On AJAX error, fall back to regular logout
                window.location.href = logoutUrl;
            }
        });
    });
});
