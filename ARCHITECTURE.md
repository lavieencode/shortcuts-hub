# Shortcuts Hub Architecture

## Overview
Shortcuts Hub is a WordPress plugin that manages and displays shortcuts in an organized and user-friendly manner. It integrates with Elementor for dynamic content display and provides a robust shortcut management system.

## Quick Reference Q&A

### Where is the plugin initialized?
**Answer:** The plugin is initialized in `shortcuts-hub.php` (main entry point) which loads `class-shortcuts-hub.php` (main plugin class). The initialization happens during the WordPress `plugins_loaded` hook.

### Which databases does the plugin create and how are they managed?
**Answer:** The plugin creates and manages several database tables in the WordPress database:
1. **Shortcut Downloads Table (`wp_shortcutshub_downloads`)**: Created by the `create_downloads_table()` function in `database.php`, this table tracks user download history including shortcut_id, shortcut_name, version, download_date, post_url, and post_id. The table is created during plugin activation through the `maybe_create_tables()` method in `class-shortcuts-hub.php` which calls `create_downloads_table()`. It is also checked on each page load to ensure it exists. The My Account widget (`my-account-widget.php`) queries this table directly to show users their download history.
2. **Shortcut-Action Relationships Table (`wp_shortcuts_hub_action_shortcut`)**: Created in `class-shortcuts-hub.php` during plugin activation (in the `maybe_create_tables()` method), this table stores connections between shortcuts and their associated actions with columns for action_id, shortcut_id, and created_at timestamp.
3. **Custom Post Type Storage**: Shortcuts are stored as a custom post type in the WordPress database with associated meta fields for name, headline, description, color, icon, input, result, and external reference ID. The custom post type is registered in `class-shortcuts-hub.php`.

Additionally, the plugin connects to the external Switchblade database through `class-sb-db-manager.php`, which uses a singleton pattern to prevent exceeding max connections to the Switchblade server.

### Where is user authentication handled?
**Answer:** Authentication is handled in `auth.php` (for Switchblade API) and `login-flow.php` (for WordPress users). Token management, refresh logic, and rate limiting are implemented in these files.

### How are assets loaded?
**Answer:** Assets are managed through `enqueue-assets.php` (comprehensive asset management) and `enqueue-core.php` (core file inclusion). These files handle script and style enqueuing for both admin and frontend contexts.

### Where is the shortcut data stored?
**Answer:** Shortcuts are stored as a custom post type with meta fields for name, headline, description, color, icon, input, result, and external reference ID. The post type is registered in `class-shortcuts-hub.php`.

### How does the plugin integrate with Elementor?
**Answer:** Elementor integration is handled in the `includes/elementor` directory. It provides dynamic tags (in `dynamic-tags` subdirectory) and widgets (in `widgets` subdirectory) for displaying shortcut data in Elementor templates.

### Where is the download functionality implemented?
**Answer:** Download functionality is implemented in the Download Button widget (`download-button-widget.php`) and tracked through functions in `database.php`. The download flow is managed through `login-flow.php` when users need to authenticate.

### How are user roles and permissions managed?
**Answer:** User roles and permissions are defined in `user-role.php` in the core directory. This file creates custom user roles with appropriate capabilities for Shortcuts Hub users.

### Where is debugging functionality located?
**Answer:** Debugging is handled by `sh-debug.php` in the core directory. It provides logging to both console and file, with context-aware debug output control.

### How does the plugin communicate with external services?
**Answer:** External API communication is managed through `sb-api.php` in the includes directory. It handles requests to the Switchblade API with proper error handling and authentication.

### How is user registration and login handled?
**Answer:** User registration and login are handled through `registration-flow.php` and `login-flow.php` in the core directory. The flow works as follows:
1. Registration: User submits an Elementor form → `registration-flow.php` processes the submission → creates user account → assigns appropriate role from `user-role.php` → logs user in automatically
2. Login: User submits login credentials → `login-flow.php` validates credentials → sets authentication cookie → checks for pending downloads → redirects user appropriately
3. Download after login: If a user clicks download while logged out → download info is stored in session → after login, `login-redirect.js` triggers the download in a popup window

## Core Files and Their Responsibilities

```
shortcuts-hub/
├── ARCHITECTURE.md             # Architecture documentation
├── PROTOCOL.md                 # Protocol documentation
├── ACTIONPLAN.md               # Action plan documentation
├── shortcuts-hub.php           # Main plugin file
├── assets/
│   ├── css/                    # Stylesheets
│   │   ├── admin.css           # Admin styles
│   │   └── frontend.css        # Frontend styles
│   ├── js/                     # JavaScript files
│   │   ├── admin/              # Admin scripts
│   │   │   ├── edit-shortcut.js
│   │   │   └── icon-selector.js
│   │   └── frontend/           # Frontend scripts
│   │       ├── download-button.js
│   │       └── login-redirect.js
│   └── images/                 # Image assets
├── includes/                   # Core functionality
│   ├── class-shortcuts-hub.php # Main plugin class
│   ├── sb-api.php              # Switchblade API integration
│   ├── auth.php                # Authentication handling
│   ├── settings.php            # Plugin settings
│   ├── enqueue-assets.php      # Asset management
│   ├── security.php            # Security functions
│   └── elementor/              # Elementor integration
│       ├── elementor-manager.php
│       ├── dynamic-tags/       # Dynamic tag classes
│       │   ├── icon-tag.php
│       │   └── name-tag.php
│       └── widgets/            # Widget classes
│           ├── download-button-widget.php
│           └── my-account-widget.php
└── core/                       # Core functionality
    ├── class-sb-db-manager.php # Database connection manager
    ├── database.php            # Database functions
    ├── enqueue-core.php        # Core asset loading
    ├── login-flow.php          # Login process handling
    ├── registration-flow.php   # Registration process handling
    ├── sh-debug.php            # Debugging functionality
    └── user-role.php           # User role management
```

### Main Plugin Files
1. **shortcuts-hub.php**
   - Main plugin entry point
   - Defines constants (SHORTCUTS_HUB_FILE, SHORTCUTS_HUB_PATH, SHORTCUTS_HUB_VERSION)
   - Includes the main class file
   - Registers activation and deactivation hooks
   - Initializes the plugin during 'plugins_loaded' hook
   - Registers Elementor integration
   - Sets up plugin settings and admin pages
   - Registers shutdown hook for database connection cleanup

2. **class-shortcuts-hub.php** (in includes directory)
   - Main plugin class using singleton pattern
   - Handles plugin initialization
   - Loads dependencies
   - Registers post types
   - Sets up admin menus
   - Initializes AJAX handlers
   - Manages core WordPress hooks
   - Handles database table creation

### Database Management
3. **class-sb-db-manager.php** (in core directory)
   - Manages database connections to Switchblade server
   - Implements singleton pattern to prevent exceeding max_connections
   - Handles connection pooling and timeout management
   - Provides methods for executing queries with proper connection management
   - Includes error handling and reconnection logic

4. **database.php** (in core directory)
   - Contains functions for logging shortcut downloads
   - Provides functionality to retrieve user download history
   - Includes AJAX handler for logging downloads
   - Interacts with the WordPress database to store download information

### Debug and Logging
5. **sh-debug.php**
   - Provides debugging functionality
   - Implements logging to both console and file
   - Controls debug output based on context
   - Includes AJAX handlers for JavaScript logging
   - Enqueues debug scripts

### API Integration
6. **sb-api.php** (in includes directory)
   - Handles communication with the Switchblade API
   - Manages API requests with proper error handling
   - Handles token refresh for authentication
   - Provides a unified interface for making API calls

7. **auth.php** (in includes directory)
   - Manages authentication with the Switchblade API
   - Handles token retrieval and refresh
   - Implements rate limiting for failed login attempts
   - Stores tokens in WordPress transients

### Settings Management
8. **settings.php** (in includes directory)
   - Manages plugin settings
   - Provides functions to retrieve and update settings
   - Registers settings in WordPress
   - Handles settings sanitization
   - Creates settings admin page

### Asset Management
9. **enqueue-assets.php** (in includes directory)
   - Comprehensive asset management
   - Handles script and style enqueuing
   - Implements proper localization for AJAX functionality
   - Provides page-specific asset loading
   - Includes detailed documentation for asset management patterns

10. **enqueue-core.php** (in core directory)
    - Handles core file inclusion
    - Manages script enqueuing for both admin and frontend
    - Controls conditional loading of scripts
    - Provides data localization for JavaScript

### User Management
11. **registration-flow.php** (in core directory)
    - Handles user registration through Elementor forms
    - Manages user role assignment
    - Processes download tokens during registration
    - Handles redirects after registration

12. **login-flow.php** (in core directory)
    - Manages user login process
    - Handles form validation for login and registration
    - Processes download tokens during login
    - Implements AJAX logout functionality
    - Manages session storage for pending downloads

13. **user-role.php** (in core directory)
    - Defines and creates custom user roles
    - Sets up appropriate capabilities for Shortcuts Hub users

### Security
14. **security.php** (in includes directory)
    - Currently empty, likely intended for security-related functionality

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