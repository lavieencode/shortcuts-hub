jQuery(document).ready(function() {

    initializeShortcutButtons();
    fetchShortcuts();
    checkUrlParameters();
});

function initializeShortcutButtons() {
    jQuery('.version-button').on('click', function() {
        const sb_id = jQuery(this).data('id');
        const urlParams = new URLSearchParams(window.location.search);
        urlParams.set('view', 'versions');
        urlParams.set('id', sb_id);
        window.history.pushState({}, '', `${window.location.pathname}?${urlParams}`);
        
        toggleVersionsView(true);
        fetchVersions(sb_id);
    });

    jQuery(document).on('click', '.edit-button', function() {
        const post_id = jQuery(this).data('post_id');
        if (post_id) {
            const editUrl = `admin.php?page=edit-shortcut&id=${post_id}`;
            window.location.href = editUrl;
        } else {
            console.error('Post ID is undefined');
        }
    });

    jQuery(document).on('click', '.delete-button', function() {
        const post_id = jQuery(this).data('post_id');
        const isRestore = false;
    
        toggleDelete(post_id, isRestore, this);
    });

    jQuery('.synced-text').on('click', function() {
        const id = jQuery(this).data('id');
        openEditModal(id);
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