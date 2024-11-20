jQuery(document).ready(function($) {
    const postId = shortcutsHubData.post_id;
    const id = shortcutsHubData.id;

    if (id) {
        fetchVersion(id, true);
    }

    $('.download-button').on('click', function(e) {
        var redirectUrl = $(this).attr('href');
        if (redirectUrl.includes('/shortcuts-gallery/login/')) {
            e.preventDefault();
            window.location.href = redirectUrl;
        } else {
            $.post(shortcutsHubData.ajax_url, {
                action: 'log_download',
                security: shortcutsHubData.security,
                post_id: postId
            }, function(response) {
                if (response.success) {
                    console.log('Download logged successfully.');
                } else {
                    console.error('Error logging download:', response.data.message);
                }
            });
        }
    });
});

function fetchVersion(id, latest = false) {
    const data = {
        action: 'fetch_version',
        security: shortcutsHubData.security,
        id: id,
        latest: latest
    };

    jQuery.ajax({
        url: shortcutsHubData.ajax_url,
        method: 'POST',
        data: data,
        success: function(response) {
            if (response.success && response.data) {
                console.log('Latest version fetched:', response.data);
                error_log('Latest version fetched: ' + JSON.stringify(response.data));
            } else {
                console.error('Error fetching version:', response.data ? response.data.message : 'Unknown error');
            }
        },
        error: function(xhr, status, error) {
            console.error('AJAX error fetching version:', status, error);
            console.error('Response Text:', xhr.responseText);
        }
    });
}