jQuery(document).ready(function() {
    // Check for URL parameters and fetch versions if needed
    const urlParams = new URLSearchParams(window.location.search);
    const view = urlParams.get('view');
    const id = urlParams.get('id');
    
    if (view === 'versions' && id) {
        // Show versions view
        jQuery('#shortcuts-view').hide();
        jQuery('#versions-view').show();
        
        // Fetch versions
        sh_debug_log('Fetching versions', {
            message: 'Fetching versions for shortcut',
            source: {
                file: 'versions-handlers.js',
                line: 40,
                function: 'document.ready'
            },
            data: {
                shortcutId: id
            },
            debug: true
        });
        
        fetchVersions(id);
    }
    
    // Use the localized data instead of checking URL again
    if (typeof shortcutsHubData !== 'undefined') {
        // DEBUG: Track shortcutsHubData
        sh_debug_log('Localized data check', {
            message: 'Checking shortcutsHubData availability',
            source: {
                file: 'versions-handlers.js',
                line: 15,
                function: 'document.ready'
            },
            data: shortcutsHubData,
            debug: true
        });

        if (shortcutsHubData.initialView === 'versions' && shortcutsHubData.shortcutId) {
            // DEBUG: Track versions view detection
            sh_debug_log('Versions view detection', {
                message: 'Versions view detected from localized data',
                source: {
                    file: 'versions-handlers.js',
                    line: 25,
                    function: 'document.ready'
                },
                data: shortcutsHubData,
                debug: true
            });
            
            // Show versions view immediately
            jQuery('#shortcuts-view').hide();
            jQuery('#versions-view').show();
            
            // Fetch versions with debug logging
            sh_debug_log('Fetching versions', {
                message: 'Initiating versions fetch',
                source: {
                    file: 'versions-handlers.js',
                    line: 40,
                    function: 'document.ready'
                },
                data: {
                    shortcutId: shortcutsHubData.shortcutId
                },
                debug: true
            });
            
            fetchVersions(shortcutsHubData.shortcutId);
        }
    } else {
        // DEBUG: Track missing shortcutsHubData
        sh_debug_log('Missing localized data', {
            message: 'shortcutsHubData is not defined',
            source: {
                file: 'versions-handlers.js',
                line: 55,
                function: 'document.ready'
            },
            debug: true
        });
    }
    
    attachVersionHandlers();
});

function fetchVersions(shortcutId) {
    // DEBUG: Track fetch versions call
    sh_debug_log('Fetch versions', {
        message: 'fetchVersions function called',
        source: {
            file: 'versions-handlers.js',
            line: 70,
            function: 'fetchVersions'
        },
        data: {
            shortcutId: shortcutId
        },
        debug: true
    });

    jQuery.ajax({
        url: ajaxurl,
        type: 'POST',
        data: {
            action: 'fetch_versions',
            shortcut_id: shortcutId,
            nonce: shortcutsHubData.nonce
        },
        success: function(response) {
            // DEBUG: Track successful versions fetch
            sh_debug_log('Versions fetch success', {
                message: 'Successfully fetched versions',
                source: {
                    file: 'versions-handlers.js',
                    line: 90,
                    function: 'fetchVersions.success'
                },
                data: {
                    response: response,
                    shortcutId: shortcutId
                },
                debug: true
            });

            if (response.success) {
                displayVersions(response.data);
            } else {
                console.error('Error fetching versions:', response.data.message);
            }
        },
        error: function(xhr, status, error) {
            // DEBUG: Track failed versions fetch
            sh_debug_log('Versions fetch error', {
                message: 'Failed to fetch versions',
                source: {
                    file: 'versions-handlers.js',
                    line: 110,
                    function: 'fetchVersions.error'
                },
                data: {
                    xhr: xhr,
                    status: status,
                    error: error,
                    shortcutId: shortcutId
                },
                debug: true
            });
            
            console.error('AJAX error:', status, error);
        }
    });
}

function attachVersionHandlers() {
    // Back to shortcuts button handler
    jQuery(document).on('click', '#back-to-shortcuts', function() {
        const urlParams = new URLSearchParams(window.location.search);
        urlParams.delete('view');
        urlParams.delete('id');
        window.history.pushState({}, '', `${window.location.pathname}?${urlParams}`);
        
        toggleVersionsView(false);
    });

    jQuery(document).on('click', '.version-header', function() {
        const versionBody = jQuery(this).next('.version-body');
        versionBody.toggle();

        const caret = jQuery(this).find('.caret');
        caret.html(versionBody.is(':visible') ? '&#9660;' : '&#9654;');
    });

    jQuery(document).on('click', '.edit-version', function(event) {
        const button = jQuery(event.target);
        const versionData = button.data('version');

        if (versionData) {
            populateVersionEditModal({ version: versionData });
            jQuery('body').addClass('modal-open');
            jQuery('#edit-version-modal').addClass('active').show();
        } else {
            console.error('Version data not found on button');
        }
    });

    jQuery('#edit-version-modal .save-button').on('click', function(event) {
        event.preventDefault();
        updateVersion('save');
    });

    jQuery('#edit-version-modal .publish-button').on('click', function(event) {
        event.preventDefault();
        updateVersion('publish');
    });

    jQuery('#edit-version-modal .delete-button').on('click', function(event) {
        event.preventDefault();
        toggleVersionDelete(jQuery('#edit-version-form #shortcut-id').val(), jQuery('#edit-version-form #version-id').val(), false);
    });

    jQuery('#edit-version-modal .cancel-button').on('click', function() {
        jQuery('#edit-version-modal').removeClass('active').hide();
        jQuery('body').removeClass('modal-open');
    });

    jQuery(document).on('click', '.delete-version, .restore-version', function() {
        const shortcutId = jQuery(this).closest('.version-item').data('shortcut-id');
        const versionId = jQuery(this).closest('.version-item').data('version-id');
        const isRestore = jQuery(this).hasClass('restore-version');
        toggleVersionDelete(shortcutId, versionId, isRestore);
    });

    jQuery('#back-to-shortcuts').on('click', function() {
        const urlParams = new URLSearchParams(window.location.search);
        urlParams.delete('view');
        urlParams.delete('id');
        window.history.pushState({}, '', `${window.location.pathname}?${urlParams}`);
        toggleVersionsView(false);
    });

    jQuery('#edit-version-modal .save-as-draft-button').on('click', function(event) {
        event.preventDefault();
        updateVersion('save');
    });

    function handleVersionEditModal(event) {
        const button = jQuery(event.currentTarget);
        const versionData = button.data('version');

        if (versionData) {
            populateVersionEditModal({ version: versionData });
            jQuery('#edit-version-modal').addClass('active').show();
        } else {
            console.error('Version data not found on button');
        }
    }
}