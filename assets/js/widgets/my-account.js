console.log('MyAccount: Immediate execution');

(function($) {
    'use strict';

    const waitForElementorFrontend = () => {
        return new Promise((resolve) => {
            if (typeof elementorFrontend !== 'undefined') {
                resolve(elementorFrontend);
                return;
            }
            const checkElementor = () => {
                if (typeof elementorFrontend !== 'undefined') {
                    resolve(elementorFrontend);
                    return;
                }
                requestAnimationFrame(checkElementor);
            };
            requestAnimationFrame(checkElementor);
        });
    };

    async function initializeWidget() {
        try {
            await waitForElementorFrontend();
            elementorFrontend.hooks.addAction('frontend/element_ready/shortcuts-hub-my-account.default', function($scope) {
                console.log('MyAccount: Widget initialized');
                
                const $tabLinks = $scope.find('.woocommerce-MyAccount-navigation-link a');
                const $tabContents = $scope.find('.e-my-account-tab__content');
                
                console.log('MyAccount: Found tab links:', $tabLinks.length);
                console.log('MyAccount: Found tab contents:', $tabContents.length);
                
                // Set initial active state
                const setInitialActiveState = () => {
                    const $activeTab = $scope.find('.woocommerce-MyAccount-navigation-link.is-active');
                    if ($activeTab.length) {
                        const classes = $activeTab.attr('class').split(' ');
                        const tabId = classes.find(cls => cls.startsWith('woocommerce-MyAccount-navigation-link--')).replace('woocommerce-MyAccount-navigation-link--', '');
                        const $content = $scope.find('[e-my-account-page="' + tabId + '"], [data-endpoint="' + tabId + '"]');
                        if ($content.length) {
                            $content.show().addClass('e-my-account-tab__content--active');
                        }
                    }
                };
                
                setInitialActiveState();
                
                $tabLinks.on('click', function(e) {
                    e.preventDefault();
                    
                    const $tabItem = jQuery(this).closest('.woocommerce-MyAccount-navigation-link');
                    const classes = $tabItem.attr('class').split(' ');
                    const tabId = classes.find(cls => cls.startsWith('woocommerce-MyAccount-navigation-link--')).replace('woocommerce-MyAccount-navigation-link--', '');
                    
                    console.log('MyAccount: Tab clicked:', tabId);
                    
                    $scope.find('.woocommerce-MyAccount-navigation-link').removeClass('is-active');
                    $tabItem.addClass('is-active');
                    
                    $tabContents.hide().removeClass('e-my-account-tab__content--active');
                    
                    const $content = $scope.find('[e-my-account-page="' + tabId + '"], [data-endpoint="' + tabId + '"]');
                    if ($content.length) {
                        $content.show().addClass('e-my-account-tab__content--active');
                        // Trigger resize to handle any responsive elements
                        jQuery(window).trigger('resize');
                    }
                    
                    console.log('MyAccount: Content shown for tab:', tabId);
                });
            });
        } catch (error) {
            console.error('Failed to initialize widget:', error);
        }
    }

    jQuery(document).ready(initializeWidget);
})(jQuery);
