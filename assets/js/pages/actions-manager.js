jQuery(document).ready(function($) {
    let actions = []; // Store fetched actions

    // Wait for IconSelector to be available
    if (typeof IconSelector === 'undefined') {
        console.error('IconSelector not loaded. Please check script dependencies.');
        return;
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
        }
    }

    // Initialize icon selectors
    initializeIconSelectors();

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

    // Initial load of actions
    loadActions();

    // Load actions list
    function loadActions() {
        const search = $('#search-actions-input').val();
        const status = $('#filter-action-status').val();
        const trash = $('#filter-action-trash').val();

        $.ajax({
            url: shortcutsHubData.ajaxurl,
            type: 'POST',
            data: {
                action: 'fetch_actions',
                security: shortcutsHubData.security.fetch_actions,
                search: search,
                status: status,
                trash: trash
            },
            success: function(response) {
                if (response.success) {
                    actions = response.data; // Store the raw posts
                    renderActions(actions);
                } else {
                    console.error('Failed to fetch actions:', response.data ? response.data.message : 'Unknown error');
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX error:', error);
            }
        });
    }

    // Render actions in the container
    function renderActions(actions) {
        const container = $('#actions-container');
        container.empty();
        
        if (!actions || actions.length === 0) {
            container.html('<p class="no-items">No actions to show</p>');
            return;
        }
        
        // Create table structure
        const table = $('<table>').addClass('actions-table');
        const thead = $('<thead>').append(`
            <tr>
                <th class="name-column">Action</th>
                <th class="description-column">Description</th>
                <th class="shortcuts-column">Shortcuts</th>
                <th class="status-column">Status</th>
                <th class="actions-column">Actions</th>
            </tr>
        `);
        const tbody = $('<tbody>');

        actions.forEach(function(action) {
            const statusClass = action.post_status === 'publish' ? 'status-published' : 'status-draft';
            const statusText = action.post_status === 'publish' ? 'Published' : 'Draft';
            const shortcutCount = parseInt(action.shortcut_count, 10) || 0;
            
            const row = $('<tr>').addClass('action-row').append(`
                <td class="name-column">
                    <div class="action-name-container">
                        <div class="action-icon">
                            ${renderActionIcon(action.icon)}
                        </div>
                        <span class="action-name">${escapeHtml(action.post_title)}</span>
                    </div>
                </td>
                <td class="description-column">
                    <div class="action-description">${escapeHtml(action.post_content)}</div>
                </td>
                <td class="shortcuts-column">
                    <span class="shortcut-count">${shortcutCount}</span>
                </td>
                <td class="status-column">
                    <span class="status-badge ${statusClass}">${statusText}</span>
                </td>
                <td class="actions-column">
                    <button class="edit-action" data-id="${action.ID}" title="Edit">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="delete-action" data-id="${action.ID}" title="Delete">
                        <i class="fas fa-trash-alt"></i>
                    </button>
                </td>
            `);
            tbody.append(row);
        });

        table.append(thead).append(tbody);
        container.append(table);
    }

    // Helper function to render action icon
    function renderActionIcon(iconData) {
        if (!iconData) return '';
        
        try {
            // Handle double-escaped JSON
            let icon;
            try {
                // First try parsing as is
                icon = JSON.parse(iconData);
            } catch (e) {
                // If that fails, try unescaping first
                const unescapedData = iconData.replace(/\\/g, '');
                icon = JSON.parse(unescapedData);
            }
            
            if (icon.type === 'fontawesome') {
                return `<i class="${icon.name}"></i>`;
            } else if (icon.type === 'custom') {
                return `<img src="${icon.url}" alt="Action icon">`;
            }
            return '';
        } catch (e) {
            // DEBUG: Log icon parsing error
            sh_debug_log('Icon Parse Error', {
                'message': 'Failed to parse icon data',
                'source': {
                    'file': 'actions-manager.js',
                    'line': 'renderActionIcon',
                    'function': 'renderActionIcon'
                },
                'data': {
                    'iconData': iconData,
                    'error': e.message
                },
                'debug': true
            });
            return '';
        }
    }

    // Helper function to escape HTML
    function escapeHtml(unsafe) {
        return unsafe
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

    // Modal functionality
    $('#add-new-action').click(function() {
        $('#add-action-modal').show().addClass('active');
        $('body').addClass('modal-open');
        // Show both buttons for new actions
        $('#add-action-modal .publish-action, #add-action-modal .save-draft').show();
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
    $('#add-action-form').on('submit', function(e) {
        e.preventDefault();
        const $form = $(this);
        const $submitButton = $(document.activeElement);
        const status = $submitButton.data('status') || 'publish';  // Default to publish if not set

        const formData = {
            name: $('#action-name').val(),
            description: $('#action-description').val(),
            icon: $('#action-icon').val(),
            status: status
        };

        $.ajax({
            url: shortcutsHubData.ajaxurl,
            type: 'POST',
            data: {
                action: 'create_action',
                security: shortcutsHubData.security.create_action,
                formData: formData
            },
            beforeSend: function() {
                $form.find('button').prop('disabled', true);
            },
            success: function(response) {
                if (response.success) {
                    $('#add-action-modal').removeClass('active').hide();
                    $('body').removeClass('modal-open');
                    loadActions();
                    $form[0].reset();
                } else {
                    console.error('Failed to create action:', response.data.message);
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX error:', error);
            },
            complete: function() {
                $form.find('button').prop('disabled', false);
            }
        });
    });

    // Search and filter functionality
    $('#search-actions-input').on('input', debounce(loadActions, 300));
    $('#filter-action-status, #filter-action-trash').change(loadActions);
    $('#reset-action-filters').click(function() {
        $('#search-actions-input').val('');
        $('#filter-action-status').val('');
        $('#filter-action-trash').val('');
        loadActions();
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

    // Edit action button click handler
    $(document).on('click', '.edit-action', function() {
        const actionId = parseInt($(this).data('id'), 10);
        const action = actions.find(a => parseInt(a.ID, 10) === actionId);
        
        if (action) {
            // DEBUG: Log action data when opening edit modal
            sh_debug_log('Edit Modal Action Data', {
                'message': 'Opening edit modal with action data',
                'source': {
                    'file': 'actions-manager.js',
                    'line': 'editActionClickHandler',
                    'function': 'editActionClickHandler'
                },
                'data': {
                    'action': action,
                    'status': action.post_status
                },
                'debug': true
            });

            const $modal = $('#edit-action-modal');
            
            // Set form values
            $('#edit-action-id').val(action.ID);
            $('#edit-action-name').val(action.post_title);
            $('#edit-action-description').val(action.post_content);
            
            // Handle icon data
            try {
                let iconData = action.icon ? JSON.parse(action.icon.replace(/\\/g, '')) : null;
                if (iconData) {
                    $('#edit-action-icon').val(action.icon);
                    $('#edit-action-icon-type').val(iconData.type);
                    
                    if (iconData.type === 'fontawesome') {
                        $('#edit-custom-icon-upload').hide();
                        $('#edit-icon-selector').show();
                        const iconPreview = $('#edit-action-modal .icon-preview');
                        iconPreview.removeClass('empty').html(`<i class="${iconData.name}"></i>`);
                    } else if (iconData.type === 'custom') {
                        $('#edit-icon-selector').hide();
                        $('#edit-custom-icon-upload').show();
                        const iconPreview = $('#edit-action-modal .icon-preview');
                        iconPreview.removeClass('empty').html(`<img src="${iconData.url}" alt="Action icon">`);
                    }
                } else {
                    // Reset icon selector if no icon data
                    $('#edit-action-icon').val('');
                    $('#edit-action-icon-type').val('fontawesome');
                    $('#edit-custom-icon-upload').hide();
                    $('#edit-icon-selector').show();
                    $('#edit-action-modal .icon-preview').addClass('empty').empty();
                }
            } catch (e) {
                console.error('Error parsing icon data:', e);
                // Reset icon selector on error
                $('#edit-action-icon').val('');
                $('#edit-action-icon-type').val('fontawesome');
                $('#edit-custom-icon-upload').hide();
                $('#edit-icon-selector').show();
                $('#edit-action-modal .icon-preview').addClass('empty').empty();
            }
            
            // Show/hide buttons based on current status
            const $updateBtn = $modal.find('.update-action');
            const $publishBtn = $modal.find('.publish-action');
            const $saveDraftBtn = $modal.find('.save-draft');
            const $revertDraftBtn = $modal.find('.revert-draft');
            
            // Hide all buttons first
            $updateBtn.hide();
            $publishBtn.hide();
            $saveDraftBtn.hide();
            $revertDraftBtn.hide();
            
            // Show appropriate buttons based on status
            if (action.post_status === 'publish') {
                $updateBtn.show();
                $revertDraftBtn.show();
            } else {
                $publishBtn.show();
                $saveDraftBtn.show();
            }
            
            // Set modal status attribute
            $modal.attr('data-status', action.post_status);
            
            // Show modal
            $modal.show().addClass('active');
            $('body').addClass('modal-open');
        } else {
            console.error('Action not found:', actionId, 'Available actions:', actions);
        }
    });

    // Edit action form submission
    $('#edit-action-form').on('submit', function(e) {
        e.preventDefault();
        const $form = $(this);
        const $submitButton = $(document.activeElement);
        const status = $submitButton.data('status');
        const actionId = parseInt($('#edit-action-id').val(), 10);
        
        const formData = {
            id: actionId,
            name: $('#edit-action-name').val(),
            description: $('#edit-action-description').val(),
            icon: $('#edit-action-icon').val(),
            status: status
        };

        $.ajax({
            url: shortcutsHubData.ajaxurl,
            type: 'POST',
            data: {
                action: 'update_action',
                security: shortcutsHubData.security.update_action,
                formData: formData
            },
            beforeSend: function() {
                $form.find('button').prop('disabled', true);
            },
            success: function(response) {
                if (response.success) {
                    $('#edit-action-modal').removeClass('active').hide();
                    $('body').removeClass('modal-open');
                    loadActions();
                } else {
                    console.error('Failed to update action:', response.data ? response.data.message : 'Unknown error');
                    alert('Failed to update action. Please try again.');
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX error:', error);
                alert('Failed to update action. Please try again.');
            },
            complete: function() {
                $form.find('button').prop('disabled', false);
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
            console.error('Action not found:', actionId);
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
                    loadActions();
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
});
