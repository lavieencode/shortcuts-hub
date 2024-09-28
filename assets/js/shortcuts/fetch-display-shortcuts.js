jQuery(document).ready(function($) {
    // Trigger fetching of shortcuts when the document is ready
    fetchShortcuts();

// Function to fetch and display the shortcuts
function fetchShortcuts() {
    console.log('Fetching shortcuts...');

    var filterStatus = $('#filter-status').val();
    var filterDeleted = $('#filter-deleted').val();
    var searchTerm = $('#search-shortcuts-input').val();

    $.ajax({
        url: shortcutsHubData.ajax_url,
        method: 'POST',
        data: {
            action: 'fetch_shortcuts',
            security: shortcutsHubData.security,
            filter_status: filterStatus,
            filter_deleted: filterDeleted,
            search: searchTerm
        },
        success: function(response) {
            if (response.success) {
                console.log('Shortcuts fetched successfully:', response.data);
                displayShortcuts(response.data); // Call the display function
            } else {
                console.error('Error fetching shortcuts:', response.data.message);
                $('#shortcuts-container').html('<p>Error fetching shortcuts.</p>');
            }
        },
        error: function(xhr) {
            console.error('AJAX error fetching shortcuts.');
            $('#shortcuts-container').html('<p>Error fetching shortcuts.</p>');
        }
    });
}

// Function to display shortcuts dynamically
function displayShortcuts(shortcuts) {
    const container = $('#shortcuts-container');
    container.empty();

    if (shortcuts.length > 0) {
        shortcuts.forEach(function(shortcut) {
            const element = `
                <div class="shortcut-item">
                    <h3>${shortcut.name}</h3>
                    <p>${shortcut.headline || 'No headline'}</p>
                    <button class="edit-button shortcuts-button" data-id="${shortcut.id}">Edit</button>
                    <button class="versions-button shortcuts-button" data-shortcut-id="${shortcut.id}">Versions</button>
                </div>`;
            container.append(element);
        });
    } else {
        container.append('<p>No shortcuts found.</p>');
    }

    // Attach event handlers to newly created edit and version buttons
    attachEditButtonHandlers();
    attachShortcutVersionsButtonHandlers();
}
});