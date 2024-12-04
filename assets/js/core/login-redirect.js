jQuery(document).ready(function($) {
    'use strict';
    
    // Helper function to check if form is registration form
    function isRegistrationForm($form) {
        const formName = $form.find('input[name="form_name"]').val();
        return formName === 'Shortcuts Gallery Registration';
    }
    
    // Function to get URL parameter
    function getUrlParameter(name) {
        name = name.replace(/[\[]/, '\\[').replace(/[\]]/, '\\]');
        var regex = new RegExp('[\\?&]' + name + '=([^&#]*)');
        var results = regex.exec(location.search);
        if (results === null) return '';
        
        try {
            return decodeURIComponent(decodeURIComponent(results[1].replace(/\+/g, ' ')));
        } catch (e) {
            try {
                return decodeURIComponent(results[1].replace(/\+/g, ' '));
            } catch (e2) {
                return results[1].replace(/\+/g, ' ');
            }
        }
    }
    
    // Get URL parameters
    const downloadToken = getUrlParameter('download_token');
    const redirectUrl = getUrlParameter('redirect_url');
    
    // Function to show popup with iframe
    function showPopup(url) {
        // Create popup container
        const $popup = $('<div>', {
            class: 'shortcuts-popup',
            css: {
                position: 'fixed',
                top: '0',
                left: '0',
                width: '100%',
                height: '100%',
                backgroundColor: 'rgba(0, 0, 0, 0.8)',
                zIndex: '9999',
                display: 'flex',
                justifyContent: 'center',
                alignItems: 'center'
            }
        });

        // Create popup content
        const $content = $('<div>', {
            class: 'shortcuts-popup-content',
            css: {
                width: '80%',
                height: '80%',
                backgroundColor: '#fff',
                borderRadius: '8px',
                position: 'relative',
                padding: '20px'
            }
        });

        // Create close button
        const $close = $('<button>', {
            text: '×',
            css: {
                position: 'absolute',
                right: '10px',
                top: '10px',
                fontSize: '24px',
                border: 'none',
                background: 'none',
                cursor: 'pointer',
                color: '#333',
                zIndex: '10000'
            },
            click: function(e) {
                e.preventDefault();
                e.stopPropagation();
                $popup.remove();
            }
        });

        // Create iframe
        const $iframe = $('<iframe>', {
            src: url,
            css: {
                width: '100%',
                height: '100%',
                border: 'none',
                borderRadius: '8px'
            }
        });

        // Assemble popup
        $content.append($close, $iframe);
        $popup.append($content);
        $('body').append($popup);
    }
    
    // Store the form data when the form is submitted
    $(document).on('submit_success', '.elementor-form', function(event, response) {
        const $form = $(this);
        
        // Get download token and redirect URL from hidden fields
        const downloadToken = $form.find('input[name="form_fields[login_download_token]"], input[name="form_fields[reg_download_token]"]').val();
        const redirectUrl = $form.find('input[name="form_fields[login_redirect_url]"], input[name="form_fields[reg_redirect_url]"]').val();
        
        if (downloadToken && redirectUrl) {
            event.preventDefault();
            event.stopPropagation();
            
            // Store URL in session storage
            sessionStorage.setItem('shortcuts_redirect_url', redirectUrl);
            
            // Show popup immediately
            showPopup(redirectUrl);
            
            return false;
        }
    });
    
    // Process forms when they appear
    function processForm($form) {
        if (!$form || $form.length === 0) return;
    }
    
    // Find and process initial forms
    $('.elementor-form').each(function() {
        processForm($(this));
    });
    
    // Watch for dynamically added forms
    const observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            mutation.addedNodes.forEach(function(node) {
                if (node.nodeType === 1) { // Element node
                    const $form = $(node).find('.elementor-form');
                    if ($form.length > 0) {
                        processForm($form);
                    }
                }
            });
        });
    });
    
    // Start observing
    observer.observe(document.body, {
        childList: true,
        subtree: true
    });
});