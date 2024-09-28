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
    // Log the shortcut ID being passed to the function
    console.log('Opening edit modal for Shortcut ID:', shortcutId);

    // Make sure we are sending a valid shortcutId
    if (!shortcutId) {
        console.error('No shortcut ID provided.');
        return;
    }

    // Log that the AJAX request is starting
    console.log('Starting AJAX request to fetch single shortcut details.');

    $.ajax({
        url: shortcutsHubData.ajax_url,
        method: 'POST',
        data: {
            action: 'fetch_single_shortcut',
            id: shortcutId,
            security: shortcutsHubData.security
        },
        success: function(response) {
            // Log the raw response from the server
            console.log('AJAX request successful. Raw response:', response);

            if (response.success) {
                // Log the details of the fetched shortcut data
                console.log('Fetched shortcut details successfully:', response.data);

                // Call the populateEditModal function to fill in the modal
                populateEditModal(response.data.shortcuts); 
                $('#edit-modal').fadeIn(); // Show the modal
            } else {
                // Log a specific error message returned from the server
                console.error('Error from server while fetching shortcut details:', response.data.message);
            }
        },
        error: function(xhr, status, error) {
            // Log the specific details of the AJAX failure
            console.error('AJAX error fetching shortcut details. Status:', status, 'Error:', error, 'Response:', xhr.responseText);
        }
    });
}

// Function to populate the edit modal with fetched data
function populateEditModal(data) {
    // Log the data being used to populate the modal
    console.log('Populating the edit modal with the following data:', data);

    if (!data) {
        // Log and exit if no data is provided
        console.error('No data provided to populate the modal.');
        return;
    }

    // Populate the modal fields with the fetched data
    $('#shortcut-name').val(data.name || '');
    $('#shortcut-headline').val(data.headline || '');
    $('#shortcut-description').val(data.description || '');
    $('#shortcut-website').val(data.website || '');
    $('#shortcut-status').val(data.state ? data.state.value : '');

    // Log successful population of the modal
    console.log('Modal populated successfully.');
}

// Function to populate the edit modal with fetched data
function populateEditModal(data) {
    // Log the start of the modal population process
    console.log('Populating edit modal with data:', data);

    // Ensure data exists and is valid
    if (!data) {
        console.error('No data provided to populate the modal.');
        return;
    }

    // Ensure that the required data fields are available
    if (!data.name || !data.description || !data.state) {
        console.error('Missing critical data fields for modal:', {
            name: data.name,
            description: data.description,
            state: data.state
        });
    }

    // Update form inputs with the data provided
    $('#shortcut-name').val(data.name || '');
    $('#shortcut-headline').val(data.headline || '');
    $('#shortcut-description').val(data.description || '');
    $('#shortcut-website').val(data.website || '');
    $('#shortcut-status').val(data.state ? data.state.value : '');

    // Log successful population
    console.log('Modal populated successfully with:', {
        name: data.name,
        headline: data.headline,
        description: data.description,
        website: data.website,
        state: data.state ? data.state.value : 'No state'
    });

    // Display the modal after populating the fields
    $('#edit-modal').fadeIn();
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