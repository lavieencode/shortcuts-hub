jQuery(document).ready(function() {
    checkUrlParameters();
});

function checkUrlParameters() {
    const urlParams = new URLSearchParams(window.location.search);
    const view = urlParams.get('view');
    const id = urlParams.get('id');
    const state = urlParams.get('state');

    if (view !== 'versions' || !id) {
        return;
    }

    // Store state in global data
    window.shortcutsHubData = window.shortcutsHubData || {};
    window.shortcutsHubData.isTrashView = state === 'trash';
}

function renderVersions(data) {
    const container = jQuery('#versions-container');
    container.empty();

    if (!data || !data.versions || !Array.isArray(data.versions)) {
        container.html('<p class="no-versions-message">No versions found.</p>');
        return;
    }

    const versions = data.versions;
    const shortcutId = data.shortcut.id;

    // DEBUG: Log initial data received
    sh_debug_log('Version Render Data', {
        debug: true,
        message: 'Initial data received by renderVersions',
        source: {
            file: 'versions-render.js',
            line: 'renderVersions',
            function: 'renderVersions'
        },
        data: {
            raw_data: data,
            data_type: typeof data,
            is_array: Array.isArray(data),
            length: data ? data.length : 0
        }
    });

    // DEBUG: Log container state
    sh_debug_log('Container Check', {
        debug: true,
        message: 'Checking versions container element',
        source: {
            file: 'versions-render.js',
            line: 'renderVersions',
            function: 'renderVersions'
        },
        data: {
            container_exists: container.length > 0,
            container_selector: '#versions-container',
            container_html: container.html()
        }
    });

    // Get shortcut ID from global data since we know it's set in fetchVersions
    const isTrashView = window.shortcutsHubData.isTrashView === true;

    // Check if there are any deleted versions and show/hide trash button
    const hasDeletedVersions = versions.some(version => version.deleted === true);
    const trashButton = jQuery('#versions-trash-view');

    // Show trash button if we're in trash view OR if there are deleted versions
    if (isTrashView || hasDeletedVersions) {
        trashButton.removeAttr('style').show();
    } else {
        trashButton.hide();
    }

    // DEBUG: Log view state
    sh_debug_log('Version View State', {
        debug: true,
        message: 'Current view state before filtering',
        source: {
            file: 'versions-render.js',
            line: 'renderVersions',
            function: 'renderVersions'
        },
        data: {
            is_trash_view: isTrashView,
            has_deleted_versions: hasDeletedVersions,
            total_versions: versions.length,
            versions_state: versions.map(v => ({
                version: v.version,
                deleted: v.deleted,
                state: v.state
            }))
        }
    });
    
    // Update UI for trash view
    const pageTitle = jQuery('.versions-page-title');
    const filterBar = jQuery('#versions-header-bar');
    const shortcutName = jQuery('#shortcut-name-display');
    const backButton = jQuery('#back-to-shortcuts');

    if (isTrashView) {
        trashButton.addClass('active').text('Back to Versions');
        pageTitle.addClass('trash').text('DELETED VERSIONS');
        shortcutName.addClass('shortcut-name-trash');
        filterBar.hide();
    } else {
        trashButton.removeClass('active').text('Trash');
        pageTitle.removeClass('trash').text('VERSIONS');
        shortcutName.removeClass('shortcut-name-trash');
        filterBar.show();
    }

    // Always show back button when in versions view
    backButton.show();

    // Filter versions based on view mode
    const versionsToShow = versions.filter(version => version.deleted === isTrashView);
    
    if (versionsToShow.length === 0) {
        container.html('<p class="no-versions-message">No ' + (isTrashView ? 'deleted ' : '') + 'versions found.</p>');
        return;
    }

    // Create versions table
    const table = jQuery('<table class="versions-table"></table>');
    const thead = jQuery('<thead></thead>');
    const headerRow = jQuery('<tr></tr>');
    
    // Add table headers
    headerRow.append(`
        <th>Version</th>
        <th>Notes</th>
        ${isTrashView ? '<th>Status</th>' : '<th>State</th>'}
        <th>Actions</th>
    `);
    
    thead.append(headerRow);
    table.append(thead);
    
    // Create table body
    const tbody = jQuery('<tbody></tbody>');
    
    // Sort versions by version number (descending)
    versionsToShow.sort((a, b) => {
        return parseFloat(b.version) - parseFloat(a.version);
    });

    versionsToShow.forEach(version => {
        // Prepare action buttons based on view mode
        let actionButtons = '';
        if (isTrashView) {
            actionButtons = `
                <button class="action-button restore-button" data-tooltip="Restore to Draft" 
                    data-shortcut-id="${shortcutId}" 
                    data-version-id="${version.version}">
                    <i class="fas fa-undo"></i>
                </button>
                <button class="action-button delete-permanent-button" data-tooltip="Delete Permanently - This cannot be undone!" 
                    data-shortcut-id="${shortcutId}" 
                    data-version-id="${version.version}">
                    <i class="fas fa-trash"></i>
                </button>
            `;
        } else {
            actionButtons = `
                <a href="${version.url || '#'}" class="action-button download-button" data-tooltip="Download" target="_blank">
                    <i class="fas fa-download"></i>
                </a>
                <button class="action-button edit-button" data-tooltip="Edit" 
                    data-shortcut-id="${shortcutId}" 
                    data-version-id="${version.version}"
                    data-version='${JSON.stringify({
                        version: version.version,
                        notes: version.notes || '',
                        url: version.url || '',
                        minimumiOS: version.minimumiOS || '',
                        minimumMac: version.minimumMac || '',
                        required: version.required,
                        state: version.state,
                        deleted: version.deleted
                    })}'>
                    <i class="fas fa-edit"></i>
                </button>
                <button class="action-button delete-button" data-tooltip="Move to Trash" 
                    data-shortcut-id="${shortcutId}" 
                    data-version-id="${version.version}">
                    <i class="fas fa-trash"></i>
                </button>
            `;
        }

        const row = jQuery('<tr>').html(`
            <td class="version-column"><span class="version-number">${version.version || ''}</span></td>
            <td>${version.notes || ''}</td>
            <td>${isTrashView ? 
                '<span class="status-badge deleted">Deleted</span>' : 
                `<span class="status-badge ${version.state && version.state.value === 1 ? 'draft' : 'published'}">${version.state && version.state.value === 1 ? 'Draft' : 'Published'}</span>`
            }</td>
            <td>
                <div class="action-buttons">
                    ${actionButtons}
                </div>
            </td>
        `);
        
        tbody.append(row);
    });

    table.append(tbody);
    container.append(table);
}

// Export the function
window.renderVersions = renderVersions;