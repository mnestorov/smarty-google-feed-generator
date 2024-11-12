<?php
/**
 * The API functionality of the plugin.
 *
 * @link       https://github.com/mnestorov
 * @since      1.0.0
 *
 * @package    Smarty_Google_Feed_Generator
 * @subpackage Smarty_Google_Feed_Generator/includes/classes
 * @author     Smarty Studio | Martin Nestorov
 */
class Smarty_Gfg_API {
    private $api_url = 'https://smartystudio.net/lm/wp-json/license-manager/v1/check-license';
    private $consumer_key; // Set your Consumer Key
    private $consumer_secret; // Set your Consumer Secret

    /**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;


    /**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;
    
    /**
	 * @since    1.0.0
	 */
    public function __construct($consumer_key, $consumer_secret) {
        $this->consumer_key = $consumer_key;
        $this->consumer_secret = $consumer_secret;

        $this->plugin_name = 'smarty_google_feed_generator';
		
		if (defined('GFG_VERSION')) {
			$this->version = GFG_VERSION;
		} else {
			$this->version = '1.0.0';
		}
    }

     /**
     * Makes an API request.
     *
     * @param string $endpoint API endpoint to call.
     * @param string $method HTTP method to use.
     * @param array $query_params Optional. Array of query parameters.
     * @return array|false Response body as an array on success, false on failure.
     */
    private function make_api_request($endpoint, $method = 'GET', $query_params = []) {
        $url = $this->api_url . $endpoint;

        // Add query parameters to URL if they exist
        if (!empty($query_params)) {
            $url = add_query_arg($query_params, $url);
        }

        $args = array(
            'method'    => $method,
            'headers'   => array(
                'Authorization' => 'Basic ' . base64_encode($this->consumer_key . ':' . $this->consumer_secret)
            )
        );

        $response = wp_remote_request($url, $args);

        if (is_wp_error($response)) {
            _gfg_write_logs('API request error: ' . $response->get_error_message());
            return false;
        }
    
        _gfg_write_logs('API request response: ' . print_r($response, true));
        return json_decode(wp_remote_retrieve_body($response), true);
    }
    
    /**
     * Validates a license key with additional site information.
     *
     * @param string $license_key The license key to validate.
     * @return bool True if license is active, false otherwise.
     */
    public function validate_license($license_key) {
        $web_server = esc_html($_SERVER['SERVER_SOFTWARE']);
        $server_ip = $_SERVER['SERVER_ADDR'] ?? 'unknown';
        $php_version = esc_html(PHP_VERSION);
        
        // Construct the endpoint URL with license key, site URL, WP version, server software, and server IP
        $endpoint = sprintf(
            '?license_key=%s&site_url=%s&wp_version=%s&web_server=%s&server_ip=%s&php_version=%s&plugin_name=%s&plugin_version=%s',
            urlencode($license_key),
            urlencode(get_site_url()),
            urlencode(get_bloginfo('version')),
            urlencode($web_server),
            urlencode($server_ip),
            urlencode($php_version),
            urlencode($this->plugin_name),
            urlencode($this->version)
        );
    
        // Make the request with the updated endpoint
        return $this->make_api_request($endpoint);
    }
}