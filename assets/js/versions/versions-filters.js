jQuery(document).ready(function() {
    const urlParams = new URLSearchParams(window.location.search);
    const shortcutId = urlParams.get('id');
    const view = urlParams.get('view');

    if (view !== 'versions') {
        return; // Exit if not in versions view
    }

    if (!shortcutId) {
        console.error('Shortcut ID is not defined or invalid.');
        return;
    }
    initializeVersionsFilters(shortcutId);
});

function initializeVersionsFilters(shortcutId) {
    jQuery('#versions-filters').on('change', 'select, input', function() {  
        fetchVersions(shortcutId);
    });

    jQuery('#filter-version-status, #filter-version-deleted, #filter-required-update, #search-versions-input').on('change keyup', function() {
        fetchVersions(shortcutId);
    });

    jQuery('#reset-version-filters').on('click', function() {
        jQuery('#filter-version-status').val('any');
        jQuery('#filter-version-deleted').val('any');
        jQuery('#filter-required-update').val('any');
        jQuery('#search-versions-input').val('');
        fetchVersions(shortcutId);
    });
}