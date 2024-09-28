jQuery(document).ready(function() {

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