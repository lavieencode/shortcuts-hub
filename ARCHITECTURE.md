# Shortcuts Hub Architecture

## Overview
Shortcuts Hub is a WordPress plugin that manages and displays shortcuts in an organized and user-friendly manner. It integrates with Elementor for dynamic content display and provides a robust shortcut management system.

## Core Components

### 1. Shortcut Management
- **Post Type**: Custom post type 'shortcut' for storing shortcut data
- **Meta Fields**:
  - `name`: Shortcut name
  - `headline`: Brief description
  - `description`: Detailed description
  - `color`: Theme color
  - `icon`: JSON-structured icon data supporting:
    - FontAwesome icons: `{"type": "fontawesome", "name": "fas fa-icon-name"}`
    - Custom icons: `{"type": "custom", "url": "path/to/icon.svg"}`
  - `input`: Input/trigger information
  - `result`: Expected outcome
  - `sb_id`: External reference ID

### 2. Admin Interface
- **Edit Shortcut Page** (`edit-shortcut.js`):
  - Form for managing shortcut data
  - Icon selector with FontAwesome and custom upload support
  - Color picker integration
  - AJAX-based save/update functionality

### 3. Icon System
- **Icon Selector** (`icon-selector.js`):
  - FontAwesome icon grid
  - Custom icon upload via WordPress Media Library
  - Preview functionality
  - JSON-based data structure for consistent storage

### 4. Elementor Integration
- **Dynamic Tags**:
  - `Icon_Dynamic_Tag`: Renders shortcut icons (FontAwesome/custom)
  - `Name_Dynamic_Tag`: Displays shortcut name
  - `Headline_Dynamic_Tag`: Shows shortcut headline
  - `Description_Dynamic_Tag`: Presents shortcut description
  - `Color_Dynamic_Tag`: Provides shortcut theme color

- **Widgets**:
  - **Shortcuts_Icon_Widget**: Displays shortcut icon with customization options
  - Support for both dynamic (shortcut-based) and custom icons
  - Styling controls for size, color, and animations
  - **Download Button** (`download-button.php`):
    - Elementor widget for shortcut downloads
    - Handles download tracking and version management
    - Integrates with WordPress AJAX system
  - **Download Log Table** (`download-log-table.php`):
    - Elementor widget displaying user's download history
    - Simple HTML table showing:
      - Shortcut Name
      - Download Date
      - Version
    - Handles empty state with user-friendly message
    - Uses existing download logging database table
    - Sorted by most recent downloads first

### 5. Data Flow
1. **Icon Selection**:
   - User selects icon (FontAwesome/custom)
   - Data stored as JSON in meta field
   - Format: `{"type": "fontawesome|custom", "name": "icon-class", "url": "custom-url"}`

2. **Icon Rendering**:
   - Dynamic tag retrieves icon data from meta
   - Parses JSON structure
   - Renders appropriate icon type
   - Handles legacy format conversion

3. **Widget Display**:
   - Retrieves current shortcut icon
   - Supports view customization
   - Falls back to default if no icon set

### 6. Download and Login Flow
- **Download Button** (`download-button.php`):
  - Sets download URL as data attribute on button during page load via fetch_version call
  - Stores download URL in button's data-download-url attribute
  - Stores redirect URL (current page) in button's data-redirect-url attribute
  - Logs download attempts and handles popup window display

- **Login Flow** (`login-flow.php`, `login-redirect.js`):
  - Uses data attributes from download button to maintain state through login process
  - After successful login:
    1. Opens download URL in popup window (800x600)
    2. Shows success message
    3. Redirects main window back to original shortcut page

- **Data Flow**:
  1. Fetch version call sets download URL on button load
  2. Button click captures URLs from data attributes
  3. Login form submission includes these URLs in form data
  4. Login success triggers download popup and redirect

### 7. Authentication System
- **Login Flow** (`login-flow.php`, `login-redirect.js`):
  - Elementor form integration for login
  - AJAX-based authentication
  - Secure password handling
  - Remember me functionality
  - Smart URL redirection system
  - Download URL preservation through login process

- **Registration Flow** (`registration-flow.php`):
  - Custom user role creation
  - Automatic login after registration
  - Data persistence through registration process
  - Integration with download system

- **Logout Handler** (`logout-handler.js`):
  - AJAX-based logout
  - State preservation during logout
  - Intelligent redirect handling
  - Fallback mechanisms for failed AJAX requests

### 8. Session Management
- **Data Persistence**:
  - Multi-layer storage (sessionStorage, cookies)
  - Shortcut data preservation
  - Download URL tracking
  - Redirect URL management

- **State Handling**:
  - User authentication state
  - Download state preservation
  - Form submission state
  - Error state management

### 9. API Integration
- **Switchblade API** (`sb-api.php`, `auth.php`):
  - Token-based authentication
  - Automatic token refresh
  - Error handling and logging
  - Secure credential management

### 10. My Account Widget Implementation Details

#### Script Loading and Initialization
1. **Proper Hook Usage**:
   ```php
   // Add script loading for both editor and preview contexts
   add_action('elementor/editor/after_enqueue_scripts', function() {
       wp_enqueue_script('shortcuts-hub-my-account', /* ... */);
   });
   add_action('elementor/preview/enqueue_scripts', function() {
       wp_enqueue_script('shortcuts-hub-my-account', /* ... */);
   });
   ```
   - Editor hook loads script in Elementor's editing interface
   - Preview hook ensures script runs in the preview iframe
   - Both are necessary for full functionality

2. **JavaScript Initialization**:
   ```javascript
   // Promise-based initialization for reliable elementorFrontend detection
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

   // Async initialization function
   async function initializeWidget() {
       try {
           await waitForElementorFrontend();
           elementorFrontend.hooks.addAction('frontend/element_ready/shortcuts-hub-my-account.default', function($scope) {
               // Widget initialization code
           });
       } catch (error) {
           console.error('Failed to initialize widget:', error);
       }
   }

   // Start initialization when document is ready
   jQuery(document).ready(initializeWidget);
   ```
   - Uses promise-based approach for reliable elementorFrontend detection
   - Leverages `requestAnimationFrame` for efficient polling
   - Handles slow-loading editor environments gracefully
   - Provides comprehensive error handling
   - Maintains proper scoping and event handling

#### Content Rendering Strategy
1. **Editor vs Frontend Rendering**:
   - Editor mode shows all tabs with preview content
   - Frontend mode only shows current tab's content
   - Different rendering methods prevent conflicts

2. **Preventing Duplicate Content**:
   ```php
   if ($page === 'shortcuts') {
       // Only output shortcuts content once
       ob_start();
       $this->render_shortcuts_content();
       echo ob_get_clean();
   }
   ```
   - Use output buffering to capture content
   - Prevents duplicate rendering in editor mode
   - Maintains clean DOM structure

3. **Tab Content Structure**:
   ```php
   <div class="e-my-account-tab__<?php echo esc_attr($page); ?> e-my-account-tab__content">
   ```
   - Each tab's content wrapped in specific classes
   - Enables proper styling and JavaScript targeting
   - Maintains WooCommerce class hierarchy

#### Layout Implementation
1. **Grid-based Layout**:
   ```php
   'style' => 'display: grid; grid-template-columns: 200px 1fr; gap: 20px; align-items: start;'
   ```
   - Uses CSS Grid for reliable layout
   - 200px fixed width for navigation
   - Flexible content area
   - Proper vertical alignment

2. **Class Structure**:
   ```php
   'class' => [
       'e-my-account-tab',
       'woocommerce',
       'elementor-grid',
   ]
   ```
   - Maintains compatibility with Elementor
   - Preserves WooCommerce styling
   - Enables proper grid functionality

#### Tab Switching Implementation
1. **Event Handling**:
   ```javascript
   $tabLinks.on('click', function(e) {
       e.preventDefault();
       // Get tab ID and update display
   });
   ```
   - Prevents default link behavior
   - Updates active states
   - Handles content visibility

2. **Content Visibility**:
   ```javascript
   $tabContents.hide().removeClass('e-my-account-tab__content--active');
   $scope.find('[e-my-account-page="' + tabId + '"]')
       .show()
       .addClass('e-my-account-tab__content--active');
   ```
   - Hides all content first
   - Shows only active tab content
   - Maintains proper state classes

#### Common Issues and Solutions

1. **Script Loading Timing**:
   - **Issue**: Scripts not loading in preview
   - **Solution**: Use both editor and preview hooks
   - **Why it Works**: Ensures script loads in all contexts

2. **Duplicate Content**:
   - **Issue**: Content appearing twice in editor
   - **Solution**: Use output buffering for content rendering
   - **Why it Works**: Captures and controls output timing

3. **Tab Switching**:
   - **Issue**: Tabs not working in preview
   - **Solution**: Proper initialization timing with Elementor hooks
   - **Why it Works**: Ensures code runs after widget is ready

4. **Layout Problems**:
   - **Issue**: Inconsistent tab/content alignment
   - **Solution**: CSS Grid with fixed navigation width
   - **Why it Works**: Provides stable, responsive layout

#### Best Practices
1. **Initialization**:
   - Always use proper Elementor hooks
   - Wait for frontend initialization
   - Use widget-specific action names

2. **Content Handling**:
   - Buffer output when needed
   - Separate editor and frontend rendering
   - Maintain proper class hierarchy

3. **Event Management**:
   - Prevent default when necessary
   - Use proper event delegation
   - Maintain state consistently

4. **Layout Structure**:
   - Use CSS Grid for main layout
   - Maintain WooCommerce classes
   - Follow Elementor structural patterns

## WooCommerce My Account Widget Integration Notes

### Endpoint Registration

#### What Works
1. Registering the endpoint with WordPress and WooCommerce:
```php
// In shortcuts-hub.php
add_rewrite_endpoint('shortcuts', EP_ROOT | EP_PAGES);

add_filter('woocommerce_get_query_vars', function($vars) {
    $vars['shortcuts'] = 'shortcuts';
    return $vars;
});
```

2. Adding the menu item to WooCommerce account menu:
```php
add_filter('woocommerce_account_menu_items', function($items) {
    $logout = false;
    if (isset($items['customer-logout'])) {
        $logout = $items['customer-logout'];
        unset($items['customer-logout']);
    }
    
    $items['shortcuts'] = esc_html__('Shortcuts', 'shortcuts-hub');
    
    if ($logout) {
        $items['customer-logout'] = $logout;
    }
    
    return $items;
});
```

This successfully makes the Shortcuts tab appear in:
- The rendered preview in the Elementor editor
- The frontend display of the My Account widget

#### What Doesn't Work
1. Using `wc_get_account_menu_items()` in the widget's `get_account_pages()` method:
```php
protected function get_account_pages() {
    $items = wc_get_account_menu_items();
    $pages = array();

    foreach ( $items as $key => $item ) {
        $pages[ $key ] = array(
            'label' => $item,
            'endpoint' => $key,
        );
    }

    return $pages;
}
```
This approach fails to make the Shortcuts tab appear in:
- The list of tabs under the Content tab in the Elementor widget settings

#### Reference
- Parent Widget Class: `ElementorPro\Modules\Woocommerce\Widgets\My_Account`
- WooCommerce Query Class: `WC_Query` (handles endpoint registration)
- Elementor Widget Base: `Elementor\Widget_Base`

## Investigation of Parent Widget Tab System

The parent widget (ElementorPro\Modules\Woocommerce\Widgets\My_Account) handles tabs in two ways:

1. **Tab Registration in Editor**:
   ```php
   protected function register_controls() {
       // ... other controls ...
       $repeater = new Repeater();
       $repeater->add_control(
           'tab_name',
           [
               'label' => esc_html__('Tab Name', 'elementor-pro'),
               'type' => Controls_Manager::TEXT,
               'dynamic' => [
                   'active' => true,
               ],
           ]
       );

       $this->add_control(
           'tabs',
           [
               'label' => '',
               'type' => Controls_Manager::REPEATER,
               'fields' => $repeater->get_controls(),
               'default' => [
                   [
                       'field_key' => 'dashboard',
                       'field_label' => esc_html__('Dashboard', 'elementor-pro'),
                       'tab_name' => esc_html__('Dashboard', 'elementor-pro'),
                   ],
                   // ... other tabs ...
               ],
           ]
       );
   }
   ```

2. **Frontend Tab Display**:
   - Uses `get_account_pages()` to define available pages
   - Modifies menu items through `modify_menu_items()` filter on 'woocommerce_account_menu_items'

### Tab Registration Solution 

After several attempts, we found that the only reliable way to add our tab to both the frontend and widget settings is to:

1. Let parent widget register all controls
2. Remove the tabs control completely
3. Re-add it with our shortcuts tab included in the defaults

```php
protected function register_controls() {
    parent::register_controls();

    // Remove the parent's tabs control first
    $this->remove_control('tabs');

    // Then add our own version with our tab included
    $repeater = new \Elementor\Repeater();
    $repeater->add_control(
        'tab_name',
        [
            'label' => esc_html__('Tab Name', 'shortcuts-hub'),
            'type' => \Elementor\Controls_Manager::TEXT,
            'dynamic' => [
                'active' => true,
            ],
        ]
    );

    $this->add_control(
        'tabs',
        [
            'label' => '',
            'type' => \Elementor\Controls_Manager::REPEATER,
            'fields' => $repeater->get_controls(),
            'item_actions' => [
                'add' => false,
                'duplicate' => false,
                'remove' => false,
                'sort' => false,
            ],
            'default' => [
                // Copy all parent tabs...
                [
                    'field_key' => 'shortcuts',
                    'field_label' => esc_html__('Shortcuts', 'shortcuts-hub'),
                    'tab_name' => esc_html__('Shortcuts', 'shortcuts-hub'),
                ],
            ],
            'title_field' => '{{{ tab_name }}}',
        ]
    );
}
```

This works because:
1. We preserve parent widget's styling by calling parent::register_controls()
2. We completely replace the tabs control instead of trying to modify it
3. We include all the original tabs plus our shortcuts tab

Failed approaches and why they didn't work:
1. ❌ Modifying parent's control after registration - Control settings are immutable
2. ❌ Using WooCommerce menu filter only - Doesn't affect widget settings
3. ❌ Trying to update control defaults - Defaults are locked once set
4. ❌ Complete control override without parent call - Lost all styling controls

## WooCommerce My Account Widget Integration Success

We've successfully integrated the Shortcuts tab into the WooCommerce My Account widget's settings panel in Elementor. Here's how it works:

1. **Widget Extension**:
   ```php
   class My_Account_Widget extends Elementor_My_Account {
       public function __construct($data = [], $args = null) {
           parent::__construct($data, $args);
           add_filter('woocommerce_account_menu_items', [$this, 'add_shortcuts_endpoint']);
           add_action('woocommerce_account_shortcuts_endpoint', [$this, 'render_shortcuts_content']);
       }
   }
   ```
   - Extends the WooCommerce My Account widget
   - Adds necessary hooks for the Shortcuts endpoint

2. **Tab Registration in Editor**:
   ```php
   protected function register_controls() {
       parent::register_controls();
       $tabs = $this->get_controls()['tabs'];
       $tabs['default'][] = [
           'field_key' => 'shortcuts',
           'field_label' => esc_html__('Shortcuts', 'shortcuts-hub'),
           'tab_name' => esc_html__('Shortcuts', 'shortcuts-hub'),
       ];
       $this->update_control('tabs', $tabs);
   }
   ```
   This approach successfully:
   - Preserves parent widget functionality
   - Adds our Shortcuts tab to the configurable settings
   - Maintains Elementor's widget control system

3. **Integration Points**:
   - The tab appears in the Elementor editor settings
   - The endpoint is properly registered with WooCommerce
   - The tab shows up in the frontend My Account menu

This implementation proves that extending the WooCommerce My Account widget while preserving its core functionality is possible. The key was modifying the tabs control after the parent widget's initialization but before the widget is fully registered.

## Current Widget Integration Status

#### What's Working
1. **My Account Widget Editor Integration**:
   - Shortcuts tab appears in the widget's Content tab settings
   - Successfully listed among configurable endpoints
   - Widget renders in the Elementor editor preview

2. **Endpoint Registration**:
   - WooCommerce endpoint properly registered
   - Menu item appears in account navigation
   - Basic content structure renders

#### Known Issues
1. **Tab Switching in Editor**:
   - All pages are showing simultaneously instead of one at a time
   - Tab clicking in editor preview doesn't trigger page switching
   - Need to investigate parent widget's tab switching mechanism

2. **Widget Category and Visibility**:
   - Shortcuts Hub category not appearing in Elementor editor
   - Download Button widget not visible in widget panel
   - May need to review widget registration and category definition

3. **Next Steps**:
   - Investigate parent widget's tab switching implementation
   - Debug category registration in Elementor
   - Review widget registration process for Download Button
   - Consider implementing custom tab switching logic if needed

This partial success suggests we're on the right track with the widget extension approach, but need to address the tab switching behavior and widget visibility issues.

## Best Practices
1. **Icon Data**:
   - Always store in JSON format
   - Include type identifier
   - Validate structure before save
   - Handle legacy data gracefully

2. **Error Handling**:
   - Validate data at each step
   - Provide meaningful fallbacks
   - Log important operations
   - Clear user feedback

3. **Performance**:
   - Cache expensive operations
   - Minimize DOM operations
   - Optimize media uploads
   - Use WordPress core functions

## Future Considerations
1. **Icon Management**:
   - Icon library categorization
   - Favorite/recent icons
   - Custom icon library

2. **UI/UX**:
   - Enhanced icon search
   - Bulk operations
   - Preview improvements
   - Mobile optimization

3. **Integration**:
   - Additional widget types
   - External API support
   - Import/export functionality

4. **Security**:
   - Enhanced token management
   - Rate limiting implementation
   - Additional authentication methods
   - Session security improvements

5. **Performance**:
   - API response caching
   - Asset optimization
   - Database query optimization
   - Lazy loading implementation
