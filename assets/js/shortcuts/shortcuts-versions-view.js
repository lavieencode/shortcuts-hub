jQuery(document).ready(function() {
    const urlParams = new URLSearchParams(window.location.search);
    const view = urlParams.get('view');
    const shortcutId = urlParams.get('id');

    if (view === 'versions' && shortcutId) {
        jQuery('#versions-header-bar').show();
        jQuery('#back-to-shortcuts').show();
        jQuery('#shortcut-name-display').show();
        fetchVersions(shortcutId);
    }
});

function toggleVersionsView(show) {
    const urlParams = new URLSearchParams(window.location.search);
    if (show) {
        urlParams.set('view', 'versions');
    } else {
        urlParams.delete('view');
        urlParams.delete('id');
    }
    window.history.pushState({}, '', `${window.location.pathname}?${urlParams}`);
    window.location.reload();
}
