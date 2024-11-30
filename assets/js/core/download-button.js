jQuery(document).ready(function($) {
    'use strict';

    $(document).on('click', '.elementor-button.shortcut-download-btn', function(e) {
        e.preventDefault();
        const $button = $(this);
        const shortcutData = $button.data('shortcut');
        const postUrl = shortcuts_hub.post_url || window.location.href;
        
        if (!shortcuts_hub.is_user_logged_in) {
            console.log('[Download Button] User not logged in, redirecting to login with parameters');
            if (shortcutData) {
                console.log('[Download Button] Shortcut data:', shortcutData);
                console.log('[Download Button] Post URL:', postUrl);
                
                // Add parameters to login URL
                const loginUrl = new URL(shortcuts_hub.login_url);
                loginUrl.searchParams.append('redirect_url', postUrl);
                loginUrl.searchParams.append('shortcut_data', JSON.stringify(shortcutData));
                
                console.log('[Download Button] Redirecting to:', loginUrl.toString());
                window.location.href = loginUrl.toString();
            } else {
                console.error('[Download Button] No shortcut data available');
                window.location.href = shortcuts_hub.login_url;
            }
            return;
        }
        
        if (shortcutData && shortcutData.version && shortcutData.version.url) {
            console.log('[Download Button] Processing download for logged-in user:', shortcutData);
            // Log the download
            $.ajax({
                url: shortcuts_hub.ajax_url,
                type: 'POST',
                data: {
                    action: 'log_shortcut_download',
                    security: shortcuts_hub.nonce,
                    shortcut_id: shortcutData.shortcut.id,
                    post_id: shortcutData.shortcut.post_id,
                    version_data: JSON.stringify(shortcutData.version)
                },
                success: function(response) {
                    console.log('[Download Button] Download logged successfully:', response);
                    
                    // Open download in popup
                    const popup = window.open(shortcutData.version.url, '_blank', 'width=800,height=600');
                    if (popup) {
                        popup.focus();
                    }
                },
                error: function(xhr, status, error) {
                    console.error('[Download Button] Failed to log download:', error);
                    // Still proceed with download
                    const popup = window.open(shortcutData.version.url, '_blank', 'width=800,height=600');
                    if (popup) {
                        popup.focus();
                    }
                }
            });
        } else {
            console.error('[Download Button] Missing version data for download:', shortcutData);
        }
    });
});