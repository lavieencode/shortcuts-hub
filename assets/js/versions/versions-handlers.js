jQuery(document).ready(function() {
    checkUrlParameters();
    attachVersionHandlers();
});

function checkUrlParameters() {
    const urlParams = new URLSearchParams(window.location.search);
    const view = urlParams.get('view');
    const shortcutId = urlParams.get('id');

    if (view === 'versions' && shortcutId) {
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

    jQuery(document).on('click', '.edit-version', function() {
        const shortcutId = jQuery(this).data('id');
        const versionId = jQuery(this).data('version-id');
        openVersionEditModal(shortcutId, versionId);
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
        toggleVersionDeletion(jQuery('#edit-version-form #shortcut-id').val(), jQuery('#edit-version-form #version-id').val(), false);
    });

    jQuery('#edit-version-modal .cancel-button').on('click', function() {
        jQuery('#edit-version-modal').removeClass('active').hide();
        jQuery('body').removeClass('modal-open');
    });

    jQuery(document).on('click', '.delete-version, .restore-version', function() {
        const shortcutId = jQuery(this).data('shortcut-id');
        const versionId = jQuery(this).data('version-id');
        const isRestore = jQuery(this).hasClass('restore-version');
        toggleVersionDeletion(shortcutId, versionId, isRestore);
    });

    jQuery('#back-to-shortcuts').on('click', function() {
        const urlParams = new URLSearchParams(window.location.search);
        urlParams.delete('view');
        urlParams.delete('id');
        window.history.pushState({}, '', `${window.location.pathname}?${urlParams}`);
        toggleVersionsView(false);
    });
}