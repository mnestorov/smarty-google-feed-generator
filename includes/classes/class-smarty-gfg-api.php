<?php

/**
 * The API functionality of the plugin.
 *
 * @link       https://smartystudio.net
 * @since      1.0.0
 *
 * @package    Smarty_Google_Feed_Generator
 * @subpackage Smarty_Google_Feed_Generator/admin/partials
 * @author     Smarty Studio | Martin Nestorov
 */
class Smarty_Dcf_API {
    private $api_url = 'https://smartystudio.net/wp-json/lmfwc/v2/licenses';
    private $consumer_key; // Set your Consumer Key
    private $consumer_secret; // Set your Consumer Secret

    public function __construct($consumer_key, $consumer_secret) {
        $this->consumer_key = $consumer_key;
        $this->consumer_secret = $consumer_secret;
    }

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

    public function activate_license($license_key) {
        $endpoint = '/activate/' . urlencode($license_key);
        return $this->make_api_request($endpoint);
    }

    public function deactivate_license($license_key) {
        $endpoint = '/deactivate/' . urlencode($license_key);
        return $this->make_api_request($endpoint);
    }

    public function validate_license($license_key) {
        $endpoint = '/validate/' . urlencode($license_key);
        return $this->make_api_request($endpoint);
    }
}