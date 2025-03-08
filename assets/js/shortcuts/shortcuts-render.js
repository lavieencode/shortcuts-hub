function getLuminance(r, g, b) {
    let [rs, gs, bs] = [r, g, b].map(c => {
        c = c / 255;
        return c <= 0.03928 ? c / 12.92 : Math.pow((c + 0.055) / 1.055, 2.4);
    });
    return 0.2126 * rs + 0.7152 * gs + 0.0722 * bs;
}

function getContrastRatio(l1, l2) {
    let lighter = Math.max(l1, l2);
    let darker = Math.min(l1, l2);
    return (lighter + 0.05) / (darker + 0.05);
}

function hexToRgb(hex) {
    hex = hex.replace(/^#/, '');
    if (hex.length === 3) {
        hex = hex[0] + hex[0] + hex[1] + hex[1] + hex[2] + hex[2];
    }
    let r = parseInt(hex.slice(0, 2), 16);
    let g = parseInt(hex.slice(2, 4), 16);
    let b = parseInt(hex.slice(4, 6), 16);
    return [r, g, b];
}

function rgbToHex(r, g, b) {
    return '#' + [r, g, b]
        .map(x => Math.round(x).toString(16).padStart(2, '0'))
        .join('');
}

function setTextColor(element, bgColor) {
    try {
        let rgb;
        if (bgColor.startsWith('#')) {
            rgb = hexToRgb(bgColor);
        } else if (bgColor.startsWith('rgb')) {
            rgb = bgColor.match(/\d+/g).map(Number);
        } else {
            throw new Error('Invalid color format');
        }

        const bgLuminance = getLuminance(...rgb);
        const darkTextLuminance = getLuminance(37, 37, 37);
        const lightTextLuminance = getLuminance(202, 202, 202);
        
        const darkContrast = getContrastRatio(bgLuminance, darkTextLuminance);
        element.style.setProperty('--is-dark', darkContrast < 3.5 ? '1' : '0', 'important');
    } catch (e) {
        element.style.setProperty('--is-dark', '0', 'important');
    }
}

function renderShortcuts(shortcuts) {
    if (!shortcuts || !Array.isArray(shortcuts)) {
        console.error('Invalid shortcuts data:', shortcuts);
        return;
    }

    const gridContainer = document.querySelector('#shortcuts-grid-container');
    const tableContainer = document.querySelector('#shortcuts-table-container tbody');
    
    if (!gridContainer || !tableContainer) {
        console.error('Shortcuts containers not found!');
        return;
    }

    window.currentShortcuts = shortcuts;

    gridContainer.innerHTML = '<div class="shortcuts-grid"></div>';
    tableContainer.innerHTML = '';

    if (shortcuts.length === 0) {
        const noShortcutsMessage = '<div class="no-shortcuts">No shortcuts found</div>';
        gridContainer.querySelector('.shortcuts-grid').innerHTML = noShortcutsMessage;
        tableContainer.innerHTML = `<tr><td colspan="5">${noShortcutsMessage}</td></tr>`;
        return;
    }

    renderGridView(shortcuts, gridContainer.querySelector('.shortcuts-grid'));
    renderTableView(shortcuts, tableContainer);
}

function renderGridView(shortcuts, container) {
    shortcuts.forEach((shortcut, index) => {
        const wp = shortcut.wordpress || {};
        const sb = shortcut.switchblade || {};
        const post_id = shortcut.ID;
        const displayName = wp.name || 'Unnamed Shortcut';
        const backgroundColor = wp.color || '#909cfe';

        let iconHtml = '';
        try {
            const iconData = typeof wp.icon === 'string' ? JSON.parse(wp.icon) : wp.icon;
            
            if (iconData && iconData.name) {
                if (iconData.type === 'fontawesome') {
                    iconHtml = `<i class="${iconData.name} shortcut-icon"></i>`;
                } else if (iconData.type === 'custom' && iconData.url) {
                    iconHtml = `<img src="${iconData.url}" class="shortcut-icon" alt="${displayName} icon">`;
                }
            } else {
                iconHtml = '<i class="fas fa-magic shortcut-icon"></i>';
            }
        } catch (e) {
            iconHtml = '<i class="fas fa-magic shortcut-icon"></i>';
        }

        const shortcutElement = document.createElement('div');
        shortcutElement.className = 'shortcut-item';
        shortcutElement.dataset.post_id = post_id;
        shortcutElement.style.backgroundColor = backgroundColor;

        setTextColor(shortcutElement, backgroundColor);

        shortcutElement.innerHTML = `
            <div class="badge-container">
                ${wp.state === 'draft' ? '<span class="badge draft">Draft</span>' : ''}
                ${wp.deleted ? '<span class="badge deleted">Deleted</span>' : ''}
            </div>
            <div class="menu-container">
                <button class="menu-toggle"><i class="fas fa-ellipsis-h"></i></button>
                <div class="action-menu">
                    ${sb.website ? `<a href="${sb.website}" class="action-button view-button" data-tooltip="View" target="_blank"><i class="fas fa-eye"></i></a>` : ''}
                    <button class="action-button edit-button" data-tooltip="Edit" data-post_id="${post_id}" data-shortcut='${JSON.stringify({
                        name: displayName,
                        headline: wp.headline || '',
                        description: wp.description || '',
                        icon: wp.icon || '',
                        color: wp.color || backgroundColor,
                        input: wp.input || '',
                        result: wp.result || '',
                        status: wp.state || 'publish'
                    })}'><i class="fas fa-edit"></i></button>
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
                    <button class="action-button edit-button" data-tooltip="Edit" data-post_id="${post_id}" data-shortcut='${JSON.stringify({
                        name: displayName,
                        headline: wp.headline || '',
                        description: wp.description || '',
                        icon: wp.icon || '',
                        color: wp.color || backgroundColor,
                        input: wp.input || '',
                        result: wp.result || '',
                        status: wp.state || 'publish'
                    })}'>Edit</button>
                    <button class="action-button versions-button" data-tooltip="Versions" data-id="${sb.sb_id}">Versions</button>
                    ${wp.deleted ? 
                        `<button class="action-button restore-button" data-tooltip="Restore" data-post_id="${post_id}" data-sb_id="${sb.sb_id}">Restore</button>` :
                        `<button class="action-button delete-button" data-tooltip="Delete" data-post_id="${post_id}" data-sb_id="${sb.sb_id}">Delete</button>`
                    }
                </div>
            </td>
        `;

        container.appendChild(row);
    });
}

function toggleView(view) {
    const gridContainer = document.querySelector('#shortcuts-grid-container');
    const tableContainer = document.querySelector('#shortcuts-table-container');
    const toggleButton = document.querySelector('#view-toggle');
    
    if (!gridContainer || !tableContainer || !toggleButton) {
        console.error('Required elements not found for view toggle');
        return;
    }

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

    localStorage.setItem('shortcuts_view_preference', view);

    if (window.currentShortcuts && window.currentShortcuts.length > 0) {
        renderShortcuts(window.currentShortcuts);
    }
}

jQuery(document).ready(function() {
    const savedView = localStorage.getItem('shortcuts_view_preference') || 'grid';
    toggleView(savedView);

    jQuery('#view-toggle').on('click', function() {
        const currentView = this.dataset.view;
        const newView = currentView === 'grid' ? 'list' : 'grid';
        toggleView(newView);
    });

    const observer = new MutationObserver(mutations => {
        mutations.forEach(mutation => {
            if (mutation.type === 'childList') {
                mutation.addedNodes.forEach(node => {
                    if (node.nodeType === 1 && node.classList.contains('shortcut-item')) {
                        const backgroundColor = window.getComputedStyle(node).backgroundColor;
                        if (backgroundColor) {
                            const rgb = backgroundColor.match(/\d+/g).map(Number);
                            const hex = '#' + rgb.map(x => x.toString(16).padStart(2, '0')).join('');
                            setTextColor(node, hex);
                        }
                    }
                });
            }
        });
    });

    const gridContainer = document.querySelector('#shortcuts-grid-container');
    if (gridContainer) {
        observer.observe(gridContainer, { 
            childList: true, 
            subtree: true 
        });
    }

    // Handle clicks outside menu containers
    jQuery(document).on('click', function(e) {
        if (!jQuery(e.target).closest('.menu-container').length) {
            jQuery('.action-menu').removeClass('active');
            jQuery('.menu-toggle').removeClass('active');
        }
    });

    // Prevent action menu clicks from bubbling
    jQuery(document).on('click', '#shortcuts-view .action-menu', function(e) {
        e.stopPropagation();
    });

    jQuery('#shortcuts-view').on('click', '.delete-button', function(e) {
        e.preventDefault();
        const button = jQuery(this);
        const postId = button.data('post_id');
        const sbId = button.data('sb_id');
        
        if (confirm('Are you sure you want to delete this shortcut?')) {
            deleteShortcut(postId, sbId, button);
        }
    });
});