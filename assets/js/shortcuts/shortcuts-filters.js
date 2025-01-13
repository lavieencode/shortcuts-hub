jQuery(document).ready(function() {
    initializeFilters();
});

function initializeFilters() {
    // Set default values
    jQuery('#filter-status').val('publish');
    jQuery('#filter-deleted').val('false');
    
    // DEBUG: Log the initial state of all filters when the page first loads to establish a baseline
    sh_debug_log('Initial Filter State', {
        status: jQuery('#filter-status').val(),
        deleted: jQuery('#filter-deleted').val(),
        search: jQuery('#search-input').val(),
        debug: false
    });
    
    // Initial fetch with default values
    fetchShortcutsFromSource('WP');
    
    // Handle filter changes
    jQuery('#filter-status, #filter-deleted').on('change', function() {
        const filterType = this.id === 'filter-status' ? 'Status' : 'Deleted';
        const newValue = this.value;
        
        // DEBUG: Log when a user changes either the status or deleted filter, capturing both old and new values for change tracking
        sh_debug_log('Filter Changed', {
            filter_type: filterType,
            old_value: this.defaultValue,
            new_value: newValue,
            all_filters: {
                status: jQuery('#filter-status').val(),
                deleted: jQuery('#filter-deleted').val(),
                search: jQuery('#search-input').val()
            },
            debug: false
        });
        
        fetchShortcutsFromSource('WP');
    });

    // Handle search input
    let searchTimeout;
    jQuery('#search-input').on('keyup', function() {
        clearTimeout(searchTimeout);
        const searchValue = this.value;
        
        searchTimeout = setTimeout(() => {
            // DEBUG: Log search term changes after the user stops typing (debounced) to track search filter usage
            sh_debug_log('Search Changed', {
                search_term: searchValue,
                all_filters: {
                    status: jQuery('#filter-status').val(),
                    deleted: jQuery('#filter-deleted').val(),
                    search: searchValue
                },
                debug: false
            });
            
            fetchShortcutsFromSource('WP');
        }, 300); // Debounce search for 300ms
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
        
        // DEBUG: Log when filters are reset to default values, capturing both the previous and new states
        sh_debug_log('Filters Reset', {
            old_state: oldState,
            new_state: {
                status: 'publish',
                deleted: 'false',
                search: ''
            },
            debug: false
        });
        
        fetchShortcutsFromSource('WP');
    });
}