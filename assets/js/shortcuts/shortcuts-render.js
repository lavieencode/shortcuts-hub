function renderShortcuts(shortcuts) {
    if (!shortcuts || !Array.isArray(shortcuts)) {
        console.error('Invalid shortcuts data:', shortcuts);
        return;
    }

    const container = jQuery('#shortcuts-container');
    container.empty();

    if (shortcuts.length === 0) {
        container.append('<p>No shortcuts found.</p>');
        return;
    }

    for (let i = 0; i < shortcuts.length; i++) {
        const shortcut = shortcuts[i];
        const post_id = shortcut.post_id;

        const syncedText = shortcut.id && shortcut.id !== '' ? 
            `<span class="synced-text" style="color: #8a8a8a; font-size: 12px;">Switchblade ID: ${shortcut.id}</span>` : 
            '<span class="synced-text" style="color: #8a8a8a; font-size: 12px;">No matching Switchblade shortcut</span>';

        const displayName = shortcut.name || 'Unnamed Shortcut';

        container.append(`
            <div class="shortcut-item" data-post_id="${post_id}">
                <input type="hidden" class="shortcut-name" value="${shortcut.name}">
                <h3>${displayName}</h3>
                <p>${shortcut.headline || 'No headline available'}</p>
                ${syncedText}
                <p class="created-date">${shortcut.post_date || 'Date not available'}</p>
                <div class="badges-container">
                    ${shortcut.deleted ? '<span class="badge deleted-badge">Deleted</span>' : ''}
                    ${shortcut.draft ? '<span class="badge draft-badge">Draft</span>' : ''}
                </div>
                <div class="button-container">
                    <button class="edit-button" data-post_id="${post_id}">Edit</button>
                    <button class="version-button" data-id="${shortcut.id}">Versions</button>
                    <button class="delete-button" data-id="${shortcut.id}">Delete</button>
                </div>
            </div>
        `);
    }

    initializeShortcutButtons();
}