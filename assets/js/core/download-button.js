jQuery(document).ready(function($) {
    'use strict';

    // Function to set cookie with path and domain
    function setCookie(name, value, days) {
        var expires = '';
        if (days) {
            var date = new Date();
            date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
            expires = '; expires=' + date.toUTCString();
        }
        document.cookie = name + '=' + (value || '') + expires + '; path=/; SameSite=Lax';
    }

    // Store redirect URL and button data on page load
    const $downloadButton = $('.elementor-button.shortcut-download-btn');
    if ($downloadButton.length) {
        const buttonData = {
            redirect_url: $downloadButton.data('redirect-url'),
            shortcut_id: $downloadButton.data('sb-id'),
            post_id: $downloadButton.data('post-id')
        };
        
        // Store in both sessionStorage and cookies
        sessionStorage.setItem('shortcuts_hub_redirect_url', buttonData.redirect_url);
        sessionStorage.setItem('shortcuts_hub_shortcut_id', buttonData.shortcut_id);
        setCookie('shortcuts_hub_redirect_url', buttonData.redirect_url, 1);
        setCookie('shortcuts_hub_shortcut_id', buttonData.shortcut_id, 1);

        // Store the complete shortcut data if available
        if (shortcuts_hub.shortcut) {
            const shortcutData = {
                shortcut: {
                    id: shortcuts_hub.shortcut.id,
                    name: shortcuts_hub.shortcut.name
                },
                version: {
                    url: shortcuts_hub.shortcut.version.url,
                    version: shortcuts_hub.shortcut.version.version
                }
            };
            
            const shortcutDataString = JSON.stringify(shortcutData);
            sessionStorage.setItem('shortcuts_hub_shortcut_data', shortcutDataString);
            setCookie('shortcuts_hub_shortcut_data', shortcutDataString, 1);
        }
    }

    $(document).on('click', '.elementor-button.shortcut-download-btn', function(e) {
        e.preventDefault();
        
        const $button = $(this);
        const buttonData = {
            redirect_url: $button.data('redirect-url'),
            shortcut_id: $button.data('sb-id'),
            post_id: $button.data('post-id')
        };
        
        if (!shortcuts_hub.is_user_logged_in) {
            // Store only essential data
            sessionStorage.setItem('shortcuts_hub_redirect_url', buttonData.redirect_url);
            sessionStorage.setItem('shortcuts_hub_shortcut_id', buttonData.shortcut_id);

            // Fetch shortcut data to store version info
            $.ajax({
                url: shortcuts_hub.ajax_url,
                type: 'POST',
                data: {
                    action: 'fetch_latest_version',
                    security: shortcuts_hub.nonce,
                    id: buttonData.shortcut_id
                },
                success: function(response) {
                    if (response.success && response.data) {
                        // Store only the necessary shortcut and version data
                        const shortcutData = {
                            shortcut: {
                                id: response.data.shortcut.id,
                                name: response.data.shortcut.name
                            },
                            version: {
                                url: response.data.version.url,
                                version: response.data.version.version
                            }
                        };
                        
                        sessionStorage.setItem('shortcuts_hub_shortcut_data', JSON.stringify(shortcutData));
                    }
                    window.location.href = shortcuts_hub.login_url;
                }
            });
            return;
        }
        
        // If we have shortcut data, proceed with download
        if (shortcuts_hub.shortcut && shortcuts_hub.shortcut.version) {
            console.log('Using cached shortcut data:', shortcuts_hub.shortcut);
            logDownload(buttonData, shortcuts_hub.shortcut.version);
            
            // Try to force popup
            const popup = window.open('about:blank', '_blank', 'width=800,height=600');
            if (popup) {
                console.log('Popup window created, redirecting to:', shortcuts_hub.shortcut.version.url);
                popup.location.href = shortcuts_hub.shortcut.version.url;
            } else {
                console.error('Popup was blocked');
            }
            return;
        }

        // Otherwise fetch the latest version
        console.log('Fetching latest version data');
        $.ajax({
            url: shortcuts_hub.ajax_url,
            type: 'POST',
            data: {
                action: 'fetch_latest_version',
                security: shortcuts_hub.nonce,
                id: buttonData.shortcut_id
            },
            success: function(response) {
                console.log('Fetch version response:', response);
                if (response.success && response.data && response.data.version) {
                    logDownload(buttonData, response.data.version);
                    
                    // Try to force popup
                    const popup = window.open('about:blank', '_blank', 'width=800,height=600');
                    if (popup) {
                        console.log('Popup window created, redirecting to:', response.data.version.url);
                        popup.location.href = response.data.version.url;
                    } else {
                        console.error('Popup was blocked');
                    }
                } else {
                    console.error('Invalid version data in response:', response);
                }
            },
            error: function(xhr, status, error) {
                console.error('Failed to fetch version:', error);
            }
        });
    });

    function logDownload(buttonData, versionData) {
        $.ajax({
            url: shortcuts_hub.ajax_url,
            type: 'POST',
            data: {
                action: 'log_download',
                security: shortcuts_hub.nonce,
                post_id: buttonData.post_id,
                shortcut_id: buttonData.shortcut_id,
                version: versionData.version
            }
        });
    }
});