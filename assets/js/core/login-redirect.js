console.log('=== Script File Loaded (Before jQuery) ===');

jQuery(document).ready(function($) {
    'use strict';
    
    console.log('=== Login/Registration Redirect Script Loaded ===');
    console.log('Browser:', navigator.userAgent);
    console.log('Initial sessionStorage:', sessionStorage);
    console.log('Initial cookies:', document.cookie);
    
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
        console.log('Getting stored data...');
        
        // Try sessionStorage first
        const shortcutData = sessionStorage.getItem('shortcuts_hub_shortcut_data');
        const redirectUrl = sessionStorage.getItem('shortcuts_hub_redirect_url');
        
        // Try cookies as fallback
        const cookieShortcutData = getCookie('shortcuts_hub_shortcut_data');
        const cookieRedirectUrl = getCookie('shortcuts_hub_redirect_url');
        
        // Use whichever source has the data
        const finalShortcutData = shortcutData || cookieShortcutData;
        const finalRedirectUrl = redirectUrl || cookieRedirectUrl;
        
        console.log('Found shortcut data:', finalShortcutData);
        console.log('Found redirect URL:', finalRedirectUrl);
        
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

        // Fall back to individual values
        return {
            shortcut_id: sessionStorage.getItem('shortcuts_hub_shortcut_id') || getCookie('shortcuts_hub_shortcut_id'),
            redirect_url: finalRedirectUrl
        };
    }

    // Function to safely open a popup window
    function openDownloadPopup(url) {
        if (!url) {
            console.log('No download URL provided');
            return false;
        }

        try {
            // Try to open the popup immediately after user action
            const popup = window.open('', '_blank');
            if (popup) {
                popup.location.href = url;
                return true;
            } else {
                console.log('Popup was blocked by the browser');
                return false;
            }
        } catch (e) {
            console.error('Error opening popup:', e);
            return false;
        }
    }

    // Function to handle redirect with proper URL encoding
    function handleRedirect(url) {
        if (!url) {
            console.error('No URL provided for redirect');
            return;
        }
        
        try {
            // First decode to prevent double-encoding
            const decodedUrl = decodeURI(url);
            // Then encode properly
            const encodedUrl = encodeURI(decodedUrl);
            console.log('Redirecting to:', encodedUrl);
            window.location.href = encodedUrl;
        } catch (e) {
            console.error('Error encoding redirect URL:', e);
            window.location.href = url;
        }
    }

    // Handle both login and registration form submissions
    $(document).on('elementor-pro/forms/submit_success', function(event, response) {
        console.log('Form success event received:', response);
        
        // Check if this is a successful login or registration
        if (response?.data?.registration_success || response?.data?.login_success) {
            console.log('Login/Registration successful');
            
            const storedData = getStoredData();
            console.log('Retrieved stored data:', storedData);
            
            // Try to get the download URL
            let downloadUrl = null;
            let popupOpened = false;
            
            // First check the stored shortcut data
            if (storedData.shortcut_data?.version?.url) {
                downloadUrl = storedData.shortcut_data.version.url;
                console.log('Found download URL in stored data:', downloadUrl);
                popupOpened = openDownloadPopup(downloadUrl);
            }
            // Then check if we need to fetch it
            else if (storedData.shortcut_id) {
                console.log('Found shortcut ID, fetching latest version...');
                $.ajax({
                    url: shortcuts_hub.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'fetch_latest_version',
                        security: shortcuts_hub.nonce,
                        id: storedData.shortcut_id
                    },
                    success: function(response) {
                        if (response.success && response.data) {
                            console.log('Storing fetched shortcut data:', response.data);
                            sessionStorage.setItem('shortcuts_hub_shortcut_data', JSON.stringify(response.data));
                            document.cookie = `shortcuts_hub_shortcut_data=${JSON.stringify(response.data)}; path=/`;
                            
                            if (response.data.version?.url) {
                                popupOpened = openDownloadPopup(response.data.version.url);
                            }
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Error fetching version:', error);
                    }
                });
            }
            
            // Handle the redirect
            const redirectUrl = storedData.redirect_url || 
                              response.data.redirect_url || 
                              getCookie('shortcuts_hub_redirect_url');
            
            if (redirectUrl) {
                // Add a small delay for the popup if it was opened
                if (popupOpened) {
                    setTimeout(() => {
                        handleRedirect(redirectUrl);
                    }, 500);
                } else {
                    handleRedirect(redirectUrl);
                }
            } else {
                console.log('No redirect URL found');
            }
        }
    });
});