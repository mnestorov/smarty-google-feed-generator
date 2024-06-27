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
	 * Instance of Smarty_Gfg_Google_Products_Feed.
	 * 
	 * @since    1.0.0
	 * @access   private
	 */
	private $google_products_feedd;
	
	/**
	 * Instance of Smarty_Gfg_Google_Reviews_Feed.
	 * 
	 * @since    1.0.0
	 * @access   private
	 */
	private $google_reviews_feedd;

	/**
	 * Instance of Smarty_Gfg_Bing_Products_Feed.
	 * 
	 * @since    1.0.0
	 * @access   private
	 */
	private $bing_products_feedd;

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

		// Include and instantiate the Google Products Feed class
		$this->google_products_feed = new Smarty_Gfg_Google_Products_Feed();

		// Include and instantiate the Google Reviews Feed class
		$this->google_reviews_feed = new Smarty_Gfg_Google_Reviews_Feed();

		// Include and instantiate the Bing Products Feed class
		$this->bing_products_feed = new Smarty_Gfg_Bing_Products_Feed();
		
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

		// Google Products Feed settings
        $this->google_products_feed->settings_init();

		// Google Reviews Feed settings
        $this->google_reviews_feed->settings_init();

		// Bing Products Feed settings
        $this->bing_products_feed->settings_init();

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
     * Callback function for the General section.
     * 
     * @since    1.0.0
     */
	public function section_general_cb() {
		echo '<p>' . __('General settings for the Google Feed Generator.', 'smarty-google-feed-generator') . '</p>';
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
     * Callback function for the convert images button.
     * 
     * @since    1.0.0
     */
	public function convert_images_button_cb() {
		echo '<button class="button secondary smarty-convert-images-button" style="display: inline-block;">' . __('First WebP Image to PNG', 'smarty-google-feed-generator') . '</button>';
		echo '<button class="button secondary smarty-convert-all-images-button" style="display: inline-block; margin: 0 10px;">' . __('All WebP Images to PNG', 'smarty-google-feed-generator') . '</button>';
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