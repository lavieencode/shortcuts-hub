jQuery(document).ready(function($) {
// Function to open the version edit modal
function openVersionEditModal(versionId) {
    console.log("Opening version edit modal for Version ID: " + versionId);
    $.ajax({
        url: shortcutsHubData.ajax_url,
        method: 'POST',
        data: {
            action: 'fetch_single_version',
            security: shortcutsHubData.security,
            version_id: versionId
        },
        success: function(response) {
            if (response.success) {
                populateVersionModal(response.data.version);
                $('#edit-version-modal').fadeIn();
            } else {
                console.error('Error fetching version details:', response.data);
            }
        },
        error: function(jqXHR, textStatus, errorThrown) {
            console.error('AJAX error fetching version details:', textStatus, errorThrown);
        }
    });
}

// Function to populate the version edit modal with fetched data
function populateVersionModal(versionData) {
    if (!versionData) {
        console.error('Version data is missing.');
        return;
    }

    console.log('Populating version modal with the following data:', versionData);

    // Set hidden version ID
    $('#version-id').val(versionData.id);

    // Populate other fields
    $('#version-name').val(versionData.version || '');
    $('#version-notes').val(versionData.notes || '');
    $('#version-url').val(versionData.url || '');

    // Populate status (0 is Published, 1 is Draft)
    $('#version-status').val(versionData.state.value);

    // Populate minimum iOS and macOS versions
    $('#version-ios').val(versionData.minimumiOS || '');
    $('#version-mac').val(versionData.minimumMac || '');

    // Populate required update field
    $('#version-required').val(versionData.required ? 'true' : 'false');
}

// Function to handle the submit event for editing a version
$('#edit-version-form').on('submit', function(e) {
    e.preventDefault();

    const versionId = $('#version-id').val();
    const versionName = $('#version-name').val();
    const versionNotes = $('#version-notes').val();
    const versionURL = $('#version-url').val();
    const versionStatus = $('#version-status').val();
    const versioniOS = $('#version-ios').val();
    const versionMac = $('#version-mac').val();
    const versionRequired = $('#version-required').val();

    $.ajax({
        url: shortcutsHubData.ajax_url,
        method: 'POST',
        data: {
            action: 'edit_version',
            security: shortcutsHubData.security,
            version_id: versionId,
            version_name: versionName,
            notes: versionNotes,
            url: versionURL,
            status: versionStatus,
            minimum_ios: versioniOS,
            minimum_mac: versionMac,
            required_update: versionRequired
        },
        success: function(response) {
            if (response.success) {
                alert('Version updated successfully!');
                $('#edit-version-modal').fadeOut();
                fetchVersions($('#shortcut-id').val(), {});  // Re-fetch versions after update
            } else {
                console.error('Error updating version:', response.data);
                alert('Error updating version. Please try again.');
            }
        },
        error: function(jqXHR, textStatus, errorThrown) {
            console.error('AJAX error updating version:', textStatus, errorThrown);
        }
    });
});

// Function to close the version modal
$('.close-button').on('click', function() {
    $('#edit-version-modal').fadeOut();
});
});