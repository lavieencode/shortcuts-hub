jQuery(document).ready(function(jQuery) {
    const urlParams = new URLSearchParams(window.location.search);
    const shortcutId = urlParams.get('id');
    if (shortcutId) {
        loadShortcutFields(shortcutId);
    }

    jQuery('#shortcut-color').on('input', function() {
        var color = jQuery(this).val();
        console.log('Color changed to:', color);
        jQuery(this).css('background-color', color);
    });

    // Manually trigger the input event when setting the value programmatically
    jQuery('#shortcut-color').val('#ff5733').trigger('input');

    jQuery('#shortcut-color').on('click', function() {
        jQuery('#color-picker-container').wpColorPicker('open');
    });

    jQuery('#color-picker-container').wpColorPicker({
        change: function(event, ui) {
            var color = ui.color.toString();
            jQuery('#shortcut-color').val(color);
        }
    });

    jQuery('#shortcut-icon').on('click', function(e) {
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
        // Add delete functionality here
    });

    jQuery('input[type="color"]').on('click', function() {
        this.select();
    });
});

function loadShortcutFields(shortcutId) {
    jQuery.ajax({
        url: shortcutsHubData.ajax_url,
        method: 'POST',
        data: {
            action: 'fetch_shortcut',
            shortcut_id: shortcutId,
            security: shortcutsHubData.security
        },
        success: function(response) {
            if (response.success) {
                const shortcutData = response.data;
                console.log('Shortcut Data:', shortcutData);
                jQuery('#shortcut-name').val(shortcutData.name);
                jQuery('#shortcut-headline').val(shortcutData.headline);
                jQuery('#shortcut-description').val(shortcutData.description);
                jQuery('#shortcut-color').val(shortcutData.color).css('background-color', shortcutData.color);
                jQuery('#shortcut-icon').val(shortcutData.icon);
                jQuery('#sb-id').val(shortcutData.sb_id);
                jQuery('#edit-shortcut-title').text(shortcutData.name);
                jQuery('#shortcut-status').val(shortcutData.state === 'draft' ? 1 : 0);
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
        id: jQuery('#shortcut-id').val(),
        name: jQuery('#shortcut-name').val(),
        headline: jQuery('#shortcut-headline').val(),
        description: jQuery('#shortcut-description').val(),
        color: jQuery('#shortcut-color').val(),
        icon: jQuery('#shortcut-icon').val(),
        sb_id: jQuery('#sb-id').val(),
        state: jQuery('#shortcut-status').val()
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

function getContrastYIQ(hexcolor){
    hexcolor = hexcolor.replace("#", "");
    var r = parseInt(hexcolor.substr(0,2),16);
    var g = parseInt(hexcolor.substr(2,2),16);
    var b = parseInt(hexcolor.substr(4,2),16);
    var yiq = ((r*299)+(g*587)+(b*114))/1000;
    return (yiq >= 128) ? 'black' : 'white';
}
