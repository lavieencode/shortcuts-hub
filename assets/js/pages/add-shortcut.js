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

    // Prevent default form submission
    $('#add-shortcut-form').on('submit', function(event) {
        event.preventDefault();
    });

    // Function to gather form data
    function getShortcutData() {
        // Prepare icon data as a proper object
        const iconData = {
            type: $('#icon-type-selector').val() || 'fontawesome',
            name: $('#shortcut-icon').val(),
            url: null // Add URL handling if needed
        };

        return {
            name: $('#name').val(),
            description: $('#description').val(),
            headline: $('#headline').val(),
            input: $('#input').val(),
            result: $('#result').val(),
            color: $('#color').val(),
            icon: JSON.stringify(iconData),
            actions: $('#actions').val(),
            sb_id: $('#sb_id').val(),
            post_id: $('#post_id').val()
        };
    }

    // Function to create shortcut
    function createShortcut(shortcutData, state) {
        const formData = {
            action: 'create_shortcut',
            security: shortcutsHubData.security.create_shortcut,
            shortcut_data: {
                name: shortcutData.name,
                headline: shortcutData.headline,
                description: shortcutData.description,
                state: state
            },
            wp_data: {
                input: shortcutData.input,
                result: shortcutData.result,
                color: shortcutData.color,
                icon: shortcutData.icon
            }
        };

        return $.ajax({
            url: shortcutsHubData.ajax_url,
            type: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    window.location.href = response.data.redirect_url;
                } else {
                    $('#feedback-message')
                        .removeClass('success')
                        .addClass('error')
                        .text(response.data.message)
                        .show();
                }
            },
            error: function(xhr, status, error) {
                $('#feedback-message')
                    .removeClass('success')
                    .addClass('error')
                    .text('Error creating shortcut. Please try again.')
                    .show();
            }
        });
    }

    // Handle publish button
    $('#add-shortcut').on('click', function(event) {
        event.preventDefault();
        const $button = $(this);
        $button.prop('disabled', true).text('Publishing...');
        createShortcut(getShortcutData(), 'publish')
            .always(function() {
                $button.prop('disabled', false).text('Add Shortcut');
            });
    });

    // Handle draft saving
    $('#save-draft').on('click', function(event) {
        event.preventDefault();
        const $button = $(this);
        $button.prop('disabled', true).text('Saving...');
        createShortcut(getShortcutData(), 'draft')
            .always(function() {
                $button.prop('disabled', false).text('Save Draft');
            });
    });
});
