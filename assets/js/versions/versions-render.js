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

    fetchVersions(id);
}

function renderVersions(data, id) {
    const sbId = id; // Set ID to SB_ID
    const container = jQuery('#versions-container');
    container.empty();

    const versions = data.versions;
    for (let i = 0; i < versions.length; i++) {
        const version = versions[i];
        const versionElement = jQuery(`
            <div class="version-item" data-version-id="${version.version}">
                <div class="version-header">
                    <h3>v${version.version || 'N/A'} <span class="caret">&#9654;</span></h3>
                    ${version.deleted ? '<span class="badge deleted-badge">Deleted</span>' : ''}
                </div>
                <div class="version-body" style="display: none;">
                    <p><strong>Notes:</strong> ${version.notes || 'No notes provided'}</p>
                    <p><strong>URL:</strong> <a href="${version.url}" target="_blank">${version.url}</a></p>
                    ${version.minimumiOS !== null ? `<p><strong>Minimum iOS:</strong> ${version.minimumiOS}</p>` : ''}
                    ${version.minimumMac !== null ? `<p><strong>Minimum Mac:</strong> ${version.minimumMac}</p>` : ''}
                    ${version.released ? `<p><strong>Released:</strong> ${new Date(version.released).toLocaleDateString()}</p>` : ''}
                    ${version.state && version.state.label ? `<p><strong>Status:</strong> ${version.state.label}</p>` : ''}
                    <p><strong>Required Update:</strong> ${version.required ? 'Yes' : 'No'}</p>
                    <div class="button-container">
                        <button class="edit-version" data-id="${sbId}" data-version-id="${version.version}">Edit Version</button>
                        ${version.deleted ? `
                            <button class="restore-button" data-version-id="${version.version}" data-shortcut-id="${sbId}">Restore Version</button>
                        ` : `
                            <button class="delete-button" data-version-id="${version.version}" data-shortcut-id="${sbId}">Delete Version</button>
                        `}
                    </div>
                </div>
            </div>
        `);
        container.append(versionElement);
    }
}