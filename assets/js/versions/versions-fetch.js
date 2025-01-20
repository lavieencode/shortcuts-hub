// Track if we're currently fetching
let isFetching = false;

jQuery(document).ready(function($) {
    // Only proceed if we have the localized data
    if (typeof window.shortcutsHubData === 'undefined') {
        console.error('shortcutsHubData is not defined');
        return;
    }

    // Load shortcut name from sessionStorage if available
    const storedName = sessionStorage.getItem('shortcutName');

    if (storedName) {
        jQuery('#shortcut-name').text(storedName);
    }

    // Fetch versions on page load if we have a shortcut ID
    const shortcutId = shortcutsHubData.shortcutId;
    if (shortcutId) {
        fetchVersions(shortcutId);
    }
});

function fetchVersions(shortcutId, retries = 0) {   
    if (!shortcutId) {
        console.error('No shortcut ID provided');
        return;
    }

    // Prevent parallel requests
    if (isFetching) {
        console.log('Already fetching versions');
        return;
    }

    isFetching = true;

    // Show loading state
    jQuery('#versions-container').html('<p>Loading versions...</p>').show();

    const filterStatus = jQuery('#filter-version-status').val();
    const filterDeleted = jQuery('#filter-version-deleted').val();
    const filterRequiredUpdate = jQuery('#filter-required-update').val();
    const searchTerm = jQuery('#search-versions-input').val();

    // Make the AJAX request
    jQuery.ajax({
        url: window.ajaxurl || shortcutsHubData.ajaxurl || '/wp-admin/admin-ajax.php',
        type: 'POST',
        data: {
            action: 'fetch_versions',
            security: shortcutsHubData.security.fetch_versions,
            id: shortcutId,
            search_term: jQuery('#search-versions-input').val() || '',
            status: jQuery('#filter-version-status').val() || '',
            deleted: jQuery('#filter-version-deleted').val() === 'any' ? '' : jQuery('#filter-version-deleted').val(),
            required_update: jQuery('#filter-version-required-update').val() === 'any' ? '' : jQuery('#filter-version-required-update').val()
        },
        success: function(response) {
            if (response.success && response.data) {
                const shortcut = response.data.shortcut;
                const versions = response.data.versions;

                // Update shortcut name
                if (shortcut && shortcut.name) {
                    jQuery('#shortcut-name').text(shortcut.name);
                    sessionStorage.setItem('shortcutName', shortcut.name);
                }

                // Update versions list
                if (versions) {
                    if (versions.length === 0) {
                        jQuery('#versions-container').html('<p class="no-versions">No versions to show</p>');
                    } else {
                        renderVersions(versions);
                    }
                }

                isFetching = false;
            } else {
                // Handle error case with default message
                jQuery('#versions-container').html('<p class="error">Error fetching versions</p>');
                isFetching = false;
            }
        },
        error: function(xhr, status, error) {
            console.group('Versions API Error');
            console.error('AJAX error fetching versions:', status, error);
            console.error('XHR Object:', xhr);
            console.groupEnd();
            
            isFetching = false;
            handleApiError(xhr);
        }
    });
}

function handleApiError(error) {
    let message = 'Error fetching versions';
    
    if (error && error.responseJSON) {
        message = error.responseJSON.message || message;
    } else if (error && error.statusText) {
        message = error.statusText;
    }
    
    jQuery('#versions-container').html(`<p class="error">${message}</p>`);
}

function fetchVersion(shortcutId, version_id, latest = false) {
    const data = {
        action: 'fetch_version',
        security: shortcutsHubData.security.fetch_version,
        id: shortcutId
    };

    if (latest) {
        data.latest = true;
    } else if (version_id) {
        data.version_id = version_id;
    }

    jQuery.ajax({
        url: window.ajaxurl || shortcutsHubData.ajaxurl || '/wp-admin/admin-ajax.php',
        method: 'POST',
        data: data,
        success: function(response) {
            if (response.success && response.data) {
                // Handle the version data
                console.log('Version data:', response.data);
            } else {
                console.error('Error fetching version:', response.data?.message);
            }
        },
        error: function(xhr, status, error) {
            console.error('AJAX error fetching version:', status, error);
            handleApiError(xhr);
        }
    });
}
