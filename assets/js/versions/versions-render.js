jQuery(document).ready(function() {
});

function renderVersions(versions, shortcutId) {
    const container = jQuery('#versions-container');
    container.empty();

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
                        <button class="edit-version" data-shortcut-id="${shortcutId}" data-version-id="${version.version}">Edit Version</button>
                        ${version.deleted ? `
                            <button class="restore-version" data-shortcut-id="${shortcutId}" data-version-id="${version.version}">Restore Version</button>
                        ` : `
                            <button class="delete-version" data-shortcut-id="${shortcutId}" data-version-id="${version.version}">Delete Version</button>
                        `}
                    </div>
                </div>
            </div>
        `);
        container.append(versionElement);
    }
}