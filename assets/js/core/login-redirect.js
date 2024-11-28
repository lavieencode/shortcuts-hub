jQuery(document).ready(function($) {
    'use strict';
    
    // Function to get cookie value
    function getCookie(name) {
        const value = `; ${document.cookie}`;
        const parts = value.split(`; ${name}=`);
        if (parts.length === 2) {
            return parts.pop().split(';').shift();
        }
        return null;
    }
    
    // Function to get stored data from both sessionStorage and cookies
    function getStoredData() {
        // Try sessionStorage first
        const shortcutData = sessionStorage.getItem('shortcuts_hub_shortcut_data');
        const redirectUrl = sessionStorage.getItem('shortcuts_hub_redirect_url');
        
        // Try cookies as fallback
        const cookieShortcutData = getCookie('shortcuts_hub_shortcut_data');
        const cookieRedirectUrl = getCookie('shortcuts_hub_redirect_url');
        
        // Use whichever source has the data
        const finalShortcutData = shortcutData || cookieShortcutData;
        const finalRedirectUrl = redirectUrl || cookieRedirectUrl;
        
        if (finalShortcutData) {
            try {
                const parsedData = JSON.parse(finalShortcutData);
                if (parsedData.version?.url) {
                    return {
                        shortcut_data: parsedData,
                        redirect_url: finalRedirectUrl
                    };
                }
            } catch (e) {
                console.error('Error parsing stored data:', e);
            }
        }
        return null;
    }

    // Function to safely open a popup window
    function openDownloadPopup(url) {
        const popup = window.open(url, '_blank');
        if (popup) {
            popup.focus();
        }
    }

    // Function to handle redirect with proper URL encoding
    function handleRedirect(url) {
        if (url) {
            window.location.href = decodeURIComponent(url);
        }
    }

    // Handle both login and registration form submissions
    $(document).on('elementor-pro/forms/submit_success', function(event, response) {
        const storedData = getStoredData();
        
        if (storedData && storedData.shortcut_data) {
            // Make AJAX call to handle the download
            $.ajax({
                url: shortcutsHubAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'shortcuts_hub_handle_download',
                    security: shortcutsHubAjax.nonce,
                    shortcut_data: JSON.stringify(storedData.shortcut_data)
                },
                success: function(response) {
                    if (response.success && response.data.download_url) {
                        openDownloadPopup(response.data.download_url);
                    }
                    
                    // Clear stored data
                    sessionStorage.removeItem('shortcuts_hub_shortcut_data');
                    sessionStorage.removeItem('shortcuts_hub_redirect_url');
                },
                error: function(xhr, status, error) {
                    console.error('Download request failed:', error);
                }
            });
        }
        
        // Handle the redirect
        const redirectUrl = storedData?.redirect_url || response?.data?.redirect_url;
        if (redirectUrl) {
            handleRedirect(redirectUrl);
        }
    });
});