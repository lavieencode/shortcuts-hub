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

    shortcuts.forEach((shortcut, index) => {
        const post_id = shortcut.post_id;

        const syncedText = shortcut.sb_id && shortcut.sb_id !== '' ? 
            `<span class="synced-text" style="color: #8a8a8a; font-size: 12px;">Switchblade ID: ${shortcut.sb_id}</span>` : 
            '<span class="synced-text" style="color: #8a8a8a; font-size: 12px;">No matching Switchblade shortcut</span>';

        const displayName = shortcut.name || 'Unnamed Shortcut';

        const shortcutElement = document.createElement('div');
        shortcutElement.className = 'shortcut-item';
        shortcutElement.dataset.post_id = post_id;

        shortcutElement.innerHTML = `
            <div class="badge-container">
                ${shortcut.post_status === 'draft' ? '<span class="badge draft">Draft</span>' : ''}
                ${shortcut.post_status === 'trash' ? '<span class="badge deleted">Deleted</span>' : ''}
            </div>
            <input type="hidden" class="shortcut-name" value="${shortcut.name}">
            <h3>${displayName}</h3>
            ${shortcut.headline ? `<p class="headline">${shortcut.headline}</p>` : ''}
            ${shortcut.description ? `<p class="description">${shortcut.description}</p>` : ''}
            ${syncedText}
            <div class="button-container">
                <button class="edit-button" data-post_id="${post_id}">Edit</button>
                <button class="version-button" data-id="${shortcut.sb_id}">Versions</button>
                <div class="btn-group">
                    ${shortcut.post_status === 'trash' ? 
                        `<button class="restore-button" data-post_id="${post_id}" data-sb_id="${shortcut.sb_id}">Restore</button>` :
                        `<button class="delete-button" data-post_id="${post_id}" data-sb_id="${shortcut.sb_id}">Delete</button>`
                    }
                    <button class="delete-dropdown-toggle" data-post_id="${post_id}" data-sb_id="${shortcut.sb_id}">
                        <span class="dropdown-caret">▼</span>
                    </button>
                    <div class="delete-dropdown-content">
                        <button class="delete-permanently" data-post_id="${post_id}" data-sb_id="${shortcut.sb_id}">Delete Permanently</button>
                    </div>
                </div>
            </div>
        `;

        container.appendChild(shortcutElement);
    });
}