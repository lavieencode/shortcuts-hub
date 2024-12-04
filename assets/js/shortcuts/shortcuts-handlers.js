jQuery(document).ready(function() {
    // Single event delegation for all shortcut buttons
    jQuery(document).on('click', '.version-button', function() {
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
        const sb_id = jQuery(this).data('sb_id');
        toggleTrash(post_id, sb_id, false, this);
    });

    // Handle dropdown toggle click
    jQuery(document).on('click', '.delete-dropdown-toggle', function(e) {
        e.preventDefault();
        e.stopPropagation();
        const dropdown = jQuery(this).siblings('.delete-dropdown-content');
        jQuery('.delete-dropdown-content').not(dropdown).removeClass('show');
        dropdown.toggleClass('show');
    });

    // Close dropdown when clicking outside
    jQuery(document).on('click', function(e) {
        if (!jQuery(e.target).closest('.btn-group').length) {
            jQuery('.delete-dropdown-content').removeClass('show');
        }
    });

    // Handle permanent delete click
    jQuery(document).on('click', '.delete-permanently', function(e) {
        e.preventDefault();
        const post_id = jQuery(this).data('post_id');
        const sb_id = jQuery(this).data('sb_id');
        deleteShortcut(post_id, sb_id, this);
    });

    jQuery(document).on('click', '.restore-button', function() {
        const post_id = jQuery(this).data('post_id');
        const sb_id = jQuery(this).data('sb_id');
        toggleTrash(post_id, sb_id, true, this);
    });

    jQuery(document).on('click', '.synced-text', function() {
        const id = jQuery(this).data('id');
        openEditModal(id);
    });

    checkUrlParameters();
});

function checkUrlParameters() {
    const urlParams = new URLSearchParams(window.location.search);
    const showVersions = urlParams.get('showVersions');

    if (showVersions) {
        toggleVersionsView(true);
    } else {
        toggleVersionsView(false);
    }
}

function fetchVersions(sb_id) {
    console.log('Fetching versions with params:', {
        sb_id
    });

    const data = new FormData();
    data.append('action', 'fetch_versions');
    data.append('security', shortcuts_hub.security);
    data.append('sb_id', sb_id);

    console.log('Sending AJAX request with FormData:', {
        action: 'fetch_versions',
        security: shortcuts_hub.security,
        sb_id
    });

    jQuery.ajax({
        url: shortcuts_hub.ajax_url,
        type: 'POST',
        data: data,
        processData: false,
        contentType: false,
        success: function(response) {
            console.log('Received response:', response);
            
            if (response.success) {
                console.log('Versions data:', response.data);
                console.log('Number of versions:', response.data.length);
                
                renderVersions(response.data);
            } else {
                console.error('Error in response:', response);
            }
        },
        error: function(xhr, status, error) {
            console.error('AJAX error:', {
                status,
                error,
                xhr
            });
        }
    });
}