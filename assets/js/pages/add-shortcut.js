jQuery(document).ready(function($) {
    $('#add-shortcut-form').on('submit', function(event) {
        event.preventDefault();
        submitAddShortcutForm();
    });

    $('#color-picker-container').wpColorPicker({
        change: function(event, ui) {
            var color = ui.color.toString();
            $('#color').val(color).css('background-color', color);
        }
    });

    $('#color').on('click', function() {
        $('#color-picker-container').wpColorPicker('open');
    });

    $('#icon').on('click', function(e) {
        e.preventDefault();
        var mediaUploader;

        if (mediaUploader) {
            mediaUploader.open();
            return;
        }

        mediaUploader = wp.media({
            title: 'Select Icon',
            button: {
                text: 'Select Icon'
            },
            multiple: false
        });

        mediaUploader.on('select', function() {
            var attachment = mediaUploader.state().get('selection').first().toJSON();
            $('#icon').val(attachment.filename);
        });

        mediaUploader.open();
    });

    $('#save-draft').on('click', function(event) {
        event.preventDefault();
        submitAddShortcutForm();
    });

    function submitAddShortcutForm() {
        const shortcutData = {
            name: $('#name').val(),
            description: $('#description').val(),
            headline: $('#headline').val(),
            input: $('#input').val(),
            result: $('#result').val(),
            color: $('#color').val(),
            icon: $('#icon').val(),
            actions: $('#actions').val(),
            sb_id: $('#sb_id').val(),
            post_id: $('#post_id').val(),
            state: $('#state').val(),
            website: $('#website').val()
        };

        $.ajax({
            url: shortcutsHubData.ajax_url,
            method: 'POST',
            data: {
                action: 'create_shortcut',
                security: shortcutsHubData.security,
                shortcut_data: shortcutData
            },
            success: function(response) {
                if (response.success && response.data && response.data.post_id) {
                    $('#message').text('Shortcut created successfully: ' + response.data.message).show();
                    
                    setTimeout(function() {
                        window.location.href = shortcutsHubData.site_url + '/wp-admin/admin.php?page=edit-shortcut&id=' + response.data.post_id;
                    }, 2000);
                } else {
                    $('#message').text('Error creating shortcut: ' + (response.data.message || 'Unknown error occurred.')).show();
                }
            },
            error: function(xhr, status, error) {
                $('#message').text('AJAX error creating shortcut: ' + xhr.responseText).show();
            }
        });
    }
});
