<?php

/**
 * The Google Reviews Feed-public specific functionality of the plugin.
 *
 * @link       https://smartystudio.net
 * @since      1.0.0
 *
 * @package    Smarty_Google_Feed_Generator
 * @subpackage Smarty_Google_Feed_Generator/admin/tabs
 * @author     Smarty Studio | Martin Nestorov
 */
class Smarty_Gfg_Google_Reviews_Feed_Public {

    /**
     * Generates the custom Google product review feed.
     * 
     * This function retrieves all the products and their reviews from a WooCommerce store and constructs
     * an XML feed that adheres to Google's specifications for review feeds.
     * 
     * @since    1.0.0
     */
    public function generate_google_reviews_feed() {
        // Add log entries
        Smarty_Gfg_Activity_Logging::add_activity_log('Generated Google Reviews Feed');

        $selected_ratings = get_option('smarty_reviews_ratings', []);
        if (!is_array($selected_ratings)) {
            $selected_ratings = explode(',', $selected_ratings);
        }

        // Set the content type to XML for the output
        header('Content-Type: application/xml; charset=utf-8');

        // Arguments for fetching all products; this query can be modified to exclude certain products or categories if needed
        $args = array(
            'post_type' => 'product',
            'numberposts' => -1, // Retrieve all products; adjust based on server performance or specific needs
        );

        // Retrieve products using WordPress get_posts function based on the defined arguments
        $products = get_posts($args);

        // Initialize the XML structure
        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->formatOutput = true;

        // Create the root <feed> element
        $feed = $dom->createElement('feed');
        $feed->setAttribute('xmlns:g', 'http://base.google.com/ns/1.0');
        $dom->appendChild($feed);

        // Iterate through each product to process its reviews
        foreach ($products as $product) {
            // Fetch all reviews for the current product. Only approved reviews are fetched
            $reviews = get_comments(array('post_id' => $product->ID));

            // Define the namespace URL for elements specific to Google feeds
            $gNamespace = 'http://base.google.com/ns/1.0';

            // Iterate through each review of the current product
            foreach ($reviews as $review) {
                $rating = get_comment_meta($review->comment_ID, 'rating', true);
                
                if ($review->comment_approved == '1' && in_array($rating, $selected_ratings)) { // Check if the review is approved before including it in the feed
                    // Create a new 'entry' element for each review
                    $entry = $dom->createElement('entry');
                    $feed->appendChild($entry);

                    // Add various child elements required by Google, ensuring data is properly escaped to avoid XML errors
                    $entry->appendChild($dom->createElementNS($gNamespace, 'g:id', htmlspecialchars(smarty_get_the_product_sku($product->ID))));
                    $entry->appendChild($dom->createElementNS($gNamespace, 'g:title', htmlspecialchars($product->post_title)));
                    $entry->appendChild($dom->createElementNS($gNamespace, 'g:content', htmlspecialchars($review->comment_content)));
                    $entry->appendChild($dom->createElementNS($gNamespace, 'g:reviewer', htmlspecialchars($review->comment_author)));
                    $entry->appendChild($dom->createElementNS($gNamespace, 'g:review_date', date('Y-m-d', strtotime($review->comment_date))));
                    $entry->appendChild($dom->createElementNS($gNamespace, 'g:rating', get_comment_meta($review->comment_ID, 'rating', true)));
                    // Add more fields as required by Google
                }
            }
        }

        // Output the final XML content
        $feed_content = $dom->saveXML();
        $cache_duration = get_option('smarty_cache_duration', 12); // Default to 12 hours if not set
        set_transient('smarty_google_reviews_feed', $feed_content, $cache_duration * HOUR_IN_SECONDS); // Cache the feed using WordPress transients
        file_put_contents(WP_CONTENT_DIR . '/uploads/smarty_google_reviews_feed.xml', $feed_content); // Optionally save the feed to a file in the WP uploads directory

        
        echo $dom->saveXML();
        exit; // Ensure the script stops here to prevent further output that could corrupt the feed
    }

    /**
     * Invalidate cache or regenerate review feed when reviews are added, updated, or deleted.
     * 
     * TODO: We need to made this to run manually.
     * 
     * @since    1.0.0
     * @param int $comment_id The ID of the comment being updated.
     * @param string $comment_approved The approval status of the comment.
     */
    public function invalidate_google_reviews_feed_cache($comment_id, $comment_approved = '') {
        // Add log entries
        Smarty_Gfg_Activity_Logging::add_activity_log('Invalidate Google Reviews Feed');

        $comment = get_comment($comment_id);
        $post_id = $comment->comment_post_ID;
        
        // Check if the comment is for a 'product' and approved
        if (get_post_type($post_id) === 'product' && ($comment_approved == 1 || $comment_approved == 'approve')) {
            // Invalidate cache
            delete_transient('smarty_google_reviews_feed');
        }
    }

    /**
     * Handle the cron scheduling of the feed.
     * 
     * @since    1.0.0
     */
    public function schedule_google_reviews_feed_generation() {
        $interval = get_option('smarty_reviews_feed_interval', 'daily');
        _gfg_write_logs('Smarty_Gfg_Google_Reviews_Feed_Public: Google Reviews Feed Interval: ' . $interval);

        $timestamp = wp_next_scheduled('smarty_generate_google_reviews_feed');

        // Only reschedule if the interval has changed or the event is not scheduled
        if (!$timestamp || wp_get_schedule('smarty_generate_google_reviews_feed') !== $interval) {
            if ($timestamp) {
                wp_unschedule_event($timestamp, 'smarty_generate_google_reviews_feed');
                _gfg_write_logs('Smarty_Gfg_Google_Reviews_Feed_Public: Unscheduled existing Google Reviews Feed event.');
            }

            if ($interval !== 'no_refresh') {
                wp_schedule_event(time(), $interval, 'smarty_generate_google_reviews_feed');
                _gfg_write_logs('Smarty_Gfg_Google_Reviews_Feed_Public: Scheduled new Google Reviews Feed event.');
            }
        } else {
            _gfg_write_logs('Smarty_Gfg_Google_Reviews_Feed_Public: Google Reviews Feed event is already scheduled with the correct interval.');
        }
    }
}
