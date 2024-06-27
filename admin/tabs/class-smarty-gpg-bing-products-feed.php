<?php

/**
 * The Bing Products Feed-specific functionality of the plugin.
 *
 * @link       https://smartystudio.net
 * @since      1.0.0
 *
 * @package    Smarty_Google_Feed_Generator
 * @subpackage Smarty_Google_Feed_Generator/admin/tabs
 * @author     Smarty Studio | Martin Nestorov
 */
class Smarty_Gfg_Bing_Products_Feed {

    use Smarty_Gfg_Google_Category_Trait;
    use Smarty_Gfg_Woo_Category_Mapping_Trait;

    /**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
        add_action('wp_ajax_smarty_get_woocommerce_categories', array($this, 'handle_ajax_get_woocommerce_categories'));
    }

    /**
	 * Initializes the Google Reviews Feed settings by registering the settings, sections, and fields.
	 *
	 * @since    1.0.0
	 */
    public function settings_init() {
        register_setting('smarty_gfg_options_bing_feed', 'smarty_bing_feed_country');
        register_setting('smarty_gfg_options_bing_feed', 'smarty_bing_include_product_variations');

        add_settings_section(
			'smarty_gfg_section_bing_feed',									    // ID of the section
			__('Bing Products Feed', 'smarty-google-feed-generator'),		    // Title of the section
			array($this, 'section_tab_bing_feed_cb'),						    // Callback function that fills the section with the desired content
			'smarty_gfg_options_bing_feed'									    // Page on which to add the section
		);

        add_settings_field(
            'smarty_bing_feed_country',										    // ID of the field
            __('Country', 'smarty-google-feed-generator'),					    // Title of the field
            array($this, 'bing_products_feed_country_cb'),					    // Callback function to display the field
            'smarty_gfg_options_bing_feed',									    // Page on which to add the field
            'smarty_gfg_section_bing_feed'									    // Section to which this field belongs
        );

        add_settings_field(
            'smarty_bing_include_product_variations',							// ID of the field
            __('Include Product Variations', 'smarty-google-feed-generator'),	// Title of the field
            array($this, 'bing_products_include_product_variations_cb'),	    // Callback function to display the field
            'smarty_gfg_options_bing_feed',									    // Page on which to add the field
            'smarty_gfg_section_bing_feed'									    // Section to which this field belongs
        );

        $this->register_google_category_settings('smarty_gfg_options_bing_feed', 'smarty_gfg_section_bing_feed', 'smarty_gfg_options_bing_feed');
        $this->register_woo_category_settings('smarty_gfg_options_bing_feed', 'smarty_gfg_section_bing_feed', 'smarty_gfg_options_bing_feed');
    }

    /**
	 * Callback function for the Google section field.
	 *
	 * @since    1.0.0
	 */
	public function section_tab_bing_feed_cb() {
		echo '<p>' . __('Main column options for the Bing Products feed.', 'smarty-google-feed-generator') . '</p>';
	}

    /**
	 *  @since    1.0.0
	 */
	public function bing_products_feed_country_cb() {
        $selected_country = get_option('smarty_bing_feed_country', '');
        $woocommerce_countries = WC()->countries->get_countries();

        echo '<select name="smarty_bing_feed_country" class="select2 smarty-country-select">';
        echo '<option value="">' . __('Select a Country', 'smarty-google-feed-generator') . '</option>';
        foreach ($woocommerce_countries as $code => $name) {
            $selected = $code === $selected_country ? 'selected' : '';
            echo '<option value="' . esc_attr($code) . '" ' . $selected . '>' . esc_html($name) . '</option>';
        }
        echo '</select>';
        echo '<p class="description">' . __('Select the country for which you want to generate the feed.', 'smarty-google-feed-generator') . '</p>';
    }

    /**
	 *  @since    1.0.0
	 */
	public function bing_products_include_product_variations_cb() {
        $option = get_option('smarty_bing_include_product_variations', 'no');
        echo '<input type="radio" name="smarty_bing_include_product_variations" value="yes" ' . checked($option, 'yes', false) . ' /> ' . __('Yes', 'smarty-google-feed-generator');
        echo '<input type="radio" name="smarty_bing_include_product_variations" value="no" ' . checked($option, 'no', false) . ' style="margin-left: 10px;" /> ' . __('No', 'smarty-google-feed-generator');
        echo '<p class="description">' . __('Select whether to include product variations in the feed.', 'smarty-google-feed-generator') . '</p>';
    }
}