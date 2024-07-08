<?php

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @link       https://smartystudio.net
 * @since      1.0.0
 *
 * @package    Smarty_Google_Feed_Generator
 * @subpackage Smarty_Google_Feed_Generator/admin/partials
 * @author     Smarty Studio | Martin Nestorov
 */
class Smarty_Gfg_Activator {

	/**
	 * This function will be executed when the plugin is activated.
	 *
	 * @since    1.0.0
	 */
	public static function activate() {
        if (!class_exists('WooCommerce')) {
            wp_die('This plugin requires WooCommerce to be installed and active.');
        }

        // Add rewrite rules
        Smarty_Gfg_Public::gfg_add_rewrite_rules();

        // Flush rewrite rules to ensure custom endpoints are registered
        flush_rewrite_rules();

        // Schedule Google Feed Event
        $interval = get_option('smarty_gfg_google_feed_interval', 'daily');
        if ($interval !== 'no_refresh' && !wp_next_scheduled('smarty_gfg_generate_google_products_feed')) {
            wp_schedule_event(time(), $interval, 'smarty_gfg_generate_google_products_feed');
        }

        // Schedule Google Reviews Feed Event
        $interval = get_option('smarty_gfg_reviews_feed_interval', 'daily');
        if ($interval !== 'no_refresh' && !wp_next_scheduled('smarty_gfg_generate_google_reviews_feed')) {
            wp_schedule_event(time(), $interval, 'smarty_gfg_generate_google_reviews_feed');
        }

        // Schedule Bing Feed Event
        if (!wp_next_scheduled('smarty_gfg_generate_bing_products_feed')) {
            wp_schedule_event(time(), 'twicedaily', 'smarty_gfg_generate_bing_products_feed');
        }
    }
}