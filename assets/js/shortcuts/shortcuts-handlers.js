jQuery(document).ready(function() {
    // Menu toggle handlers
    jQuery(document).on('click', '.menu-toggle', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        console.group('Menu Toggle Click Handler');
        console.log('Event:', e);
        
        const menuToggle = jQuery(this);
        console.log('Menu Toggle Element:', menuToggle[0]);
        
        const menuContainer = jQuery(menuToggle.closest('.menu-container'));
        console.log('Menu Container:', menuContainer[0]);
        
        const actionMenu = menuContainer.find('.action-menu');
        console.log('Action Menu:', actionMenu[0]);
        
        const actionButtons = actionMenu.find('.action-button');
        console.log('Action Buttons:', actionButtons.toArray());

        // Remove any inline styles that might interfere
        console.log('Removing inline styles...');
        console.log('Before menuToggle style:', menuToggle.attr('style'));
        menuToggle.removeAttr('style');
        console.log('After menuToggle style:', menuToggle.attr('style'));
        
        console.log('Before actionMenu style:', actionMenu.attr('style'));
        actionMenu.removeAttr('style');
        console.log('After actionMenu style:', actionMenu.attr('style'));
        
        // Get initial states for logging
        const initialStates = {
            menuToggle: {
                hasClass: menuToggle.hasClass('active'),
                styles: window.getComputedStyle(menuToggle[0]),
                cssText: window.getComputedStyle(menuToggle[0]).cssText,
                allClasses: menuToggle.attr('class'),
                parentClasses: menuContainer.attr('class'),
                inlineStyle: menuToggle.attr('style') || 'none'
            },
            actionMenu: {
                hasClass: actionMenu.hasClass('active'),
                styles: window.getComputedStyle(actionMenu[0]),
                cssText: window.getComputedStyle(actionMenu[0]).cssText,
                allClasses: actionMenu.attr('class'),
                parentClasses: menuContainer.attr('class'),
                inlineStyle: actionMenu.attr('style') || 'none'
            },
            firstButton: actionButtons.length ? {
                styles: window.getComputedStyle(actionButtons[0]),
                cssText: window.getComputedStyle(actionButtons[0]).cssText,
                allClasses: actionButtons.attr('class'),
                parentClasses: actionMenu.attr('class'),
                inlineStyle: actionButtons.attr('style') || 'none'
            } : null
        };
        
        // Close any other open menus first
        console.log('Closing other open menus...');
        jQuery('.menu-container').not(menuContainer).each(function() {
            const container = jQuery(this);
            console.log('Closing menu in container:', container[0]);
            container.find('.menu-toggle').removeClass('active').removeAttr('style');
            container.find('.action-menu').removeClass('active').removeAttr('style');
        });
        
        // Toggle current menu
        console.log('Toggling current menu...');
        console.log('Before toggle - menuToggle classes:', menuToggle.attr('class'));
        console.log('Before toggle - actionMenu classes:', actionMenu.attr('class'));
        
        menuToggle.toggleClass('active');
        actionMenu.toggleClass('active');
        
        console.log('After toggle - menuToggle classes:', menuToggle.attr('class'));
        console.log('After toggle - actionMenu classes:', actionMenu.attr('class'));
        
        // Get computed styles after toggle
        const menuToggleStyles = window.getComputedStyle(menuToggle[0]);
        const actionMenuStyles = window.getComputedStyle(actionMenu[0]);
        
        console.log('Final computed styles:', {
            menuToggle: {
                opacity: menuToggleStyles.opacity,
                visibility: menuToggleStyles.visibility,
                pointerEvents: menuToggleStyles.pointerEvents
            },
            actionMenu: {
                opacity: actionMenuStyles.opacity,
                visibility: actionMenuStyles.visibility,
                pointerEvents: actionMenuStyles.pointerEvents
            }
        });
        
        // Get final states after changes
        const finalStates = {
            menuToggle: {
                hasClass: menuToggle.hasClass('active'),
                styles: window.getComputedStyle(menuToggle[0]),
                cssText: window.getComputedStyle(menuToggle[0]).cssText,
                allClasses: menuToggle.attr('class'),
                parentClasses: menuContainer.attr('class'),
                inlineStyle: menuToggle.attr('style') || 'none'
            },
            actionMenu: {
                hasClass: actionMenu.hasClass('active'),
                styles: window.getComputedStyle(actionMenu[0]),
                cssText: window.getComputedStyle(actionMenu[0]).cssText,
                allClasses: actionMenu.attr('class'),
                parentClasses: menuContainer.attr('class'),
                inlineStyle: actionMenu.attr('style') || 'none'
            },
            firstButton: finalStates.firstButton ? {
                styles: window.getComputedStyle(actionButtons[0]),
                cssText: window.getComputedStyle(actionButtons[0]).cssText,
                allClasses: actionButtons.attr('class'),
                parentClasses: actionMenu.attr('class'),
                inlineStyle: actionButtons.attr('style') || 'none'
            } : null
        };

        // DEBUG: Log the complete toggle interaction flow
        sh_debug_log('Menu Toggle Interaction Flow', {
            'message': 'Complete flow of menu toggle interaction',
            'source': {
                'file': 'shortcuts-handlers.js',
                'line': 'menu-toggle-click',
                'function': 'menu-toggle-click'
            },
            'data': {
                'executedCode': {
                    'toggleButton': 'menuToggle.toggleClass("active")',
                    'actionMenu': 'actionMenu.toggleClass("active")'
                },
                'domStructure': {
                    'toggleSelector': menuToggle.get(0).outerHTML,
                    'menuContainer': menuContainer.get(0).outerHTML,
                    'actionMenuHTML': actionMenu.get(0).outerHTML
                },
                'beforeToggle': {
                    'menuToggle': {
                        'hasActiveClass': initialStates.menuToggle.hasClass,
                        'opacity': initialStates.menuToggle.styles.opacity,
                        'visibility': initialStates.menuToggle.styles.visibility,
                        'cssText': initialStates.menuToggle.cssText,
                        'allClasses': initialStates.menuToggle.allClasses,
                        'parentClasses': initialStates.menuToggle.parentClasses,
                        'inlineStyle': initialStates.menuToggle.inlineStyle
                    },
                    'actionMenu': {
                        'hasActiveClass': initialStates.actionMenu.hasClass,
                        'opacity': initialStates.actionMenu.styles.opacity,
                        'visibility': initialStates.actionMenu.styles.visibility,
                        'cssText': initialStates.actionMenu.cssText,
                        'allClasses': initialStates.actionMenu.allClasses,
                        'parentClasses': initialStates.actionMenu.parentClasses,
                        'inlineStyle': initialStates.actionMenu.inlineStyle
                    },
                    'firstButton': initialStates.firstButton ? {
                        'opacity': initialStates.firstButton.styles.opacity,
                        'visibility': initialStates.firstButton.styles.visibility,
                        'cssText': initialStates.firstButton.cssText,
                        'allClasses': initialStates.firstButton.allClasses,
                        'parentClasses': initialStates.firstButton.parentClasses,
                        'inlineStyle': initialStates.firstButton.inlineStyle
                    } : null
                },
                'afterToggle': {
                    'menuToggle': {
                        'hasActiveClass': finalStates.menuToggle.hasClass,
                        'opacity': finalStates.menuToggle.styles.opacity,
                        'visibility': finalStates.menuToggle.styles.visibility,
                        'cssText': finalStates.menuToggle.cssText,
                        'allClasses': finalStates.menuToggle.allClasses,
                        'parentClasses': finalStates.menuToggle.parentClasses,
                        'inlineStyle': finalStates.menuToggle.inlineStyle
                    },
                    'actionMenu': {
                        'hasActiveClass': finalStates.actionMenu.hasClass,
                        'opacity': finalStates.actionMenu.styles.opacity,
                        'visibility': finalStates.actionMenu.styles.visibility,
                        'cssText': finalStates.actionMenu.cssText,
                        'allClasses': finalStates.actionMenu.allClasses,
                        'parentClasses': finalStates.actionMenu.parentClasses,
                        'inlineStyle': finalStates.actionMenu.inlineStyle
                    },
                    'firstButton': finalStates.firstButton ? {
                        'opacity': finalStates.firstButton.styles.opacity,
                        'visibility': finalStates.firstButton.styles.visibility,
                        'cssText': finalStates.firstButton.cssText,
                        'allClasses': finalStates.firstButton.allClasses,
                        'parentClasses': finalStates.firstButton.parentClasses,
                        'inlineStyle': finalStates.firstButton.inlineStyle
                    } : null
                },
                'cssRules': {
                    'menuToggleActive': '.menu-toggle.active { opacity: 0 !important; visibility: hidden !important; }',
                    'actionMenuActive': '.action-menu.active { opacity: 1 !important; visibility: visible !important; }'
                }
            },
            'debug': true
        });
        
        console.groupEnd();

        // Keep the sh_debug_log for backend logging
        sh_debug_log('Menu Toggle Interaction Flow', {
            message: 'Menu toggle interaction details',
            source: {
                file: 'shortcuts-handlers.js',
                line: 'menuToggleHandler',
                function: 'menuToggleHandler'
            },
            data: {
                executedCode: {
                    toggleButton: 'menuToggle.toggleClass("active")',
                    actionMenu: 'actionMenu.toggleClass("active")'
                },
                domStructure: {
                    toggleSelector: menuToggle[0].outerHTML,
                    menuContainer: menuContainer[0].outerHTML,
                    actionMenuHTML: actionMenu[0].outerHTML
                },
                finalComputedStyles: {
                    menuToggle: {
                        opacity: menuToggleStyles.opacity,
                        visibility: menuToggleStyles.visibility,
                        pointerEvents: menuToggleStyles.pointerEvents
                    },
                    actionMenu: {
                        opacity: actionMenuStyles.opacity,
                        visibility: actionMenuStyles.visibility,
                        pointerEvents: actionMenuStyles.pointerEvents
                    }
                }
            },
            debug: true
        });
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

    jQuery(document).on('click', '.action-menu', function(e) {
        e.stopPropagation();
    });

    // Version and edit handlers
    jQuery(document).on('click', '.version-button', function() {
        const sb_id = jQuery(this).data('id');
        const urlParams = new URLSearchParams(window.location.search);
        urlParams.set('view', 'versions');
        urlParams.set('id', sb_id);
        window.history.pushState({}, '', `${window.location.pathname}?${urlParams}`);

        toggleVersionsView(true, sb_id);
    });

    // Edit button handler
    jQuery(document).on('click', '.edit-button', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        const postId = jQuery(this).data('post_id');
        const shortcutData = jQuery(this).data('shortcut');
        const editModal = jQuery('#edit-shortcut-modal');
        
        // Set the form values
        editModal.find('#id').val(postId);
        editModal.find('#shortcut-name').val(shortcutData.name);
        editModal.find('#shortcut-headline').val(shortcutData.headline);
        editModal.find('#shortcut-description').val(shortcutData.description);
        
        // Handle icon
        if (shortcutData.icon) {
            try {
                const iconData = JSON.parse(shortcutData.icon);
                editModal.find('#shortcut-icon').val(iconData.name);
                editModal.find('#shortcut-icon-type').val(iconData.type);
                
                // Update icon preview
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
        
        // Handle color - set both the input and the background color
        const colorInput = editModal.find('#shortcut-color');
        const colorPicker = editModal.find('.color-picker');
        const colorValue = editModal.find('.color-value');
        
        colorInput.val(shortcutData.color);
        colorPicker.val(shortcutData.color);
        colorValue.val(shortcutData.color);
        colorPicker.css('background-color', shortcutData.color);
        
        // Set input with proper handling for null/undefined
        const shortcutInput = shortcutData.input || '';
        editModal.find('#shortcut-input').val(shortcutInput);
        console.log('Setting input value:', shortcutInput);
        
        // Set the name in the h2
        editModal.find('#edit-shortcut-name-display').text(shortcutData.name || 'Unnamed Shortcut');
        
        // Show/hide buttons based on status
        const isPublished = shortcutData.status === 'publish';
        editModal.find('.update-shortcut, .revert-button').toggle(isPublished);
        editModal.find('.publish-shortcut, .save-draft-button').toggle(!isPublished);
        
        // Show modal after data is loaded
        setTimeout(() => {
            editModal.css('display', 'block').addClass('active');
            jQuery('body').addClass('modal-open');
        }, 0);
    });

    jQuery(document).on('click', '.delete-button', function(e) {
        e.preventDefault();
        const $button = jQuery(this);
        const post_id = $button.data('post_id');
        const sb_id = $button.data('sb_id');
        const $item = $button.closest('.shortcut-item');
        const isDraft = $item.find('.draft-badge').length > 0;

        handleShortcutStateChange(post_id, sb_id, this, true);
    });

    jQuery(document).on('click', '.restore-button', function(e) {
        e.preventDefault();
        const $button = jQuery(this);
        const postId = $button.data('post_id');
        const sbId = $button.data('sb_id');

        // Call deleteShortcut directly with restore=true
        deleteShortcut(postId, sbId, this, false, true);
    });

    jQuery(document).on('click', '.delete-permanently', function(e) {
        e.preventDefault();
        e.stopPropagation(); // Stop event from bubbling up
        const post_id = jQuery(this).data('post_id');
        const sb_id = jQuery(this).data('sb_id');

        if (confirm('Are you sure you want to PERMANENTLY delete this shortcut? This action cannot be undone.')) {
            deleteShortcut(post_id, sb_id, this, true, false);
        }
    });

    // Dropdown handlers
    jQuery(document).on('click', '.delete-dropdown-toggle', function(e) {
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

    jQuery(document).on('click', '.synced-text', function() {
        const id = jQuery(this).data('id');

        openEditModal(id);
    });

    // Cancel button handler
    jQuery('.cancel-button').click(function() {
        const modal = jQuery(this).closest('.modal');
        modal.removeClass('active');
        jQuery('body').removeClass('modal-open');
        setTimeout(() => {
            modal.hide();
        }, 300);
    });

    // Edit form submission handler
    jQuery('#edit-shortcut-form').on('submit', function(e) {
        e.preventDefault();
        const $form = jQuery(this);
        const $submitButton = jQuery(document.activeElement);
        const status = $submitButton.data('status');

        // Prepare icon data as a proper object
        const iconData = {
            type: $form.find('#icon-type-selector').val() || 'fontawesome',
            name: $form.find('#shortcut-icon').val(),
            url: null // Add URL handling if needed
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
                    // Close modal and refresh list
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