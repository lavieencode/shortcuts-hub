jQuery(document).ready(function($) {

    function hideShortcutsContainers() {
    $('#switchblade-shortcuts-container').hide();
    $('#wp-shortcuts-container').hide();
    $('#shortcuts-header-bar').hide();
    $('.section-title').hide();
}
    // Function to check URL parameters and switch to versions view if needed
    function checkUrlParameters() {
        const urlParams = new URLSearchParams(window.location.search);
        const view = urlParams.get('view');
        const shortcutId = urlParams.get('id');

        if (view === 'versions' && shortcutId) {
            hideShortcutsContainers();
            switchToVersionsView(shortcutId);
        }
    }

    checkUrlParameters();

    function fetchSBShortcuts() {
        $.ajax({
            url: shortcutsHubData.ajax_url,
            method: 'POST',
            data: {
                action: 'fetch_sb_shortcuts',
                security: shortcutsHubData.security
            },
            success: function(response) {
                if (response.success && response.data && Array.isArray(response.data.shortcuts)) {
                    renderSBShortcuts(response.data.shortcuts);
                } else {
                    console.error('Expected an array of shortcuts, but received:', response.data.shortcuts);
                    $('#switchblade-shortcuts-container').html('<p>No shortcuts found.</p>');
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX error:', status, error);
                console.error('Response Text:', xhr.responseText);
                $('#switchblade-shortcuts-container').html('<p>Error loading shortcuts.</p>');
            }
        });
    }

    fetchSBShortcuts();

    function renderSBShortcuts(shortcuts) {
        const container = $('#switchblade-shortcuts-container');
        container.empty();

        shortcuts.forEach(function(shortcut) {
            const name = shortcut.name || 'Unnamed SB Shortcut';
            const headline = shortcut.headline || 'No headline provided';

            const shortcutElement = $(`
                <div class="shortcut-item" data-id="${shortcut.id}" data-name="${name}">
                    <h3>${name}</h3>
                    <p>${headline}</p>
                    <div class="button-container">
                        <button class="edit-button" data-id="${shortcut.id}">Edit</button>
                        <button class="version-button" data-id="${shortcut.id}">Versions</button>
                        <button class="delete-button" data-id="${shortcut.id}">${shortcut.deleted ? 'Restore' : 'Delete'}</button>
                    </div>
                    ${shortcut.deleted ? '<span class="badge">Deleted</span>' : ''}
                </div>
            `);
            container.append(shortcutElement);
        });

        attachSBShortcutHandlers();
    }

    function attachSBShortcutHandlers() {
        $('.edit-button').on('click', function() {
            const shortcutId = $(this).data('id');
            openEditModal(shortcutId);
        });

        $(document).on('click', '.version-button', function() {
            const shortcutId = $(this).data('id');
            console.log('Version button clicked for shortcut ID:', shortcutId);
            switchToVersionsView(shortcutId);
        });

        // Add handler for "Edit Version" button
        $('.edit-version-button').on('click', function() {
            const shortcutId = $(this).data('shortcut-id');
            const versionId = $(this).data('version-id');
            console.log('Edit Version button clicked for:', { shortcutId, versionId });
            openVersionEditModal(shortcutId, versionId);
        });

        $('.delete-button').on('click', function() {
            const shortcutId = $(this).data('id');
            const isRestore = $(this).text() === 'Restore';
            toggleSBShortcutDeletion(shortcutId, isRestore);
        });
    }

    function openEditModal(shortcutId) {
        $.ajax({
            url: shortcutsHubData.ajax_url,
            method: 'POST',
            data: {
                action: 'get_sb_shortcut_data',
                security: shortcutsHubData.security,
                shortcut_id: shortcutId
            },
            success: function(response) {
                if (response.success) {
                    populateEditModal(response.data);
                    $('#edit-modal').addClass('active');
                    $('body').addClass('modal-open');
                } else {
                    console.error('Error loading SB shortcut data:', response.data.message);
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX error:', status, error);
                console.error('Response Text:', xhr.responseText);
            }
        });
    }

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
                if (response.success && response.data && response.data.versions && Array.isArray(response.data.versions)) {
                    renderVersions(response.data, shortcutId);
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

    function renderVersions(data, shortcutId) {
        const container = $('#versions-container');
        container.empty();

        const shortcutName = data.shortcut_name || 'Unnamed Shortcut';
        $('#shortcut-name-display').text(shortcutName).show();

        const versions = data.versions;
        const filterStatus = $('#filter-version-status').val();
        const filterRequiredUpdate = $('#filter-required-update').val();

        versions.forEach(function(version) {
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
                        ${version.deleted ? '<span class="badge deleted-badge">Deleted</span>' : ''}
                    </div>
                    <div class="version-body" style="display: none;">
                        ${version.notes ? `<p><strong>Notes:</strong> ${version.notes}</p>` : ''}
                        ${version.url ? `<p><strong>URL:</strong> <a href="${version.url}" target="_blank">${version.url}</a></p>` : ''}
                        ${version.minimumiOS ? `<p><strong>Minimum iOS:</strong> ${version.minimumiOS}</p>` : ''}
                        ${version.minimumMac ? `<p><strong>Minimum Mac:</strong> ${version.minimumMac}</p>` : ''}
                        ${version.released ? `<p><strong>Released:</strong> ${new Date(version.released).toLocaleDateString()}</p>` : ''}
                        ${version.state && version.state.label ? `<p><strong>Status:</strong> ${version.state.label}</p>` : ''}
                        <p><strong>Required Update:</strong> ${version.required ? 'Yes' : 'No'}</p>
                        <div class="button-container">
                            <button class="edit-version" data-shortcut-id="${shortcutId}" data-version-id="${version.version}">Edit Version</button>
                            ${version.deleted ? `
                                <button class="restore-version" data-shortcut-id="${shortcutId}" data-version-id="${version.version}">Restore Version</button>
                            ` : `
                                <button class="delete-version" data-shortcut-id="${shortcutId}" data-version-id="${version.version}">Delete Version</button>
                            `}
                        </div>
                    </div>
                </div>`;
            container.append(element);
        });

        attachVersionHandlers();

        if (container.children().length === 0) {
            container.append('<p>No versions found.</p>');
        }
    }

    function attachVersionHandlers() {
        $('.version-header').on('click', function() {
            const versionBody = $(this).next('.version-body');
            versionBody.toggle();

            const caret = $(this).find('.caret');
            if (versionBody.is(':visible')) {
                caret.html('&#9660;'); // Down arrow
            } else {
                caret.html('&#9654;'); // Right arrow
            }
        });

        $('.edit-version').on('click', function() {
            const shortcutId = $(this).data('shortcut-id');
            const versionId = $(this).data('version-id');
            openVersionEditModal(shortcutId, versionId);
        });

        $('.restore-version, .delete-version').on('click', function() {
            const shortcutId = $(this).data('shortcut-id');
            const versionId = $(this).data('version-id');
            const isRestore = $(this).hasClass('restore-version');
            toggleVersionDeletion(shortcutId, versionId, isRestore);
        });
    }

    function updateVersionDeletedState(shortcutId, versionId, isDeleted) {
        $.ajax({
            url: shortcutsHubData.ajax_url,
            method: 'POST',
            data: {
                action: 'update_version',
                shortcut_id: shortcutId,
                version_id: versionId,
                version_data: { deleted: isDeleted },
                security: shortcutsHubData.security,
                _method: 'PATCH'
            },
            success: function(response) {
                if (typeof response === 'object' && response !== null) {
                    showSuccessMessage(isDeleted ? 'Version deleted successfully.' : 'Version restored successfully.');
                    setTimeout(() => {
                        $('#version-feedback-message').text('').hide();
                        $('#edit-version-modal').removeClass('active').hide();
                        $('body').removeClass('modal-open');
                        fetchVersions(shortcutId);
                    }, 1500);
                } else {
                    console.error('Unexpected response format:', response);
                    $('#version-feedback-message').text('Unexpected response format.').css('color', 'red').show();
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX error:', status, error);
                $('#version-feedback-message').text('Error updating version.').css('color', 'red').show();
            }
        });
    }

    function showSuccessMessage(message) {
        const feedbackMessage = $('#version-feedback-message');
        feedbackMessage.text(message).css('color', '#909CFE').show();
        setTimeout(() => {
            feedbackMessage.fadeOut();
        }, 1000);
    }

    function openVersionEditModal(shortcutId, versionId) {
        $('#shortcut-id').val(shortcutId);
        $('#version-id').val(versionId);

        $.ajax({
            url: shortcutsHubData.ajax_url,
            method: 'POST',
            data: {
                action: 'fetch_version',
                shortcut_id: shortcutId,
                version_id: versionId,
                security: shortcutsHubData.security
            },
            success: function(response) {
                if (response.success && response.data) {
                    populateVersionEditModal(response.data.version);
                    $('#edit-version-modal').addClass('active').show();
                    $('body').addClass('modal-open');
                } else {
                    console.error('Failed to fetch version data:', response.data ? response.data.message : 'No data');
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX error:', status, error);
            }
        });
    }

    function populateVersionEditModal(version) {
        $('#version-display').text(version.version || '');
        $('#version-notes').val(version.notes || '');
        $('#version-url').val(version.url || '');
        $('#version-ios').val(version.minimumiOS || '');
        $('#version-mac').val(version.minimumMac || '');
        $('#version-required').val(version.required ? 'true' : 'false');

        if (version.state && version.state.value === 1) {
            $('.publish-button').show();
            $('.switch-to-draft-button').hide();
        } else if (version.state && version.state.value === 0) {
            $('.publish-button').hide();
            $('.switch-to-draft-button').show();
        }
    }

    function deleteVersion(shortcutId, versionId) {
        $.ajax({
            url: shortcutsHubData.ajax_url,
            method: 'POST',
            data: {
                action: 'delete_sb_version',
                security: shortcutsHubData.security,
                shortcut_id: shortcutId,
                version_id: versionId
            },
            success: function(response) {
                if (response.success) {
                    alert('Version deleted successfully.');
                    fetchVersions(shortcutId);
                } else {
                    console.error('Error deleting version:', response.data.message);
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX error:', status, error);
                console.error('Response Text:', xhr.responseText);
            }
        });
    }

    function restoreVersion(shortcutId, versionId) {
        $.ajax({
            url: shortcutsHubData.ajax_url,
            method: 'POST',
            data: {
                action: 'restore_sb_version',
                security: shortcutsHubData.security,
                shortcut_id: shortcutId,
                version_id: versionId
            },
            success: function(response) {
                if (response.success) {
                    alert('Version restored successfully.');
                    fetchVersions(shortcutId);
                } else {
                    console.error('Error restoring version:', response.data.message);
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX error:', status, error);
                console.error('Response Text:', xhr.responseText);
            }
        });
    }

    function populateEditModal(data) {
        $('#edit-shortcut-form #shortcut-id').val(data.id);
        $('#edit-shortcut-form #shortcut-name').val(data.name);
        $('#edit-shortcut-form #shortcut-headline').val(data.headline);
        $('#edit-shortcut-form #shortcut-description').val(data.description);
        $('#edit-shortcut-form #shortcut-website').val(data.website);
        $('#edit-modal').show();
    }

    function toggleSBShortcutDeletion(shortcutId, isRestore) {
        $.ajax({
            url: shortcutsHubData.ajax_url,
            method: 'POST',
            data: {
                action: isRestore ? 'restore_sb_shortcut' : 'delete_sb_shortcut',
                security: shortcutsHubData.security,
                shortcut_id: shortcutId
            },
            success: function(response) {
                if (response.success) {
                    fetchSBShortcuts();
                } else {
                    console.error('Error updating SB shortcut:', response.data.message);
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX error:', status, error);
                console.error('Response Text:', xhr.responseText);
            }
        });
    }

    $('#back-to-shortcuts').on('click', function() {
        $('#versions-container').hide();
        $('#versions-header-bar').hide();
        $('#back-to-shortcuts').hide();
        $('#shortcut-name-display').hide();
        $('#switchblade-shortcuts-container').show();
        $('#wp-shortcuts-container').show();
        $('#shortcuts-header-bar').show();
        $('.section-title').show();
    });

    $('#edit-modal .close-button').on('click', function() {
        $('#edit-modal').removeClass('active');
        $('body').removeClass('modal-open');
    });

    $('#edit-version-modal .close-button').on('click', function() {
        $('#edit-version-modal').removeClass('active').hide();
        $('body').removeClass('modal-open');
        const shortcutId = $('#shortcut-id').val();
        fetchVersions(shortcutId);
    });

    $('#edit-version-modal .save-button').on('click', function(event) {
        event.preventDefault();

        const shortcutId = $('#shortcut-id').val();
        const versionId = $('#version-id').val();
        const versionState = $('#version-status').val();
        const stateValue = versionState === '0' ? 0 : 1;

        const requiredValue = $('#version-required').val() === 'true';

        const versionData = {
            version: $('#version-display').text(),
            notes: $('#version-notes').val(),
            url: $('#version-url').val(),
            state: stateValue,
            minimumiOS: $('#version-ios').val(),
            minimumMac: $('#version-mac').val(),
            required: requiredValue
        };

        $.ajax({
            url: shortcutsHubData.ajax_url,
            method: 'POST',
            data: {
                action: 'update_version',
                shortcut_id: shortcutId,
                version_id: versionId,
                version_data: versionData,
                security: shortcutsHubData.security,
                _method: 'PATCH'
            },
            success: function(response) {
                if (typeof response === 'object' && response !== null) {
                    $('#version-feedback-message').text('Version updated successfully.').css('color', 'green').show();
                    $('#edit-version-modal').removeClass('active').hide();
                    $('body').removeClass('modal-open');
                    fetchVersions(shortcutId);
                } else {
                    console.error('Unexpected response format:', response);
                    $('#version-feedback-message').text('Unexpected response format.').css('color', 'red').show();
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX error:', status, error);
                $('#version-feedback-message').text('Error updating version.').css('color', 'red').show();
            }
        });
    });

    function switchToVersionsView(shortcutId) {
        hideShortcutsContainers();

        const currentUrl = new URL(window.location.href);
        currentUrl.searchParams.set('view', 'versions');
        currentUrl.searchParams.set('id', shortcutId);

        const newUrl = currentUrl.href;

        try {
            window.history.pushState({}, '', newUrl);
        } catch (error) {
            console.error('Error updating URL with pushState:', error);
        }

        $('#versions-container').show();
        $('#versions-header-bar').show();
        $('#back-to-shortcuts').show();

        fetchShortcutName(shortcutId);
        fetchVersions(shortcutId);
    }

    function fetchShortcutName(shortcutId) {
        $.ajax({
            url: shortcutsHubData.ajax_url,
            method: 'POST',
            data: {
                action: 'fetch_wp_shortcut',
                security: shortcutsHubData.security,
                shortcut_id: shortcutId
            },
            success: function(response) {
                if (response.success) {
                    const shortcutName = response.data.name || 'Unnamed Shortcut';
                    $('#shortcut-name-display').text(shortcutName).show();
                } else {
                    console.error('Error fetching shortcut name:', response.data.message);
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX error:', status, error);
                console.error('Response Text:', xhr.responseText);
            }
        });
    }

    // Initialize filters
    $('#versions-filters').on('change', 'select, input', function() {
        const shortcutId = $('#shortcut-id').val();
        fetchVersions(shortcutId);
    });

    $('#filter-version-status, #filter-version-deleted, #filter-required-update, #search-versions-input').on('change keyup', function() {
        fetchVersions(currentShortcutId);
    });

});
