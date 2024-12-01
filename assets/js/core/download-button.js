jQuery(document).ready(function($) {
    'use strict';

    // Function to update button state based on login status
    function updateButtonState($button) {
        const isLoggedIn = shortcuts_hub.is_user_logged_in === '1' || shortcuts_hub.is_user_logged_in === true;
        const downloadUrl = $button.data('download-url');
        const redirectUrl = $button.data('redirect-url');
        const loginUrl = new URL('https://debotchery.ai/shortcuts-gallery/login');
        
        if (!isLoggedIn && redirectUrl) {
            // Store download data and get token
            $.ajax({
                url: shortcuts_hub.ajax_url,
                type: 'POST',
                data: {
                    action: 'ajax_log_download',
                    nonce: shortcuts_hub.nonce,
                    download_url: downloadUrl,
                    redirect_url: redirectUrl
                },
                success: function(response) {
                    if (response.success && response.data.token) {
                        loginUrl.searchParams.set('redirect_url', redirectUrl);
                        loginUrl.searchParams.set('download_token', response.data.token);
                        $button.attr('href', loginUrl.toString());
                    } else {
                        console.error('[Button] Failed to get token from AJAX response');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('[Button] AJAX error:', error);
                }
            });
        }
        
        // Update button classes
        $button.removeClass('logged-in logged-out')
               .addClass(isLoggedIn ? 'logged-in' : 'logged-out');
        
        // Update button text and attributes
        const $buttonText = $button.find('.elementor-button-text');
        const $buttonIcon = $button.find('.elementor-button-icon');
        
        if (isLoggedIn) {
            $buttonText.text('Download Now');
            $button.attr('href', downloadUrl);
        } else {
            $buttonText.text('Login to Download');
            $button.attr('href', loginUrl.toString());
        }
        
        $button.removeClass('loading');
        $buttonIcon.removeClass('eicon-loading eicon-animation-spin').addClass('eicon-download');
    }

    // Function to update button with download URL
    function updateButtonDownloadUrl($button) {
        const shortcutData = $button.data('shortcut');
        if (!shortcutData || !shortcutData.shortcut_id) {
            return;
        }

        // Make AJAX call to get latest version
        $.ajax({
            url: shortcuts_hub.ajax_url,
            method: 'POST',
            data: {
                action: 'fetch_latest_version',
                nonce: shortcuts_hub.nonce,
                id: shortcutData.shortcut_id
            },
            success: function(response) {
                if (response.success && response.data.version && response.data.version.url) {
                    // Update shortcut data
                    shortcutData.download_url = response.data.version.url;
                    $button.data('shortcut', shortcutData);
                    
                    // Update button text and href if user is logged in
                    if (shortcutData.is_logged_in) {
                        $button.text('Download');
                        // Add href but keep click handler for popup behavior
                        $button.attr('href', response.data.version.url);
                    }
                } else {
                    console.error('[Download Button] Invalid response:', response);
                }
            },
            error: function(xhr, status, error) {
                console.error('[Download Button] Failed to get download URL:', error);
            }
        });
    }

    // Update download URL when page loads and when login state changes
    function updateAllButtons() {
        $('.shortcuts-hub-download-button').each(function() {
            updateButtonDownloadUrl($(this));
        });
    }

    // Initial button setup
    $('.shortcut-download-btn').each(function() {
        updateButtonState($(this));
    });

    // Initial update
    updateAllButtons();

    // Update on login state change
    $(document).on('shortcuts_hub_login_state_changed', function() {
        $('.shortcuts-hub-download-button').each(function() {
            const $button = $(this);
            const shortcutData = $button.data('shortcut');
            
            // Update login state in button data
            shortcutData.is_logged_in = shortcuts_hub.is_user_logged_in;
            $button.data('shortcut', shortcutData);
            
            // Update button text and href
            if (shortcuts_hub.is_user_logged_in) {
                $button.text('Download');
                if (shortcutData.download_url) {
                
                    $button.attr('href', shortcutData.download_url);
                }
            } else {
                $button.text('Login to Download');
                $button.removeAttr('href');
            }

            // Update download URL
            updateButtonDownloadUrl($button);
            
        });
    });

    // Check if we have a download token in the URL
    const urlParams = new URLSearchParams(window.location.search);
    const downloadToken = urlParams.get('download_token');
    
    if (downloadToken && shortcuts_hub.is_user_logged_in) {
        // Process the download token
        $.ajax({
            url: shortcuts_hub.ajax_url,
            type: 'POST',
            data: {
                action: 'ajax_log_download',
                nonce: shortcuts_hub.nonce,
                token: downloadToken
            },
            success: function(response) {
                if (response.success && response.data.download_url) {
                    const popup = window.open(response.data.download_url, '_blank', 'width=800,height=600');
                    if (popup) {
                        popup.focus();
                    }
                }
            }
        });

        // Clean up the URL by removing the token
        urlParams.delete('download_token');
        const newUrl = window.location.pathname + (urlParams.toString() ? '?' + urlParams.toString() : '');
        window.history.replaceState({}, '', newUrl);
    }

    // Clear URL parameters after they've been processed
    function clearUrlParameters() {
        const url = new URL(window.location.href);
        url.searchParams.delete('download_token');
        const newUrl = url.toString();
        window.history.replaceState({}, '', newUrl);
    }

    // Handle WordPress logout
    $(document).on('click', 'a[href*="logout"]', function() {
        shortcuts_hub.is_user_logged_in = false;
        $(document).trigger('shortcuts_hub_login_state_changed');
    });

    // Handle login state changes
    $(document).on('shortcuts_hub_login_state_changed', function() {
        $('.shortcuts-hub-download-button').each(function() {
            const $button = $(this);
            const shortcutData = $button.data('shortcut');
            
            // Update login state in button data
            shortcutData.is_logged_in = shortcuts_hub.is_user_logged_in;
            $button.data('shortcut', shortcutData);
            
            // Update button text
            const buttonText = shortcuts_hub.is_user_logged_in ? 
                'Download' : 'Login to Download';
            $button.text(buttonText);
        });
    });

    // Handle download button click
    $('.shortcuts-hub-download-button').on('click', function(e) {
        e.preventDefault();
        
        const shortcutData = $(this).data('shortcut');
        
        // If user is not logged in, redirect to login page with parameters
        if (!shortcutData.is_logged_in) {
            const loginUrl = new URL(shortcutData.login_url);
            loginUrl.searchParams.set('redirect_url', shortcutData.redirect_url);
            if (shortcutData.download_token) {
                loginUrl.searchParams.set('download_token', shortcutData.download_token);
            }
            window.location.href = loginUrl.toString();
            return;
        }
        
        // For logged-in users on shortcut page, just open the download URL
        if (shortcutData && shortcutData.download_url) {
            const popup = window.open(shortcutData.download_url, '_blank');
            if (popup) {
                popup.focus();
            }
        } else {
            console.error('[Download Button] No download URL available');
        }
    });
});