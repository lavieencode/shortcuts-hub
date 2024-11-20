jQuery(document).ready(function($) {
    const urlParams = new URLSearchParams(window.location.search);
    const view = urlParams.get('view');
    const sb_id = urlParams.get('id');

    if (view === 'versions' && sb_id) {
        console.log('Fetching versions for shortcut ID on page load:', sb_id);
        fetchVersions(sb_id);
    }

});

function fetchVersions(sb_id, retries = 3) {   
    console.log('Fetching versions for shortcut ID:', sb_id);
    jQuery('#versions-container').hide();
    jQuery('#shortcut-name-display').hide();

    const filterStatus = jQuery('#filter-version-status').val();
    const filterDeleted = jQuery('#filter-version-deleted').val();
    const filterRequiredUpdate = jQuery('#filter-required-update').val();
    const searchTerm = jQuery('#search-versions-input').val();

    const data = {
        action: 'fetch_versions',  
        security: shortcutsHubData.security,
        id: sb_id,  
        search_term: searchTerm,
        status: filterStatus
    };

    if (filterDeleted === 'true') {
        data.deleted = true;
    } else if (filterDeleted === 'false') {
        data.deleted = false;
    }

    if (filterRequiredUpdate !== '') {
        data.required_update = filterRequiredUpdate === 'true';
    }

    jQuery.post(shortcutsHubData.ajax_url, data, function(response) {
        if (response.success) {
            const shortcutData = response.data.shortcut || {};
            const shortcutName = shortcutData.name || '';
            jQuery('#shortcut-name-display').text(shortcutName).show();
            sessionStorage.setItem('shortcutName', shortcutName);
            const sb_id = shortcutData.id || sb_id;  
            if (response.data && response.data.versions && response.data.versions.length > 0) {
                jQuery('#versions-container').empty();
                renderVersions(response.data.versions, sb_id);
                jQuery('#versions-container').show();
            } else {
                jQuery('#versions-container').html('<p>No versions to list.</p>').show();
            }
        } else {
            jQuery('#versions-container').html('<p>Error loading versions. Please try again later.</p>').show();
        }
    }).fail(function(xhr, status, error) {
        console.error('AJAX error fetching versions:', status, error);
        console.error('Response Text:', xhr.responseText);
        if (retries > 0) {
            fetchVersions(sb_id, retries - 1);
        } else {
            jQuery('#versions-container').html('<p>Error loading versions. Please try again later.</p>').show();
        }
    });
}

function fetchVersion(sb_id, version_id, latest = false) {
    const data = {
        action: 'fetch_version',
        security: shortcutsHubData.security,
        id: sb_id
    };

    if (version_id) {
        data.version_id = version_id;
    }

    if (latest) {
        data.latest = true;
    }

    jQuery.ajax({
        url: shortcutsHubData.ajax_url,
        method: 'POST',
        data: data,
        success: function(response) {
            console.log('Response from server:', response);
            if (response.success && response.data) {
                populateVersionEditModal(response.data);
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
