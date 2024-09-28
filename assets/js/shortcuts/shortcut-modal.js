jQuery(document).ready(function() {

// Function to open the edit modal and fetch shortcut details
function openEditModal(shortcutId) {
    console.log('Opening edit modal for Shortcut ID:', shortcutId);

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
                populateEditModal(response.data);
                $('#edit-shortcut-modal').fadeIn(); // Show the modal
            } else {
                console.error('Error: ' + response.data.message);
            }
        },
        error: function(xhr) {
            console.error('Failed to fetch shortcut details.');
        }
    });
}

// Function to populate the edit modal with fetched data
function populateEditModal(data) {
    $('#edit-shortcut-name').val(data.name);
    $('#edit-shortcut-description').val(data.description);
    $('#edit-shortcut-headline').val(data.headline);
    $('#edit-shortcut-website').val(data.website);
    $('#edit-shortcut-status').val(data.state.value);
}

// Function to submit the edit form
function submitEditShortcutForm(e) {
    e.preventDefault();
    
    const formData = $('#edit-shortcut-form').serialize();

    $.ajax({
        url: shortcutsHubData.ajax_url,
        method: 'POST',
        data: formData + '&action=edit_shortcut&security=' + shortcutsHubData.security,
        success: function(response) {
            if (response.success) {
                alert('Shortcut updated successfully!');
                $('#edit-shortcut-modal').fadeOut(); // Close the modal after successful submission
            } else {
                alert('Error: ' + response.data.message);
            }
        },
        error: function(xhr) {
            console.error('Failed to update shortcut.');
        }
    });
}

// Function to close the modal
function closeModal() {
    $('#edit-shortcut-modal').fadeOut();
}

});