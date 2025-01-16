// Initial page load fetch
jQuery(document).ready(function() {
    const urlParams = new URLSearchParams(window.location.search);
    const view = urlParams.get('view');

    if (view !== 'versions') {
        // Wait for debug session before fetching
        jQuery(document).one('sh_debug_ready', function() {
            // Only fetch WordPress shortcuts on initial load
            fetchShortcutsFromSource('WP');
        });
    }
});

function fetchShortcuts() {
    // Only fetch Switchblade shortcuts when refresh is clicked
    fetchShortcutsFromSource('SB');
}

function fetchShortcutsFromSource(source) {
    const filterStatus = jQuery('#filter-status').val();
    const filterDeleted = jQuery('#filter-deleted').val();
    const searchTerm = jQuery('#search-input').val();

    const data = {
        action: 'fetch_shortcuts',
        security: shortcutsHubData.security.fetch_shortcuts,
        filter: searchTerm || '',
        status: filterStatus || '',
        deleted: filterDeleted === 'true' ? true : (filterDeleted === 'false' ? false : null),
        source: source
    };

    // DEBUG: Log the current filter parameters and security token
    sh_debug_log('Filter Parameters', {
        message: 'Preparing to fetch shortcuts',
        source: {
            file: 'shortcuts-fetch.js',
            function: 'fetchShortcutsFromSource'
        },
        data: {
            filter_status: filterStatus,
            filter_deleted: filterDeleted,
            search_term: searchTerm,
            source: source,
            security_token: shortcutsHubData.security.fetch_shortcuts
        },
        debug: true
    });

    jQuery.ajax({
        url: shortcutsHubData.ajax_url,
        method: 'POST',
        data: data,
        success: function(response) {
            if (response.success) {
                if (source === 'WP') {
                    // Store shortcuts globally for re-rendering
                    window.currentShortcuts = response.data;
                    
                    // DEBUG: Log the results
                    sh_debug_log('Filter Results', {
                        message: 'Successfully fetched shortcuts',
                        source: {
                            file: 'shortcuts-fetch.js',
                            function: 'fetchShortcutsFromSource'
                        },
                        data: {
                            total_shortcuts: response.data.length,
                            filter_params: data,
                            shortcuts: response.data
                        },
                        debug: true
                    });
                    
                    renderShortcuts(response.data);
                }
            } else {
                // DEBUG: Log any errors
                sh_debug_log('Filter Error', {
                    message: 'Failed to fetch shortcuts',
                    source: {
                        file: 'shortcuts-fetch.js',
                        function: 'fetchShortcutsFromSource'
                    },
                    data: {
                        error: response.data.message,
                        filter_params: data
                    },
                    debug: true
                });
                
                console.error('Error fetching shortcuts:', response.data.message);
                if (source === 'WP') {
                    renderShortcuts([]);
                }
            }
        },
        error: function(xhr, status, error) {
            if (source === 'WP') {
                renderShortcuts([]);
            }
            
            // DEBUG: Log AJAX errors
            sh_debug_log('Ajax Error', {
                message: 'AJAX request failed',
                source: {
                    file: 'shortcuts-fetch.js',
                    function: 'fetchShortcutsFromSource'
                },
                data: {
                    error: error,
                    status: status,
                    filter_params: data
                },
                debug: true
            });
            
            console.error('Ajax error fetching shortcuts from ' + source);
        }
    });
}