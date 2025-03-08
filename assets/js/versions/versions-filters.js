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
    const urlParams = new URLSearchParams(window.location.search);
    const shortcutId = urlParams.get('sb_id');
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
    // Debounced version of fetchVersions
    const debouncedFetch = debounce((shortcutId) => {
        const filterStatus = jQuery('#filter-version-status').val();
        const filterDeleted = jQuery('#filter-version-deleted').val();
        const filterRequiredUpdate = jQuery('#filter-required-update').val();
        const searchTerm = jQuery('#search-versions-input').val();

        // DEBUG: Log filter change
        sh_debug_log('Version filters changed', {
            message: 'User changed version filters',
            source: {
                file: 'versions-filters.js',
                line: 25,
                function: 'initializeVersionsFilters'
            },
            data: {
                shortcutId: shortcutId,
                filters: {
                    status: filterStatus,
                    deleted: filterDeleted,
                    required_update: filterRequiredUpdate,
                    search_term: searchTerm
                }
            },
            debug: true
        });
        
        fetchVersions(shortcutId);
    }, 500); // Wait 500ms after last keystroke before searching

    // For dropdown filters, trigger immediately
    jQuery('#filter-version-status, #filter-version-deleted, #filter-required-update').on('change', function() {
        const filterStatus = jQuery('#filter-version-status').val();
        const filterDeleted = jQuery('#filter-version-deleted').val();
        const filterRequiredUpdate = jQuery('#filter-required-update').val();
        const searchTerm = jQuery('#search-versions-input').val();

        // DEBUG: Log dropdown filter change
        sh_debug_log('Dropdown filter changed', {
            message: 'User changed a dropdown filter',
            source: {
                file: 'versions-filters.js',
                line: 55,
                function: 'initializeVersionsFilters'
            },
            data: {
                shortcutId: shortcutId,
                changedFilter: {
                    element: jQuery(this).attr('id'),
                    value: jQuery(this).val()
                },
                allFilters: {
                    status: filterStatus,
                    deleted: filterDeleted,
                    required_update: filterRequiredUpdate,
                    search_term: searchTerm
                }
            },
            debug: true
        });
        
        fetchVersions(shortcutId);
    });

    // For search input, use debouncing
    jQuery('#search-versions-input').on('input', function() {
        // DEBUG: Log search input change
        sh_debug_log('Search input changed', {
            message: 'User typing in search box',
            source: {
                file: 'versions-filters.js',
                line: 85,
                function: 'initializeVersionsFilters'
            },
            data: {
                shortcutId: shortcutId,
                searchValue: jQuery(this).val()
            },
            debug: true
        });
        
        debouncedFetch(shortcutId);
    });

    jQuery('#reset-version-filters').on('click', function() {
        // Store old values for logging
        const oldState = {
            status: jQuery('#filter-version-status').val(),
            deleted: jQuery('#filter-version-deleted').val(),
            required_update: jQuery('#filter-required-update').val(),
            search_term: jQuery('#search-versions-input').val()
        };

        // Reset all filters to 'any' to match PHP expectations
        jQuery('#filter-version-status').val('any').trigger('change');
        jQuery('#filter-version-deleted').val('any').trigger('change');
        jQuery('#filter-required-update').val('any').trigger('change');
        jQuery('#search-versions-input').val('').trigger('input');

        // DEBUG: Log filter reset
        sh_debug_log('Version filters reset', {
            message: 'User reset all filters',
            source: {
                file: 'versions-filters.js',
                line: 85,
                function: 'initializeVersionsFilters'
            },
            data: {
                shortcutId: shortcutId,
                old_state: oldState,
                new_state: {
                    status: 'any',
                    deleted: 'any',
                    required_update: 'any',
                    search_term: ''
                }
            },
            debug: true
        });
        
        // Fetch versions with reset filters
        fetchVersions(shortcutId);
    });
}