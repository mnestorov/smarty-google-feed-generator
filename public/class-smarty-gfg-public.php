<?php

/**
 * The public functionality of the plugin.
 * 
 * Defines the plugin name, version, and two hooks for how to enqueue 
 * the public-facing stylesheet (CSS) and JavaScript code.
 * 
 * @link       https://smartystudio.net
 * @since      1.0.0
 *
 * @package    smarty_google_products_feed_Generator
 * @subpackage smarty_google_products_feed_Generator/admin/partials
 * @author     Smarty Studio | Martin Nestorov
 */
class Smarty_Gfg_Public {

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
	 * Instance of Smarty_Gfg_Google_Products_Feed_Public.
	 * 
	 * @since    1.0.0
	 * @access   private
	 */
	private $google_products_feed;

    /**
	 * Instance of Smarty_Gfg_Google_Reviews_Feed_Public.
	 * 
	 * @since    1.0.0
	 * @access   private
	 */
	private $google_reviews_feed;

    /**
	 * Instance of Smarty_Gfg_Bing_Products_Feed_Public.
	 * 
	 * @since    1.0.0
	 * @access   private
	 */
	private $bing_products_feed;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param    string    $plugin_name     The name of the plugin.
	 * @param    string    $version         The version of this plugin.
	 */
	public function __construct($plugin_name, $version) {
		$this->plugin_name = $plugin_name;
		$this->version     = $version;

        // Include and instantiate the Google Products Feed public class
		$this->google_products_feed = new Smarty_Gfg_Google_Products_Feed_Public();

        // Include and instantiate the Google Reviews Feed public class
		$this->google_reviews_feed = new Smarty_Gfg_Google_Reviews_Feed_Public();

        // Include and instantiate the Bing Products Feed public class
		$this->bing_products_feed = new Smarty_Gfg_Bing_Products_Feed_Public();
	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {
		/**
		 * This function enqueues custom CSS for the WooCommerce checkout page.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Smarty_Gfg_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Smarty_Gfg_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */
         
        wp_enqueue_style($this->plugin_name, plugin_dir_url(__FILE__) . 'css/smarty-gfg-public.css', array(), $this->version, 'all');
    }

	/**
	 * Register the JavaScript for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {
		/**
		 * This function enqueues custom JavaScript for the WooCommerce checkout page.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Smarty_Gfg_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Smarty_Gfg_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_script($this->plugin_name, plugin_dir_url(__FILE__) . 'js/smarty-gfg-public.js', array('jquery'), $this->version, true);
	}

	/**
	 * Handle requests to custom endpoints.
     * 
     * @since    1.0.0
	 */
	public function handle_template_redirect() {
		if (get_query_var('smarty_google_products_feed')) {
			$this->google_products_feed->generate_google_products_feed();
			exit;
		}
	
		if (get_query_var('smarty_google_reviews_feed')) {
			$this->google_reviews_feed->generate_google_reviews_feed();
			exit;
		}
	
		if (get_query_var('smarty_google_csv_export')) {
			$this->google_products_feed->generate_google_csv_export();
			exit;
		}

        if (get_query_var('smarty_bing_products_feed')) {
            $this->bing_products_feed->generate_bing_products_feed();
            exit;
        }

        if (get_query_var('smarty_bing_txt_feed')) {
            $this->bing_products_feed->generate_bing_products_feed('txt');
            exit;
        }
	}

	/**
	 * Add rewrite rules for custom endpoints.
     * 
     * @since    1.0.0
	 */
	public static function add_rewrite_rules() {
		add_rewrite_rule('^smarty-google-products-feed/?', 'index.php?smarty_google_products_feed=1', 'top'); // url: ?smarty-google-products-feed
		add_rewrite_rule('^smarty-google-reviews-feed/?', 'index.php?smarty_google_reviews_feed=1', 'top');   // url: ?smarty-google-reviews-feed
		add_rewrite_rule('^smarty-google-csv-export/?', 'index.php?smarty_google_csv_export=1', 'top');       // url: ?smarty-google-csv-export
        add_rewrite_rule('^smarty-bing-products-feed/?', 'index.php?smarty_bing_products_feed=1', 'top');     // url: ?smarty-bing-products-feed
        add_rewrite_rule('^smarty-bing-txt-feed/?', 'index.php?smarty_bing_txt_feed=1', 'top');               // url: ?smarty-bing-txt-feed
    }
	
	/**
     * Register query vars for custom endpoints.
     * 
     * @since    1.0.0
     * @param    array    $vars    An array of query variables.
     * @return   array    $vars    The modified array of query variables.
     */
	public function add_query_vars($vars) {
		$vars[] = 'smarty_google_products_feed';
		$vars[] = 'smarty_google_reviews_feed';
		$vars[] = 'smarty_google_csv_export';
        $vars[] = 'smarty_bing_products_feed';
        $vars[] = 'smarty_bing_txt_feed';
		return $vars;
	}

    /**
     * Handle AJAX request to generate feed.
     * 
     * @since    1.0.0
     */
	public function handle_ajax_generate_feed() {
		check_ajax_referer('smarty_feed_generator_nonce', 'nonce');
	
		if (!current_user_can('manage_options')) {
			wp_send_json_error('You do not have sufficient permissions to access this page.');
		}
	
		$action = sanitize_text_field($_POST['feed_action']);
		switch ($action) {
			case 'generate_google_products_feed':
				$this->google_products_feed->generate_google_products_feed();
				wp_send_json_success('Google Products feed generated successfully.');
				break;
			case 'generate_google_reviews_feed':
				$this->google_reviews_feed->generate_google_reviews_feed();
				wp_send_json_success('Google Reviews feed generated successfully.');
				break;
			case 'generate_google_csv_export':
				$this->google_products_feed->generate_google_csv_export();
				wp_send_json_success('CSV export generated successfully.');
				break;
            case 'generate_bing_products_feed':
                $this->bing_products_feed->generate_bing_products_feed();
                wp_send_json_success('Bing Products feed generated successfully.');
                break;
			default:
				wp_send_json_error('Invalid action.');
				break;
		}
	}

	/**
     * Hook into product changes.
     * 
     * @since    1.0.0
     * @param int $post_id The ID of the post being changed.
     */
    public function handle_product_change($post_id) {
        if (get_post_type($post_id) == 'product') {
            $this->google_products_feed->regenerate_google_products_feed(); // Regenerate the Google Products feed
            $this->bing_products_feed->regenerate_bing_products_feed();     // Regenerate the Bing Products feed
	    }
    }

	/**
     * Get custom label value based on the logic defined in the settings.
     * 
     * @since    1.0.0
     * @param WC_Product $product The WooCommerce product instance.
     * @param string $label The custom label key (e.g., 'custom_label_0').
     * @return string The custom label value.
     */
    public static function get_custom_label($product, $label) {
        $logic_option = 'smarty_' . strtolower(str_replace(' ', '_', $label)) . '_logic';
        $days_option = 'smarty_' . strtolower(str_replace(' ', '_', $label)) . '_days';
        $value_option = 'smarty_' . strtolower(str_replace(' ', '_', $label)) . '_value';
        $categories_option = 'smarty_' . strtolower(str_replace(' ', '_', $label)) . '_categories';

        $custom_label_logic = get_option($logic_option, '');
        $days = get_option($days_option, '');
        $value = get_option($value_option, '');
        $categories = get_option($categories_option, []);

        if (!is_array($categories)) {
            $categories = explode(',', $categories);
        }

        switch ($custom_label_logic) {
            case 'older_than_days':
            case 'not_older_than_days':
                $date_created = $product->get_date_created();
                $now = new DateTime();
                $interval = $now->diff($date_created)->days;

                if ($custom_label_logic === 'older_than_days' && $interval > $days) {
                    return $value;
                } elseif ($custom_label_logic === 'not_older_than_days' && $interval <= $days) {
                    return $value;
                }
                break;

            case 'most_ordered_days':
                $args = [
                    'post_type'      => 'shop_order',
                    'post_status'    => ['wc-completed', 'wc-processing'],
                    'posts_per_page' => -1,
                    'date_query'     => [
                        'after' => date('Y-m-d', strtotime("-$days days")),
                    ],
                ];

                $orders = get_posts($args);

                foreach ($orders as $order_post) {
                    $order = wc_get_order($order_post->ID);
                    foreach ($order->get_items() as $item) {
                        if ($item->get_product_id() == $product->get_id() || $item->get_variation_id() == $product->get_id()) {
                            return $value;
                        }
                    }
                }
                break;

            case 'high_rating_value':
                $average_rating = $product->get_average_rating();

                if ($average_rating >= 4) {
                    return $value;
                }
                break;

            case 'category':
                $product_categories = wp_get_post_terms($product->get_id(), 'product_cat', ['fields' => 'ids']);
                $matched_values = [];

                foreach ($categories as $index => $category) {
                    if (in_array($category, $product_categories)) {
                        $matched_values[] = $value;
                    }
                }

                $matched_values = array_unique($matched_values);
                return implode(', ', $matched_values);

            case 'has_sale_price':
                if ($product->is_type('simple')) {
                    if ($product->is_on_sale() && !empty($product->get_sale_price())) {
                        return $value;
                    }
                }

                if ($product->is_type('variable')) {
                    $variations = $product->get_children();
                    if (!empty($variations)) {
                        $first_variation_id = $variations[0];
                        $variation = wc_get_product($first_variation_id);

                        if ($variation->is_on_sale() && !empty($variation->get_sale_price())) {
                            return $value;
                        }
                    }
                }
                break;

            default:
                return '';
        }

        return '';
    }

    /**
     * Evaluates the criteria for custom labels.
     * 
     * @since    1.0.0
     * @param WC_Product $product The WooCommerce product instance.
     * @param string $criteria The JSON encoded criteria.
     * @return string The evaluated label.
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
     * Get the flat rate shipping cost from WooCommerce settings.
     * 
     * @since 1.0.0
     * @return string|false The shipping cost or false if not found.
     */
    public static function get_shipping_cost() {
        $shipping_zones = WC_Shipping_Zones::get_zones();
    
        foreach ($shipping_zones as $zone) {
            $shipping_methods = $zone['shipping_methods'];
            
            foreach ($shipping_methods as $method) {
                if ($method->id === 'flat_rate') {
                    if (isset($method->cost) && !empty($method->cost)) {
                        return $method->cost;
                    } else {
                        //error_log('Flat rate shipping cost is not set or empty');
                    }
                }
            }
        }
    
        // Check for the 'Rest of the World' zone
        $default_zone = new WC_Shipping_Zone(0);
        $shipping_methods = $default_zone->get_shipping_methods();
    
        foreach ($shipping_methods as $method) {
            if ($method->id === 'flat_rate') {
                if (isset($method->cost) && !empty($method->cost)) {
                    return $method->cost;
                } else {
                    //error_log('Flat rate shipping cost is not set or empty in the default zone');
                }
            }
        }
    
        //error_log('No flat rate shipping method found'); // Debug
        return false;
    }

    /**
     * Adds custom intervals to the WordPress cron schedules.
     *
     * @since 1.0.0
     * @param array $schedules The existing cron schedules.
     * @return array The modified cron schedules.
     */
    function gfg_custom_cron_intervals($schedules) {
        $schedules['hourly'] = array(
            'interval' => 3600,
            'display' => __('Hourly')
        );
        $schedules['twicedaily'] = array(
            'interval' => 12 * 3600,
            'display' => __('Twice Daily')
        );
        $schedules['daily'] = array(
            'interval' => 24 * 3600,
            'display' => __('Daily')
        );
        return $schedules;
    }
}