jQuery(document).ready(function() {
    jQuery('.add-version-button').on('click', function() {
        const sb_id = getShortcutIdFromUrl();
        const shortcutName = sessionStorage.getItem('shortcutName') || 'Unknown Shortcut';
        jQuery('#add-version-modal #shortcut-name-display').text(shortcutName);
        
        // Fetch the Switchblade shortcut data using sb_id
        jQuery.ajax({
            url: shortcutsHubData.ajax_url,
            method: 'POST',
            data: {
                action: 'fetch_shortcut',
                security: shortcutsHubData.security,
                id: sb_id,
                source: 'SB'
            },
            success: function(response) {
                if (response.success && response.data) {
                    console.log('Shortcut data:', response.data);
                } else {
                    console.error('Error fetching shortcut data:', response.data ? response.data.message : 'No data');
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX error:', status, error);
            }
        }).done(function() {
            jQuery('#add-version-modal').css('display', 'block').addClass('active').css('transform', 'translateX(0)');
            jQuery('body').addClass('modal-open');
        });
    });

    jQuery('#add-version-modal .cancel-button').on('click', function() {
        jQuery('#add-version-modal').removeClass('active').css('transform', 'translateX(100%)');
        jQuery('body').removeClass('modal-open');
    });

    jQuery('#add-version-modal .save-draft-button').on('click', function(event) {
        event.preventDefault();
        createVersion('create_version');
    });

    jQuery('#add-version-modal .publish-button').on('click', function(event) {
        event.preventDefault();
        createVersion('publish');
    });
});

function getShortcutIdFromUrl() {
    const urlParams = new URLSearchParams(window.location.search);
    return urlParams.get('id');
}

function createVersion(action) {
    const sb_id = getShortcutIdFromUrl();
    const versionName = jQuery('#add-version-form #version-name').val();
    const notes = jQuery('#add-version-form #version-notes').val();
    const url = jQuery('#add-version-form #version-url').val();
    const minimumiOS = jQuery('#add-version-form #minimum-ios').val();
    const minimumMac = jQuery('#add-version-form #minimum-mac').val();
    const required = jQuery('#add-version-form #required').is(':checked');

    const isDraft = action === 'create_version';

    jQuery.ajax({
        url: shortcutsHubData.ajax_url,
        method: 'POST',
        data: {
            action: 'create_version',
            security: shortcutsHubData.security,
            id: sb_id,
            version: versionName,
            notes: notes,
            url: url,
            minimum_ios: minimumiOS,
            minimum_mac: minimumMac,
            required: required,
            version_state: isDraft ? 'draft' : 'published'
        },
        success: function(response) {
            if (response.success) {
                jQuery('#version-feedback-message').text('Version created successfully.').show();
                setTimeout(function() {
                    jQuery('#add-version-modal').removeClass('active').css('transform', 'translateX(100%)');
                    jQuery('body').removeClass('modal-open');
                    fetchVersions(sb_id);
                }, 2000);
            } else {
                jQuery('#version-feedback-message').text('Error creating version: ' + response.data.message).show();
            }
        },
        error: function(xhr, status, error) {
            jQuery('#version-feedback-message').text('AJAX error creating version: ' + xhr.responseText).show();
        }
    });
}
