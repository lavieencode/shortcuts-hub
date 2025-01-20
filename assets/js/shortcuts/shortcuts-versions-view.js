jQuery(document).ready(function() {
    // URL parameters are now handled by checkUrlParameters in versions-handlers.js
});

function toggleVersionsView(show, shortcutId) {
    if (show) {
        jQuery('#shortcuts-view').hide();
        jQuery('#versions-view, #versions-header-bar, #back-to-shortcuts, #shortcut-name-display').show();
        fetchVersions(shortcutId);
    } else {
        jQuery('#versions-view, #versions-header-bar, #back-to-shortcuts, #shortcut-name-display').hide();
        jQuery('#shortcuts-view').show();
        fetchShortcutsFromSource('WP');
    }
}
