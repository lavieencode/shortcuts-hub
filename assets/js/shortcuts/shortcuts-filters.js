jQuery(document).ready(function() {
    initializeFilters();
});

function initializeFilters() {
    jQuery('#shortcuts-filters').on('change keyup', 'select, input', function() {
        fetchShortcuts();
    });

    jQuery('#reset-filters-button').on('click', function() {
        jQuery('#filter-status').val('');
        jQuery('#filter-deleted').val('any');
        jQuery('#search-input').val('');
        fetchShortcuts();
    });
}