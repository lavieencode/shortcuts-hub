jQuery(document).ready(function($) {
    const urlParams = new URLSearchParams(window.location.search);
    const view = urlParams.get('view');
    const shortcutId = urlParams.get('id');

    if (view === 'versions' && shortcutId) {
        fetchVersions(shortcutId);
    }

    if (shortcutsHubData.shortcut_id) {
        fetchVersion(shortcutsHubData.shortcut_id, true);
    }
});

function fetchVersions(shortcutId, retries = 3) {
    jQuery('#versions-container').hide();
    jQuery('#shortcut-name-display').hide();

    const filterStatus = jQuery('#filter-version-status').val();
    const filterDeleted = jQuery('#filter-version-deleted').val();
    const filterRequiredUpdate = jQuery('#filter-required-update').val();
    const searchTerm = jQuery('#search-versions-input').val();

    const data = {
        action: 'fetch_versions',
        security: shortcutsHubData.security,
        shortcut_id: shortcutId,
        search_term: searchTerm,
        status: filterStatus,
        deleted: filterDeleted === 'true',
        required_update: filterRequiredUpdate === 'true'
    };

    jQuery.post(shortcutsHubData.ajax_url, data, function(response) {
        if (response.success) {
            renderVersions(response.data, shortcutId);
            jQuery('#shortcut-name-display').text(response.data.shortcut.name).show();
            jQuery('#versions-container').show();
        } else {
            jQuery('#versions-container').html('<p>Error loading versions. Please try again later.</p>').show();
        }
    }).fail(function(xhr, status, error) {
        if (retries > 0) {
            fetchVersions(shortcutId, retries - 1);
        } else {
            jQuery('#versions-container').html('<p>Error loading versions. Please try again later.</p>').show();
        }
    });
}

function fetchVersion(shortcutId, versionId, latest = false) {
    const data = {
        action: 'fetch_version',
        security: shortcutsHubData.security,
        shortcut_id: shortcutId
    };

    if (versionId) {
        data.version_id = versionId;
    }

    if (latest) {
        data.latest = true;
    }

    jQuery.ajax({
        url: shortcutsHubData.ajax_url,
        method: 'POST',
        data: data,
        success: function(response) {
            if (response.success && response.data) {
                populateEditVersionForm(response.data);
            } else {
                console.error('Error fetching version:', response.data ? response.data.message : 'Unknown error');
            }
        },
        error: function(xhr, status, error) {
            console.error('AJAX error fetching version:', status, error);
            console.error('Response Text:', xhr.responseText);
        }
    });
}
