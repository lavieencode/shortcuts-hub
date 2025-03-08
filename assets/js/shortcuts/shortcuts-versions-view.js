jQuery(document).ready(function() {
    // URL parameters are now handled by checkUrlParameters in versions-handlers.js
});

function toggleVersionsView(show = false, shortcutId = null) {
    // DEBUG: Log view state toggle
    if (typeof window.sh_debug_log === 'function') {
        window.sh_debug_log('Toggling versions view state',
            {
                debug: true,
                message: 'Toggling between versions and shortcuts views',
                params: {
                    show: show,
                    shortcut_id: shortcutId
                },
                elements: {
                    shortcuts_view_visible: jQuery('#shortcuts-view').is(':visible'),
                    versions_view_visible: jQuery('#versions-view').is(':visible')
                }
            },
            {
                file: 'shortcuts-versions-view.js',
                line: 'toggleVersionsView',
                function: 'toggleVersionsView'
            }
        );
    }

    if (show) {
        jQuery('#shortcuts-view').hide();
        jQuery('#versions-view, #versions-header-bar, #back-to-shortcuts, #shortcut-name-display').show();
        
        if (shortcutId) {
            fetchVersions(shortcutId);
        }
    } else {
        // Only hide if we're not on versions page
        const urlParams = new URLSearchParams(window.location.search);
        const view = urlParams.get('view');
        
        if (view !== 'versions') {
            jQuery('#versions-view, #versions-header-bar, #back-to-shortcuts, #shortcut-name-display').hide();
            jQuery('#shortcuts-view').show();
        }
    }
}
