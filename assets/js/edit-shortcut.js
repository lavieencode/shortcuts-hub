jQuery(document).ready(function($) {
    const urlParams = new URLSearchParams(window.location.search);
    const shortcutId = urlParams.get('id');

    if (shortcutId) {
        loadShortcutFields(shortcutId);
    }

    function loadShortcutFields(shortcutId) {
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
                    populateFields(response.data);
                } else {
                    console.error('Error loading shortcut data:', response.data.message);
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX error:', status, error);
                console.error('Response Text:', xhr.responseText);
            }
        });
    }

    function populateFields(data) {
        $('#shortcut-name').val(data.name || '');
        $('#shortcut-headline').val(data.headline || '');
        $('#shortcut-description').val(data.description || '');
        $('#shortcut-website').val(data.website || '');
        $('#shortcut-color').val(data.color || '');
        $('#shortcut-icon').val(data.icon || '');
        $('#shortcut-input').val(data.input || '');
        $('#shortcut-result').val(data.result || '');
        $('#sb-id').val(data.sb_id || '');
    }

    $('#edit-shortcut-form').on('submit', function(e) {
        e.preventDefault();
        var formData = $(this).serialize();

        $.ajax({
            url: shortcutsHubData.ajax_url,
            method: 'POST',
            data: {
                action: 'save_shortcut_data',
                security: shortcutsHubData.security,
                form_data: formData
            },
            success: function(response) {
                if (response.success) {
                    $('#feedback-message').text('Shortcut saved successfully.').css('color', 'green');
                    location.reload(); // Refresh the page to show updated info
                } else {
                    $('#feedback-message').text('Error saving shortcut: ' + response.data.message).css('color', 'red');
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX error:', status, error);
                console.error('Response Text:', xhr.responseText);
                $('#feedback-message').text('Error saving shortcut.').css('color', 'red');
            }
        });
    });

    $('#delete-shortcut').on('click', function() {
        if (confirm('Are you sure you want to delete this shortcut?')) {
            var shortcutId = $('#shortcut-id').val();

            $.ajax({
                url: shortcutsHubData.ajax_url,
                method: 'POST',
                data: {
                    action: 'delete_shortcut',
                    security: shortcutsHubData.security,
                    shortcut_id: shortcutId
                },
                success: function(response) {
                    if (response.success) {
                        $('#feedback-message').text('Shortcut deleted successfully.').css('color', 'green');
                        location.reload(); // Refresh the page after deletion
                    } else {
                        $('#feedback-message').text('Error deleting shortcut: ' + response.data.message).css('color', 'red');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX error:', status, error);
                    console.error('Response Text:', xhr.responseText);
                    $('#feedback-message').text('Error deleting shortcut.').css('color', 'red');
                }
            });
        }
    });
});
