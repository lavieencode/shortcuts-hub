jQuery(document).ready(function() {
    const urlParams = new URLSearchParams(window.location.search);
    const id = urlParams.get('id');
    const versionId = urlParams.get('version_id');

    if (id && versionId) {
        fetchVersion(id, versionId);
    }
});

function populateEditVersionForm(data) {
    const versionData = data.version;

    jQuery('#version-notes').val(versionData.notes || '');
    jQuery('#version-url').val(versionData.url || '');
    jQuery('#version-ios').val(versionData.minimumiOS || '');
    jQuery('#version-mac').val(versionData.minimumMac || '');
    jQuery('#version-required').val(versionData.required ? 'true' : 'false');
}
