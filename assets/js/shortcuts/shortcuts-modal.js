function openEditModal(shortcutId) {
    jQuery.ajax({
        url: shortcutsHubData.ajax_url,
        method: 'POST',
        data: {
            action: 'fetch_shortcut',
            id: id,
            security: shortcutsHubData.security
        },
        success: function(response) {
            if (response && response.success) {
                const shortcutData = response.data;

                populateEditModal(shortcutData);

                jQuery('#edit-modal').addClass('active').css('transform', 'translateX(0)');
                jQuery('body').addClass('modal-open');
            } else {
                const errorMessage = response.data ? response.data.message : 'Unknown error fetching WordPress shortcut data.';
                console.error('Error fetching WordPress shortcut data:', errorMessage);
                alert(errorMessage);
            }
        },
        error: function(xhr, status, error) {
            console.error('AJAX error fetching shortcut data:', status, error);
            alert('Error fetching shortcut data. Please try again later.');
        }
    });
}

function populateEditModal(data) {
    jQuery('#edit-shortcut-form #shortcut-id').val(data.id);
    jQuery('#edit-shortcut-form #shortcut-name').val(data.name);
    jQuery('#edit-shortcut-form #shortcut-headline').val(data.headline);
    jQuery('#edit-shortcut-form #shortcut-description').val(data.description);
    jQuery('#edit-shortcut-form #shortcut-website').val(data.website);
    jQuery('#edit-modal').show();
}