<?php
/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 * @link       https://github.com/mnestorov/smarty-google-feed-generator
 * @since      1.0.0
 *
 * @package    Smarty_Google_Feed_Generator
 * @subpackage Smarty_Google_Feed_Generator/includes/classes
 * @author     Smarty Studio | Martin Nestorov
 */
class Smarty_Gfg_Deactivator {

	/**
	 * This function will be executed when the plugin is deactivated.
	 *
	 * @since    1.7.0
	 */
	public static function deactivate() {
		// Flush rewrite rules
        flush_rewrite_rules();

        // Clear scheduled feed regeneration event
        $google_feed_timestamp = wp_next_scheduled('smarty_gfg_generate_google_products_feed');
        if ($google_feed_timestamp) {
            wp_unschedule_event($google_feed_timestamp, 'smarty_gfg_generate_google_products_feed');
        }

        // Unscheduling the reviews feed event
        $review_feed_timestamp = wp_next_scheduled('smarty_gfg_generate_google_reviews_feed');
        if ($review_feed_timestamp) {
            wp_unschedule_event($review_feed_timestamp, 'smarty_gfg_generate_google_reviews_feed');
        }

        // Clear scheduled feed regeneration event
        $bing_feed_timestamp = wp_next_scheduled('smarty_gfg_generate_bing_products_feed');
        if ($bing_feed_timestamp) {
            wp_unschedule_event($bing_feed_timestamp, 'smarty_gfg_generate_bing_products_feed');
        }

        // Path to the generated XML file
        $google_products_feed_file_path = WP_CONTENT_DIR . '/uploads/smarty_google_products_feed.xml';
        $bing_products_feed_file_path = WP_CONTENT_DIR . '/uploads/smarty_bing_products_feed.xml';

        // Check if the file exists and delete it
        if (file_exists($google_products_feed_file_path)) {
            unlink($google_products_feed_file_path);
        }

        if (file_exists($bing_products_feed_file_path)) {
            unlink($bing_products_feed_file_path);
        }
    }
}