jQuery(document).ready(function($) {
    const urlParams = new URLSearchParams(window.location.search);
    const shortcutId = urlParams.get('id') || $('#shortcut-id').val();
    loadShortcutFields(shortcutId);

    $('#shortcut-color').on('click', function() {
        $('#color-picker-container').wpColorPicker('open');
    });

    $('#color-picker-container').wpColorPicker({
        change: function(event, ui) {
            var color = ui.color.toString();
            $('#shortcut-color').val(color).css('background-color', color);
        }
    });

    $('#shortcut-icon').on('click', function(e) {
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
            $('#shortcut-icon').val(attachment.filename);
        });

        mediaUploader.open();
    });

    $('#save-shortcut').on('click', function(event) {
        event.preventDefault();
        saveShortcutFormData();
    });
});

function loadShortcutFields(shortcutId) {
    $.ajax({
        url: shortcutsHubData.ajax_url,
        method: 'POST',
        data: {
            action: 'fetch_shortcut',
            security: shortcutsHubData.security,
            shortcut_id: shortcutId
        },
        success: function(response) {
            if (response.success) {
                const shortcutData = response.data;
                $('#shortcut-name').val(shortcutData.name);
                $('#shortcut-headline').val(shortcutData.headline);
                $('#shortcut-description').val(shortcutData.description);
                $('#shortcut-color').val(shortcutData.color).css('background-color', shortcutData.color);
                $('#shortcut-icon').val(shortcutData.icon);
                $('#sb-id').val(shortcutData.sb_id);
                $('#edit-shortcut-title').text(shortcutData.name);
                $('#shortcut-status').val(shortcutData.state === 'draft' ? 1 : 0);
            } else {
                console.error('Error loading shortcut data:', response.data.message);
            }
        },
        error: function(xhr, status, error) {
            console.error('AJAX error loading shortcut data:', xhr.responseText);
        }
    });
}

function saveShortcutFormData() {
    const shortcutData = {
        id: $('#shortcut-id').val(),
        name: $('#shortcut-name').val(),
        headline: $('#shortcut-headline').val(),
        description: $('#shortcut-description').val(),
        color: $('#shortcut-color').val(),
        icon: $('#shortcut-icon').val(),
        sb_id: $('#sb-id').val(),
        state: $('#shortcut-status').val()
    };

    $.ajax({
        url: shortcutsHubData.ajax_url,
        method: 'POST',
        data: {
            action: 'update_shortcut',
            security: shortcutsHubData.security,
            shortcut_data: shortcutData
        },
        success: function(response) {
            if (response.success) {
                alert('Shortcut saved successfully.');
            } else {
                alert('Error saving shortcut: ' + response.data.message);
            }
        }
    });
}