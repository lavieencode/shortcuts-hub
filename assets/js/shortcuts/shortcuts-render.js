function renderShortcuts(shortcuts) {
    if (!shortcuts || !Array.isArray(shortcuts)) {
        console.error('Invalid shortcuts data:', shortcuts);
        return;
    }

    const gridContainer = document.querySelector('#shortcuts-grid-container .shortcuts-grid');
    const tableContainer = document.querySelector('#shortcuts-table-container tbody');
    
    if (!gridContainer || !tableContainer) {
        console.error('Shortcuts containers not found!');
        return;
    }

    // Store shortcuts globally for re-rendering
    window.currentShortcuts = shortcuts;

    // Clear existing shortcuts
    gridContainer.innerHTML = '';
    tableContainer.innerHTML = '';

    if (shortcuts.length === 0) {
        const noShortcutsMessage = '<div class="no-shortcuts">No shortcuts found</div>';
        gridContainer.innerHTML = noShortcutsMessage;
        tableContainer.innerHTML = `<tr><td colspan="5">${noShortcutsMessage}</td></tr>`;
        return;
    }

    // Render both views
    renderGridView(shortcuts, gridContainer);
    renderTableView(shortcuts, tableContainer);
}

function renderGridView(shortcuts, container) {
    shortcuts.forEach((shortcut) => {
        const wp = shortcut.wordpress || {};
        const sb = shortcut.switchblade || {};
        const post_id = shortcut.ID;

        const displayName = wp.name || 'Unnamed Shortcut';
        const backgroundColor = wp.color || '#909cfe';
        
        // Parse icon data
        let iconHtml = '';
        try {
            const iconData = wp.icon ? JSON.parse(wp.icon) : (sb.icon ? JSON.parse(sb.icon) : null);
            if (iconData) {
                if (iconData.type === 'fontawesome' && iconData.name) {
                    iconHtml = `<i class="${iconData.name} shortcut-icon"></i>`;
                } else if (iconData.url) {
                    iconHtml = `<img src="${iconData.url}" class="shortcut-icon" alt="${displayName} icon">`;
                }
            }
        } catch (e) {
            console.error('Error parsing icon data:', e);
        }

        const shortcutElement = document.createElement('div');
        shortcutElement.className = 'shortcut-item';
        shortcutElement.dataset.post_id = post_id;
        shortcutElement.style.backgroundColor = backgroundColor;

        shortcutElement.innerHTML = `
            <div class="badge-container">
                ${wp.state === 'draft' ? '<span class="badge draft">Draft</span>' : ''}
                ${wp.deleted ? '<span class="badge deleted">Deleted</span>' : ''}
            </div>
            <div class="menu-container">
                <button class="menu-toggle"><i class="fas fa-ellipsis-h"></i></button>
                <div class="action-menu">
                    ${sb.website ? `<a href="${sb.website}" class="action-button view-button" data-tooltip="View" target="_blank"><i class="fas fa-eye"></i></a>` : ''}
                    <button class="action-button edit-button" data-tooltip="Edit" data-post_id="${post_id}"><i class="fas fa-edit"></i></button>
                    <button class="action-button versions-button" data-tooltip="Versions" data-id="${sb.sb_id}"><i class="fas fa-list"></i></button>
                    ${wp.deleted ? 
                        `<button class="action-button restore-button" data-tooltip="Restore" data-post_id="${post_id}" data-sb_id="${sb.sb_id}"><i class="fas fa-undo"></i></button>` :
                        `<button class="action-button delete-button" data-tooltip="Delete" data-post_id="${post_id}" data-sb_id="${sb.sb_id}"><i class="fas fa-trash"></i></button>`
                    }
                </div>
            </div>
            ${iconHtml}
            <a href="${sb.website}" class="shortcut-name" target="_blank"><h3>${displayName}</h3></a>
        `;

        // Add click handlers for menu
        const menuToggle = shortcutElement.querySelector('.menu-toggle');
        const actionMenu = shortcutElement.querySelector('.action-menu');
        
        menuToggle.addEventListener('click', (e) => {
            e.stopPropagation();
            actionMenu.classList.toggle('active');
            menuToggle.classList.toggle('active');
        });

        // Close menu when clicking outside
        document.addEventListener('click', () => {
            actionMenu.classList.remove('active');
            menuToggle.classList.remove('active');
        });

        // Prevent menu close when clicking inside menu
        actionMenu.addEventListener('click', (e) => {
            e.stopPropagation();
        });

        container.appendChild(shortcutElement);
    });
}

function renderTableView(shortcuts, container) {
    shortcuts.forEach((shortcut) => {
        const wp = shortcut.wordpress || {};
        const sb = shortcut.switchblade || {};
        const post_id = shortcut.ID;

        const displayName = wp.name || 'Unnamed Shortcut';
        const row = document.createElement('tr');
        
        const statusBadges = [];
        if (wp.state === 'draft') {
            statusBadges.push('<span class="status-badge draft">Draft</span>');
        } else if (wp.state === 'publish') {
            statusBadges.push('<span class="status-badge published">Published</span>');
        }
        if (wp.deleted) {
            statusBadges.push('<span class="status-badge deleted">Deleted</span>');
        }
        
        row.innerHTML = `
            <td class="name-column">
                <a href="${sb.website}" target="_blank" class="shortcut-name">${displayName}</a>
            </td>
            <td class="headline-column">${sb.headline || ''}</td>
            <td class="status-column">${statusBadges.join(' ')}</td>
            <td class="actions-count-column">${wp.actions ? wp.actions.length : 0}</td>
            <td class="actions-column">
                <div class="button-container">
                    <button class="edit-button" data-tooltip="Edit" data-post_id="${post_id}">Edit</button>
                    <button class="version-button" data-tooltip="Versions" data-id="${sb.sb_id}">Versions</button>
                    ${wp.deleted ? 
                        `<button class="restore-button" data-tooltip="Restore" data-post_id="${post_id}" data-sb_id="${sb.sb_id}">Restore</button>` :
                        `<button class="delete-button" data-tooltip="Delete" data-post_id="${post_id}" data-sb_id="${sb.sb_id}">Delete</button>`
                    }
                </div>
            </td>
        `;

        container.appendChild(row);
    });
}

// Function to toggle between grid and list views
function toggleView(view) {
    const gridContainer = document.querySelector('#shortcuts-grid-container');
    const tableContainer = document.querySelector('#shortcuts-table-container');
    const toggleButton = document.querySelector('#view-toggle');
    
    if (!gridContainer || !tableContainer || !toggleButton) {
        console.error('Required elements not found for view toggle');
        return;
    }

    // Update view state
    if (view === 'grid') {
        gridContainer.classList.add('active');
        tableContainer.classList.remove('active');
        toggleButton.dataset.view = 'grid';
        toggleButton.querySelector('.grid-icon').style.opacity = '1';
        toggleButton.querySelector('.list-icon').style.opacity = '0.5';
    } else {
        gridContainer.classList.remove('active');
        tableContainer.classList.add('active');
        toggleButton.dataset.view = 'list';
        toggleButton.querySelector('.grid-icon').style.opacity = '0.5';
        toggleButton.querySelector('.list-icon').style.opacity = '1';
    }

    // Save view preference
    localStorage.setItem('shortcuts_view_preference', view);

    // Re-render shortcuts if needed
    if (window.currentShortcuts && window.currentShortcuts.length > 0) {
        renderShortcuts(window.currentShortcuts);
    }
}

// Initialize view based on saved preference
jQuery(document).ready(function() {
    const savedView = localStorage.getItem('shortcuts_view_preference') || 'grid';
    toggleView(savedView);

    // Set up view toggle click handler
    jQuery('#view-toggle').on('click', function() {
        const currentView = this.dataset.view;
        const newView = currentView === 'grid' ? 'list' : 'grid';
        toggleView(newView);
    });
});