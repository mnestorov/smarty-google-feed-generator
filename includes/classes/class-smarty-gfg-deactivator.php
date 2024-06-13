<?php

/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 * @link       https://smartystudio.net
 * @since      1.0.0
 *
 * @package    Smarty_Google_Feed_Generator
 * @subpackage Smarty_Google_Feed_Generator/admin/partials
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
        $google_feed_timestamp = wp_next_scheduled('smarty_generate_google_feed');
        if ($google_feed_timestamp) {
            wp_unschedule_event($google_feed_timestamp, 'smarty_generate_google_feed');
        }

        // Unscheduling the reviews feed event
        $review_feed_timestamp = wp_next_scheduled('smarty_generate_google_reviews_feed');
        if ($review_feed_timestamp) {
            wp_unschedule_event($review_feed_timestamp, 'smarty_generate_google_reviews_feed');
        }

        // Path to the generated XML file
        $feed_file_path = WP_CONTENT_DIR . '/uploads/smarty_google_feed.xml';

        // Check if the file exists and delete it
        if (file_exists($feed_file_path)) {
            unlink($feed_file_path);
        }
    }
}