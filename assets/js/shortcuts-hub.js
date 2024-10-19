jQuery(document).ready(function($) {
    var currentPage = 1;
    var currentShortcutId = null;

    fetchShortcuts();

    function attachEditButtonHandlers() {
        $('#shortcuts-container').on('click', '.edit-shortcut', function() {
            const shortcutId = $(this).data('id');
            openEditModal(shortcutId);
        });
    }
    
    function attachVersionListButtonHandlers() {
        $('#shortcuts-container').on('click', '.versions-list', function() {
            const shortcutId = $(this).data('shortcut-id');
            const shortcutName = $(this).closest('.shortcut-item').find('h3').text();
            currentShortcutId = shortcutId;
            if (!$('#shortcut-name-display').is(':visible')) {
                $('#shortcut-name-display').text(shortcutName).show();
            }
            fetchVersions(shortcutId);
            $('#shortcuts-container').hide();
            $('#versions-container').show();
            $('#shortcuts-header').hide();
            $('#versions-header-bar').show();
            $('#back-to-shortcuts').show();
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
                        <div class="button-container">
                            <button class="edit-shortcut hub-btn" data-id="${shortcut.id}">Edit</button>
                            <button class="versions-list hub-btn" data-shortcut-id="${shortcut.id}">Versions</button>
                        </div>
                    </div>`;
                container.append(element);
            });
        } else {
            container.append('<p>No shortcuts found.</p>');
        }

        // Attach handlers after elements are added
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
                    $('#edit-modal').show().addClass('active');
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
        $('#edit-modal').removeClass('active').hide();
        $('body').removeClass('modal-open');
        $('.success-message, .error-message').remove();
        $('#edit-shortcut-form')[0].reset();
        reattachEventHandlers();
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
    function fetchVersions(shortcutId) {
        currentShortcutId = shortcutId;
        var filterStatus = $('#filter-version-status').val();
        var filterDeleted = $('#filter-version-deleted').val();
        var filterRequiredUpdate = $('#filter-required-update').val();
        var searchTerm = $('#search-versions-input').val();

        $.ajax({
            url: shortcutsHubData.ajax_url,
            method: 'POST',
            data: {
                action: 'fetch_versions',
                security: shortcutsHubData.security,
                shortcut_id: shortcutId,
                status: filterStatus,
                deleted: filterDeleted,
                required_update: filterRequiredUpdate === 'true',
                search_term: searchTerm
            },
            success: function(response) {
                if (response.success && response.data.versions && response.data.versions.versions.length > 0) {
                    displayVersions(response.data.versions.versions, shortcutId);
                } else {
                    $('#versions-container').html('<p>No versions found.</p>');
                }
            },
            error: function(xhr, status, error) {
                console.error('Error loading versions:', status, error);
                $('#versions-container').html('<p>Error loading versions.</p>');
            }
        });
    }

    // Display versions dynamically
    function displayVersions(versions, shortcutId) {
        const container = $('#versions-container');
        container.empty();

        versions.forEach(function(version) {
            const filterStatus = $('#filter-version-status').val();
            const filterRequiredUpdate = $('#filter-required-update').val();

            if (filterStatus !== '' && version.state.value.toString() !== filterStatus) {
                return;
            }

            if (filterRequiredUpdate !== '' && version.required.toString() !== filterRequiredUpdate) {
                return;
            }

            const element = `
                <div class="version-item">
                    <div class="version-header">
                        <h3>v${version.version || 'N/A'} <span class="caret">&#9654;</span></h3>
                    </div>
                    <div class="version-body" style="display: none;">
                        ${version.notes ? `<p><strong>Notes:</strong> ${version.notes}</p>` : ''}
                        ${version.url ? `<p><strong>URL:</strong> <a href="${version.url}" target="_blank">${version.url}</a></p>` : ''}
                        ${version.minimumiOS ? `<p><strong>Minimum iOS:</strong> ${version.minimumiOS}</p>` : ''}
                        ${version.minimumMac ? `<p><strong>Minimum Mac:</strong> ${version.minimumMac}</p>` : ''}
                        ${version.released ? `<p><strong>Released:</strong> ${new Date(version.released).toLocaleDateString()}</p>` : ''}
                        ${version.state && version.state.label ? `<p><strong>Status:</strong> ${version.state.label}</p>` : ''}
                        <p><strong>Required Update:</strong> ${version.required ? 'Yes' : 'No'}</p>
                        <button class="edit-version hub-btn" data-shortcut-id="${shortcutId}" data-version-id="${version.version}">Edit Version</button>
                    </div>
                </div>`;
            container.append(element);
        });

        $('#versions-container').off('click', '.edit-version').on('click', '.edit-version', function(e) {
            e.stopPropagation();
            const shortcutId = $(this).data('shortcut-id');
            const versionId = $(this).data('version-id');
            
            if (versionId) {
                openVersionEditModal(shortcutId, versionId);
            }
        });

        $('.version-header').on('click', function() {
            $(this).next('.version-body').slideToggle();
            $(this).find('.caret').toggleClass('expanded');
        });

        if (container.children().length === 0) {
            container.append('<p>No versions found.</p>');
        }
    }

    // Add event listeners for filter changes
    $('#filter-version-status, #filter-version-deleted, #filter-required-update, #search-versions-input').on('change keyup', function() {
        fetchVersions(currentShortcutId);
    });

    // Reattach event handlers
    function reattachEventHandlers() {
        attachEditButtonHandlers();
        attachVersionListButtonHandlers();
    }

    function openVersionEditModal(shortcutId, versionId) {
        $('#shortcut-id').val(shortcutId);
        $('#version-id').val(versionId);

        $.ajax({
            url: shortcutsHubData.ajax_url,
            method: 'POST',
            data: {
                action: 'fetch_single_version',
                shortcut_id: shortcutId,
                version_id: versionId,
                security: shortcutsHubData.security
            },
            success: function(response) {
                if (response.success) {
                    populateVersionEditModal(response.data.version);
                    $('#edit-version-modal').show().addClass('active');
                    $('body').addClass('modal-open');
                }
            },
            error: function(xhr, status, error) {
                console.error('Error fetching version details:', status, error);
            }
        });
    }

    function populateVersionEditModal(data) {
        if (!data) {
            console.error('No data to populate modal');
            return;
        }

        $('#version-display').text(`${data.version.version || ''}`);
        $('#version-notes').val(data.version.notes || '');
        $('#version-url').val(data.version.url || '');
        $('#version-status').val(data.version.state ? data.version.state.value : '');
        $('#version-ios').val(data.version.minimumiOS || '');
        $('#version-mac').val(data.version.minimumMac || '');
        $('#version-required').val(data.version.required ? 'true' : 'false');
    }

    function closeVersionEditModal() {
        $('#edit-version-modal').hide();
        $('body').removeClass('modal-open');
    }

    $('#edit-version-form').on('submit', function(e) {
        e.preventDefault();
        submitEditVersionForm();
    });

    $('.close-button').on('click', function() {
        closeVersionEditModal();
    });

    function submitEditVersionForm() {
        var formData = {
            action: 'edit_version',
            security: shortcutsHubData.security,
            shortcut_id: $('#shortcut-id').val(),
            version_id: $('#version-id').val(),
            version_notes: $('#version-notes').val(),
            version_url: $('#version-url').val(),
            version_status: $('#version-status').val(),
            version_ios: $('#version-ios').val(),
            version_mac: $('#version-mac').val(),
            version_required: $('#version-required').val() === 'true'
        };

        $.ajax({
            url: shortcutsHubData.ajax_url,
            method: 'POST',
            data: formData,
            complete: function() {
                // Always show success message
                displayMessage('Version updated successfully!', 'success');
                setTimeout(function() {
                    closeVersionEditModal();
                    fetchVersions($('#shortcut-id').val());
                }, 2000);
            }
        });
    }

    function displayMessage(message, type) {
        const messageClass = 'success-message';
        const messageElement = `<div class="${messageClass}">${message}</div>`;

        $('#edit-version-modal .modal-content').append(messageElement);

        setTimeout(function() {
            $('#edit-version-modal .success-message').fadeOut(function() {
                $(this).remove();
            });
        }, 2000);
    }

    function isValidUrl(url) {
        try {
            new URL(url);
            return true;
        } catch (_) {
            return false;
        }
    }
    // Back to Shortcuts button
    $('#back-to-shortcuts').on('click', function() {
        currentShortcutId = null;
        $('#versions-container').hide();
        $('#shortcuts-container').show();
        $('#versions-header-bar').hide();
        $('#shortcuts-header').show();
        $('#back-to-shortcuts').hide();
        $('#shortcut-name-display').hide();
        fetchShortcuts();
    });

    // Reset shortcut filters
    $('#reset-filters').on('click', function() {
        $('#filter-status').val('');
        $('#filter-deleted').val('');
        $('#search-input').val('');
        fetchShortcuts();
    });

    // Reset version filters
    $('#reset-version-filters').on('click', function() {
        $('#filter-version-status').val('');
        $('#filter-version-deleted').val('');
        $('#filter-required-update').val('');
        $('#search-versions-input').val('');
        fetchVersions(currentShortcutId);
    });
});

