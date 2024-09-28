jQuery(document).ready(function($) {
    var currentPage = 1;
    var currentShortcutId = null;

    fetchShortcuts();

    // Fetch shortcuts on initial load and whenever search/filter changes
    function fetchShortcuts() {
        var filterStatus = $('#filter-status').val();
        var filterDeleted = $('#filter-deleted').val();
        var searchTerm = $('#search-input').val();

        console.log('Fetching shortcuts with the following filters:');
        console.log('Status:', filterStatus || 'No filter applied');
        console.log('Deleted:', filterDeleted || 'No filter applied');
        console.log('Search Term:', searchTerm || 'No search term');

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
                console.log('Response from server:', response);
                if (response.success) {
                    displayShortcuts(response.data.shortcuts);
                } else {
                    $('#shortcuts-container').html('<p>No shortcuts found.</p>');
                }
            },
            error: function(xhr) {
                console.error('Error loading shortcuts:', xhr);
                $('#shortcuts-container').html('<p>Error loading shortcuts.</p>');
            }
        });
    }

    // Display shortcuts dynamically
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

        // Attach event handlers to newly created edit and version buttons
        attachEditButtonHandlers();
        attachShortcutVersionsButtonHandlers();
    }

    // Attach event handlers for edit buttons
    function attachEditButtonHandlers() {
        $('.edit-button').on('click', function() {
            var id = $(this).data('id');
            openEditModal(id); // Open the modal for editing
        });
    }

    // Attach event handlers for versions buttons
    function attachShortcutVersionsButtonHandlers() {
        $('.versions-button').on('click', function() {
            var shortcutId = $(this).data('shortcut-id');  // Get the shortcut ID from the button
            console.log(`Loading versions for Shortcut ID: ${shortcutId}`);

            if (!shortcutId) {
                console.error('Shortcut ID is missing.');
                return;
            }

            loadVersions(shortcutId);  // Call loadVersions with the shortcut ID
        });
    }

    // Open and populate the edit modal
    function openEditModal(id) {
        const modal = $('#edit-modal');
        modal.data('id', id); // Store the ID in the modal for later access
        modal.show().addClass('active');
        $('body').addClass('modal-active');

        // Fetch and display data for the shortcut to be edited
        $.ajax({
            url: shortcutsHubData.ajax_url,
            method: 'POST',
            data: {
                action: 'fetch_single_shortcut',
                id: id,  // Pass the shortcut ID here
                security: shortcutsHubData.security
            },
            success: function(response) {
                if (response.success) {
                    populateEditModal(response.data.shortcut); // Populate the modal with fetched data
                } else {
                    console.error('Failed to fetch shortcut details:', response);
                }
            },
            error: function(xhr) {
                console.error('Error loading shortcut data:', xhr.responseText);
            }
        });
    }

    // Populate shortcut edit modal with fetched data
    function populateEditModal(shortcut) {
        $('#shortcut-name').val(shortcut.name);
        $('#shortcut-headline').val(shortcut.headline || '');
        $('#shortcut-description').val(shortcut.description || '');
        $('#shortcut-website').val(shortcut.website || '');
        $('#shortcut-state').val(shortcut.state.value);
    }

    // Save changes in the modal and update shortcut data
    $('#edit-shortcut-form').on('submit', function(e) {
        e.preventDefault();

        var id = $('#edit-modal').data('id'); // Get the stored shortcut ID
        var name = $('#shortcut-name').val();
        var headline = $('#shortcut-headline').val();
        var description = $('#shortcut-description').val();
        var website = $('#shortcut-website').val();
        var state = $('#shortcut-state').val();

        $.ajax({
            url: shortcutsHubData.ajax_url,
            method: 'POST',
            data: {
                action: 'update_shortcut',
                id: id,  // Pass the shortcut ID here
                security: shortcutsHubData.security,
                name: name,
                headline: headline,
                description: description,
                website: website,
                state: state
            },
            success: function(response) {
                if (response.success) {
                    alert('Shortcut updated successfully!');
                    closeModal();  // Close the modal after update
                    fetchShortcuts();  // Refresh the list after update
                } else {
                    console.error('Failed to update shortcut:', response);
                }
            },
            error: function(xhr) {
                console.error('Error updating shortcut:', xhr.responseText);
            }
        });
    });

    // Close modal and reset UI
    function closeModal() {
        $('#edit-modal').removeClass('active');
        $('body').removeClass('modal-active');
    }

    $('.close-button').on('click', function() {
        closeModal();
    });

    // Function to load the shortcut name
function loadShortcutName(shortcutId) {
    $.ajax({
        url: shortcutsHubData.ajax_url,
        method: 'POST',
        data: {
            action: 'fetch_single_shortcut',
            shortcut_id: shortcutId,
            security: shortcutsHubData.security
        },
        success: function(response) {
            console.log('Shortcut response:', response);

            if (response.success && response.data.shortcut) {
                $('#shortcut-name-display').text(response.data.shortcut.name).show();
            } else {
                console.error('Failed to load shortcut name.');
            }
        },
        error: function(xhr) {
            console.error('Error loading shortcut name:', xhr.responseText);
        }
    });
}


// Fetch versions for a specific shortcut with updated filters
function loadVersions(shortcutId) {
    currentShortcutId = shortcutId;  // Set currentShortcutId globally

    console.log('Loading versions for shortcut ID:', shortcutId); // Log shortcutId

    // First, hide the shortcuts and display the versions section
    $('#shortcuts-header').hide(); // Hide shortcuts header
    $('#shortcuts-container').hide(); // Hide shortcuts container
    $('#versions-header-bar').show(); // Show versions header bar
    $('#versions-container').html('Loading versions...').show(); // Show versions container with loading text

    // Get the filter values from the UI inputs
    var searchTerm = $('#search-versions-input').val();
    var status = $('#filter-version-status').val();
    var deleted = $('#filter-version-deleted').val();
    var requiredUpdate = $('#filter-required-update').val();

    console.log('Filter values: ', {
        searchTerm,
        status,
        deleted,
        requiredUpdate
    });

    // Make an AJAX call to fetch the versions
    $.ajax({
        url: shortcutsHubData.ajax_url,
        method: 'POST',
        data: {
            action: 'fetch_versions',
            shortcut_id: shortcutId,
            search_term: searchTerm,
            status: status,
            deleted: deleted,
            required_update: requiredUpdate,
            security: shortcutsHubData.security
        },
        success: function(response) {
            console.log('Full response:', response);

            // Ensure that the response contains the correct data structure
            if (response.success && response.data && response.data.versions && response.data.versions.versions) {
                console.log('Versions found:', response.data.versions.versions);
                displayVersions(response.data.versions.versions);  // Display versions
            } else {
                console.warn('No versions found for shortcut ID:', shortcutId);
                $('#versions-container').html('<p>No versions found.</p>');  // Display no versions message
            }
        },
        error: function(xhr) {
            console.error('Error loading versions:', xhr.responseText);
            $('#versions-container').html('<p>Error loading versions.</p>');  // Display error message
        }
    });
}

function displayVersions(versions) {
    const container = $('#versions-container');
    container.empty();

    if (versions.length > 0) {
        versions.forEach(function(versionData) {
            const versionNumber = versionData.version || 'Unknown Version';  // Ensure this is the correct version number
            const notes = versionData.notes || 'No notes available';
            const required = versionData.required ? 'Required' : 'Optional';
            const state = versionData.state ? versionData.state.label : 'Unknown';

            // Ensure that the button has the correct data-version-number attribute
            const element = `
                <div class="version-item">
                    <div class="version-header">
                        <h3>Version ${versionNumber}</h3>
                        <span class="caret">&#9656;</span>
                    </div>
                    <div class="version-details" style="display: none;">
                        <p><strong>Status:</strong> ${state}</p>
                        <p><strong>Notes:</strong> ${notes}</p>
                        <p><strong>Required Update:</strong> ${required}</p>
                        <button class="edit-version-btn shortcuts-button" data-version-number="${versionNumber}">Edit Version</button>
                    </div>
                </div>`;

            container.append(element);
        });

        attachCaretToggleHandlers();
        attachVersionEditButtonHandlers();
    } else {
        container.append('<p>No versions found.</p>');
    }
}

    // Attach caret toggle handler for version details
function attachCaretToggleHandlers() {
    $('.version-header').on('click', function() {
        const caret = $(this).find('.caret');
        const details = $(this).next('.version-details');

        // Toggle the caret direction and the visibility of the details
        details.slideToggle();
        caret.toggleClass('expanded');
    });
}

function attachVersionEditButtonHandlers() {
    $('.edit-version-btn').on('click', function() {
        const versionNumber = $(this).data('version-number');  // Get version number
        const shortcutId = currentShortcutId;  // Use global currentShortcutId

        console.log(`Opening version edit modal for Shortcut ID: ${shortcutId}, Version Number: ${versionNumber}`);

        openVersionEditModal(shortcutId, versionNumber);  // Pass both shortcutId and versionNumber
    });
}

// Open and populate the version edit modal with correct version data
function openVersionEditModal(shortcutId, versionNumber) {
    const modal = $('#edit-version-modal');
    modal.show().addClass('active');
    $('body').addClass('modal-active');

    console.log(`Opening version edit modal for Shortcut ID: ${shortcutId}, Version Number: ${versionNumber}`);

    // Make an AJAX request to fetch version details using the shortcut ID and version number
    $.ajax({
        url: shortcutsHubData.ajax_url,
        method: 'POST',
        data: {
            action: 'fetch_single_version',
            shortcut_id: shortcutId,  // Pass shortcut ID
            version_number: versionNumber,  // Pass version number
            security: shortcutsHubData.security
        },
        success: function(response) {
            console.log('Version details response:', response);

            // Ensure we have a valid response and version data
            if (response.success && response.data && response.data.version && response.data.version.version) {
                populateVersionModal(response.data.version.version);  // Populate the modal with version data
            } else {
                console.error('Failed to load version details:', response);
                alert('Error loading version details. Please try again.');
            }
        },
        error: function(xhr) {
            console.error('Error fetching version details:', xhr.responseText);
            alert('Error fetching version details. Please try again.');
        }
    });
}

// Function to populate the version edit modal with the fetched version data
function populateVersionModal(versionData) {
    if (!versionData) {
        console.error('Version data is missing.');
        return;
    }

    console.log('Populating version modal with the following data:', versionData);

    // Populate the hidden version ID
    $('#version-id').val(versionData.version || 'Unknown Version');  // Ensure version ID is set correctly

    // Populate version name (version number)
    $('#version-name').val(versionData.version);

    // Populate release notes
    $('#version-notes').val(versionData.notes);

    // Populate download URL
    $('#version-url').val(versionData.url);

    // Populate status (0 is Published, 1 is Draft)
    if (versionData.version.state && versionData.version.state.value !== undefined) {
        $('#version-status').val(versionData.version.state.value.toString()); }

    // Populate minimum iOS version
    $('#version-ios').val(versionData.minimumiOS || '');

    // Populate minimum Mac version
    $('#version-mac').val(versionData.minimumMac || '');

    // Populate required update field
    $('#version-required').val(versionData.required ? 'true' : 'false');
}

// Save changes in the modal and update version data
$('#edit-version-form').on('submit', function(e) {
    e.preventDefault();

    const versionId = $('#version-id').val();  // Get the version ID from the hidden field
    const versionName = $('#version-name').val();  // Get the version number
    const versionNotes = $('#version-notes').val();  // Get the release notes
    const versionUrl = $('#version-url').val();  // Get the download URL
    const versionStatus = $('#version-status').val();  // Get the version status (Published, Draft, etc.)
    const minimumiOS = $('#version-ios').val();  // Get the minimum iOS version
    const minimumMac = $('#version-mac').val();  // Get the minimum Mac version
    const requiredUpdate = $('#version-required').val();  // Get the required update status

    // Make an AJAX request to update the version with all fields
    $.ajax({
        url: shortcutsHubData.ajax_url,
        method: 'PATCH',  // Use PATCH for updates
        data: {
            action: 'update_version',
            version_id: versionId,  // Pass the version ID
            security: shortcutsHubData.security,
            version_name: versionName,
            version_notes: versionNotes,
            version_url: versionUrl,
            status: versionStatus,  // Send version status (Published, Draft, etc.)
            minimum_ios: minimumiOS,  // Send minimum iOS version
            minimum_mac: minimumMac,  // Send minimum macOS version
            required_update: requiredUpdate  // Send required update status
        },
        success: function(response) {
            if (response.success) {
                alert('Version updated successfully!');
                closeVersionModal();  // Close the modal after update
                loadVersions(currentShortcutId);  // Refresh the versions list after update
            } else {
                console.error('Failed to update version:', response);
                alert('Failed to update version. Please try again.');
            }
        },
        error: function(xhr) {
            console.error('Error updating version:', xhr.responseText);
            alert('Error updating version. Please try again.');
        }
    });
});

    function closeVersionModal() {
        $('#edit-version-modal').removeClass('active');
        $('body').removeClass('modal-active');
    }

    $('.close-button').on('click', function() {
        closeVersionModal();
    });

    $('#back-to-shortcuts-btn').on('click', function() {
        $('#versions-header-bar').hide();
        $('#versions-container').hide();
        $('#shortcuts-header').show();
        $('#shortcuts-container').show();
    });

    $('#filter-status, #filter-deleted, #search-input').on('change input', function() {
        fetchShortcuts();
    });

    $('#reset-filters').on('click', function() {
        $('#filter-status, #filter-deleted').val('');
        $('#search-input').val('');
        fetchShortcuts();
    });

    $('#reset-version-filters').on('click', function() {
        $('#search-versions-input').val('');       
        $('#filter-version-status').val('');       
        $('#filter-version-deleted').val('');      
        $('#filter-required-update').val('');      

        loadVersions(currentShortcutId);
    });
});