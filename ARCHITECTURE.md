# Shortcuts Hub Architecture

## Table of Contents
1. [Overview](#overview)
2. [Core Components](#core-components)
   - [Download Button](#download-button)
   - [Login Flow](#login-flow)
   - [Database Management](#database-management)
3. [Plugin Structure](#plugin-structure)
4. [Key Features](#key-features)
5. [Integration Points](#integration-points)
6. [Security Considerations](#security-considerations)

## Overview
Shortcuts Hub is a WordPress plugin that integrates with the Switchblade API to manage and distribute Apple Shortcuts. It provides a seamless experience for users to browse, download, and manage shortcuts while tracking usage and maintaining user authentication.

## Core Components

### Download Button (`core/download-button.php`)
An Elementor widget that manages the shortcut download process.

Key features:
- Displays a customizable download button on shortcut post pages
- Handles both logged-in and non-logged-in user scenarios
- Integrates with the Switchblade API to fetch latest shortcut versions
- Stores download URLs and post information in transients for post-login redirection
- Supports flexible positioning (left, center, right, stretch)
- Comprehensive styling options through Elementor controls
- Tracks download metrics and user interactions

Button Behavior:
1. When clicked, it initiates the download of the shortcut
2. The page redirects to the download URL
3. After download initiation, it automatically redirects back to the original shortcut page
4. The download is logged in both the database and error log for tracking

### Login Flow (`core/login-flow.php`)
Manages user authentication through Elementor Pro forms.

Key features:
- Custom validation for username/email and password
- Secure token management
- Post-login redirection handling
- Integration with Switchblade authentication
- Session management and security checks

### Database Management (`core/database.php`)
Handles all database operations and schema management.

Tables:
1. `shortcutshub_downloads`:
   - Tracks all shortcut downloads
   - Fields:
     - `id`: Auto-incrementing primary key
     - `user_id`: WordPress user ID
     - `shortcut_id`: Switchblade shortcut ID
     - `post_id`: WordPress post ID
     - `post_url`: Full URL of the shortcut post
     - `shortcut_name`: Name of the shortcut
     - `version`: Version number
     - `version_notes`: Release notes
     - `minimum_ios`: Minimum required iOS version
     - `minimum_mac`: Minimum required macOS version
     - `download_url`: Direct download URL
     - `ip_address`: User's IP address
     - `is_required`: Boolean flag for required versions
     - `created_at`: Timestamp of download
   - Indexes on user_id, shortcut_id, and post_id for efficient queries

2. `shortcutshub_users`:
   - Stores user-specific data and preferences
   - Manages user authentication tokens

## Plugin Structure
- `/core/`: Core functionality modules
- `/includes/`: Supporting functions and utilities
- `/assets/`: JavaScript, CSS, and media files
- `/templates/`: Template files for various views

## Key Features
1. **Plugin Lifecycle Management**:
   - Activation Hook:
     - Sets up database tables
     - Registers custom post types
     - Schedules permalink structure refresh
   - Deactivation Hook:
     - Cleans up rewrite rules
     - Maintains data integrity
   - Permalink Management:
     - Automatic flushing on activation/deactivation
     - Delayed flush to prevent conflicts
     - Proper handling during plugin resets

2. **Shortcut Management**:
   - Custom post type for shortcuts
   - Version control integration
   - Metadata management

3. **Download Tracking**:
   - Comprehensive logging of all downloads
   - User activity tracking
   - Version requirement tracking
   - IP address logging for security

4. **User Management**:
   - Secure authentication flow
   - Token-based API access
   - Session handling
   - Role-based permissions

## Integration Points
1. **Switchblade API**:
   - Version fetching
   - User authentication
   - Shortcut metadata
   - Download URL generation

2. **WordPress**:
   - Custom post types
   - User management
   - Database integration
   - Admin interface

3. **Elementor**:
   - Custom widgets
   - Form handling
   - Styling integration
   - Dynamic content

## Security Considerations
1. **Data Protection**:
   - Input sanitization
   - Output escaping
   - SQL preparation
   - XSS prevention

2. **Authentication**:
   - Secure token storage
   - Session management
   - API key protection
   - Rate limiting

3. **Download Protection**:
   - URL validation
   - User verification
   - Download logging
   - IP tracking

## Authentication and Download Flows

### Login Flow (Detailed Steps)

1. **Initial Download Button Click**
   - User clicks download button on a shortcut post
   - Button checks if user is logged in
   - If not logged in, redirects to `https://debotchery.ai/shortcuts-gallery/login`
   - Passes shortcut ID and post URL as data attributes

2. **Login Form Submission**
   ```
   Form submission → AJAX request → Server validation → Authentication → Response
   ```
   
   a. **Form Submission**
   - User enters username/email and password
   - Form is submitted via AJAX using Elementor Pro's form handler
   - Form data includes original shortcut context (IDs and URLs)

   b. **Server-Side Processing**
   - Validates username/email and password fields
   - Authenticates user using `wp_authenticate()`
   - Sets authentication cookie using `wp_set_auth_cookie()`
   - Retrieves stored download URL from transients
   - Prepares response with download and redirect URLs

   c. **Response Handling**
   - Success response includes:
     - `download_url`: Latest version URL from Switchblade
     - `redirect_url`: Original shortcut post URL
   - JavaScript handles:
     - Opening download URL in new tab
     - Redirecting user back to shortcut post

### Registration Flow (Detailed Steps)

1. **Initial Form Access**
   - User arrives from download button redirect
   - Registration form loads with hidden fields for shortcut context

2. **Form Submission and Validation**
   ```
   Form submission → Field validation → User creation → Login → Response
   ```

   a. **Field Validation**
   - Username:
     - Must be at least 4 characters
     - Must not already exist
     - Sanitized using `sanitize_user()`
   - Email:
     - Must be valid email format
     - Must not already exist
     - Sanitized using `sanitize_email()`
   - Password:
     - Must be at least 8 characters
     - Must match confirmation field

   b. **User Creation**
   - Creates user using `wp_create_user()`
   - Sets role to 'shortcuts_user'
   - Automatically logs in new user

   c. **Response Handling**
   - Similar to login flow
   - Includes download and redirect URLs
   - JavaScript handles redirection and download

## Database Architecture

### Downloads Tracking Table

The plugin uses a custom table `{prefix}shortcutshub_downloads` to track all shortcut downloads by registered users. This table stores comprehensive information about each download event:

#### Table Structure
- `id` (bigint): Auto-incrementing primary key
- `user_id` (bigint): WordPress user ID of the downloader
- `shortcut_id` (varchar): Unique identifier of the shortcut from the Switchblade API
- `post_id` (bigint): WordPress post ID of the shortcut
- `post_url` (text): Full URL of the shortcut's WordPress post
- `shortcut_name` (varchar): Name of the shortcut
- `version` (varchar): Version number of the downloaded shortcut
- `version_notes` (text): Release notes for this version
- `minimum_ios` (varchar): Minimum required iOS version
- `minimum_mac` (varchar): Minimum required macOS version
- `download_url` (text): Direct download URL for the shortcut
- `ip_address` (varchar): IP address of the downloader
- `download_date` (datetime): Timestamp of the download

#### Indexes
- Primary Key: `id`
- Foreign Keys:
  - `user_id`: Links to WordPress users table
  - `shortcut_id`: Links to Switchblade API shortcut ID
  - `post_id`: Links to WordPress posts table
- Additional Indexes:
  - `download_date`: For efficient date-based queries
  - `ip_address`: For tracking download patterns

#### Functionality
1. **Download Logging**
   - Automatically logs every shortcut download
   - Captures comprehensive metadata about the download event
   - Includes version information and system requirements
   - Records user and access information for analytics

2. **Download History**
   - Provides detailed download history per user
   - Enables tracking of download patterns and popular shortcuts
   - Supports future features like download limits and analytics

3. **Error Handling**
   - Robust error logging for debugging
   - Validates user authentication
   - Sanitizes all input data
   - Handles edge cases gracefully

### Implementation Details
The database functionality is implemented in `core/database.php` and includes:
- Table creation and updates via WordPress' `dbDelta`
- Download logging with comprehensive error handling
- User download history retrieval
- Debug logging for troubleshooting

The table is created or updated during plugin activation, and the schema version is tracked via WordPress options to handle future updates.

## Elementor Integration Notes

1. **Form Handling**
   - Uses `elementor_pro/forms/process` action hook
   - Requires specific form names:
     - 'Shortcuts Gallery Login'
     - 'Shortcuts Gallery Registration'
   - Form fields must match expected names exactly

2. **Common Issues**
   - Form field access requires direct array keys (not nested)
   - Field values are accessed via ['value'] key
   - Required fields must be set in Elementor form settings
   - AJAX responses must use Elementor's handler methods

3. **JavaScript Integration**
   - Uses custom event 'elementorFormSubmissionSuccess'
   - Handles both form types in single listener
   - Manages URL opening and redirection timing

## Security Considerations

1. **Data Sanitization**
   - All user input is sanitized using WordPress functions
   - Passwords are never logged or exposed
   - Nonces used for AJAX requests

2. **Transient Data**
   - Download URLs stored temporarily
   - Cleaned up after successful login/registration
   - Tied to user session or ID

3. **URL Handling**
   - Download URLs validated before storage
   - Redirect URLs limited to site pages
   - Login page hardcoded for security

## API Integration

1. **Switchblade Server**
   - Handles version management
   - Provides download URLs
   - Requires proper authentication

2. **WordPress Integration**
   - Uses `wp_remote_post()` for API calls
   - Handles errors gracefully
   - Logs important responses

## Known Limitations

1. **Form Requirements**
   - Must use Elementor Pro forms
   - Field names must match exactly
   - Custom styling limited to Elementor options

2. **Session Handling**
   - Transients used for data storage
   - 12-hour expiration on stored data
   - May require cleanup in some cases

3. **Browser Behavior**
   - Popup blockers may affect download
   - Requires JavaScript enabled
   - Multiple tabs handled via window.open()
