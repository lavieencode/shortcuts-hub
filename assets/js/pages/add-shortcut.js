jQuery(document).ready(function($) {
    // Initialize color picker
    $('#color-picker-container').wpColorPicker({
        change: function(event, ui) {
            var color = ui.color.toString();
            $('#color').val(color);
        }
    });

    // Initialize icon selector
    if (typeof IconSelector !== 'undefined' && !window.iconSelector) {
        window.iconSelector = new IconSelector({
            container: document.getElementById('icon-selector-content'),
            inputField: document.getElementById('shortcut-icon'),
            previewContainer: document.querySelector('.icon-preview'),
            onChange: function(value) {
                console.log('Icon changed:', value);
            }
        });
    } else if (!IconSelector) {
        console.error('IconSelector not loaded');
    }

    // Handle form submission
    $('#add-shortcut-form').on('submit', function(event) {
        event.preventDefault();
        submitAddShortcutForm('publish');
    });

    // Handle draft saving
    $('#save-draft').on('click', function(event) {
        event.preventDefault();
        submitAddShortcutForm('draft');
    });

    function submitAddShortcutForm(status) {
        const shortcutData = {
            name: $('#name').val(),
            description: $('#description').val(),
            headline: $('#headline').val(),
            input: $('#input').val(),
            result: $('#result').val(),
            color: $('#color').val(),
            icon: $('#shortcut-icon').val(),
            actions: $('#actions').val(),
            sb_id: $('#sb_id').val(),
            post_id: $('#post_id').val(),
            state: status,
            website: shortcutsHubData.site_url + '/wp-admin/admin.php?page=edit-shortcut&id=' + $('#post_id').val()
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
                    const websiteUrl = shortcutsHubData.site_url + '/wp-admin/admin.php?page=edit-shortcut&id=' + response.data.post_id;
                    shortcutData.website = websiteUrl;
                    $('#website').val(websiteUrl);

                    $.ajax({
                        url: shortcutsHubData.ajax_url,
                        method: 'POST',
                        data: {
                            action: 'update_shortcut_website',
                            security: shortcutsHubData.security,
                            post_id: response.data.post_id,
                            website: websiteUrl
                        }
                    });

                    $('#message')
                        .removeClass('error-message')
                        .addClass('success-message')
                        .text('Shortcut created successfully: ' + response.data.message)
                        .show();
                    
                    setTimeout(function() {
                        window.location.href = websiteUrl;
                    }, 2000);
                } else {
                    $('#message')
                        .removeClass('success-message')
                        .addClass('error-message')
                        .text('Error creating shortcut: ' + (response.data.message || 'Unknown error occurred.'))
                        .show();
                }
            },
            error: function(xhr, status, error) {
                $('#message')
                    .removeClass('success-message')
                    .addClass('error-message')
                    .text('AJAX error creating shortcut: ' + xhr.responseText)
                    .show();
            }
        });
    }
});
