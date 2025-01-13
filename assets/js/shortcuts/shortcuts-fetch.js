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
        security: shortcutsHubData.security,
        filter: searchTerm || '',
        status: filterStatus || '',
        deleted: filterDeleted === 'true' ? true : (filterDeleted === 'false' ? false : null),
        source: source
    };

    // DEBUG: Log the current filter parameters before making the AJAX request to track what filters are being applied
    sh_debug_log('Filter Parameters', {
        filter_status: filterStatus,
        filter_deleted: filterDeleted,
        search_term: searchTerm,
        source: source,
        debug: false
    });

    jQuery.ajax({
        url: shortcutsHubData.ajax_url,
        method: 'POST',
        data: data,
        success: function(response) {
            if (response.success) {
                if (source === 'WP') {
                    // DEBUG: Log the results of the filter operation, including total count and full shortcut data for verification
                    sh_debug_log('Filter Results', {
                        total_shortcuts: response.data.length,
                        filter_params: data,
                        shortcuts: response.data,
                        debug: false
                    });
                    renderShortcuts(response.data);
                }
                // For Switchblade, we just want to log the data
                console.log('Switchblade shortcuts:', response.data);
            } else {
                // DEBUG: Log any errors that occur during the filter operation to help diagnose filter failures
                sh_debug_log('Filter Error', {
                    error: response.data.message,
                    filter_params: data,
                    debug: false
                });
                console.error('Error fetching shortcuts:', response.data.message);
                if (source === 'WP') {
                    jQuery('#shortcuts-container').html('<div class="no-shortcuts">No shortcuts found</div>');
                }
            }
        },
        error: function(xhr, status, error) {
            if (source === 'WP') {
                jQuery('#shortcuts-container').html('<div class="no-shortcuts">No shortcuts found</div>');
            }
            // DEBUG: Log AJAX-specific errors that occur during the filter request for network-related issues
            sh_debug_log('Ajax Error', {
                error: error,
                status: status,
                filter_params: data,
                debug: false
            });
            console.error('Ajax error fetching shortcuts from ' + source);
        }
    });
}

function fetchShortcut(shortcutId) {
    const data = {
        action: 'fetch_shortcut',
        security: shortcutsHubData.security,
        id: shortcutId
    };

    jQuery.ajax({
        url: shortcutsHubData.ajax_url,
        method: 'POST',
        data: data,
        success: function(response) {
            if (response.success) {
                populateEditModal(response.data);
            } else {
                console.error('Error fetching shortcut:', response.data.message);
            }
        },
        error: function() {
            console.error('Error loading shortcut');
        }
    });
}