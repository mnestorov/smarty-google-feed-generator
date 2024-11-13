<?php

/**
 * The License-specific functionality of the plugin.
 *
 * @link       https://github.com/mnestorov/smarty-google-feed-generator
 * @since      1.0.0
 *
 * @package    Smarty_Google_Feed_Generator
 * @subpackage Smarty_Google_Feed_Generator/admin/tabs
 * @author     Smarty Studio | Martin Nestorov
 */
class Smarty_Gfg_License {

    /**
     * Instance of Smarty_Gfg_API.
	 * 
     * @since    1.0.0
     * @var Smarty_Gfg_API
     */
    private $api_instance;

    /**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 */
    public function __construct() {
        // Instantiate the API class
        $this->api_instance = new Smarty_Gfg_API(CK_KEY, CS_KEY);
    }

    /**
	 * Initializes the License settings by registering the settings, sections, and fields.
	 *
	 * @since    1.0.0
	 */
    public function gfg_l_settings_init() {
        register_setting('smarty_gfg_options_license', 'smarty_gfg_settings_license', array($this, 'gfg_sanitize_license_settings'));

        add_settings_section(
			'smarty_gfg_section_license',										// ID of the section
			__('License', 'smarty-google-feed-generator'),						// Title of the section  
			array($this, 'gfg_section_tab_license_cb'),							// Callback function that fills the section with the desired content
			'smarty_gfg_options_license'										// Page on which to add the section
		);

		add_settings_field(
			'smarty_gfg_api_key',												// ID of the field
			__('License Key', 'smarty-google-feed-generator'),						// Title of the field
			array($this, 'gfg_field_api_key_cb'),								// Callback function to display the field
			'smarty_gfg_options_license',										// Page on which to add the field
			'smarty_gfg_section_license'										// Section to which this field belongs
		);
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
	public function gfg_sanitize_license_settings($input) {
        $new_input = array();
        if (isset($input['api_key'])) {
            $new_input['api_key'] = sanitize_text_field($input['api_key']);
        }
        return $new_input;
    }

    /**
     * Check if the API key is valid.
     * 
     * @since    1.0.0
     * @param string $api_key The API key to validate.
     * @return bool True if the API key is valid, false otherwise.
     */
    public function gfg_is_valid_api_key($api_key) {
        $response = $this->api_instance->validate_license($api_key);
    
        if (isset($response['status']) && $response['status'] === 'active') {
            _gfg_write_logs('License validation successful: ' . $api_key);
            return true;
        }
        
        _gfg_write_logs('License validation failed: ' . print_r($response, true));
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
	public function gfg_handle_license_status_check($option_name, $old_value, $value) {
        if (!$this->api_instance) {
			// Handle the error
			return;
		}

        if ($option_name === 'smarty_gfg_settings_license' && isset($value['api_key'])) {
            $api_key = $value['api_key'];
            
            // Check the license status
            $isValid = $this->gfg_is_valid_api_key($api_key);

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
     * Callback function for the License section.
     * 
     * @since    1.0.0
     * @param array $args Arguments for the callback.
     */
	public function gfg_section_tab_license_cb($args) {
		?>
		<p id="<?php echo esc_attr($args['id']); ?>">
			<?php echo esc_html__('Enter your License key to enable advanced features.', 'smarty-google-feed-generator'); ?>
		</p>
		<?php
	}

    /**
     * Callback function for the API key field.
     * 
     * @since    1.0.0
     * @param array $args Arguments for the callback.
     */
	public function gfg_field_api_key_cb($args) {
		$options = get_option('smarty_gfg_settings_license');
		?>
		<input type="text" id="smarty_gfg_api_key" name="smarty_gfg_settings_license[api_key]" size="30" value="<?php echo isset($options['api_key']) ? esc_attr($options['api_key']) : ''; ?>">
		<p class="description">
			<?php echo esc_html__('Enter a valid License key.', 'smarty-google-feed-generator'); ?>
		</p>
		<?php
	}

    /**
     * Function to check for transients and other conditions to display admin notice.
     *
     * @since    1.0.0
     */
    public function gfg_license_notice() {
        $options = get_option('smarty_gfg_settings_license');
		
		if (isset($_GET['license-activated']) && $_GET['license-activated'] == 'true') {
			?>
			<div class="notice notice-success smarty-gfg-auto-hide-notice">
				<p><?php echo esc_html__('License activated successfully.', 'smarty-google-feed-generator'); ?></p>
			</div>
			<?php
		}
    }
}