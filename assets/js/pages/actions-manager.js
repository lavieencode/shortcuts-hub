jQuery(document).ready(function($) {
    let actions = []; // Store fetched actions
    let selectedShortcuts = []; // Store selected shortcuts for add modal
    let editSelectedShortcuts = []; // Store selected shortcuts for edit modal

    // Wait for IconSelector to be available
    if (typeof IconSelector === 'undefined') {
        console.error('IconSelector not loaded. Please check script dependencies.');
        return;
    }

    // Utility function to escape HTML
    function escapeHtml(unsafe) {
        if (!unsafe) return '';
        return unsafe
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

    // Show notification
    function showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `notification ${type}`;
        notification.innerHTML = message;
        
        document.body.appendChild(notification);
        
        // Show notification
        setTimeout(() => {
            notification.classList.add('show');
        }, 10);
        
        // Hide and remove notification after 3 seconds
        setTimeout(() => {
            notification.classList.remove('show');
            setTimeout(() => {
                notification.remove();
            }, 300);
        }, 3000);
    }

    // ShortcutsSelector class for managing shortcuts selection
    class ShortcutsSelector {
        constructor(options) {
            this.container = options.container;
            this.searchInput = this.container.querySelector('input[type="text"]');
            this.shortcutsList = this.container.querySelector('.shortcuts-list');
            this.actionId = options.actionId || 0;
            this.isEdit = this.container.closest('#edit-action-modal') !== null;
            this.isAddModal = !this.isEdit;
            this.selectedShortcuts = this.isEdit ? editSelectedShortcuts : selectedShortcuts;
            
            // Store all shortcuts for client-side filtering
            this.allShortcuts = options.preloadedShortcuts || [];
            this.isLoaded = this.allShortcuts.length > 0;
            
            // Store instance in a data attribute for later retrieval
            this.container.shortcutsSelector = this;
            
            this.init();
        }
        
        init() {
            // Initialize search functionality
            this.searchInput.addEventListener('input', this.handleSearch.bind(this));
            
            // Show loading indicator if the list is empty and we don't have preloaded shortcuts
            if (!this.shortcutsList.innerHTML.trim() && !this.isLoaded) {
                this.shortcutsList.innerHTML = '<div class="shortcuts-loading">Loading...</div>';
            }
            
            // Only load shortcuts if we don't already have them preloaded
            if (!this.isLoaded) {
                // Only load shortcuts if we're in the add modal or if we don't have an action ID yet
                // For edit modal, we'll preload the shortcuts before showing the modal
                if (!this.isEdit || this.actionId === 0) {
                    this.loadShortcuts();
                }
            }
            
            // Handle clicks on the shortcuts list
            this.shortcutsList.addEventListener('click', this.handleShortcutClick.bind(this));
        }
        
        handleSearch(e) {
            const searchTerm = e.target.value.trim().toLowerCase();
            
            // If shortcuts are already loaded, filter them client-side
            if (this.isLoaded && this.allShortcuts.length > 0) {
                this.filterShortcuts(searchTerm);
            } else {
                // Fall back to server-side search if not loaded yet
                this.loadShortcuts(searchTerm);
            }
        }
        
        filterShortcuts(searchTerm) {
            if (!searchTerm) {
                // If no search term, render all shortcuts
                this.renderShortcuts(this.allShortcuts);
                return;
            }
            
            // Filter shortcuts based on search term
            const filteredShortcuts = this.allShortcuts.filter(shortcut => {
                if (!shortcut) return false;
                const title = (shortcut.post_title || '').toLowerCase();
                return title.includes(searchTerm);
            });
            
            // Render the filtered shortcuts
            this.renderShortcuts(filteredShortcuts);
        }
        
        handleShortcutClick(e) {
            const listItem = e.target.closest('li');
            if (!listItem) return;
            
            const shortcutId = parseInt(listItem.dataset.id, 10);
            const checkbox = listItem.querySelector('.shortcut-checkbox i');
            const isCurrentlySelected = listItem.classList.contains('selected');
            
            // Toggle selection - store current state before changing it
            if (isCurrentlySelected) {
                // Deselect
                listItem.classList.remove('selected');
                checkbox.classList.remove('fa-check-square');
                checkbox.classList.add('fa-square');
                this.selectedShortcuts = this.selectedShortcuts.filter(id => id !== shortcutId);
            } else {
                // Select
                listItem.classList.add('selected');
                checkbox.classList.remove('fa-square');
                checkbox.classList.add('fa-check-square');
                this.selectedShortcuts.push(shortcutId);
            }
            
            // Update the appropriate array based on which modal we're in
            if (this.isEdit) {
                editSelectedShortcuts = this.selectedShortcuts;
            } else {
                selectedShortcuts = this.selectedShortcuts;
            }
            
            // Update all other list items with the same ID in case there are duplicates
            // This ensures consistent state across the UI
            const allListItems = this.shortcutsList.querySelectorAll(`li[data-id="${shortcutId}"]`);
            allListItems.forEach(item => {
                if (item !== listItem) { // Skip the one we just clicked
                    const itemCheckbox = item.querySelector('.shortcut-checkbox i');
                    if (!isCurrentlySelected) { // We're selecting
                        item.classList.add('selected');
                        itemCheckbox.classList.remove('fa-square');
                        itemCheckbox.classList.add('fa-check-square');
                    } else { // We're deselecting
                        item.classList.remove('selected');
                        itemCheckbox.classList.remove('fa-check-square');
                        itemCheckbox.classList.add('fa-square');
                    }
                }
            });
        }
        
        loadShortcuts(search = '') {
            // If we already have shortcuts loaded and there's a search term, use client-side filtering
            if (this.isLoaded && this.allShortcuts.length > 0 && search) {
                this.filterShortcuts(search);
                return;
            }
            
            // Initialize shortcuts array if not already done
            if (!this.allShortcuts) {
                this.allShortcuts = [];
            }
            
            // Show subtle loading indicator
            if (!this.shortcutsList.innerHTML.trim() || this.shortcutsList.innerHTML.includes('No shortcuts')) {
                this.shortcutsList.innerHTML = '<div class="shortcuts-loading">Loading...</div>';
            }
            
            $.ajax({
                url: shortcutsHubData.ajaxurl,
                type: 'POST',
                data: {
                    action: 'fetch_shortcuts_for_action',
                    security: shortcutsHubData.security.fetch_shortcuts_for_action,
                    action_id: this.actionId,
                    search: search
                },
                success: (response) => {
                    if (response.success) {
                        const shortcuts = response.data || [];
                        
                        // Store all shortcuts for client-side filtering (only if this is the initial load with no search term)
                        if (!search) {
                            // Add new shortcuts to our collection, avoiding duplicates
                            shortcuts.forEach(shortcut => {
                                const id = parseInt(shortcut.id || shortcut.ID, 10);
                                // Check if we already have this shortcut
                                const exists = this.allShortcuts.some(s => {
                                    return parseInt(s.id || s.ID, 10) === id;
                                });
                                
                                if (!exists) {
                                    this.allShortcuts.push(shortcut);
                                }
                            });
                            
                            this.isLoaded = true;
                        }
                        
                        // Render the shortcuts we received
                        this.renderShortcuts(shortcuts);
                    } else {
                        console.error('Failed to load shortcuts:', response.data ? response.data.message : 'Unknown error');
                        this.shortcutsList.innerHTML = '<div class="shortcuts-list-empty">Failed to load</div>';
                    }
                },
                error: (xhr, status, error) => {
                    console.error('AJAX error:', error);
                    this.shortcutsList.innerHTML = '<div class="shortcuts-list-empty">Error loading</div>';
                }
            });
        }
        
        renderShortcuts(shortcuts) {
            // Clear any loading indicators or previous content
            this.shortcutsList.innerHTML = '';
            
            if (!shortcuts || shortcuts.length === 0) {
                this.shortcutsList.innerHTML = '<div class="shortcuts-list-empty">No shortcuts</div>';
                return;
            }
            
            // Create a Set of IDs to avoid duplicates
            const renderedIds = new Set();
            
            // Always update selectedShortcuts with associated shortcuts when we have an action ID
            // This ensures they're selected when the modal opens
            if (this.actionId > 0) {
                // First, identify all associated shortcuts
                const associatedIds = [];
                shortcuts.forEach(shortcut => {
                    if (shortcut && shortcut.is_associated) {
                        // Handle both uppercase ID and lowercase id
                        const id = parseInt(shortcut.id || shortcut.ID, 10);
                        if (!associatedIds.includes(id)) {
                            associatedIds.push(id);
                        }
                    }
                });
                
                // Update selectedShortcuts to include all associated shortcuts
                // This ensures they're always selected, even if the user deselected some
                associatedIds.forEach(id => {
                    if (!this.selectedShortcuts.includes(id)) {
                        this.selectedShortcuts.push(id);
                    }
                });
                
                // Update the appropriate array based on which modal we're in
                if (this.isEdit) {
                    editSelectedShortcuts = this.selectedShortcuts;
                } else {
                    selectedShortcuts = this.selectedShortcuts;
                }
            }
            
            shortcuts.forEach(shortcut => {
                if (!shortcut) return;
                
                // Handle both uppercase ID and lowercase id
                const shortcutId = parseInt(shortcut.id || shortcut.ID, 10);
                
                // Skip if we've already rendered this shortcut
                if (renderedIds.has(shortcutId)) return;
                
                // Add to rendered set
                renderedIds.add(shortcutId);
                
                const isSelected = this.selectedShortcuts.includes(shortcutId);
                const li = document.createElement('li');
                li.dataset.id = shortcutId;
                li.dataset.associated = shortcut.is_associated ? 'true' : 'false';
                li.className = isSelected ? 'selected' : '';
                
                // Add title attribute to associated shortcuts
                if (shortcut.is_associated && !isSelected) {
                    li.title = 'This shortcut is associated with this action';
                }
                
                li.innerHTML = `
                    <span class="shortcut-checkbox">
                        <i class="fas ${isSelected ? 'fa-check-square' : 'fa-square'}"></i>
                    </span>
                    <span class="shortcut-title">${escapeHtml(shortcut.post_title)}</span>
                `;
                
                this.shortcutsList.appendChild(li);
            });
        }
        
        setActionId(actionId) {
            this.actionId = actionId;
            
            // If we already have shortcuts loaded, we can filter them client-side
            // to show which ones are associated with this action
            if (this.isLoaded && this.allShortcuts.length > 0) {
                // We still need to fetch from the server to get the associated status
                this.loadShortcuts('');
            } else {
                this.loadShortcuts();
            }
        }
        
        reset() {
            this.selectedShortcuts = [];
            this.searchInput.value = '';
            this.loadShortcuts();
        }
        
        preloadAllShortcuts() {
            // Only load shortcuts if we're in the add modal or if we don't have an action ID yet
            if (this.isAddModal || !this.actionId) {
                this.loadShortcuts('');
            } else {
                // For edit modal with an action ID, we'll load the associated shortcuts
                this.loadShortcuts('');
            }
            
            // Set a flag to indicate we've preloaded the shortcuts
            this.shortcutsPreloaded = true;
        }
    }

    // Initialize icon selectors
    function initializeIconSelectors() {
        // Add modal icon selector
        const addIconContainer = document.querySelector('#add-action-modal .icon-selector-container');
        if (addIconContainer) {
            const addActionIconSelector = new IconSelector({
                container: addIconContainer,
                inputField: document.getElementById('action-icon'),
                onChange: function(value) {
                    // Optional: Add any onChange handling here
                }
            });
            
            // Store the selector instance on the container for later use
            addIconContainer._iconSelector = addActionIconSelector;
            
            // Force the type selector to the first option and trigger change event
            const typeSelect = addIconContainer.querySelector('.icon-type-selector');
            if (typeSelect) {
                // Ensure it's set to the default option
                typeSelect.selectedIndex = 0;
                typeSelect.value = '';
                
                // Force a change event to ensure the UI updates
                const changeEvent = new Event('change');
                typeSelect.dispatchEvent(changeEvent);
            }
        }

        // Edit modal icon selector
        const editIconContainer = document.querySelector('#edit-action-modal .icon-selector-container');
        if (editIconContainer) {
            const editActionIconSelector = new IconSelector({
                container: editIconContainer,
                inputField: document.getElementById('edit-action-icon'),
                onChange: function(value) {
                    // Optional: Add any onChange handling here
                }
            });
            
            // Store the selector instance on the container for later use
            editIconContainer._iconSelector = editActionIconSelector;
            
            // Force the type selector to the first option and trigger change event
            const typeSelect = editIconContainer.querySelector('.icon-type-selector');
            if (typeSelect) {
                // Ensure it's set to the default option
                typeSelect.selectedIndex = 0;
                typeSelect.value = '';
                
                // Force a change event to ensure the UI updates
                const changeEvent = new Event('change');
                typeSelect.dispatchEvent(changeEvent);
            }
        }
    }

    // Initialize shortcuts selectors
    function initializeShortcutsSelectors() {
        // Preload all shortcuts for faster client-side filtering
        function preloadAllShortcuts() {
            return $.ajax({
                url: shortcutsHubData.ajaxurl,
                type: 'POST',
                data: {
                    action: 'fetch_shortcuts_for_action',
                    security: shortcutsHubData.security.fetch_shortcuts_for_action,
                    action_id: 0
                }
            });
        }
        
        // Preload all shortcuts once
        const shortcutsPromise = preloadAllShortcuts();
        
        // Add modal shortcuts selector
        const addShortcutsContainer = document.querySelector('#add-action-modal .shortcuts-selector-container');
        
        // Wait for shortcuts to be preloaded before initializing selectors
        shortcutsPromise.then(response => {
            const preloadedShortcuts = response.success && response.data ? response.data : [];
            

            
            // Initialize add modal selector with preloaded shortcuts
            if (addShortcutsContainer) {
                const addShortcutsSelector = new ShortcutsSelector({
                    container: addShortcutsContainer,
                    preloadedShortcuts: preloadedShortcuts
                });
                
                // Render the shortcuts immediately if we have them
                if (preloadedShortcuts.length > 0) {
                    addShortcutsSelector.renderShortcuts(preloadedShortcuts);
                }
            }
            
            // Initialize edit modal selector with preloaded shortcuts
            const editShortcutsContainer = document.querySelector('#edit-action-modal .shortcuts-selector-container');
            if (editShortcutsContainer) {
                const editShortcutsSelector = new ShortcutsSelector({
                    container: editShortcutsContainer,
                    preloadedShortcuts: preloadedShortcuts
                });
            }
        }).catch(error => {
            console.error('Error preloading shortcuts:', error);
            
            // Initialize selectors without preloaded data if there was an error
            if (addShortcutsContainer) {
                const addShortcutsSelector = new ShortcutsSelector({
                    container: addShortcutsContainer
                });
            }
            
            const editShortcutsContainer = document.querySelector('#edit-action-modal .shortcuts-selector-container');
            if (editShortcutsContainer) {
                const editShortcutsSelector = new ShortcutsSelector({
                    container: editShortcutsContainer
                });
            }
        });

        // Edit modal shortcuts selector is now initialized in the shortcutsPromise.then callback above
    }

    // Initialize icon selectors
    initializeIconSelectors();

    // Initialize shortcuts selectors
    initializeShortcutsSelectors();

    // Handle icon type change for both modals
    $('.icon-type-selector').on('change', function() {
        const isEdit = $(this).attr('id') === 'edit-action-icon-type';
        const modalPrefix = isEdit ? 'edit-' : '';
        const type = $(this).val();

        if (type === 'custom') {
            $(`#${modalPrefix}icon-selector`).hide();
            $(`#${modalPrefix}custom-icon-upload`).show();
        } else if (type === 'fontawesome') {
            $(`#${modalPrefix}custom-icon-upload`).hide();
            $(`#${modalPrefix}icon-selector`).show();
        }

        // Clear the icon preview when switching types
        $(`#${modalPrefix}action-modal .icon-preview`).empty();
    });

    // Fetch actions from server
    function fetchActions() {
        console.log('Fetching actions from server...');
        // Show loading spinner and ensure it's visible
        $('#actions-loading').css({
            'display': 'flex',
            'opacity': '1'
        });
        $.ajax({
            url: shortcutsHubData.ajaxurl,
            type: 'POST',
            data: {
                action: 'fetch_actions',
                security: shortcutsHubData.security.fetch_actions
            },
            success: function(response) {
                console.log('Fetch actions response:', response);
                if (response.success) {
                    // Log the raw data before mapping
                    console.log('Raw action data from server:', response.data);
                    
                    actions = response.data.map(action => {
                        const mappedAction = {
                            id: parseInt(action.ID, 10),
                            name: action.post_title,
                            description: action.post_content,
                            icon: action.icon || 'fas fa-bolt',
                            input: action.input || '',
                            result: action.result || '',
                            shortcuts: action.shortcuts || [],
                            status: action.post_status || 'publish',
                            permalink: action.permalink || ''
                        };
                        

                        

                        
                        return mappedAction;
                    });
                    
                    renderActions(actions);
                } else {
                    console.error('Failed to fetch actions:', response.data ? response.data.message : 'Unknown error');
                }
                // Hide loading spinner with a slight delay for visibility
                setTimeout(function() {
                    $('#actions-loading').fadeOut(300);
                }, 500);
            },
            error: function(xhr, status, error) {
                console.error('AJAX error:', error);
                // Hide loading spinner with a slight delay for visibility
                setTimeout(function() {
                    $('#actions-loading').fadeOut(300);
                }, 500);
            }
        });
    }

    // Render actions list
    function renderActions(actions) {
        const $actionsList = $('#actions-list');
        $actionsList.empty();
        
        if (!actions || actions.length === 0) {
            $actionsList.html('<tr><td colspan="5" class="no-actions">No actions found</td></tr>');
            return;
        }
        
        actions.forEach(action => {
            if (!action) return;
            
            let shortcutsHtml = '<span class="shortcut-count">0</span>';
            
            if (action.shortcuts && action.shortcuts.length > 0) {
                shortcutsHtml = `<span class="shortcut-count">${action.shortcuts.length}</span>`;
            }
            
            // Process icon
            let iconHtml = '<i class="fas fa-bolt"></i>'; // Default icon
            if (action.icon) {
                try {
                    // Try to parse the icon if it's JSON
                    if (typeof action.icon === 'string' && (action.icon.startsWith('{') || action.icon.includes('\\'))) {
                        iconHtml = renderActionIcon(action.icon);
                    } else {
                        // If it's just a class name
                        iconHtml = `<i class="${escapeHtml(action.icon)}"></i>`;
                    }
                } catch (e) {
                    console.error('Error rendering icon:', e);
                    iconHtml = '<i class="fas fa-bolt"></i>'; // Fallback to default
                }
            }
            
            // If renderActionIcon returned empty, use default
            if (!iconHtml) {
                iconHtml = '<i class="fas fa-bolt"></i>';
            }
            
            // Ensure we have valid HTML for the icon
            if (typeof iconHtml !== 'string' || !iconHtml.includes('<i') && !iconHtml.includes('<img')) {
                iconHtml = '<i class="fas fa-bolt"></i>';
            }
            
            // Determine status badge
            const status = action.status || 'draft';
            const statusText = status === 'publish' ? 'Published' : 'Draft';
            const statusBadgeClass = status === 'publish' ? 'status-published' : 'status-draft';
            const statusBadgeHtml = `<span class="status-badge ${statusBadgeClass}">${statusText}</span>`;
            
            const $row = $(`
                <tr data-id="${action.id}">
                    <td class="action-name-column">
                        <div class="action-name-container">
                            <span class="action-icon">${iconHtml}</span>
                            <span class="action-name-text">${escapeHtml(action.name || '')}</span>
                        </div>
                    </td>
                    <td class="action-description-column">${escapeHtml(action.description || '')}</td>
                    <td class="action-status-column">${statusBadgeHtml}</td>
                    <td class="action-shortcuts-column">${shortcutsHtml}</td>
                    <td class="action-actions-column">
                        <button class="view-action" data-id="${action.id}" title="View Action"><i class="fas fa-eye"></i></button>
                        <button class="edit-action" data-id="${action.id}" title="Edit Action"><i class="fas fa-edit"></i></button>
                        <button class="delete-action" data-id="${action.id}" title="Delete Action"><i class="fas fa-trash"></i></button>
                    </td>
                </tr>
            `);
            
            $actionsList.append($row);
        });
    }

    // Helper function to render action icon
    function renderActionIcon(iconData) {
        if (!iconData) return '';
        

        
        try {
            // DIRECT APPROACH: Handle all possible icon data formats
            
            // Case 1: Direct Font Awesome class string (e.g., "fas fa-home")
            if (typeof iconData === 'string' && iconData.startsWith('fa')) {

                return `<i class="${iconData}"></i>`;
            }
            
            // Case 2: JSON string representation
            if (typeof iconData === 'string' && (iconData.startsWith('{') || iconData.startsWith('['))) {
                try {
                    const parsedIcon = JSON.parse(iconData);
                    

                    
                    if (parsedIcon.type === 'fontawesome') {
                        return `<i class="${parsedIcon.name}"></i>`;
                    } else if (parsedIcon.type === 'custom' && parsedIcon.url) {
                        return `<img src="${parsedIcon.url}" alt="Action icon">`;
                    }
                } catch (e) {

                }
            }
            
            // Case 3: Already an object
            if (typeof iconData === 'object' && iconData !== null) {

                
                if (iconData.type === 'fontawesome') {
                    return `<i class="${iconData.name}"></i>`;
                } else if (iconData.type === 'custom' && iconData.url) {
                    return `<img src="${iconData.url}" alt="Action icon">`;
                }
            }
            
            // Case 4: Fallback - if it's a non-empty string, try using it as a class
            if (typeof iconData === 'string' && iconData.trim() !== '') {

                return `<i class="${iconData}"></i>`;
            }
            
            // If we got here, we couldn't render the icon

            return '';
        } catch (e) {
            console.error('Error rendering icon:', e, iconData);

            return '';
        }
    }

    // Modal functionality
    $('#add-new-action').click(function() {
        $('#add-action-modal').show().addClass('active');
        $('body').addClass('modal-open');
        // Show both buttons for new actions
        $('#add-action-modal .publish-action, #add-action-modal .save-draft').show();
        
        // Ensure the icon selector is properly initialized
        const addIconContainer = document.querySelector('#add-action-modal .icon-selector-container');
        if (addIconContainer && addIconContainer._iconSelector) {
            // Reset the icon selector
            const typeSelect = addIconContainer.querySelector('.icon-type-selector');
            if (typeSelect) {
                typeSelect.selectedIndex = 0;
                typeSelect.value = '';
                
                // Force a change event to ensure the UI updates
                const changeEvent = new Event('change');
                typeSelect.dispatchEvent(changeEvent);
                

            }
        }
    });

    $('.cancel-button').click(function() {
        $('#add-action-modal').removeClass('active');
        setTimeout(function() {
            $('#add-action-modal').hide();
            $('#add-action-form')[0].reset();
        }, 300);
        $('body').removeClass('modal-open');
    });

    // Add action form submission
    $('#add-action-form').submit(function(e) {
        e.preventDefault();
        
        const name = $('#action-name').val();
        const description = $('#action-description').val();
        const icon = $('#action-icon').val();
        const input = $('#action-input').val();
        const result = $('#action-result').val();
        

        
        if (!name) {
            alert('Please enter an action name');
            return;
        }
        
        $.ajax({
            url: shortcutsHubData.ajaxurl,
            type: 'POST',
            data: {
                action: 'add_action',
                security: shortcutsHubData.security.add_action,
                name: name,
                description: description,
                icon: icon,
                input: input,
                result: result,
                shortcuts: selectedShortcuts
            },
            success: function(response) {
                if (response.success) {
                    // Close modal
                    $('#add-action-modal').removeClass('active').hide();
                    
                    // Reset form
                    $('#add-action-form')[0].reset();
                    selectedShortcuts = [];
                    
                    // Refresh actions list
                    fetchActions();
                    
                    // Show success message
                    showNotification('Action added successfully', 'success');
                } else {
                    showNotification(response.data.message || 'Failed to add action', 'error');
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX error:', error);
                showNotification('An error occurred. Please try again.', 'error');
            }
        });
    });

    // Search and filter functionality
    $('#search-actions-input').on('input', debounce(fetchActions, 300));
    $('#filter-action-status, #filter-action-trash').change(fetchActions);
    $('#reset-action-filters').click(function() {
        $('#search-actions-input').val('');
        $('#filter-action-status').val('');
        $('#filter-action-trash').val('');
        fetchActions();
    });

    // Debounce helper function
    function debounce(func, wait) {
        let timeout;
        return function() {
            const context = this;
            const args = arguments;
            clearTimeout(timeout);
            timeout = setTimeout(function() {
                func.apply(context, args);
            }, wait);
        };
    }

    // View action button click handler
    $(document).on('click', '.view-action', function() {
        const actionId = $(this).data('id');
        const action = actions.find(a => a.id === actionId);
        
        if (action && action.permalink) {
            window.open(action.permalink, '_blank');
        }
    });
    
    // Edit action button click handler
    $(document).on('click', '.edit-action', function() {
        const actionId = $(this).data('id');
        const action = actions.find(a => a.id === actionId);
        
        if (!action) {
            console.error('Action not found:', actionId);
            return;
        }
        
        // Reset form
        $('#edit-action-form')[0].reset();
        
        // Initialize editSelectedShortcuts with the action's shortcuts
        // Handle different formats of shortcuts (array of objects, array of IDs, or mixed)
        if (action.shortcuts) {
            editSelectedShortcuts = action.shortcuts.map(shortcut => {
                if (typeof shortcut === 'number') return shortcut;
                if (typeof shortcut === 'string' && !isNaN(parseInt(shortcut, 10))) return parseInt(shortcut, 10);
                if (typeof shortcut === 'object' && shortcut !== null && (shortcut.ID || shortcut.id)) {
                    return parseInt(shortcut.ID || shortcut.id, 10);
                }
                return null;
            }).filter(id => id !== null);
        } else {
            editSelectedShortcuts = [];
        }
        
        // Populate form fields
        $('#edit-action-id').val(action.id);
        $('#edit-action-name').val(action.name);
        $('#edit-action-description').val(action.description);
        $('#edit-action-input').val(action.input || '');
        $('#edit-action-result').val(action.result || '');
        $('#edit-action-icon').val(action.icon);
        
        // ENHANCED ICON HANDLING: Properly initialize the icon selector with current value
        const iconSelectorContainer = document.querySelector('#edit-action-modal .icon-selector-container');
        if (iconSelectorContainer) {
            // Get the icon preview container
            const previewContainer = iconSelectorContainer.querySelector('.icon-preview');
            

            
            // Process the icon data into a standardized format
            let processedIconData = null;
            
            // Case 1: String that starts with 'fa' (direct Font Awesome class)
            if (typeof action.icon === 'string' && action.icon.startsWith('fa')) {
                processedIconData = {
                    type: 'fontawesome',
                    name: action.icon
                };
            }
            // Case 2: String that starts with '{' (JSON string)
            else if (typeof action.icon === 'string' && action.icon.startsWith('{')) {
                try {
                    processedIconData = JSON.parse(action.icon);
                    

                } catch (e) {
                    console.error('Error parsing icon JSON:', e);

                }
            }
            // Case 3: Already an object
            else if (typeof action.icon === 'object' && action.icon !== null) {
                processedIconData = action.icon;
                

            }
            
            // If we have processed icon data, update the form and preview
            if (processedIconData) {
                // Set the hidden input value with the processed data
                const iconInput = document.getElementById('edit-action-icon');
                if (iconInput) {
                    iconInput.value = JSON.stringify(processedIconData);
                    

                }
                
                // If we have the IconSelector instance, use its API
                if (iconSelectorContainer._iconSelector) {

                    
                    // Create a new instance to ensure clean initialization
                    const editActionIconSelector = new IconSelector({
                        container: iconSelectorContainer,
                        inputField: document.getElementById('edit-action-icon'),
                        onChange: function(value) {
                            // Log any changes

                        }
                    });
                    
                    // Store the new selector instance
                    iconSelectorContainer._iconSelector = editActionIconSelector;
                    
                    // Wait for the next tick to ensure the selector is fully initialized
                    setTimeout(() => {
                        // Then set the icon data
                        iconSelectorContainer._iconSelector.setIcon(
                            processedIconData.type,
                            processedIconData.name,
                            processedIconData.url
                        );
                    }, 0);
                    

                }
                // Direct manipulation if no IconSelector instance
                else if (previewContainer) {
                    previewContainer.classList.remove('empty');
                    
                    if (processedIconData.type === 'fontawesome') {
                        previewContainer.innerHTML = `<i class="${processedIconData.name}"></i>`;
                    } else if (processedIconData.type === 'custom' && processedIconData.url) {
                        previewContainer.innerHTML = `<img src="${processedIconData.url}" alt="Custom Icon">`;
                    }
                    
                        // Always set the type selector to the default empty option
                    const typeSelect = iconSelectorContainer.querySelector('.icon-type-selector');
                    if (typeSelect) {
                        typeSelect.value = '';
                        typeSelect.selectedIndex = 0;
                        
                        // Force a change event to ensure the UI updates
                        const changeEvent = new Event('change');
                        typeSelect.dispatchEvent(changeEvent);
                        

                    }
                    

                }
            } else {
                // Fallback for no valid icon data
                if (previewContainer) {
                    previewContainer.innerHTML = '<i class="fas fa-image"></i>';
                    previewContainer.classList.add('empty');
                }
                

            }
        }
        
        // Initialize shortcuts selector with action ID
        const editShortcutsContainer = document.querySelector('#edit-action-modal .shortcuts-selector-container');
        

        
        if (editShortcutsContainer) {
            // Check if we already have a selector instance
            if (editShortcutsContainer.shortcutsSelector) {
                // Reset the selected shortcuts first to ensure we start fresh
                editShortcutsContainer.shortcutsSelector.selectedShortcuts = [];
                // Update existing instance
                editShortcutsContainer.shortcutsSelector.actionId = actionId;
                

                
                // We'll preload the shortcuts before showing the modal
                // No need to call loadShortcuts() here
            } else {
                // Create new instance
                

                
                const editShortcutsSelector = new ShortcutsSelector({
                    container: editShortcutsContainer,
                    actionId: actionId
                });
            }
        }
        
        // Show the appropriate buttons based on action status
        const status = action.status || 'publish'; // Default to publish if status not available
        
        // Hide all buttons first
        $('#edit-action-form .save-button').hide();
        
        // Show appropriate buttons based on status
        if (status === 'publish') {
            $('#edit-action-form .update-action, #edit-action-form .revert-draft').show();
        } else {
            $('#edit-action-form .publish-action, #edit-action-form .save-draft').show();
        }
        
        // Show modal immediately
        $('#edit-action-modal').show().addClass('active');
        $('body').addClass('modal-open');
        
        // Show loading indicator in the shortcuts list
        if (editShortcutsContainer) {
            const shortcutsList = editShortcutsContainer.querySelector('.shortcuts-list');
            if (shortcutsList) {
                // Use a more subtle loading indicator
                shortcutsList.innerHTML = '<div class="shortcuts-loading">Loading...</div>';
            }
        }
        
        // Load shortcuts in the background
        $.ajax({
            url: shortcutsHubData.ajaxurl,
            type: 'POST',
            data: {
                action: 'fetch_shortcuts_for_action',
                security: shortcutsHubData.security.fetch_shortcuts_for_action,
                action_id: actionId
            },
            success: function(response) {

                
                if (response.success) {
                    const shortcuts = response.data;
                    
                    // Update the shortcuts selector with loaded data
                    if (editShortcutsContainer && editShortcutsContainer.shortcutsSelector) {
                        // Store the shortcuts for client-side filtering
                        editShortcutsContainer.shortcutsSelector.allShortcuts = shortcuts || [];
                        editShortcutsContainer.shortcutsSelector.isLoaded = true;
                        
                        // Directly render the shortcuts without another AJAX call
                        editShortcutsContainer.shortcutsSelector.renderShortcuts(shortcuts);
                        

                    }
                } else {
                    console.error('Failed to load shortcuts:', response.data ? response.data.message : 'Unknown error');
                    // Show error message
                    if (editShortcutsContainer) {
                        const shortcutsList = editShortcutsContainer.querySelector('.shortcuts-list');
                        if (shortcutsList) {
                            shortcutsList.innerHTML = '<div class="shortcuts-list-empty">Failed to load</div>';
                        }
                    }
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX error when loading shortcuts:', error);
                // Show error message
                if (editShortcutsContainer) {
                    const shortcutsList = editShortcutsContainer.querySelector('.shortcuts-list');
                    if (shortcutsList) {
                        shortcutsList.innerHTML = '<div class="shortcuts-list-empty">Error loading</div>';
                    }
                }
            }
        });
    });

    // Edit action form submission
    $('#edit-action-form').submit(function(e) {
        e.preventDefault();
        
        const actionId = $('#edit-action-id').val();
        const name = $('#edit-action-name').val();
        const description = $('#edit-action-description').val();
        let icon = $('#edit-action-icon').val();
        const status = $(e.originalEvent.submitter).data('status') || 'publish';

        
        // Ensure icon is properly formatted
        if (icon && typeof icon === 'string') {
            if (icon.startsWith('fa') && !icon.startsWith('{')) {
                // Convert direct Font Awesome class to proper JSON format
                icon = JSON.stringify({
                    type: 'fontawesome',
                    name: icon
                });
                

            }
        }
        
        // Get input and result values
        const input = $('#edit-action-input').val();
        const result = $('#edit-action-result').val();

        
        // Debug log the form submission data
        console.log('Edit action form submission:', {
            actionId,
            name,
            description,
            icon,
            input,
            result,
            status,
            'submitter': e.originalEvent.submitter,
            'submitter_data': $(e.originalEvent.submitter).data()
        });
        
        if (!name) {
            alert('Please enter an action name');
            return;
        }
        
        if (!actionId) {
            console.error('No action ID provided');
            return;
        }
        
        // Ensure shortcuts is an array of integers
        if (!Array.isArray(editSelectedShortcuts)) {
            if (typeof editSelectedShortcuts === 'string') {
                try {
                    editSelectedShortcuts = JSON.parse(editSelectedShortcuts);
                } catch (e) {
                    editSelectedShortcuts = [];
                }
            } else {
                editSelectedShortcuts = [];
            }
        }
        
        // Make sure all items in the array are integers
        editSelectedShortcuts = editSelectedShortcuts.map(id => {
            // If it's already a number, return it
            if (typeof id === 'number') return id;
            // If it's a string that can be converted to a number, convert it
            if (typeof id === 'string' && !isNaN(parseInt(id, 10))) return parseInt(id, 10);
            // If it's an object with an ID property, use that
            if (typeof id === 'object' && id !== null && (id.ID || id.id)) return parseInt(id.ID || id.id, 10);
            // Otherwise return null (will be filtered out)
            return null;
        }).filter(id => id !== null);
        

        
        // Debug log the AJAX request data
        console.log('Update action AJAX request data:', {
            action: 'update_action',
            security: shortcutsHubData.security.update_action,
            action_id: actionId,
            name: name,
            description: description,
            icon: icon,
            input: input,
            result: result,
            shortcuts: editSelectedShortcuts,
            status: status
        });
        
        $.ajax({
            url: shortcutsHubData.ajaxurl,
            type: 'POST',
            data: {
                action: 'update_action',
                security: shortcutsHubData.security.update_action,
                action_id: actionId,
                name: name,
                description: description,
                icon: icon,
                input: input,
                result: result,
                shortcuts: JSON.stringify(editSelectedShortcuts),
                status: status
            },
            success: function(response) {
                // Debug log the response
                console.log('Update action response:', response);
                
                if (response.success) {
                    console.log('Action updated successfully, refreshing table...');
                    
                    // Close modal
                    $('#edit-action-modal').removeClass('active').hide();
                    
                    // Reset form
                    $('#edit-action-form')[0].reset();
                    editSelectedShortcuts = [];
                    
                    // Remove modal-open class from body to re-enable interaction
                    $('body').removeClass('modal-open');
                    
                    // Force a delay before fetching actions to ensure the server has time to update
                    setTimeout(() => {
                        // Refresh actions list
                        fetchActions();
                        
                        // Show success message
                        showNotification('Action updated successfully', 'success');
                    }, 500);
                } else {
                    showNotification(response.data.message || 'Failed to update action', 'error');
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX error:', error);
                showNotification('An error occurred. Please try again.', 'error');
            }
        });
    });

    // Close edit modal
    $('#edit-action-modal .cancel-button').on('click', function() {
        $('#edit-action-modal').removeClass('active');
        setTimeout(function() {
            $('#edit-action-modal').hide();
            $('#edit-action-form')[0].reset();
        }, 300);
        $('body').removeClass('modal-open');
    });

    // Delete action button click handler
    $(document).on('click', '.delete-action', function() {
        const actionId = $(this).data('id');
        
        // Convert actionId to number for strict comparison
        const action = actions.find(a => parseInt(a.id) === parseInt(actionId));
        
        if (!action) {
            console.error('Action not found:', actionId, 'Available actions:', actions);
            return;
        }

        // Only permanently delete if action is already in trash status
        const permanent = action.post_status === 'trash';
        deleteAction(actionId, permanent);
    });

    /**
     * Delete an action
     * @param {number} id - The ID of the action to delete
     * @param {boolean} perm - Whether to permanently delete the action
     */
    function deleteAction(id, perm) {
        const confirmMessage = perm ? 
            'Are you sure you want to permanently delete this action?' : 
            'Are you sure you want to move this action to trash?';
            
        if (!confirm(confirmMessage)) {
            return;
        }

        $.ajax({
            url: shortcutsHubData.ajaxurl,
            type: 'POST',
            data: {
                action: 'delete_action',
                security: shortcutsHubData.security.delete_action,
                id: id,
                force: perm
            },
            success: function(response) {
                if (response.success) {
                    fetchActions();
                    alert('Action deleted successfully!');
                } else {
                    console.error('Failed to delete action:', response.data.message);
                    alert('Failed to delete action. Please try again.');
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX error:', error);
                alert('Failed to delete action. Please try again.');
            }
        });
    }

    // Load shortcuts for an action
    function loadShortcutsForAction(actionId) {
        
        $.ajax({
            url: shortcutsHubData.ajaxurl,
            type: 'POST',
            data: {
                action: 'fetch_shortcuts_for_action',
                security: shortcutsHubData.security.fetch_shortcuts_for_action,
                action_id: actionId
            },
            success: function(response) {
                if (response.success) {
                    const shortcuts = response.data;
                    

                    
                    // Render shortcuts in the edit modal
                    const $shortcutsContainer = $('#edit-action-modal .shortcuts-container');
                    $shortcutsContainer.empty();
                    
                    if (shortcuts && shortcuts.length > 0) {
                        shortcuts.forEach(function(shortcut) {
                            // Make sure we're handling both the old format (objects) and new format (IDs)
                            let shortcutName = '';
                            let shortcutKey = '';
                            let shortcutId = null;
                            
                            if (typeof shortcut === 'object' && shortcut !== null) {
                                // Old format - object with properties
                                shortcutName = shortcut.post_title || shortcut.name || '';
                                shortcutKey = shortcut.key || '';
                                shortcutId = parseInt(shortcut.ID || shortcut.id, 10);
                            } else if (typeof shortcut === 'number' || (typeof shortcut === 'string' && !isNaN(parseInt(shortcut, 10)))) {
                                // New format - just the ID
                                shortcutId = parseInt(shortcut, 10);
                                shortcutName = 'Shortcut #' + shortcutId;
                                shortcutKey = '';
                            } else {
                                // Unknown format
                                console.warn('Unknown shortcut format:', shortcut);
                                shortcutName = 'Unknown Shortcut';
                                shortcutKey = '';
                                shortcutId = null;
                            }
                            
                            // Skip if we couldn't determine a valid ID
                            if (shortcutId === null) return;
                            
                            const shortcutHtml = `
                                <div class="shortcut">
                                    <span class="shortcut-name">${escapeHtml(shortcutName)}</span>
                                    <span class="shortcut-key">${escapeHtml(shortcutKey)}</span>
                                </div>
                            `;
                            $shortcutsContainer.append(shortcutHtml);
                        });
                    } else {
                        $shortcutsContainer.html('<p>No shortcuts associated with this action.</p>');
                    }
                } else {
                    console.error('Failed to load shortcuts:', response.data ? response.data.message : 'Unknown error');
                    $('#edit-action-modal .shortcuts-container').html('<p>Failed to load shortcuts.</p>');
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX error:', error);
                $('#edit-action-modal .shortcuts-container').html('<p>Error loading shortcuts.</p>');
            }
        });
    }

    // Show loading spinner immediately on page load with forced visibility
    $('#actions-loading').css({
        'display': 'flex',
        'opacity': '1'
    });
    
    // Initial load of actions
    fetchActions();
});
