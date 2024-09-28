jQuery(document).ready(function($) {

// Attach event handlers for the edit modal buttons
function attachEditButtonHandlers() {
    // Open the edit modal when the edit button is clicked
    jQuery('.edit-button').on('click', function() {
        const shortcutId = jQuery(this).data('id');
        openEditModal(shortcutId);  // Opens the modal and fetches the data
    });
}

function attachVersionListButtonHandlers() {
    jQuery('.versions-button').on('click', function() {
        const shortcutId = jQuery(this).data('shortcut-id');
        console.log('Switching to versions list for Shortcut ID:', shortcutId);

        fetchVersions(shortcutId);
    });
}

// Trigger fetching of shortcuts when the document is ready
fetchShortcuts();

// Function to fetch and display the shortcuts
function fetchShortcuts() {
    console.log('Fetching shortcuts...');

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
                console.log('Shortcuts fetched successfully:', response.data.shortcuts);
                displayShortcuts(response.data.shortcuts); // Pass the shortcuts array directly to the display function
            } else {
                console.error('Error fetching shortcuts:', response.data.message);
                $('#shortcuts-container').html('<p>Error fetching shortcuts.</p>');
            }
        },
        error: function(xhr) {
            console.error('AJAX error fetching shortcuts.');
            $('#shortcuts-container').html('<p>Error fetching shortcuts.</p>');
        }
    });
}

// Function to display shortcuts dynamically
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
    attachVersionListButtonHandlers();
}

// Function to open the edit modal and fetch shortcut details
function openEditModal(shortcutId) {
    console.log('Opening edit modal for Shortcut ID:', shortcutId);

    // Make sure we are sending a valid shortcutId
    if (!shortcutId) {
        console.error('No shortcut ID provided.');
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
            console.log('AJAX request successful. Raw response:', response);

            if (response.success) {
                console.log('Fetched shortcut details successfully:', response.data);

                // Populate modal with shortcut details
                populateEditModal(response.data.shortcut);

                // Log to ensure this point is reached
                console.log('Populating modal and showing it.');

                // Add the active class to display the modal
                $('#edit-modal').addClass('active').fadeIn();
            } else {
                console.error('Error from server while fetching shortcut details:', response.data.message);
            }
        },
        error: function(xhr, status, error) {
            console.error('AJAX error fetching shortcut details. Status:', status, 'Error:', error, 'Response:', xhr.responseText);
        }
    });
}

// Function to populate the edit modal with fetched data
function populateEditModal(data) {
    // Log the data object to inspect its structure
    console.log('Populating Edit Modal with data:', data);

    // Check if the data object is valid
    if (!data) {
        console.error('No data received.');
        return;
    }

    // Since the data is not wrapped inside a 'shortcut' object, use data directly
    const shortcut = data;

    // Log the shortcut details to ensure it's correctly received
    console.log('Shortcut details:', shortcut);

    // Populate the modal fields with the shortcut data
    $('#shortcut-name').val(shortcut.name || '');
    $('#shortcut-description').val(shortcut.description || '');
    $('#shortcut-headline').val(shortcut.headline || '');
    $('#shortcut-website').val(shortcut.website || '');
    
    if (shortcut.state && shortcut.state.value !== undefined) {
        $('#shortcut-status').val(shortcut.state.value);
    } else {
        console.error('No state value found for shortcut');
    }

    console.log('Edit Modal populated successfully');
}

function setupModalButtons() {
    // Log setup of button handlers
    console.log('Setting up button handlers for the modal.');

    // Attach event handlers for Save and Cancel buttons
    $('#edit-shortcut-form').on('submit', function(e) {
        e.preventDefault();
        console.log('Submit button clicked, submitting the form...');
        submitEditShortcutForm();
    });

    $('.close-button').on('click', function() {
        console.log('Cancel button clicked, closing the modal.');
        $('#edit-modal').fadeOut();
    });

    // Log successful attachment of handlers
    console.log('Button handlers attached successfully.');
}

function submitEditShortcutForm() {
    // Gather form data
    var formData = {
        name: $('#shortcut-name').val(),
        headline: $('#shortcut-headline').val(),
        description: $('#shortcut-description').val(),
        website: $('#shortcut-website').val(),
        state: $('#shortcut-status').val()
    };

    // Log form data before submission
    console.log('Submitting edit form with data:', formData);

    // Perform AJAX request to submit form data
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
                console.log('Shortcut updated successfully:', response);

                // Log that modal will close on success
                console.log('Closing modal after successful update.');
                $('#edit-modal').fadeOut(); // Close the modal on success
            } else {
                // Log the error message from the server
                console.error('Server returned an error while updating shortcut:', response.data.message);
            }
        },
        error: function(xhr, status, error) {
            // Log detailed information about the failure
            console.error('AJAX request failed. Status:', status, 'Error:', error, 'Response:', xhr.responseText);
        }
    });
}

// Log document ready state
console.log('Document ready, setting up modal button handlers.');

$(document).ready(function() {
    setupModalButtons();
}
)});