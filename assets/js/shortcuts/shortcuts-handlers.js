jQuery(document).ready(function() {
    // DEBUG: Set up mutation observer for all action menus
    const observer = new MutationObserver((mutations) => {
        mutations.forEach((mutation) => {
            if (mutation.type === 'attributes' && mutation.attributeName === 'class') {
                const element = mutation.target;
                window.sh_debug_log('Action Menu Class Changed', {
                    element: {
                        id: element.id,
                        oldClasses: mutation.oldValue,
                        newClasses: element.className,
                        html: element.outerHTML
                    },
                    stack: new Error().stack // This will show us where the change came from
                }, {
                    file: 'shortcuts-handlers.js',
                    line: 'mutationObserver',
                    function: 'mutationObserver'
                });
            }
        });
    });

    // Observe all action menus
    jQuery('.action-menu').each(function() {
        observer.observe(this, {
            attributes: true,
            attributeOldValue: true,
            attributeFilter: ['class']
        });
    });

    // First unbind any existing handlers to prevent duplicates
    jQuery(document).off('click', '.menu-toggle');
    jQuery(document).off('click', '.action-menu');

    // Handle menu toggle clicks
    jQuery(document).on('click', '.menu-toggle', function(e) {
        e.preventDefault();
        e.stopPropagation();

        const menuToggle = jQuery(this);
        const menuContainer = jQuery(menuToggle.closest('.menu-container'));
        const actionMenu = menuContainer.find('.action-menu');
        const actionButtons = actionMenu.find('.action-button');

        // DEBUG: Log element selection
        window.sh_debug_log('Element Selection Check', {
            menuToggle: {
                exists: menuToggle.length > 0,
                element: menuToggle[0].outerHTML
            },
            menuContainer: {
                exists: menuContainer.length > 0,
                element: menuContainer[0].outerHTML
            },
            actionMenu: {
                exists: actionMenu.length > 0,
                element: actionMenu.length > 0 ? actionMenu[0].outerHTML : 'Not found'
            }
        }, {
            file: 'shortcuts-handlers.js',
            line: 'menuToggleClick',
            function: 'menuToggleClick'
        });

        // DEBUG: Track menu toggle click and state
        window.sh_debug_log('Menu Toggle Click', {
            event: {
                type: e.type,
                targetId: e.target.id,
                targetClass: e.target.className
            },
            menuToggle: {
                hasActiveClass: menuToggle.hasClass('active'),
                id: menuToggle.attr('id'),
                classes: menuToggle.attr('class')
            },
            actionMenu: {
                hasActiveClass: actionMenu.hasClass('active'),
                currentVisibility: actionMenu.css('visibility'),
                id: actionMenu.attr('id'),
                classes: actionMenu.attr('class')
            },
            debug: true,
            page: 'shortcuts-list'
        }, {
            file: 'shortcuts-handlers.js',
            line: 'menuToggleClick',
            function: 'menuToggleClick'
        });

        menuToggle.removeAttr('style');
        actionMenu.removeAttr('style');
        
        const initialStates = {
            menuToggle: {
                hasClass: menuToggle.hasClass('active'),
                classes: menuToggle.attr('class'),
                inlineStyle: menuToggle.attr('style') || 'none'
            },
            actionMenu: {
                hasClass: actionMenu.hasClass('active'),
                classes: actionMenu.attr('class'),
                inlineStyle: actionMenu.attr('style') || 'none'
            },
            actionButtons: actionButtons.length ? {
                hasClass: actionButtons.hasClass('active'),
                classes: actionButtons.attr('class'),
                inlineStyle: actionButtons.attr('style') || 'none'
            } : null
        };
        
        // Close any other open menus
        jQuery('.menu-container').not(menuContainer).each(function() {
            const container = jQuery(this);
            container.find('.menu-toggle').removeClass('active');
            container.find('.action-menu').removeClass('active');
        });

        // DEBUG: Log before toggle
        window.sh_debug_log('Before Toggle', {
            menuToggle: {
                hasClass: menuToggle.hasClass('active'),
                allClasses: menuToggle.attr('class')
            },
            actionMenu: {
                hasClass: actionMenu.hasClass('active'),
                allClasses: actionMenu.attr('class')
            }
        }, {
            file: 'shortcuts-handlers.js',
            line: 'menuToggleClick',
            function: 'menuToggleClick'
        });

        // Try explicit class addition/removal instead of toggle
        const isActive = menuToggle.hasClass('active');
        if (isActive) {
            menuToggle.removeClass('active');
            actionMenu.removeClass('active');
        } else {
            menuToggle.addClass('active');
            actionMenu.addClass('active');
        }

        // DEBUG: Log after toggle
        window.sh_debug_log('After Toggle', {
            menuToggle: {
                hasClass: menuToggle.hasClass('active'),
                allClasses: menuToggle.attr('class')
            },
            actionMenu: {
                hasClass: actionMenu.hasClass('active'),
                allClasses: actionMenu.attr('class'),
                element: actionMenu[0].outerHTML
            }
        }, {
            file: 'shortcuts-handlers.js',
            line: 'menuToggleClick',
            function: 'menuToggleClick'
        });

        // DEBUG: Log immediate visibility state after setting
        window.sh_debug_log('Immediate Visibility State', {
            menu: {
                hasActiveClass: actionMenu.hasClass('active'),
                visibilityProperty: actionMenu.css('visibility'),
                computedVisibility: window.getComputedStyle(actionMenu[0]).visibility,
                element: actionMenu[0].outerHTML
            },
            debug: true,
            page: 'shortcuts-list'
        }, {
            file: 'shortcuts-handlers.js',
            line: 'menuToggleClick',
            function: 'menuToggleClick'
        });

        // DEBUG: Log menu visibility change
        window.sh_debug_log('Menu Visibility Change', {
            menu: {
                element: actionMenu[0].outerHTML,
                visibility: actionMenu.css('visibility'),
                display: actionMenu.css('display'),
                isActive: actionMenu.hasClass('active'),
                finalState: {
                    visibility: actionMenu.css('visibility'),
                    display: actionMenu.css('display'),
                    isActive: actionMenu.hasClass('active')
                }
            },
            debug: true,
            page: 'shortcuts-list'
        }, {
            file: 'shortcuts-handlers.js',
            line: 'menuToggleClick',
            function: 'menuToggleClick'
        });
    });

    // Prevent action menu clicks from bubbling
    jQuery(document).on('click', '.action-menu', function(e) {
        e.stopPropagation();
    });

    jQuery(document).on('click', function(e) {
        if (!jQuery(e.target).closest('.menu-container').length) {
            jQuery('.menu-container').each(function() {
                const container = jQuery(this);
                container.find('.menu-toggle').removeClass('active').removeAttr('style');
                container.find('.action-menu').removeClass('active').removeAttr('style');
            });
        }
    });

    // Handle versions button click
    jQuery('#shortcuts-container').on('click', '.versions-button', function(e) {
        e.preventDefault();
        const shortcutId = jQuery(this).data('id');
        
        // DEBUG: Log versions button click
        if (typeof window.sh_debug_log === 'function') {
            window.sh_debug_log('Versions button clicked', 
                {
                    debug: true,
                    message: 'User clicked versions button',
                    button_data: shortcutId,
                    url_params: {
                        page: 'shortcuts-list',
                        view: 'versions',
                        sb_id: shortcutId
                    }
                },
                {
                    file: 'shortcuts-handlers.js',
                    line: 'versionsButtonClick',
                    function: 'versionsButtonClick'
                }
            );
        }

        // Update URL with proper parameters
        const params = new URLSearchParams(window.location.search);
        params.set('page', 'shortcuts-list');
        params.set('view', 'versions');
        params.set('sb_id', shortcutId);
        window.history.replaceState({}, '', `${window.location.pathname}?${params.toString()}`);

        // Toggle versions view
        toggleVersionsView(true, shortcutId);
    });

    jQuery('#shortcuts-view').on('click', '.edit-button', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        const postId = jQuery(this).data('post_id');
        const shortcutData = jQuery(this).data('shortcut');
        const editModal = jQuery('#edit-shortcut-modal');
        
        editModal.find('#id').val(postId);
        editModal.find('#shortcut-name').val(shortcutData.name);
        editModal.find('#shortcut-headline').val(shortcutData.headline);
        editModal.find('#shortcut-description').val(shortcutData.description);
        
        if (shortcutData.icon) {
            try {
                const iconData = JSON.parse(shortcutData.icon);
                editModal.find('#shortcut-icon').val(iconData.name);
                editModal.find('#shortcut-icon-type').val(iconData.type);
                
                const previewIcon = editModal.find('.icon-preview');
                previewIcon.removeClass('empty');
                if (iconData.type === 'fontawesome') {
                    previewIcon.html(`<i class="${iconData.name}"></i>`);
                } else if (iconData.type === 'custom' && iconData.url) {
                    previewIcon.html(`<img src="${iconData.url}" alt="Icon">`);
                }
            } catch (e) {
                console.error('Error parsing icon data:', e);
            }
        }
        
        const colorInput = editModal.find('#shortcut-color');
        const colorPicker = editModal.find('.color-picker');
        const colorValue = editModal.find('.color-value');
        
        colorInput.val(shortcutData.color);
        colorPicker.val(shortcutData.color);
        colorValue.val(shortcutData.color);
        colorPicker.css('background-color', shortcutData.color);
        
        const shortcutInput = shortcutData.input || '';
        editModal.find('#shortcut-input').val(shortcutInput);
        
        editModal.find('#edit-shortcut-name-display').text(shortcutData.name || 'Unnamed Shortcut');
        
        const isPublished = shortcutData.status === 'publish';
        editModal.find('.update-shortcut, .revert-button').toggle(isPublished);
        editModal.find('.publish-shortcut, .save-draft-button').toggle(!isPublished);
        
        setTimeout(() => {
            editModal.css('display', 'block').addClass('active');
            jQuery('body').addClass('modal-open');
        }, 0);
    });

    jQuery('#shortcuts-view').on('click', '.delete-button', function(e) {
        e.preventDefault();
        const $button = jQuery(this);
        const post_id = $button.data('post_id');
        const sb_id = $button.data('sb_id');
        const $item = $button.closest('.shortcut-item');
        const isDraft = $item.find('.draft-badge').length > 0;

        handleShortcutStateChange(post_id, sb_id, this, true);
    });

    jQuery('#shortcuts-view').on('click', '.restore-button', function(e) {
        e.preventDefault();
        const $button = jQuery(this);
        const postId = $button.data('post_id');
        const sbId = $button.data('sb_id');

        deleteShortcut(postId, sbId, this, false, true);
    });

    jQuery('#shortcuts-view').on('click', '.delete-permanently', function(e) {
        e.preventDefault();
        e.stopPropagation();
        const post_id = jQuery(this).data('post_id');
        const sb_id = jQuery(this).data('sb_id');

        if (confirm('Are you sure you want to PERMANENTLY delete this shortcut? This action cannot be undone.')) {
            deleteShortcut(postId, sbId, this, true, false);
        }
    });

    jQuery('#shortcuts-view').on('click', '.delete-dropdown-toggle', function(e) {
        e.preventDefault();
        e.stopPropagation();
        const dropdown = jQuery(this).siblings('.delete-dropdown-content');

        jQuery('.delete-dropdown-content').not(dropdown).removeClass('show');
        dropdown.toggleClass('show');
    });

    jQuery(document).on('click', function(e) {
        if (!jQuery(e.target).closest('.btn-group').length) {
            jQuery('.delete-dropdown-content').removeClass('show');
        }
    });

    jQuery('#shortcuts-view').on('click', '.synced-text', function() {
        const id = jQuery(this).data('id');

        openEditModal(id);
    });

    jQuery('.cancel-button').click(function() {
        const modal = jQuery(this).closest('.modal');
        modal.removeClass('active');
        jQuery('body').removeClass('modal-open');
        setTimeout(() => {
            modal.hide();
        }, 300);
    });

    jQuery('#edit-shortcut-form').on('submit', function(e) {
        e.preventDefault();
        const $form = jQuery(this);
        const $submitButton = jQuery(document.activeElement);
        const status = $submitButton.data('status');

        const iconData = {
            type: $form.find('#icon-type-selector').val() || 'fontawesome',
            name: $form.find('#shortcut-icon').val(),
            url: null
        };
        
        const formData = {
            action: 'update_shortcut',
            security: shortcutsHubData.security.update_shortcut,
            shortcut_data: {
                post_id: $form.find('#id').val(),
                name: $form.find('#shortcut-name').val(),
                headline: $form.find('#shortcut-headline').val(),
                description: $form.find('#shortcut-description').val(),
                icon: JSON.stringify(iconData),
                color: $form.find('#shortcut-color').val(),
                input: $form.find('#shortcut-input').val(),
                result: $form.find('#shortcut-result').val(),
                state: status
            }
        };

        jQuery.ajax({
            url: shortcutsHubData.ajax_url,
            type: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    jQuery('#edit-shortcut-modal').removeClass('active');
                    jQuery('body').removeClass('modal-open');
                    setTimeout(() => {
                        jQuery('#edit-shortcut-modal').hide();
                    }, 300);
                    loadShortcuts();
                }
            }
        });
    });

    checkUrlParameters();
});

function handleShortcutStateChange(post_id, sb_id, buttonElement, isDeleted) {
    deleteShortcut(post_id, sb_id, buttonElement, false, !isDeleted);
}

function checkUrlParameters() {
    const urlParams = new URLSearchParams(window.location.search);
    const view = urlParams.get('view');
    const shortcutId = urlParams.get('id');

    if (view === 'versions' && shortcutId) {
        toggleVersionsView(true, shortcutId);
    } else {
        toggleVersionsView(false);
    }
}