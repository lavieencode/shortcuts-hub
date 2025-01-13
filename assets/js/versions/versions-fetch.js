// Track if we're currently fetching
let isFetching = false;

jQuery(document).ready(function($) {
    // Load shortcut name from sessionStorage if available
    const storedName = sessionStorage.getItem('shortcutName');
    if (storedName) {
        jQuery('#shortcut-name-display').text(storedName).show();
    }
});

function fetchVersions(shortcutId, retries = 0) {   
    if (!shortcutId) {
        console.error('No shortcut ID provided');
        return;
    }

    // DEBUG: Log fetch attempt
    sh_debug_log('Fetching versions', {
        message: 'Attempting to fetch versions',
        source: {
            file: 'versions-fetch.js',
            line: 15,
            function: 'fetchVersions'
        },
        data: {
            shortcutId: shortcutId,
            security: shortcutsHubData.versions_security,
            retries: retries
        },
        debug: false
    });

    // Prevent parallel requests
    if (isFetching) {
        console.log('Already fetching versions, skipping request');
        return;
    }

    // Check if we're rate limited
    const rateLimitUntil = sessionStorage.getItem('rateLimitUntil');
    if (rateLimitUntil && parseInt(rateLimitUntil) > Date.now()) {
        const minutes = Math.ceil((parseInt(rateLimitUntil) - Date.now()) / (1000 * 60));
        jQuery('#versions-container').html(`<p>Rate limit exceeded. Please try again in ${minutes} minutes.</p>`);
        return;
    }

    isFetching = true;

    jQuery('#versions-container').hide();
    
    // Show loading state
    jQuery('#versions-container').html('<p>Loading versions...</p>').show();

    const filterStatus = jQuery('#filter-version-status').val();
    const filterDeleted = jQuery('#filter-version-deleted').val();
    const filterRequiredUpdate = jQuery('#filter-required-update').val();
    const searchTerm = jQuery('#search-versions-input').val();

    const data = {
        action: 'fetch_versions',  
        security: shortcutsHubData.versions_security,
        id: shortcutId,  
        search_term: searchTerm || '',
        status: filterStatus || '',
        deleted: filterDeleted === 'any' ? '' : filterDeleted,
        required_update: filterRequiredUpdate === 'any' ? '' : filterRequiredUpdate
    };

    // DEBUG: Log request data
    sh_debug_log('Making AJAX request', {
        message: 'Sending AJAX request to fetch versions',
        source: {
            file: 'versions-fetch.js',
            line: 70,
            function: 'fetchVersions'
        },
        data: data,
        debug: false
    });

    jQuery.post(shortcutsHubData.ajax_url, data)
        .done(function(response) {
            console.group('Versions API Response');
            console.log('Full Response:', response);
            console.log('Success Status:', response.success);
            console.log('Response Data:', response.data);
            if (response.data) {
                console.log('Shortcut Data:', response.data.shortcut);
                console.log('Versions Data:', response.data.versions);
            }
            console.groupEnd();

            isFetching = false;
            if (response.success && response.data) {
                const shortcutData = response.data.shortcut || {};
                const versions = response.data.versions || [];
                const shortcutName = shortcutData.name || '';
                
                // Always show the name if we have it from the API
                if (shortcutName) {
                    jQuery('#shortcut-name-display').text(shortcutName).show();
                    // Store it for future page loads/refreshes
                    sessionStorage.setItem('shortcutName', shortcutName);
                }

                // Handle versions display
                if (versions.length > 0) {
                    jQuery('#versions-container').empty();
                    renderVersions(versions, shortcutId);
                    jQuery('#versions-container').show();
                } else {
                    console.warn('No versions found in response');
                    jQuery('#versions-container').html('<p>No versions found.</p>');
                }
            } else {
                const errorMessage = response.data?.message || 'Error loading versions. Please try again later.';
                console.error('API Response Error:', errorMessage);
                jQuery('#versions-container').html(`<p>${errorMessage}</p>`);
            }
        })
        .fail(function(xhr, status, error) {
            console.group('Versions API Error');
            console.error('AJAX error fetching versions:', status, error);
            console.error('XHR Object:', xhr);
            console.error('Response Text:', xhr.responseText);
            console.error('Status Code:', xhr.status);
            console.error('Headers:', xhr.getAllResponseHeaders());
            console.groupEnd();
            
            let errorMessage = 'Failed to load versions. Please try again later.';
            
            // Handle rate limiting
            if (xhr.responseText && xhr.responseText.includes('rate limit')) {
                const oneHour = Date.now() + (60 * 60 * 1000);
                sessionStorage.setItem('rateLimitUntil', oneHour.toString());
                errorMessage = 'Rate limit exceeded. Please try again in 1 hour.';
            } 
            // Only retry on specific server errors, not on client errors or rate limits
            else if (retries > 0 && xhr.status >= 500 && xhr.status < 600) {
                console.log('Retrying fetchVersions, attempts remaining:', retries - 1);
                setTimeout(() => {
                    isFetching = false;
                    fetchVersions(shortcutId, retries - 1);
                }, 3000); // Fixed 3 second delay between retries
                return;
            }
            
            isFetching = false;
            handleApiError(xhr);
        });
}

function handleApiError(error) {
    console.error('API Error:', error);
    let errorMessage = 'Error fetching versions. ';
    
    if (error.responseJSON && error.responseJSON.message) {
        if (error.responseJSON.message.includes('viewAnyDraftShortcut')) {
            errorMessage += 'Permission error occurred. Please try refreshing the page.';
        } else if (error.responseJSON.message.includes('Too many login attempts')) {
            errorMessage += 'Rate limit reached. Please wait a few minutes and try again.';
        } else {
            errorMessage += error.responseJSON.message;
        }
    } else if (error.status === 429) {
        errorMessage += 'Rate limit reached. Please wait a few minutes and try again.';
    } else if (error.status >= 500) {
        errorMessage += 'Server error occurred. Please try again later.';
    }

    jQuery('#versions-container').html(`<p>${errorMessage}</p>`).show();
}

function fetchVersion(shortcutId, version_id, latest = false) {
    const data = {
        action: 'fetch_version',
        security: shortcutsHubData.security,
        id: shortcutId
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
