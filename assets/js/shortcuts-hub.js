jQuery(document).ready(function($) {
    var currentPage = 1;
    var currentShortcutId = null;

    fetchShortcuts();

    // Helper: Attach event handlers for editing shortcuts
    function attachEditButtonHandlers() {
        $('.edit-button').on('click', function() {
            const shortcutId = $(this).data('id');
            openEditModal(shortcutId);
        });
    }
    
    // Attach event handlers for version list buttons
    function attachVersionListButtonHandlers() {
        $('.versions-button').on('click', function() {
            const shortcutId = $(this).data('shortcut-id');
            fetchVersions(shortcutId);
            $('#shortcuts-container').hide();
            $('#versions-container').show();
            $('#shortcuts-header').hide();
            $('#versions-header-bar').show();
        });
    }

    // Fetch shortcuts on initial load and whenever search/filter changes
    function fetchShortcuts() {
        var filterStatus = $('#filter-status').val();
        var filterDeleted = $('#filter-deleted').val();
        var searchTerm = $('#search-input').val();

        var statusFilter = filterStatus || null;
        var deletedFilter = filterDeleted || null;

        $.ajax({
            url: shortcutsHubData.ajax_url,
            method: 'POST',
            data: {
                action: 'fetch_shortcuts',
                security: shortcutsHubData.security,
                filter_status: statusFilter,
                filter_deleted: deletedFilter,
                search: searchTerm
            },
            success: function(response) {
                if (response.success && Array.isArray(response.data)) {
                    displayShortcuts(response.data);
                } else {
                    $('#shortcuts-container').html('<p>No shortcuts found.</p>');
                }
            },
            error: function(xhr) {
                $('#shortcuts-container').html('<p>Error loading shortcuts.</p>');
            }
        });
    }

    // Display shortcuts dynamically
    function displayShortcuts(shortcuts) {
        const container = $('#shortcuts-container');
        container.empty();

        if (Array.isArray(shortcuts) && shortcuts.length > 0) {
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
            console.warn('No shortcuts to display:', shortcuts);
            container.append('<p>No shortcuts found.</p>');
        }

        // Attach event handlers to newly created edit and version buttons
        attachEditButtonHandlers();
        attachVersionListButtonHandlers();
    }

    // Add event listeners for filter changes
    $('#filter-status, #filter-deleted, #search-input').on('change keyup', function() {
        fetchShortcuts();
    });

    // Open the shortcut edit modal
    function openEditModal(shortcutId) {
        if (!shortcutId) return;

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
                    $('#edit-modal').addClass('active');
                    $('body').addClass('modal-open');
                }
            },
            error: function() {
                $('#edit-modal').fadeOut();
            }
        });
    }

    // Close the shortcut edit modal
    function closeEditModal() {
        $('#edit-modal').removeClass('active').fadeOut();
        $('body').removeClass('modal-open');
    }

    // Populate shortcut edit modal fields
    function populateEditModal(data) {
        if (!data || !data.id) return;

        $('#shortcut-id').val(data.id);
        $('#shortcut-name').val(data.name || '');
        $('#shortcut-description').val(data.description || '');
        $('#shortcut-headline').val(data.headline || '');
        $('#shortcut-website').val(data.website || '');
        $('#shortcut-status').val(data.state ? data.state.value : '');
    }

    // Submit the shortcut edit form
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
                        closeEditModal();
                        $('.success-message').remove();
                        fetchShortcuts();
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

    // Attach event handlers for the shortcut form
    function setupModalButtons() {
        $('#edit-shortcut-form').on('submit', function(e) {
            e.preventDefault();
            submitEditShortcutForm();
        });

        $('.close-button').on('click', function() {
            closeEditModal();
        });
    }

    // Initialize modal buttons
    setupModalButtons();

    // Fetch versions for a specific shortcut
    function fetchVersions(shortcutId, filters = {}) {
        console.log("Fetching versions for shortcut ID:", shortcutId);

        $.ajax({
            url: shortcutsHubData.ajax_url,
            method: 'POST',
            data: {
                action: 'fetch_versions',
                security: shortcutsHubData.security,
                shortcut_id: shortcutId,
                search_term: filters.searchTerm || '',
                status: filters.status || '',
                deleted: filters.deleted || '',
                required_update: filters.requiredUpdate || ''
            },
            success: function(response) {
                if (response.success) {
                    displayVersions(response.data.versions);
                } else {
                    console.error('Error fetching versions:', response.data);
                    $('#versions-container').html('<p>Error fetching versions.</p>');
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.error('AJAX error fetching versions:', textStatus, errorThrown);
                $('#versions-container').html('<p>AJAX error fetching versions.</p>');
            }
        });
    }

    // Display versions dynamically
    function displayVersions(versions) {
        const container = $('#versions-container');
        container.empty();

        if (versions.length > 0) {
            versions.forEach(function(version) {
                const element = `
                    <div class="version-item">
                        <h4>${version.version_name}</h4>
                        <p>${version.notes || 'No notes available'}</p>
                        <p>Status: ${version.status}</p>
                    </div>`;
                container.append(element);
            });
        } else {
            container.append('<p>No versions found.</p>');
        }
    }

    // Fetch versions on page load
    fetchVersions(currentShortcutId, {});
});