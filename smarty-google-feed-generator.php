<?php
/**
 * The plugin bootstrap file.
 * 
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 * 
 * @link                    https://smartystudio.net
 * @since                   1.0.0
 * @package                 Smarty_Google_Feed_Generator
 * 
 * @wordpress-plugin
 * Plugin Name:             SM - Google Feed Generator for WooCommerce
 * Plugin URI:              https://smartystudio.net/smarty-google-feed-generator
 * Description:             Generates google product and product review feeds for Google Merchant Center.
 * Version:                 1.0.0
 * Author:                  Smarty Studio | Martin Nestorov
 * Author URI:              https://smartystudio.net
 * License:                 GPL-2.0+
 * License URI:             http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:             smarty-google-feed-generator
 * Domain Path:             /languages
 * WC requires at least:    3.5.0
 * WC tested up to:         5.1.0
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
	die;
}

// Check if DCF_VERSION is not already defined
if (!defined('GFG_VERSION')) {
	/**
	 * Current plugin version.
	 * For the versioning of the plugin is used SemVer - https://semver.org
	 */
	define('GFG_VERSION', '1.0.0');
}

// Check if GFG_BASE_DIR is not already defined
if (!defined('GFG_BASE_DIR')) {
	/**
	 * This constant is used as a base path for including other files or referencing directories within the plugin.
	 */
    define('GFG_BASE_DIR', dirname(__FILE__));
}

// Check if MIN_WP_VER is not already defined
if (!defined('MIN_WP_VER')) {
	/**
	 * WP Version for plugin compatibility
	 */
    define('MIN_WP_VER', '5.4');
}

// Check if MIN_WP_VER is not already defined
if (!defined('MIN_WC_VER')) {
	/**
	 * WP Version for plugin compatibility
	 */
    define('MIN_WC_VER', '3.5');
}

// Check if MIN_PHP_VER is not already defined
if (!defined('MIN_PHP_VER')) {
	/**
	 * PHP Version for plugin compatibility
	 */
    define('MIN_PHP_VER', '7.4');
}

if (!defined('CK_KEY')) {
    define('CK_KEY', '');
}

if (!defined('CS_KEY')) {
    define('CS_KEY', '');
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