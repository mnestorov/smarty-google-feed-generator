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
     * Instance of Smarty_Gfg_API.
	 * 
     * @since    1.0.0
     * @var Smarty_Gfg_API
     */
    private $api_instance;

	/**
	 * The tsv/csv columns description.
	 * 
	 * @since    1.0.0
	 * @access   private
	 */
	private $csv_column_descriptions = array(
		'MPN' 			=> 'The Manufacturer Part Number for the product (e.g., GO12345OOGLE).',
		'Title' 		=> 'The title of the product (e.g., Mens Pique Polo Shirt).',
		'Description' 	=> 'The description of the product.',
		'Product Type' 	=> 'The product type or category (e.g., Home > Women > Dresses > Maxi Dresses).',
		'ID2' 			=> 'Secondary unique identifier for the product.',
		'Link' 			=> 'The URL of the product page (e.g., https://www.example.com/asp/sp.asp?cat=12&id=1030).',
		'Mobile Link' 	=> 'The mobile URL of the product page (e.g., https://www.m.example.com/asp/sp.asp?cat=12&id=1030).',
		'Image Link' 	=> 'The URL of the product image (e.g., https://www.example.com/image1.jpg).',
		'Bundle' 		=> 'Indicates if the product is a bundle (e.g., yes, no).',
	);
	
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

		// Instantiate the API class
        $this->api_instance = new Smarty_Gfg_API(CK_KEY, CS_KEY);
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
			'general' 		=> __('General', 'smarty-google-feed-generator'),
			'mapping'       => __('TSV/CSV Column Mapping', 'smarty-google-feed-generator'),
			'compatibility' => __('Plugin Compatibility', 'smarty-google-feed-generator'),
			'license' 		=> __('License', 'smarty-google-feed-generator')
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
			error_log("Unable to include: '$partial_file'");
		}
	}

	/**
	 * Initializes the plugin settings by registering the settings, sections, and fields.
	 *
	 * @since    1.0.0
	 */
	public function settings_init() {
		// General Settings
		register_setting('smarty_gfg_options_general', 'smarty_google_product_category');
		register_setting('smarty_gfg_options_general', 'smarty_google_category_as_id', array($this,'sanitize_checkbox'));
		register_setting('smarty_gfg_options_general', 'smarty_exclude_patterns', 'sanitize_textarea_field');
		register_setting('smarty_gfg_options_general', 'smarty_excluded_categories');
		register_setting('smarty_gfg_options_general', 'smarty_condition', array($this, 'sanitize_text_field'));
		register_setting('smarty_gfg_options_general', 'smarty_size_system');
		register_setting('smarty_gfg_options_general', 'smarty_excluded_destination', array($this, 'sanitize_excluded_destination'));
		register_setting('smarty_gfg_options_general', 'smarty_included_destination', array($this, 'sanitize_included_destination'));
		register_setting('smarty_gfg_options_general', 'smarty_excluded_countries_for_shopping_ads', array($this, 'sanitize_excluded_countries'));
			
		register_setting('smarty_gfg_options_general', 'smarty_custom_label_0_older_than_days', array($this,'sanitize_number_field'));
		register_setting('smarty_gfg_options_general', 'smarty_custom_label_0_older_than_value', 'sanitize_text_field');
		register_setting('smarty_gfg_options_general', 'smarty_custom_label_0_not_older_than_days', array($this,'sanitize_number_field'));
		register_setting('smarty_gfg_options_general', 'smarty_custom_label_0_not_older_than_value', 'sanitize_text_field');
		register_setting('smarty_gfg_options_general', 'smarty_custom_label_1_most_ordered_days', array($this,'sanitize_number_field'));
		register_setting('smarty_gfg_options_general', 'smarty_custom_label_1_most_ordered_value', 'sanitize_text_field');
		register_setting('smarty_gfg_options_general', 'smarty_custom_label_2_high_rating_value', 'sanitize_text_field');
		register_setting('smarty_gfg_options_general', 'smarty_custom_label_3_category');
		register_setting('smarty_gfg_options_general', 'smarty_custom_label_3_category_value', array($this,'sanitize_category_values'));
		register_setting('smarty_gfg_options_general', 'smarty_custom_label_4_sale_price_value', 'sanitize_text_field');
	
		register_setting('smarty_gfg_options_general', 'smarty_meta_title_field', 'sanitize_text_field');
		register_setting('smarty_gfg_options_general', 'smarty_meta_description_field', 'sanitize_text_field');
			
		register_setting('smarty_gfg_options_general', 'smarty_clear_cache', array($this,'sanitize_checkbox'));
		register_setting('smarty_gfg_options_general', 'smarty_cache_duration', array($this,'sanitize_number_field'));

		// Mapping Settings
		register_setting('smarty_gfg_options_mapping', 'smarty_gfg_settings_mapping', array($this, 'sanitize_mapping_settings'));

		// Compatibility Settings
		register_setting('smarty_gfg_options_compatibility', 'smarty_gfg_settings_compatibility', array($this, 'sanitize_compatibility_settings'));

		// License Settings
		register_setting('smarty_gfg_options_license', 'smarty_gfg_settings_license', array($this, 'sanitize_license_settings'));

		// Add General section
		add_settings_section(
			'smarty_gfg_section_general',                                   	// ID of the section
			__('General', 'smarty-google-feed-generator'),                  	// Title of the section
			array($this,'section_general_cb'),                          		// Callback function that fills the section with the desired content
			'smarty_gfg_options_general'                                		// Page on which to add the section
		);

		add_settings_section(
			'smarty_gfg_section_custom_labels',                             	// ID of the section
			__('Custom Labels', 'smarty-google-feed-generator'),            	// Title of the section
			array($this,'section_custom_labels_cb'),                    		// Callback function that fills the section with the desired content
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
			__('Meta Fields', 'smarty-google-feed-generator'),              	// Title of the section
			array($this,'section_meta_fields_cb'),                      		// Callback function that fills the section with the desired content
			'smarty_gfg_options_general'                                		// Page on which to add the section
		);
	
		add_settings_section(
			'smarty_gfg_section_settings',                                  	// ID of the section
			__('Cache', 'smarty-google-feed-generator'),                    	// Title of the section
			array($this,'section_settings_cb'),                         		// Callback function that fills the section with the desired content
			'smarty_gfg_options_general'                                		// Page on which to add the section
		);
	
		// Add settings fields
		add_settings_field(
			'smarty_google_product_category',                               	// ID of the field
			__('Google Product Category', 'smarty-google-feed-generator'),  	// Title of the field
			array($this,'google_product_category_cb'),                      	// Callback function to display the field
			'smarty_gfg_options_general',                               		// Page on which to add the field
			'smarty_gfg_section_general'                                    	// Section to which this field belongs
		);
	
		add_settings_field(
			'smarty_google_category_as_id',                                 	// ID of the field
			__('Use Google Category ID', 'smarty-google-feed-generator'),   	// Title of the field
			array($this,'google_category_as_id_cb'),                        	// Callback function to display the field
			'smarty_gfg_options_general',                               		// Page on which to add the field
			'smarty_gfg_section_general'                                    	// Section to which this field belongs
		);
	
		add_settings_field(
			'smarty_exclude_patterns',                                      	// ID of the field
			__('Exclude Patterns', 'smarty-google-feed-generator'),         	// Title of the field
			array($this,'exclude_patterns_cb'),                            		// Callback function to display the field
			'smarty_gfg_options_general',                               		// Page on which to add the field
			'smarty_gfg_section_general'                                    	// Section to which this field belongs
		);
	
		add_settings_field(
			'smarty_excluded_categories',                                   	// ID of the field
			__('Excluded Categories', 'smarty-google-feed-generator'),      	// Title of the field
			array($this,'excluded_categories_cb'),                          	// Callback function to display the field
			'smarty_gfg_options_general',                               		// Page on which to add the field
			'smarty_gfg_section_general'                                    	// Section to which this field belongs
		);

		add_settings_field(
			'smarty_condition',													// ID of the field
			__('Condition', 'smarty-google-feed-generator'),					// Title of the field
			array($this, 'condition_field_cb'),									// Callback function to display the field
			'smarty_gfg_options_general',										// Page on which to add the field
			'smarty_gfg_section_general'										// Section to which this field belongs
		);

		add_settings_field(
			'smarty_size_system',												// ID of the field
			__('Size System', 'smarty-google-feed-generator'),					// Title of the field
			array($this, 'size_system_cb'),										// Callback function to display the field
			'smarty_gfg_options_general',										// Page on which to add the field
			'smarty_gfg_section_general'										// Section to which this field belongs
		);		

		add_settings_field(
			'smarty_excluded_destination',                                		// ID of the field
			__('Excluded Destination', 'smarty-google-feed-generator'),   		// Title of the field
			array($this, 'excluded_destination_cb'),                      		// Callback function to display the field
			'smarty_gfg_options_general',                                 		// Page on which to add the field
			'smarty_gfg_section_general'                                 	 	// Section to which this field belongs
		);
	
		add_settings_field(
			'smarty_included_destination',                                		// ID of the field
			__('Included Destination', 'smarty-google-feed-generator'),   		// Title of the field
			array($this, 'included_destination_cb'),                      		// Callback function to display the field
			'smarty_gfg_options_general',                                 		// Page on which to add the field
			'smarty_gfg_section_general'                                  		// Section to which this field belongs
		);
	
		add_settings_field(
			'smarty_excluded_countries_for_shopping_ads',                 		// ID of the field
			__('Excluded Countries for Shopping Ads', 'smarty-google-feed-generator'), // Title of the field
			array($this, 'excluded_countries_cb'),                        		// Callback function to display the field
			'smarty_gfg_options_general',                                 		// Page on which to add the field
			'smarty_gfg_section_general'                                  		// Section to which this field belongs
		);
			
		add_settings_field(
			'smarty_custom_label_0_older_than_days',                        	// ID of the field
			__('Older Than (Days)', 'smarty-google-feed-generator'),        	// Title of the field
			array($this,'custom_label_days_cb'),                            	// Callback function to display the field
			'smarty_gfg_options_general',                               		// Page on which to add the field
			'smarty_gfg_section_custom_labels',                             	// Section to which this field belongs
			['label' => 'smarty_custom_label_0_older_than_days']
		);
	
		add_settings_field(
			'smarty_custom_label_0_older_than_value',                       	// ID of the field
			__('Older Than Value', 'smarty-google-feed-generator'),         	// Title of the field
			array($this,'custom_label_value_cb'),                           	// Callback function to display the field
			'smarty_gfg_options_general',                               		// Page on which to add the field
			'smarty_gfg_section_custom_labels',                             	// Section to which this field belongs
			['label' => 'smarty_custom_label_0_older_than_value']
		);
	
		add_settings_field(
			'smarty_custom_label_0_not_older_than_days',                    	// ID of the field
			__('Not Older Than (Days)', 'smarty-google-feed-generator'),    	// Title of the field
			array($this,'custom_label_days_cb'),                            	// Callback function to display the field
			'smarty_gfg_options_general',                               		// Page on which to add the field
			'smarty_gfg_section_custom_labels',                             	// Section to which this field belongs
			['label' => 'smarty_custom_label_0_not_older_than_days']
		);
	
		add_settings_field(
			'smarty_custom_label_0_not_older_than_value',                   	// ID of the field
			__('Not Older Than Value', 'smarty-google-feed-generator'),     	// Title of the field
			array($this,'custom_label_value_cb'),                           	// Callback function to display the field
			'smarty_gfg_options_general',                               		// Page on which to add the field
			'smarty_gfg_section_custom_labels',                             	// Section to which this field belongs
			['label' => 'smarty_custom_label_0_not_older_than_value']
		);
	
		add_settings_field(
			'smarty_custom_label_1_most_ordered_days',                          // ID of the field
			__('Most Ordered in Last (Days)', 'smarty-google-feed-generator'),  // Title of the field
			array($this,'custom_label_days_cb'),                                // Callback function to display the field
			'smarty_gfg_options_general',                                   	// Page on which to add the field
			'smarty_gfg_section_custom_labels',                                 // Section to which this field belongs
			['label' => 'smarty_custom_label_1_most_ordered_days']
		);
	
		add_settings_field(
			'smarty_custom_label_1_most_ordered_value',                     	// ID of the field
			__('Most Ordered Value', 'smarty-google-feed-generator'),       	// Title of the field
			array($this,'custom_label_value_cb'),                           	// Callback function to display the field
			'smarty_gfg_options_general',                               		// Page on which to add the field
			'smarty_gfg_section_custom_labels',                             	// Section to which this field belongs
			['label' => 'smarty_custom_label_1_most_ordered_value']
		);
	
		add_settings_field(
			'smarty_custom_label_2_high_rating_value',                      	// ID of the field
			__('High Rating Value', 'smarty-google-feed-generator'),        	// Title of the field
			array($this,'custom_label_value_cb'),                           	// Callback function to display the field
			'smarty_gfg_options_general',                               		// Page on which to add the field
			'smarty_gfg_section_custom_labels',                             	// Section to which this field belongs
			['label' => 'smarty_custom_label_2_high_rating_value']
		);
	
		add_settings_field(
			'smarty_custom_label_3_category',                               	// ID of the field
			__('Category', 'smarty-google-feed-generator'),                 	// Title of the field
			array($this,'custom_label_category_cb'),                        	// Callback function to display the field
			'smarty_gfg_options_general',                               		// Page on which to add the field
			'smarty_gfg_section_custom_labels',                             	// Section to which this field belongs
			['label' => 'smarty_custom_label_3_category']
		);
	
		add_settings_field(
			'smarty_custom_label_3_category_value',                         	// ID of the field
			__('Category Value', 'smarty-google-feed-generator'),           	// Title of the field
			array($this,'custom_label_value_cb'),                           	// Callback function to display the field
			'smarty_gfg_options_general',                               		// Page on which to add the field
			'smarty_gfg_section_custom_labels',                             	// Section to which this field belongs
			['label' => 'smarty_custom_label_3_category_value']
		);
	
		add_settings_field(
			'smarty_custom_label_4_sale_price_value',                       	// ID of the field
			__('Sale Price Value', 'smarty-google-feed-generator'),         	// Title of the field
			array($this,'custom_label_value_cb'),                           	// Callback function to display the field
			'smarty_gfg_options_general',                               		// Page on which to add the field
			'smarty_gfg_section_custom_labels',                             	// Section to which this field belongs
			['label' => 'smarty_custom_label_4_sale_price_value']
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
	
		// Add settings fields to Meta Fields section
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

		add_settings_section(
			'smarty_gfg_section_mapping',										// ID of the section
			__('TSV/CSV Column Mapping', 'smarty-google-feed-generator'),		// Title of the section  
			array($this, 'section_mapping_cb'),									// Callback function that fills the section with the desired content
			'smarty_gfg_options_mapping'										// Page on which to add the section
		);

		$csv_columns = array(
			'MPN', 																// Example custom value: SKU
			'Title', 															// Example custom value: Item Title
			'Description', 														// Example custom value: Item Description
			'Product Type', 													// Example custom value: Item Category
			'Link', 															// Example custom value: Final URL 	
			'Mobile Link', 														// Example custom value: Final Mobile URL				
			'Image Link', 														// Example custom value: Image URL
			'Bundle', 															// Example custom value: Is Bundle
		);
	
		foreach ($csv_columns as $column) {
			add_settings_field(
				'smarty_gfg_mapping_' . $column,								// ID of the field
				__($column, 'smarty-google-feed-generator'),					// Title of the field
				array($this, 'csv_mapping_fields_cb'),							// Callback function to display the field
				'smarty_gfg_options_mapping',									// Page on which to add the field
				'smarty_gfg_section_mapping',									// Section to which this field belongs
				array('column' => $column)
			);
		}

		add_settings_section(
			'smarty_gfg_section_compatibility',									// ID of the section
			__('Compatibility', 'smarty-google-feed-generator'),    			// Title of the section  		
			array($this, 'section_compatibility_cb'),                			// Callback function that fills the section with the desired content	
			'smarty_gfg_options_compatibility'                   				// Page on which to add the section   		
		);                            	
	
		add_settings_section(
			'smarty_gfg_section_license',										// ID of the section
			__('License', 'smarty-google-feed-generator'),						// Title of the section  
			array($this, 'section_license_cb'),									// Callback function that fills the section with the desired content
			'smarty_gfg_options_license'										// Page on which to add the section
		);

		add_settings_field(
			'smarty_api_key',													// ID of the field
			__('API Key', 'smarty-google-feed-generator'),						// Title of the field
			array($this, 'field_api_key_cb'),									// Callback function to display the field
			'smarty_gfg_options_license',										// Page on which to add the field
			'smarty_gfg_section_license'										// Section to which this field belongs
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
	 * Sanitize the "Excluded Countries for Shopping Ads" option.
	 *
	 * @since 1.0.0
	 * @param string $input The input value.
	 * @return string Sanitized value.
	 */
	public function sanitize_excluded_countries($input) {
		$input = strtoupper(trim($input));
		if (preg_match('/^[A-Z]{2}$/', $input)) {
			return $input;
		}
		add_settings_error('smarty_excluded_countries', 'invalid_country_code', __('Please enter a valid 2-character ISO 3166-1 alpha-2 country code.', 'smarty-google-feed-generator'));
		return '';
	}

	/**
     * Handle AJAX request to convert images.
     * 
     * @since    1.0.0
     */
	public function handle_ajax_convert_images() {
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
	 * Callback function for the Condition field.
	 * 
     * @since    1.0.0
     */
	public function condition_field_cb() {
		$option = get_option('smarty_condition', 'new');
		$options = array(
			'new' 		  => __('New', 'smarty-google-feed-generator'),
			'refurbished' => __('Refurbished', 'smarty-google-feed-generator'),
			'used' 		  => __('Used', 'smarty-google-feed-generator')
		);
		echo '<select name="smarty_condition">';
		foreach ($options as $value => $label) {
			echo '<option value="' . esc_attr($value) . '" ' . selected($option, $value, false) . '>' . esc_html($label) . '</option>';
		}
		echo '</select>';
		echo '<p class="description">' . __('Select the default condition for the products.', 'smarty-google-feed-generator') . '</p>';
	}

	/**
	 * Callback function for the Size System field.
	 *
	 * @since 1.0.0
	 */
	public function size_system_cb() {
		$option = get_option('smarty_size_system', '');
		$size_systems = ['US', 'UK', 'EU', 'DE', 'FR', 'JP', 'CN', 'IT', 'BR', 'MEX', 'AU'];
		
		echo '<select name="smarty_size_system">';
		foreach ($size_systems as $system) {
			echo '<option value="' . esc_attr($system) . '" ' . selected($option, $system, false) . '>' . esc_html($system) . '</option>';
		}
		echo '</select>';
		echo '<p class="description">' . __('Select a size system.', 'smarty-google-feed-generator') . '</p>';
	}

	/**
     * @since    1.0.0
     */
	public function excluded_destination_cb() {
		$options = get_option('smarty_excluded_destination', []);
		$destinations = ['Shopping_ads', 'Buy_on_Google_listings', 'Display_ads', 'Local_inventory_ads', 'Free_listings', 'Free_local_listings', 'YouTube_Shopping'];
		echo '<select name="smarty_excluded_destination[]" multiple="multiple" class="smarty-excluded-destination">';
		foreach ($destinations as $destination) {
			echo '<option value="' . esc_attr($destination) . '" ' . (in_array($destination, $options) ? 'selected' : '') . '>' . esc_html($destination) . '</option>';
		}
		echo '</select>';
		echo '<p class="description">' . __('A setting that you can use to exclude a product from participating in a specific type of advertising campaign.', 'smarty-google-feed-generator') . '</p>';
	}
	
	/**
     * @since    1.0.0
     */
	public function included_destination_cb() {
		$options = get_option('smarty_included_destination', []);
		$destinations = ['Shopping_ads', 'Buy_on_Google_listings', 'Display_ads', 'Local_inventory_ads', 'Free_listings', 'Free_local_listings', 'YouTube_Shopping'];
		echo '<select name="smarty_included_destination[]" multiple="multiple" class="smarty-included-destination">';
		foreach ($destinations as $destination) {
			echo '<option value="' . esc_attr($destination) . '" ' . (in_array($destination, $options) ? 'selected' : '') . '>' . esc_html($destination) . '</option>';
		}
		echo '</select>';
		echo '<p class="description">' . __('A setting that you can use to include a product from participating in a specific type of advertising campaign.', 'smarty-google-feed-generator') . '</p>';
	}
	
	/**
     * @since    1.0.0
     */
	public function excluded_countries_cb() {
		$option = get_option('smarty_excluded_countries_for_shopping_ads', '');
		echo '<input type="text" name="smarty_excluded_countries_for_shopping_ads" value="' . esc_attr($option) . '" class="regular-text" maxlength="2" />';
		echo '<p class="description">' . __('A setting that allows you to exclude countries where your products are advertised on Shopping ads. Enter <a href="https://en.wikipedia.org/wiki/ISO_3166-1_alpha-2" target="_blank">ISO 3166-1 alpha-2</a> country codes separated by commas. <br><b>Example:</b> US', 'smarty-google-feed-generator') . '</p>';
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
     * Check if the API key is valid.
     * 
     * @since    1.0.0
     * @param string $api_key The API key to validate.
     * @return bool True if the API key is valid, false otherwise.
     */
    private function is_valid_api_key($api_key) {
		$response = $this->api_instance->validate_license($api_key);
	
		if (isset($response['success']) && $response['success']) {
			$isActive = false;
			$activations = $response['data']['activationData'] ?? [];
	
			foreach ($activations as $activation) {
				if (empty($activation['deactivated_at'])) {
					$isActive = true;
					break;
				}
			}
	
			//error_log('Checking API key validity: ' . $api_key);
			//error_log('API Response: ' . print_r($response, true));
			//error_log('License is ' . ($isActive ? 'active' : 'inactive'));
			return $isActive;
		}
	
		return false;
	}

	/**
     * Handle license status check.
     * 
     * @since    1.0.0
     * @param string $option_name The name of the option.
     * @param mixed $old_value The old value of the option.
     * @param mixed $value The new value of the option.
     */
	public function handle_license_status_check($option_name, $old_value, $value) {
		if (!$this->api_instance) {
			// Handle the error
			return;
		}
	
		if ($option_name == 'smarty_gfg_settings_license' && isset($value['api_key'])) {
			$api_key = $value['api_key'];
	
			// Check the license status
			$isValid = $this->is_valid_api_key($api_key);
	
			// Add an admin notice based on the validity of the license
			if ($isValid) {
				// Add query arg or admin notice for valid license
				add_query_arg('license-valid', 'true');
			} else {
				// Add query arg or admin notice for invalid license
				add_query_arg('license-invalid', 'true');
			}
		}
	}

	/**
     * Sanitize mapping settings.
     * 
	 * @since    1.0.0
     * @param array $input The input array.
     * @return array Sanitized array.
     */
    public function sanitize_mapping_settings($input) {
        $new_input = array();
        foreach ($input as $key => $value) {
            $new_input[$key] = sanitize_text_field($value);
        }
        return $new_input;
    }

    /**
     * Sanitizes the plugin Compatibility settings - 
	 * validates and sanitizes user input for the settings.
     *
	 * @since    1.0.0
     * @param array $input The input settings array.
     * @return array Sanitized settings.
     */
	public function sanitize_compatibility_settings($input) {
		$new_input = array();
	
		// TODO
	
		return $new_input;
	}

    /**
     * Sanitizes the plugin License settings.
     *
     * Validates and sanitizes user input for the settings.
     *
	 * @since    1.0.0
     * @param array $input The input settings array.
     * @return array Sanitized settings.
     */
	public function sanitize_license_settings($input) {
		$new_input = array();
		if (isset($input['api_key'])) {
			$new_input['api_key'] = sanitize_text_field($input['api_key']);
		}
		return $new_input;
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
		echo '<p class="description">' . __('The Google Product Category for the product. <br><b>Example:</b> Apparel & Accessories > Clothing > Outerwear > Coats & Jackets', 'smarty-google-feed-generator') . '</p>';
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
	
	/**
     * Callback function for the Custom Labels section.
     * 
     * @since    1.0.0
     */
	public function section_custom_labels_cb() {
		echo '<p>' . __('Define default values for custom labels.', 'smarty-google-feed-generator') . '</p>';
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
		echo '<p>' . __('Meta fields settings for the Google Feed Generator.', 'smarty-google-feed-generator') . '</p>';
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
     * Callback function for custom label days field.
     * 
     * @since    1.0.0
     * @param array $args Arguments for the callback.
     */
	public function custom_label_days_cb($args) {
		$option = get_option($args['label'], 30);
		$days = [10, 20, 30, 60, 90, 120];
		echo '<select name="' . esc_attr($args['label']) . '">';
		foreach ($days as $day) {
			echo '<option value="' . esc_attr($day) . '" ' . selected($option, $day, false) . '>' . esc_html($day) . '</option>';
		}
		echo '</select>';
	}
	
	/**
     * Callback function for custom label value field.
     * 
     * @since    1.0.0
     * @param array $args Arguments for the callback.
     */
	public function custom_label_value_cb($args) {
		$option = get_option($args['label'], '');
		echo '<input type="text" name="' . esc_attr($args['label']) . '" value="' . esc_attr($option) . '" class="regular-text" />';
		
		// Add custom descriptions based on the label
		switch ($args['label']) {
			case 'smarty_custom_label_0_older_than_value':
				echo '<p class="description">' . __('Enter the value to label products older than the specified number of days.', 'smarty-google-feed-generator') . '</p>';
				break;
			case 'smarty_custom_label_0_not_older_than_value':
				echo '<p class="description">' . __('Enter the value to label products not older than the specified number of days.', 'smarty-google-feed-generator') . '</p>';
				break;
			case 'smarty_custom_label_1_most_ordered_value':
				echo '<p class="description">' . __('Enter the value to label the most ordered products in the last specified days.', 'smarty-google-feed-generator') . '</p>';
				break;
			case 'smarty_custom_label_2_high_rating_value':
				echo '<p class="description">' . __('Enter the value to label products with high ratings.', 'smarty-google-feed-generator') . '</p>';
				break;
			case 'smarty_custom_label_3_category_value':
				echo '<p class="description">' . __('Enter custom values for the categories separated by commas. <br><b>Example:</b> Tech, Apparel, Literature <br><small><em><strong>Important:</strong> <span style="color: #c51244;">Ensure these values are in the same order as the selected categories.', 'smarty-google-feed-generator') . '</span></em></small></p>';
				break;
			case 'smarty_custom_label_4_sale_price_value':
				echo '<p class="description">' . __('Enter the value to label products with a sale price.', 'smarty-google-feed-generator') . '</p>';
				break;
			default:
				echo '<p class="description">' . __('Enter a custom value for this label.', 'smarty-google-feed-generator') . '</p>';
				break;
		}
	}

	/**
     * Callback function for custom label category field.
     * 
     * @since    1.0.0
     * @param array $args Arguments for the callback.
     */
	public function custom_label_category_cb($args) {
		$option = get_option($args['label'], []);
		$categories = get_terms([
			'taxonomy' => 'product_cat',
			'hide_empty' => false,
		]);
		
		echo '<select name="' . esc_attr($args['label']) . '[]" multiple="multiple" class="smarty-excluded-categories">';
		foreach ($categories as $category) {
			echo '<option value="' . esc_attr($category->term_id) . '" ' . (in_array($category->term_id, (array) $option) ? 'selected' : '') . '>' . esc_html($category->name) . '</option>';
		}
		echo '</select>';
		
		// Add description for the category selection
		if ($args['label'] === 'smarty_custom_label_3_category') {
			echo '<p class="description">' . __('Select one or multiple categories from the list.', 'smarty-google-feed-generator') . '<br><small><em><b>' . __('Important: ', 'smarty-google-feed-generator') . '</b><span style="color: #c51244;">' . __('Ensure the values for each category are entered in the same order in the Category Value field.', 'smarty-google-feed-generator') . '</span></em></small></p>';
		}
	}

	/**
     * Display the settings field with the values properly trimmed.
	 * 
	 * @since    1.0.0
     */
    public function display_category_values_field() {
        $category_values = get_option('smarty_custom_label_3_category_value', '');
        if (!is_array($category_values)) {
            $category_values = explode(',', $category_values);
        }
        $category_values = array_map('trim', $category_values);
        $category_values = implode(', ', $category_values);
        echo '<input type="text" id="smarty_custom_label_3_category_value" name="smarty_custom_label_3_category_value" value="' . esc_attr($category_values) . '" class="regular-text" />';
        echo '<p class="description">' . __('Enter custom values for the categories separated by commas.', 'smarty-google-feed-generator') . '<strong>' . __('Example: ', 'smarty-google-feed-generator') . '</strong>' . __('Tech, Apparel, Literature', 'smarty-google-feed-generator') . '</p>';
        echo '<p class="description"><strong>' . __('Important:', 'smarty-google-feed-generator') . '</strong>' . __('Ensure these values are in the same order as the selected categories.', 'smarty-google-feed-generator') . '</p>';
    }
	
	/**
     * Callback function for the convert images button.
     * 
     * @since    1.0.0
     */
	public function convert_images_button_cb() {
		echo '<button class="button secondary smarty-convert-images-button" style="display: inline-block;">' . __('Convert First WebP Image to PNG', 'smarty-google-feed-generator') . '</button>';
		echo '<button class="button secondary smarty-convert-all-images-button" style="display: inline-block; margin: 0 10px;">' . __('Convert All WebP Images to PNG', 'smarty-google-feed-generator') . '</button>';
	}
	
	/**
     * Callback function for the generate feed buttons.
     * 
     * @since    1.0.0
     */
	public function generate_feed_buttons_cb() {
		echo '<button class="button secondary smarty-generate-feed-button" data-feed-action="generate_product_feed" style="display: inline-block;">' . __('Generate Product Feed', 'smarty-google-feed-generator') . '</button>';
		echo '<button class="button secondary smarty-generate-feed-button" data-feed-action="generate_reviews_feed" style="display: inline-block; margin: 0 10px;">' . __('Generate Reviews Feed', 'smarty-google-feed-generator') . '</button>';
		echo '<button class="button secondary smarty-generate-feed-button" data-feed-action="generate_csv_export" style="display: inline-block; margin-right: 10px;">' . __('Generate CSV Export', 'smarty-google-feed-generator') . '</button>';
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
	 * Callback function for the Mapping section field.
	 *
	 * @since    1.0.0
	 */
	public function section_mapping_cb() {
		echo '<p>' . __('Configure the mapping of TSV/CSV column headers to WooCommerce product attributes.', 'smarty-google-feed-generator') . '</p>';
	}

	/**
     * Callback function for rendering the TSV/CSV mapping fields.
     * 
     * @since 1.0.0
     */
    public function csv_mapping_fields_cb($args) {
        $column = $args['column'];
        $options = get_option('smarty_gfg_settings_mapping', array());
        $new_value = isset($options[$column]) ? $options[$column] : '';
		echo '<input type="text" id="smarty_gfg_settings_mapping_' . esc_attr($column) . '" name="smarty_gfg_settings_mapping[' . esc_attr($column) . ']" value="' . esc_attr($new_value) . '" class="regular-text" />';
	}

	/**
	 * Callback function for the Compatibility section field.
	 *
	 * @since    1.0.0
	 */
	public static function section_compatibility_cb() {
		return smarty_check_compatibility();
	}

    /**
     * Callback function for the License section.
     * 
     * @since    1.0.0
     * @param array $args Arguments for the callback.
     */
	public function section_license_cb($args) {
		?>
		<p id="<?php echo esc_attr($args['id']); ?>">
			<?php echo esc_html__('Enter your API key to enable advanced features.', 'smarty-google-feed-generator'); ?>
		</p>
		<?php
	}

    /**
     * Callback function for the API key field.
     * 
     * @since    1.0.0
     * @param array $args Arguments for the callback.
     */
	public function field_api_key_cb($args) {
		$options = get_option('smarty_gfg_settings_license');
		?>
		<input type="text" id="smarty_api_key" name="smarty_gfg_settings_license[api_key]" size="30" value="<?php echo isset($options['api_key']) ? esc_attr($options['api_key']) : ''; ?>">
		<p class="description">
			<?php echo esc_html__('Enter a valid API key.', 'smarty-google-feed-generator'); ?>
		</p>
		<?php
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

    /**
     * Function to check for transients and other conditions to display admin notice.
     *
     * @since    1.0.0
     */
    public function admin_notice() {
        $options = get_option('smarty_gfg_settings_general');
		
		if (isset($_GET['license-activated']) && $_GET['license-activated'] == 'true') {
			?>
			<div class="notice notice-success smarty-auto-hide-notice">
				<p><?php echo esc_html__('License activated successfully.', 'smarty-google-feed-generator'); ?></p>
			</div>
			<?php
		}
    }
}