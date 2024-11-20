jQuery(document).ready(function(jQuery) {
    const urlParams = new URLSearchParams(window.location.search);
    const id = urlParams.get('id');
    if (id) {
        loadShortcutFields(id);
    }

    jQuery('#color').on('input', function() {
        var color = jQuery(this).val();
        console.log('Color changed to:', color);
        jQuery(this).css('background-color', color);
    });

    jQuery('#color').val('#ff5733').trigger('input');

    jQuery('#color').on('click', function() {
        jQuery('#color-picker-container').wpColorPicker('open');
    });

    jQuery('#color-picker-container').wpColorPicker({
        change: function(event, ui) {
            var color = ui.color.toString();
            jQuery('#shortcut-color').val(color);
        }
    });

    jQuery('#icon').on('click', function(e) {
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
            jQuery('#shortcut-icon').val(attachment.filename);
        });

        mediaUploader.open();
    });

    jQuery('#publish-shortcut').on('click', function(event) {
        event.preventDefault();
        saveShortcutFormData();
    });

    jQuery('#delete-shortcut').on('click', function(event) {
        event.preventDefault();
    });

    jQuery('input[type="color"]').on('click', function() {
        this.select();
    });
});

function loadShortcutFields(id) {
    jQuery.ajax({
        url: shortcutsHubData.ajax_url,
        method: 'POST',
        data: {
            action: 'fetch_shortcut',
            id: id,
            security: shortcutsHubData.security
        },
        success: function(response) {
            if (response.success) {
                const shortcutData = response.data;
                jQuery('#name').val(shortcutData.name);
                jQuery('#headline').val(shortcutData.headline);
                jQuery('#description').val(shortcutData.description);
                jQuery('#color').val(shortcutData.color).css('background-color', shortcutData.color);
                jQuery('#icon').val(shortcutData.icon);
                jQuery('#id').val(shortcutData.id);
                jQuery('#state').val(shortcutData.state === 'draft' ? 1 : 0);
                jQuery('#edit-shortcut-title').text(shortcutData.name);

            } else {
                console.error('Error fetching shortcut:', response.data.message);
            }
        },
        error: function(xhr, status, error) {
            console.error('AJAX error:', status, error);
            console.error('Response Text:', xhr.responseText);
        }
    });
}

function saveShortcutFormData() {
    const shortcutData = {
        post_id: jQuery('#post_id').val(),
        sb_id: jQuery('#sb_id').val(),
        name: jQuery('#name').val(),
        headline: jQuery('#headline').val(),
        description: jQuery('#description').val(),
        color: jQuery('#color').val(),
        icon: jQuery('#icon').val(),
        state: jQuery('#state').val()
    };

    jQuery.ajax({
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
