# Debugging Documentation: My Account Widget JavaScript Loading

## Context and Problem Statement

We are extending Elementor Pro's My_Account widget (`ElementorPro\Modules\Woocommerce\Widgets\My_Account`) to add custom tab functionality. The core issue is that our custom JavaScript file (`my-account.js`) is not loading in the Elementor editor environment, though it works correctly on the frontend.

## Technical Background

### Widget Architecture
Our widget (`ShortcutsHub\Elementor\Widgets\My_Account_Widget`) extends Elementor Pro's My_Account widget, which itself extends Elementor's Widget_Base. This creates an inheritance chain:

```
Elementor\Widget_Base
└── ElementorPro\Modules\Woocommerce\Widgets\My_Account
    └── ShortcutsHub\Elementor\Widgets\My_Account_Widget
```

### Required JavaScript Functionality
The JavaScript file (`my-account.js`) needs to:
1. Initialize when the widget loads in the editor
2. Handle tab switching interactions
3. Maintain tab state in the editor preview
4. Work alongside Elementor's frontend scripts

## Attempted Solutions and Their Outcomes

### 1. Elementor's Standard Widget Script Loading Method
**Source of Approach**: Elementor's official documentation and Widget_Base implementation

**Implementation**:
```php
public function get_script_depends() {
    return ['shortcuts-hub-my-account'];
}
```

**Expected Behavior**:
- Elementor should recognize the script dependency
- Load the script automatically in both editor and frontend contexts
- Handle script registration and enqueuing through Elementor's system

**Actual Outcome**:
- Script does not load in the editor
- No JavaScript errors in console
- Widget preview shows static content instead of interactive tabs

**Analysis**:
- This method works for widgets that directly extend Widget_Base
- The parent My_Account widget might be overriding this method
- Elementor Pro widgets potentially handle script loading differently
- No documentation found about script loading in extended Pro widgets

### 2. Direct WordPress Script Registration in Widget
**Source of Approach**: WordPress script handling best practices

**Implementation**:
```php
public function enqueue_widget_assets() {
    wp_register_script(
        'shortcuts-hub-my-account',
        plugins_url('/assets/js/widgets/my-account.js', dirname(dirname(__FILE__))),
        ['jquery', 'elementor-frontend'],
        filemtime(plugin_dir_path(dirname(dirname(__FILE__))) . 'assets/js/widgets/my-account.js'),
        true
    );

    if (\Elementor\Plugin::$instance->editor->is_edit_mode() || is_account_page()) {
        wp_enqueue_script('shortcuts-hub-my-account');
    }
}

// In constructor:
add_action('wp_enqueue_scripts', [$this, 'enqueue_widget_assets']);
```

**Expected Behavior**:
- Script should register and enqueue when WordPress loads scripts
- Editor detection should trigger script loading in editor context

**Actual Outcome**:
- Script registers successfully but doesn't load in editor
- Frontend loading works correctly
- No JavaScript errors or console messages

**Analysis**:
- WordPress script loading hooks might fire too early/late for Elementor editor
- Elementor editor might use different script loading mechanism
- wp_enqueue_scripts hook might not be appropriate for editor context

### 3. Centralized Script Management in Elementor Manager
**Source of Approach**: Observed pattern in other Elementor plugins

**Implementation**:
```php
// In Elementor_Manager class
public function register_frontend_scripts() {
    wp_register_script(
        'shortcuts-hub-my-account',
        plugins_url('/assets/js/widgets/my-account.js', dirname(dirname(__FILE__))),
        ['jquery', 'elementor-frontend'],
        filemtime(plugin_dir_path(dirname(dirname(__FILE__))) . 'assets/js/widgets/my-account.js'),
        true
    );

    if (\Elementor\Plugin::$instance->editor->is_edit_mode()) {
        wp_enqueue_script('shortcuts-hub-my-account');
    }
}

add_action('elementor/frontend/after_register_scripts', [$this, 'register_frontend_scripts']);
```

**Expected Behavior**:
- Centralized script registration should ensure consistent loading
- Editor detection should handle editor-specific loading
- Frontend scripts should load normally

**Actual Outcome**:
- Script registration works but editor loading fails
- Frontend continues to work correctly
- No error messages or debugging output

**Analysis**:
- The timing of 'elementor/frontend/after_register_scripts' might be wrong for editor
- Editor might need different script registration approach
- Elementor Pro widgets might bypass this registration system

### 4. Editor-Specific Script Loading
**Source of Approach**: Elementor editor documentation

**Implementation**:
```php
add_action('elementor/editor/after_enqueue_scripts', [$this, 'enqueue_widget_assets']);
```

**Expected Behavior**:
- Hook should fire specifically in editor context
- Script should load after Elementor's editor scripts
- Widget should initialize with JavaScript functionality

**Actual Outcome**:
- Hook appears to fire but script doesn't load
- No JavaScript errors or console output
- Widget remains non-interactive in editor

**Analysis**:
- Hook timing might be correct but something else prevents script loading
- Possible conflict with parent widget's script handling
- Editor environment might need different script initialization

## JavaScript Loading in Elementor Editor

### Issue: My Account Widget JavaScript Not Loading in Editor
The JavaScript file for the My Account widget (`my-account.js`) wasn't loading properly in the Elementor editor environment.

### Solution
Fixed by directly enqueueing the script in the widget's constructor using an anonymous function on the `elementor/editor/after_enqueue_scripts` hook:

```php
add_action('elementor/editor/after_enqueue_scripts', function() {
    wp_enqueue_script(
        'shortcuts-hub-my-account',
        plugins_url('/assets/js/widgets/my-account.js', dirname(dirname(dirname(__FILE__)))),
        ['jquery'],
        filemtime(plugin_dir_path(dirname(dirname(dirname(__FILE__)))) . 'assets/js/widgets/my-account.js'),
        true
    );
});
```

### Key Points
1. **Hook Timing**: Using `elementor/editor/after_enqueue_scripts` ensures the script loads at the right time in the editor
2. **Path Resolution**: Using `dirname(dirname(dirname(__FILE__)))` to correctly navigate up to the plugin root directory
3. **Version Control**: Using `filemtime()` for automatic version numbers based on file modification time
4. **Direct Enqueueing**: Enqueuing directly in the constructor rather than through separate methods
5. **Scope**: Anonymous function ensures proper scope access to `__FILE__` constant

### Related Changes
- Removed script from `get_script_depends()`
- Removed script from `enqueue_widget_assets()`
- Removed `enqueue_editor_scripts()` method as it's no longer needed

This approach ensures the JavaScript loads consistently in both the editor and frontend environments.

## Current State

### Working Components
1. Script Registration:
   - Successfully registers in WordPress
   - Correct file path and dependencies
   - Proper version handling with filemtime

2. Frontend Functionality:
   - Scripts load correctly on frontend
   - Tab switching works as expected
   - No console errors or warnings

3. Widget Extension:
   - Successfully extends Pro widget
   - Renders correct HTML structure
   - Maintains parent widget functionality

### Non-Working Components
1. Editor Script Loading:
   - No script loading in editor context
   - Static preview instead of interactive
   - No error messages to debug

2. Script Dependencies:
   - Unclear if all required dependencies load in editor
   - Possible timing issues with Elementor frontend scripts
   - Potential conflicts with parent widget scripts

## Comparison with Working Widgets

### Download Log Widget (Working Example)
1. Base Class:
   - Extends Widget_Base directly
   - No intermediate parent widget
   - Standard script loading pattern works

2. Script Loading:
   - Uses get_script_depends()
   - No editor-specific handling needed
   - Simple dependency chain

### My Account Widget (Our Implementation)
1. Base Class:
   - Extends Pro widget
   - Complex inheritance chain
   - Potential script loading conflicts

2. Script Loading:
   - Multiple attempted methods
   - Editor context issues
   - Unclear dependency requirements

## Next Investigation Steps

1. Parent Widget Analysis
   - Examine Elementor Pro's My_Account widget source
   - Identify script loading mechanisms
   - Look for overrideable methods

2. Editor Environment
   - Add extensive console logging
   - Track script loading sequence
   - Monitor widget initialization

3. Alternative Approaches
   - Research other Pro widget extensions
   - Consider editor-specific script injection
   - Investigate Elementor Pro patterns

## Questions for Further Research

1. How does Elementor Pro handle script loading for its widgets?
2. Are there specific hooks or methods for Pro widget extensions?
3. Could the parent widget be preventing our scripts from loading?
4. Is there a documented pattern for extending Pro widgets with custom JS?

## Related Files

1. `/includes/elementor/widgets/my-account-widget.php`
   - Main widget class
   - Script loading attempts
   - HTML rendering

2. `/includes/elementor/classes/class-elementor-manager.php`
   - Script registration
   - Hook management
   - Widget registration

3. `/assets/js/widgets/my-account.js`
   - Tab functionality
   - Editor interactions
   - Widget initialization

## References

1. Elementor Documentation:
   - Widget Base: https://developers.elementor.com/docs/widgets/widget-base/
   - Editor Scripts: https://developers.elementor.com/docs/scripts-styles/

2. WordPress Documentation:
   - Script Registration: https://developer.wordpress.org/reference/functions/wp_register_script/
   - Script Enqueuing: https://developer.wordpress.org/reference/functions/wp_enqueue_script/

## Maintainer Notes

- Document any successful patterns discovered
- Track all attempted solutions
- Note any Elementor Pro specific behaviors
- Keep error messages and debugging output
