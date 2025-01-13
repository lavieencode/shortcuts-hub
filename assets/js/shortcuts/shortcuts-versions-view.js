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
