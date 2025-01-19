jQuery(document).ready(function($) {
    // View toggle functionality
    const viewToggle = $('#view-toggle');
    const shortcutsContainer = $('#shortcuts-container');
    
    // Add shortcut button click handler
    $('.add-shortcut-button').on('click', function(e) {
        e.preventDefault();
        // Reset form and show empty modal for new shortcut
        $('#add-shortcut-modal').addClass('active').css('transform', 'translateX(0)').show();
        $('#add-shortcut-form')[0].reset();
        $('body').addClass('modal-open');
    });
    
    // Add shortcut form submission handler
    $('#add-shortcut-form').on('submit', function(e) {
        e.preventDefault();
        
        // Get form values
        const shortcutData = {
            name: $('#add-shortcut-form #shortcut-name').val().trim(),
            headline: $('#add-shortcut-form #shortcut-headline').val().trim(),
            description: $('#add-shortcut-form #shortcut-description').val().trim(),
            input: $('#add-shortcut-form #shortcut-input').val().trim(),
            result: $('#add-shortcut-form #shortcut-result').val().trim(),
            color: $('#add-shortcut-form .color-picker').val().trim(),
            icon: $('#add-shortcut-form #shortcut-icon').val().trim()
        };

        // Validate required fields
        if (!shortcutData.name || !shortcutData.headline) {
            sh_debug_log('Form Validation Failed', {
                message: 'Required fields missing',
                source: {
                    file: 'shortcuts-list.js',
                    line: 'validateForm',
                    function: 'validateForm'
                },
                data: {
                    form_data: shortcutData,
                    missing_fields: {
                        name: !shortcutData.name,
                        headline: !shortcutData.headline
                    }
                },
                debug: true
            });

            $('#message')
                .removeClass('success-message')
                .addClass('error-message')
                .text('Please fill in all required fields')
                .show();
            return;
        }

        createShortcut(shortcutData, 'publish')
            .then(function(response) {
                if (response.success) {
                    // Close modal
                    $('#add-shortcut-modal').removeClass('active');
                    setTimeout(function() {
                        $('#add-shortcut-modal').hide();
                        $('body').removeClass('modal-open');
                    }, 300);

                    // Reset form
                    $('#add-shortcut-form')[0].reset();
                    
                    // Refresh shortcuts list
                    refreshShortcuts();
                }
            })
            .catch(function(error) {
                console.error('Error creating shortcut:', error);
                $('#message')
                    .removeClass('success-message')
                    .addClass('error-message')
                    .text('Error creating shortcut: ' + error)
                    .show();
            });
    });

    // Close modal button handler
    $('.cancel-button').on('click', function() {
        $('#add-shortcut-modal').removeClass('active');
        setTimeout(function() {
            $('#add-shortcut-modal').hide();
        }, 300);
        $('body').removeClass('modal-open');
    });

    // Add shortcut form handlers
    $('#add-shortcut-form .save-draft-button').on('click', function() {
        const formData = new FormData($('#add-shortcut-form')[0]);
        formData.append('action', 'add_shortcut');
        formData.append('security', shortcutsHubData.security.add_shortcut);
        formData.append('post_status', 'draft');
        
        $.ajax({
            url: shortcutsHubData.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    $('#add-shortcut-modal').removeClass('active').hide();
                    $('body').removeClass('modal-open');
                    refreshShortcuts();
                } else {
                    alert('Error adding shortcut: ' + response.data.message);
                }
            },
            error: function() {
                alert('Error adding shortcut. Please try again.');
            }
        });
    });

    function toggleView(view) {
        shortcutsContainer.removeClass('view-grid view-list').addClass('view-' + view);
    }
    
    function updateView(view) {
        shortcutsContainer.removeClass('view-grid view-list').addClass('view-' + view);
        viewToggle.attr('data-view', view === 'grid' ? 'list' : 'grid');
        
        // Save preference
        localStorage.setItem('shortcuts_view_preference', view);
    }
    
    // Initialize view from saved preference
    const savedView = localStorage.getItem('shortcuts_view_preference') || 'grid';
    toggleView(savedView);
    
    // If we're not in the saved view, switch to it
    if (!shortcutsContainer.hasClass('view-' + savedView)) {
        viewToggle.trigger('click');
    }
    
    viewToggle.on('click', function() {
        const currentView = $(this).attr('data-view');
        const newView = currentView === 'grid' ? 'list' : 'grid';
        
        // Show loading state
        shortcutsContainer.addClass('loading');
        
        // Toggle the view
        toggleView(newView);
        
        // Update toggle button state
        $(this).attr('data-view', newView === 'grid' ? 'list' : 'grid');
        
        // Make AJAX call to get new view
        $.ajax({
            url: shortcutsHubData.ajax_url,
            type: 'POST',
            data: {
                action: 'toggle_' + newView + '_view',
                security: shortcutsHubData.security.toggle_view,
                status: $('#filter-status').val(),
                deleted: $('#filter-deleted').val(),
                search: $('#search-input').val()
            },
            success: function(response) {
                if (response.success) {
                    shortcutsContainer.html(response.data.html);
                    renderShortcuts(window.currentShortcuts);
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', error);
                shortcutsContainer.removeClass('loading');
            },
            complete: function() {
                shortcutsContainer.removeClass('loading');
            }
        });
    });

    // Update filter handling to respect current view
    function refreshShortcuts() {
        const currentView = shortcutsContainer.hasClass('view-grid') ? 'grid' : 'list';
        
        shortcutsContainer.addClass('loading');
        
        // Set refresh flag for render logging
        window.isRefreshing = true;
        
        $.ajax({
            url: shortcutsHubData.ajax_url,
            type: 'POST',
            data: {
                action: 'toggle_' + currentView + '_view',
                security: shortcutsHubData.security.toggle_view,
                status: $('#filter-status').val(),
                deleted: $('#filter-deleted').val(),
                search: $('#search-input').val()
            },
            success: function(response) {
                if (response.success) {
                    shortcutsContainer.html(response.data.html);
                    if (window.currentShortcuts) {
                        renderShortcuts(window.currentShortcuts);
                    }
                }
            },
            complete: function() {
                shortcutsContainer.removeClass('loading');
                // Reset refresh flag
                window.isRefreshing = false;
            }
        });
    }

    // Attach to existing filter events
    $('#search-input').on('input', debounce(refreshShortcuts, 300));
    $('#filter-status, #filter-deleted').on('change', refreshShortcuts);
    $('#reset-filters').on('click', function() {
        $('#search-input').val('');
        $('#filter-status, #filter-deleted').val('');
        refreshShortcuts();
    });
});
