jQuery(document).ready(function($) {
    function submitShortcutForm(state) {
        $('.error-message').remove();

        var isValid = true;
        var shortcutData = {
            name: $('#shortcut-name').val(),
            headline: $('#shortcut-headline').val(),
            description: $('#shortcut-description').val(),
            state: state === 'draft' ? 1 : 0,
            deleted: false
        };

        if (!shortcutData.name) {
            $('#shortcut-name').after('<span class="error-message">Name is required and must be unique.</span>');
            isValid = false;
        }

        if (!isValid) {
            return;
        }

        var apiUrl = shortcutsHubData.sb_url + '/shortcuts';
        var method = 'POST';

        console.log('Making API call to:', apiUrl);
        console.log('Request Method:', method);
        console.log('Request Headers:', {
            'Authorization': 'Bearer ' + shortcutsHubData.token,
            'Content-Type': 'application/json'
        });
        console.log('Request Body:', shortcutData);

        $.ajax({
            url: apiUrl,
            method: method,
            headers: {
                'Authorization': 'Bearer ' + shortcutsHubData.token,
                'Content-Type': 'application/json'
            },
            data: JSON.stringify(shortcutData),
            success: function(response) {
                console.log('API response:', response);
                if (response.success) {
                    alert('Shortcut synced with Switchblade successfully.');
                    shortcutData.sb_id = response.data.id;
                    saveShortcutInWordPress(shortcutData);
                } else {
                    alert('Error syncing with Switchblade: ' + response.message);
                }
            },
            error: function(xhr, status, error) {
                console.error('Error syncing with Switchblade:', status, error);
                console.error('Response Text:', xhr.responseText);
                alert('Error syncing with Switchblade: ' + xhr.responseText);
            }
        });
    }

    function saveShortcutInWordPress(shortcutData) {
        console.log('Saving shortcut in WordPress with data:', shortcutData);

        $.ajax({
            url: shortcutsHubData.ajax_url,
            method: 'POST',
            data: {
                action: 'create_shortcut_post',
                security: shortcutsHubData.security,
                shortcut_data: shortcutData
            },
            success: function(response) {
                console.log('WordPress response:', response);
                if (response.success) {
                    alert('Shortcut created successfully in WordPress.');
                    var editPageUrl = shortcutsHubData.site_url + '/wp-admin/post.php?post=' + response.data.post_id + '&action=edit';
                    window.location.href = editPageUrl;
                } else {
                    alert('Error creating shortcut in WordPress: ' + response.data.message);
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX error creating shortcut in WordPress:', status, error);
                console.error('Response Text:', xhr.responseText);
                alert('AJAX error creating shortcut in WordPress: ' + xhr.responseText);
            }
        });
    }

    $('#add-shortcut').on('click', function() {
        submitShortcutForm('published');
    });

    $('#save-draft').on('click', function() {
        submitShortcutForm('draft');
    });
});
