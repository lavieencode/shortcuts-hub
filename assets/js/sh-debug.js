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
    // Handle the case where a single object is passed with message, data, and source properties
    if (typeof message === 'string' && data !== null && typeof data === 'object') {
        // Check if this is the combined object format
        if (data.hasOwnProperty('message') || data.hasOwnProperty('source') || data.hasOwnProperty('data')) {
            // Extract the components from the combined object format
            const extractedMessage = data.hasOwnProperty('message') ? data.message : message;
            const extractedSource = data.hasOwnProperty('source') ? data.source : source;
            
            // Determine the data to use
            let extractedData;
            if (data.hasOwnProperty('data') && data.data !== null) {
                // Use the nested data property if it exists
                extractedData = data.data;
            } else {
                // Otherwise use the original data object without the special properties
                extractedData = { ...data };
                delete extractedData.source;
                delete extractedData.message;
                delete extractedData.data;
                delete extractedData.debug;
            }
            
            // Check for debug flag
            const debug = data.hasOwnProperty('debug') ? data.debug : true;
            
            // Only proceed if debug is true
            if (debug) {
                sendLog(extractedMessage, extractedData, extractedSource);
                return;
            }
        }
    }
    
    // Validate log format for standard usage
    const formatError = validateLogFormat(message, data, source);
    if (formatError && window.shortcutsHubData?.debug) {
        sendLog('Log Format Error', {
            error: formatError,
            received: {
                message,
                data,
                source
            },
            expected: {
                message: 'string',
                data: 'object with arbitrary data',
                source: {
                    file: 'string',
                    line: 'string',
                    function: 'string'
                }
            }
        }, {
            file: 'sh-debug.js',
            line: 'sh_debug_log',
            function: 'sh_debug_log'
        });
        return;
    }

    // Send the actual log if format is valid
    sendLog(message, data, source);
};

// Helper function to validate log format
function validateLogFormat(message, data, source) {
    let error = null;
    
    if (typeof message !== 'string') {
        error = 'Message must be a string';
    }
    if (data !== null && typeof data !== 'object') {
        error = 'Data must be null or an object';
    }
    if (source !== null && (typeof source !== 'object' || !source.file || !source.line || !source.function)) {
        error = 'Source must contain file, line, and function properties';
    }
    
    if (error) {
        // Use error_log instead of console.error to maintain consistent logging style
        error_log('[DEBUG] Debug Log Failed: ' + error);
        return error;
    }
    
    return null;
}

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

    // Check if debug is explicitly set to false in the data
    if (data && typeof data === 'object' && data.hasOwnProperty('debug') && data.debug === false) {
        // Skip logging if debug is false
        return;
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

    // Style definitions
    const debugStyle = 'background: #909cfe; color: #252525; font-weight: bold;';
    const sourceStyle = 'background: #909cfe; color: #252525; font-weight: bold;';
    const keyStyle = 'color: #909cfe; font-weight: bold;';

    // Format source info
    let sourceInfo = '';
    if (source) {
        const sourceTag = '[SOURCE]';
        const restOfSource = ` ${source.file}${source.line ? ':' + source.line : ''} [${source.function}]`;
        sourceInfo = `%c${sourceTag}%c${restOfSource}`;
    }

    // Format console output
    console.groupCollapsed(`%c[DEBUG] ${message}`, debugStyle);
    if (sourceInfo) {
        console.log(sourceInfo, sourceStyle, '');
    }
    if (data) {
        console.log('%c[DATA]', debugStyle);
        // Log each key-value pair with styled keys
        Object.entries(data).forEach(([key, value]) => {
            console.log(`%c${key}:`, keyStyle, value);
        });
    }
    console.groupEnd();

    // Send to PHP logger
    jQuery.ajax({
        url: window.shortcutsHubData.ajaxurl,
        type: 'POST',
        data: {
            action: 'sh_debug_log',
            security: window.shortcutsHubData.security.debug_log,
            message: message,
            data: data ? JSON.stringify(data) : null,
            source: source ? JSON.stringify(source) : null
        }
    }).done(function(response) {
        // Success - no need to log anything
    }).fail(function(jqXHR, textStatus, errorThrown) {
        // Log AJAX errors separately to avoid recursion
        const errorMessage = '[DEBUG] Failed to send log to server: ' + errorThrown;
        error_log(errorMessage);
    });
}

// Process any queued debug calls
function processDebugQueue() {
    if (!window.debugQueue || !window.debugQueue.length) {
        // No need to log when there's nothing to process
        return;
    }

    // Use sendLog for consistent logging style
    sendLog('Processing queued logs', { count: window.debugQueue.length }, {
        file: 'sh-debug.js',
        line: 'processDebugQueue',
        function: 'processDebugQueue'
    });
    
    while (window.debugQueue.length > 0) {
        const { message, data, source } = window.debugQueue.shift();
        sendLog(message, data, source);
    }
}

// Helper function to get stack trace
function getStackTrace() {
    const stackLines = new Error().stack.split('\n');
    
    // Find the caller's line (skip Error, sh_debug_log frames)
    let relevantLine = '';
    for (let i = 0; i < stackLines.length; i++) {
        const line = stackLines[i].trim();
        // Skip internal calls and jQuery internals
        if (!line.includes('sh-debug.js') && 
            !line.includes('Error') && 
            !line.includes('getStackTrace') &&
            !line.includes('jquery.min.js')) {
            relevantLine = line;
            break;
        }
    }
    
    // Parse the line information
    // Format: "at function (file:line:col)" or "at file:line:col"
    const match = relevantLine.match(/at (?:([^(]+) \()?([^:]+):(\d+):(\d+)\)?/);
    if (!match) {
        // Fallback for jQuery ready handler
        if (relevantLine.includes('jQuery.ready')) {
            return {
                file: 'versions-handlers.js',
                line: '1',  // First line of the ready handler
                function: 'document.ready'
            };
        }
        return null;
    }
    
    const [, fnName, filePath, lineNumber] = match;
    const fileName = filePath.split('/').pop(); // Get just the filename
    const functionName = fnName ? fnName.trim() : 'anonymous';
    
    return {
        file: fileName,
        line: lineNumber,
        function: functionName
    };
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

    // Use error_log instead of console.error for consistent logging style
    error_log('[DEBUG] Debug Error: ' + message + ' in ' + functionName);

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
    }, {
        file: 'sh-debug.js',
        line: '262',
        function: 'initDebug'
    });
}

// Initialize debug when document is ready and shortcutsHubData is available
function initDebug() {
    // Enable debug mode
    window.shortcutsHubData.debug = true;

    // Process any queued debug calls
    processDebugQueue();

    // Log initialization
    window.sh_debug_log('Debug initialized', {
        shortcutsHubData: {
            debug: window.shortcutsHubData.debug,
            ajaxurl: window.shortcutsHubData.ajaxurl,
            security: window.shortcutsHubData.security
        }
    }, {
        file: 'sh-debug.js',
        line: '262',
        function: 'initDebug'
    });
}

// Try to initialize immediately if jQuery and shortcutsHubData are available
jQuery(function() {
    if (window.shortcutsHubData) {
        initDebug();
    }
});

// Log session start when page loads
window.sh_debug_log('Debug session started', null, {
    file: 'sh-debug.js',
    line: '293',
    function: 'global'
});

// Helper function to log errors to PHP error log
function error_log(message) {
    if (!window.shortcutsHubData || !window.shortcutsHubData.security || !window.shortcutsHubData.security.debug_log) {
        // Can't use error_log recursively, so just fail silently
        return;
    }

    jQuery.post(window.shortcutsHubData.ajaxurl, {
        action: 'sh_error_log',
        security: window.shortcutsHubData.security.debug_log,
        message: message
    });
}
