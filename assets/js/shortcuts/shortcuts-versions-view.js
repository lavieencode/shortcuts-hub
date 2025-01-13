jQuery(document).ready(function() {
    // URL parameters are now handled by checkUrlParameters in versions-handlers.js
});

function toggleVersionsView(show, shortcutId) {
    const urlParams = new URLSearchParams(window.location.search);
    if (show) {
        urlParams.set('view', 'versions');
        urlParams.set('id', shortcutId);
        jQuery('#shortcuts-view').hide();
        jQuery('#versions-view, #versions-header-bar, #back-to-shortcuts, #shortcut-name-display').show();
        fetchVersions(shortcutId);
    } else {
        urlParams.delete('view');
        urlParams.delete('id');
        jQuery('#versions-view, #versions-header-bar, #back-to-shortcuts, #shortcut-name-display').hide();
        jQuery('#shortcuts-view').show();
        fetchShortcutsFromSource('WP');
    }
    window.history.pushState({}, '', `${window.location.pathname}?${urlParams}`);
}

function fetchVersions(shortcutId) {
    // DEBUG: Fetching versions from Switchblade API for shortcut ID
    window.sh_debug_log('Fetching versions from Switchblade API', {
        'shortcut_id': shortcutId,
        'source': 'shortcuts-versions-view.js',
        'function': 'fetchVersions',
        'url': window.location.href,
        'debug': false
    });

    jQuery.ajax({
        url: shortcutsHubData.ajax_url,
        type: 'POST',
        data: {
            action: 'fetch_versions',
            security: shortcutsHubData.security,
            id: shortcutId
        },
        success: function(response) {
            // DEBUG: Received versions response from Switchblade API
            window.sh_debug_log('Received versions response', {
                'shortcut_id': shortcutId,
                'response': response,
                'source': 'shortcuts-versions-view.js',
                'function': 'fetchVersions.success',
                'debug': true
            });

            if (response.success) {
                displayVersions(response.data);
            } else {
                console.error('Error fetching versions:', response.data);
            }
        },
        error: function(xhr, status, error) {
            // DEBUG: Error fetching versions from Switchblade API
            window.sh_debug_log('Error fetching versions', {
                'shortcut_id': shortcutId,
                'error': error,
                'status': status,
                'xhr': xhr,
                'source': 'shortcuts-versions-view.js',
                'function': 'fetchVersions.error',
                'debug': false
            });

            console.error('Error fetching versions:', error);
        }
    });
}
