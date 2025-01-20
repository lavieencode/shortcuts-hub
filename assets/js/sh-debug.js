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
    // Get caller info
    let callerSource = {
        file: 'sh-debug.js',
        line: 'unknown',
        function: 'unknown'
    };
    
    try {
        const stack = new Error().stack;
        if (stack) {
            const lines = stack.split('\n');
            if (lines.length > 2) {
                const caller = lines[2]; // 0 is Error, 1 is sh_debug_log, 2 is caller
                // Try different stack trace formats
                let match = caller.match(/at (\w+) \((.*):(\d+):(\d+)\)/);
                if (!match) {
                    match = caller.match(/at (.*):(\d+):(\d+)/);
                }
                if (match) {
                    callerSource = {
                        file: match[2] ? match[2].split('/').pop() : 'sh-debug.js',
                        line: match[3] || 'unknown',
                        function: match[1] || 'unknown'
                    };
                }
            }
        }
    } catch (e) {
        console.warn('Error getting caller info:', e);
    }

    // Use caller info if no source is provided
    if (!source) {
        source = callerSource;
    }

    // Queue the call if we're not ready
    if (!window.shortcutsHubData || !window.shortcutsHubData.security || !window.shortcutsHubData.security.debug_log) {
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
    try {
        console.log('Debug call trace:', {
            message: message,
            data: data,
            source: source,
            stack: new Error().stack
        });
    } catch (e) {
        logError('Failed to log debug call trace', 'sendLog');
    }

    // Validate shortcutsHubData
    if (!window.shortcutsHubData) {
        logError('sh_debug_log failed: shortcutsHubData not found', 'sendLog');
        return;
    }

    if (!window.shortcutsHubData.ajaxurl) {
        logError('sh_debug_log failed: ajaxurl not found in shortcutsHubData', 'sendLog');
        return;
    }

    if (!window.shortcutsHubData.security || !window.shortcutsHubData.security.debug_log) {
        logError('sh_debug_log failed: debug_log security token not found', 'sendLog');
        return;
    }

    // Process source information
    if (source instanceof Error) {
        const stackTrace = source.stack;
        const stackLines = stackTrace ? stackTrace.split('\n') : [];
        const sourceLine = stackLines.find(line => 
            line.includes('/plugins/shortcuts-hub/') && 
            !line.includes('sh-debug.js')
        );
        
        if (sourceLine) {
            const match = sourceLine.match(/\((.*?)\)/);
            let sourcePath = match ? match[1] : sourceLine.trim();
            
            // Clean up the source path
            sourcePath = sourcePath.replace(/https?:\/\/[^\/]+\//, '/');  // Remove domain
            sourcePath = sourcePath.replace(/\?.*?:/, ':');  // Remove query params
            const fileMatch = sourcePath.match(/[^\/]+\.[^\/]+:\d+:\d+$/);
            source = fileMatch ? fileMatch[0] : sourcePath;
        } else {
            source = null;
        }
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
        if (source) {
            console.log('%c[SOURCE] %c' + source, sourceTagStyle, fileNameStyle);
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
        // Format source string from source object
        const sourceStr = source ? `${source.file}:${source.line}` : 'unknown:0';
        
        // Prepare data object that includes source info
        const debugData = {
            ...data,  // Include any existing data
            source: source || {  // Always include source info
                file: 'sh-debug.js',
                line: 'unknown',
                function: 'unknown'
            }
        };
        
        logData = {
            action: 'sh_debug_log',
            security: window.shortcutsHubData.security.debug_log,
            message: message,
            source: sourceStr,
            data: JSON.stringify(debugData),
            page: getCurrentPage()
        };
    } catch (e) {
        logError('sh_debug_log failed: Error preparing data - ' + e.message, 'sendLog');
        return;
    }

    // Send to PHP for logging
    jQuery.ajax({
        url: window.shortcutsHubData.ajaxurl,
        type: 'POST',
        data: logData,
        success: function(response) {
            if (!response || !response.success) {
                logError('sh_debug_log failed: Server returned error - ' + 
                    (response ? JSON.stringify(response) : 'No response'), 'sendLog');
            }
        },
        error: function(xhr, status, error) {
            logError('sh_debug_log failed: AJAX error - ' + status + ' - ' + error, 'sendLog');
        }
    });
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
    return error.stack ? error.stack.split('\n').slice(2).join('\n') : '';
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

    // Log initialization state
    sh_debug_log('Debug initialization state', {
        message: 'Initializing debug system',
        source: {
            file: 'sh-debug.js',
            line: 'initDebug',
            function: 'initDebug'
        },
        data: getScriptLoadingState(),
        debug: true
    });

    // Process any debug calls that happened before initialization
    processDebugQueue();

    // Create a promise for session start logging
    window.sessionLogPromise = new Promise((resolve) => {
        if (!window.shortcutsHubData.security || !window.shortcutsHubData.security.debug_log) {
            logError('Missing security nonce for debug logging', 'initDebug');
            resolve();
            return;
        }

        // Log session start
        jQuery.ajax({
            url: window.shortcutsHubData.ajaxurl,
            type: 'POST',
            data: {
                action: 'sh_debug_log',
                security: window.shortcutsHubData.security.debug_log,
                message: 'Debug session started',
                source: 'session-start',
                page: getCurrentPage()
            },
            success: function(response) {
                if (!response || !response.success) {
                    logError('Failed to start debug session:', 'initDebug');
                }
                resolve();
            },
            error: function(xhr, status, error) {
                logError('Failed to start debug session:', 'initDebug');
                resolve();
            }
        });
    });
}

// Try to initialize, but wait for jQuery and shortcutsHubData
function tryInit() {
    if (window.jQuery && window.shortcutsHubData) {
        initDebug();
    } else {
        setTimeout(tryInit, 100);
    }
}

// Start initialization process
tryInit();

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
