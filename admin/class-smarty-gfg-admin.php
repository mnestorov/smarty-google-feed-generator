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
     * @var Smarty_Gfg_API
     */
    private $api_instance;

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
        wp_enqueue_script('select2', 'https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/js/select2.min.js', array('jquery', 'select2'), '4.0.13', true);
		wp_enqueue_script($this->plugin_name, plugin_dir_url(__FILE__) . 'js/smarty-gfg-admin.js', array('jquery'), $this->version, false);
        wp_localize_script(
            'smarty-admin-js',
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
	 */
	public function add_settings_page() {
		add_options_page(
			'Google Feed Generator | Settings',         // Page title
			'Google Feed Generator',                    // Menu title
			'manage_options',                           // Capability required to access this page
			'smarty-feed-generator-settings',           // Menu slug
			'smarty_feed_generator_settings_page_html'  // Callback function to display the page content
		);
	}

	/**
	 * Register plugin settings.
	 */
	public function register_settings() {
		// General Settings
		register_setting('smarty_feed_generator_settings', 'smarty_google_product_category');
		register_setting('smarty_feed_generator_settings', 'smarty_google_category_as_id', 'sanitize_checkbox');
		register_setting('smarty_feed_generator_settings', 'smarty_exclude_patterns', 'sanitize_textarea_field');
		register_setting('smarty_feed_generator_settings', 'smarty_excluded_categories');
			
		// Custom Label Settings
		register_setting('smarty_feed_generator_settings', 'smarty_custom_label_0_older_than_days', 'sanitize_number_field');
		register_setting('smarty_feed_generator_settings', 'smarty_custom_label_0_older_than_value', 'sanitize_text_field');
		register_setting('smarty_feed_generator_settings', 'smarty_custom_label_0_not_older_than_days', 'sanitize_number_field');
		register_setting('smarty_feed_generator_settings', 'smarty_custom_label_0_not_older_than_value', 'sanitize_text_field');
		register_setting('smarty_feed_generator_settings', 'smarty_custom_label_1_most_ordered_days', 'sanitize_number_field');
		register_setting('smarty_feed_generator_settings', 'smarty_custom_label_1_most_ordered_value', 'sanitize_text_field');
		register_setting('smarty_feed_generator_settings', 'smarty_custom_label_2_high_rating_value', 'sanitize_text_field');
		register_setting('smarty_feed_generator_settings', 'smarty_custom_label_3_category');
		register_setting('smarty_feed_generator_settings', 'smarty_custom_label_3_category_value', 'sanitize_category_values');
		register_setting('smarty_feed_generator_settings', 'smarty_custom_label_4_sale_price_value', 'sanitize_text_field');
	
		// Meta Fields
		register_setting('smarty_feed_generator_settings', 'smarty_meta_title_field', 'sanitize_text_field');
		register_setting('smarty_feed_generator_settings', 'smarty_meta_description_field', 'sanitize_text_field');
			
		// Cache Settings
		register_setting('smarty_feed_generator_settings', 'smarty_clear_cache', 'sanitize_checkbox');
		register_setting('smarty_feed_generator_settings', 'smarty_cache_duration', 'sanitize_number_field');
	
		// Add General section
		add_settings_section(
			'smarty_gfg_section_general',                                   // ID of the section
			__('General', 'smarty-google-feed-generator'),                  // Title of the section
			'section_general_cb',                          					// Callback function that fills the section with the desired content
			'smarty_feed_generator_settings'                                // Page on which to add the section
		);
	
		// Add Custom Labels section
		add_settings_section(
			'smarty_gfg_section_custom_labels',                             // ID of the section
			__('Custom Labels', 'smarty-google-feed-generator'),            // Title of the section
			'section_custom_labels_cb',                    					// Callback function that fills the section with the desired content
			'smarty_feed_generator_settings'                                // Page on which to add the section
		);
	
		// Add Convert Images section
		add_settings_section(
			'smarty_gfg_section_convert_images',                            // ID of the section
			__('Convert Images', 'smarty-google-feed-generator'),           // Title of the section
			'section_convert_images_cb',                   					// Callback function that fills the section with the desired content
			'smarty_feed_generator_settings'                                // Page on which to add the section
		);
	
		// Add Generate Feeds section
		add_settings_section(
			'smarty_gfg_section_generate_feeds',                            // ID of the section
			__('Generate Feeds', 'smarty-google-feed-generator'),           // Title of the section
			'section_generate_feeds_cb',                   					// Callback function that fills the section with the desired content
			'smarty_feed_generator_settings'                                // Page on which to add the section
		);
	
		// Add Meta Fields section
		add_settings_section(
			'smarty_gfg_section_meta_fields',                               // ID of the section
			__('Meta Fields', 'smarty-google-feed-generator'),              // Title of the section
			'section_meta_fields_cb',                      					// Callback function that fills the section with the desired content
			'smarty_feed_generator_settings'                                // Page on which to add the section
		);
	
		// Add Settings section
		add_settings_section(
			'smarty_gfg_section_settings',                                  // ID of the section
			__('Cache', 'smarty-google-feed-generator'),                    // Title of the section
			'section_settings_cb',                         					// Callback function that fills the section with the desired content
			'smarty_feed_generator_settings'                                // Page on which to add the section
		);
	
		// Add settings fields
		add_settings_field(
			'smarty_google_product_category',                               // ID of the field
			__('Google Product Category', 'smarty-google-feed-generator'),  // Title of the field
			'google_product_category_cb',                      				// Callback function to display the field
			'smarty_feed_generator_settings',                               // Page on which to add the field
			'smarty_gfg_section_general'                                    // Section to which this field belongs
		);
	
		add_settings_field(
			'smarty_google_category_as_id',                                 // ID of the field
			__('Use Google Category ID', 'smarty-google-feed-generator'),   // Title of the field
			'google_category_as_id_cb',                        				// Callback function to display the field
			'smarty_feed_generator_settings',                               // Page on which to add the field
			'smarty_gfg_section_general'                                    // Section to which this field belongs
		);
	
		add_settings_field(
			'smarty_exclude_patterns',                                      // ID of the field
			__('Exclude Patterns', 'smarty-google-feed-generator'),         // Title of the field
			'exclude_patterns_cb',                            				// Callback function to display the field
			'smarty_feed_generator_settings',                               // Page on which to add the field
			'smarty_gfg_section_general'                                    // Section to which this field belongs
		);
	
		add_settings_field(
			'smarty_excluded_categories',                                   // ID of the field
			__('Excluded Categories', 'smarty-google-feed-generator'),      // Title of the field
			'excluded_categories_cb',                          				// Callback function to display the field
			'smarty_feed_generator_settings',                               // Page on which to add the field
			'smarty_gfg_section_general'                                    // Section to which this field belongs
		);
			
		// Add settings fields for each criteria
		add_settings_field(
			'smarty_custom_label_0_older_than_days',                        // ID of the field
			__('Older Than (Days)', 'smarty-google-feed-generator'),        // Title of the field
			'custom_label_days_cb',                            				// Callback function to display the field
			'smarty_feed_generator_settings',                               // Page on which to add the field
			'smarty_gfg_section_custom_labels',                             // Section to which this field belongs
			['label' => 'smarty_custom_label_0_older_than_days']
		);
	
		add_settings_field(
			'smarty_custom_label_0_older_than_value',                       // ID of the field
			__('Older Than Value', 'smarty-google-feed-generator'),         // Title of the field
			'custom_label_value_cb',                           				// Callback function to display the field
			'smarty_feed_generator_settings',                               // Page on which to add the field
			'smarty_gfg_section_custom_labels',                             // Section to which this field belongs
			['label' => 'smarty_custom_label_0_older_than_value']
		);
	
		add_settings_field(
			'smarty_custom_label_0_not_older_than_days',                    // ID of the field
			__('Not Older Than (Days)', 'smarty-google-feed-generator'),    // Title of the field
			'custom_label_days_cb',                            				// Callback function to display the field
			'smarty_feed_generator_settings',                               // Page on which to add the field
			'smarty_gfg_section_custom_labels',                             // Section to which this field belongs
			['label' => 'smarty_custom_label_0_not_older_than_days']
		);
	
		add_settings_field(
			'smarty_custom_label_0_not_older_than_value',                   // ID of the field
			__('Not Older Than Value', 'smarty-google-feed-generator'),     // Title of the field
			'custom_label_value_cb',                           				// Callback function to display the field
			'smarty_feed_generator_settings',                               // Page on which to add the field
			'smarty_gfg_section_custom_labels',                             // Section to which this field belongs
			['label' => 'smarty_custom_label_0_not_older_than_value']
		);
	
		add_settings_field(
			'smarty_custom_label_1_most_ordered_days',                          // ID of the field
			__('Most Ordered in Last (Days)', 'smarty-google-feed-generator'),  // Title of the field
			'custom_label_days_cb',                                				// Callback function to display the field
			'smarty_feed_generator_settings',                                   // Page on which to add the field
			'smarty_gfg_section_custom_labels',                                 // Section to which this field belongs
			['label' => 'smarty_custom_label_1_most_ordered_days']
		);
	
		add_settings_field(
			'smarty_custom_label_1_most_ordered_value',                     // ID of the field
			__('Most Ordered Value', 'smarty-google-feed-generator'),       // Title of the field
			'custom_label_value_cb',                           				// Callback function to display the field
			'smarty_feed_generator_settings',                               // Page on which to add the field
			'smarty_gfg_section_custom_labels',                             // Section to which this field belongs
			['label' => 'smarty_custom_label_1_most_ordered_value']
		);
	
		add_settings_field(
			'smarty_custom_label_2_high_rating_value',                      // ID of the field
			__('High Rating Value', 'smarty-google-feed-generator'),        // Title of the field
			'custom_label_value_cb',                           				// Callback function to display the field
			'smarty_feed_generator_settings',                               // Page on which to add the field
			'smarty_gfg_section_custom_labels',                             // Section to which this field belongs
			['label' => 'smarty_custom_label_2_high_rating_value']
		);
	
		add_settings_field(
			'smarty_custom_label_3_category',                               // ID of the field
			__('Category', 'smarty-google-feed-generator'),                 // Title of the field
			'custom_label_category_cb',                        				// Callback function to display the field
			'smarty_feed_generator_settings',                               // Page on which to add the field
			'smarty_gfg_section_custom_labels',                             // Section to which this field belongs
			['label' => 'smarty_custom_label_3_category']
		);
	
		add_settings_field(
			'smarty_custom_label_3_category_value',                         // ID of the field
			__('Category Value', 'smarty-google-feed-generator'),           // Title of the field
			'custom_label_value_cb',                           				// Callback function to display the field
			'smarty_feed_generator_settings',                               // Page on which to add the field
			'smarty_gfg_section_custom_labels',                             // Section to which this field belongs
			['label' => 'smarty_custom_label_3_category_value']
		);
	
		add_settings_field(
			'smarty_custom_label_4_sale_price_value',                       // ID of the field
			__('Sale Price Value', 'smarty-google-feed-generator'),         // Title of the field
			'custom_label_value_cb',                           				// Callback function to display the field
			'smarty_feed_generator_settings',                               // Page on which to add the field
			'smarty_gfg_section_custom_labels',                             // Section to which this field belongs
			['label' => 'smarty_custom_label_4_sale_price_value']
		);
	
		add_settings_field(
			'smarty_convert_images',                                        // ID of the field
			__('Convert', 'smarty-google-feed-generator'),                  // Title of the field
			'convert_images_button_cb',                        				// Callback function to display the field
			'smarty_feed_generator_settings',                               // Page on which to add the field
			'smarty_gfg_section_convert_images'                             // Section to which this field belongs
		);
	
		add_settings_field(
			'smarty_generate_feed_now',                                     // ID of the field
			__('Generate', 'smarty-google-feed-generator'),                 // Title of the field
			'generate_feed_buttons_cb',                        				// Callback function to display the field
			'smarty_feed_generator_settings',                               // Page on which to add the field
			'smarty_gfg_section_generate_feeds'                             // Section to which this field belongs
		);
	
		// Add settings fields to Meta Fields section
		add_settings_field(
			'smarty_meta_title_field',                                      // ID of the field
			__('Meta Title', 'smarty-google-feed-generator'),               // Title of the field
			'meta_title_field_cb',                              			// Callback function to display the field
			'smarty_feed_generator_settings',                               // Page on which to add the field
			'smarty_gfg_section_meta_fields'                                // Section to which this field belongs
		);
	
		add_settings_field(
			'smarty_meta_description_field',                                // ID of the field
			__('Meta Description', 'smarty-google-feed-generator'),         // Title of the field
			'meta_description_field_cb',                        			// Callback function to display the field
			'smarty_feed_generator_settings',                               // Page on which to add the field
			'smarty_gfg_section_meta_fields'                                // Section to which this field belongs
		);
	
		// Add settings field to Cache section
		add_settings_field(
			'smarty_clear_cache',                                           // ID of the field
			__('Clear Cache', 'smarty-google-feed-generator'),              // Title of the field
			'clear_cache_cb',                                   			// Callback function to display the field
			'smarty_feed_generator_settings',                               // Page on which to add the field
			'smarty_gfg_section_settings'                                   // Section to which this field belongs
		);
	
		// Add settings field for cache duration
		add_settings_field(
			'smarty_cache_duration',                                        // ID of the field
			__('Cache Duration (hours)', 'smarty-google-feed-generator'),   // Title of the field
			'cache_duration_cb',                                			// Callback function to display the field
			'smarty_feed_generator_settings',                               // Page on which to add the field
			'smarty_gfg_section_settings'                                   // Section to which this field belongs
		);
	}
	
	public function sanitize_checkbox($input) {
		return $input == 1 ? 1 : 0;
	}

	
	public function sanitize_number_field($input) {
		return absint($input);
	}

	/**
	 * Function to sanitize the plugin settings on save.
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

	public function handle_ajax_convert_images() {
		check_ajax_referer('smarty_feed_generator_nonce', 'nonce');
	
		if (!current_user_can('manage_options')) {
			wp_send_json_error('You do not have sufficient permissions to access this page.');
		}
	
		$this->convert_first_webp_image_to_png();
	
		wp_send_json_success(__('The first WebP image of each product has been converted to PNG.', 'smarty-google-feed-generator'));
	}

	public function handle_ajax_generate_feed() {
		check_ajax_referer('smarty_feed_generator_nonce', 'nonce');
	
		if (!current_user_can('manage_options')) {
			wp_send_json_error('You do not have sufficient permissions to access this page.');
		}
	
		$action = sanitize_text_field($_POST['feed_action']);
		switch ($action) {
			case 'generate_product_feed':
				smarty_generate_google_feed();
				wp_send_json_success('Product feed generated successfully.');
				break;
			case 'generate_reviews_feed':
				smarty_generate_google_reviews_feed();
				wp_send_json_success('Reviews feed generated successfully.');
				break;
			case 'generate_csv_export':
				smarty_generate_csv_export();
				wp_send_json_success('CSV export generated successfully.');
				break;
			default:
				wp_send_json_error('Invalid action.');
				break;
		}
	}

	/**
	 * AJAX handler to load Google Product Categories.
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
	 * Settings page HTML.
	 */
	public function settings_page_html() {
		// Check user capabilities
		if (!current_user_can('manage_options')) {
			return;
		}
	
		// HTML
		include_once('partials/smarty-gfg-admin-dipslay.php');
	}

	/**
	 * Fetch Google product categories from the taxonomy file and return them as an associative array with IDs.
	 * 
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
     * Converts an image from WEBP to PNG, updates the product image, and regenerates the feed.
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
                    smarty_invalidate_feed_cache($product->get_id());
                }
            }
        }
    }

	/**
     * Converts a WEBP image file to PNG.
     * 
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
     */
    public function convert_first_webp_image_to_png() {
        $products = wc_get_products(array(
            'status' => 'publish',
            'limit'  => -1,
        ));

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
    }

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
	
			smarty_write_logs('Checking API key validity: ' . $api_key);
			smarty_write_logs('API Response: ' . print_r($response, true));
			smarty_write_logs('License is ' . ($isActive ? 'active' : 'inactive'));
			return $isActive;
		}
	
		return false;
	}

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
     * Sanitizes the plugin Compatibility settings.
     *
     * Validates and sanitizes user input for the settings.
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

	public function section_general_cb() {
		echo '<p>' . __('General settings for the Google Feed Generator.', 'smarty-google-feed-generator') . '</p>';
	}
	
	public function google_product_category_cb() {
		$option = get_option('smarty_google_product_category');
		echo '<select name="smarty_google_product_category" class="smarty-select2-ajax">';
		if ($option) {
			echo '<option value="' . esc_attr($option) . '" selected>' . esc_html($option) . '</option>';
		}
		echo '</select>';
	}

	public function google_category_as_id_cb() {
		$option = get_option('smarty_google_category_as_id');
		echo '<input type="checkbox" name="smarty_google_category_as_id" value="1" ' . checked(1, $option, false) . ' />';
		echo '<p class="description">' . __('Check to use Google Product Category ID in the CSV feed instead of the name.', 'smarty-google-feed-generator') . '</p>';
	}
}
	
	public function section_custom_labels_cb() {
		echo '<p>' . __('Define default values for custom labels.', 'smarty-google-feed-generator') . '</p>';
	}

	
	public function section_convert_images_cb() {
		echo '<p>' . __('Use the button below to manually convert the first WebP image of each products in to the feed to PNG.', 'smarty-google-feed-generator') . '</p>';
	}
	
	public function section_generate_feeds_cb() {
		echo '<p>' . __('Use the buttons below to manually generate the feeds.', 'smarty-google-feed-generator') . '</p>';
	}
	
	public function section_meta_fields_cb() {
		echo '<p>' . __('Meta fields settings for the Google Feed Generator.', 'smarty-google-feed-generator') . '</p>';
	}
	
	public function section_settings_cb() {
		echo '<p>' . __('Cache settings for the Google Feed Generator.', 'smarty-google-feed-generator') . '</p>';
	}
	
	public function exclude_patterns_cb() {
		$option = get_option('smarty_exclude_patterns');
		echo '<textarea name="smarty_exclude_patterns" rows="10" cols="50" class="large-text">' . esc_textarea($option) . '</textarea>';
		echo '<p class="description">' . __('Enter patterns to exclude from the CSV feed, one per line.', 'smarty-google-feed-generator') . '</p>';
	}
	
	/**
	 * Callback function to display the excluded categories field.
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
		echo '<p class="description">' . __('Select categories to exclude from the feed.', 'smarty-google-feed-generator') . '</p>';
	}
	
	public function custom_label_days_cb($args) {
		$option = get_option($args['label'], 30);
		$days = [10, 20, 30, 60, 90, 120];
		echo '<select name="' . esc_attr($args['label']) . '">';
		foreach ($days as $day) {
			echo '<option value="' . esc_attr($day) . '" ' . selected($option, $day, false) . '>' . esc_html($day) . '</option>';
		}
		echo '</select>';
	}
	
	public function custom_label_value_cb($args) {
		$option = get_option($args['label'], '');
		echo '<input type="text" name="' . esc_attr($args['label']) . '" value="' . esc_attr($option) . '" class="regular-text" />';
	
		// Add custom descriptions based on the label
		switch ($args['label']) {
			case 'smarty_custom_label_0_older_than_value':
				echo '<p class="description">Enter the value to label products older than the specified number of days.</p>';
				break;
			case 'smarty_custom_label_0_not_older_than_value':
				echo '<p class="description">Enter the value to label products not older than the specified number of days.</p>';
				break;
			case 'smarty_custom_label_1_most_ordered_value':
				echo '<p class="description">Enter the value to label the most ordered products in the last specified days.</p>';
				break;
			case 'smarty_custom_label_2_high_rating_value':
				echo '<p class="description">Enter the value to label products with high ratings.</p>';
				break;
			case 'smarty_custom_label_3_category_value':
				echo '<p class="description">Enter custom values for the categories separated by commas. <b>Example:</b> Tech, Apparel, Literature <br><small><em><strong>Important:</strong> <span style="color: #c51244;">Ensure these values are in the same order as the selected categories. </span></em></small></p>';
				break;
			case 'smarty_custom_label_4_sale_price_value':
				echo '<p class="description">Enter the value to label products with a sale price.</p>';
				break;
			default:
				echo '<p class="description">Enter a custom value for this label.</p>';
				break;
		}
	}
	
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
			echo '<p class="description">Select one or multiple categories from the list. <br><small><em><b>Important:</b> <span style="color: #c51244;">Ensure the values for each category are entered in the same order in the Category Value field.</span></em></small></p>';
		}
	}
	
	public function convert_images_button_cb() {
		echo '<button class="button secondary smarty-convert-images-button" style="display: inline-block; margin-bottom: 10px;">' . __('Convert WebP to PNG', 'smarty-google-feed-generator') . '</button>';
	}
	
	public function generate_feed_buttons_cb() {
		echo '<button class="button secondary smarty-generate-feed-button" data-feed-action="generate_product_feed" style="display: inline-block;">' . __('Generate Product Feed', 'smarty-google-feed-generator') . '</button>';
		echo '<button class="button secondary smarty-generate-feed-button" data-feed-action="generate_reviews_feed" style="display: inline-block; margin: 0 10px;">' . __('Generate Reviews Feed', 'smarty-google-feed-generator') . '</button>';
		echo '<button class="button secondary smarty-generate-feed-button" data-feed-action="generate_csv_export" style="display: inline-block; margin-right: 10px;">' . __('Generate CSV Export', 'smarty-google-feed-generator') . '</button>';
	}
	
	public function meta_title_field_cb() {
		$option = get_option('smarty_meta_title_field', 'meta-title');
		echo '<input type="text" name="smarty_meta_title_field" value="' . esc_attr($option) . '" class="regular-text" />';
		echo '<p class="description">' . __('Enter the custom field name for the product title meta.', 'smarty-google-feed-generator') . '</p>';
	}
	
	public function meta_description_field_cb() {
		$option = get_option('smarty_meta_description_field', 'meta-description');
		echo '<input type="text" name="smarty_meta_description_field" value="' . esc_attr($option) . '" class="regular-text" />';
		echo '<p class="description">' . __('Enter the custom field name for the product description meta.', 'smarty-google-feed-generator') . '</p>';
	}
	
	public function clear_cache_cb() {
		$option = get_option('smarty_clear_cache');
		echo '<input type="checkbox" name="smarty_clear_cache" value="1" ' . checked(1, $option, false) . ' />';
		echo '<p class="description">' . __('Check to clear the cache each time the feed is generated. <br><small><em><b>Important:</b> <span style="color: #c51244;">Remove this in production to utilize caching.</span></em></small>', 'smarty-google-feed-generator') . '</p>';
	}
	
	public function cache_duration_cb() {
		$option = get_option('smarty_cache_duration', 12); // Default to 12 hours if not set
		echo '<input type="number" name="smarty_cache_duration" value="' . esc_attr($option) . '" />';
		echo '<p class="description">' . __('Set the cache duration in hours.', 'smarty-google-feed-generator') . '</p>';
	}

	/**
     * Display the settings field with the values properly trimmed.
     */
    public function display_category_values_field() {
        $category_values = get_option('smarty_custom_label_3_category_value', '');
        if (!is_array($category_values)) {
            $category_values = explode(',', $category_values);
        }
        $category_values = array_map('trim', $category_values);
        $category_values = implode(', ', $category_values);

        echo '<input type="text" id="smarty_custom_label_3_category_value" name="smarty_custom_label_3_category_value" value="' . esc_attr($category_values) . '" class="regular-text" />';
        echo '<p class="description">Enter custom values for the categories separated by commas. <strong>Example:</strong> Tech, Apparel, Literature</p>';
        echo '<p class="description"><strong>Important:</strong> Ensure these values are in the same order as the selected categories.</p>';
    }
    // Add the display callback when registering the setting field
    add_action('admin_init', function() {
        register_setting('smarty_settings', 'smarty_custom_label_3_category_value', [
            'sanitize_cb' => 'smarty_sanitize_category_values'
        ]);

        add_settings_field(
            'smarty_custom_label_3_category_value',
            __('Category Value', 'smarty'),
            'display_category_values_field',
            'smarty-settings-page',
            'smarty_settings_section'
        );
    });

    /**
	 * Callback function for the section.
	 * 
	 * @since    1.0.0
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
	 */
	public function field_api_key_cb($args) {
		$options = get_option('smarty_gfg_settings_license');
		?>
		<input type="text" id="smarty_gfg_api_key" name="smarty_gfg_settings_license[api_key]" size="30" value="<?php echo isset($options['api_key']) ? esc_attr($options['api_key']) : ''; ?>">
		<p class="description">
			<?php echo esc_html__('Enter a valid API key.', 'smarty-google-feed-generator'); ?>
		</p>
		<?php
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
        if (isset($options['schedule']) && smarty_check_coupon_schedule()) { 
			?>
            <div class="notice notice-info smarty-auto-hide-notice">
				<p><?php echo esc_html__('Coupon field is currently disabled based on the schedule.', 'smarty-google-feed-generator'); ?></p>
			</div>
			<?php
        }
		
		if (isset($_GET['license-activated']) && $_GET['license-activated'] == 'true') {
			?>
			<div class="notice notice-success smarty-auto-hide-notice">
				<p><?php echo esc_html__('License activated successfully.', 'smarty-google-feed-generator'); ?></p>
			</div>
			<?php
		}
    }
}