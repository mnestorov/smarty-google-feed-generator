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
 * @package    Smarty_Google_Feed_Generator
 * @subpackage Smarty_Google_Feed_Generator/admin/partials
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
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param    string    $plugin_name     The name of the plugin.
	 * @param    string    $version         The version of this plugin.
	 */
	public function __construct($plugin_name, $version) {
		$this->plugin_name = $plugin_name;
		$this->version     = $version;
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
		if (get_query_var('smarty_google_feed')) {
			$this->generate_google_feed();
			exit;
		}
	
		if (get_query_var('smarty_google_reviews_feed')) {
			$this->generate_google_reviews_feed();
			exit;
		}
	
		if (get_query_var('smarty_csv_export')) {
			$this->generate_csv_export();
			exit;
		}
	}

	/**
	 * Add rewrite rules for custom endpoints.
     * 
     * @since    1.0.0
	 */
	public static function add_rewrite_rules() {
		add_rewrite_rule('^smarty-google-feed/?', 'index.php?smarty_google_feed=1', 'top');                 // url: ?smarty-google-feed
		add_rewrite_rule('^smarty-google-reviews-feed/?', 'index.php?smarty_google_reviews_feed=1', 'top'); // url: ?smarty-google-reviews-feed
		add_rewrite_rule('^smarty-csv-export/?', 'index.php?smarty_csv_export=1', 'top');                   // url: ?smarty-csv-export
	}
	
	/**
     * Register query vars for custom endpoints.
     * 
     * @since    1.0.0
     * @param    array    $vars    An array of query variables.
     * @return   array    $vars    The modified array of query variables.
     */
	public function add_query_vars($vars) {
		$vars[] = 'smarty_google_feed';
		$vars[] = 'smarty_google_reviews_feed';
		$vars[] = 'smarty_csv_export';
		return $vars;
	}

	/**
     * Generates the custom Google product feed.
     * 
     * This function is designed to generate a custom Google product feed for WooCommerce products. 
     * It uses WordPress and WooCommerce functions to build an XML feed that conforms to Google's specifications for product feeds.
     * 
     * @since    1.0.0
     */
    public function generate_google_feed() {
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

                        // Convert the first WebP image to PNG if needed
                        Smarty_Gfg_Admin::convert_first_webp_image_to_png($product);

                        // Add product details as child nodes
                        $this->add_google_product_details($dom, $item, $product, $variation);
                    }
                } else {
                    // Process simple products similarly
                    $item = $dom->createElement('item');
                    $feed->appendChild($item);

                    // Convert the first WebP image to PNG if needed
                    Smarty_Gfg_Admin::convert_first_webp_image_to_png($product);

                    // Add product details as child nodes
                    $this->add_google_product_details($dom, $item, $product);
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
		$item->appendChild($dom->createElementNS($gNamespace, 'g:image_link', wp_get_attachment_url($image_id)));

		// Add additional images
		$gallery_ids = $product->get_gallery_image_ids();
		foreach ($gallery_ids as $gallery_id) {
			$item->appendChild($dom->createElementNS($gNamespace, 'g:additional_image_link', wp_get_attachment_url($gallery_id)));
		}

		// Add price details
		$item->appendChild($dom->createElementNS($gNamespace, 'g:price', htmlspecialchars($price . ' ' . get_woocommerce_currency())));
		if ($is_on_sale) {
			$item->appendChild($dom->createElementNS($gNamespace, 'g:sale_price', htmlspecialchars($sale_price . ' ' . get_woocommerce_currency())));
		}

		// Add product categories
		$google_product_category = $this->get_cleaned_google_product_category();
		if ($google_product_category) {
			$item->appendChild($dom->createElementNS($gNamespace, 'g:google_product_category', htmlspecialchars($google_product_category)));
		}

		// Add product categories
		$categories = wp_get_post_terms($product->get_id(), 'product_cat');
		if (!empty($categories) && !is_wp_error($categories)) {
			$category_names = array_map(function($term) { return $term->name; }, $categories);
			$item->appendChild($dom->createElementNS($gNamespace, 'g:product_type', htmlspecialchars(join(' > ', $category_names))));
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

		// Use the condition value from the settings
        $condition = get_option('smarty_condition', 'new');
		$item->appendChild($dom->createElementNS($gNamespace, 'g:condition', htmlspecialchars($condition)));

		// Add brand
		$brand = get_bloginfo('name'); // Use the site name as the brand
		$item->appendChild($dom->createElementNS($gNamespace, 'g:brand', htmlspecialchars($brand)));

		// Custom Labels
		$item->appendChild($dom->createElementNS($gNamespace, 'g:custom_label_0', $this->get_custom_label_0($product)));
		$item->appendChild($dom->createElementNS($gNamespace, 'g:custom_label_1', $this->get_custom_label_1($product)));
		$item->appendChild($dom->createElementNS($gNamespace, 'g:custom_label_2', $this->get_custom_label_2($product)));
		$item->appendChild($dom->createElementNS($gNamespace, 'g:custom_label_3', $this->get_custom_label_3($product)));
		$item->appendChild($dom->createElementNS($gNamespace, 'g:custom_label_4', $this->get_custom_label_4($product)));

        // Add Excluded Destinations
        $excluded_destinations = get_option('smarty_excluded_destination', []);
        foreach ($excluded_destinations as $excluded_destination) {
            $item->appendChild($dom->createElementNS($gNamespace, 'g:excluded_destination', htmlspecialchars($excluded_destination)));
        }

        // Add Included Destinations
        $included_destinations = get_option('smarty_included_destination', []);
        foreach ($included_destinations as $included_destination) {
            $item->appendChild($dom->createElementNS($gNamespace, 'g:included_destination', htmlspecialchars($included_destination)));
        }

        // Add Excluded Countries for Shopping Ads
        $excluded_countries = get_option('smarty_excluded_countries', '');
        if (!empty($excluded_countries)) {
            $countries = explode(',', $excluded_countries);
            foreach ($countries as $country) {
                $item->appendChild($dom->createElementNS($gNamespace, 'g:excluded_countries', htmlspecialchars(trim($country))));
            }
        }
	}

	/**
     * Generates the custom Google product review feed.
     * 
     * This function retrieves all the products and their reviews from a WooCommerce store and constructs
     * an XML feed that adheres to Google's specifications for review feeds.
     * 
     * @since    1.0.0
     */
    public function generate_google_reviews_feed() {
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
                    $entry->appendChild($dom->createElementNS($gNamespace, 'g:id', htmlspecialchars(smarty_get_the_product_sku($product->ID))));
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
    public function generate_csv_export() {
        // Set headers to force download and define the file name
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="woocommerce-products.csv"');
    
        // Open PHP output stream as a writable file
        $handle = fopen('php://output', 'w');
        
        // Check if the file handle is valid
        if ($handle === false) {
            wp_die('Failed to open output stream for CSV export'); // Kill the script and display message if handle is invalid
        }
    
        // Fetch user-defined column mappings
        $mappings = get_option('smarty_gfg_settings_mapping', array());

        // Define the columns and map headers based on user settings
        $csv_columns = array(
            'ID',                       // WooCommerce product ID
            'ID2',                      // SKU, often used as an alternate identifier
			'Link', 					// URL to the product page
            'Mobile Link', 				// URL to the product page, mobile-specific if applicable
			'Image Link', 	            // Main image URL
            'Additional Image Link',
			'Title', 					// Title of the product
			'Description', 				// Description of the product
			'Google Product Category',  // Google's product category if needed for feeds
			'Product Type', 			// Categories the product belongs to
			'Price',                    // Regular price of the product
			'Sale Price',               // Sale price if the product is on discount
			'Bundle', 					// Indicates if the product is a bundle
			'Brand',                    // Brand of the product
			'GTIN',
			'MPN',                      // Manufacturer Part Number
			'Availability',             // Stock status
			//'Availability Date',
			'Condition',                // Condition of the product, usually "new" for e-commerce
			'Multipack',
            'Color',
			'Gender',
			'Material',
			'Size',
			'Size Type',
			'Size System',
			//'Item Group ID',
			//'Product Detail',
            'Custom Label 0',
            'Custom Label 1',
            'Custom Label 2',
            'Custom Label 3',
            'Custom Label 4',
			'Excluded Destination',
			'Included Destination',
			'Excluded Countries for Shopping Ads',
			'Shipping',
            //'Shipping Label',
        );

        $headers = array();
        foreach ($csv_columns as $column) {
            $headers[] = isset($mappings[$column]) && !empty($mappings[$column]) ? $mappings[$column] : $column;
        }
    
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

            // Use the condition value from the settings
            $condition = get_option('smarty_condition', 'new');
            
            // Convert the first WebP image to PNG if needed
            Smarty_Gfg_Admin::convert_first_webp_image_to_png($product);

            // Prepare product data for the CSV
            $id = $product->get_id();
            $regular_price = $product->get_regular_price();
            $sale_price = $product->get_sale_price() ?: ''; // Fallback to empty string if no sale price
            $categories = wp_get_post_terms($id, 'product_cat', array('fields' => 'names'));
            $categories = !empty($categories) ? implode(', ', $categories) : '';
            $image_id = $product->get_image_id();
            $image_link = $image_id ? wp_get_attachment_url($image_id) : '';

            // Get the first additional image link if available
            $gallery_ids = $product->get_gallery_image_ids();
            $additional_image_link = '';
            if (!empty($gallery_ids)) {
                $additional_image_link = wp_get_attachment_url(reset($gallery_ids));
            }

            // Custom meta fields for title and description if set
            $meta_title = get_post_meta($id, get_option('smarty_meta_title_field', 'meta-title'), true);
            $meta_description = get_post_meta($id, get_option('smarty_meta_description_field', 'meta-description'), true);
            $name = !empty($meta_title) ? htmlspecialchars(strip_tags($meta_title)) : htmlspecialchars(strip_tags($product->get_name()));
            $description = !empty($meta_description) ? htmlspecialchars(strip_tags($meta_description)) : htmlspecialchars(strip_tags($product->get_short_description()));
            $description = preg_replace('/\s+/', ' ', $description); // Normalize whitespace in descriptions
            $availability = $product->is_in_stock() ? 'in stock' : 'out of stock';
            
            // Get Google category as ID or name
            $google_product_category = $this->get_cleaned_google_product_category(); // Get Google category from plugin settings
            //error_log('Google Product Category: ' . $google_product_category); // Debugging line
            if ($google_category_as_id) {
                $google_product_category = explode('-', $google_product_category)[0]; // Get only the ID part
                //error_log('Google Product Category ID: ' . $google_product_category); // Debugging line
            }

            $brand = get_bloginfo('name');

            // Custom Labels
            $custom_label_0 = $this->get_custom_label_0($product);
            $custom_label_1 = $this->get_custom_label_1($product);
            $custom_label_2 = $this->get_custom_label_2($product);
            $custom_label_3 = $this->get_custom_label_3($product);
            $custom_label_4 = $this->get_custom_label_4($product);

            // Check if the product has the "bundle" tag
            $is_bundle = 'no';
            $product_tags = wp_get_post_terms($product->get_id(), 'product_tag', array('fields' => 'slugs'));
            if (in_array('bundle', $product_tags)) {
                $is_bundle = 'yes';
            }

            $excluded_destinations = get_option('smarty_excluded_destination', []);
            $included_destinations = get_option('smarty_included_destination', []);
            $excluded_countries = get_option('smarty_excluded_countries', '');
            
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
                        'ID'                                    => $id,
                        'ID2'                                   => $sku,
                        'Link'                                  => $product_link,
                        'Mobile Link'                           => $product_link,
                        'Image Link'                            => $variation_image,
                        'Additional Image Link'                 => $additional_image_link,
                        'Title'                                 => $name,
                        'Description'                           => $description,
                        'Google Product Category'               => $google_product_category,
                        'Product Type'                          => $categories,
                        'Price'                                 => $variation_price,
                        'Sale Price'                            => $variation_sale_price,
                        'Bundle'                                => $is_bundle,
                        'Brand'                                 => $brand,
                        'GTIN'                                  => '',
                        'MPN'                                   => $sku,
                        'Availability'                          => $availability,
                        //'Availability Date'                   => '', 
                        'Condition'                             => $condition,
                        'Multipack'                             => '',
                        'Color'                                 => '',
                        'Gender'                                => '',
                        'Material'                              => '',
                        'Size'                                  => '',
                        'Size Type'                             => '',
                        'Size System'                           => '',
                        //'Item Group ID'                       => '',
                        //'Product Detail'                      => '',
                        'Custom Label 0'                        => $custom_label_0, 
                        'Custom Label 1'                        => $custom_label_1, 
                        'Custom Label 2'                        => $custom_label_2,
                        'Custom Label 3'                        => $custom_label_3, 
                        'Custom Label 4'                        => $custom_label_4,
                        'Excluded Destination'                  => implode(', ', $excluded_destinations),
                        'Included Destination'                  => implode(', ', $included_destinations),
                        'Excluded Countries for Shopping Ads'   => $excluded_countries,
                        //'Shipping'                            => '',
                        //'Shipping Label'                      => '',
                    );
                }
            } else {
                // Prepare row for a simple product
                $sku = $product->get_sku();
                $row = array(
                    'ID'                                    => $id,
                    'ID2'                                   => $sku,
                    'Link'                                  => $product_link,
                    'Mobile Link'                           => $product_link,
                    'Image Link'                            => $image_link,
                    'Additional Image Link'                 => $additional_image_link,
                    'Title'                                 => $name,
                    'Description'                           => $description,
                    'Product Type'                          => $categories,
                    'Google Product Category'               => $google_product_category,
                    'Price'                                 => $regular_price,
                    'Sale Price'                            => $sale_price,
                    'Bundle'                                => $is_bundle,
                    'Brand'                                 => $brand,
                    'GTIN'                                  => '',
                    'MPN'                                   => $sku,
                    'Availability'                          => $availability,
                    //'Availability Date'                   => '',
                    'Condition'                             => $condition,
                    'Multipack'                             => '',
                    'Color'                                 => '',
                    'Gender'                                => '',
                    'Material'                              => '',
                    'Size'                                  => '',
                    'Size Type'                             => '',
                    'Size System'                           => '',
                    //'Item Group ID'                       => '',
                    //'Product Detail'                      => '',
                    'Custom Label 0'                        => $custom_label_0, 
                    'Custom Label 1'                        => $custom_label_1, 
                    'Custom Label 2'                        => $custom_label_2,
                    'Custom Label 3'                        => $custom_label_3, 
                    'Custom Label 4'                        => $custom_label_4,
                    'Excluded Destination'                  => implode(', ', $excluded_destinations),
                    'Included Destination'                  => implode(', ', $included_destinations),
                    'Excluded Countries for Shopping Ads'   => $excluded_countries,
                    'Shipping'                              => '',
                    //'Shipping Label'                      => '',
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
			case 'generate_product_feed':
				$this->generate_google_feed();
				wp_send_json_success('Product feed generated successfully.');
				break;
			case 'generate_reviews_feed':
				$this->generate_google_reviews_feed();
				wp_send_json_success('Reviews feed generated successfully.');
				break;
			case 'generate_csv_export':
				$this->generate_csv_export();
				wp_send_json_success('CSV export generated successfully.');
				break;
			default:
				wp_send_json_error('Invalid action.');
				break;
		}
	}

	/**
     * Invalidate cache or regenerate feed when a product is created, updated, or deleted.
     * 
     * @since    1.0.0
     * @param int $product_id The ID of the product that has been created, updated, or deleted.
     */
    public static function invalidate_feed_cache($product_id) {
        // Check if the post is a 'product'
        if (get_post_type($product_id) === 'product') {
            // Invalidate cache
			delete_transient('smarty_google_feed');
			// Regenerate the feed
			$this->regenerate_feed();
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
            $this->regenerate_feed();
        }
    }

	/**
     * Invalidate cache or regenerate review feed when reviews are added, updated, or deleted.
     * 
     * @since    1.0.0
     * @param int $comment_id The ID of the comment being updated.
     * @param string $comment_approved The approval status of the comment.
     */
    public function invalidate_review_feed_cache($comment_id, $comment_approved = '') {
        $comment = get_comment($comment_id);
        $post_id = $comment->comment_post_ID;
        
        // Check if the comment is for a 'product' and approved
        if (get_post_type($post_id) === 'product' && ($comment_approved == 1 || $comment_approved == 'approve')) {
            // Invalidate cache
            delete_transient('smarty_google_reviews_feed');
            // Optionally, regenerate the feed file
            // smarty_regenerate_google_reviews_feed();
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

                    $item->addChild('g:image_link', wp_get_attachment_url($product->get_image_id()), $gNamespace);

                    // Variation specific image, if different from the main product image
                    $image_id = $variation->get_image_id() ? $variation->get_image_id() : $product->get_image_id();
                    $variationImageURL = wp_get_attachment_url($variation->get_image_id());
                    $mainImageURL = wp_get_attachment_url($product->get_image_id());
                    if ($variationImageURL !== $mainImageURL) {
                        $item->addChild('g:image_link', wp_get_attachment_url($image_id), $gNamespace);
                    }

                    // Additional images: Loop through gallery if available
                    $gallery_ids = $product->get_gallery_image_ids();
                    foreach ($gallery_ids as $gallery_id) {
                        $item->addChild('g:additional_image_link', wp_get_attachment_url($gallery_id), $gNamespace);
                    }

                    // Pricing: Regular and sale prices
                    $item->addChild('g:price', htmlspecialchars($variation->get_regular_price() . ' ' . get_woocommerce_currency()), $gNamespace);
                    if ($variation->is_on_sale()) {
                        $item->addChild('g:sale_price', htmlspecialchars($variation->get_sale_price() . ' ' . get_woocommerce_currency()), $gNamespace);
                    }

                    // Categories: Compile a list from the product's categories
                    $categories = wp_get_post_terms($product->get_id(), 'product_cat');
                    if (!empty($categories) && !is_wp_error($categories)) {
                        $category_names = array_map(function($term) { return $term->name; }, $categories);
                        $item->addChild('g:product_type', htmlspecialchars(join(' > ', $category_names)), $gNamespace);
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
                $item->addChild('g:image_link', wp_get_attachment_url($product->get_image_id()), $gNamespace);
                $gallery_ids = $product->get_gallery_image_ids();
                foreach ($gallery_ids as $gallery_id) {
                    $item->addChild('g:additional_image_link', wp_get_attachment_url($gallery_id), $gNamespace);
                }
    
                // Pricing information
                $item->addChild('g:price', htmlspecialchars($product->get_price() . ' ' . get_woocommerce_currency()), $gNamespace);
                if ($product->is_on_sale() && !empty($product->get_sale_price())) {
                    $item->addChild('g:sale_price', htmlspecialchars($product->get_sale_price() . ' ' . get_woocommerce_currency()), $gNamespace);
                }
    
                // Category information
                $categories = wp_get_post_terms($product->get_id(), 'product_cat');
                if (!empty($categories) && !is_wp_error($categories)) {
                    $category_names = array_map(function($term) { return $term->name; }, $categories);
                    $item->addChild('g:product_type', htmlspecialchars(join(' > ', $category_names)), $gNamespace);
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
     * @param int $post_id The ID of the post being changed.
     */
    public function handle_product_change($post_id) {
        if (get_post_type($post_id) == 'product') {
            $this->regenerate_feed(); // Regenerate the feed
	    }
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
     * Custom Label 0: Older Than X Days & Not Older Than Y Days.
     * 
     * @since    1.0.0
     * @param WC_Product $product The WooCommerce product instance.
     * @return string The custom label 0 value.
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
     * Custom Label 1: Most Ordered in Last Z Days.
     * 
     * @since    1.0.0
     * @param WC_Product $product The WooCommerce product instance.
     * @return string The custom label 1 value.
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
     * Custom Label 2: High Rating.
     * 
     * @since    1.0.0
     * @param WC_Product $product The WooCommerce product instance.
     * @return string The custom label 2 value.
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
     * Custom Label 3: In Selected Category.
     * 
     * @since    1.0.0
     * @param WC_Product $product The WooCommerce product instance.
     * @return string The custom label 3 value.
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
     * Custom Label 4: Has Sale Price.
     * 
     * @since    1.0.0
     * @param WC_Product $product The WooCommerce product instance.
     * @return string The custom label 4 value.
     */
    public function get_custom_label_4($product) {
        $excluded_categories = get_option('smarty_excluded_categories', array()); // Get excluded categories from settings
        $product_categories = wp_get_post_terms($product->get_id(), 'product_cat', array('fields' => 'ids'));

        // Check if product is in excluded categories
        $is_excluded = !empty(array_intersect($excluded_categories, $product_categories));

        // Log debug information
        //error_log('Product ID: ' . $product->get_id());
        //error_log('Product is on sale: ' . ($product->is_on_sale() ? 'yes' : 'no'));
        //error_log('Product sale price: ' . $product->get_sale_price());
        //error_log('Excluded categories: ' . print_r($excluded_categories, true));
        //error_log('Product categories: ' . print_r($product_categories, true));
        //error_log('Is product excluded: ' . ($is_excluded ? 'yes' : 'no'));

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
                //error_log('First Variation ID: ' . $variation->get_id() . ' is on sale: ' . ($variation->is_on_sale() ? 'yes' : 'no') . ' Sale price: ' . $variation->get_sale_price());
                if ($variation->is_on_sale() && !empty($variation->get_sale_price())) {
                    return get_option('smarty_custom_label_4_sale_price_value', 'on_sale');
                }
            }
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
}