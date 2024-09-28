jQuery(document).ready(function($) {
// Function to fetch versions using AJAX
function fetchVersions(shortcutId, filters) {
    console.log("Fetching versions for shortcut ID: " + shortcutId);
    $.ajax({
        url: shortcutsHubData.ajax_url,
        method: 'POST',
        data: {
            action: 'fetch_versions',
            security: shortcutsHubData.security,
            shortcut_id: shortcutId,
            search_term: filters.searchTerm || '',
            status: filters.status || '',
            deleted: filters.deleted || '',
            required_update: filters.requiredUpdate || ''
        },
        success: function(response) {
            if (response.success) {
                displayVersions(response.data.versions);
            } else {
                console.error('Error fetching versions:', response.data);
            }
        },
        error: function(jqXHR, textStatus, errorThrown) {
            console.error('AJAX error fetching versions:', textStatus, errorThrown);
        }
    });
}

// Function to display versions
function displayVersions(versions) {
    const versionsContainer = $('#versions-container');
    versionsContainer.empty();

    if (versions.length > 0) {
        versions.forEach(function(version) {
            const versionElement = `
                <div class="version-item">
                    <h3>Version: ${version.version}</h3>
                    <p>Notes: ${version.notes || 'No notes available'}</p>
                    <button class="edit-version-button" data-version-id="${version.id}">Edit Version</button>
                </div>`;
            versionsContainer.append(versionElement);
        });
    } else {
        versionsContainer.append('<p>No versions found.</p>');
    }

    // Attach event handlers to newly created edit buttons
    attachVersionEditButtonHandlers();
}

// Function to attach event handlers to the version edit buttons
function attachVersionEditButtonHandlers() {
    $('.edit-version-button').on('click', function() {
        const versionId = $(this).data('version-id');
        openVersionEditModal(versionId);
    });
}
});