// Debounce function to limit rate of function calls
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

jQuery(document).ready(function() {
    initializeFilters();
});

function initializeFilters() {
    // Set default values
    jQuery('#filter-status').val('publish');
    jQuery('#filter-deleted').val('false');
    
    // Initial fetch with default values
    fetchShortcutsFromSource('WP');
    
    // Debounced version of fetchShortcuts
    const debouncedFetch = debounce(() => {
        const filterStatus = jQuery('#filter-status').val();
        const filterDeleted = jQuery('#filter-deleted').val();
        const searchTerm = jQuery('#search-input').val();
        
        fetchShortcutsFromSource('WP');
    }, 500);

    // Handle dropdown filters - immediate response
    jQuery('#filter-status, #filter-deleted').on('change', function() {
        const filterType = this.id === 'filter-status' ? 'Status' : 'Deleted';
        const newValue = this.value;
        fetchShortcutsFromSource('WP');
    });

    // Handle search input with debouncing
    jQuery('#search-input').on('input', function() {
        const searchValue = this.value;
    });
    

    // Handle reset button
    jQuery('#reset-filters').on('click', function() {
        const oldState = {
            status: jQuery('#filter-status').val(),
            deleted: jQuery('#filter-deleted').val(),
            search: jQuery('#search-input').val()
        };
        
        jQuery('#filter-status').val('publish');
        jQuery('#filter-deleted').val('false');
        jQuery('#search-input').val('');
        
        fetchShortcutsFromSource('WP');
    });    
}