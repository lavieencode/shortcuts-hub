jQuery(document).ready(function() {
    fetchShortcuts();
});

function fetchShortcuts() {
    const filterStatus = jQuery('#filter-status').val();
    const filterDeleted = jQuery('#filter-deleted').val();
    const searchTerm = jQuery('#search-input').val();

    const data = {
        action: 'fetch_shortcuts',
        security: shortcutsHubData.security,
        filter: searchTerm,
        status: filterStatus,
        deleted: filterDeleted === 'true' ? true : (filterDeleted === 'false' ? false : null)
    };

    jQuery.ajax({
        url: shortcutsHubData.ajax_url,
        method: 'POST',
        data: data,
        success: function(response) {
            if (response.success) {
                renderShortcuts(response.data);
            } else {
                console.error('Error fetching shortcuts:', response.data.message);
                jQuery('#shortcuts-container').html('<p>No shortcuts found.</p>');
            }
        },
        error: function(xhr, status, error) {
            console.error('Error loading shortcuts:', status, error);
            jQuery('#shortcuts-container').html('<p>Error loading shortcuts. Please try again later.</p>');
        }
    });
}