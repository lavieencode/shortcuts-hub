jQuery(document).ready(function($) {
    // Listen for a custom event triggered by AJAX success
    $(document).on('elementorFormSubmissionSuccess', function(event) {
        const data = event.detail;
        if (data.download_url) {
            window.open(data.download_url, '_blank');
        }
        if (data.redirect_url) {
            window.location.href = data.redirect_url;
        } else if (data.post_url) {
            window.location.href = data.post_url;
        }
    });

    // Ensure that the form submission triggers the correct event
    $('form[name="Shortcuts Gallery Registration"]').on('submit', function(event) {
        event.preventDefault();
        const formData = $(this).serializeArray();
        const data = {};
        formData.forEach(field => {
            data[field.name.replace('form_fields[', '').replace(']', '')] = field.value;
        });
        data.action = 'elementor_pro_forms_ajax_handler';

        // Include post_id if available
        const postIdField = $(this).find('input[name="post_id"]');
        if (postIdField.length) {
            data.post_id = postIdField.val();
        }

        $.ajax({
            url: shortcutsHubData.ajax_url,
            method: 'POST',
            data: data,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    const data = response.data;
                    document.dispatchEvent(new CustomEvent('elementorFormSubmissionSuccess', { detail: data }));
                } else {
                    console.error('Error:', response.data.message);
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX error:', status, error);
            }
        });
    });
});