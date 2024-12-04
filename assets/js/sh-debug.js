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
            source = match ? match[1] : sourceLine.trim();
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
            source: source ? `[SOURCE] ${source}` : ''
        }
    });

    // Style console output
    const debugStyle = 'background: #909cfe; color: #252525; font-weight: bold;';
    const sourceTagStyle = 'color: #909cfe; font-weight: bold;';
    const fileNameStyle = 'font-weight: bold;';
    const jsonKeyStyle = 'color: #909cfe; font-weight: bold;';

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
        
        // Keep all logs at info level and same style for consistency
        const logMethod = console.log;
        const groupMethod = console.groupCollapsed;
        
        // Log debug message with styling
        groupMethod.call(console, '%c[DEBUG] ' + message, debugStyle);
        
        // Split source info into parts and style separately
        if (source) {
            const [fullMatch, fileName] = source.split(' ');
            const questionMarkIndex = fileName.indexOf('?');
            if (questionMarkIndex !== -1) {
                const beforeQuestion = fileName.substring(0, questionMarkIndex);
                const afterQuestion = fileName.substring(questionMarkIndex);
                logMethod.call(
                    console,
                    '%c[SOURCE] %c%s%c%s',
                    sourceTagStyle,
                    fileNameStyle,
                    beforeQuestion,
                    '',
                    afterQuestion
                );
            } else {
                logMethod.call(console, '%c[SOURCE] %c%s', sourceTagStyle, fileNameStyle, fileName);
            }
        } else {
            logMethod.call(console, '%c[SOURCE] %c%s', sourceTagStyle, '', callerLine);
        }
        
        if (data) {
            // Split the JSON string into lines
            const lines = JSON.stringify(data, null, 2).split('\n');
            
            // Process each line
            lines.forEach(line => {
                // Check if line contains a key (matches "key": pattern)
                const keyMatch = line.match(/^(\s*)"([^"]+)":/);
                if (keyMatch) {
                    // Preserve indentation, style the key, and add the rest of the line
                    const [, indent, key] = keyMatch;
                    const restOfLine = line.slice(keyMatch[0].length);
                    logMethod.call(
                        console,
                        indent + '%c"' + key + '"%c:' + restOfLine,
                        jsonKeyStyle,
                        'color: inherit; font-weight: normal;'
                    );
                } else {
                    logMethod.call(console, line);
                }
            });
        }
        console.groupEnd();
    }
}
