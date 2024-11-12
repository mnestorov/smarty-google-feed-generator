<?php
/**
 * The plugin functions file.
 *
 * This is used to define general functions, shortcodes etc.
 * 
 * Important: Always use the `smarty_` prefix for function names.
 *
 * @link       https://smartystudio.net
 * @since      1.0.0
 *
 * @package    Smarty_Google_Feed_Generator
 * @subpackage Smarty_Google_Feed_Generator/includes
 * @author     Smarty Studio | Martin Nestorov
 */

 if (!function_exists('smarty_get_the_product_sku')) {
    /**
     * Helper function to get the product SKU.
     * 
     * @since      1.0.0
     */
    function smarty_get_the_product_sku($product_id) {
        return get_post_meta($product_id, '_sku', true);
    }
}

if (!function_exists('smarty_check_compatibility')) {
    /**
     * Helper function to check compatibility.
     * 
     * @since      1.0.0
     */
    function smarty_check_compatibility() {
        $min_wp_version = MIN_WP_VER; // Minimum WordPress version required
        $min_wc_version = MIN_WC_VER; // Minimum WooCommerce version required
        $min_php_version = MIN_PHP_VER; // Minimum PHP version required

        $wp_compatible = version_compare(get_bloginfo('version'), $min_wp_version, '>=');
        $php_compatible = version_compare(PHP_VERSION, $min_php_version, '>=');

        // Check WooCommerce version
        if (!function_exists('get_plugins')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        $plugins = get_plugins();
        $wc_version = isset($plugins['woocommerce/woocommerce.php']) ? $plugins['woocommerce/woocommerce.php']['Version'] : '0';
        $wc_compatible = version_compare($wc_version, $min_wc_version, '>=');

        if (!$wp_compatible || !$php_compatible || !$wc_compatible) {
            deactivate_plugins(plugin_basename(__FILE__));
            wp_die('This plugin requires at least WordPress version ' . $min_wp_version . ', PHP ' . $min_php_version . ', and WooCommerce ' . $min_wc_version . ' to run.');
        }
        
        return array(
            'wp_version' => get_bloginfo('version'),
            'php_version' => PHP_VERSION,
            'wp_compatible' => $wp_compatible,
            'php_compatible' => $php_compatible,
            'wc_version' => $wc_version,
            'wc_compatible' => $wc_compatible,
        );
    }
}

if (!function_exists('_gfg_validate_license_key')) {
    function _gfg_validate_license_key() {
        // Retrieve the stored license key from options
        $license_options = get_option('smarty_gfg_settings_license');
        $api_key = $license_options['api_key'] ?? '';

        // Instantiate the License class and validate the license key
        $license_checker = new Smarty_Gfg_License();
        return $license_checker->gfg_is_valid_api_key($api_key);
    }
}

if (!function_exists('_gfg_write_logs')) {
	/**
     * Writes logs for the plugin.
     * 
     * @since      1.0.0
     * @param string $message Message to be logged.
     * @param mixed $data Additional data to log, optional.
     */
    function _gfg_write_logs($message, $data = null) {
        $log_entry = '[' . current_time('mysql') . '] ' . $message;
    
        if (!is_null($data)) {
            $log_entry .= ' - ' . print_r($data, true);
        }

        $logs_file = fopen(GFG_BASE_DIR . DIRECTORY_SEPARATOR . "logs.txt", "a+");
        fwrite($logs_file, $log_entry . "\n");
        fclose($logs_file);
    }
}