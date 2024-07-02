<?php

/**
 * The Google Reviews Feed-specific functionality of the plugin.
 *
 * @link       https://smartystudio.net
 * @since      1.0.0
 *
 * @package    Smarty_Google_Feed_Generator
 * @subpackage Smarty_Google_Feed_Generator/admin/tabs
 * @author     Smarty Studio | Martin Nestorov
 */
class Smarty_Gfg_Google_Reviews_Feed {

    /**
	 * Initializes the Google Reviews Feed settings by registering the settings, sections, and fields.
	 *
	 * @since    1.0.0
	 */
    public function settings_init() {
        register_setting('smarty_gfg_options_google_reviews_feed', 'smarty_reviews_feed_country');
        register_setting('smarty_gfg_options_google_reviews_feed', 'smarty_reviews_ratings');
        register_setting('smarty_gfg_options_google_reviews_feed', 'smarty_reviews_feed_interval');

        add_settings_section(
            'smarty_gfg_section_google_reviews_feed',                           // ID of the section
            __('Google Reviews Feed', 'smarty-google-feed-generator'),          // Title of the section 
            array($this, 'section_tab_google_reviews_feed_cb'),                 // Callback function that fills the section with the desired content
            'smarty_gfg_options_google_reviews_feed'                            // Page on which to add the section 
        );

        add_settings_field(
            'smarty_reviews_feed_country',                                      // ID of the field
            __('Country', 'smarty-google-feed-generator'),                      // Title of the field
            array($this, 'reviews_feed_country_cb'),                            // Callback function to display the field
            'smarty_gfg_options_google_reviews_feed',                           // Page on which to add the field
            'smarty_gfg_section_google_reviews_feed'                            // Section to which this field belongs
        );

        add_settings_field(
            'smarty_reviews_ratings',                                           // ID of the field
            __('Ratings', 'smarty-google-feed-generator'),                      // Title of the field
            array($this, 'reviews_ratings_cb'),                                 // Callback function to display the field
            'smarty_gfg_options_google_reviews_feed',                           // Page on which to add the field
            'smarty_gfg_section_google_reviews_feed'                            // Section to which this field belongs
        );

        add_settings_field(
            'smarty_reviews_feed_interval',                                     // ID of the field
            __('Cron Job / Refresh Interval', 'smarty-google-feed-generator'),  // Title of the field
            array($this, 'reviews_feed_interval_cb'),                           // Callback function to display the field
            'smarty_gfg_options_google_reviews_feed',                           // Page on which to add the field
            'smarty_gfg_section_google_reviews_feed'                            // Section to which this field belongs
        );
    }

    /**
     * Callback function for the Google Reviews Feed main section.
     * 
     * @since    1.0.0
     */
    public function section_tab_google_reviews_feed_cb() {
        echo '<p>' . __('Settings for the Google Reviews Feed.', 'smarty-google-feed-generator') . '</p>';
    }

    /**
     * Callback function for the Reviews Feed country field.
     * 
     * @since    1.0.0
     */
    public function reviews_feed_country_cb() {
        $selected_country = get_option('smarty_reviews_feed_country', '');
        $woocommerce_countries = WC()->countries->get_countries();

        echo '<select name="smarty_reviews_feed_country" class="select2">';
        echo '<option value="">' . __('Select a Country', 'smarty-google-feed-generator') . '</option>';
        foreach ($woocommerce_countries as $code => $name) {
            $selected = $code === $selected_country ? 'selected' : '';
            echo '<option value="' . esc_attr($code) . '" ' . $selected . '>' . esc_html($name) . '</option>';
        }
        echo '</select>';
        echo '<p class="description">' . __('Select the country for which you want to generate the reviews feed.', 'smarty-google-feed-generator') . '</p>';
    }

    /**
     * Callback function for the Reviews Feed rating field.
     * 
     * @since    1.0.0
     */
    public function reviews_ratings_cb() {
        $selected_ratings = get_option('smarty_reviews_ratings', array());
        if (!is_array($selected_ratings)) {
            $selected_ratings = explode(',', $selected_ratings);
        }

        $ratings = [1, 2, 3, 4, 5];

        echo '<select name="smarty_reviews_ratings[]" multiple="multiple" class="smarty-reviews-ratings" style="width:50%;">';
        foreach ($ratings as $rating) {
            $selected = in_array($rating, $selected_ratings) ? 'selected' : '';
            echo '<option value="' . esc_attr($rating) . '" ' . $selected . '>' . esc_html($rating) . '</option>';
        }
        echo '</select>';
        echo '<p class="description">' . __('Select the review ratings to include in the feed.', 'smarty-google-feed-generator') . '</p>';
    }

    /**
     * Callback function for the Google Reviews Feed schedule interval.
     * 
     * @since    1.0.0
     */
    public function reviews_feed_interval_cb() {
        $interval = get_option('smarty_reviews_feed_interval', 'no_refresh');
        $options = [
            'no_refresh'  => __('No Refresh', 'smarty-google-feed-generator'),
            'hourly'      => __('Hourly', 'smarty-google-feed-generator'),
            'daily'       => __('Daily', 'smarty-google-feed-generator'),
            'twicedaily'  => __('Twice a day', 'smarty-google-feed-generator')
        ];

        echo '<select name="smarty_reviews_feed_interval">';
        foreach ($options as $value => $label) {
            $selected = $value === $interval ? 'selected' : '';
            echo '<option value="' . esc_attr($value) . '" ' . $selected . '>' . esc_html($label) . '</option>';
        }
        echo '</select>';
        echo '<p class="description">' . __('Select how often the Google Reviews feed should be refreshed.', 'smarty-google-feed-generator') . '</p>';
    }
}
