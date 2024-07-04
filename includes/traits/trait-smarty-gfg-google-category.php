<?php

/**
 * Trait to handle Google Products category-related functionality for product feeds.
 *
 * @link       https://smartystudio.net
 * @since      1.0.0
 *
 * @package    Smarty_Google_Feed_Generator
 * @subpackage Smarty_Google_Feed_Generator/includes/traits
 * @author     Smarty Studio | Martin Nestorov
 */
trait Smarty_Gfg_Google_Category_Trait {

	/**
     * Register settings and fields related to Google Category.
	 * 
	 * @since    1.0.0
     * @param string $options_group The options group name.
     * @param string $section_id The section ID for settings fields.
     * @param string $page The settings page where fields are displayed.
     */
    public function register_google_category_settings($options_group, $section_id, $page) {
        register_setting('smarty_gfg_options_google_feed', 'smarty_gfg_google_product_category');
		register_setting('smarty_gfg_options_google_feed', 'smarty_gfg_google_category_as_id', array($this,'gfg_sanitize_checkbox'));

		add_settings_field(
			'smarty_gfg_google_product_category',                               // ID of the field
			__('Google Product Category', 'smarty-google-feed-generator'),  	// Title of the field
			array($this,'gfg_google_product_category_cb'),                      // Callback function to display the field
			$page,                                								// Page on which to add the field
			$section_id                                    						// Section to which this field belongs
		);

		add_settings_field(
			'smarty_gfg_google_category_as_id',                                	// ID of the field
			__('Use Google Category ID', 'smarty-google-feed-generator'),   	// Title of the field
			array($this,'gfg_google_category_as_id_cb'),                        // Callback function to display the field
			$page,                               								// Page on which to add the field
			$section_id                                							// Section to which this field belongs
		);
    }

	/**
     * Function to sanitize the plugin settings on save.
     * 
     * @since    1.0.0
     */
	public function gfg_sanitize_checkbox($input) {
		return $input == 1 ? 1 : 0;
	}

    /**
     * Callback function for the Google Product Category field.
     * 
     * @since    1.0.0
     */
	public function gfg_google_product_category_cb() {
		$option = get_option('smarty_gfg_google_product_category');
		echo '<select name="smarty_gfg_google_product_category" class="smarty-select2-ajax">';
		if ($option) {
			echo '<option value="' . esc_attr($option) . '" selected>' . esc_html($option) . '</option>';
		}
		echo '</select>';
		echo '<p class="description">' . __('The global Google Product Category for all of the products.<br><em><b>Important:</b> <span class="smarty-text-danger">This also works for Bing Products feed.</em></span>', 'smarty-google-feed-generator') . '</p>';
	}

    /**
     * Callback function for the Use Google Category ID field.
     * 
     * @since    1.0.0
     */
	public function gfg_google_category_as_id_cb() {
		echo '<label class="smarty-toggle-switch">';
		$option = get_option('smarty_gfg_google_category_as_id');
		echo '<input type="checkbox" name="smarty_gfg_google_category_as_id" value="1" ' . checked(1, $option, false) . ' />';
		echo '<span class="smarty-slider round"></span>';
        echo '</label>';
		echo '<p class="description">' . __('Check to use Google Product Category ID in the feed instead of the name.<br><em><b>Important:</b> <span class="smarty-text-danger">This also works for Bing Products feed.</em></span>', 'smarty-google-feed-generator') . '</p>';
	}

	/**
	 * AJAX handler to load Google Product Categories.
	 * 
	 * @since    1.0.0
	 */
	public function gfg_handle_ajax_load_google_categories() {
		// Add log entries
		Smarty_Gfg_Activity_Logging::gfg_add_activity_log('Load Google Categories');

		check_ajax_referer('smarty_feed_generator_nonce', 'nonce');
	
		if (!current_user_can('manage_options')) {
			wp_send_json_error('You do not have sufficient permissions to access this page.');
		}
	
		$search = sanitize_text_field($_GET['q']);
		$google_categories = $this->gfg_get_google_product_categories();
	
		$results = array();
		foreach ($google_categories as $category) {
			if (stripos($category, $search) !== false) {
				$results[] = array('id' => $category, 'text' => $category);
			}
		}
	
		wp_send_json_success($results);
	}

	/**
     * Fetch Google product categories from the taxonomy file and return them as an associative array with IDs.
     * 
     * @since    1.0.0
     * @return array Associative array of category IDs and names.
     */
	public function gfg_get_google_product_categories() {
		// Check if the categories are already cached
		$categories = get_transient('smarty_google_product_categories');
			
		if ($categories === false) {
			// URL of the Google taxonomy file
			$taxonomy_url = 'https://www.google.com/basepages/producttype/taxonomy-with-ids.en-US.txt';
				
			// Download the file
			$response = wp_remote_get($taxonomy_url);
				
			if (is_wp_error($response)) {
				return [];
			}
	
			$body = wp_remote_retrieve_body($response);
				
			// Parse the file
			$lines = explode("\n", $body);
			$categories = [];
	
			foreach ($lines as $line) {
				if (!empty($line) && strpos($line, '#') !== 0) {
						$categories[] = trim($line);
				}
			}
	
			// Cache the feed using WordPress transients              
			$cache_duration = get_option('smarty_gfg_cache_duration', 12); // Default to 12 hours if not set
			set_transient('smarty_gfg_google_feed', $categories, $cache_duration * HOUR_IN_SECONDS);  
		}
			
		return $categories;
	}
}