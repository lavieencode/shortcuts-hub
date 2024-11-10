jQuery(document).ready(function($) {
    $('#download-button').on('click', function(e) {
        var redirectUrl = $(this).attr('href');
        if (redirectUrl.includes('/shortcuts-gallery/login/')) {
            e.preventDefault();
            window.location.href = redirectUrl;
        }
    });
});
