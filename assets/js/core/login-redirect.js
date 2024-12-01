jQuery(document).ready(function($) {
    'use strict';
    
    // Helper function to log to both console and PHP
    function logFormFlow(message, data = null, isRegistration = false) {
        const prefix = isRegistration ? '[REGISTRATION FORM FLOW]' : '[LOGIN FORM FLOW]';
        const fullMessage = prefix + ' ' + message;
        
        // Only log to console in development
        if (typeof shortcutsHubAjax !== 'undefined' && shortcutsHubAjax.debug) {
            if (data) {
                console.log(fullMessage, data);
            } else {
                console.log(fullMessage);
            }
        }
        
        // Log to PHP
        if (typeof shortcutsHubAjax !== 'undefined') {
            $.ajax({
                url: shortcutsHubAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'shortcuts_hub_handle_log',
                    message: fullMessage,
                    data: data ? JSON.stringify(data) : '',
                    nonce: shortcutsHubAjax.nonce
                }
            });
        }
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
    
    // Function to determine if form is registration
    function isRegistrationForm($form) {
        const formId = $form.attr('id') || '';
        const formName = $form.data('form-name') || '';
        return formId === 'shortcuts_gallery_reg_form' || formName === 'Shortcuts Gallery Registration';
    }
    
    // Function to handle form submission and show popup
    function handleFormSubmission($form, $tokenField, $redirectField, isRegistration) {
        console.log('handleFormSubmission called', {
            token: $tokenField.val(),
            redirect: $redirectField.val(),
            isRegistration
        });

        const redirectTo = $redirectField.val();
        if (redirectTo) {
            console.log('Making AJAX request to log download');
            // Log the download
            $.ajax({
                url: shortcutsHubAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'shortcuts_hub_log_download',
                    nonce: shortcutsHubAjax.nonce,
                    token: $tokenField.val(),
                    redirect_url: redirectTo,
                    download_url: redirectTo
                },
                success: function(response) {
                    console.log('AJAX response:', response);
                    if (response.success) {
                        console.log('Showing popup for:', redirectTo);
                        showPopup(redirectTo);
                    } else {
                        console.error('Failed to log download:', response);
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    console.error('AJAX request failed:', {
                        status: textStatus,
                        error: errorThrown,
                        response: jqXHR.responseText
                    });
                }
            });
        } else {
            console.error('No redirect URL found');
        }
    }
    
    // Function to show popup
    function showPopup(redirectTo) {
        const popupHtml = `
            <div class="shortcuts-popup-overlay">
                <div class="shortcuts-popup-content">
                    <div class="shortcuts-popup-header">
                        <h3>Loading Shortcut...</h3>
                        <button class="shortcuts-popup-close">&times;</button>
                    </div>
                    <iframe src="${redirectTo}" frameborder="0" style="width:100%;height:80vh;"></iframe>
                </div>
            </div>
        `;

        // Add popup styles if not already added
        if (!document.getElementById('shortcuts-popup-styles')) {
            const styles = `
                <style id="shortcuts-popup-styles">
                    .shortcuts-popup-overlay {
                        position: fixed;
                        top: 0;
                        left: 0;
                        width: 100%;
                        height: 100%;
                        background: rgba(0,0,0,0.7);
                        display: flex;
                        justify-content: center;
                        align-items: center;
                        z-index: 9999;
                    }
                    .shortcuts-popup-content {
                        background: white;
                        padding: 20px;
                        border-radius: 8px;
                        width: 90%;
                        max-width: 1200px;
                        max-height: 90vh;
                        position: relative;
                    }
                    .shortcuts-popup-header {
                        display: flex;
                        justify-content: space-between;
                        align-items: center;
                        margin-bottom: 15px;
                    }
                    .shortcuts-popup-close {
                        background: none;
                        border: none;
                        font-size: 24px;
                        cursor: pointer;
                        padding: 5px;
                    }
                    .shortcuts-popup-close:hover {
                        color: #666;
                    }
                </style>
            `;
            $('head').append(styles);
        }

        // Add popup to page
        const $popup = $(popupHtml);
        $('body').append($popup);

        // Handle close button
        $popup.find('.shortcuts-popup-close').on('click', function() {
            $popup.remove();
        });

        // Close on overlay click
        $popup.on('click', function(e) {
            if ($(e.target).hasClass('shortcuts-popup-overlay')) {
                $popup.remove();
            }
        });
    }
    
    // Function to set form fields
    function setFormFields($form) {
        if (!$form?.length) {
            console.error('Form not found');
            return;
        }
        
        const isRegistration = isRegistrationForm($form);
        console.log('Setting up form fields for:', isRegistration ? 'registration' : 'login');
        
        const fields = {
            token: {
                name: isRegistration ? 'reg_download_token' : 'login_download_token',
                value: downloadToken
            },
            redirect: {
                name: isRegistration ? 'reg_redirect_url' : 'login_redirect_url',
                value: redirectUrl
            }
        };
        
        // Find form fields
        const elements = {};
        for (const [key, field] of Object.entries(fields)) {
            const selector = `[name="form_fields[${field.name}]"], [name="${field.name}"]`;
            const $element = $form.find(selector);
            
            if (!$element.length) {
                console.error(`${key} field not found with selector:`, selector);
                return;
            }
            elements[key] = $element;
            
            // Set field value if available
            if (field.value) {
                $element.val(field.value);
                console.log(`Set ${key} value:`, field.value);
            }
        }
        
        // Listen for successful form submission using Elementor's event
        const formId = $form.attr('data-form-id');
        console.log('Setting up form submission listener for form ID:', formId);
        
        jQuery(document).on('submit_success', function(e, response) {
            console.log('Form submit_success event fired:', {
                targetFormId: jQuery(e.target).attr('data-form-id'),
                expectedFormId: formId,
                response
            });
            
            const $targetForm = jQuery(e.target);
            if ($targetForm.attr('data-form-id') === formId) {
                console.log('Form ID matched, handling submission');
                handleFormSubmission($form, elements.token, elements.redirect, isRegistration);
            }
        });
    }
    
    // Process forms when they appear
    function processForm($form) {
        if (!$form || $form.length === 0) return;
        setFormFields($form);
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