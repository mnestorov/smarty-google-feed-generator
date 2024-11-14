<?php
/**
 * The API functionality of the plugin.
 *
 * @link       https://github.com/mnestorov/smarty-google-feed-generator
 * @since      1.0.0
 *
 * @package    Smarty_Google_Feed_Generator
 * @subpackage Smarty_Google_Feed_Generator/includes/classes
 * @author     Smarty Studio | Martin Nestorov
 */
class Smarty_Gfg_API {

    /**
     * The base URL for the license API endpoint.
     *
     * This URL is used to make requests to the License Manager API. 
     * It's typically defined as a constant in the main plugin file and passed 
     * to this class during instantiation.
     *
     * @since   1.0.1
     * @access  private
     * @var     string $api_url The base API URL for license validation requests.
     */
    private $api_url;

    /**
     * The consumer key for API authentication.
     *
     * This key, along with the consumer secret, is used to authenticate API requests
     * to the License Manager. It is passed as a parameter during class instantiation.
     *
     * @since   1.0.1
     * @access  private
     * @var     string $consumer_key The consumer key for secure API access.
     */
    private $consumer_key;

    /**
     * The consumer secret for API authentication.
     *
     * Used in conjunction with the consumer key to authenticate requests to the License Manager API.
     * It is passed to the class during instantiation and should be kept private to ensure secure access.
     *
     * @since   1.0.1
     * @access  private
     * @var     string $consumer_secret The consumer secret for secure API access.
     */
    private $consumer_secret;

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
	 * @since    1.0.1
	 */
    public function __construct($consumer_key, $consumer_secret) {
        if (defined('API_URL')) {
			$this->api_url = API_URL;
		} else {
			$this->api_url = '';
		}
		
        $this->consumer_key = $consumer_key;
        $this->consumer_secret = $consumer_secret;
        $this->plugin_name = 'smarty-google-feed-generator';
		
		if (defined('GFG_VERSION')) {
			$this->version = GFG_VERSION;
		} else {
			$this->version = '1.0.1';
		}
    }

     /**
     * Makes an API request.
     *
     * @since    1.0.1
     * @param    string $endpoint API endpoint to call.
     * @param    string $method HTTP method to use.
     * @param    array $query_params Optional. Array of query parameters.
     * @return   array|false Response body as an array on success, false on failure.
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
            //_gfg_write_logs('API request error: ' . $response->get_error_message());
            return false;
        }
    
        //_gfg_write_logs('API request response: ' . print_r($response, true));
        return json_decode(wp_remote_retrieve_body($response), true);
    }
    
    /**
     * Validates a license key with additional site information.
     *
     * @since    1.0.1
     * @param    string $license_key The license key to validate.
     * @return   bool True if license is active, false otherwise.
     */
    public function validate_license($license_key) {
        $web_server = esc_html($_SERVER['SERVER_SOFTWARE']);
        $server_ip = $_SERVER['SERVER_ADDR'] ?? 'unknown';
        $php_version = esc_html(PHP_VERSION);
        $browser_device_info = smarty_gfg_get_browser_and_device_type();
        $user_ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $browser = urlencode($browser_device_info['browser']);
        $device_type = urlencode($browser_device_info['device_type']);
        $os = urlencode(smarty_gfg_get_os());
        
        // Construct the endpoint URL with license key, site URL, WP version, server software, and server IP
        $endpoint = sprintf(
            '?license_key=%s&site_url=%s&wp_version=%s&web_server=%s&server_ip=%s&php_version=%s&plugin_name=%s&plugin_version=%s&user_ip=%s&browser=%s&device_type=%s&os=%s',
            urlencode($license_key),
            urlencode(get_site_url()),
            urlencode(get_bloginfo('version')),
            urlencode($web_server),
            urlencode($server_ip),
            urlencode($php_version),
            urlencode($this->plugin_name),
            urlencode($this->version),
            urlencode($user_ip),
            $browser,
            $device_type,
            $os,
        );
    
        // Make the request with the updated endpoint
        return $this->make_api_request($endpoint);
    }
}