<?php
/**
 * The plugin bootstrap file.
 * 
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 * 
 * @link                    https://github.com/mnestorov/smarty-google-feed-generator
 * @since                   1.0.0
 * @package                 Smarty_Google_Feed_Generator
 * 
 * @wordpress-plugin
 * Plugin Name:             SM - Google Feed Generator for WooCommerce
 * Plugin URI:              https://github.com/mnestorov/smarty-google-feed-generator
 * Description:             Generates google product and product review feeds for Google Merchant Center.
 * Version:                 1.0.1
 * Author:                  Smarty Studio | Martin Nestorov
 * Author URI:              https://github.com/mnestorov
 * License:                 GPL-2.0+
 * License URI:             http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:             smarty-google-feed-generator
 * Domain Path:             /languages
 * WC requires at least:    3.5.0
 * WC tested up to:         9.4.1
 * Requires Plugins:		woocommerce
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
	die;
}

// Check if GFG_VERSION is not already defined
if (!defined('GFG_VERSION')) {
	/**
	 * Current plugin version.
	 * For the versioning of the plugin is used SemVer - https://semver.org
	 */
	define('GFG_VERSION', '1.0.1');
}

// Check if GFG_BASE_DIR is not already defined
if (!defined('GFG_BASE_DIR')) {
	/**
	 * This constant is used as a base path for including other files or referencing directories within the plugin.
	 */
    define('GFG_BASE_DIR', dirname(__FILE__));
}

/**
 * The base URL for the License Manager API.
 *
 * This URL points to the endpoint used for license validation requests.
 * It should not be modified, as it is required for secure communication with the License Manager.
 *
 * @since 	1.0.1
 * @access 	public
 */
if (!defined('API_URL')) { // Do not change!
    define('API_URL', base64_decode('aHR0cHM6Ly9zbWFydHlzdHVkaW8ud2Vic2l0ZS93cC1qc29uL3NtYXJ0eS12c2xtL3YxL2NoZWNrLWxpY2Vuc2U=')); 
}

/**
 * The Consumer Key for API authentication.
 *
 * Used to authenticate API requests securely alongside the Consumer Secret. 
 * This key should remain constant and not be changed to avoid disruptions to API communication.
 *
 * @since 	1.0.1
 * @access 	public
 */
if (!defined('CK_KEY')) { // Do not change!
    define('CK_KEY', base64_decode('Y2tfZWVmNTlhMjZhNDZmYTUwYmRiZjdjZGE5MzA2YzVmYWI5YmYyNjExZA==')); 
}

/**
 * The Consumer Secret for API authentication.
 *
 * Used in combination with the Consumer Key to secure API requests.
 * This key should remain unchanged to ensure consistent and secure access to the API.
 *
 * @since 	1.0.1
 * @access 	public
 */
if (!defined('CS_KEY')) { // Do not change!
    define('CS_KEY', base64_decode('Y3NfNWJlNDc4ZWVkMDEzNDIzZGJmN2RhYzBjNzBhODczNDZmZjhiM2UyZA==')); 
}

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/classes/class-smarty-gfg-activator.php
 * 
 * @since    1.0.0
 */
function activate_gfg() {
	require_once plugin_dir_path(__FILE__) . 'includes/classes/class-smarty-gfg-activator.php';
	Smarty_Gfg_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/classes/class-smarty-gfg-deactivator.php
 * 
 * @since    1.0.0
 */
function deactivate_gfg() {
	require_once plugin_dir_path(__FILE__) . 'includes/classes/class-smarty-gfg-deactivator.php';
	Smarty_Gfg_Deactivator::deactivate();
}

register_activation_hook(__FILE__, 'activate_gfg');
register_deactivation_hook(__FILE__, 'deactivate_gfg');

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path(__FILE__) . 'includes/classes/class-smarty-gfg-locator.php';

/**
 * The plugin functions file that is used to define general functions, shortcodes etc.
 */
require plugin_dir_path(__FILE__) . 'includes/functions.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_gfg_locator() {
	$plugin = new Smarty_Gfg_Locator();
	$plugin->run();
}

run_gfg_locator();