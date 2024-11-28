class ShortcutsHubMyAccountHandler extends elementorModules.frontend.handlers.Base {
    getDefaultSettings() {
        console.log('ShortcutsHubMyAccountHandler: Initializing settings');
        return {
            selectors: {
                tabLinks: '.woocommerce-MyAccount-navigation-link a',
                tabWrapper: '.e-my-account-tab',
                tabItem: '.woocommerce-MyAccount-navigation-link',
                tabContent: '.e-my-account-tab__content'
            }
        };
    }

    getDefaultElements() {
        const selectors = this.getSettings('selectors');
        const elements = {
            $tabLinks: this.$element.find(selectors.tabLinks),
            $tabWrapper: this.$element.find(selectors.tabWrapper),
            $tabItems: this.$element.find(selectors.tabItem),
            $tabContents: this.$element.find(selectors.tabContent)
        };
        console.log('ShortcutsHubMyAccountHandler: Found elements', elements);
        return elements;
    }

    bindEvents() {
        console.log('ShortcutsHubMyAccountHandler: Binding events');
        this.elements.$tabLinks.on('click', this.onTabClick.bind(this));
    }

    onTabClick(event) {
        event.preventDefault();
        const $clickedLink = jQuery(event.currentTarget);
        const tabId = $clickedLink.data('tab');
        console.log('ShortcutsHubMyAccountHandler: Tab clicked', tabId);
        this.activateTab(tabId);
    }

    activateTab(tabId) {
        console.log('ShortcutsHubMyAccountHandler: Activating tab', tabId);
        
        // Update navigation
        this.elements.$tabItems.removeClass('is-active');
        this.elements.$tabItems.filter('.woocommerce-MyAccount-navigation-link--' + tabId).addClass('is-active');

        // Update content
        this.elements.$tabContents.hide();
        this.elements.$tabContents.filter('[e-my-account-page="' + tabId + '"]').show();

        // Update wrapper attribute
        this.elements.$tabWrapper.attr('e-my-account-page', tabId);
    }

    onInit() {
        console.log('ShortcutsHubMyAccountHandler: Initializing');
        super.onInit();
        // Set initial tab
        const initialTab = this.elements.$tabWrapper.attr('e-my-account-page') || 'dashboard';
        console.log('ShortcutsHubMyAccountHandler: Setting initial tab', initialTab);
        this.activateTab(initialTab);
    }
}

console.log('ShortcutsHubMyAccountHandler: Script loaded');

elementorFrontend.hooks.addAction('frontend/element_ready/shortcuts-hub-my-account.default', ($element) => {
    console.log('ShortcutsHubMyAccountHandler: Widget ready', $element);
    elementorFrontend.elementsHandler.addHandler(ShortcutsHubMyAccountHandler, {
        $element,
    });
});
