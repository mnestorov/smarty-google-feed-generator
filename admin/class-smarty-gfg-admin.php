<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two hooks for how to enqueue 
 * the admin-specific stylesheet (CSS) and JavaScript code.
 *
 * @link       https://smartystudio.net
 * @since      1.0.0
 *
 * @package    Smarty_Google_Feed_Generator
 * @subpackage Smarty_Google_Feed_Generator/admin/partials
 * @author     Smarty Studio | Martin Nestorov
 */
class Smarty_Gfg_Admin {
    /**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name     The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version         The current version of this plugin.
	 */
	private $version;

	/**
	 * The tsv/csv columns description.
	 * 
	 * @since    1.0.0
	 * @access   private
	 */
	private $column_descriptions = array();
	
	/**
	 * Instance of Smarty_Gfg_Google_Reviews_Feed.
	 * 
	 * @since    1.0.0
	 * @access   private
	 */
	private $reviews_feed;

	/**
	 * Instance of Smarty_Gfg_Activity_Logging.
	 * 
	 * @since    1.0.0
	 * @access   private
	 */
	private $activity_logging;

	/**
	 * Instance of Smarty_Gfg_License.
	 * 
	 * @since    1.0.0
	 * @access   private
	 */
	private $license;
	
	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param    string    $plugin_name     The name of this plugin.
	 * @param    string    $version         The version of this plugin.
	 */
	public function __construct($plugin_name, $version) {
		$this->plugin_name = $plugin_name;
		$this->version = $version;

		// Initialize column descriptions with translations
        $this->column_descriptions = array(
            'ID'                        			=> __('Your products unique identifier.', 'smarty-google-feed-generator'),
            'MPN'                       			=> __('The Manufacturer Part Number for the product.', 'smarty-google-feed-generator'),
            'GTIN'									=> __('A Global Trade Item Number (GTIN) is a unique and internationally recognized identifier for a product.', 'smarty-google-feed-generator'),
			'Title'                     			=> __('The title of the product.', 'smarty-google-feed-generator'),
            'Description'               			=> __('The description of the product.', 'smarty-google-feed-generator'),
            'Product Type'              			=> __('The product type or category.', 'smarty-google-feed-generator'),
            'Link'                      			=> __('The URL of the product page.', 'smarty-google-feed-generator'),
            'Mobile Link'               			=> __('The mobile URL of the product page.', 'smarty-google-feed-generator'),
            'Image Link'                			=> __('The URL of the product image.', 'smarty-google-feed-generator'),
            'Additional Image Link'     			=> __('The URL of an additional image for your product.', 'smarty-google-feed-generator'),
            'Google Product Category'   			=> __('Google-defined product category for your product.', 'smarty-google-feed-generator'),
            'Price'                    			 	=> __('Your products price.', 'smarty-google-feed-generator'),
            'Sale Price'                			=> __('Your products sale price.', 'smarty-google-feed-generator'),
            'Bundle'                    			=> __('Indicates a product is a merchant-defined custom group of different products featuring one main product.', 'smarty-google-feed-generator'),
            'Brand'                     			=> __('Your products brand name.', 'smarty-google-feed-generator'),
            'Condition'                 			=> __('The condition of your product at time of sale.', 'smarty-google-feed-generator'),
            'Multipack'                 			=> __('The number of identical products sold within a merchant-defined multipack.', 'smarty-google-feed-generator'),
            'Color'                     			=> __('Your products color(s).', 'smarty-google-feed-generator'),
            'Gender'                    			=> __('The gender for which your product is intended.', 'smarty-google-feed-generator'),
            'Material'                  			=> __('Your products fabric or material.', 'smarty-google-feed-generator'),
            'Size'                      			=> __('Your products size.', 'smarty-google-feed-generator'),
            'Size Type'                 			=> __('Your apparel products cut.', 'smarty-google-feed-generator'),
            'Size System'               			=> __('The country of the size system used by your product.', 'smarty-google-feed-generator'),
            'Availability'              			=> __('Your products availability.', 'smarty-google-feed-generator'),
            'Custom Label'              			=> __('Label that you assign to a product to help organize bidding and reporting in Shopping campaigns.', 'smarty-google-feed-generator'),
			'Excluded Destination'					=> __('A setting that you can use to exclude a product from participating in a specific type of advertising campaign.', 'smarty-google-feed-generator'),
			'Included Destination'					=> __('A setting that you can use to include a product in a specific type of advertising campaign.', 'smarty-google-feed-generator'),
			'Excluded Countries for Shopping Ads' 	=> __('A setting that allows you to exclude countries where your products are advertised on Shopping ads.', 'smarty-google-feed-generator'),
			'Shipping'								=> __('Your products shipping cost, shipping speeds, and the locations your product ships to.', 'smarty-google-feed-generator'),
		);

		// Include and instantiate the Google Reviews Feed class
		$this->reviews_feed = new Smarty_Gfg_Google_Reviews_Feed();
		
		// Include and instantiate the Activity Logging class
		$this->activity_logging = new Smarty_Gfg_Activity_Logging();

		// Include and instantiate the License class
		$this->license = new Smarty_Gfg_License();
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {
		/**
		 * This function enqueues custom CSS for the plugin settings in WordPress admin.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Smarty_Gfg_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Smarty_Gfg_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */
        wp_enqueue_style('select2', 'https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css', array(), '4.0.13');
		wp_enqueue_style($this->plugin_name, plugin_dir_url(__FILE__) . 'css/smarty-gfg-admin.css', array(), $this->version, 'all');
		wp_enqueue_style('dashicons');
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {
		/**
		 * This function enqueues custom JavaScript for the plugin settings in WordPress admin.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Smarty_Gfg_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Smarty_Gfg_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */
		wp_enqueue_script('select2', 'https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/js/select2.min.js', array('jquery'), '4.0.13', true);
        wp_enqueue_script($this->plugin_name, plugin_dir_url(__FILE__) . 'js/smarty-gfg-admin.js', array('jquery', 'select2'), $this->version, true);
		wp_localize_script(
            $this->plugin_name,
            'smartyFeedGenerator',
            array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'siteUrl' => site_url(),
                'nonce'   => wp_create_nonce('smarty_feed_generator_nonce'),
            )
        );
	}

	/**
	 * Add settings page to the WordPress admin menu.
	 * 
	 * @since    1.0.0
	 */
	public function add_settings_page() {
		add_options_page(
			'Google Feed Generator | Settings',         // Page title
			'Google Feed Generator',                    // Menu title
			'manage_options',                           // Capability required to access this page
			'smarty-gfg-settings',           			// Menu slug
			array($this, 'display_settings_page')  		// Callback function to display the page content
		);
	}

	/**
	 * @since    1.0.0
	 */
	private function get_settings_tabs() {
		return array(
			'general' 				 => __('General', 'smarty-google-feed-generator'),
			'google-feed'   		 => __('Google Products Feed', 'smarty-google-feed-generator'),
			'google-mp-feed'		 => __('Google Merchant Promotions Feed', 'smarty-google-feed-generator'), 		// Merchant Promotions
			'google-rp-feed'   		 => __('Google Remarketing Promotions Feed', 'smarty-google-feed-generator'), 	// Remarketing Promotions
			'google-dsa-feed'   	 => __('Google DSA Feed', 'smarty-google-feed-generator'), 						// Dynamic Search Ads
			'google-lp-feed'   	 	 => __('Google Local Products Feed', 'smarty-google-feed-generator'), 			// Local Products
			'google-lpi-feed'   	 => __('Google Local Products Inventory Feed', 'smarty-google-feed-generator'), // Local Products Invetory
			'google-reviews-feed'  	 => __('Google Reviews Feed', 'smarty-google-feed-generator'),
			'bing-products-feed'   	 => __('Bing Products Feed', 'smarty-google-feed-generator'),
			'facebook-products-feed' => __('Facebook Products Feed', 'smarty-google-feed-generator'),
			'activity-logging'  	 => __('Activity & Logging', 'smarty-google-feed-generator'),
			'license' 				 => __('License', 'smarty-google-feed-generator')
		);
	}

	/**
	 * Outputs the HTML for the settings page.
	 * 
	 * @since    1.0.0
	 */
	public function display_settings_page() {
		// Check user capabilities
		if (!current_user_can('manage_options')) {
			return;
		}

		$current_tab = isset($_GET['tab']) ? $_GET['tab'] : 'general';
		$tabs = $this->get_settings_tabs();
	
		// Define the path to the external file
		$partial_file = plugin_dir_path(__FILE__) . 'partials/smarty-gfg-admin-display.php';

		if (file_exists($partial_file) && is_readable($partial_file)) {
			include_once $partial_file;
		} else {
			//error_log("Unable to include: '$partial_file'");
		}
	}

	/**
	 * Initializes the plugin settings by registering the settings, sections, and fields.
	 *
	 * @since    1.0.0
	 */
	public function settings_init() {
		// General Settings
		register_setting('smarty_gfg_options_general', 'smarty_meta_title_field', 'sanitize_text_field');
		register_setting('smarty_gfg_options_general', 'smarty_meta_description_field', 'sanitize_text_field');
		register_setting('smarty_gfg_options_general', 'smarty_clear_cache', array($this,'sanitize_checkbox'));
		register_setting('smarty_gfg_options_general', 'smarty_cache_duration', array($this,'sanitize_number_field'));

		// Google Products Feed Settings
		register_setting('smarty_gfg_options_google_feed', 'smarty_feed_country');
        register_setting('smarty_gfg_options_google_feed', 'smarty_include_product_variations');
		register_setting('smarty_gfg_options_google_feed', 'smarty_google_product_category');
		register_setting('smarty_gfg_options_google_feed', 'smarty_google_category_as_id', array($this,'sanitize_checkbox'));
		register_setting('smarty_gfg_options_google_feed', 'smarty_exclude_patterns', 'sanitize_textarea_field');
		register_setting('smarty_gfg_options_google_feed', 'smarty_excluded_categories');
		register_setting('smarty_gfg_options_google_feed', 'smarty_category_mapping', array($this, 'sanitize_category_mapping'));
		register_setting('smarty_gfg_options_google_feed', 'smarty_exclude_xml_columns');
		register_setting('smarty_gfg_options_google_feed', 'smarty_exclude_csv_columns');
		register_setting('smarty_gfg_options_google_feed', 'smarty_condition');
		register_setting('smarty_gfg_options_google_feed', 'smarty_size_system');
		register_setting('smarty_gfg_options_google_feed', 'smarty_excluded_destination', array($this, 'sanitize_excluded_destination'));
		register_setting('smarty_gfg_options_google_feed', 'smarty_included_destination', array($this, 'sanitize_included_destination'));
		register_setting('smarty_gfg_options_google_feed', 'smarty_excluded_countries_for_shopping_ads');
		
		$custom_labels = [
			'Custom Label 0' => ['logic', 'value', 'days', 'categories'],
			'Custom Label 1' => ['logic', 'value', 'days', 'categories'],
			'Custom Label 2' => ['logic', 'value', 'days', 'categories'],
			'Custom Label 3' => ['logic', 'value', 'days', 'categories'],
			'Custom Label 4' => ['logic', 'value', 'days', 'categories'],
		];
		
		foreach ($custom_labels as $label => $fields) {
			foreach ($fields as $field) {
				$option_name = 'smarty_' . strtolower(str_replace(' ', '_', $label)) . '_' . $field;
				register_setting('smarty_gfg_options_google_feed', $option_name);
			}
		}

		// Google Reviews Feed settings
        $this->reviews_feed->settings_init();

		// Activity & Logging settings
		$this->activity_logging->settings_init();

		// License settings
		$this->license->settings_init();

		add_settings_section(
			'smarty_gfg_section_general',                                   	// ID of the section
			__('General', 'smarty-google-feed-generator'),                  	// Title of the section
			array($this,'section_general_cb'),                          		// Callback function that fills the section with the desired content
			'smarty_gfg_options_general'                                		// Page on which to add the section
		);

		add_settings_section(
			'smarty_gfg_section_convert_images',                            	// ID of the section
			__('Convert Images', 'smarty-google-feed-generator'),           	// Title of the section
			array($this,'section_convert_images_cb'),                   		// Callback function that fills the section with the desired content
			'smarty_gfg_options_general'                                		// Page on which to add the section
		);
	
		add_settings_section(
			'smarty_gfg_section_generate_feeds',                            	// ID of the section
			__('Generate Feeds', 'smarty-google-feed-generator'),           	// Title of the section
			array($this,'section_generate_feeds_cb'),                   		// Callback function that fills the section with the desired content
			'smarty_gfg_options_general'                                		// Page on which to add the section
		);
	
		add_settings_section(
			'smarty_gfg_section_meta_fields',                               	// ID of the section
			__('Custom Meta Fields', 'smarty-google-feed-generator'),           // Title of the section
			array($this,'section_meta_fields_cb'),                      		// Callback function that fills the section with the desired content
			'smarty_gfg_options_general'                                		// Page on which to add the section
		);
	
		add_settings_section(
			'smarty_gfg_section_settings',                                  	// ID of the section
			__('Cache', 'smarty-google-feed-generator'),                    	// Title of the section
			array($this,'section_settings_cb'),                         		// Callback function that fills the section with the desired content
			'smarty_gfg_options_general'                                		// Page on which to add the section
		);
	
		add_settings_field(
			'smarty_convert_images',                                        	// ID of the field
			__('Convert', 'smarty-google-feed-generator'),                  	// Title of the field
			array($this,'convert_images_button_cb'),                        	// Callback function to display the field
			'smarty_gfg_options_general',                               		// Page on which to add the field
			'smarty_gfg_section_convert_images'                             	// Section to which this field belongs
		);
	
		add_settings_field(
			'smarty_generate_feed_now',                                     	// ID of the field
			__('Generate', 'smarty-google-feed-generator'),                 	// Title of the field
			array($this,'generate_feed_buttons_cb'),                        	// Callback function to display the field
			'smarty_gfg_options_general',                               		// Page on which to add the field
			'smarty_gfg_section_generate_feeds'                             	// Section to which this field belongs
		);
	
		add_settings_field(
			'smarty_meta_title_field',                                      	// ID of the field
			__('Meta Title', 'smarty-google-feed-generator'),               	// Title of the field
			array($this,'meta_title_field_cb'),                             	// Callback function to display the field
			'smarty_gfg_options_general',                               		// Page on which to add the field
			'smarty_gfg_section_meta_fields'                                	// Section to which this field belongs
		);
	
		add_settings_field(
			'smarty_meta_description_field',                                	// ID of the field
			__('Meta Description', 'smarty-google-feed-generator'),         	// Title of the field
			array($this,'meta_description_field_cb'),                       	// Callback function to display the field
			'smarty_gfg_options_general',                               		// Page on which to add the field
			'smarty_gfg_section_meta_fields'                                	// Section to which this field belongs
		);
	
		add_settings_field(
			'smarty_clear_cache',                                           	// ID of the field
			__('Clear Cache', 'smarty-google-feed-generator'),              	// Title of the field
			array($this,'clear_cache_cb'),                                  	// Callback function to display the field
			'smarty_gfg_options_general',                               		// Page on which to add the field
			'smarty_gfg_section_settings'                                   	// Section to which this field belongs
		);
	
		add_settings_field(
			'smarty_cache_duration',                                        	// ID of the field
			__('Cache Duration (hours)', 'smarty-google-feed-generator'),   	// Title of the field
			array($this,'cache_duration_cb'),                               	// Callback function to display the field
			'smarty_gfg_options_general',                               		// Page on which to add the field
			'smarty_gfg_section_settings'                                   	// Section to which this field belongs
		);

		/*
		 * GOOGLE PRODUCTS FEED TAB
		 */

		// Sections

		add_settings_section(
			'smarty_gfg_section_google_feed',									// ID of the section
			__('Google Products Feed', 'smarty-google-feed-generator'),			// Title of the section
			array($this, 'section_tab_google_feed_cb'),							// Callback function that fills the section with the desired content
			'smarty_gfg_options_google_feed'									// Page on which to add the section
		);

		// Fields

		add_settings_field(
            'smarty_feed_country',												// ID of the field
            __('Country', 'smarty-google-feed-generator'),						// Title of the field
            array($this, 'feed_country_cb'),									// Callback function to display the field
            'smarty_gfg_options_google_feed',									// Page on which to add the field
            'smarty_gfg_section_google_feed'									// Section to which this field belongs
        );

		add_settings_field(
            'smarty_include_product_variations',								// ID of the field
            __('Include Product Variations', 'smarty-google-feed-generator'),	// Title of the field
            array($this, 'include_product_variations_cb'),						// Callback function to display the field
            'smarty_gfg_options_google_feed',									// Page on which to add the field
            'smarty_gfg_section_google_feed'									// Section to which this field belongs
        );

		add_settings_field(
			'smarty_google_product_category',                               	// ID of the field
			__('Google Product Category', 'smarty-google-feed-generator'),  	// Title of the field
			array($this,'google_product_category_cb'),                      	// Callback function to display the field
			'smarty_gfg_options_google_feed',                               	// Page on which to add the field
			'smarty_gfg_section_google_feed'                                    // Section to which this field belongs
		);
	
		add_settings_field(
			'smarty_google_category_as_id',                                		// ID of the field
			__('Use Google Category ID', 'smarty-google-feed-generator'),   	// Title of the field
			array($this,'google_category_as_id_cb'),                        	// Callback function to display the field
			'smarty_gfg_options_google_feed',                               	// Page on which to add the field
			'smarty_gfg_section_google_feed'                                	// Section to which this field belongs
		);

		add_settings_field(
			'smarty_exclude_patterns',                                      	// ID of the field
			__('Exclude Patterns', 'smarty-google-feed-generator'),         	// Title of the field
			array($this,'exclude_patterns_cb'),                            		// Callback function to display the field
			'smarty_gfg_options_google_feed',                               	// Page on which to add the field
			'smarty_gfg_section_google_feed'                                    // Section to which this field belongs
		);
	
		add_settings_field(
			'smarty_excluded_categories',                                   	// ID of the field
			__('Excluded Categories', 'smarty-google-feed-generator'),      	// Title of the field
			array($this,'excluded_categories_cb'),                          	// Callback function to display the field
			'smarty_gfg_options_google_feed',                               	// Page on which to add the field
			'smarty_gfg_section_google_feed'                                    // Section to which this field belongs
		);

		add_settings_field(
            'smarty_category_mapping',											// ID of the field
            __('Category Mapping', 'smarty-google-feed-generator'),				// Title of the section
            array($this, 'category_mapping_field_cb'),							// Callback function to display the field
            'smarty_gfg_options_google_feed',									// Page on which to add the section
            'smarty_gfg_section_google_feed'									// Section to which this field belongs
        );

		add_settings_field(
			'smarty_exclude_xml_columns', 										// ID of the field
			__('XML Columns', 'smarty-google-feed-generator'),  				// Title of the section
			array($this,'exclude_xml_columns_cb'), 								// Callback function to display the field
			'smarty_gfg_options_google_feed', 									// Page on which to add the section
			'smarty_gfg_section_google_feed'									// Section to which this field belongs
		);

		add_settings_field(
			'smarty_exclude_csv_columns', 										// ID of the field
			__('CSV Columns', 'smarty-google-feed-generator'), 					// Title of the field
			array($this,'exclude_csv_columns_cb'), 								// Callback function to display the field
			'smarty_gfg_options_google_feed', 									// Page on which to add the field
			'smarty_gfg_section_google_feed'									// Section to which this field belongs
		);

		add_settings_field(
			'smarty_optional_attributes_table',									// ID of the field
			__('Optional Attributes', 'smarty-google-feed-generator'),			// Title of the field
			array($this, 'optional_attributes_table_cb'),						// Callback function to display the field
			'smarty_gfg_options_google_feed',									// Page on which to add the field
			'smarty_gfg_section_google_feed'									// Section to which this field belongs
		);

		add_settings_field(
			'smarty_custom_labels_table',                                      	// ID of the field
			__('Custom Labels', 'smarty-google-feed-generator'),               	// Title of the field
			array($this, 'custom_labels_table_cb'),                            	// Callback function to display the field
			'smarty_gfg_options_google_feed',                                  	// Page on which to add the field
			'smarty_gfg_section_google_feed'                                 	// Section to which this field belongs
		);
	}
	
	/**
     * Function to sanitize the plugin settings on save.
     * 
     * @since    1.0.0
     */
	public function sanitize_checkbox($input) {
		return $input == 1 ? 1 : 0;
	}

	/**
     * Function to sanitize number fields on save.
     * 
     * @since    1.0.0
     */
	public function sanitize_number_field($input) {
		return absint($input);
	}

	/**
     * Sanitize category mapping settings.
     * 
	 * @since    1.0.0
     */
	public function sanitize_category_mapping($input) {
		$sanitized_input = array();
		foreach ($input as $key => $value) {
			$sanitized_input[$key] = sanitize_text_field($value);
		}
		return $sanitized_input;
	}

	/**
     * Function to sanitize category values on save.
     * 
     * @since    1.0.0
     */
	public function sanitize_category_values($input) {
		// Check if the input is an array
		if (is_array($input)) {
			return array_map('sanitize_text_field', array_map('trim', $input));
		} else {
			// If the input is a string, split it by commas, trim each value, and return as a comma-separated string
			$values = explode(',', $input);
			$values = array_map('sanitize_text_field', array_map('trim', $values));
			return implode(', ', $values);
		}
	}

	/**
     * Function to sanitize excluded destination on save.
     * 
     * @since    1.0.0
     */
	public function sanitize_excluded_destination($input) {
		if (is_array($input)) {
			return array_map('sanitize_text_field', $input);
		}
		return [];
	}
	
	/**
     * Function to sanitize included destination on save.
     * 
     * @since    1.0.0
     */
	public function sanitize_included_destination($input) {
		if (is_array($input)) {
			return array_map('sanitize_text_field', $input);
		}
		return [];
	}

	/**
     * Handle AJAX request to convert images.
     * 
     * @since    1.0.0
     */
	public function handle_ajax_convert_images() {
		// Add log entries
		self::add_activity_log('Convert the first WebP image to PNG');

		check_ajax_referer('smarty_feed_generator_nonce', 'nonce');
	
		if (!current_user_can('manage_options')) {
			wp_send_json_error('You do not have sufficient permissions to access this page.');
		}
	
		try {
			// Attempt to convert images
			$this->convert_first_webp_image_to_png();
			wp_send_json_success(__('The first WebP image of each product has been converted to PNG.', 'smarty-google-feed-generator'));
		} catch (Exception $e) {
			// Handle exceptions by sending a descriptive error message
			wp_send_json_error('Error converting images: ' . $e->getMessage());
		}
	}

	/**
     * Handle AJAX request to convert all images.
     * 
     * @since    1.0.0
     */
	public function handle_ajax_convert_all_images() {
		// Add log entries
		self::add_activity_log('Convert all WebP images to PNG');

		check_ajax_referer('smarty_feed_generator_nonce', 'nonce');

		if (!current_user_can('manage_options')) {
			wp_send_json_error('You do not have sufficient permissions to access this page.');
		}
	
		try {
			// Attempt to convert images
			$this->convert_all_webp_images_to_png();
			wp_send_json_success(__('All images have been converted to PNG.', 'smarty-google-feed-generator'));
		} catch (Exception $e) {
			// Handle exceptions by sending a descriptive error message
			wp_send_json_error('Error converting images: ' . $e->getMessage());
		}
	}

	/**
	 * AJAX handler to load Google Product Categories.
	 * 
	 * @since    1.0.0
	 */
	public function handle_ajax_load_google_categories() {
		// Add log entries
		self::add_activity_log('Load Google Categories');

		check_ajax_referer('smarty_feed_generator_nonce', 'nonce');
	
		if (!current_user_can('manage_options')) {
			wp_send_json_error('You do not have sufficient permissions to access this page.');
		}
	
		$search = sanitize_text_field($_GET['q']);
		$google_categories = $this->get_google_product_categories();
	
		$results = array();
		foreach ($google_categories as $category) {
			if (stripos($category, $search) !== false) {
				$results[] = array('id' => $category, 'text' => $category);
			}
		}
	
		wp_send_json_success($results);
	}

	/**
	 * AJAX handler to load Woocommerce Product Categories.
	 * 
	 * @since    1.0.0
	 */
	public function handle_ajax_get_woocommerce_categories() {
		check_ajax_referer('smarty_feed_generator_nonce', 'nonce');
	
		if (!current_user_can('manage_options')) {
			wp_send_json_error('You do not have sufficient permissions to access this page.');
		}
	
		$categories = get_terms([
			'taxonomy' => 'product_cat',
			'hide_empty' => false,
		]);
	
		wp_send_json_success($categories);
	}

	/**
	 *  @since    1.0.0
	 */
	public function feed_country_cb() {
        $selected_country = get_option('smarty_feed_country', '');
        $woocommerce_countries = WC()->countries->get_countries();

        echo '<select name="smarty_feed_country" class="select2 smarty-country-select">';
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
	public function include_product_variations_cb() {
        $option = get_option('smarty_include_product_variations', 'no');
        echo '<input type="radio" name="smarty_include_product_variations" value="yes" ' . checked($option, 'yes', false) . ' /> ' . __('Yes', 'smarty-google-feed-generator');
        echo '<input type="radio" name="smarty_include_product_variations" value="no" ' . checked($option, 'no', false) . ' style="margin-left: 10px;" /> ' . __('No', 'smarty-google-feed-generator');
        echo '<p class="description">' . __('Select whether to include product variations in the feed.', 'smarty-google-feed-generator') . '</p>';
    }

	/**
     * Fetch Google product categories from the taxonomy file and return them as an associative array with IDs.
     * 
     * @since    1.0.0
     * @return array Associative array of category IDs and names.
     */
	public function get_google_product_categories() {
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
			$cache_duration = get_option('smarty_cache_duration', 12); // Default to 12 hours if not set
			set_transient('smarty_google_feed', $categories, $cache_duration * HOUR_IN_SECONDS);  
		}
			
		return $categories;
	}

	/**
	 *  @since    1.0.0
	 */
	public function optional_attributes_table_cb() {
		// Fetch current options
		$condition = get_option('smarty_condition', 'new');
		$excluded_destinations = get_option('smarty_excluded_destination', array());
		$included_destinations = get_option('smarty_included_destination', array());
		$excluded_country = get_option('smarty_excluded_countries_for_shopping_ads', '');
		$size_system = get_option('smarty_size_system', '');
	
		// Ensure arrays are properly formatted
		if (!is_array($excluded_destinations)) {
			$excluded_destinations = array();
		}
		if (!is_array($included_destinations)) {
			$included_destinations = array();
		}

		// Condition options
		$condition_options = [
			'new' => __('New', 'smarty-google-feed-generator'),
			'refurbished' => __('Refurbished', 'smarty-google-feed-generator'),
			'used' => __('Used', 'smarty-google-feed-generator'),
		];
	
		// WooCommerce countries
		$woocommerce_countries = WC()->countries->get_countries();
	
		// Destination options
		$destinations = ['Shopping_ads', 'Buy_on_Google_listings', 'Display_ads', 'Local_inventory_ads', 'Free_listings', 'Free_local_listings', 'YouTube_Shopping'];
	
		// Size system options
		$size_systems = ['US', 'UK', 'EU', 'DE', 'FR', 'JP', 'CN', 'IT', 'BR', 'MEX', 'AU'];
		
		echo '<p>' . __('Manage additional optional attributes for Google Products feed.', 'smarty-google-feed-generator') . '</p>';
		echo '<table class="form-table user-friendly-table"><tbody>';
		echo '<tr><th>' . __('Attributes', 'smarty-google-feed-generator') . '</th><th>' . __('Values', 'smarty-google-feed-generator') . '</th></tr>';

		// Condition
		echo '<tr>';
		echo '<td>' . __('Condition', 'smarty-google-feed-generator') . '</td>';
		echo '<td>';
		echo '<table><tr>';
		foreach ($condition_options as $value => $label) {
			$checked = $condition === $value ? 'checked' : '';
			echo '<td><label><input type="radio" name="smarty_condition" value="' . esc_attr($value) . '" ' . $checked . '> ' . esc_html($label) . '</label></td>';
		}
		echo '</tr></table>';
		echo '</td>';
		echo '</tr>';
	
		// Excluded Destination
		echo '<tr>';
		echo '<td>' . __('Excluded Destination', 'smarty-google-feed-generator') . '</td>';
		echo '<td>';
		$half_count = ceil(count($destinations) / 2);
		echo '<table><tr>';
		for ($i = 0; $i < $half_count; $i++) {
			$destination = $destinations[$i];
			$checked = in_array($destination, $excluded_destinations) ? 'checked' : '';
			echo '<td><label><input type="checkbox" name="smarty_excluded_destination[]" value="' . esc_attr($destination) . '" ' . $checked . '> ' . esc_html(str_replace('_', ' ', $destination)) . '</label></td>';
		}
		echo '</tr><tr>';
		for ($i = $half_count; $i < count($destinations); $i++) {
			$destination = $destinations[$i];
			$checked = in_array($destination, $excluded_destinations) ? 'checked' : '';
			echo '<td><label><input type="checkbox" name="smarty_excluded_destination[]" value="' . esc_attr($destination) . '" ' . $checked . '> ' . esc_html(str_replace('_', ' ', $destination)) . '</label></td>';
		}
		echo '</tr></table>';
		echo '</td>';
		echo '</tr>';
	
		// Included Destination
		echo '<tr>';
		echo '<td>' . __('Included Destination', 'smarty-google-feed-generator') . '</td>';
		echo '<td>';
		echo '<table><tr>';
		for ($i = 0; $i < $half_count; $i++) {
			$destination = $destinations[$i];
			$checked = in_array($destination, $included_destinations) ? 'checked' : '';
			echo '<td><label><input type="checkbox" name="smarty_included_destination[]" value="' . esc_attr($destination) . '" ' . $checked . '> ' . esc_html(str_replace('_', ' ', $destination)) . '</label></td>';
		}
		echo '</tr><tr>';
		for ($i = $half_count; $i < count($destinations); $i++) {
			$destination = $destinations[$i];
			$checked = in_array($destination, $included_destinations) ? 'checked' : '';
			echo '<td><label><input type="checkbox" name="smarty_included_destination[]" value="' . esc_attr($destination) . '" ' . $checked . '> ' . esc_html(str_replace('_', ' ', $destination)) . '</label></td>';
		}
		echo '</tr></table>';
		echo '</td>';
		echo '</tr>';
	
		// Excluded Countries for Shopping Ads
		echo '<tr>';
		echo '<td>' . __('Excluded Countries for Shopping Ads', 'smarty-google-feed-generator') . '</td>';
		echo '<td>';
		echo '<select name="smarty_excluded_countries_for_shopping_ads" class="smarty-excluded-countries">';
		echo '<option value="">' . __('Select a Country', 'smarty-google-feed-generator') . '</option>';
		foreach ($woocommerce_countries as $code => $name) {
			$selected = $code === $excluded_country ? 'selected' : '';
			echo '<option value="' . esc_attr($code) . '" ' . $selected . '>' . esc_html($name) . '</option>';
		}
		echo '</select>';
		echo '</td>';
		echo '</tr>';
	
		// Size System
		echo '<tr>';
		echo '<td>' . __('Size System', 'smarty-google-feed-generator') . '</td>';
		echo '<td>';
		$half_count = ceil(count($size_systems) / 2);
		echo '<table><tr>';
		for ($i = 0; $i < $half_count; $i++) {
			$system = $size_systems[$i];
			$checked = $system === $size_system ? 'checked' : '';
			echo '<td><label><input type="radio" name="smarty_size_system" value="' . esc_attr($system) . '" ' . $checked . '> ' . esc_html($system) . '</label></td>';
		}
		echo '</tr><tr>';
		for ($i = $half_count; $i < count($size_systems); $i++) {
			$system = $size_systems[$i];
			$checked = $system === $size_system ? 'checked' : '';
			echo '<td><label><input type="radio" name="smarty_size_system" value="' . esc_attr($system) . '" ' . $checked . '> ' . esc_html($system) . '</label></td>';
		}
		echo '</tr></table>';
		echo '</td>';
		echo '</tr>';
	
		echo '</tbody></table>';
	}
	
	/**
     * Converts an image from WEBP to PNG, updates the product image, and regenerates the feed.
	 * 
	 * @since    1.0.0
     * @param WC_Product $product Product object.
     */
    public function convert_and_update_product_image($product) {
        $image_id = $product->get_image_id();

        if ($image_id) {
            $file_path = get_attached_file($image_id);
            
            if ($file_path && preg_match('/\.webp$/', $file_path)) {
                $new_file_path = preg_replace('/\.webp$/', '.png', $file_path);
                
                if ($this->convert_webp_to_png($file_path, $new_file_path)) {
                    // Update the attachment file type post meta
                    wp_update_attachment_metadata($image_id, wp_generate_attachment_metadata($image_id, $new_file_path));
                    update_post_meta($image_id, '_wp_attached_file', $new_file_path);

                    // Regenerate thumbnails
                    if (function_exists('wp_update_attachment_metadata')) {
                        wp_update_attachment_metadata($image_id, wp_generate_attachment_metadata($image_id, $new_file_path));
                    }

                    // Optionally, delete the original WEBP file
                    @unlink($file_path);

                    // Invalidate feed cache and regenerate
                    Smarty_Gfg_Public::invalidate_feed_cache($product->get_id());
                }
            }
        }
    }

	/**
     * Converts a WEBP image file to PNG.
     * 
	 * @since    1.0.0
     * @param string $source The source file path.
     * @param string $destination The destination file path.
     * @return bool True on success, false on failure.
     */
    public function convert_webp_to_png($source, $destination) {
        if (!function_exists('imagecreatefromwebp')) {
            //error_log('GD Library is not installed or does not support WEBP.');
            return false;
        }

        $image = imagecreatefromwebp($source);
        
        if (!$image) return false;

        $result = imagepng($image, $destination);
        imagedestroy($image);

        return $result;
    }

	/**
     * Convert the first WebP image of each product to PNG.
	 * 
	 * @since    1.0.0
     */
	public static function convert_first_webp_image_to_png() {
		$offset = 0;
		$limit = 50; // Process 50 products at a time
	
		while (true) {
			$products = wc_get_products(array(
				'status' => 'publish',
				'limit'  => $limit,
				'offset' => $offset,
			));
	
			if (empty($products)) {
				break;
			}
	
			foreach ($products as $product) {
				$image_id = $product->get_image_id();
				$file_path = get_attached_file($image_id);
	
				if ($file_path && preg_match('/\.webp$/', $file_path)) {
					$new_file_path = preg_replace('/\.webp$/', '.png', $file_path);
	
					if ($this->convert_webp_to_png($file_path, $new_file_path)) {
						// Update the attachment file type post meta
						wp_update_attachment_metadata($image_id, wp_generate_attachment_metadata($image_id, $new_file_path));
						update_post_meta($image_id, '_wp_attached_file', $new_file_path);
						
						// Regenerate thumbnails
						if (function_exists('wp_update_attachment_metadata')) {
							wp_update_attachment_metadata($image_id, wp_generate_attachment_metadata($image_id, $new_file_path));
						}
						
						// Optionally, delete the original WEBP file
						@unlink($file_path);
					}
				}
			}
	
			$offset += $limit;
		}
	}

	/**
     * Convert all WEBP images (main and gallery) for each product to PNG.
     * 
     * @since    1.0.0
     */
    public function convert_all_webp_images_to_png() {
        $offset = 0;
        $limit = 50; // Number of products to process at a time

        while (true) {
            $products = wc_get_products(array(
                'status' => 'publish',
                'limit'  => $limit,
                'offset' => $offset,
            ));

            if (empty($products)) {
                break; // Exit the loop if no more products are found
            }

            foreach ($products as $product) {
                // Convert the main product image
                $this->convert_product_image($product->get_image_id());

                // Convert all gallery images
                $gallery_ids = $product->get_gallery_image_ids();
                foreach ($gallery_ids as $gallery_id) {
                    $this->convert_product_image($gallery_id);
                }
            }

            $offset += $limit; // Increment the offset for the next batch
        }
    }

	/**
	 * Converts a single product image from WEBP to PNG format.
	 *
	 * @since    1.0.0
	 * @param int $image_id The ID of the image to convert.
	 */
	private function convert_product_image($image_id) {
		$file_path = get_attached_file($image_id);

		if ($file_path && preg_match('/\.webp$/', $file_path)) {
			$new_file_path = preg_replace('/\.webp$/', '.png', $file_path);
			
			if ($this->convert_webp_to_png($file_path, $new_file_path)) {
				// Update the attachment file type post meta
				wp_update_attachment_metadata($image_id, wp_generate_attachment_metadata($image_id, $new_file_path));
				update_post_meta($image_id, '_wp_attached_file', $new_file_path);

				// Regenerate thumbnails if the function exists
				if (function_exists('wp_update_attachment_metadata')) {
					wp_update_attachment_metadata($image_id, wp_generate_attachment_metadata($image_id, $new_file_path));
				}

				// Optionally, delete the original WEBP file
				@unlink($file_path);

				return true; // Return true on success
			}
		}

		return false; // Return false if conversion did not take place
	}

	/**
     * Callback function for the General section.
     * 
     * @since    1.0.0
     */
	public function section_general_cb() {
		echo '<p>' . __('General settings for the Google Feed Generator.', 'smarty-google-feed-generator') . '</p>';
	}
	
	/**
     * Callback function for the Google Product Category field.
     * 
     * @since    1.0.0
     */
	public function google_product_category_cb() {
		$option = get_option('smarty_google_product_category');
		echo '<select name="smarty_google_product_category" class="smarty-select2-ajax">';
		if ($option) {
			echo '<option value="' . esc_attr($option) . '" selected>' . esc_html($option) . '</option>';
		}
		echo '</select>';
		echo '<p class="description">' . __('The <b>global</b> Google Product Category for all of the products.', 'smarty-google-feed-generator') . '</p>';
	}

	/**
     * Callback function for the Use Google Category ID field.
     * 
     * @since    1.0.0
     */
	public function google_category_as_id_cb() {
		$option = get_option('smarty_google_category_as_id');
		echo '<input type="checkbox" name="smarty_google_category_as_id" value="1" ' . checked(1, $option, false) . ' />';
		echo '<p class="description">' . __('Check to use Google Product Category ID in the feed instead of the name.', 'smarty-google-feed-generator') . '</p>';
	}

	public function category_mapping_field_cb() {
        $category_mapping = get_option('smarty_category_mapping', array());
		echo '<p class="description">' . __('Map your categories to the categories of your selected channel.', 'smarty-google-feed-generator') . '</p>';
        echo '<table class="form-table user-friendly-table"><tbody>';
        echo '<tr><th>' . __('WooCommerce Category', 'smarty-google-feed-generator') . '</th><th>' . __('Google Product Category', 'smarty-google-feed-generator') . '</th></tr>';
    
        $woocommerce_categories = get_terms(array('taxonomy' => 'product_cat', 'hide_empty' => false));
    
        foreach ($woocommerce_categories as $category) {
            $google_category = isset($category_mapping[$category->term_id]) ? $category_mapping[$category->term_id] : '';
            echo '<tr>';
            echo '<td>' . esc_html($category->name) . '</td>';
            echo '<td>';
            echo '<select name="smarty_category_mapping[' . esc_attr($category->term_id) . ']" class="smarty-select2-ajax">';
            echo '<option value="">' . __('Select a Google Category', 'smarty-google-feed-generator') . '</option>';
            foreach ($this->get_google_product_categories() as $google_cat) {
                $selected = $google_category === $google_cat ? 'selected' : '';
                echo '<option value="' . esc_attr($google_cat) . '" ' . $selected . '>' . esc_html($google_cat) . '</option>';
            }
            echo '</select>';
			echo '</td>';
            echo '</tr>';
		}
    
        echo '</tbody></table>';
	}

	/**
     * Callback function for the Convert Images section.
     * 
     * @since    1.0.0
     */
	public function section_convert_images_cb() {
		echo '<p>' . __('Use the buttons below to manually convert the first or all WebP image(s) of each products in to the feed to PNG format.', 'smarty-google-feed-generator') . '</p>';
	}
	
	/**
     * Callback function for the Generate Feeds section.
     * 
     * @since    1.0.0
     */
	public function section_generate_feeds_cb() {
		echo '<p>' . __('Use the buttons below to manually generate the feeds.', 'smarty-google-feed-generator') . '</p>';
	}
	
	/**
     * Callback function for the Meta Fields section.
     * 
     * @since    1.0.0
     */
	public function section_meta_fields_cb() {
		echo '<p>' . __('Use this settings for the Google Feed Generator If custom meta fields exists.', 'smarty-google-feed-generator') . '</p>';
	}
	
	/**
     * Callback function for the Cache section.
     * 
     * @since    1.0.0
     */
	public function section_settings_cb() {
		echo '<p>' . __('Cache settings for the Google Feed Generator.', 'smarty-google-feed-generator') . '</p>';
	}
	
	/**
     * Callback function for the Exclude Patterns field.
     * 
     * @since    1.0.0
     */
	public function exclude_patterns_cb() {
		$option = get_option('smarty_exclude_patterns');
		echo '<textarea name="smarty_exclude_patterns" rows="10" cols="50" class="large-text">' . esc_textarea($option) . '</textarea>';
		echo '<p class="description">' . __('Enter URL patterns to exclude from the TSV/CSV feed, one per line.', 'smarty-google-feed-generator') . '</p>';
	}
	
	/**
	 * Callback function to display the excluded categories field.
	 * 
	 * @since    1.0.0
	 */
	public function excluded_categories_cb() {
		$option = get_option('smarty_excluded_categories', array());
		$categories = get_terms(array(
			'taxonomy' => 'product_cat',
			'hide_empty' => false,
		));
	
		echo '<select name="smarty_excluded_categories[]" multiple="multiple" class="smarty-excluded-categories" style="width:50%;">';
		foreach ($categories as $category) {
			echo '<option value="' . esc_attr($category->term_id) . '" ' . (in_array($category->term_id, (array)$option) ? 'selected' : '') . '>' . esc_html($category->name) . '</option>';
		}
		echo '</select>';
		echo '<p class="description">' . __('Select product categories to exclude from the feed.', 'smarty-google-feed-generator') . '</p>';
	}

	/**
     * @since    1.0.0
     */
	public function custom_labels_table_cb() {
		$logics = [
			'older_than_days'       => __('Older Than Days', 'smarty-google-feed-generator'),
			'not_older_than_days'   => __('Not Older Than Days', 'smarty-google-feed-generator'),
			'most_ordered_days'     => __('Most Ordered in Last Days', 'smarty-google-feed-generator'),
			'high_rating_value'     => __('High Rating Value', 'smarty-google-feed-generator'),
			'category'              => __('In Selected Category', 'smarty-google-feed-generator'),
			'has_sale_price'        => __('Has Sale Price', 'smarty-google-feed-generator'),
		];
	
		$custom_labels = [
			'Custom Label 0' => ['logic', 'value', 'days', 'categories'],
			'Custom Label 1' => ['logic', 'value', 'days', 'categories'],
			'Custom Label 2' => ['logic', 'value', 'days', 'categories'],
			'Custom Label 3' => ['logic', 'value', 'days', 'categories'],
			'Custom Label 4' => ['logic', 'value', 'days', 'categories'],
		];
	
		echo '<p>' . __('Configure the logic and values for custom labels.', 'smarty-google-feed-generator') . '</p>';
		echo '<table class="form-table user-friendly-table"><tbody>';
		echo '<tr><th>' . __('Label Names', 'smarty-google-feed-generator') . '</th><th>' . __('Predefined Logic', 'smarty-google-feed-generator') . '</th><th>' . __('Parameter Fields', 'smarty-google-feed-generator') . '</th><th>' . __('Value Fields', 'smarty-google-feed-generator') . '</th></tr>';
	
		foreach ($custom_labels as $label => $details) {
			$logic_option = get_option('smarty_' . strtolower(str_replace(' ', '_', $label)) . '_logic', '');
			$value_option = get_option('smarty_' . strtolower(str_replace(' ', '_', $label)) . '_value', '');
			$days_option = get_option('smarty_' . strtolower(str_replace(' ', '_', $label)) . '_days', '');
			$categories_option = get_option('smarty_' . strtolower(str_replace(' ', '_', $label)) . '_categories', []);
	
			// Ensure $categories_option is an array
			if (!is_array($categories_option)) {
				$categories_option = explode(',', $categories_option);
			}
	
			echo '<tr>';
			echo '<td>' . esc_html($label) . '</td>';
			echo '<td>';
			echo '<select name="smarty_' . strtolower(str_replace(' ', '_', $label)) . '_logic" class="custom-label-logic">';
			foreach ($logics as $key => $logic) {
				$selected = $logic_option == $key ? 'selected' : '';
				echo '<option value="' . esc_attr($key) . '" ' . $selected . '>' . esc_html($logic) . '</option>';
			}
			echo '</select>';
			echo '</td>';
			echo '<td class="custom-label-input">';
			if (in_array($logic_option, ['older_than_days', 'not_older_than_days', 'most_ordered_days'])) {
				echo '<input type="number" name="smarty_' . strtolower(str_replace(' ', '_', $label)) . '_days" value="' . esc_attr($days_option) . '" class="small-text" />';
			} elseif (in_array($logic_option, ['high_rating_value', 'has_sale_price'])) {
				echo '<input type="number" name="smarty_' . strtolower(str_replace(' ', '_', $label)) . '_value" value="' . esc_attr($value_option) . '" class="small-text" />';
			} elseif ($logic_option === 'category') {
				echo '<select name="smarty_' . strtolower(str_replace(' ', '_', $label)) . '_categories[]" multiple="multiple" class="select2" style="width:50%;">';
				foreach (get_terms(['taxonomy' => 'product_cat', 'hide_empty' => false]) as $category) {
					$selected = in_array($category->term_id, $categories_option) ? 'selected' : '';
					echo '<option value="' . esc_attr($category->term_id) . '" ' . $selected . '>' . esc_html($category->name) . '</option>';
				}
				echo '</select>';
			} else {
				echo '<input type="text" name="smarty_' . strtolower(str_replace(' ', '_', $label)) . '_default" value="' . esc_attr($value_option) . '" class="regular-text" />';
			}
			echo '</td>';
			echo '<td class="custom-label-value">';
			echo '<input type="text" name="smarty_' . strtolower(str_replace(' ', '_', $label)) . '_value" value="' . esc_attr($value_option) . '" class="regular-text" />';
			echo '</td>';
			echo '</tr>';
		}
	
		echo '</tbody></table>';
	}	

	/**
     * Callback function for the convert images button.
     * 
     * @since    1.0.0
     */
	public function convert_images_button_cb() {
		echo '<button class="button secondary smarty-convert-images-button" style="display: inline-block;">' . __('First WebP Image to PNG', 'smarty-google-feed-generator') . '</button>';
		echo '<button class="button secondary smarty-convert-all-images-button" style="display: inline-block; margin: 0 10px;">' . __('All WebP Images to PNG', 'smarty-google-feed-generator') . '</button>';
	}
	
	/**
     * Callback function for the generate feed buttons.
     * 
     * @since    1.0.0
     */
	public function generate_feed_buttons_cb() {
		echo '<button class="button secondary smarty-generate-feed-button" data-feed-action="generate_bing_feed" style="display: inline-block; margin: 0 10px;">' . __('Bing Shopping Feed', 'smarty-google-feed-generator') . '</button>';
		echo '<button class="button secondary smarty-generate-feed-button" data-feed-action="generate_bing_txt_feed" style="display: inline-block;">' . __('Bing TXT Feed', 'smarty-google-feed-generator') . '</button>';
	}
	
	/**
     * Callback function for the Meta Title field.
     * 
     * @since    1.0.0
     */
	public function meta_title_field_cb() {
		$option = get_option('smarty_meta_title_field', 'meta-title');
		echo '<input type="text" name="smarty_meta_title_field" value="' . esc_attr($option) . '" class="regular-text" />';
		echo '<p class="description">' . __('Enter the custom field name for the product title meta.', 'smarty-google-feed-generator') . '</p>';
	}
	
	/**
     * Callback function for the Meta Description field.
     * 
     * @since    1.0.0
     */
	public function meta_description_field_cb() {
		$option = get_option('smarty_meta_description_field', 'meta-description');
		echo '<input type="text" name="smarty_meta_description_field" value="' . esc_attr($option) . '" class="regular-text" />';
		echo '<p class="description">' . __('Enter the custom field name for the product description meta.', 'smarty-google-feed-generator') . '</p>';
	}
	
	/**
     * Callback function for the Clear Cache field.
     * 
     * @since    1.0.0
     */
	public function clear_cache_cb() {
		$option = get_option('smarty_clear_cache');
		echo '<input type="checkbox" name="smarty_clear_cache" value="1" ' . checked(1, $option, false) . ' />';
		echo '<p class="description">' . __('Check to clear the cache each time the feed is generated. <br><small><em><b>Important:</b> <span style="color: #c51244;">Remove this in production to utilize caching.</span></em></small>', 'smarty-google-feed-generator') . '</p>';
	}
	
	/**
     * Callback function for the Cache Duration field.
     * 
     * @since    1.0.0
     */
	public function cache_duration_cb() {
		$option = get_option('smarty_cache_duration', 12); // Default to 12 hours if not set
		echo '<input type="number" name="smarty_cache_duration" value="' . esc_attr($option) . '" />';
		echo '<p class="description">' . __('Set the cache duration in hours.', 'smarty-google-feed-generator') . '</p>';
	}

	/**
	 * Callback function for the Google section field.
	 *
	 * @since    1.0.0
	 */
	public function section_tab_google_feed_cb() {
		echo '<p>' . __('Main column options for the Google Products feed.', 'smarty-google-feed-generator') . '</p>';
	}

	/**
	 * A common method to render the columns for both XML and CSV settings.
	 *
	 * @since    1.0.0
	 */
	public function render_columns_cb($option_name, $columns, $disabled_columns, $type) {
		$options = get_option($option_name, array());
	
		// Ensure $options is an array
		if (!is_array($options)) {
			$options = array();
		}
	
		echo '<p>' . __('Exclude columns from the TSV/CSV and XML feeds.', 'smarty-google-feed-generator') . '</p>';
		echo '<table class="form-table user-friendly-table"><tbody>';
		echo '<tr><th>' . __('Columns', 'smarty-google-feed-generator') . '</th></tr>';
	
		$column_count = count($columns);
		$columns_per_row = 5; // Adjust this value to change the number of columns per row
	
		echo '<tr><td>';
		echo '<table>';
	
		for ($i = 0; $i < $column_count; $i += $columns_per_row) {
			echo '<tr>';
			for ($j = 0; $j < $columns_per_row; $j++) {
				$index = $i + $j;
				if ($index < $column_count) {
					$column = $columns[$index];
					$checked = in_array($column, $options) ? 'checked' : '';
					$disabled = in_array($column, $disabled_columns) ? 'disabled' : '';
					$description = isset($this->column_descriptions[$column]) ? $this->column_descriptions[$column] : '';
	
					echo '<td><span class="tooltip dashicons dashicons-info"><span class="tooltiptext">' . esc_html($description) . '</span></span><label><input type="checkbox" id="' . esc_attr($column) . '" name="' . esc_attr($option_name) . '[]" value="' . esc_attr($column) . '" ' . $checked . ' ' . $disabled . '> ' . esc_html($column) . '</label></td>';
				} else {
					echo '<td></td>'; // Fill empty cells if columns are less than columns_per_row
				}
			}
			echo '</tr>';
		}
	
		echo '</table>';
		echo '</td></tr>';
	
		echo '</tbody></table>';
	}

	/**
     * @since    1.0.0
     */
	public function exclude_xml_columns_cb() {
		$feed_type = get_option('smarty_feed_type', 'google');
		list($columns, $disabled_columns) = $this->get_feed_columns($feed_type);
		$this->render_columns_cb('smarty_exclude_xml_columns', $columns, $disabled_columns, 'xml');
	}
	
	/**
     * @since    1.0.0
     */
	public function exclude_csv_columns_cb() {
		$feed_type = get_option('smarty_feed_type', 'google');
		list($columns, $disabled_columns) = $this->get_feed_columns($feed_type);
		$this->render_columns_cb('smarty_exclude_csv_columns', $columns, $disabled_columns, 'csv');
	}	

	/**
	 * Get the appropriate column names and excluded columns based on the feed type.
	 * 
     * @since    1.0.0
     */
	public function get_feed_columns($feed_type) {
		$columns = array(
			'ID', 
			'MPN', 
			'GTIN',
			'Title', 
			'Description', 
			'Product Type', 
			'Link',
			'Mobile Link', 
			'Image Link', 
			'Additional Image Link', 
			'Google Product Category',
			'Price', 
			'Sale Price', 
			'Bundle', 
			'Brand', 
			'Condition', 
			'Multipack', 
			'Color',
			'Gender', 
			'Material', 
			'Size', 
			'Size Type', 
			'Size System', 
			'Availability',
			'Custom Label 0', 
			'Custom Label 1', 
			'Custom Label 2', 
			'Custom Label 3', 
			'Custom Label 4',
			'Excluded Destination',
			'Included Destination',
			'Excluded Countries for Shopping Ads',
			'Shipping',
		);
	
		$disabled_columns = array(
			'ID', 
			'MPN',
			'Title', 
			'Description', 
			'Product Type', 
			'Link',
			'Image Link', 
			'Additional Image Link',
			'Google Product Category',
			'Price', 
			'Sale Price', 
			'Bundle', 
			'Brand', 
			'Condition',
			'Availability',
		);
	
		return array($columns, $disabled_columns);
	}

	/**
     * @since    1.0.0
     */
	private function is_field_excluded($field) {
		$exclude_xml_columns = get_option('smarty_exclude_xml_columns', array());
		$exclude_csv_columns = get_option('smarty_exclude_csv_columns', array());
	
		// Ensure these options are arrays
		if (!is_array($exclude_xml_columns)) {
			$exclude_xml_columns = array();
		}
		if (!is_array($exclude_csv_columns)) {
			$exclude_csv_columns = array();
		}
	
		return in_array($field, $exclude_xml_columns) && in_array($field, $exclude_csv_columns);
	}

    /**
	 * Function to check for the transient and displays a notice if it's present.
	 *
	 * @since    1.0.0
	 */
	public function success_notice() {
		if (get_transient('smarty_gfg_settings_updated')) { 
			?>
			<div class="notice notice-success smarty-auto-hide-notice">
				<p><?php echo esc_html__('Settings saved.', 'smarty-google-feed-generator'); ?></p>
			</div>
			<?php
			// Delete the transient so we don't keep displaying the notice
			delete_transient('smarty_gfg_settings_updated');
		}
	}
}