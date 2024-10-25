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
    private $api_url = WEB_APP_URL;
    //private $api_url = 'https://smartystudio.net/wp-json/lmfwc/v2/licenses';
    //private $consumer_key; // Set your Consumer Key
    //private $consumer_secret; // Set your Consumer Secret
    
    /**
	 * @since    1.0.0
	 */
    public function __construct($consumer_key, $consumer_secret) {
        //$this->consumer_key = $consumer_key;
        //$this->consumer_secret = $consumer_secret;
    }

    /**
	 * @since    1.0.0
	 */
    /*
    private function make_api_request($endpoint, $method = 'GET') {
        $url = $this->api_url . $endpoint;
        $args = array(
            'method'    => $method,
            'headers'   => array(
                'Authorization' => 'Basic ' . base64_encode($this->consumer_key . ':' . $this->consumer_secret)
            )
        );

        $response = wp_remote_request($url, $args);

        if (is_wp_error($response)) {
            return false; // Handle error appropriately
        }

        return json_decode(wp_remote_retrieve_body($response), true);
    }
    */

    /**
	 * Makes a request to Google Sheets API.
	 *
	 * @since    1.0.0
	 */
    private function make_api_request($license_key) {
        $url = add_query_arg('license_key', urlencode($license_key), $this->api_url);
        
        $response = wp_remote_get($url);

        if (is_wp_error($response)) {
            return false; // Handle error appropriately
        }

        return json_decode(wp_remote_retrieve_body($response), true);
    }

    /**
	 * @since    1.0.0
	 */
    /*
    public function activate_license($license_key) {
        $endpoint = '/activate/' . urlencode($license_key);
        return $this->make_api_request($endpoint);
    }
    */

    /**
	 * Activates the license key. (No Google Sheets equivalent; optional)
	 *
	 * @since    1.0.0
	 */
    public function activate_license($license_key) {
        // No activate endpoint in Google Sheets; kept for compatibility
        return $this->validate_license($license_key);
    }

    /**
	 * @since    1.0.0
	 */
    /*
    public function deactivate_license($license_key) {
        $endpoint = '/deactivate/' . urlencode($license_key);
        return $this->make_api_request($endpoint);
    }
    */

    /**
	 * Deactivates the license key. (No Google Sheets equivalent; optional)
	 *
	 * @since    1.0.0
	 */
    public function deactivate_license($license_key) {
        // No deactivate endpoint in Google Sheets; kept for compatibility
        return $this->validate_license($license_key);
    }
    
    /**
	 * @since    1.0.0
	 */
    /*
    public function validate_license($license_key) {
        $endpoint = '/validate/' . urlencode($license_key);
        return $this->make_api_request($endpoint);
    }
    */

    /**
	 * Validates the license key.
	 *
	 * @since    1.0.0
	 */
    public function validate_license($license_key) {
        $response = $this->make_api_request($license_key);

        if (isset($response['status']) && $response['status'] === 'valid') {
            return true; // License is valid
        } else {
            return false; // License is invalid
        }
    }
}