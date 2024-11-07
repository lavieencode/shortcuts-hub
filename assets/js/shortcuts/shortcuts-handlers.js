jQuery(document).ready(function() {
    initializeShortcutButtons();
    fetchShortcuts();
    checkUrlParameters();
});

function initializeShortcutButtons() {
    jQuery('.version-button').on('click', function() {
        const sbId = jQuery(this).data('sb-id');
        toggleVersionsView(true);
        fetchVersions(sbId);
    });

    jQuery('.edit-button').on('click', function() {
        const shortcutId = jQuery(this).data('id');
        const editUrl = `admin.php?page=edit-shortcut&id=${shortcutId}`;
        window.location.href = editUrl;
    });

    jQuery(document).on('click', '.delete-button', function() {
        const shortcutId = jQuery(this).data('id');
        const isRestore = false;
    
        toggleDelete(shortcutId, isRestore, this);
    });

    jQuery('.synced-text').on('click', function() {
        const sbId = jQuery(this).data('sb-id');
        openEditModal(sbId);
    });
}

function checkUrlParameters() {
    const urlParams = new URLSearchParams(window.location.search);
    const showVersions = urlParams.get('showVersions');

    if (showVersions) {
        toggleVersionsView(true);
    } else {
        toggleVersionsView(false);
    }
}