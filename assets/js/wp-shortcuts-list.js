jQuery(document).ready(function($) {
    fetchWPShortcuts();

    function fetchWPShortcuts() {
        $.ajax({
            url: shortcutsHubData.ajax_url,
            method: 'POST',
            data: {
                action: 'fetch_wp_shortcuts',
                security: shortcutsHubData.security
            },
            success: function(response) {
                if (response.success) {
                    renderWPShortcuts(response.data);
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX error:', status, error);
                console.error('Response Text:', xhr.responseText);
            }
        });
    }

    function renderWPShortcuts(shortcuts) {
        const container = $('#wp-shortcuts-container');
        container.empty();

        shortcuts.forEach(function(shortcut) {

            const name = shortcut.name || 'Unnamed WP Shortcut';
            const headline = shortcut.headline || 'No headline provided';

            const shortcutElement = $(`
                <div class="shortcut-item" data-id="${shortcut.id}">
                    <h3>${name}</h3>
                    <p>${headline}</p>
                    <div class="button-container">
                        <button class="edit-button" data-post-id="${shortcut.id}">Edit</button>
                        <button class="delete-button" data-id="${shortcut.id}">${shortcut.deleted ? 'Restore' : 'Delete'}</button>
                    </div>
                    ${shortcut.deleted ? '<span class="badge">Deleted</span>' : ''}
                </div>
            `);
            container.append(shortcutElement);
        });

        attachWPShortcutHandlers();
    }

    function attachWPShortcutHandlers() {
        $('.edit-button').on('click', function() {
            const postId = $(this).data('post-id');
            window.location.href = `/edit-shortcut?id=${postId}`;
        });

        $('.delete-button').on('click', function() {
            const shortcutId = $(this).data('id');
            const isRestore = $(this).text() === 'Restore';
            toggleWPShortcutDeletion(shortcutId, isRestore);
        });
    }
});
