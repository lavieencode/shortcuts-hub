jQuery(document).ready(function() {
    initializeFilters();
});

function initializeFilters() {
    // Set default values
    jQuery('#filter-status').val('publish');
    jQuery('#filter-deleted').val('false');
    
    // Handle filter changes
    jQuery('#filter-status, #filter-deleted').on('change', function() {
        console.log('Filter changed:', {
            element: this.id,
            value: this.value,
            label: this.options[this.selectedIndex].text
        });
        fetchShortcuts();
    });

    // Handle search input
    jQuery('#search-input').on('keyup', function() {
        console.log('Search input changed:', {
            value: this.value
        });
        fetchShortcuts();
    });

    // Handle reset button
    jQuery('#reset-filters').on('click', function() {
        jQuery('#filter-status').val('publish');
        jQuery('#filter-deleted').val('false');
        jQuery('#search-input').val('');
        fetchShortcuts();
    });
}