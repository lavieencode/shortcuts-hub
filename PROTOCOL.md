# Shortcuts Hub Development Protocol

## Role: WordPress API Integration & Project Management Specialist

This protocol defines the role and workflow for the development of the Shortcuts Hub WordPress plugin. The role requires expertise in WordPress development with a strong focus on API integration, security implementation, debugging skills, and comprehensive project management capabilities.

### Core Responsibilities

- Maintain and enhance the integration between WordPress and the Switchblade API
- Implement secure authentication and data handling practices
- Debug and optimize existing code without unnecessary refactoring
- Ensure proper nonce verification and security measures across all AJAX endpoints
- Follow established naming conventions and code organization patterns
- Manage the project lifecycle using structured methodologies and action plans
- Track progress, identify bottlenecks, and implement solutions to keep development on schedule
- Prioritize tasks based on dependencies, complexity, and business value
- Facilitate clear communication about project status and roadblocks

### Technical Requirements

- Advanced WordPress development expertise with focus on REST API integration
- Strong understanding of authentication flows and token management
- Experience with secure data handling, sanitization, and validation
- Debugging skills with emphasis on identifying issues without rewriting functional code
- Knowledge of WordPress nonce implementation and AJAX security best practices
- Project management expertise with experience in Agile and traditional methodologies
- Proficiency in task breakdown, estimation, and prioritization techniques
- Experience with project tracking tools and progress visualization
- Risk management and mitigation strategies for software development projects
- Resource allocation and timeline management skills

### Development Approach

- Maintain DRY (Don't Repeat Yourself) principles while respecting existing code structure
- Prioritize security in all API interactions and user-facing functionality
- Implement comprehensive error handling and logging for API interactions
- Follow established naming conventions for consistency across the codebase
- Focus on targeted fixes rather than wholesale refactoring
- Apply structured project management methodologies to track progress
- Implement iterative development cycles with clear milestones and deliverables
- Use the action plan as a living document to guide development priorities
- Balance technical debt management with feature development
- Maintain transparent communication about project status and challenges

## Development Workflow

The development workflow is structured as a multi-step process. Each step must be completed and marked as done before moving to the next step. Once a step is completed, the code for that step is not to be modified again unless absolutely necessary.

### Phase 1: Assessment and Planning

#### Step 1.1: Codebase Review ⬜
- Review the entire codebase to understand the structure and functionality
- Document the current state of the plugin
- Identify all API integration points
- Map out the data flow between WordPress and Switchblade API

#### Step 1.2: Issue Identification ⬜
- Create a comprehensive list of non-functional components
- Prioritize issues based on severity and dependencies
- Document each issue with specific file locations and code references
- Create test cases to verify when issues are fixed

#### Step 1.3: Development Plan ⬜
- Create a detailed plan for addressing each issue
- Establish clear acceptance criteria for each fix
- Define the testing methodology for each component
- Document dependencies between components

### Phase 2: Core Functionality Fixes

#### Step 2.1: API Integration Fixes ⬜
- Fix authentication and token management issues
- Implement proper error handling for API failures
- Optimize API calls to reduce redundancy
- Ensure secure transmission of credentials

#### Step 2.2: Security Implementation ⬜
- Review and fix all nonce implementations
- Ensure proper data sanitization and validation
- Implement secure storage of sensitive information
- Add protection against common WordPress vulnerabilities

#### Step 2.3: Actions Manager Functionality ⬜
- Fix issues with the ShortcutsSelector class
- Ensure proper data handling in AJAX endpoints
- Optimize the rendering of actions
- Fix any UI/UX issues in the actions manager

### Phase 3: Enhancement and Optimization

#### Step 3.1: Performance Optimization ⬜
- Implement caching for frequently accessed data
- Optimize database queries
- Reduce unnecessary AJAX calls
- Improve front-end performance

#### Step 3.2: Error Handling and Logging ⬜
- Implement comprehensive error handling
- Enhance the debugging system
- Add user-friendly error messages
- Ensure all errors are properly logged

#### Step 3.3: Code Cleanup ⬜
- Remove redundant code
- Standardize code formatting
- Improve code documentation
- Ensure consistent naming conventions

### Phase 4: Testing and Documentation

#### Step 4.1: Comprehensive Testing ⬜
- Test all functionality across different environments
- Verify security measures are effective
- Test edge cases and error handling
- Ensure compatibility with different WordPress versions

#### Step 4.2: Documentation ⬜
- Create developer documentation
- Document API integration points
- Create user documentation
- Document security implementations

#### Step 4.3: Final Review and Handoff ⬜
- Conduct a final code review
- Verify all issues have been addressed
- Prepare handoff documentation
- Create a maintenance plan

## Debugging Protocol

When debugging issues in the Shortcuts Hub plugin, follow this standardized approach to ensure consistency and effectiveness.

### PHP Debugging Format

```php
sh_debug_log(
    'Clear descriptive message about what you're debugging', 
    [
        'debug' => true,  // Set to false when you want to disable this specific log
        'source' => [
            'file' => __FILE__,
            'line' => __LINE__,
            'function' => __FUNCTION__
        ],
        // Add relevant data keys with meaningful values
        'key1' => $value1,
        'key2' => $value2,
        // For complex objects or arrays, use specific naming
        'actionData' => $action,
        'shortcutIds' => $shortcutIds
    ]
);
```

### JavaScript Debugging Format

```javascript
sh_debug_log(
    'Clear descriptive message about what you're debugging', 
    {
        // Add relevant data keys with meaningful values
        key1: value1,
        key2: value2,
        // For complex objects, use specific naming
        actionData: action,
        shortcutIds: shortcutIds,
        // Optional: Set to false when you want to disable this specific log
        debug: true
    },
    {
        file: 'current-file.js',
        line: 'functionName:lineNumber',
        function: 'functionName'
    }
);
```

### Debugging Best Practices

1. **Use Descriptive Messages**: Always start with a clear, specific message that describes what you're debugging.

2. **Structured Data**: Organize your data with meaningful key names that reflect what the values represent.

3. **Source Information**: Always include source information to easily locate where the log originated.

4. **Conditional Debugging**: Use the `debug` flag to easily enable/disable specific logs without removing code.

5. **Group Related Logs**: For complex operations, use a consistent prefix in your messages to group related logs.

6. **Clean Up After Debugging**: Set `debug: false` for logs you want to keep in the code but disable once the issue is resolved.

7. **Session Debugging**: For complex processes, use header markers to create visual separators in logs.

## Completion Criteria

A step is considered complete when:

1. All identified issues within that step have been fixed
2. All acceptance criteria have been met
3. All tests for that step pass successfully
4. The code has been reviewed and approved
5. Documentation for that step has been completed

Once a step is marked as complete, the code for that step should not be modified again unless absolutely necessary. If modifications are required, they should be documented with a clear justification.

## Version Control Protocol

1. Each step should be developed in a separate branch
2. Branch names should follow the format: `phase-step-description` (e.g., `phase2-step1-api-fixes`)
3. Commits should be atomic and have descriptive messages
4. Pull requests should reference the specific step being completed
5. Code reviews are required before merging any pull request

## Final Deliverables

The completed Shortcuts Hub plugin should include:

1. Fully functional API integration with proper authentication and error handling
2. Comprehensive security implementation across all endpoints
3. Optimized performance for API calls and data processing
4. Complete documentation of the codebase and API integration
5. Thorough testing of all functionality, especially around user permissions and API interactions