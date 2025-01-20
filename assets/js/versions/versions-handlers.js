jQuery(document).ready(function() {
    // Only proceed if we have the localized data
    if (typeof window.shortcutsHubData === 'undefined') {
        sh_debug_log('Missing shortcutsHubData', {
            message: 'shortcutsHubData is not defined',
            source: {
                file: 'versions-handlers.js',
                line: 'document.ready',
                function: 'document.ready'
            },
            data: {},
            debug: true
        });
        return;
    }

    // DEBUG: Log initialization with full data
    sh_debug_log('Initializing versions handlers', {
        message: 'Starting versions handlers initialization',
        source: {
            file: 'versions-handlers.js',
            line: 'document.ready',
            function: 'document.ready'
        },
        data: {
            shortcutsHubData: window.shortcutsHubData
        },
        debug: true
    });

    // Handle initial view state
    if (window.shortcutsHubData.initialView === 'versions' && window.shortcutsHubData.shortcutId) {
        toggleVersionsView(true, window.shortcutsHubData.shortcutId);
    }

    // Attach event handler for back button
    jQuery('#back-to-shortcuts').on('click', function() {
        toggleVersionsView(false);
    });

    // Attach version-specific handlers
    attachVersionHandlers();
});

function displayVersions(data) {
    const versionsContainer = jQuery('#versions-container');
    versionsContainer.empty();

    if (!data.versions || data.versions.length === 0) {
        versionsContainer.append('<div class="no-versions">No versions found</div>');
        return;
    }

    data.versions.forEach(function(version) {
        const versionHtml = `
            <div class="version-item" data-shortcut-id="${version.shortcut_id}" data-version-id="${version.id}">
                <div class="version-header">
                    <span class="caret">&#9654;</span>
                    <span class="version-title">${version.title}</span>
                    <span class="version-date">${version.created_at}</span>
                    <span class="version-status">${version.state.label}</span>
                </div>
                <div class="version-body" style="display: none;">
                    <div class="version-content">
                        <p>${version.description}</p>
                        <div class="version-actions">
                            <button class="edit-version" data-version='${JSON.stringify(version)}'>Edit</button>
                            ${version.deleted ? 
                                `<button class="restore-version">Restore</button>` : 
                                `<button class="delete-version">Delete</button>`
                            }
                        </div>
                    </div>
                </div>
            </div>
        `;
        versionsContainer.append(versionHtml);
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

    jQuery(document).on('click', '.delete-version, .restore-version', function() {
        const shortcutId = jQuery(this).closest('.version-item').data('shortcut-id');
        const versionId = jQuery(this).closest('.version-item').data('version-id');
        const isRestore = jQuery(this).hasClass('restore-version');
        toggleVersionDelete(shortcutId, versionId, isRestore);
    });
}