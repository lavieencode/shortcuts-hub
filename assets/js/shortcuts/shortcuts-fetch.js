jQuery(document).ready(function() {
    const urlParams = new URLSearchParams(window.location.search);
    const view = urlParams.get('view');

    if (view !== 'versions') {
        fetchShortcuts();
    }
});

function fetchShortcuts() {
    const filterStatus = jQuery('#filter-status').val();
    const filterDeleted = jQuery('#filter-deleted').val();
    const searchTerm = jQuery('#search-input').val();

    console.log('Fetching shortcuts with filters:', {
        status: filterStatus,
        deleted: filterDeleted,
        search: searchTerm,
        statusElement: document.getElementById('filter-status'),
        deletedElement: document.getElementById('filter-deleted')
    });

    const data = {
        action: 'fetch_shortcuts',
        security: shortcutsHubData.security,
        filter: searchTerm || '',
        status: filterStatus || '',
        deleted: filterDeleted === 'true' ? true : (filterDeleted === 'false' ? false : null)
    };

    console.log('Sending AJAX request with data:', data);

    jQuery.ajax({
        url: shortcutsHubData.ajax_url,
        method: 'POST',
        data: data,
        success: function(response) {
            console.log('Received response:', response);
            if (response.success) {
                console.log('Rendering shortcuts:', response.data);
                console.log('Number of shortcuts:', response.data.length);
                renderShortcuts(response.data);
            } else {
                console.error('Error fetching shortcuts:', response.data.message);
                jQuery('#shortcuts-container').html('<div class="no-shortcuts">No shortcuts found</div>');
            }
        },
        error: function() {
            jQuery('#shortcuts-container').html('<div class="no-shortcuts">No shortcuts found</div>');
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