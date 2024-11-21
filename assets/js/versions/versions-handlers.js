jQuery(document).ready(function() {
    checkUrlParameters();
    attachVersionHandlers();
});

function checkUrlParameters() {
    const urlParams = new URLSearchParams(window.location.search);
    const view = urlParams.get('view');
    const shortcutId = urlParams.get('id');

    console.log('Checking URL parameters in versions-handlers.js with view:', view, 'and shortcutId:', shortcutId);

    if (view === 'versions' && shortcutId) {
        console.log('Calling fetchVersions from checkUrlParameters in versions-handlers.js with shortcutId:', shortcutId);
        toggleVersionsView(true);
        fetchVersions(shortcutId);
    } else {
        toggleVersionsView(false);
    }
}

function attachVersionHandlers() {
    jQuery(document).on('click', '.version-header', function() {
        const versionBody = jQuery(this).next('.version-body');
        versionBody.toggle();

        const caret = jQuery(this).find('.caret');
        caret.html(versionBody.is(':visible') ? '&#9660;' : '&#9654;');
    });

    jQuery(document).on('click', '.edit-version', function(event) {
        const button = jQuery(event.target);
        const versionData = button.data('version');

        if (versionData) {
            populateVersionEditModal({ version: versionData });
            jQuery('body').addClass('modal-open');
            jQuery('#edit-version-modal').addClass('active').show();
        } else {
            console.error('Version data not found on button');
        }
    });

    jQuery('#edit-version-modal .save-button').on('click', function(event) {
        event.preventDefault();
        updateVersion('save');
    });

    jQuery('#edit-version-modal .publish-button').on('click', function(event) {
        event.preventDefault();
        updateVersion('publish');
    });

    jQuery('#edit-version-modal .delete-button').on('click', function(event) {
        event.preventDefault();
        toggleVersionDelete(jQuery('#edit-version-form #shortcut-id').val(), jQuery('#edit-version-form #version-id').val(), false);
    });

    jQuery('#edit-version-modal .cancel-button').on('click', function() {
        jQuery('#edit-version-modal').removeClass('active').hide();
        jQuery('body').removeClass('modal-open');
    });

    jQuery(document).on('click', '.delete-version, .restore-version', function() {
        const shortcutId = jQuery(this).closest('.version-item').data('shortcut-id');
        const versionId = jQuery(this).closest('.version-item').data('version-id');
        const isRestore = jQuery(this).hasClass('restore-version');
        toggleVersionDelete(shortcutId, versionId, isRestore);
    });

    jQuery('#back-to-shortcuts').on('click', function() {
        const urlParams = new URLSearchParams(window.location.search);
        urlParams.delete('view');
        urlParams.delete('id');
        window.history.pushState({}, '', `${window.location.pathname}?${urlParams}`);
        toggleVersionsView(false);
    });

    jQuery('#edit-version-modal .save-as-draft-button').on('click', function(event) {
        event.preventDefault();
        updateVersion('save');
    });

    function handleVersionEditModal(event) {
        const button = jQuery(event.currentTarget);
        const versionData = button.data('version');

        if (versionData) {
            populateVersionEditModal({ version: versionData });
            jQuery('#edit-version-modal').addClass('active').show();
        } else {
            console.error('Version data not found on button');
        }
    }
}