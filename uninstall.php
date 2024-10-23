<?php

/**
 * Fired when the plugin is uninstalled.
 *
 * When populating this file, consider the following flow of control:
 *
 * - This method should be static
 * - Check if the $_REQUEST content actually is the plugin name
 * - Run an admin referrer check to make sure it goes through authentication
 * - Verify the output of $_GET makes sense
 * - Repeat with other user roles. Best directly by using the links/query string parameters.
 * - Repeat things for multisite. Once for a single site in the network, once sitewide.
 *
 * This file may be updated more in future version of the Boilerplate; however, this is the
 * general skeleton and outline for how the file should work.
 *
 * @link       https://github.com/mnestorov
 * @since      1.0.0
 *
 * @package    Smarty_Google_Feed_Generator
 * @author     Smarty Studio | Martin Nestorov
 */

// If uninstall not called from WordPress, then exit.
if (!defined('WP_UNINSTALL_PLUGIN')) {
	exit;
}

// Delete plugin options
delete_option('smarty_plugin_settings');

// Clear scheduled events
$timestamp = wp_next_scheduled('smarty_gfg_generate_google_products_feed');
wp_unschedule_event($timestamp, 'smarty_gfg_generate_google_products_feed');

// Remove generated files
$google_products_feed_file_path = WP_content_dir() . '/uploads/smarty_google_products_feed.xml';
if (file_exists($google_products_feed_file_path)) {
    unlink($google_products_feed_file_path);
}

$bing_products_feed_file_path = WP_content_dir() . '/uploads/smarty_bing_products_feed.xml';
if (file_exists($bing_products_feed_file_path)) {
    unlink($bing_products_feed_file_path);
}

// Remove all transients
delete_transient('smarty_google_products_feed');
delete_transient('smarty_google_reviews_feed');
delete_transient('smarty_bing_products_feed');
