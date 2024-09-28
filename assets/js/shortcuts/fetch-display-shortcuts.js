jQuery(document).ready(function($) {

    function attachEditButtonHandlers() {
        jQuery('.edit-button').on('click', function() {
            const shortcutId = jQuery(this).data('id');
            openEditModal(shortcutId);
        });
    }

    function attachVersionListButtonHandlers() {
        jQuery('.versions-button').on('click', function() {
            const shortcutId = jQuery(this).data('shortcut-id');
            fetchVersions(shortcutId);
        });
    }

    fetchShortcuts();

    function fetchShortcuts() {
        var filterStatus = $('#filter-status').val();
        var filterDeleted = $('#filter-deleted').val();
        var searchTerm = $('#search-shortcuts-input').val();

        $.ajax({
            url: shortcutsHubData.ajax_url,
            method: 'POST',
            data: {
                action: 'fetch_shortcuts',
                security: shortcutsHubData.security,
                filter_status: filterStatus,
                filter_deleted: filterDeleted,
                search: searchTerm
            },
            success: function(response) {
                if (response.success) {
                    displayShortcuts(response.data);
                } else {
                    $('#shortcuts-container').html('<p>Error fetching shortcuts.</p>');
                }
            },
            error: function() {
                $('#shortcuts-container').html('<p>Error fetching shortcuts.</p>');
            }
        });
    }

    function displayShortcuts(shortcuts) {
        const container = $('#shortcuts-container');
        container.empty();

        if (shortcuts.length > 0) {
            shortcuts.forEach(function(shortcut) {
                const element = `
                    <div class="shortcut-item">
                        <h3>${shortcut.name}</h3>
                        <p>${shortcut.headline || 'No headline'}</p>
                        <button class="edit-button shortcuts-button" data-id="${shortcut.id}">Edit</button>
                        <button class="versions-button shortcuts-button" data-shortcut-id="${shortcut.id}">Versions</button>
                    </div>`;
                container.append(element);
            });
        } else {
            container.append('<p>No shortcuts found.</p>');
        }

        attachEditButtonHandlers();
        attachVersionListButtonHandlers();
    }

    function openEditModal(shortcutId) {
        if (!shortcutId) {
            return;
        }

        $.ajax({
            url: shortcutsHubData.ajax_url,
            method: 'POST',
            data: {
                action: 'fetch_single_shortcut',
                id: shortcutId,
                security: shortcutsHubData.security
            },
            success: function(response) {
                if (response.success) {
                    populateEditModal(response.data.shortcut);
                    $('#edit-modal').addClass('active').fadeIn();
                }
            },
            error: function() {
                $('#edit-modal').fadeOut();
            }
        });
    }

    function populateEditModal(data) {
        if (!data || !data.id) {
            return;
        }

        $('#shortcut-id').val(data.id);
        $('#shortcut-name').val(data.name || '');
        $('#shortcut-description').val(data.description || '');
        $('#shortcut-headline').val(data.headline || '');
        $('#shortcut-website').val(data.website || '');
        $('#shortcut-status').val(data.state ? data.state.value : '');
    }

    function setupModalButtons() {
        $('#edit-shortcut-form').on('submit', function(e) {
            e.preventDefault(); // Prevents the form from submitting the old-fashioned way
            submitEditShortcutForm();
        });

        $('.close-button').on('click', function() {
            $('#edit-modal').fadeOut();
        });
    }

    function submitEditShortcutForm() {
        var formData = {
            id: $('#shortcut-id').val(),
            name: $('#shortcut-name').val(),
            headline: $('#shortcut-headline').val(),
            description: $('#shortcut-description').val(),
            website: $('#shortcut-website').val(),
            state: $('#shortcut-status').val()
        };
    
        $.ajax({
            url: shortcutsHubData.ajax_url,
            type: 'POST',
            data: {
                action: 'update_shortcut',
                ...formData,
                security: shortcutsHubData.security
            },
            success: function(response) {
                if (response.success) {
                    $('<p class="success-message">Shortcut updated successfully!</p>').appendTo('#edit-modal .modal-content');
                    
                    setTimeout(function() {
                        $('#edit-modal').fadeOut();
                        $('.success-message').remove();
                        fetchShortcuts(); // Refresh the shortcuts list
                    }, 2000);
                } else {
                    $('<p class="error-message">Failed to update shortcut: ' + response.data + '</p>').appendTo('#edit-modal .modal-content');
                }
            },
            error: function() {
                $('<p class="error-message">AJAX request failed.</p>').appendTo('#edit-modal .modal-content');
            }
        });
    }

    setupModalButtons();
});