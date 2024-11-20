function toggleDelete(id, isRestore, buttonElement) {
    const loadingText = isRestore ? 'Restoring...' : 'Deleting...';
    jQuery(buttonElement).text(loadingText).prop('disabled', true);

    jQuery.ajax({
        url: shortcutsHubData.ajax_url,
        method: 'POST',
        data: {
            action: 'toggle_delete',
            security: shortcutsHubData.security,
            id: id
        },
        success: function(response) {
            if (response.success) {
                const id = response.data.id;

                if (id) {
                    const deletedStatus = !isRestore; // Set to true if deleting, false if restoring

                    // Update the Switchblade server
                    jQuery.ajax({
                        url: shortcutsHubData.ajax_url,
                        method: 'POST',
                        data: {
                            action: 'toggle_delete',
                            security: shortcutsHubData.security,
                            id: id,
                            deleted: deletedStatus // Send the deleted status
                        },
                        success: function(sbResponse) {
                            if (sbResponse.success) {
                                // Update the button class and text based on the new state
                                const newButtonText = deletedStatus ? 'Restore' : 'Delete';
                                jQuery(buttonElement).text(newButtonText).toggleClass('restore-button delete-button');
                                fetchShortcuts(); // Refresh the shortcuts list
                            } else {
                                alert('Error updating Switchblade status: ' + sbResponse.data.message);
                            }
                        },
                        error: function(xhr, status, error) {
                            console.error('Error updating Switchblade status:', xhr.responseText);
                            alert('Error updating Switchblade status. Please try again later.');
                        }
                    });
                } else {
                    alert('Error: Switchblade ID not found.');
                }
            } else {
                alert('Error: ' + response.data.message);
            }
        },
        error: function(xhr, status, error) {
            console.error('AJAX error toggling shortcut:', xhr.responseText);
            alert('Error toggling shortcut. Please try again later.');
        },
        complete: function() {
            jQuery(buttonElement).prop('disabled', false);
        }
    });
}