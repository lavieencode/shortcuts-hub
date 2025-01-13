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
    
    // DEBUG: Log the initial state of all filters when the page first loads
    sh_debug_log('Initial Filter State', {
        message: 'Initial filter state on page load',
        source: {
            file: 'shortcuts-filters.js',
            line: 20,
            function: 'initializeFilters'
        },
        data: {
            status: jQuery('#filter-status').val(),
            deleted: jQuery('#filter-deleted').val(),
            search: jQuery('#search-input').val()
        },
        debug: true
    });
    
    // Initial fetch with default values
    fetchShortcutsFromSource('WP');
    
    // Debounced version of fetchShortcuts
    const debouncedFetch = debounce(() => {
        const filterStatus = jQuery('#filter-status').val();
        const filterDeleted = jQuery('#filter-deleted').val();
        const searchTerm = jQuery('#search-input').val();

        // DEBUG: Log filter state before fetch
        sh_debug_log('Fetching shortcuts with filters', {
            message: 'Preparing to fetch shortcuts with current filters',
            source: {
                file: 'shortcuts-filters.js',
                line: 45,
                function: 'debouncedFetch'
            },
            data: {
                filters: {
                    status: filterStatus,
                    deleted: filterDeleted,
                    search: searchTerm
                }
            },
            debug: true
        });
        
        fetchShortcutsFromSource('WP');
    }, 500);

    // Handle dropdown filters - immediate response
    jQuery('#filter-status, #filter-deleted').on('change', function() {
        const filterType = this.id === 'filter-status' ? 'Status' : 'Deleted';
        const newValue = this.value;
        
        // DEBUG: Log dropdown filter change
        sh_debug_log('Dropdown filter changed', {
            message: 'User changed a dropdown filter',
            source: {
                file: 'shortcuts-filters.js',
                line: 70,
                function: 'initializeFilters'
            },
            data: {
                filter_type: filterType,
                old_value: this.defaultValue,
                new_value: newValue,
                all_filters: {
                    status: jQuery('#filter-status').val(),
                    deleted: jQuery('#filter-deleted').val(),
                    search: jQuery('#search-input').val()
                }
            },
            debug: true
        });
        
        fetchShortcutsFromSource('WP');
    });

    // Handle search input with debouncing
    jQuery('#search-input').on('input', function() {
        const searchValue = this.value;
        
        // DEBUG: Log search input change
        sh_debug_log('Search input changed', {
            message: 'User typing in search box',
            source: {
                file: 'shortcuts-filters.js',
                line: 95,
                function: 'initializeFilters'
            },
            data: {
                searchValue: searchValue,
                allFilters: {
                    status: jQuery('#filter-status').val(),
                    deleted: jQuery('#filter-deleted').val(),
                    search: searchValue
                }
            },
            debug: true
        });
        
        debouncedFetch();
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
        
        // DEBUG: Log filter reset
        sh_debug_log('Filters reset', {
            message: 'User reset all filters to default values',
            source: {
                file: 'shortcuts-filters.js',
                line: 130,
                function: 'initializeFilters'
            },
            data: {
                old_state: oldState,
                new_state: {
                    status: 'publish',
                    deleted: 'false',
                    search: ''
                }
            },
            debug: true
        });
        
        fetchShortcutsFromSource('WP');
    });
}