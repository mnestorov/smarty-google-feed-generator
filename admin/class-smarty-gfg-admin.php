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