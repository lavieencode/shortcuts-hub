jQuery(document).ready(function() {
    const urlParams = new URLSearchParams(window.location.search);
    const view = urlParams.get('view');
    const shortcutId = urlParams.get('id');

    if (view === 'versions' && shortcutId) {
        toggleVersionsView(true);
        fetchVersions(shortcutId);
    } else {
        toggleVersionsView(false);
    }
});

function toggleVersionsView(show) {
    if (show) {
        jQuery('#versions-container').show();
        jQuery('#versions-header-bar').show();
        jQuery('#back-to-shortcuts').show();
        
        jQuery('.shortcuts-page-title').hide();
        jQuery('#shortcuts-header-bar').hide();
        jQuery('#shortcuts-container').hide();
        jQuery('.versions-page-title').show();
        jQuery('#shortcut-name-display').show();
    } else {
        jQuery('#versions-container').hide();
        jQuery('#versions-header-bar').hide();
        jQuery('#back-to-shortcuts').hide();
        
        jQuery('.shortcuts-page-title').show();
        jQuery('#shortcuts-header-bar').show();
        jQuery('#shortcuts-container').show();
        jQuery('.versions-page-title').hide();
        jQuery('#shortcut-name-display').hide();
    }
}
