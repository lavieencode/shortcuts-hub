jQuery('.version-button').on('click', function() {
    const sbId = jQuery(this).data('sb-id');
    const urlParams = new URLSearchParams(window.location.search);
    urlParams.set('view', 'versions');
    urlParams.set('id', sbId);
    window.history.pushState({}, '', `${window.location.pathname}?${urlParams}`);
    
    toggleVersionsView(true);
    fetchVersions(sbId);
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
