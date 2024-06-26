<?php

class Smarty_Gfg_Activity_Logging {

    /**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 */
    public function __construct() {
        add_action('admin_init', array($this, 'settings_init'));
        add_action('wp_ajax_smarty_clear_logs', array($this, 'handle_ajax_clear_logs'));
    }

    /**
	 * Initializes the Google Reviews Feed settings by registering the settings, sections, and fields.
	 *
	 * @since    1.0.0
	 */
    public function settings_init() {
        register_setting('smarty_gfg_options_activity_logging', 'smarty_gfg_settings_activity_logging');
		register_setting('smarty_gfg_options_activity_logging', 'smarty_gfg_settings_activity_log');

        add_settings_section(
			'smarty_gfg_section_activity_logging',								// ID of the section
			__('Activity & Logging', 'smarty-google-feed-generator'),    		// Title of the section  		
			array($this, 'section_tab_activity_logging_cb'),                	// Callback function that fills the section with the desired content	
			'smarty_gfg_options_activity_logging'                   			// Page on which to add the section   		
		);

		add_settings_field(
            'smarty_system_info', 												// ID of the field
            __('System Info', 'smarty-google-feed-generator'), 					// Title of the field
            array($this, 'system_info_cb'), 									// Callback function to display the field
            'smarty_gfg_options_activity_logging', 								// Page on which to add the field
            'smarty_gfg_section_activity_logging' 								// Section to which this field belongs
        );

		add_settings_field(
			'smarty_activity_log',												// ID of the section
			__('Activity Log', 'smarty-google-feed-generator'),    				// Title of the section  		
			array($this, 'activity_log_cb'),                					// Callback function that fills the section with the desired content	
			'smarty_gfg_options_activity_logging',                   			// Page on which to add the section
			'smarty_gfg_section_activity_logging'								// Section to which this field belongs  		
		);
    }

    /**
	 * Callback function for the Activity & Logging section field.
	 *
	 * @since    1.0.0
	 */
	public static function section_tab_activity_logging_cb() {
		echo '<p>' . __('View and manage the activity logs for the plugin.', 'smarty-google-feed-generator') . '</p>';
	}

    /**
	 * Callback function for the Activity & Logging section field.
	 *
	 * @since    1.0.0
	 */
	public static function activity_log_cb() {
		$instance = new self('Smarty_Gfg_Admin', '1.0.0');
		$instance->display_activity_log();
	}

    /**
     * Callback function to display the system info.
     *
     * @since    1.0.0
     */
    public function system_info_cb() {
		$system_info = $this->get_system_info();
		echo '<ul>';
		foreach ($system_info as $key => $value) {
			echo '<li><strong>' . esc_html($key) . ':</strong> ' . wp_kses($value, array('span' => array('style' => array()))) . '</li>';
		}
		echo '</ul>';
	}

	/**
     * Get system information.
     *
     * @since    1.0.0
     * @return string System information.
     */
    private function get_system_info() {
		$system_info = array(
			'User Agent' => esc_html($_SERVER['HTTP_USER_AGENT']),
			'Web Server' => esc_html($_SERVER['SERVER_SOFTWARE']),
			'PHP Version' => esc_html(PHP_VERSION),
			'PHP Max POST Size' => esc_html(ini_get('post_max_size')),
			'PHP Max Upload Size' => esc_html(ini_get('upload_max_filesize')),
			'PHP Memory Limit' => esc_html(ini_get('memory_limit')),
			'PHP DateTime Class' => class_exists('DateTime') ? '<span style="color: #28a745;">Available</span>' : '<span style="color: #c82333;">Not Available</span>',
			'PHP Curl' => function_exists('curl_version') ? '<span style="color: #28a745;">Available</span>' : '<span style="color: #c82333;">Not Available</span>',
		);
		
		return $system_info;
	}

	/**
	 * Display the activity log.
	 *
	 * @since    1.0.0
	 */
	public function display_activity_log() {
		// Retrieve log entries from the database or file
		$logs = get_option('smarty_activity_log', array());

		if (empty($logs)) {
			echo '<ul><li><span style="color: #c82333;">' . esc_html__('Log empty', 'smarty-google-feed-generator') . '</span></li></ul>';
			echo '<button id="delete-logs-button" class="btn btn-danger disabled" disabled>' . esc_html__('Delete Logs', 'smarty-google-feed-generator') . '</button>';
		} else {
			echo '<ul>';
			foreach ($logs as $log) {
				echo '<li>' . esc_html($log) . '</li>';
			}
			echo '</ul>';
			echo '<button id="delete-logs-button" class="btn btn-danger">' . esc_html__('Delete Logs', 'smarty-google-feed-generator') . '</button>';
		}
	}

	/**
	 * Add an entry to the activity log.
	 *
	 * @since    1.0.0
	 * @param string $message The log message.
	 */
	public static function add_activity_log($message) {
		$logs = get_option('smarty_activity_log', array());
		
		// Get the current time in the specified format
		$time_format = 'Y-M-d H:i:s';  // PHP date format
		$current_time = current_time($time_format);
	
		// Get the timezone
		$timezone = new DateTimeZone(get_option('timezone_string') ?: 'UTC');
		$datetime = new DateTime(null, $timezone);
		$timezone_name = $datetime->format('T');  // Gets the timezone abbreviation, e.g., GMT
	
		// Combine time, timezone, and message
		$log_entry = sprintf("[%s %s] - %s", $current_time, $timezone_name, $message);
		$logs[] = $log_entry;
	
		// Save the updated logs back to the database
		update_option('smarty_activity_log', $logs);
	}

	/**
	 * Clear the activity log.
	 *
	 * @since    1.0.0
	 */
	public function clear_activity_log() {
		update_option('smarty_activity_log', array());
	}

	/**
	 * Handle the AJAX request to clear the logs.
	 *
	 * @since    1.0.0
	 */
	public function handle_ajax_clear_logs() {
		check_ajax_referer('smarty_feed_generator_nonce', 'nonce');

		if (!current_user_can('manage_options')) {
			wp_send_json_error('You do not have sufficient permissions to access this page.');
		}

		$this->clear_activity_log();
		wp_send_json_success(__('Logs cleared.', 'smarty-google-feed-generator'));
	}
}