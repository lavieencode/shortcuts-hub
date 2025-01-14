function renderShortcuts(shortcuts) {
    if (!shortcuts || !Array.isArray(shortcuts)) {
        console.error('Invalid shortcuts data:', shortcuts);
        return;
    }

    const container = document.querySelector('#shortcuts-container');
    if (!container) {
        console.error('Shortcuts container not found!');
        return;
    }

    // Clear existing shortcuts
    container.innerHTML = '';

    if (shortcuts.length === 0) {
        container.innerHTML = '<div class="no-shortcuts">No shortcuts found</div>';
        return;
    }

    shortcuts.forEach((shortcut) => {
        const wp = shortcut.wordpress || {};
        const sb = shortcut.switchblade || {};
        const post_id = shortcut.ID;

        const syncedText = sb.sb_id && sb.sb_id !== '' ? 
            `<span class="synced-text" style="color: #8a8a8a; font-size: 12px;">Switchblade ID: ${sb.sb_id}</span>` : 
            '<span class="synced-text" style="color: #8a8a8a; font-size: 12px;">No matching Switchblade shortcut</span>';

        const displayName = wp.name || 'Unnamed Shortcut';

        const shortcutElement = document.createElement('div');
        shortcutElement.className = 'shortcut-item';
        shortcutElement.dataset.post_id = post_id;

        shortcutElement.innerHTML = `
            <div class="badge-container">
                ${wp.state === 'draft' ? '<span class="badge draft">Draft</span>' : ''}
                ${wp.deleted ? '<span class="badge deleted">Deleted</span>' : ''}
            </div>
            <input type="hidden" class="shortcut-name" value="${wp.name}">
            <h3>${displayName}</h3>
            ${sb.headline ? `<p class="headline">${sb.headline}</p>` : ''}
            ${wp.description ? `<p class="description">${wp.description}</p>` : ''}
            ${syncedText}
            <div class="button-container">
                <button class="edit-button" data-post_id="${post_id}">Edit</button>
                <button class="version-button" data-id="${sb.sb_id}">Versions</button>
                <div class="btn-group">
                    ${wp.deleted ? 
                        `<button class="restore-button" data-post_id="${post_id}" data-sb_id="${sb.sb_id}">Restore</button>` :
                        `<button class="delete-button" data-post_id="${post_id}" data-sb_id="${sb.sb_id}">Delete</button>`
                    }
                    <button class="delete-dropdown-toggle" data-post_id="${post_id}" data-sb_id="${sb.sb_id}">
                        <span class="dropdown-caret">▼</span>
                    </button>
                    <div class="delete-dropdown-content">
                        <button class="delete-permanently" data-post_id="${post_id}" data-sb_id="${sb.sb_id}">Delete Permanently</button>
                    </div>
                </div>
            </div>
        `;

        container.appendChild(shortcutElement);
    });
}