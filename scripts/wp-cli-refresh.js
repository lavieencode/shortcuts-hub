// WordPress Plugin Refresh Script using WP-CLI
// This script uses WP-CLI to deactivate and reactivate a WordPress plugin
const { exec } = require('child_process');
const util = require('util');
const execPromise = util.promisify(exec);

// Configuration - Update these values
const config = {
  pluginSlug: 'shortcuts-hub', // The slug of your plugin
  wpPath: '/home/user/htdocs/srv608334.hstgr.cloud' // Path to your WordPress installation
};

/**
 * Execute a WP-CLI command
 * @param {string} command - The WP-CLI command to execute
 * @returns {Promise<string>} - The command output
 */
async function runWpCli(command) {
  const fullCommand = `cd ${config.wpPath} && wp ${command} --allow-root`;
  console.log(`Running: ${fullCommand}`);
  
  try {
    const { stdout, stderr } = await execPromise(fullCommand);
    if (stderr) {
      console.error(`Command error: ${stderr}`);
    }
    return stdout.trim();
  } catch (error) {
    console.error(`Execution error: ${error.message}`);
    throw error;
  }
}

/**
 * Main function to refresh a WordPress plugin using WP-CLI
 * This will:
 * 1. Check if the plugin is active
 * 2. Deactivate the plugin if active
 * 3. Activate the plugin
 */
async function refreshPlugin() {
  console.log('Starting WordPress plugin refresh using WP-CLI...');
  
  try {
    // Check if plugin is active
    const pluginStatus = await runWpCli(`plugin is-active ${config.pluginSlug}`);
    const isActive = pluginStatus === '';
    
    console.log(`Plugin ${config.pluginSlug} is ${isActive ? 'active' : 'inactive'}`);
    
    // Deactivate plugin if active
    if (isActive) {
      console.log(`Deactivating plugin ${config.pluginSlug}...`);
      await runWpCli(`plugin deactivate ${config.pluginSlug}`);
      console.log('Plugin deactivated successfully');
    }
    
    // Activate plugin
    console.log(`Activating plugin ${config.pluginSlug}...`);
    await runWpCli(`plugin activate ${config.pluginSlug}`);
    console.log('Plugin activated successfully');
    
    return 'Plugin refresh completed successfully';
  } catch (error) {
    console.error('Error during plugin refresh:', error);
    return `Plugin refresh failed: ${error.message}`;
  }
}

// Run the function if this script is executed directly
if (require.main === module) {
  refreshPlugin()
    .then(result => console.log(result))
    .catch(error => console.error('Uncaught error:', error));
}

// Export the function for use in other scripts
module.exports = { refreshPlugin };
