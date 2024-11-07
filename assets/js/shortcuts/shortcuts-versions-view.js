
function toggleVersionsView(show) {
    if (show) {
        jQuery('#versions-container').show();
        jQuery('#versions-header-bar').show();
        jQuery('#back-to-shortcuts').show();
    } else {
        jQuery('#versions-container').hide();
        jQuery('#versions-header-bar').hide();
        jQuery('#back-to-shortcuts').hide();
    }
}
