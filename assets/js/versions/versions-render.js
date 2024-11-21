jQuery(document).ready(function() {
    checkUrlParameters();
});

function checkUrlParameters() {
    const urlParams = new URLSearchParams(window.location.search);
    const view = urlParams.get('view');
    const id = urlParams.get('id');

    if (view !== 'versions' || !id) {
        return;
    }
}

function renderVersions(data, id) {
    const sbId = id;
    const container = jQuery('#versions-container');
    container.empty();

    const versions = data;
    if (!versions || versions.length === 0) {
        console.error('No versions data available to render.');
        return;
    }

    for (let i = 0; i < versions.length; i++) {
        const version = versions[i];

        // Create the version element
        const versionElement = jQuery(`
            <div class="version-item" data-version-id="${version.version}" data-shortcut-id="${id}">
                <div class="version-header">
                    <h3>v${version.version || 'N/A'} <span class="caret">&#9654;</span></h3>
                </div>
                <div class="version-body" style="display: none;">
                    <p><strong>Notes:</strong> ${version.notes || 'No notes provided'}</p>
                    ${version.minimumiOS !== null ? `<p><strong>Minimum iOS:</strong> ${version.minimumiOS}</p>` : ''}
                    ${version.minimumMac !== null ? `<p><strong>Minimum Mac:</strong> ${version.minimumMac}</p>` : ''}
                    ${version.released ? `<p><strong>Released:</strong> ${new Date(version.released).toLocaleDateString()}</p>` : ''}
                    ${version.state && version.state.label ? `<p><strong>Status:</strong> ${version.state.label}</p>` : ''}
                    <p><strong>Required:</strong> ${version.required ? 'Yes' : 'No'}</p>
                    <div class="button-container">
                        ${!version.url ? console.warn(`Version ${version.version} does not have a URL.`) : ''}
                        <button class="download-button" onclick="window.open('${version.url || '#'}', '_blank')">Download</button>
                        <button class="edit-version" data-id="${id}" data-version-id="${version.version}" data-version='${JSON.stringify(version)}'>Edit</button>
                        <button type="button" class="delete-button delete-version" data-shortcut-id="${id}" data-version-id="${version.version}">Delete</button>
                    </div>
                </div>
            </div>
        `);

        // Append badges in the correct order
        const header = versionElement.find('.version-header');
        if (version.state && version.state.value === 1) {
            header.append('<span class="badge draft-badge">Draft</span>');
        }
        if (version.deleted) {
            header.append('<span class="badge deleted-badge">Deleted</span>');
        }

        container.append(versionElement);
    }
}