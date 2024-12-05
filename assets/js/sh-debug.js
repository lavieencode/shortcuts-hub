/**
 * Debug logging function for Shortcuts Hub
 * @param {string} message - The debug message
 * @param {Object} data - Optional data to log
 * @param {string} source - Optional source info
 */

// Log session start when page loads
jQuery(document).ready(function() {
    // Create a promise for session start logging
    window.sessionLogPromise = jQuery.ajax({
        url: shortcutsHubData.ajax_url,
        type: 'POST',
        data: {
            action: 'sh_debug_log',
            security: shortcutsHubData.security,
            message: 'Session Started',
            data: null,
            source: 'session-start'
        }
    });
});

function sh_debug_log(message, data = null, source = null) {
    if (!source) {
        source = new Error();
    }
    
    if (window.sessionLogPromise) {
        window.sessionLogPromise.then(() => {
            sendLog(message, data, source);
        });
    } else {
        sendLog(message, data, source);
    }
}

function sendLog(message, data = null, source = null) {
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

    // Send to PHP for logging
    jQuery.ajax({
        url: shortcutsHubData.ajax_url,
        type: 'POST',
        data: {
            action: 'sh_debug_log',
            security: shortcutsHubData.security,
            message: message,
            data: JSON.stringify(data, null, 4),
            source: source || ''  // Let PHP handle the [SOURCE] prefix
        }
    });

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
            console.groupEnd();
        }
        console.groupEnd();
    }
}
