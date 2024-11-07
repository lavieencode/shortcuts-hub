jQuery(document).ready(function() {
    const urlParams = new URLSearchParams(window.location.search);
    const view = urlParams.get('view');
    const shortcutId = urlParams.get('id');

    if (view === 'versions' && shortcutId) {
        fetchVersions(shortcutId);
    }
});

function fetchVersions(shortcutId, retries = 3) {
    const filterStatus = jQuery('#filter-version-status').val();
    const filterDeleted = jQuery('#filter-version-deleted').val();
    const filterRequiredUpdate = jQuery('#filter-required-update').val();
    const searchTerm = jQuery('#search-versions-input').val();

    const data = {
        action: 'fetch_versions',
        security: shortcutsHubData.security,
        shortcut_id: shortcutId,
        status: filterStatus,
        required_update: filterRequiredUpdate === 'true',
        search_term: searchTerm
    };

    if (filterDeleted === 'true') {
        data.deleted = true;
    } else if (filterDeleted === 'false') {
        data.deleted = false;
    }

    console.log('Sending AJAX request with data:', data);

    jQuery.ajax({
        url: shortcutsHubData.ajax_url,
        method: 'POST',
        data: data,
        success: function(response) {
            console.log('AJAX response:', response);
            if (response.success && Array.isArray(response.data.versions)) {
                renderVersions(response.data.versions, shortcutId);
            } else {
                console.error('No versions found or unexpected response:', response);
                jQuery('#versions-container').html('<p>No versions found.</p>');
            }
        },
        error: function(xhr, status, error) {
            console.error('Error loading versions:', status, error);
            if (retries > 0) {
                console.log(`Retrying... (${3 - retries + 1})`);
                fetchVersions(shortcutId, retries - 1);
            } else {
                jQuery('#versions-container').html('<p>Error loading versions. Please try again later.</p>');
            }
        }
    });
}