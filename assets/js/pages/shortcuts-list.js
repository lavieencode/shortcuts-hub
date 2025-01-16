jQuery(document).ready(function($) {
    // View toggle functionality
    const viewToggle = $('#view-toggle');
    const shortcutsContainer = $('#shortcuts-container');
    
    // Add shortcut button click handler
    $('.add-shortcut-button').on('click', function(e) {
        e.preventDefault();
        window.location.href = $(this).attr('href');
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
                    // Update the container content
                    shortcutsContainer.html(response.data.html);
                    updateView(newView);
                } else {
                    console.error('Failed to toggle view');
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
                }
            },
            complete: function() {
                shortcutsContainer.removeClass('loading');
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
