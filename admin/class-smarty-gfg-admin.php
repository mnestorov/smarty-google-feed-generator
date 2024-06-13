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
     * Add rewrite rules for custom endpoints.
     * 
     * @since    1.0.0
     */
    public static function feed_generator_add_rewrite_rules() {
        add_rewrite_rule('^smarty-google-feed/?', 'index.php?smarty_google_feed=1', 'top');                 // url: ?smarty-google-feed
        add_rewrite_rule('^smarty-google-reviews-feed/?', 'index.php?smarty_google_reviews_feed=1', 'top'); // url: ?smarty-google-reviews-feed
        add_rewrite_rule('^smarty-csv-export/?', 'index.php?smarty_csv_export=1', 'top');                   // url: ?smarty-csv-export
    }

    /**
     * Register query vars for custom endpoints.
     * 
     * @since    1.0.0
     */
    public function feed_generator_query_vars($vars) {
        $vars[] = 'smarty_google_feed';
        $vars[] = 'smarty_google_reviews_feed';
        $vars[] = 'smarty_csv_export';
        return $vars;
    }

    /**
     * Handle requests to custom endpoints.
     * 
     * @since    1.0.0
     */
    public function feed_generator_template_redirect() {
        if (get_query_var('smarty_google_feed')) {
            self::generate_google_feed();
            exit;
        }

        if (get_query_var('smarty_google_reviews_feed')) {
            self::generate_google_reviews_feed();
            exit;
        }

        if (get_query_var('smarty_csv_export')) {
            self::generate_csv_export();
            exit;
        }
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
                
                if (self::convert_webp_to_png($file_path, $new_file_path)) {
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
                    invalidate_feed_cache($product->get_id());
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

    public function handle_ajax_convert_images() {
        check_ajax_referer('smarty_feed_generator_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('You do not have sufficient permissions to access this page.');
        }

        self::convert_first_webp_image_to_png();

        wp_send_json_success(__('The first WebP image of each product has been converted to PNG.', 'smarty-google-feed-generator'));
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

                if (self::convert_webp_to_png($file_path, $new_file_path)) {
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

    /**
     * Add settings page to the WordPress admin menu.
     */
    function add_admin_menu() {
        add_options_page(
            __('Google Feed Generator | Settings'),  // Page title
            __('Google Feed Generator'),             // Menu title
            'manage_options',                        // Capability required to access this page
            'smarty-feed-generator-settings',        // Menu slug
            array($this, 'display_settings_page')    // Callback function to display the page content
        );
    }

    /**
	 * @since    1.0.0
	 */
	private function get_settings_tabs() {
		return array(
			'general' 		=> __('General', 'smarty-google-feed-generator'),
			'compatibility' => __('Compatibility', 'smarty-google-feed-generator'),
			'license' 		=> __('License', 'smarty-google-feed-generator')
		);
	}

    /**
	 * Outputs the HTML for the settings page.
	 * 
	 * @since    1.0.0
	 */
	public function display_settings_page() {
		if (!current_user_can('manage_options')) {
			return;
		}

		$current_tab = isset($_GET['tab']) ? $_GET['tab'] : 'general';
		$tabs = self::get_settings_tabs();

		// Check if settings have been submitted
		if (isset($_GET['settings-updated']) && $_GET['settings-updated']) {
			// Redirect to settings page with custom query variable to avoid the default notice
			wp_redirect(add_query_arg('smarty-settings-updated', 'true', menu_page_url('smarty-gfg-settings', false)));
			exit;
		}
		
		// Define the path to the external file
		$partial_file = plugin_dir_path(__FILE__) . 'partials/smarty-gfg-admin-display.php';

		if (file_exists($partial_file) && is_readable($partial_file)) {
			include_once $partial_file;
		} else {
			smarty_write_logs("Unable to include: '$partial_file'");
		}
	}

    /**
	 * Initializes the plugin settings by registering the settings, sections, and fields.
	 *
	 * @since    1.0.0
	 */
	public function settings_init() {
		// Check if the settings were saved and set a transient
		if (isset($_GET['settings-updated']) && $_GET['settings-updated']) {
			set_transient('smarty_gfg_settings_updated', 'yes', 5);
		}

		/**
		 *  Register settings for the General tab
		 */
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

        register_setting('smarty_settings', 'smarty_custom_label_3_category_value', [
            'sanitize_callback' => 'sanitize_category_values'
        ]);

        // Meta Fields
        register_setting('smarty_feed_generator_settings', 'smarty_meta_title_field', 'sanitize_text_field');
        register_setting('smarty_feed_generator_settings', 'smarty_meta_description_field', 'sanitize_text_field');
        
        // Cache Settings
        register_setting('smarty_feed_generator_settings', 'smarty_clear_cache', 'sanitize_checkbox');
        register_setting('smarty_feed_generator_settings', 'smarty_cache_duration', 'sanitize_number_field');

        // Compatibility Tab
        register_setting('smarty_gfg_options_compatibility', 'smarty_gfg_settings_compatibility', 'sanitize_compatibility_settings');

        // License Tab
		register_setting('smarty_gfg_options_license', 'smarty_gfg_settings_license', 'sanitize_license_settings');
		
        add_settings_section(
			'smarty_gfg_section_general',                         			    // ID of the section
			__('General', 'smarty-google-feed-generator'),      			    // Title of the section
			array($this, 'section_general_cb'),                                 // Callback function that fills the section with the desired content
			'smarty_feed_generator_settings'                     		        // Page on which to add the section
		);

        // Add Custom Labels section
        add_settings_section(
            'smarty_gfg_section_custom_labels',                                 // ID of the section
            __('Custom Labels', 'smarty-google-feed-generator'),                // Title of the section
            array($this, 'section_custom_labels_cb'),                           // Callback function that fills the section with the desired content
            'smarty_feed_generator_settings'                                    // Page on which to add the section
        );

        // Add Convert Images section
        add_settings_section(
            'smarty_gfg_section_convert_images',                                // ID of the section
            __('Convert Images', 'smarty-google-feed-generator'),               // Title of the section
            array($this, 'section_convert_images_cb'),                          // Callback function that fills the section with the desired content
            'smarty_feed_generator_settings'                                    // Page on which to add the section
        );

        // Add Generate Feeds section
        add_settings_section(
            'smarty_gfg_section_generate_feeds',                                // ID of the section
            __('Generate Feeds', 'smarty-google-feed-generator'),               // Title of the section
            array($this, 'section_generate_feeds_cb'),                          // Callback function that fills the section with the desired content
            'smarty_feed_generator_settings'                                    // Page on which to add the section
        );

        // Add Meta Fields section
        add_settings_section(
            'smarty_gfg_section_meta_fields',                                   // ID of the section
            __('Meta Fields', 'smarty-google-feed-generator'),                  // Title of the section
            array($this, 'section_meta_fields_cb'),                             // Callback function that fills the section with the desired content
            'smarty_feed_generator_settings'                                    // Page on which to add the section
        );

        // Add Settings section
        add_settings_section(
            'smarty_gfg_section_settings',                                      // ID of the section
            __('Cache', 'smarty-google-feed-generator'),                        // Title of the section
            array($this, 'section_settings_cb'),                                // Callback function that fills the section with the desired content
            'smarty_feed_generator_settings'                                    // Page on which to add the section
        );

        // Add settings fields
        add_settings_field(
            'smarty_google_product_category',                                   // ID of the field
            __('Google Product Category', 'smarty-google-feed-generator'),      // Title of the field
            array($this, 'google_product_category_cb'),                         // Callback function to display the field
            'smarty_feed_generator_settings',                                   // Page on which to add the field
            'smarty_gfg_section_general'                                        // Section to which this field belongs
        );

        add_settings_field(
            'smarty_google_category_as_id',                                     // ID of the field
            __('Use Google Category ID', 'smarty-google-feed-generator'),       // Title of the field
            array($this, 'google_category_as_id_cb'),                           // Callback function to display the field
            'smarty_feed_generator_settings',                                   // Page on which to add the field
            'smarty_gfg_section_general'                                        // Section to which this field belongs
        );

        add_settings_field(
            'smarty_exclude_patterns',                                          // ID of the field
            __('Exclude Patterns', 'smarty-google-feed-generator'),             // Title of the field
            array($this, 'exclude_patterns_cb'),                                // Callback function to display the field
            'smarty_feed_generator_settings',                                   // Page on which to add the field
            'smarty_gfg_section_general'                                        // Section to which this field belongs
        );

        add_settings_field(
            'smarty_excluded_categories',                                       // ID of the field
            __('Excluded Categories', 'smarty-google-feed-generator'),          // Title of the field
            array($this, 'excluded_categories_cb'),                             // Callback function to display the field
            'smarty_feed_generator_settings',                                   // Page on which to add the field
            'smarty_gfg_section_general'                                        // Section to which this field belongs
        );
        
        // Add settings fields for each criteria
        add_settings_field(
            'smarty_custom_label_0_older_than_days',                            // ID of the field
            __('Older Than (Days)', 'smarty-google-feed-generator'),            // Title of the field
            array($this, 'custom_label_days_cb'),                               // Callback function to display the field
            'smarty_feed_generator_settings',                                   // Page on which to add the field
            'smarty_gfg_section_custom_labels',                                 // Section to which this field belongs
            ['label' => 'smarty_custom_label_0_older_than_days']
        );

        add_settings_field(
            'smarty_custom_label_0_older_than_value',                           // ID of the field
            __('Older Than Value', 'smarty-google-feed-generator'),             // Title of the field
            array($this, 'custom_label_value_cb'),                              // Callback function to display the field
            'smarty_feed_generator_settings',                                   // Page on which to add the field
            'smarty_gfg_section_custom_labels',                                 // Section to which this field belongs
            ['label' => 'smarty_custom_label_0_older_than_value']
        );

        add_settings_field(
            'smarty_custom_label_0_not_older_than_days',                        // ID of the field
            __('Not Older Than (Days)', 'smarty-google-feed-generator'),        // Title of the field
            array($this, 'custom_label_days_cb'),                               // Callback function to display the field
            'smarty_feed_generator_settings',                                   // Page on which to add the field
            'smarty_gfg_section_custom_labels',                                 // Section to which this field belongs
            ['label' => 'smarty_custom_label_0_not_older_than_days']
        );

        add_settings_field(
            'smarty_custom_label_0_not_older_than_value',                       // ID of the field
            __('Not Older Than Value', 'smarty-google-feed-generator'),         // Title of the field
            array($this, 'custom_label_value_cb'),                              // Callback function to display the field
            'smarty_feed_generator_settings',                                   // Page on which to add the field
            'smarty_gfg_section_custom_labels',                                 // Section to which this field belongs
            ['label' => 'smarty_custom_label_0_not_older_than_value']
        );

        add_settings_field(
            'smarty_custom_label_1_most_ordered_days',                          // ID of the field
            __('Most Ordered in Last (Days)', 'smarty-google-feed-generator'),  // Title of the field
            array($this, 'custom_label_days_cb'),                               // Callback function to display the field
            'smarty_feed_generator_settings',                                   // Page on which to add the field
            'smarty_gfg_section_custom_labels',                                 // Section to which this field belongs
            ['label' => 'smarty_custom_label_1_most_ordered_days']
        );

        add_settings_field(
            'smarty_custom_label_1_most_ordered_value',                         // ID of the field
            __('Most Ordered Value', 'smarty-google-feed-generator'),           // Title of the field
            array($this, 'custom_label_value_cb'),                              // Callback function to display the field
            'smarty_feed_generator_settings',                                   // Page on which to add the field
            'smarty_gfg_section_custom_labels',                                 // Section to which this field belongs
            ['label' => 'smarty_custom_label_1_most_ordered_value']
        );

        add_settings_field(
            'smarty_custom_label_2_high_rating_value',                          // ID of the field
            __('High Rating Value', 'smarty-google-feed-generator'),            // Title of the field
            array($this, 'custom_label_value_cb'),                              // Callback function to display the field
            'smarty_feed_generator_settings',                                   // Page on which to add the field
            'smarty_gfg_section_custom_labels',                                 // Section to which this field belongs
            ['label' => 'smarty_custom_label_2_high_rating_value']
        );

        add_settings_field(
            'smarty_custom_label_3_category',                                   // ID of the field
            __('Category', 'smarty-google-feed-generator'),                     // Title of the field
            array($this, 'custom_label_category_cb'),                           // Callback function to display the field
            'smarty_feed_generator_settings',                                   // Page on which to add the field
            'smarty_gfg_section_custom_labels',                                 // Section to which this field belongs
            ['label' => 'smarty_custom_label_3_category']
        );

        add_settings_field(
            'smarty_custom_label_3_category_value',                             // ID of the field
            __('Category Value', 'smarty-google-feed-generator'),               // Title of the field
            array($this, 'custom_label_value_cb'),                              // Callback function to display the field
            'smarty_feed_generator_settings',                                   // Page on which to add the field
            'smarty_gfg_section_custom_labels',                                 // Section to which this field belongs
            ['label' => 'smarty_custom_label_3_category_value']
        );

        add_settings_field(
            'smarty_custom_label_3_category_value',
            __('Category Value', 'smarty-google-feed-generator'),
            array($this, 'display_category_values_field_cb'),
            'smarty-settings-page',
            'smarty_settings_section'
        );

        add_settings_field(
            'smarty_custom_label_4_sale_price_value',                           // ID of the field
            __('Sale Price Value', 'smarty-google-feed-generator'),             // Title of the field
            array($this, 'custom_label_value_cb'),                              // Callback function to display the field
            'smarty_feed_generator_settings',                                   // Page on which to add the field
            'smarty_gfg_section_custom_labels',                                 // Section to which this field belongs
            ['label' => 'smarty_custom_label_4_sale_price_value']
        );

        add_settings_field(
            'smarty_convert_images',                                            // ID of the field
            __('Convert', 'smarty-google-feed-generator'),                      // Title of the field
            array($this, 'convert_images_button_cb'),                           // Callback function to display the field
            'smarty_feed_generator_settings',                                   // Page on which to add the field
            'smarty_gfg_section_convert_images'                                 // Section to which this field belongs
        );

        add_settings_field(
            'smarty_generate_feed_now',                                         // ID of the field
            __('Generate', 'smarty-google-feed-generator'),                     // Title of the field
            array($this, 'generate_feed_buttons_cb'),                           // Callback function to display the field
            'smarty_feed_generator_settings',                                   // Page on which to add the field
            'smarty_gfg_section_generate_feeds'                                 // Section to which this field belongs
        );

        // Add settings fields to Meta Fields section
        add_settings_field(
            'smarty_meta_title_field',                                          // ID of the field
            __('Meta Title', 'smarty-google-feed-generator'),                   // Title of the field
            array($this, 'meta_title_field_cb'),                                // Callback function to display the field
            'smarty_feed_generator_settings',                                   // Page on which to add the field
            'smarty_gfg_section_meta_fields'                                    // Section to which this field belongs
        );

        add_settings_field(
            'smarty_meta_description_field',                                    // ID of the field
            __('Meta Description', 'smarty-google-feed-generator'),             // Title of the field
            array($this, 'meta_description_field_cb'),                          // Callback function to display the field
            'smarty_feed_generator_settings',                                   // Page on which to add the field
            'smarty_gfg_section_meta_fields'                                    // Section to which this field belongs
        );

        // Add settings field to Cache section
        add_settings_field(
            'smarty_clear_cache',                                               // ID of the field
            __('Clear Cache', 'smarty-google-feed-generator'),                  // Title of the field
            array($this, 'clear_cache_cb'),                                     // Callback function to display the field
            'smarty_feed_generator_settings',                                   // Page on which to add the field
            'smarty_gfg_section_settings'                                       // Section to which this field belongs
        );

        // Add settings field for cache duration
        add_settings_field(
            'smarty_cache_duration',                                            // ID of the field
            __('Cache Duration (hours)', 'smarty-google-feed-generator'),       // Title of the field
            array($this, 'cache_duration_cb'),                                  // Callback function to display the field
            'smarty_feed_generator_settings',                                   // Page on which to add the field
            'smarty_gfg_section_settings'                                       // Section to which this field belongs
        );

		add_settings_section(
			'smarty_gfg_section_compatibility',                         	    // ID of the section
			__('Compatibility', 'smarty-google-feed-generator'),      	        // Title of the section
			array($this, 'section_compatibility_cb'),                		    // Callback function that fills the section with the desired content
			'smarty-gfg-settings-compatibility'                      		    // Page on which to add the section
		);

		add_settings_section(
			'smarty_gfg_section_license',                                       // ID of the section
			__('License', 'smarty-google-feed-generator'),                      // Title of the field
			array($this, 'section_license_cb'),
			'smarty-gfg-settings-license'
		);

		add_settings_field(
			'smarty_gfg_api_key',                                               // ID of the section
			__('API Key', 'smarty-google-feed-generator'),                      // Title of the field
			array($this, 'field_api_key_cb'),
			'smarty-gfg-settings-license',
			'smarty_gfg_section_license'
		);
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

    /**
	 * @since    1.0.0
	 */
    public function sanitize_checkbox($input) {
        return $input == 1 ? 1 : 0;
    }
    
    /**
	 * @since    1.0.0
	 */
    public function sanitize_number_field($input) {
        return absint($input);
    }

    /**
     * Function to sanitize the plugin settings on save.
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
     * Retrieves the Google Product Category based on the plugin settings.
     *
     * This function fetches the Google Product Category as stored in the plugin settings.
     * If the "Use Google Category ID" option is checked, it returns the category ID.
     * Otherwise, it returns the category name, removing any preceding ID and hyphen.
     *
     * @since    1.0.0
     * @return string The cleaned Google Product Category name or ID.
     */
    public function get_cleaned_google_product_category() {
        // Get the option value
        $category = get_option('smarty_google_product_category');
        $use_id = get_option('smarty_google_category_as_id', false);

        // Split the string by the '-' character
        $parts = explode(' - ', $category);

        if ($use_id && count($parts) > 1) {
            // If the option to use the ID is enabled, return the first part (ID)
            return trim($parts[0]);
        } elseif (count($parts) > 1) {
            // If the option to use the name is enabled, return the second part (Name)
            return trim($parts[1]);
        }

        // If the string doesn't contain a '-', return the original value or handle as needed
        return $category;
    }

    /**
	 * Callback function for the section.
	 *
	 * @since    1.0.0
	 * @param array  $args	Additional arguments passed by add_settings_section.
	 */
    public function section_general_cb($args) { ?>
		<p id="<?php echo esc_attr($args['id']); ?>">
			<?php echo esc_html__('General settings for the Google Feed Generator.', 'smarty-google-feed-generator'); ?>
		</p>
		<?php
	}

    /**
	 * Callback function for the Google Product Category dropdown.
	 *
	 * @since    1.0.0
	 * @param array  $args	Additional arguments passed by add_settings_section.
	 */
    public function google_product_category_cb() {
        $option = get_option('smarty_google_product_category');
        echo '<select name="smarty_google_product_category" class="smarty-select2-ajax">';
        if ($option) {
            echo '<option value="' . esc_attr($option) . '" selected>' . esc_html($option) . '</option>';
        }
        echo '</select>';
    }

    /**
     * AJAX handler to load Google Product Categories.
     * 
     * @since    1.0.0
     */
    public function load_google_categories() {
        check_ajax_referer('smarty_feed_generator_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('You do not have sufficient permissions to access this page.');
        }

        $search = sanitize_text_field($_GET['q']);
        $google_categories = self::get_google_product_categories();

        $results = array();
        foreach ($google_categories as $category) {
            if (stripos($category, $search) !== false) {
                $results[] = array('id' => $category, 'text' => $category);
            }
        }

        wp_send_json_success($results);
    }

    /**
	 * Callback function for the Google Product Category as ID checkbox.
	 * 
	 * @since    1.0.0
	 */
    public function google_category_as_id_cb() {
        $option = get_option('smarty_google_category_as_id');
        echo '<input type="checkbox" name="smarty_google_category_as_id" value="1" ' . checked(1, $option, false) . ' />';
        echo '<p class="description">' . __('Check to use Google Product Category ID in the CSV feed instead of the name.', 'smarty-google-feed-generator') . '</p>';
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
	 * Callback function for the convert images section.
	 * 
	 * @since    1.0.0
	 */
    public function section_convert_images_cb() {
        echo '<p>' . __('Use the button below to manually convert the first WebP image of each products in to the feed to PNG.', 'smarty-google-feed-generator') . '</p>';
    }
    
    /**
	 * Callback function for the generate section.
	 * 
	 * @since    1.0.0
	 */
    public function section_generate_feeds_cb() {
        echo '<p>' . __('Use the buttons below to manually generate the feeds.', 'smarty-google-feed-generator') . '</p>';
    }
    
    /**
	 * Callback function for the meta section.
	 * 
	 * @since    1.0.0
	 */
    public function section_meta_fields_cb() {
        echo '<p>' . __('Meta fields settings for the Google Feed Generator.', 'smarty-google-feed-generator') . '</p>';
    }
    
    /**
	 * Callback function for the cache section.
	 * 
	 * @since    1.0.0
	 */
    public function section_settings_cb() {
        echo '<p>' . __('Cache settings for the Google Feed Generator.', 'smarty-google-feed-generator') . '</p>';
    }
    
    /**
	 * Callback function for the exclude patterns.
	 * 
	 * @since    1.0.0
	 */
    public function exclude_patterns_cb() {
        $option = get_option('smarty_exclude_patterns');
        echo '<textarea name="smarty_exclude_patterns" rows="10" cols="50" class="large-text">' . esc_textarea($option) . '</textarea>';
        echo '<p class="description">' . __('Enter patterns to exclude from the CSV feed, one per line.', 'smarty-google-feed-generator') . '</p>';
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
        echo '<p class="description">' . __('Select categories to exclude from the feed.', 'smarty-google-feed-generator') . '</p>';
    }
    
    /**
	 * Callback function for the Custom Label days.
	 * 
	 * @since    1.0.0
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
	 * Callback function for the Custom Label value.
	 * 
	 * @since    1.0.0
	 */
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
    
    /**
	 * Callback function for the Custom Label category.
	 * 
	 * @since    1.0.0
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
            echo '<p class="description">Select one or multiple categories from the list. <br><small><em><b>Important:</b> <span style="color: #c51244;">Ensure the values for each category are entered in the same order in the Category Value field.</span></em></small></p>';
        }
    }

    /**
     * Display the settings field with the values properly trimmed.
     * 
     * @since    1.0.0
     */
    public function display_category_values_field_cb() {
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
    
    /**
	 * Callback function for the convert images button.
	 * 
	 * @since    1.0.0
	 */
    public function convert_images_button_cb() {
        echo '<button class="button secondary smarty-convert-images-button" style="display: inline-block; margin-bottom: 10px;">' . __('Convert WebP to PNG', 'smarty-google-feed-generator') . '</button>';
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
	 * Callback function for the meta title field.
	 * 
	 * @since    1.0.0
	 */
    public function meta_title_field_cb() {
        $option = get_option('smarty_meta_title_field', 'meta-title');
        echo '<input type="text" name="smarty_meta_title_field" value="' . esc_attr($option) . '" class="regular-text" />';
        echo '<p class="description">' . __('Enter the custom field name for the product title meta.', 'smarty-google-feed-generator') . '</p>';
    }
    
    /**
	 * Callback function for the meta description field.
	 * 
	 * @since    1.0.0
	 */
    public function meta_description_field_cb() {
        $option = get_option('smarty_meta_description_field', 'meta-description');
        echo '<input type="text" name="smarty_meta_description_field" value="' . esc_attr($option) . '" class="regular-text" />';
        echo '<p class="description">' . __('Enter the custom field name for the product description meta.', 'smarty-google-feed-generator') . '</p>';
    }
    
    /**
	 * Callback function for the cache.
	 * 
	 * @since    1.0.0
	 */
    public function clear_cache_cb() {
        $option = get_option('smarty_clear_cache');
        echo '<input type="checkbox" name="smarty_clear_cache" value="1" ' . checked(1, $option, false) . ' />';
        echo '<p class="description">' . __('Check to clear the cache each time the feed is generated. <br><small><em><b>Important:</b> <span style="color: #c51244;">Remove this in production to utilize caching.</span></em></small>', 'smarty-google-feed-generator') . '</p>';
    }
    
    /**
	 * Callback function for the cache duration.
	 * 
	 * @since    1.0.0
	 */
    public function cache_duration_cb() {
        $option = get_option('smarty_cache_duration', 12); // Default to 12 hours if not set
        echo '<input type="number" name="smarty_cache_duration" value="' . esc_attr($option) . '" />';
        echo '<p class="description">' . __('Set the cache duration in hours.', 'smarty-google-feed-generator') . '</p>';
    }

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
     * Generates the custom Google product feed.
     * 
     * This function is designed to generate a custom Google product feed for WooCommerce products. 
     * It uses WordPress and WooCommerce functions to build an XML feed that conforms to Google's specifications for product feeds.
     * 
     * @since    1.0.0
     */
    public static function generate_google_feed() {
        // Start output buffering to prevent any unwanted output
        ob_start();

        // Set the content type to XML for the output
        header('Content-Type: application/xml; charset=utf-8');

        // Check if the clear cache option is enabled
        if (get_option('smarty_clear_cache')) {
            delete_transient('smarty_google_feed');
        }

        // Attempt to retrieve the cached version of the feed
        $cached_feed = get_transient('smarty_google_feed');
        if ($cached_feed !== false) {
            echo $cached_feed; // Output the cached feed and stop processing if it exists
            ob_end_flush();
            exit;
        }

        // Check if WooCommerce is active before proceeding
        if (class_exists('WooCommerce')) {
            // Get excluded categories from settings
            $excluded_categories = get_option('smarty_excluded_categories', array());
            //error_log('Excluded Categories: ' . print_r($excluded_categories, true));

            // Set up arguments for querying products, excluding certain categories
            $args = array(
                'status'       => 'publish',              // Only fetch published products
                'stock_status' => 'instock',              // Only fetch products that are in stock
                'limit'        => -1,                     // Fetch all products that match criteria
                'orderby'      => 'date',                 // Order by date
                'order'        => 'DESC',                 // In descending order
                'type'         => ['simple', 'variable'], // Fetch both simple and variable products
                'tax_query'    => array(
                    array(
                        'taxonomy' => 'product_cat',
                        'field'    => 'term_id',
                        'terms'    => $excluded_categories,
                        'operator' => 'NOT IN',
                    ),
                ),
            );

            // Fetch products using WooCommerce function
            $products = wc_get_products($args);
            //error_log('Product Query Args: ' . print_r($args, true));
            //error_log('Products: ' . print_r($products, true));

            // Initialize the XML structure
            $dom = new DOMDocument('1.0', 'UTF-8');
            $dom->formatOutput = true;

            // Create the root <feed> element
            $feed = $dom->createElement('feed');
            $feed->setAttribute('xmlns', 'http://www.w3.org/2005/Atom');
            $feed->setAttribute('xmlns:g', 'http://base.google.com/ns/1.0');
            $dom->appendChild($feed);

            // Loop through each product to add details to the feed
            foreach ($products as $product) {
                //error_log('Processing Product: ' . print_r($product->get_data(), true));
                if ($product->is_type('variable')) {
                    // Get all variations if product is variable
                    $variations = $product->get_children();

                    if (!empty($variations)) {
                        $first_variation_id = $variations[0]; // Process only the first variation for the feed
                        $variation = wc_get_product($first_variation_id);
                        $item = $dom->createElement('item'); // Add item node for each product
                        $feed->appendChild($item);

                        // Add product details as child nodes
                        self::add_google_product_details($dom, $item, $product, $variation);
                    }
                } else {
                    // Process simple products similarly
                    $item = $dom->createElement('item');
                    $feed->appendChild($item);

                    // Add product details as child nodes
                    self::add_google_product_details($dom, $item, $product);
                }
            }

            // Save and output the XML
            $feed_content = $dom->saveXML();
            //error_log('Feed Content: ' . $feed_content);

            if ($feed_content) {
                $cache_duration = get_option('smarty_cache_duration', 12); // Default to 12 hours if not set
                set_transient('smarty_google_feed', $feed_content, $cache_duration * HOUR_IN_SECONDS);

                echo $feed_content;
                ob_end_flush();
                exit; // Ensure the script stops here to prevent further output that could corrupt the feed
            } else {
                ob_end_clean();
                //error_log('Failed to generate feed content.');
                echo '<error>Failed to generate feed content.</error>';
                exit;
            }
        } else {
            ob_end_clean();
            echo '<error>WooCommerce is not active.</error>';
            exit;
        }
    }

    /**
     * Adds Google product details to the XML item node.
     *
     * @since    1.0.0
     * @param DOMDocument $dom The DOMDocument instance.
     * @param DOMElement $item The item element to which details are added.
     * @param WC_Product $product The WooCommerce product instance.
     * @param WC_Product $variation Optional. The variation instance if the product is variable.
     */
    public function add_google_product_details($dom, $item, $product, $variation = null) {
        $gNamespace = 'http://base.google.com/ns/1.0';

        if ($variation) {
            $id = $variation->get_id();
            $sku = $variation->get_sku();
            $price = $variation->get_regular_price();
            $sale_price = $variation->get_sale_price();
            $image_id = $variation->get_image_id() ? $variation->get_image_id() : $product->get_image_id();
            $is_on_sale = $variation->is_on_sale();
            $is_in_stock = $variation->is_in_stock();
        } else {
            $id = $product->get_id();
            $sku = $product->get_sku();
            $price = $product->get_price();
            $sale_price = $product->get_sale_price();
            $image_id = $product->get_image_id();
            $is_on_sale = $product->is_on_sale();
            $is_in_stock = $product->is_in_stock();
        }

        $item->appendChild($dom->createElementNS($gNamespace, 'g:id', $id));
        $item->appendChild($dom->createElementNS($gNamespace, 'g:sku', $sku));
        $item->appendChild($dom->createElementNS($gNamespace, 'title', htmlspecialchars($product->get_name())));
        $item->appendChild($dom->createElementNS($gNamespace, 'link', get_permalink($product->get_id())));

        // Add description, using meta description if available or fallback to short description
        $meta_description = get_post_meta($product->get_id(), get_option('smarty_meta_description_field', 'meta-description'), true);
        $description = !empty($meta_description) ? $meta_description : $product->get_short_description();
        $item->appendChild($dom->createElementNS($gNamespace, 'description', htmlspecialchars(strip_tags($description))));

        // Add image links
        $item->appendChild($dom->createElementNS($gNamespace, 'image_link', wp_get_attachment_url($image_id)));

        // Add additional images
        $gallery_ids = $product->get_gallery_image_ids();
        foreach ($gallery_ids as $gallery_id) {
            $item->appendChild($dom->createElementNS($gNamespace, 'additional_image_link', wp_get_attachment_url($gallery_id)));
        }

        // Add price details
        $item->appendChild($dom->createElementNS($gNamespace, 'price', htmlspecialchars($price . ' ' . get_woocommerce_currency())));
        if ($is_on_sale) {
            $item->appendChild($dom->createElementNS($gNamespace, 'sale_price', htmlspecialchars($sale_price . ' ' . get_woocommerce_currency())));
        }

        // Add product categories
        $google_product_category = Smarty_Gfg_Admin::get_cleaned_google_product_category();
        if ($google_product_category) {
            $item->appendChild($dom->createElementNS($gNamespace, 'g:google_product_category', htmlspecialchars($google_product_category)));
        }

        // Add product categories
        $categories = wp_get_post_terms($product->get_id(), 'product_cat');
        if (!empty($categories) && !is_wp_error($categories)) {
            $category_names = array_map(function($term) { return $term->name; }, $categories);
            $item->appendChild($dom->createElementNS($gNamespace, 'product_type', htmlspecialchars(join(' > ', $category_names))));
        }

        // Check if the product has the "bundle" tag
        $is_bundle = 'no';
        $product_tags = wp_get_post_terms($product->get_id(), 'product_tag', array('fields' => 'slugs'));
        if (in_array('bundle', $product_tags)) {
            $is_bundle = 'yes';
        }
        $item->appendChild($dom->createElementNS($gNamespace, 'g:is_bundle', $is_bundle));

        // Add availability
        $availability = $is_in_stock ? 'in_stock' : 'out_of_stock';
        $item->appendChild($dom->createElementNS($gNamespace, 'g:availability', $availability));

        // Add condition
        $item->appendChild($dom->createElementNS($gNamespace, 'g:condition', 'new'));

        // Add brand
        $brand = get_bloginfo('name'); // Use the site name as the brand
        $item->appendChild($dom->createElementNS($gNamespace, 'g:brand', htmlspecialchars($brand)));

        // Custom Labels
        $item->appendChild($dom->createElementNS($gNamespace, 'g:custom_label_0', smarty_get_custom_label_0($product)));
        $item->appendChild($dom->createElementNS($gNamespace, 'g:custom_label_1', smarty_get_custom_label_1($product)));
        $item->appendChild($dom->createElementNS($gNamespace, 'g:custom_label_2', smarty_get_custom_label_2($product)));
        $item->appendChild($dom->createElementNS($gNamespace, 'g:custom_label_3', smarty_get_custom_label_3($product)));
        $item->appendChild($dom->createElementNS($gNamespace, 'g:custom_label_4', smarty_get_custom_label_4($product)));
    }

    /**
     * Generates the custom Google product review feed.
     * 
     * This function retrieves all the products and their reviews from a WooCommerce store and constructs
     * an XML feed that adheres to Google's specifications for review feeds.
     * 
     * @since    1.0.0
     */
    public static function generate_google_reviews_feed() {
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
                if ($review->comment_approved == '1') { // Check if the review is approved before including it in the feed
                    // Create a new 'entry' element for each review
                    $entry = $dom->createElement('entry');
                    $feed->appendChild($entry);

                    // Add various child elements required by Google, ensuring data is properly escaped to avoid XML errors
                    $entry->appendChild($dom->createElementNS($gNamespace, 'g:id', htmlspecialchars(get_the_product_sku($product->ID))));
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
        echo $dom->saveXML();
        exit; // Ensure the script stops here to prevent further output that could corrupt the feed
    }

    /**
     * Generates a CSV file export of products for Google Merchant Center or similar services.
     * 
     * This function handles generating a downloadable CSV file that includes details about products
     * filtered based on certain criteria such as stock status and product category.
     * 
     * @since    1.0.0
     */
    public static function generate_csv_export() {
        // Set headers to force download and define the file name
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="woocommerce-products.csv"');
    
        // Open PHP output stream as a writable file
        $handle = fopen('php://output', 'w');
        
        // Check if the file handle is valid
        if ($handle === false) {
            wp_die('Failed to open output stream for CSV export'); // Kill the script and display message if handle is invalid
        }
    
        // Define the header row of the CSV file
        $headers = array(
            'ID',                       // WooCommerce product ID
            'ID2',                      // SKU, often used as an alternate identifier
            'Final URL',                // URL to the product page
            'Final Mobile URL',         // URL to the product page, mobile-specific if applicable
            'Image URL',                // Main image URL
            'Item Title',               // Title of the product
            'Item Description',         // Description of the product
            'Item Category',            // Categories the product belongs to
            'Price',                    // Regular price of the product
            'Sale Price',               // Sale price if the product is on discount
            'Google Product Category',  // Google's product category if needed for feeds
            'Is Bundle',                // Indicates if the product is a bundle
            'MPN',                      // Manufacturer Part Number
            'Availability',             // Stock status
            'Condition',                // Condition of the product, usually "new" for e-commerce
            'Brand',                    // Brand of the product
            'Custom Label 0',           // Custom label 0
            'Custom Label 1',           // Custom label 1
            'Custom Label 2',           // Custom label 2
            'Custom Label 3',           // Custom label 3
            'Custom Label 4',           // Custom label 4
        );
    
        // Write the header row to the CSV file
        fputcsv($handle, $headers);

        // Get excluded categories from settings
        $excluded_categories = get_option('smarty_excluded_categories', array());
        //error_log('Excluded Categories: ' . print_r($excluded_categories, true));

        // Prepare arguments for querying products excluding specific categories
        $args = array(
            'status'        => 'publish',              // Only fetch published products
            'stock_status'  => 'instock',              // Only fetch products that are in stock
            'limit'         => -1,                     // Fetch all products that match criteria
            'orderby'       => 'date',                 // Order by date
            'order'         => 'DESC',                 // In descending order
            'type'          => ['simple', 'variable'], // Include both simple and variable products
            'tax_query'     => array(
                array(
                    'taxonomy'  => 'product_cat',
                    'field'     => 'term_id',
                    'terms'     => $excluded_categories,
                    'operator'  => 'NOT IN',            // Exclude products from these categories
                ),
            ),
        );
        
        // Retrieve products using the defined arguments
        $products = wc_get_products($args);
        //error_log('Product Query Args: ' . print_r($args, true));
        //error_log('Products: ' . print_r($products, true));

        // Get exclude patterns from settings and split into array
        $exclude_patterns = preg_split('/\r\n|\r|\n/', get_option('smarty_exclude_patterns'));

        // Check if Google category should be ID
        $google_category_as_id = get_option('smarty_google_category_as_id', false);

        // Iterate through each product
        foreach ($products as $product) {
            // Retrieve the product URL
            $product_link = get_permalink($product->get_id());
    
            // Skip products whose URL contains any of the excluded patterns
            foreach ($exclude_patterns as $pattern) {
                if (strpos($product_link, $pattern) !== false) {
                    continue 2; // Continue to the next product
                }
            }
            
            // Convert the first WebP image to PNG if needed
            $image_id = $product->get_image_id();
            $image_url = wp_get_attachment_url($image_id);
            $file_path = get_attached_file($image_id);

            if ($file_path && preg_match('/\.webp$/', $file_path)) {
                $new_file_path = preg_replace('/\.webp$/', '.png', $file_path);
                if (Smarty_Gfg_Admin::convert_webp_to_png($file_path, $new_file_path)) {
                    // Update the attachment file type post meta
                    wp_update_attachment_metadata($image_id, wp_generate_attachment_metadata($image_id, $new_file_path));
                    update_post_meta($image_id, '_wp_attached_file', $new_file_path);
                    // Regenerate thumbnails
                    if (function_exists('wp_update_attachment_metadata')) {
                        wp_update_attachment_metadata($image_id, wp_generate_attachment_metadata($image_id, $new_file_path));
                    }
                    // Update image URL
                    $image_url = wp_get_attachment_url($image_id);
                    // Optionally, delete the original WEBP file
                    @unlink($file_path);
                }
            }

            // Prepare product data for the CSV
            $id = $product->get_id();
            $regular_price = $product->get_regular_price();
            $sale_price = $product->get_sale_price() ?: ''; // Fallback to empty string if no sale price
            $categories = wp_get_post_terms($id, 'product_cat', array('fields' => 'names'));
            $categories = !empty($categories) ? implode(', ', $categories) : '';
            $image_id = $product->get_image_id();
            $image_link = $image_id ? wp_get_attachment_url($image_id) : '';

            // Custom meta fields for title and description if set
            $meta_title = get_post_meta($id, get_option('smarty_meta_title_field', 'meta-title'), true);
            $meta_description = get_post_meta($id, get_option('smarty_meta_description_field', 'meta-description'), true);
            $name = !empty($meta_title) ? htmlspecialchars(strip_tags($meta_title)) : htmlspecialchars(strip_tags($product->get_name()));
            $description = !empty($meta_description) ? htmlspecialchars(strip_tags($meta_description)) : htmlspecialchars(strip_tags($product->get_short_description()));
            $description = preg_replace('/\s+/', ' ', $description); // Normalize whitespace in descriptions
            $availability = $product->is_in_stock() ? 'in stock' : 'out of stock';
            
            // Get Google category as ID or name
            $google_product_category = Smarty_Gfg_Admin::get_cleaned_google_product_category(); // Get Google category from plugin settings
            //error_log('Google Product Category: ' . $google_product_category); // Debugging line
            if ($google_category_as_id) {
                $google_product_category = explode('-', $google_product_category)[0]; // Get only the ID part
                //error_log('Google Product Category ID: ' . $google_product_category); // Debugging line
            }

            $brand = get_bloginfo('name');

            // Custom Labels
            $custom_label_0 = Smarty_Gfg_Admin::get_custom_label_0($product);
            $custom_label_1 = Smarty_Gfg_Admin::custom_label_1($product);
            $custom_label_2 = Smarty_Gfg_Admin::custom_label_2($product);
            $custom_label_3 = Smarty_Gfg_Admin::custom_label_3($product);
            $custom_label_4 = Smarty_Gfg_Admin::custom_label_4($product);

            // Check if the product has the "bundle" tag
            $is_bundle = 'no';
            $product_tags = wp_get_post_terms($product->get_id(), 'product_tag', array('fields' => 'slugs'));
            if (in_array('bundle', $product_tags)) {
                $is_bundle = 'yes';
            }
            
            // Check for variable type to handle variations
            if ($product->is_type('variable')) {
                // Handle variations separately if product is variable
                $variations = $product->get_children();
                if (!empty($variations)) {
                    $first_variation_id = reset($variations); // Get only the first variation
                    $variation = wc_get_product($first_variation_id);
                    $sku = $variation->get_sku();
                    $variation_image = wp_get_attachment_url($variation->get_image_id());
                    $variation_price = $variation->get_regular_price();
                    $variation_sale_price = $variation->get_sale_price() ?: '';
                    
                    // Prepare row for each variation
                    $row = array(
                        'ID'                      => $id,
                        'ID2'                     => $sku,
                        'Final URL'               => $product_link,
                        'Final Mobile URL'        => $product_link,
                        'Image URL'               => $variation_image,
                        'Item Title'              => $name,
                        'Item Description'        => $description,
                        'Item Category'           => $categories,
                        'Price'                   => $variation_price,
                        'Sale Price'              => $variation_sale_price,
                        'Google Product Category' => $google_product_category,
                        'Is Bundle'               => $is_bundle,
                        'MPN'                     => $sku,
                        'Availability'            => $availability,
                        'Condition'               => 'New',
                        'Brand'                   => $brand,
                        'Custom Label 0'          => $custom_label_0, 
                        'Custom Label 1'          => $custom_label_1, 
                        'Custom Label 2'          => $custom_label_2,
                        'Custom Label 3'          => $custom_label_3, 
                        'Custom Label 4'          => $custom_label_4,
                    );
                }
            } else {
                // Prepare row for a simple product
                $sku = $product->get_sku();
                $row = array(
                    'ID'                      => $id,
                    'ID2'                     => $sku,
                    'Final URL'               => $product_link,
                    'Final Mobile URL'        => $product_link,
                    'Image URL'               => $image_link,
                    'Item Title'              => $name,
                    'Item Description'        => $description,
                    'Item Category'           => $categories,
                    'Price'                   => $regular_price,
                    'Sale Price'              => $sale_price,
                    'Google Product Category' => $google_product_category,
                    'Is Bundle'               => $is_bundle,
                    'MPN'                     => $sku,
                    'Availability'            => $availability,
                    'Condition'               => 'New',
                    'Brand'                   => $brand,
                    'Custom Label 0'          => $custom_label_0, 
                    'Custom Label 1'          => $custom_label_1, 
                    'Custom Label 2'          => $custom_label_2,
                    'Custom Label 3'          => $custom_label_3, 
                    'Custom Label 4'          => $custom_label_4,
                );
            }
    
            // Only output the row if the SKU is set (some products may not have variations correctly set)
            if (!empty($sku)) {
                fputcsv($handle, $row);
            }
        }
    
        fclose($handle);
        exit;
    }

    /**
     * Invalidate cache or regenerate feed when a product is created, updated, or deleted.
     */
    public function invalidate_feed_cache($product_id) {
        // Check if the post is a 'product'
        if (get_post_type($product_id) === 'product') {
            // Invalidate cache
			delete_transient('smarty_google_feed');
			// Regenerate the feed
			self::regenerate_feed();
        }
    }

    /**
     * Invalidates the cached Google product feed and optionally regenerates it upon the deletion of a product.
     * 
     * This function ensures that when a product is deleted from WooCommerce, any cached version of the feed
     * does not continue to include data related to the deleted product.
     *
     * @since    1.0.0
     * @param int $post_id The ID of the post (product) being deleted.
     */
    public function invalidate_feed_cache_on_delete($post_id) {
        // Check if the post being deleted is a product
        if (get_post_type($post_id) === 'product') {
            // Invalidate the cache by deleting the stored transient that contains the feed data
            // This forces the system to regenerate the feed next time it's requested, ensuring that the deleted product is no longer included
            delete_transient('smarty_google_feed');
            
            // Regenerate the feed immediately to update the feed file
            // This step is optional but recommended to keep the feed up to date
            self::regenerate_feed();
        }
    }

    /**
     * Invalidate cache or regenerate review feed when reviews are added, updated, or deleted.
     * 
     * @since    1.0.0
     */
    public function invalidate_review_feed_cache($comment_id, $comment_approved = '') {
        $comment = get_comment($comment_id);
        $post_id = $comment->comment_post_ID;
        
        // Check if the comment is for a 'product' and approved
        if (get_post_type($post_id) === 'product' && ($comment_approved == 1 || $comment_approved == 'approve')) {
            // Invalidate cache
            delete_transient('smarty_google_reviews_feed');
            // Optionally, regenerate the feed file
            self::regenerate_google_reviews_feed();
        }
    }

    /**
     * Regenerates the feed and saves it to a transient or a file.
     * 
     * This function is designed to refresh the product feed by querying the latest product data from WooCommerce,
     * constructing an XML feed, and saving it either to a transient for fast access or directly to a file.
     * 
     * @since    1.0.0
     */
	public function regenerate_feed() {
        // Fetch products from WooCommerce that are published and in stock
		$products = wc_get_products(array(
			'status'        => 'publish',
            'stock_status'  => 'instock',
			'limit'         => -1,          // No limit to ensure all qualifying products are included
			'orderby'       => 'date',      // Order by product date
			'order'         => 'DESC',      // Order in descending order
		));

        // Initialize XML structure with a root element and namespace attribute
		$xml = new SimpleXMLElement('<feed xmlns:g="http://base.google.com/ns/1.0"/>');
		
        // Iterate through each product to populate the feed
		foreach ($products as $product) {
            if ($product->is_type('variable')) {
                // Handle variable products, which can have multiple variations
                foreach ($product->get_children() as $child_id) {
                    $variation = wc_get_product($child_id);
                    $item = $xml->addChild('item');
                    $gNamespace = 'http://base.google.com/ns/1.0';

                    // Add basic product and variation details
                    $item->addChild('g:id', $variation->get_id(), $gNamespace);
                    $item->addChild('g:sku', $variation->get_sku(), $gNamespace);
                    $item->addChild('title', htmlspecialchars($product->get_name()), $gNamespace);
                    $item->addChild('link', get_permalink($product->get_id()), $gNamespace);
                    
                    // Description: Use main product description or fallback to the short description if empty
                    $description = $product->get_description();
                    if (empty($description)) {
                        // Fallback to short description if main description is empty
                        $description = $product->get_short_description();
                    }

                    if (!empty($description)) {
                        // Ensure that HTML tags are removed and properly encoded
                        $item->addChild('description', htmlspecialchars(strip_tags($description)), $gNamespace);
                    } else {
                        $item->addChild('description', 'No description available', $gNamespace);
                    }

                    $item->addChild('image_link', wp_get_attachment_url($product->get_image_id()), $gNamespace);

                    // Variation specific image, if different from the main product image
                    $image_id = $variation->get_image_id() ? $variation->get_image_id() : $product->get_image_id();
                    $variationImageURL = wp_get_attachment_url($variation->get_image_id());
                    $mainImageURL = wp_get_attachment_url($product->get_image_id());
                    if ($variationImageURL !== $mainImageURL) {
                        $item->addChild('image_link', wp_get_attachment_url($image_id), $gNamespace);
                    }

                    // Additional images: Loop through gallery if available
                    $gallery_ids = $product->get_gallery_image_ids();
                    foreach ($gallery_ids as $gallery_id) {
                        $item->addChild('additional_image_link', wp_get_attachment_url($gallery_id), $gNamespace);
                    }

                    // Pricing: Regular and sale prices
                    $item->addChild('price', htmlspecialchars($variation->get_regular_price() . ' ' . get_woocommerce_currency()), $gNamespace);
                    if ($variation->is_on_sale()) {
                        $item->addChild('sale_price', htmlspecialchars($variation->get_sale_price() . ' ' . get_woocommerce_currency()), $gNamespace);
                    }

                    // Categories: Compile a list from the product's categories
                    $categories = wp_get_post_terms($product->get_id(), 'product_cat');
                    if (!empty($categories) && !is_wp_error($categories)) {
                        $category_names = array_map(function($term) { return $term->name; }, $categories);
                        $item->addChild('product_type', htmlspecialchars(join(' > ', $category_names)), $gNamespace);
                    }
                }
            } else {
                // Handle simple products
                $item = $xml->addChild('item');
                $gNamespace = 'http://base.google.com/ns/1.0';
                $item->addChild('g:id', $product->get_id(), $gNamespace);
                $item->addChild('g:sku', $product->get_sku(), $gNamespace);
                $item->addChild('title', htmlspecialchars($product->get_name()), $gNamespace);
                $item->addChild('link', get_permalink($product->get_id()), $gNamespace);

                // Description handling
                $description = $product->get_description();
                if (empty($description)) {
                    // Fallback to short description if main description is empty
                    $description = $product->get_short_description();
                }

                if (!empty($description)) {
                    // Ensure that HTML tags are removed and properly encoded
                    $item->addChild('description', htmlspecialchars(strip_tags($description)), $gNamespace);
                } else {
                    $item->addChild('description', 'No description available', $gNamespace);
                }
                
                 // Main image and additional images
                $item->addChild('image_link', wp_get_attachment_url($product->get_image_id()), $gNamespace);
                $gallery_ids = $product->get_gallery_image_ids();
                foreach ($gallery_ids as $gallery_id) {
                    $item->addChild('additional_image_link', wp_get_attachment_url($gallery_id), $gNamespace);
                }
    
                // Pricing information
                $item->addChild('price', htmlspecialchars($product->get_price() . ' ' . get_woocommerce_currency()), $gNamespace);
                if ($product->is_on_sale() && !empty($product->get_sale_price())) {
                    $item->addChild('sale_price', htmlspecialchars($product->get_sale_price() . ' ' . get_woocommerce_currency()), $gNamespace);
                }
    
                // Category information
                $categories = wp_get_post_terms($product->get_id(), 'product_cat');
                if (!empty($categories) && !is_wp_error($categories)) {
                    $category_names = array_map(function($term) { return $term->name; }, $categories);
                    $item->addChild('product_type', htmlspecialchars(join(' > ', $category_names)), $gNamespace);
                }
            }
        }

        // Save the generated XML content to a transient or a file for later use
		$feed_content = $xml->asXML();
        $cache_duration = get_option('smarty_cache_duration', 12); // Default to 12 hours if not set
        set_transient('smarty_google_feed', $feed_content, $cache_duration * HOUR_IN_SECONDS);  // Cache the feed using WordPress transients              
		file_put_contents(WP_CONTENT_DIR . '/uploads/smarty_google_feed.xml', $feed_content);   // Optionally save the feed to a file in the WP uploads directory
	}

    /**
     * Hook into product changes.
     * 
     * @since    1.0.0
     */
    public function handle_product_change($post_id) {
        if (get_post_type($post_id) == 'product') {
            self::regenerate_feed(); // Regenerate the feed
	    }
    }

    /**
	 * @since    1.0.0
	 */
    public function handle_ajax_generate_feed() {
        check_ajax_referer('smarty_feed_generator_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('You do not have sufficient permissions to access this page.');
        }

        $action = sanitize_text_field($_POST['feed_action']);
        switch ($action) {
            case 'generate_product_feed':
                self::generate_google_feed();
                wp_send_json_success('Product feed generated successfully.');
                break;
            case 'generate_reviews_feed':
                self::generate_google_reviews_feed();
                wp_send_json_success('Reviews feed generated successfully.');
                break;
            case 'generate_csv_export':
                self::generate_csv_export();
                wp_send_json_success('CSV export generated successfully.');
                break;
            default:
                wp_send_json_error('Invalid action.');
                break;
        }
    }

    /**
     * Custom Label 0: Older Than X Days & Not Older Than Y Days
     * 
     * @since    1.0.0
     */ 
    public function get_custom_label_0($product) {
        $date_created = $product->get_date_created();
        $now = new DateTime();
            
        // Older Than X Days
        $older_than_days = get_option('smarty_custom_label_0_older_than_days', 30);
        $older_than_value = get_option('smarty_custom_label_0_older_than_value', 'established');
        
        if ($date_created && $now->diff($date_created)->days > $older_than_days) {
            return $older_than_value;
        }
    
        // Not Older Than Y Days
        $not_older_than_days = get_option('smarty_custom_label_0_not_older_than_days', 30);
        $not_older_than_value = get_option('smarty_custom_label_0_not_older_than_value', 'new');
        
        if ($date_created && $now->diff($date_created)->days <= $not_older_than_days) {
            return $not_older_than_value;
        }
    
        return '';
    }
    
    /**
     * Custom Label 1: Most Ordered in Last Z Days 
     * 
     * @since    1.0.0
     */ 
    public function get_custom_label_1($product) {
        $most_ordered_days = get_option('smarty_custom_label_1_most_ordered_days', 30);
        $most_ordered_value = get_option('smarty_custom_label_1_most_ordered_value', 'bestseller');
    
        $args = [
            'post_type'      => 'shop_order',
            'post_status'    => ['wc-completed', 'wc-processing'],
            'posts_per_page' => -1,
            'date_query'     => [
                'after' => date('Y-m-d', strtotime("-$most_ordered_days days")),
            ],
        ];
    
        $orders = get_posts($args);
    
        foreach ($orders as $order_post) {
            $order = wc_get_order($order_post->ID);
            foreach ($order->get_items() as $item) {
                if ($item->get_product_id() == $product->get_id() || $item->get_variation_id() == $product->get_id()) {
                    return $most_ordered_value;
                }
            }
        }
    
        return '';
    }
    
    /**
     * Custom Label 2: High Rating
     * 
     * @since    1.0.0
     */
    public function get_custom_label_2($product) {
        $average_rating = $product->get_average_rating();
        $high_rating_value = get_option('smarty_custom_label_2_high_rating_value', 'high_rating');
        if ($average_rating >= 4) {
            return $high_rating_value;
        }
        return '';
    }
    
    /**
     * Custom Label 3: In Selected Category
     * 
     * @since    1.0.0
     */
    public function get_custom_label_3($product) {
        // Retrieve selected categories and their values from options
        $selected_categories = get_option('smarty_custom_label_3_category', []);
        $selected_category_values = get_option('smarty_custom_label_3_category_value', 'category_selected');
            
        // Ensure selected categories and values are arrays and strip any whitespace
        if (!is_array($selected_categories)) {
            $selected_categories = array_map('trim', explode(',', $selected_categories));
        } else {
            $selected_categories = array_map('trim', $selected_categories);
        }
    
        if (!is_array($selected_category_values)) {
            $selected_category_values = array_map('trim', explode(',', $selected_category_values));
        } else {
            $selected_category_values = array_map('trim', $selected_category_values);
        }
    
        // Retrieve the product's categories
        $product_categories = wp_get_post_terms($product->get_id(), 'product_cat', ['fields' => 'ids']);
    
        // Initialize an array to hold the values to be returned
        $matched_values = [];
    
        // Check each selected category
        foreach ($selected_categories as $index => $category) {
            if (in_array($category, $product_categories)) {
                // Add the corresponding value to the matched values array
                if (isset($selected_category_values[$index])) {
                    $matched_values[] = $selected_category_values[$index];
                }
            }
        }
    
        // Remove duplicates
        $matched_values = array_unique($matched_values);
    
        // Return the matched values as a comma-separated string
        return implode(', ', $matched_values);
    }
    
    /**
     * Custom Label 4: Has Sale 
     * 
     * @since    1.0.0
     */
    public function get_custom_label_4($product) {
        $excluded_categories = get_option('smarty_excluded_categories', array()); // Get excluded categories from settings
        $product_categories = wp_get_post_terms($product->get_id(), 'product_cat', array('fields' => 'ids'));
    
        // Check if product is in excluded categories
        $is_excluded = !empty(array_intersect($excluded_categories, $product_categories));
    
        // Log debug information
        smarty_write_logs('Product ID: ' . $product->get_id());
        smarty_write_logs('Product is on sale: ' . ($product->is_on_sale() ? 'yes' : 'no'));
        smarty_write_logs('Product sale price: ' . $product->get_sale_price());
        smarty_write_logs('Excluded categories: ' . print_r($excluded_categories, true));
        smarty_write_logs('Product categories: ' . print_r($product_categories, true));
        smarty_write_logs('Is product excluded: ' . ($is_excluded ? 'yes' : 'no'));
    
        if ($is_excluded) {
            return '';
        }
    
        // Handle single products
        if ($product->is_type('simple')) {
            if ($product->is_on_sale() && !empty($product->get_sale_price())) {
                return get_option('smarty_custom_label_4_sale_price_value', 'on_sale');
            }
        }
    
        // Handle variable products
        if ($product->is_type('variable')) {
            $variations = $product->get_children();
            
            if (!empty($variations)) {
                $first_variation_id = $variations[0]; // Check only the first variation
                $variation = wc_get_product($first_variation_id);
                
                smarty_write_logs('First Variation ID: ' . $variation->get_id() . ' is on sale: ' . ($variation->is_on_sale() ? 'yes' : 'no') . ' Sale price: ' . $variation->get_sale_price());
                
                if ($variation->is_on_sale() && !empty($variation->get_sale_price())) {
                    return get_option('smarty_custom_label_4_sale_price_value', 'on_sale');
                }
            }
        }
    
        return '';
    }
    
    /**
     * @since    1.0.0
     */
    public function evaluate_criteria($product, $criteria) {
        if (empty($criteria)) {
            return '';
        }
    
        $criteria = json_decode($criteria, true);
    
        if (json_last_error() !== JSON_ERROR_NONE) {
            return '';
        }
    
        foreach ($criteria as $criterion) {
            if (isset($criterion['attribute']) && isset($criterion['value']) && isset($criterion['label'])) {
                $attribute_value = get_post_meta($product->get_id(), $criterion['attribute'], true);
    
                if ($attribute_value == $criterion['value']) {
                    return $criterion['label'];
                }
            }
        }
    
        return '';
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