jQuery(document).ready(function() {
    const urlParams = new URLSearchParams(window.location.search);
    const id = urlParams.get('id');
    if (id) {
        fetchShortcut(id);
    }

    jQuery('.add-version-button').on('click', function() {
        const shortcutName = jQuery('#shortcut-name-display').text();
        jQuery('#add-version-modal #shortcut-name-display').text(shortcutName);
        jQuery('#add-version-modal').addClass('active').show();
        jQuery('body').addClass('modal-open');
    });

    jQuery('#add-version-modal .cancel-button').on('click', function() {
        jQuery('#add-version-modal').removeClass('active').hide();
        jQuery('body').removeClass('modal-open');
    });

    jQuery('#add-version-modal .save-draft-button').on('click', function(event) {
        event.preventDefault();
        createVersion('save_draft');
    });

    jQuery('#add-version-modal .publish-button').on('click', function(event) {
        event.preventDefault();
        createVersion('publish');
    });
});

function fetchShortcut(id) {
    jQuery.ajax({
        url: shortcutsHubData.ajax_url,
        method: 'POST',
        data: {
            action: 'fetch_shortcut',
            security: shortcutsHubData.security,
            id: id
        },
        success: function(response) {
            if (response.success) {
                const shortcutData = response.data;
                jQuery('#shortcut-name-display').text(shortcutData.name);
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