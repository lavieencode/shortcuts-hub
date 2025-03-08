// Expose initialization function globally
window.initializeVersionsView = function(shortcutId) {
    // Store state in global data
    window.shortcutsHubData = window.shortcutsHubData || {};
    const urlParams = new URLSearchParams(window.location.search);
    window.shortcutsHubData.isTrashView = urlParams.get('state') === 'trash';
    window.shortcutsHubData.shortcutId = shortcutId;

    // DEBUG: Log versions view initialization
    sh_debug_log('Initializing versions view', {
        debug: true,
        message: 'Initializing versions view with shortcut ID',
        shortcut_id: shortcutId,
        global_data: {
            isTrashView: window.shortcutsHubData.isTrashView,
            shortcutId: window.shortcutsHubData.shortcutId
        }
    });
    
    // Fetch the versions data
    if (typeof window.fetchVersions === 'function') {
        window.fetchVersions(shortcutId);
    }
};

jQuery(document).ready(function() {
    // Initialize debug logging first
    if (typeof window.sh_debug_log === 'function') {
        window.sh_debug_log('Debug session started', [], {
            file: 'versions-handlers.js',
            line: 'document.ready',
            function: 'document.ready'
        });
    }

    checkUrlParameters();
    attachVersionHandlers();
});

function checkUrlParameters() {
    const urlParams = new URLSearchParams(window.location.search);
    const view = urlParams.get('view');
    const shortcutId = urlParams.get('sb_id');
    const state = urlParams.get('state');

    if (view === 'versions' && shortcutId) {
        // Set view state in global data
        window.shortcutsHubData = window.shortcutsHubData || {};
        window.shortcutsHubData.isTrashView = state === 'trash';
        window.shortcutsHubData.shortcutId = shortcutId;

        // DEBUG: Log versions initialization
        if (typeof window.sh_debug_log === 'function') {
            window.sh_debug_log('Initializing versions view', {
                debug: true,
                message: 'Initializing versions view with shortcut ID',
                shortcut_id: shortcutId,
                global_data: {
                    isTrashView: window.shortcutsHubData.isTrashView,
                    shortcutId: window.shortcutsHubData.shortcutId
                }
            }, {
                file: 'versions-handlers.js',
                line: 'checkUrlParameters',
                function: 'checkUrlParameters'
            });
        }

        // Use the existing fetchVersions from versions-fetch.js
        if (typeof window.fetchVersions === 'function') {
            window.fetchVersions(shortcutId);
        } else {
            console.error('fetchVersions function not available');
        }
    }
}

function attachVersionHandlers() {
    // Back to shortcuts button handler
    jQuery('#versions-view').on('click', '#back-to-shortcuts', function(e) {
        e.preventDefault();
        window.location.href = '?page=shortcuts-list';
    });

    // Version edit button handler
    jQuery(document).on('click', '.version-edit-button', function(e) {
        e.preventDefault();
        const versionId = jQuery(this).data('version-id');
        const shortcutId = window.shortcutsHubData.shortcutId;
        
        if (versionId && shortcutId) {
            window.location.href = `?page=edit-version&version_id=${versionId}&shortcut_id=${shortcutId}`;
        }
    });

    // Version delete button handler
    jQuery(document).on('click', '.version-delete-button', function(e) {
        e.preventDefault();
        const versionId = jQuery(this).data('version-id');
        if (versionId && typeof window.deleteVersion === 'function') {
            window.deleteVersion(versionId);
        }
    });

    // Trash view toggle handler
    jQuery(document).on('click', '#versions-trash-view', function(e) {
        e.preventDefault();
        window.shortcutsHubData.isTrashView = true;
        if (typeof window.fetchVersions === 'function') {
            window.fetchVersions(window.shortcutsHubData.shortcutId);
        }
    });

    // Active view toggle handler
    jQuery(document).on('click', '#versions-active-view', function(e) {
        e.preventDefault();
        window.shortcutsHubData.isTrashView = false;
        if (typeof window.fetchVersions === 'function') {
            window.fetchVersions(window.shortcutsHubData.shortcutId);
        }
    });
}

function displayVersions(data) {
    const versionsContainer = jQuery('#versions-list');
    versionsContainer.empty();

    // DEBUG: Log versions data being rendered
    if (typeof window.sh_debug_log === 'function') {
        window.sh_debug_log('Rendering versions data', {
            debug: true,
            message: 'Rendering versions data to container',
            data: data
        }, {
            file: 'versions-handlers.js',
            line: 'displayVersions',
            function: 'displayVersions'
        });
    }

    if (!data || !data.versions || !Array.isArray(data.versions)) {
        versionsContainer.html('<p>No versions found.</p>');
        return;
    }

    data.versions.forEach(version => {
        const versionHtml = `
            <div class="version-item" data-version-id="${version.id}">
                <div class="version-header">
                    <h3>Version ${version.version}</h3>
                    <div class="version-status">
                        <span class="status-label ${version.state.value === 0 ? 'published' : 'draft'}">${version.state.label}</span>
                        ${version.deleted ? '<span class="status-label deleted">Deleted</span>' : ''}
                        ${version.required ? '<span class="status-label required">Required Update</span>' : ''}
                        ${version.prerelease ? '<span class="status-label prerelease">Pre-release</span>' : ''}
                    </div>
                </div>
                <div class="version-details">
                    <p class="version-notes">${version.notes}</p>
                    <div class="version-requirements">
                        <span>Minimum iOS: ${version.minimumiOS}</span>
                        <span>Minimum macOS: ${version.minimumMac}</span>
                    </div>
                    <div class="version-meta">
                        <span>Created by: ${version.creator.name}</span>
                        ${version.released ? `<span>Released: ${version.released}</span>` : '<span>Not yet released</span>'}
                    </div>
                    <div class="version-actions">
                        <button class="button version-edit-button" data-version-id="${version.id}">Edit</button>
                        <button class="button version-delete-button" data-version-id="${version.id}">Delete</button>
                    </div>
                </div>
            </div>
        `;
        versionsContainer.append(versionHtml);
    });
}