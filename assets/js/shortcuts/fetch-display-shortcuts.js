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
            console.log('AJAX request successful. Response:', response);

            if (response.success) {
                displayShortcuts(response.data);  // Call the function to render shortcuts
            } else {
                console.error('Error from server:', response.data);
                $('#shortcuts-container').html('<p>Error fetching shortcuts.</p>');
            }
        },
        error: function(xhr, status, error) {
            console.error('AJAX error fetching shortcuts. Status:', status, 'Error:', error, 'Response:', xhr.responseText);
        }
    });
}

// Function to dynamically render the shortcuts
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

    // Attach event handlers to the dynamically created buttons
    attachEditButtonHandlers();
    attachVersionListButtonHandlers();
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
    console.log('Populating Edit Modal with data:', data);

    // Populate the hidden field for shortcut ID
    if (data.id) {
        $('#shortcut-id').val(data.id); // Populate hidden field with shortcut ID
    } else {
        console.error('No shortcut ID found for the shortcut.');
        return; // Exit if no ID is found
    }

    // Populate other fields in the modal
    $('#shortcut-name').val(data.name || '');
    $('#shortcut-description').val(data.description || '');
    $('#shortcut-headline').val(data.headline || '');
    $('#shortcut-website').val(data.website || '');
    $('#shortcut-status').val(data.state ? data.state.value : '');
}

function setupModalButtons() {
    // Log setup of button handlers
    console.log('Setting up button handlers for the modal.');

    // Attach event handler for form submission
    $('#edit-shortcut-form').on('submit', function(e) {
        e.preventDefault(); // Prevent default form submission behavior
        console.log('Submit button clicked, submitting the form...');

        // Call the submitEditShortcutForm function
        submitEditShortcutForm();
    });

    // Attach event handler for Cancel button
    $('.close-button').on('click', function() {
        console.log('Cancel button clicked, closing the modal.');
        $('#edit-modal').fadeOut();
    });

    // Log successful attachment of handlers
    console.log('Button handlers attached successfully.');
}

function submitEditShortcutForm(e) {

    // Gather form data, including the shortcut ID
    var formData = {
        id: $('#shortcut-id').val(), // Hidden field for shortcut ID
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
                // Log that the shortcut was successfully updated
                console.log('Shortcut updated successfully:', response);

                // Close the modal
                console.log('Closing modal after successful update.');
                $('#edit-modal').fadeOut();

                // Refresh the shortcuts list
                console.log('Refreshing the shortcuts list...');
                fetchShortcuts();  // Call the function to fetch and display the updated shortcuts
            } else {
                // Log the server error message
                console.error('Server returned an error while updating shortcut:', response.data.message);
            }
        },
        error: function(xhr, status, error) {
            // Log detailed information about the failure
            console.error('AJAX request failed. Status:', status, 'Error:', error, 'Response:', xhr.responseText);
        }
    });
}

// Attach submit handler to the edit form
$('#edit-shortcut-form').on('submit', submitEditShortcutForm);

// Log document ready state
console.log('Document ready, setting up modal button handlers.');

$(document).ready(function() {
    setupModalButtons();
}
)});