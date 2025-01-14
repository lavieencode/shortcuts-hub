jQuery(document).ready(function() {
    // Version and edit handlers
    jQuery(document).on('click', '.version-button', function() {
        const sb_id = jQuery(this).data('id');
        const urlParams = new URLSearchParams(window.location.search);
        urlParams.set('view', 'versions');
        urlParams.set('id', sb_id);
        window.history.pushState({}, '', `${window.location.pathname}?${urlParams}`);
        
        toggleVersionsView(true, sb_id);
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

    // Delete/Restore handlers for all states
    jQuery(document).on('click', '.delete-button', function(e) {
        e.preventDefault();
        const $button = jQuery(this);
        const post_id = $button.data('post_id');
        const sb_id = $button.data('sb_id');
        const $item = $button.closest('.shortcut-item');
        const isDraft = $item.find('.draft-badge').length > 0;
        
        handleShortcutStateChange(post_id, sb_id, this, true);
    });

    jQuery(document).on('click', '.restore-button', function(e) {
        e.preventDefault();
        const $button = jQuery(this);
        const postId = $button.data('post_id');
        const sbId = $button.data('sb_id');
        
        // Call deleteShortcut directly with restore=true
        deleteShortcut(postId, sbId, this, false, true);
    });

    jQuery(document).on('click', '.delete-permanently', function(e) {
        e.preventDefault();
        e.stopPropagation(); // Stop event from bubbling up
        const post_id = jQuery(this).data('post_id');
        const sb_id = jQuery(this).data('sb_id');
        
        if (confirm('Are you sure you want to PERMANENTLY delete this shortcut? This action cannot be undone.')) {
            deleteShortcut(post_id, sb_id, this, true, false);
        }
    });

    // Dropdown handlers
    jQuery(document).on('click', '.delete-dropdown-toggle', function(e) {
        e.preventDefault();
        e.stopPropagation();
        const dropdown = jQuery(this).siblings('.delete-dropdown-content');
        jQuery('.delete-dropdown-content').not(dropdown).removeClass('show');
        dropdown.toggleClass('show');
    });

    jQuery(document).on('click', function(e) {
        if (!jQuery(e.target).closest('.btn-group').length) {
            jQuery('.delete-dropdown-content').removeClass('show');
        }
    });

    jQuery(document).on('click', '.synced-text', function() {
        const id = jQuery(this).data('id');
        openEditModal(id);
    });

    checkUrlParameters();
});

function handleShortcutStateChange(post_id, sb_id, buttonElement, isDeleted) {
    deleteShortcut(post_id, sb_id, buttonElement, false, !isDeleted);
}

function checkUrlParameters() {
    const urlParams = new URLSearchParams(window.location.search);
    const view = urlParams.get('view');
    const shortcutId = urlParams.get('id');

    if (view === 'versions' && shortcutId) {
        toggleVersionsView(true, shortcutId);
    } else {
        toggleVersionsView(false);
    }
}