/**
 * Debug logging function for Shortcuts Hub
 * @param {string} message - The debug message
 * @param {Object} data - Optional data to log
 * @param {string} source - Optional source info
 */

// Queue for storing debug calls that happen before initialization
window.debugQueue = [];

// Main debug function - initially set up to queue calls until properly initialized
window.sh_debug_log = function(message, data = null, source = null) {
    // Handle source info from caller
    const callerSource = getStackTrace();
    if (callerSource && (!source || typeof source === 'string')) {
        source = callerSource;
    }

    // Queue the call if we're not ready
    if (!window.shortcutsHubData || !window.shortcutsHubData.security || !window.shortcutsHubData.security.debug_log) {
        window.debugQueue = window.debugQueue || [];
        window.debugQueue.push({message, data, source});
        return;
    }

    sendLog(message, data, source);
};

// Helper function to get the current admin page
function getCurrentPage() {
    const urlParams = new URLSearchParams(window.location.search);
    return urlParams.get('page') || 'unknown';
}

function sendLog(message, data = null, source = null) {
    // Validate parameters
    if (typeof message !== 'string') {
        error_log('[DEBUG] sh_debug_log failed: message must be a string, received ' + typeof message);
        return;
    }

    if (data !== null && typeof data === 'object') {
        if (data.hasOwnProperty('message') && data.hasOwnProperty('source') && data.hasOwnProperty('data')) {
            error_log('[DEBUG] sh_debug_log failed: Incorrect format - you are passing a single combined object. Instead use: sh_debug_log(message, data, source)');
            return;
        }
    }

    // Validate shortcutsHubData
    if (!window.shortcutsHubData) {
        error_log('[DEBUG] sh_debug_log failed: shortcutsHubData not found. Make sure the script is properly localized');
        return;
    }

    if (!window.shortcutsHubData.ajaxurl) {
        error_log('[DEBUG] sh_debug_log failed: ajaxurl not found in shortcutsHubData. Check enqueue-assets.php localization');
        return;
    }

    if (!window.shortcutsHubData.security || !window.shortcutsHubData.security.debug_log) {
        error_log('[DEBUG] sh_debug_log failed: debug_log security token not found. Ensure nonce is created in enqueue-assets.php');
        return;
    }

    // Get source info
    let sourceInfo = getStackTrace();
    if (source === 'session-start') {
        sourceInfo = 'sh-debug.js [session-start]';
    }

    // Style console output
    const debugStyle = 'background: #909cfe; color: #252525; font-weight: bold;';
    const sourceTagStyle = 'color: #909cfe; font-weight: bold;';
    const dataTagStyle = 'color: #909cfe; font-weight: bold;';
    const fileNameStyle = 'text-decoration: underline;';

    if (source === 'session-start') {
        const headerStyle = 'background: #909cfe; color: #252525; font-weight: bold;';
        const asterisks = '*'.repeat(116);
        const datetime = new Date().toLocaleString('en-US', { 
            timeZone: 'America/New_York',
            year: 'numeric',
            month: '2-digit',
            day: '2-digit',
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit',
            hour12: true
        });
        console.log('%c' + asterisks, headerStyle);
        console.log('%c' + `[START DEBUG LOG: ${datetime} EST]`.padStart(58 + datetime.length/2).padEnd(116), headerStyle);
        console.log('%c' + asterisks, headerStyle);
        console.log(''); // Empty line after header
    } else {
        // Check if this is an error message or contains error data
        const isError = message.toLowerCase().includes('error') || 
            (data && (
                (data.success === false) ||
                (data.status >= 400) ||
                (data.error) ||
                (data.data && data.data.success === false)
            ));
        
        // Start a collapsible group for this log entry
        console.groupCollapsed('%c[DEBUG] ' + message, debugStyle);
        
        // Log source if present
        if (sourceInfo) {
            console.log('%c[SOURCE] %c' + sourceInfo, sourceTagStyle, fileNameStyle);
        }
        
        if (data) {
            console.groupCollapsed('%c[DATA]', dataTagStyle);
            try {
                const cleanData = JSON.parse(JSON.stringify(data));
                Object.entries(cleanData).forEach(([key, value]) => {
                    if (value && typeof value === 'object') {
                        // For nested objects, iterate through their properties
                        Object.entries(value).forEach(([k, v]) => {
                            console.log('%c' + k + ':%c ' + JSON.stringify(v), 'color: #909cfe; font-weight: bold;', 'color: inherit; font-weight: normal;');
                        });
                    } else {
                        console.log('%c' + key + ':%c ' + value, 'color: #909cfe; font-weight: bold;', 'color: inherit; font-weight: normal;');
                    }
                });
            } catch (e) {
                console.error('Error displaying data:', e);
                console.log('Raw data:', data);
            }
            console.groupEnd();
        }
        console.groupEnd();
    }

    // Prepare data for sending
    let logData;
    try {
        // Parse source info for PHP
        const [fileName, functionName] = sourceInfo.split(' [');
        const sourceData = {
            file: fileName,
            function: functionName ? functionName.slice(0, -1) : '',  // Remove trailing ]
            line: ''  // We're not using line numbers anymore
        };

        // Prepare data object
        const debugData = {
            ...data,  // Include any existing data
            debug: true  // Ensure debug is true
        };
        
        logData = {
            action: 'sh_debug_log',
            security: window.shortcutsHubData.security.debug_log,
            message: message,
            source: JSON.stringify(sourceData),
            data: JSON.stringify(debugData),
            page: getCurrentPage(),
            debug: true
        };

        // Send to PHP for logging
        jQuery.ajax({
            url: window.shortcutsHubData.ajaxurl,
            type: 'POST',
            data: logData,
            success: function(response) {
                if (!response || !response.success) {
                    error_log('[DEBUG] sh_debug_log failed: Server returned error - ' + 
                        (response ? JSON.stringify(response) : 'No response'));
                }
            },
            error: function(xhr, status, error) {
                error_log('[DEBUG] sh_debug_log failed: AJAX error - ' + status + ' - ' + error);
                console.error('AJAX Request:', {
                    url: window.shortcutsHubData.ajaxurl,
                    data: debugData
                });
            }
        });
    } catch (e) {
        error_log('[DEBUG] sh_debug_log failed: Data serialization error - ' + e.message);
        console.error('Failed to serialize data:', {
            source: sourceInfo,
            data: debugData,
            error: e
        });
        return;
    }
}

// Process any queued debug calls
function processDebugQueue() {
    if (window.debugQueue && window.debugQueue.length > 0) {
        console.log(`Processing ${window.debugQueue.length} queued debug calls`);
        window.debugQueue.forEach(item => {
            sendLog(item.message, item.data, item.source);
        });
        window.debugQueue = [];
    }
}

// Helper function to get stack trace
function getStackTrace() {
    const error = new Error();
    if (!error.stack) return '';
    
    // Get the first relevant stack frame (skip Error and sh_debug_log frames)
    const stackFrames = error.stack.split('\n');
    if (stackFrames.length < 3) return '';
    
    const frame = stackFrames[2];
    
    // Parse the stack frame
    // Expected format: "    at functionName (file:///path/to/file.js?ver=1.0.0:lineNumber:column)"
    const match = frame.match(/at\s+(?:(\w+)\s+)?\(?(?:.*\/)?([^\/]+?)(?:\?[^:]*)?:(\d+):/);
    if (!match) return '';
    
    const [, functionName, fileName, lineNumber] = match;
    return fileName + ':' + lineNumber + (functionName ? ' [' + functionName + ']' : '');
}

// Helper function to get script loading state
function getScriptLoadingState() {
    return {
        documentReadyState: document.readyState,
        jQueryLoaded: typeof jQuery !== 'undefined',
        shortcutsHubDataExists: typeof window.shortcutsHubData !== 'undefined',
        shortcutsHubSecurityExists: window.shortcutsHubData?.security ? true : false,
        callingScript: document.currentScript?.src || 'unknown',
        stackTrace: getStackTrace()
    };
}

// Helper function to properly log errors
function logError(message, functionName) {
    const loadingState = getScriptLoadingState();
    const errorContext = {
        message: message,
        source: {
            file: 'sh-debug.js',
            line: functionName,
            function: functionName
        },
        data: {
            loadingState: loadingState,
            url: window.location.href,
            timestamp: new Date().toISOString()
        },
        debug: true
    };

    // Only log to console to prevent recursion
    console.error('Debug Error:', {
        message: message,
        functionName: functionName,
        context: errorContext
    });
}

// Initialize debug when document is ready and shortcutsHubData is available
function initDebug() {

    if (!window.shortcutsHubData) {
        logError('shortcutsHubData not available for initialization', 'initDebug');
        return;
    }

    // Ensure debug is enabled
    window.shortcutsHubData.debug = true;

    // Process any debug calls that happened before initialization
    processDebugQueue();

    // Log initialization
    window.sh_debug_log('Debug initialized', {
        shortcutsHubData: {
            debug: window.shortcutsHubData.debug,
            ajaxurl: window.shortcutsHubData.ajaxurl,
            security: window.shortcutsHubData.security
        }
    }, 'initDebug');
}

// Try to initialize immediately if jQuery and shortcutsHubData are available
if (window.jQuery && window.shortcutsHubData) {
    initDebug();
} else {
    // Otherwise wait for document ready
    jQuery(document).ready(function() {
        tryInit();
    });
}

// Helper function to log errors to PHP error log
function error_log(message) {
    jQuery.ajax({
        url: window.ajaxurl || shortcutsHubData.ajaxurl || '/wp-admin/admin-ajax.php',
        type: 'POST',
        data: {
            action: 'sh_error_log',
            message: message
        }
    });
}

// Log session start when page loads
window.sh_debug_log('Debug session started', null, 'session-start');
