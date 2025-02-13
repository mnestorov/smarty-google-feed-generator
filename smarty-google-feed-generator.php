<?php
/**
 * Plugin Name:             SM - Google Feed Generator for WooCommerce
 * Plugin URI:              https://github.com/mnestorov/smarty-google-feed-generator
 * Description:             Generates google product and product review feeds for Google Merchant Center.
 * Version:                 1.0.6
 * Author:                  Martin Nestorov
 * Author URI:              https://github.com/mnestorov
 * License:                 GPL-2.0+
 * License URI:             http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:             smarty-google-feed-generator
 * WC requires at least:    3.5.0
 * WC tested up to:         9.6.0
 * Requires Plugins:        woocommerce
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
	die;
}

/**
 * HPOS Compatibility Declaration.
 *
 * This ensures that the plugin explicitly declares compatibility with 
 * WooCommerce's High-Performance Order Storage (HPOS).
 * 
 * HPOS replaces the traditional `wp_posts` and `wp_postmeta` storage system 
 * for orders with a dedicated database table structure, improving scalability 
 * and performance.
 * 
 * More details:
 * - WooCommerce HPOS Documentation: 
 *   https://developer.woocommerce.com/2022/09/12/high-performance-order-storage-in-woocommerce/
 * - Declaring Plugin Compatibility: 
 *   https://github.com/woocommerce/woocommerce/wiki/High-Performance-Order-Storage-Upgrade-Recipe-Book#how-to-declare-compatibility
 */
add_action('before_woocommerce_init', function() {
    if (class_exists('\Automattic\WooCommerce\Utilities\FeaturesUtil')) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
    }
});

if (!function_exists('smarty_gfg_enqueue_admin_scripts')) {
    /**
     * Enqueues admin scripts and styles for the settings page.
     *
     * This function enqueues the necessary JavaScript and CSS files for the
     * admin settings pages of the Google Feed Generator plugin.
     * It also localizes the script to pass AJAX-related data to the JavaScript file.
     *
     * @param string $hook_suffix The current admin page hook suffix.
     */
    function smarty_gfg_enqueue_admin_scripts($hook_suffix) {
        // Only add to the admin page of the plugin
        if ('woocommerce_page_smarty-gfg-settings' !== $hook_suffix) {
            return;
        }
        
        wp_enqueue_script('select2', 'https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/js/select2.min.js', array('jquery'), '4.0.13', true);
        wp_enqueue_style('select2', 'https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css', array(), '4.0.13');
        wp_enqueue_script('smarty-gfg-admin-js', plugin_dir_url(__FILE__) . 'js/smarty-gfg-admin.js', array('jquery', 'select2'), '1.0.0', true);
        wp_enqueue_style('smarty-gfg-admin-css', plugin_dir_url(__FILE__) . 'css/smarty-gfg-admin.css', array(), '1.0.0');
        wp_localize_script(
            'smarty-gfg-admin-js',
            'smartyGoogleFeedGenerator',
            array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'siteUrl' => site_url(),
                'nonce'   => wp_create_nonce('smarty_feed_generator_nonce'),
            )
        );
    }
    add_action('admin_enqueue_scripts', 'smarty_gfg_enqueue_admin_scripts');
}

if (!function_exists('smarty_gfg_feed_generator_add_rewrite_rules')) {
    /**
     * Add rewrite rules for custom endpoints.
     */
    function smarty_gfg_feed_generator_add_rewrite_rules() {
        add_rewrite_rule('^smarty-google-feed/?', 'index.php?smarty_google_feed=1', 'top');                 // url: ?smarty-google-feed
        add_rewrite_rule('^smarty-google-reviews-feed/?', 'index.php?smarty_google_reviews_feed=1', 'top'); // url: ?smarty-google-reviews-feed
        add_rewrite_rule('^smarty-csv-export/?', 'index.php?smarty_csv_export=1', 'top');                   // url: ?smarty-csv-export
        add_rewrite_rule('^smarty-bing-feed/?', 'index.php?smarty_bing_feed=1', 'top');                     // url: ?smarty-bing-feed
    }
    add_action('init', 'smarty_gfg_feed_generator_add_rewrite_rules');
}

if (!function_exists('smarty_gfg_feed_generator_query_vars')) {
    /**
     * Register query vars for custom endpoints.
     */
    function smarty_gfg_feed_generator_query_vars($vars) {
        $vars[] = 'smarty_google_feed';
        $vars[] = 'smarty_google_reviews_feed';
        $vars[] = 'smarty_csv_export';
        $vars[] = 'smarty_bing_feed';
        return $vars;
    }
    add_filter('query_vars', 'smarty_gfg_feed_generator_query_vars');
}

if (!function_exists('smarty_gfg_feed_generator_template_redirect')) {
    /**
     * Handle requests to custom endpoints.
     */
    function smarty_gfg_feed_generator_template_redirect() {
        if (get_query_var('smarty_google_feed')) {
            smarty_gfg_generate_google_feed();
            exit;
        }

        if (get_query_var('smarty_google_reviews_feed')) {
            smarty_gfg_generate_google_reviews_feed();
            exit;
        }

        if (get_query_var('smarty_csv_export')) {
            smarty_gfg_generate_csv_export();
            exit;
        }

        if (get_query_var('smarty_bing_feed')) {
            smarty_gfg_generate_bing_feed();
            exit;
        }

        // Output an error message for invalid endpoints
        if (is_404()) {
            wp_die(__('Invalid feed request.', 'smarty-google-feed-generator'));
        }
    }
    add_action('template_redirect', 'smarty_gfg_feed_generator_template_redirect');
}

if (!function_exists('smarty_gfg_generate_google_feed')) {
    /**
     * Generates the custom Google product feed.
     * 
     * This function is designed to generate a custom Google product feed for WooCommerce products. 
     * It uses WordPress and WooCommerce functions to build an XML feed that conforms to Google's specifications for product feeds.
     */
    function smarty_gfg_generate_google_feed() {
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

                        // Add product details as child nodes
                        smarty_gfg_add_google_product_details($dom, $item, $product, $variation);
                    }
                } else {
                    // Process simple products similarly
                    $item = $dom->createElement('item');
                    $feed->appendChild($item);

                    // Add product details as child nodes
                    smarty_gfg_add_google_product_details($dom, $item, $product);
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
    add_action('smarty_gfg_generate_google_feed', 'smarty_gfg_generate_google_feed');
}

if (!function_exists('smarty_gfg_add_google_product_details')) {
    /**
     * Adds Google product details to the XML item node.
     *
     * @param DOMDocument $dom The DOMDocument instance.
     * @param DOMElement $item The item element to which details are added.
     * @param WC_Product $product The WooCommerce product instance.
     * @param WC_Product $variation Optional. The variation instance if the product is variable.
     */
    function smarty_gfg_add_google_product_details($dom, $item, $product, $variation = null) {
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
        $item->appendChild($dom->createElementNS($gNamespace, 'image_link', wp_get_attachment_url($image_id)));

        // Add additional images
        $gallery_ids = $product->get_gallery_image_ids();
        foreach ($gallery_ids as $gallery_id) {
            $item->appendChild($dom->createElementNS($gNamespace, 'additional_image_link', wp_get_attachment_url($gallery_id)));
        }

        // Add price details
        $item->appendChild($dom->createElementNS($gNamespace, 'price', htmlspecialchars($price . ' ' . get_woocommerce_currency())));
        if ($is_on_sale) {
            $item->appendChild($dom->createElementNS($gNamespace, 'sale_price', htmlspecialchars($sale_price . ' ' . get_woocommerce_currency())));
        }

        // Add product categories
        $google_product_category = smarty_gfg_get_cleaned_google_product_category();
        if ($google_product_category) {
            $item->appendChild($dom->createElementNS($gNamespace, 'g:google_product_category', htmlspecialchars($google_product_category)));
        }

        // Add product categories
        $categories = wp_get_post_terms($product->get_id(), 'product_cat');
        if (!empty($categories) && !is_wp_error($categories)) {
            $category_names = array_map(function($term) { return $term->name; }, $categories);
            $item->appendChild($dom->createElementNS($gNamespace, 'product_type', htmlspecialchars(join(' > ', $category_names))));
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

        // Add condition
        $item->appendChild($dom->createElementNS($gNamespace, 'g:condition', 'new'));

        // Add brand
        $brand = get_bloginfo('name'); // Use the site name as the brand
        $item->appendChild($dom->createElementNS($gNamespace, 'g:brand', htmlspecialchars($brand)));

        ////////////////////////////////////////
        // Shipping Attribute //////////////////
        ////////////////////////////////////////
                
        // Detect country based on the current request URL (better for multisite)
        $request_uri = $_SERVER['REQUEST_URI']; // Get the requested URL path
        $filter_country = null; // Default to null

        // Detect country using regex match
        if (preg_match('/\/cz\//i', $request_uri)) {
            $filter_country = 'CZ';
        } elseif (preg_match('/\/pl\//i', $request_uri)) {
            $filter_country = 'PL';
        } elseif (preg_match('/\/de\//i', $request_uri)) {
            $filter_country = 'DE';
        } elseif (preg_match('/\/at\//i', $request_uri)) {
            $filter_country = 'AT';
        } elseif (preg_match('/\/ch\//i', $request_uri)) {
            $filter_country = 'CH';
        } elseif (preg_match('/\/nl\//i', $request_uri)) {
            $filter_country = 'NL';
        } elseif (preg_match('/\/be\//i', $request_uri)) {
            $filter_country = 'BE';
        } elseif (preg_match('/\/it\//i', $request_uri)) {
            $filter_country = 'IT';
        } elseif (preg_match('/\/es\//i', $request_uri)) {
            $filter_country = 'ES';
        } elseif (preg_match('/\/ro\//i', $request_uri)) {
            $filter_country = 'RO';
        } elseif (preg_match('/\/fr\//i', $request_uri)) {
            $filter_country = 'FR';
        }

        // Fetch shipping rates for detected country
        if ($filter_country) {
            $shipping_rates = smarty_gfg_get_shipping_rates_google($filter_country);
            if (!empty($shipping_rates)) {
                foreach ($shipping_rates as $shipping_rate) {
                    $shipping_element = $dom->createElementNS($gNamespace, 'g:shipping');

                    // Country
                    $shipping_element->appendChild($dom->createElementNS($gNamespace, 'g:country', $shipping_rate['country']));

                    // Service (if available)
                    if (!empty($shipping_rate['service'])) {
                        $shipping_element->appendChild($dom->createElementNS($gNamespace, 'g:service', $shipping_rate['service']));
                    }

                    // Price
                    $shipping_element->appendChild($dom->createElementNS($gNamespace, 'g:price', $shipping_rate['price']));

                    // Add to item
                    $item->appendChild($shipping_element);
                }
            }
        }

        // Custom Labels
        $item->appendChild($dom->createElementNS($gNamespace, 'g:custom_label_0', smarty_gfg_get_custom_label_0($product)));
        $item->appendChild($dom->createElementNS($gNamespace, 'g:custom_label_1', smarty_gfg_get_custom_label_1($product)));
        $item->appendChild($dom->createElementNS($gNamespace, 'g:custom_label_2', smarty_gfg_get_custom_label_2($product)));
        $item->appendChild($dom->createElementNS($gNamespace, 'g:custom_label_3', smarty_gfg_get_custom_label_3($product)));
        $item->appendChild($dom->createElementNS($gNamespace, 'g:custom_label_4', smarty_gfg_get_custom_label_4($product)));
    }
}

if (!function_exists('smarty_gfg_get_shipping_rates_google')) {
    function smarty_gfg_get_shipping_rates_google($filter_country = null) {
        $shipping_rates = [];
        $supported_countries = ['DE', 'AT', 'CZ', 'PL', 'CH', 'NL', 'BE', 'IT', 'ES', 'RO', 'FR'];

        $zones = WC_Shipping_Zones::get_zones();
        $default_zone = new WC_Shipping_Zone(0);
        $zones[0] = ['zone_id' => 0, 'zone' => $default_zone];

        foreach ($zones as $zone) {
            $zone_obj = new WC_Shipping_Zone($zone['zone_id']);
            $zone_methods = $zone_obj->get_shipping_methods();

            foreach ($zone_methods as $method) {
                if ($method->enabled !== 'yes') {
                    continue;
                }

                foreach ($supported_countries as $country) {
                    if ($filter_country && $filter_country !== $country) {
                        continue;
                    }

                    if ($method->id === 'flat_rate') {
                        $rate = number_format(floatval($method->cost), 2, '.', '') . ' ' . get_woocommerce_currency();
                        $shipping_rates[] = ['country' => $country, 'service' => 'Standard', 'price' => $rate];
                    } elseif ($method->id === 'free_shipping') {
                        $shipping_rates[] = ['country' => $country, 'service' => 'Free Shipping', 'price' => '0.00 ' . get_woocommerce_currency()];
                    }
                }
            }
        }

        return $shipping_rates;
    }
}

if (!function_exists('smarty_gfg_generate_google_reviews_feed')) {
    /**
     * Generates the custom Google product review feed.
     * 
     * This function retrieves all the products and their reviews from a WooCommerce store and constructs
     * an XML feed that adheres to Google's specifications for review feeds.
     */
    function smarty_gfg_generate_google_reviews_feed() {
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
                    $entry->appendChild($dom->createElementNS($gNamespace, 'g:id', htmlspecialchars(get_the_product_sku($product->ID))));
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
    add_action('smarty_gfg_generate_google_reviews_feed', 'smarty_gfg_generate_google_reviews_feed'); // the first one is event
}

if (!function_exists('smarty_gfg_generate_csv_export')) {
    /**
     * Generates a CSV file export of products for Google Merchant Center or similar services.
     * 
     * This function handles generating a downloadable CSV file that includes details about products
     * filtered based on certain criteria such as stock status and product category.
     */
    function smarty_gfg_generate_csv_export() {
        // Set headers to force download and define the file name
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="woocommerce-products.csv"');
    
        // Open PHP output stream as a writable file
        $handle = fopen('php://output', 'w');
        
        // Check if the file handle is valid
        if ($handle === false) {
            wp_die('Failed to open output stream for CSV export'); // Kill the script and display message if handle is invalid
        }
    
        // Define the header row of the CSV file
        $headers = array(
            'ID',                       // WooCommerce product ID
            'ID2',                      // SKU, often used as an alternate identifier
            'Final URL',                // URL to the product page
            'Final Mobile URL',         // URL to the product page, mobile-specific if applicable
            'Image URL',                // Main image URL
            'Item Title',               // Title of the product
            'Item Description',         // Description of the product
            'Item Category',            // Categories the product belongs to
            'Product Type',             // WooCommerce hierarchical category structure
            'Price',                    // Regular price of the product
            'Sale Price',               // Sale price if the product is on discount
            'Google Product Category',  // Google's product category if needed for feeds
            'Is Bundle',                // Indicates if the product is a bundle
            'MPN',                      // Manufacturer Part Number
            'Availability',             // Stock status
            'Condition',                // Condition of the product, usually "new" for e-commerce
            'Brand',                    // Brand of the product
            'Custom Label 0',           // Custom label 0
            'Custom Label 1',           // Custom label 1
            'Custom Label 2',           // Custom label 2
            'Custom Label 3',           // Custom label 3
            'Custom Label 4',           // Custom label 4
        );
    
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
            
            // Convert the first WebP image to PNG if needed
            $image_id = $product->get_image_id();
            $image_url = wp_get_attachment_url($image_id);
            $file_path = get_attached_file($image_id);

            if ($file_path && preg_match('/\.webp$/', $file_path)) {
                $new_file_path = preg_replace('/\.webp$/', '.png', $file_path);
                if (smarty_gfg_convert_webp_to_png($file_path, $new_file_path)) {
                    // Update the attachment file type post meta
                    wp_update_attachment_metadata($image_id, wp_generate_attachment_metadata($image_id, $new_file_path));
                    update_post_meta($image_id, '_wp_attached_file', $new_file_path);
                    // Regenerate thumbnails
                    if (function_exists('wp_update_attachment_metadata')) {
                        wp_update_attachment_metadata($image_id, wp_generate_attachment_metadata($image_id, $new_file_path));
                    }
                    // Update image URL
                    $image_url = wp_get_attachment_url($image_id);
                    // Optionally, delete the original WEBP file
                    @unlink($file_path);
                }
            }

            // Prepare product data for the CSV
            $id = $product->get_id();
            $regular_price = $product->get_regular_price();
            $sale_price = $product->get_sale_price() ?: ''; // Fallback to empty string if no sale price
            $categories = wp_get_post_terms($id, 'product_cat', array('fields' => 'names'));
            $categories = !empty($categories) ? implode(', ', $categories) : '';
            $image_id = $product->get_image_id();
            $image_link = $image_id ? wp_get_attachment_url($image_id) : '';

            // Custom meta fields for title and description if set
            $meta_title = get_post_meta($id, get_option('smarty_meta_title_field', 'meta-title'), true);
            $meta_description = get_post_meta($id, get_option('smarty_meta_description_field', 'meta-description'), true);
            $name = !empty($meta_title) ? htmlspecialchars(strip_tags($meta_title)) : htmlspecialchars(strip_tags($product->get_name()));
            $description = !empty($meta_description) ? htmlspecialchars(strip_tags($meta_description)) : htmlspecialchars(strip_tags($product->get_short_description()));
            $description = preg_replace('/\s+/', ' ', $description); // Normalize whitespace in descriptions
            $availability = $product->is_in_stock() ? 'in stock' : 'out of stock';
            
            // Get Google category as ID or name
            $google_product_category = smarty_gfg_get_cleaned_google_product_category(); // Get Google category from plugin settings
            //error_log('Google Product Category: ' . $google_product_category); // Debugging line
            if ($google_category_as_id) {
                $google_product_category = explode('-', $google_product_category)[0]; // Get only the ID part
                //error_log('Google Product Category ID: ' . $google_product_category); // Debugging line
            }

            $brand = get_bloginfo('name');

            // Extract hierarchical category structure for Product Type
            $product_categories = wp_get_post_terms($product->get_id(), 'product_cat');
            $product_type = '';
            if (!empty($product_categories) && !is_wp_error($product_categories)) {
                $category_names = array_map(function($term) { return $term->name; }, $product_categories);
                $product_type = join(' > ', $category_names);
            }

            // Custom Labels
            $custom_label_0 = smarty_gfg_get_custom_label_0($product);
            $custom_label_1 = smarty_gfg_get_custom_label_1($product);
            $custom_label_2 = smarty_gfg_get_custom_label_2($product);
            $custom_label_3 = smarty_gfg_get_custom_label_3($product);
            $custom_label_4 = smarty_gfg_get_custom_label_4($product);

            // Check if the product has the "bundle" tag
            $is_bundle = 'no';
            $product_tags = wp_get_post_terms($product->get_id(), 'product_tag', array('fields' => 'slugs'));
            if (in_array('bundle', $product_tags)) {
                $is_bundle = 'yes';
            }
            
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
                        'ID'                      => $id,
                        'ID2'                     => $sku,
                        'Final URL'               => $product_link,
                        'Final Mobile URL'        => $product_link,
                        'Image URL'               => $variation_image,
                        'Item Title'              => $name,
                        'Item Description'        => $description,
                        'Item Category'           => $categories,
                        'Product Type'            => $product_type,
                        'Price'                   => $variation_price,
                        'Sale Price'              => $variation_sale_price,
                        'Google Product Category' => $google_product_category,
                        'Is Bundle'               => $is_bundle,
                        'MPN'                     => $sku,
                        'Availability'            => $availability,
                        'Condition'               => 'New',
                        'Brand'                   => $brand,
                        'Custom Label 0'          => $custom_label_0, 
                        'Custom Label 1'          => $custom_label_1, 
                        'Custom Label 2'          => $custom_label_2,
                        'Custom Label 3'          => $custom_label_3, 
                        'Custom Label 4'          => $custom_label_4,
                    );
                }
            } else {
                // Prepare row for a simple product
                $sku = $product->get_sku();
                $row = array(
                    'ID'                      => $id,
                    'ID2'                     => $sku,
                    'Final URL'               => $product_link,
                    'Final Mobile URL'        => $product_link,
                    'Image URL'               => $image_link,
                    'Item Title'              => $name,
                    'Item Description'        => $description,
                    'Item Category'           => $categories,
                    'Product Type'            => $product_type,
                    'Price'                   => $regular_price,
                    'Sale Price'              => $sale_price,
                    'Google Product Category' => $google_product_category,
                    'Is Bundle'               => $is_bundle,
                    'MPN'                     => $sku,
                    'Availability'            => $availability,
                    'Condition'               => 'New',
                    'Brand'                   => $brand,
                    'Custom Label 0'          => $custom_label_0, 
                    'Custom Label 1'          => $custom_label_1, 
                    'Custom Label 2'          => $custom_label_2,
                    'Custom Label 3'          => $custom_label_3, 
                    'Custom Label 4'          => $custom_label_4,
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
}

if (!function_exists('smarty_gfg_generate_bing_feed')) {
    /**
     * Generate Bing feed.
     */
    function smarty_gfg_generate_bing_feed() {
        header('Content-Type: application/xml; charset=utf-8');
        
        // Check if the clear cache option is enabled
        if (get_option('smarty_clear_cache')) {
            delete_transient('smarty_bing_feed');
        }

        // Attempt to retrieve the cached version of the feed
        $cached_feed = get_transient('smarty_bing_feed');
        if ($cached_feed !== false) {
            echo $cached_feed; // Output the cached feed and stop processing if it exists
            ob_end_flush();
            exit;
        }

        if (class_exists('WooCommerce')) {
            $products = wc_get_products(array(
                'status' => 'publish',
                'limit'  => -1,
            ));

            $dom = new DOMDocument('1.0', 'UTF-8');
            $dom->formatOutput = true;

            $feed = $dom->createElement('feed');
            $feed->setAttribute('xmlns:g', 'http://base.google.com/ns/1.0');
            $dom->appendChild($feed);

            foreach ($products as $product) {
                $item = $dom->createElement('item');
                $feed->appendChild($item);

                // Product details
                $item->appendChild($dom->createElement('id', $product->get_id()));
                $item->appendChild($dom->createElement('title', htmlspecialchars($product->get_name())));
                $item->appendChild($dom->createElement('link', get_permalink($product->get_id())));

                // Description
                $description = $product->get_short_description() ?: $product->get_description();
                $item->appendChild($dom->createElement('description', htmlspecialchars(strip_tags($description))));

                // Main image
                $item->appendChild($dom->createElement('image_link', wp_get_attachment_url($product->get_image_id())));

                // Additional images
                $gallery_ids = $product->get_gallery_image_ids();
                if (!empty($gallery_ids)) {
                    foreach ($gallery_ids as $gallery_id) {
                        $image_url = wp_get_attachment_url($gallery_id);
                        if ($image_url) {
                            $item->appendChild($dom->createElement('additional_image_link', $image_url));
                        }
                    }
                }

                // Price
                $price = $product->get_price();
                $item->appendChild($dom->createElement('price', $price . ' ' . get_woocommerce_currency()));

                // Product categories
                $categories = wp_get_post_terms($product->get_id(), 'product_cat');
                if (!empty($categories) && !is_wp_error($categories)) {
                    $category_names = array_map(function($term) { return $term->name; }, $categories);
                    $item->appendChild($dom->createElement('product_type', htmlspecialchars(join(' > ', $category_names))));
                }

                // Add Google-specific product category to Bing
                $product_category = smarty_gfg_get_cleaned_google_product_category();
                $item->appendChild($dom->createElement('product_category', htmlspecialchars($product_category)));

                // Availability
                $availability = $product->is_in_stock() ? 'in stock' : 'out of stock';
                $item->appendChild($dom->createElement('availability', $availability));

                // Brand
                $brand = get_bloginfo('name');
                $item->appendChild($dom->createElement('brand', htmlspecialchars($brand)));

                ////////////////////////////////////////
                // Shipping Attribute //////////////////
                ////////////////////////////////////////
                
                // Detect country based on the current request URL (better for multisite)
                $request_uri = $_SERVER['REQUEST_URI']; // Get the requested URL path

                // Ensure case-insensitive and trimmed matching
                if (preg_match('/\/de\//i', $request_uri)) {
                    $filter_country = 'DE';
                } elseif (preg_match('/\/at\//i', $request_uri)) {
                    $filter_country = 'AT';
                }

                // Log detection for debugging
                //error_log("Bing Feed - Fixed Country Detection: " . ($filter_country ?: 'NOT DETECTED'));

                // Add Shipping Attribute
                if ($filter_country) {
                    $shipping_string = smarty_gfg_get_shipping_rates_bing($filter_country);

                    // Log the retrieved shipping rates for debugging
                    //error_log("Bing Feed - Shipping Rates for $filter_country: " . $shipping_string);

                    if (!empty($shipping_string)) {
                        $item->appendChild($dom->createElement('shipping', $shipping_string));
                    }
                }
            }

            // Save and output the XML
            $feed_content = $dom->saveXML();
            if ($feed_content) {
                $cache_duration = get_option('smarty_cache_duration', 12); // Default to 12 hours if not set
                set_transient('smarty_bing_feed', $feed_content, $cache_duration * HOUR_IN_SECONDS);

                echo $feed_content;
                ob_end_flush();
                exit; // Ensure the script stops here to prevent further output that could corrupt the feed
            } else {
                ob_end_clean();
                echo '<error>Failed to generate feed content.</error>';
                exit;
            }
        } else {
            echo '<error>WooCommerce is not active.</error>';
            exit;
        }
    }
}

if (!function_exists('smarty_gfg_get_shipping_rates_bing')) {
    /**
     * Get the shipping cost string for Bing feed in the format "country:service:price"
     * Only returns shipping rates for the requested country.
     */
    function smarty_gfg_get_shipping_rates_bing($filter_country = null) {
        $shipping_rates = [];
        $supported_countries = ['DE', 'AT'];

        if (!$filter_country || !in_array($filter_country, $supported_countries)) {
            return ''; // If the country is not supported, return an empty string
        }

        // Get all shipping zones
        $zones = WC_Shipping_Zones::get_zones();
        $default_zone = new WC_Shipping_Zone(0); // Default zone for uncovered locations
        $zones[0] = ['zone_id' => 0, 'zone' => $default_zone];

        foreach ($zones as $zone) {
            $zone_obj = new WC_Shipping_Zone($zone['zone_id']);
            $zone_methods = $zone_obj->get_shipping_methods();

            foreach ($zone_methods as $method) {
                if ($method->enabled !== 'yes') {
                    continue; // Skip disabled methods
                }

                if ($method->id === 'flat_rate') {
                    $rate = isset($method->cost) ? floatval($method->cost) : 0.00;
                    $rate = number_format($rate, 2, '.', '');
                    $shipping_rates[] = "{$filter_country}:Standard:{$rate}";
                } elseif ($method->id === 'free_shipping') {
                    $shipping_rates[] = "{$filter_country}:Free Shipping:0.00";
                } elseif ($method->id === 'local_pickup') {
                    $rate = isset($method->cost) ? floatval($method->cost) : 0.00;
                    $rate = number_format($rate, 2, '.', '');
                    $shipping_rates[] = "{$filter_country}:Local Pickup:{$rate}";
                }
            }
        }

        error_log("Shipping Rates for {$filter_country}: " . implode(', ', $shipping_rates));

        return !empty($shipping_rates) ? implode(', ', $shipping_rates) : '';
    }
}

if (!function_exists('get_the_product_sku')) {
    /**
     * Helper function to get the product SKU.
     * Adjust if your SKU is stored differently.
     */
    function get_the_product_sku($product_id) {
        return get_post_meta($product_id, '_sku', true);
    }
}

if (!function_exists('smarty_gfg_invalidate_feed_cache')) {
    /**
     * Invalidate cache or regenerate feed when a product is created, updated, or deleted.
     */
    function smarty_gfg_invalidate_feed_cache($product_id) {
        // Check if the post is a 'product'
        if (get_post_type($product_id) === 'product') {
            // Invalidate cache
			delete_transient('smarty_google_feed');
			// Regenerate the feed
			smarty_gfg_regenerate_feed();
        }
    }
    add_action('woocommerce_new_product', 'smarty_gfg_invalidate_feed_cache');
    add_action('woocommerce_update_product', 'smarty_gfg_invalidate_feed_cache');
}

if (!function_exists('smarty_gfg_invalidate_feed_cache_on_delete')) {
    /**
     * Invalidates the cached Google product feed and optionally regenerates it upon the deletion of a product.
     * 
     * This function ensures that when a product is deleted from WooCommerce, any cached version of the feed
     * does not continue to include data related to the deleted product.
     *
     * @param int $post_id The ID of the post (product) being deleted.
     */
    function smarty_gfg_invalidate_feed_cache_on_delete($post_id) {
        // Check if the post being deleted is a product
        if (get_post_type($post_id) === 'product') {
            // Invalidate the cache by deleting the stored transient that contains the feed data
            // This forces the system to regenerate the feed next time it's requested, ensuring that the deleted product is no longer included
            delete_transient('smarty_google_feed');
            
            // Regenerate the feed immediately to update the feed file
            // This step is optional but recommended to keep the feed up to date
            smarty_gfg_regenerate_feed();
        }
    }
    add_action('before_delete_post', 'smarty_gfg_invalidate_feed_cache_on_delete');
}

if (!function_exists('smarty_gfg_invalidate_review_feed_cache')) {
    /**
     * Invalidate cache or regenerate review feed when reviews are added, updated, or deleted.
     */
    function smarty_gfg_invalidate_review_feed_cache($comment_id, $comment_approved = '') {
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
    add_action('comment_post', 'smarty_gfg_invalidate_review_feed_cache', 10, 2);
    add_action('edit_comment', 'smarty_gfg_invalidate_review_feed_cache');
    add_action('deleted_comment', 'smarty_gfg_invalidate_review_feed_cache');
    add_action('wp_set_comment_status', 'smarty_gfg_invalidate_review_feed_cache');
}

if (!function_exists('smarty_gfg_regenerate_feed')) {
    /**
     * Regenerates the feed and saves it to a transient or a file.
     * 
     * This function is designed to refresh the product feed by querying the latest product data from WooCommerce,
     * constructing an XML feed, and saving it either to a transient for fast access or directly to a file.
     */
	function smarty_gfg_regenerate_feed() {
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

                    $item->addChild('image_link', wp_get_attachment_url($product->get_image_id()), $gNamespace);

                    // Variation specific image, if different from the main product image
                    $image_id = $variation->get_image_id() ? $variation->get_image_id() : $product->get_image_id();
                    $variationImageURL = wp_get_attachment_url($variation->get_image_id());
                    $mainImageURL = wp_get_attachment_url($product->get_image_id());
                    if ($variationImageURL !== $mainImageURL) {
                        $item->addChild('image_link', wp_get_attachment_url($image_id), $gNamespace);
                    }

                    // Additional images: Loop through gallery if available
                    $gallery_ids = $product->get_gallery_image_ids();
                    foreach ($gallery_ids as $gallery_id) {
                        $item->addChild('additional_image_link', wp_get_attachment_url($gallery_id), $gNamespace);
                    }

                    // Pricing: Regular and sale prices
                    $item->addChild('price', htmlspecialchars($variation->get_regular_price() . ' ' . get_woocommerce_currency()), $gNamespace);
                    if ($variation->is_on_sale()) {
                        $item->addChild('sale_price', htmlspecialchars($variation->get_sale_price() . ' ' . get_woocommerce_currency()), $gNamespace);
                    }

                    // Categories: Compile a list from the product's categories
                    $categories = wp_get_post_terms($product->get_id(), 'product_cat');
                    if (!empty($categories) && !is_wp_error($categories)) {
                        $category_names = array_map(function($term) { return $term->name; }, $categories);
                        $item->addChild('product_type', htmlspecialchars(join(' > ', $category_names)), $gNamespace);
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
                $item->addChild('image_link', wp_get_attachment_url($product->get_image_id()), $gNamespace);
                $gallery_ids = $product->get_gallery_image_ids();
                foreach ($gallery_ids as $gallery_id) {
                    $item->addChild('additional_image_link', wp_get_attachment_url($gallery_id), $gNamespace);
                }
    
                // Pricing information
                $item->addChild('price', htmlspecialchars($product->get_price() . ' ' . get_woocommerce_currency()), $gNamespace);
                if ($product->is_on_sale() && !empty($product->get_sale_price())) {
                    $item->addChild('sale_price', htmlspecialchars($product->get_sale_price() . ' ' . get_woocommerce_currency()), $gNamespace);
                }
    
                // Category information
                $categories = wp_get_post_terms($product->get_id(), 'product_cat');
                if (!empty($categories) && !is_wp_error($categories)) {
                    $category_names = array_map(function($term) { return $term->name; }, $categories);
                    $item->addChild('product_type', htmlspecialchars(join(' > ', $category_names)), $gNamespace);
                }
            }
        }

        // Save the generated XML content to a transient or a file for later use
		$feed_content = $xml->asXML();
        $cache_duration = get_option('smarty_cache_duration', 12); // Default to 12 hours if not set
        set_transient('smarty_google_feed', $feed_content, $cache_duration * HOUR_IN_SECONDS);  // Cache the feed using WordPress transients              
		file_put_contents(WP_CONTENT_DIR . '/uploads/smarty_google_feed.xml', $feed_content);   // Optionally save the feed to a file in the WP uploads directory
	}
}

if (!function_exists('smarty_gfg_handle_product_change')) {
    /**
     * Hook into product changes.
     */
    function smarty_gfg_handle_product_change($post_id) {
        if (get_post_type($post_id) == 'product') {
            smarty_gfg_regenerate_feed(); // Regenerate the feed
	    }
    }
	add_action('save_post_product', 'smarty_gfg_handle_product_change');
	add_action('deleted_post', 'smarty_gfg_handle_product_change');
}

if (!function_exists('smarty_gfg_feed_generator_activate')) {
    /**
     * Activation hook to flush rewrite rules and schedule feed regeneration on plugin activation.
     */
    function smarty_gfg_feed_generator_activate() {
        if (!class_exists('WooCommerce')) {
            wp_die('This plugin requires WooCommerce to be installed and active.');
        }

        // Add rewrite rules
        smarty_gfg_feed_generator_add_rewrite_rules();

        // Flush rewrite rules to ensure custom endpoints are registered
        flush_rewrite_rules();

        // Schedule Google Feed Event
        if (!wp_next_scheduled('smarty_gfg_generate_google_feed')) {
            wp_schedule_event(time(), 'twicedaily', 'smarty_gfg_generate_google_feed');
        }

        // Schedule Google Reviews Feed Event
        if (!wp_next_scheduled('smarty_gfg_generate_google_reviews_feed')) {
            wp_schedule_event(time(), 'daily', 'smarty_gfg_generate_google_reviews_feed');
        }
    }
    register_activation_hook(__FILE__, 'smarty_gfg_feed_generator_activate');
}

if (!function_exists('smarty_gfg_deactivate')) {
    /**
     * Deactivation hook to flush rewrite rules, clear scheduled feed regeneration,
     * and remove the generated .xml file on plugin deactivation.
     */
    function smarty_gfg_deactivate() {
        // Flush rewrite rules
        flush_rewrite_rules();

        // Clear scheduled feed regeneration event
        $google_feed_timestamp = wp_next_scheduled('smarty_gfg_generate_google_feed');
        if ($google_feed_timestamp) {
            wp_unschedule_event($google_feed_timestamp, 'smarty_gfg_generate_google_feed');
        }

        // Unscheduling the reviews feed event
        $review_feed_timestamp = wp_next_scheduled('smarty_gfg_generate_google_reviews_feed');
        if ($review_feed_timestamp) {
            wp_unschedule_event($review_feed_timestamp, 'smarty_gfg_generate_google_reviews_feed');
        }

        // Path to the generated XML file
        $feed_file_path = WP_CONTENT_DIR . '/uploads/smarty_google_feed.xml';

        // Check if the file exists and delete it
        if (file_exists($feed_file_path)) {
            unlink($feed_file_path);
        }
    }
    register_deactivation_hook(__FILE__, 'smarty_gfg_deactivate');
}

if (!function_exists('smarty_gfg_convert_and_update_product_image')) {
    /**
     * Converts an image from WEBP to PNG, updates the product image, and regenerates the feed.
     * @param WC_Product $product Product object.
     */
    function smarty_gfg_convert_and_update_product_image($product) {
        $image_id = $product->get_image_id();

        if ($image_id) {
            $file_path = get_attached_file($image_id);
            
            if ($file_path && preg_match('/\.webp$/', $file_path)) {
                $new_file_path = preg_replace('/\.webp$/', '.png', $file_path);
                
                if (smarty_gfg_convert_webp_to_png($file_path, $new_file_path)) {
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
                    smarty_gfg_invalidate_feed_cache($product->get_id());
                }
            }
        }
    }
}

if (!function_exists('smarty_gfg_convert_webp_to_png')) {
    /**
     * Converts a WEBP image file to PNG.
     * 
     * @param string $source The source file path.
     * @param string $destination The destination file path.
     * @return bool True on success, false on failure.
     */
    function smarty_gfg_convert_webp_to_png($source, $destination) {
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
    add_action('woocommerce_admin_process_product_object', 'smarty_gfg_convert_and_update_product_image', 10, 1);
}

if (!function_exists('smarty_gfg_get_google_product_categories')) {
    /**
     * Fetch Google product categories from the taxonomy file and return them as an associative array with IDs.
     * 
     * @return array Associative array of category IDs and names.
     */
    function smarty_gfg_get_google_product_categories() {
        // Check if the categories are already cached
        $categories = get_transient('smarty_google_product_categories');
        
        if ($categories === false) {
            // URL of the Google taxonomy file
            $taxonomy_url = 'https://www.google.com/basepages/producttype/taxonomy-with-ids.en-US.txt';
            
            // Download the file
            $response = wp_remote_get($taxonomy_url);
            
            if (is_wp_error($response)) {
                return [];
            }

            $body = wp_remote_retrieve_body($response);
            
            // Parse the file
            $lines = explode("\n", $body);
            $categories = [];

            foreach ($lines as $line) {
                if (!empty($line) && strpos($line, '#') !== 0) {
                    $categories[] = trim($line);
                }
            }

            // Cache the feed using WordPress transients              
            $cache_duration = get_option('smarty_cache_duration', 12); // Default to 12 hours if not set
            set_transient('smarty_google_feed', $categories, $cache_duration * HOUR_IN_SECONDS);  
        }
        
        return $categories;
    }
}

if (!function_exists('smarty_gfg_register_settings_page')) {
    /**
     * Add settings page to the WordPress admin menu.
     */
    function smarty_gfg_register_settings_page() {
        add_submenu_page(
            'woocommerce',
            __('Google Feed Generator | | Settings', 'smarty-google-feed-generator'),
            __('Google Feed Generator', 'smarty-google-feed-generator'),                // Menu title
            'manage_options',                                                           // Capability required to access this page
            'smarty-gfg-settings',                                                      // Menu slug
            'smarty_gfg_settings_page_content'                                          // Callback function to display the page content
        );
    }
    add_action('admin_menu', 'smarty_gfg_register_settings_page');
}

if (!function_exists('smarty_gfg_get_cleaned_google_product_category')) {
    /**
     * Retrieves the Google Product Category based on plugin settings.
     *
     * This function fetches the Google Product Category as stored in the plugin settings.
     * It optionally uses the category ID or name based on the "Use Google Category ID" setting.
     *
     * @return string The cleaned Google Product Category name or ID.
     */
    function smarty_gfg_get_cleaned_google_product_category() {
        // Get the category option value from settings
        $category = get_option('smarty_google_product_category');
        $use_id = get_option('smarty_google_category_as_id', false);

        if (!$category) {
            return ''; // Return empty string if no category is set
        }

        // Split the category string by " - " to separate ID and name
        $parts = explode(' - ', $category);

        if ($use_id && count($parts) > 1) {
            // Return the ID if "Use Google Category ID" is enabled
            return trim($parts[0]);
        } elseif (count($parts) > 1) {
            // Return the name if only the name is to be used
            return trim($parts[1]);
        }

        // If the string doesn't contain a "-", return the original value
        return $category;
    }
}

if (!function_exists('smarty_gfg_sanitize_checkbox')) {
    /**
     * Sanitize a checkbox field input.
     *
     * @param mixed $input The checkbox input value.
     * @return int 1 if checked, 0 otherwise.
     */
    function smarty_gfg_sanitize_checkbox($input) {
        return $input == 1 ? 1 : 0;
    }
}

if (!function_exists('smarty_gfg_feed_generator_register_settings')) {
    /**
     * Register plugin settings.
     */
    function smarty_gfg_feed_generator_register_settings() {
        // General Settings
        register_setting('smarty_feed_generator_settings', 'smarty_google_product_category');
        register_setting('smarty_feed_generator_settings', 'smarty_google_category_as_id', 'smarty_gfg_sanitize_checkbox');
        register_setting('smarty_feed_generator_settings', 'smarty_exclude_patterns', 'sanitize_textarea_field');
        register_setting('smarty_feed_generator_settings', 'smarty_excluded_categories');
        
        // Custom Label Settings
        register_setting('smarty_feed_generator_settings', 'smarty_custom_label_0_older_than_days', 'absint');
        register_setting('smarty_feed_generator_settings', 'smarty_custom_label_0_older_than_value', 'sanitize_text_field');
        register_setting('smarty_feed_generator_settings', 'smarty_custom_label_0_not_older_than_days', 'absint');
        register_setting('smarty_feed_generator_settings', 'smarty_custom_label_0_not_older_than_value', 'sanitize_text_field');
        register_setting('smarty_feed_generator_settings', 'smarty_custom_label_1_most_ordered_days', 'absint');
        register_setting('smarty_feed_generator_settings', 'smarty_custom_label_1_most_ordered_value', 'sanitize_text_field');
        register_setting('smarty_feed_generator_settings', 'smarty_custom_label_2_high_rating_value', 'sanitize_text_field');
        register_setting('smarty_feed_generator_settings', 'smarty_custom_label_3_category');
        register_setting('smarty_feed_generator_settings', 'smarty_custom_label_3_category_value', 'smarty_gfg_sanitize_category_values');
        register_setting('smarty_feed_generator_settings', 'smarty_custom_label_4_sale_price_value', 'sanitize_text_field');

        // Meta Fields
        register_setting('smarty_feed_generator_settings', 'smarty_meta_title_field', 'sanitize_text_field');
        register_setting('smarty_feed_generator_settings', 'smarty_meta_description_field', 'sanitize_text_field');
        
        // Cache Settings
        register_setting('smarty_feed_generator_settings', 'smarty_clear_cache', 'smarty_gfg_sanitize_checkbox');
        register_setting('smarty_feed_generator_settings', 'smarty_cache_duration', 'absint');

        // Add General section
        add_settings_section(
            'smarty_gfg_section_general',                                   // ID of the section
            __('General', 'smarty-google-feed-generator'),                  // Title of the section
            'smarty_gfg_section_general_callback',                          // Callback function that fills the section with the desired content
            'smarty_feed_generator_settings'                                // Page on which to add the section
        );

        // Add Custom Labels section
        add_settings_section(
            'smarty_gfg_section_custom_labels',                             // ID of the section
            __('Custom Labels', 'smarty-google-feed-generator'),            // Title of the section
            'smarty_gfg_section_custom_labels_callback',                    // Callback function that fills the section with the desired content
            'smarty_feed_generator_settings'                                // Page on which to add the section
        );

        // Add Convert Images section
        add_settings_section(
            'smarty_gfg_section_convert_images',                            // ID of the section
            __('Convert Images', 'smarty-google-feed-generator'),           // Title of the section
            'smarty_gfg_section_convert_images_callback',                   // Callback function that fills the section with the desired content
            'smarty_feed_generator_settings'                                // Page on which to add the section
        );

        // Add Generate Feeds section
        add_settings_section(
            'smarty_gfg_section_generate_feeds',                            // ID of the section
            __('Generate Feeds', 'smarty-google-feed-generator'),           // Title of the section
            'smarty_gfg_section_generate_feeds_callback',                   // Callback function that fills the section with the desired content
            'smarty_feed_generator_settings'                                // Page on which to add the section
        );

        // Add Meta Fields section
        add_settings_section(
            'smarty_gfg_section_meta_fields',                               // ID of the section
            __('Meta Fields', 'smarty-google-feed-generator'),              // Title of the section
            'smarty_gfg_section_meta_fields_callback',                      // Callback function that fills the section with the desired content
            'smarty_feed_generator_settings'                                // Page on which to add the section
        );

        // Add Settings section
        add_settings_section(
            'smarty_gfg_section_settings',                                  // ID of the section
            __('Cache', 'smarty-google-feed-generator'),                    // Title of the section
            'smarty_gfg_section_settings_callback',                         // Callback function that fills the section with the desired content
            'smarty_feed_generator_settings'                                // Page on which to add the section
        );

        // Add settings fields
        add_settings_field(
            'smarty_google_product_category',                               // ID of the field
            __('Google Product Category', 'smarty-google-feed-generator'),  // Title of the field
            'smarty_gfg_google_product_category_callback',                  // Callback function to display the field
            'smarty_feed_generator_settings',                               // Page on which to add the field
            'smarty_gfg_section_general'                                    // Section to which this field belongs
        );

        add_settings_field(
            'smarty_google_category_as_id',                                 // ID of the field
            __('Use Google Category ID', 'smarty-google-feed-generator'),   // Title of the field
            'smarty_gfg_google_category_as_id_callback',                    // Callback function to display the field
            'smarty_feed_generator_settings',                               // Page on which to add the field
            'smarty_gfg_section_general'                                    // Section to which this field belongs
        );

        add_settings_field(
            'smarty_exclude_patterns',                                      // ID of the field
            __('Exclude Patterns', 'smarty-google-feed-generator'),         // Title of the field
            'smarty_gfg_exclude_patterns_callback',                         // Callback function to display the field
            'smarty_feed_generator_settings',                               // Page on which to add the field
            'smarty_gfg_section_general'                                    // Section to which this field belongs
        );

        add_settings_field(
            'smarty_excluded_categories',                                   // ID of the field
            __('Excluded Categories', 'smarty-google-feed-generator'),      // Title of the field
            'smarty_gfg_excluded_categories_callback',                      // Callback function to display the field
            'smarty_feed_generator_settings',                               // Page on which to add the field
            'smarty_gfg_section_general'                                    // Section to which this field belongs
        );
        
        // Add settings fields for each criteria
        add_settings_field(
            'smarty_custom_label_0_older_than_days',                        // ID of the field
            __('Older Than (Days)', 'smarty-google-feed-generator'),        // Title of the field
            'smarty_gfg_custom_label_days_callback',                        // Callback function to display the field
            'smarty_feed_generator_settings',                               // Page on which to add the field
            'smarty_gfg_section_custom_labels',                             // Section to which this field belongs
            ['label' => 'smarty_custom_label_0_older_than_days']
        );

        add_settings_field(
            'smarty_custom_label_0_older_than_value',                       // ID of the field
            __('Older Than Value', 'smarty-google-feed-generator'),         // Title of the field
            'smarty_gfg_custom_label_value_callback',                       // Callback function to display the field
            'smarty_feed_generator_settings',                               // Page on which to add the field
            'smarty_gfg_section_custom_labels',                             // Section to which this field belongs
            ['label' => 'smarty_custom_label_0_older_than_value']
        );

        add_settings_field(
            'smarty_custom_label_0_not_older_than_days',                    // ID of the field
            __('Not Older Than (Days)', 'smarty-google-feed-generator'),    // Title of the field
            'smarty_gfg_custom_label_days_callback',                        // Callback function to display the field
            'smarty_feed_generator_settings',                               // Page on which to add the field
            'smarty_gfg_section_custom_labels',                             // Section to which this field belongs
            ['label' => 'smarty_custom_label_0_not_older_than_days']
        );

        add_settings_field(
            'smarty_custom_label_0_not_older_than_value',                   // ID of the field
            __('Not Older Than Value', 'smarty-google-feed-generator'),     // Title of the field
            'smarty_gfg_custom_label_value_callback',                       // Callback function to display the field
            'smarty_feed_generator_settings',                               // Page on which to add the field
            'smarty_gfg_section_custom_labels',                             // Section to which this field belongs
            ['label' => 'smarty_custom_label_0_not_older_than_value']
        );

        add_settings_field(
            'smarty_custom_label_1_most_ordered_days',                          // ID of the field
            __('Most Ordered in Last (Days)', 'smarty-google-feed-generator'),  // Title of the field
            'smarty_gfg_custom_label_days_callback',                            // Callback function to display the field
            'smarty_feed_generator_settings',                                   // Page on which to add the field
            'smarty_gfg_section_custom_labels',                                 // Section to which this field belongs
            ['label' => 'smarty_custom_label_1_most_ordered_days']
        );

        add_settings_field(
            'smarty_custom_label_1_most_ordered_value',                     // ID of the field
            __('Most Ordered Value', 'smarty-google-feed-generator'),       // Title of the field
            'smarty_gfg_custom_label_value_callback',                       // Callback function to display the field
            'smarty_feed_generator_settings',                               // Page on which to add the field
            'smarty_gfg_section_custom_labels',                             // Section to which this field belongs
            ['label' => 'smarty_custom_label_1_most_ordered_value']
        );

        add_settings_field(
            'smarty_custom_label_2_high_rating_value',                      // ID of the field
            __('High Rating Value', 'smarty-google-feed-generator'),        // Title of the field
            'smarty_gfg_custom_label_value_callback',                       // Callback function to display the field
            'smarty_feed_generator_settings',                               // Page on which to add the field
            'smarty_gfg_section_custom_labels',                             // Section to which this field belongs
            ['label' => 'smarty_custom_label_2_high_rating_value']
        );

        add_settings_field(
            'smarty_custom_label_3_category',                               // ID of the field
            __('Category', 'smarty-google-feed-generator'),                 // Title of the field
            'smarty_gfg_custom_label_category_callback',                    // Callback function to display the field
            'smarty_feed_generator_settings',                               // Page on which to add the field
            'smarty_gfg_section_custom_labels',                             // Section to which this field belongs
            ['label' => 'smarty_custom_label_3_category']
        );

        add_settings_field(
            'smarty_custom_label_3_category_value',                         // ID of the field
            __('Category Value', 'smarty-google-feed-generator'),           // Title of the field
            'smarty_gfg_custom_label_value_callback',                       // Callback function to display the field
            'smarty_feed_generator_settings',                               // Page on which to add the field
            'smarty_gfg_section_custom_labels',                             // Section to which this field belongs
            ['label' => 'smarty_custom_label_3_category_value']
        );

        add_settings_field(
            'smarty_custom_label_4_sale_price_value',                       // ID of the field
            __('Sale Price Value', 'smarty-google-feed-generator'),         // Title of the field
            'smarty_gfg_custom_label_value_callback',                       // Callback function to display the field
            'smarty_feed_generator_settings',                               // Page on which to add the field
            'smarty_gfg_section_custom_labels',                             // Section to which this field belongs
            ['label' => 'smarty_custom_label_4_sale_price_value']
        );

        add_settings_field(
            'smarty_convert_images',                                        // ID of the field
            __('Convert', 'smarty-google-feed-generator'),                  // Title of the field
            'smarty_gfg_convert_images_button_callback',                    // Callback function to display the field
            'smarty_feed_generator_settings',                               // Page on which to add the field
            'smarty_gfg_section_convert_images'                             // Section to which this field belongs
        );

        add_settings_field(
            'smarty_generate_feed_now',                                     // ID of the field
            null,                                                           // Title of the field
            'smarty_gfg_generate_feed_buttons_callback',                    // Callback function to display the field
            'smarty_feed_generator_settings',                               // Page on which to add the field
            'smarty_gfg_section_generate_feeds'                             // Section to which this field belongs
        );

        // Add settings fields to Meta Fields section
        add_settings_field(
            'smarty_meta_title_field',                                       // ID of the field
            __('Meta Title', 'smarty-google-feed-generator'),                // Title of the field
            'smarty_gfg_meta_title_field_callback',                          // Callback function to display the field
            'smarty_feed_generator_settings',                                // Page on which to add the field
            'smarty_gfg_section_meta_fields'                                 // Section to which this field belongs
        );

        add_settings_field(
            'smarty_meta_description_field',                                 // ID of the field
            __('Meta Description', 'smarty-google-feed-generator'),          // Title of the field
            'smarty_gfg_meta_description_field_callback',                    // Callback function to display the field
            'smarty_feed_generator_settings',                                // Page on which to add the field
            'smarty_gfg_section_meta_fields'                                 // Section to which this field belongs
        );

        // Add settings field to Cache section
        add_settings_field(
            'smarty_clear_cache',                                            // ID of the field
            __('Clear Cache', 'smarty-google-feed-generator'),               // Title of the field
            'smarty_gfg_clear_cache_callback',                               // Callback function to display the field
            'smarty_feed_generator_settings',                                // Page on which to add the field
            'smarty_gfg_section_settings'                                    // Section to which this field belongs
        );

        // Add settings field for cache duration
        add_settings_field(
            'smarty_cache_duration',                                         // ID of the field
            __('Cache Duration (hours)', 'smarty-google-feed-generator'),    // Title of the field
            'smarty_gfg_cache_duration_callback',                            // Callback function to display the field
            'smarty_feed_generator_settings',                                // Page on which to add the field
            'smarty_gfg_section_settings'                                    // Section to which this field belongs
        );
    }
    add_action('admin_init', 'smarty_gfg_feed_generator_register_settings');
}

if (!function_exists('smarty_gfg_section_general_callback')) {
    /**
     * Display the description for the general settings section.
     *
     * This function outputs the description text for the "General" section
     * in the plugin's settings page.
     *
     * @return void
     */
    function smarty_gfg_section_general_callback() {
        echo '<p>' . __('General settings for the Google Feed Generator.', 'smarty-google-feed-generator') . '</p>';
    }
}

if (!function_exists('smarty_gfg_google_product_category_callback')) {
    /**
     * Render the Google Product Category dropdown field.
     *
     * This function outputs an HTML `<select>` dropdown for selecting a Google Product Category.
     * The selected option is retrieved from the plugin's saved options.
     *
     * @return void
     */
    function smarty_gfg_google_product_category_callback() {
        $option = get_option('smarty_google_product_category');
        echo '<select name="smarty_google_product_category" class="smarty-select2-ajax">';
        if ($option) {
            echo '<option value="' . esc_attr($option) . '" selected>' . esc_html($option) . '</option>';
        }
        echo '</select>';
        echo '<p class="description">Select Google Product categories from the list. <br><small><em><b>Important:</b> <span style="color: #c51244;">This also works for Bing Products feed.</span></em></small></p>';
    }
}

if (!function_exists('smarty_gfg_load_google_categories')) {
    /**
     * AJAX handler to load Google Product Categories.
     */
    function smarty_gfg_load_google_categories() {
        check_ajax_referer('smarty_feed_generator_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('You do not have sufficient permissions to access this page.');
        }

        $search = sanitize_text_field($_GET['q']);
        $google_categories = smarty_gfg_get_google_product_categories();

        $results = array();
        foreach ($google_categories as $category) {
            if (stripos($category, $search) !== false) {
                $results[] = array('id' => $category, 'text' => $category);
            }
        }

        wp_send_json_success($results);
    }
    add_action('wp_ajax_smarty_load_google_categories', 'smarty_gfg_load_google_categories');
}

if (!function_exists('smarty_gfg_google_category_as_id_callback')) {
    /**
     * Render the checkbox for using Google Product Category ID.
     *
     * This function outputs a checkbox to allow users to choose whether to use 
     * Google Product Category ID instead of the name in the CSV feed.
     *
     * @return void
     */
    function smarty_gfg_google_category_as_id_callback() {
        $option = get_option('smarty_google_category_as_id');
        echo '<input type="checkbox" name="smarty_google_category_as_id" value="1" ' . checked(1, $option, false) . ' />';
        echo '<p class="description">' . __('Check to use Google Product Category ID in the CSV feed instead of the name.', 'smarty-google-feed-generator') . '</p>';
    }
}

if (!function_exists('smarty_gfg_section_custom_labels_callback')) {
    /**
     * Display the description for the custom labels section.
     *
     * This function outputs the description text for the "Custom Labels" section
     * in the plugin's settings page.
     *
     * @return void
     */
    function smarty_gfg_section_custom_labels_callback() {
        echo '<p>' . __('Define default values for custom labels.', 'smarty-google-feed-generator') . '</p>';
    }
}

if (!function_exists('smarty_gfg_gfg_section_convert_images_callback')) {
    /**
     * Display the description for the convert images section.
     *
     * This function outputs the description text for the "Convert Images" section,
     * explaining the manual WebP to PNG conversion option.
     *
     * @return void
     */
    function smarty_gfg_section_convert_images_callback() {
        echo '<p>' . __('Use the button below to manually convert the first WebP image of each products in to the feed to PNG.', 'smarty-google-feed-generator') . '</p>';
    }
}

if (!function_exists('smarty_gfg_section_generate_feeds_callback')) {
    /**
     * Display the description for the generate feeds section.
     *
     * This function outputs the description text for the "Generate Feeds" section,
     * providing instructions for manually generating product feeds.
     *
     * @return void
     */
    function smarty_gfg_section_generate_feeds_callback() {
        echo '<p>' . __('Use the buttons below to manually generate the feeds.', 'smarty-google-feed-generator') . '</p>';
    }
}

if (!function_exists('smarty_gfg_section_meta_fields_callback')) {
    /**
     * Display the description for the meta fields section.
     *
     * This function outputs the description text for the "Meta Fields" section
     * in the plugin's settings page, explaining options for meta field settings.
     *
     * @return void
     */
    function smarty_gfg_section_meta_fields_callback() {
        echo '<p>' . __('Meta fields settings for the Google Feed Generator.', 'smarty-google-feed-generator') . '</p>';
    }
}

if (!function_exists('smarty_gfg_section_settings_callback')) {
    /**
     * Display the description for the cache settings section.
     *
     * This function outputs the description text for the "Cache" section,
     * explaining options related to caching for the Google Feed Generator.
     *
     * @return void
     */
    function smarty_gfg_section_settings_callback() {
        echo '<p>' . __('Cache settings for the Google Feed Generator.', 'smarty-google-feed-generator') . '</p>';
    }
}

if (!function_exists('smarty_gfg_exclude_patterns_callback')) {
    /**
     * Render the textarea field for excluding patterns in the CSV feed.
     *
     * This function outputs a `<textarea>` field where users can enter patterns
     * to exclude specific products or URLs from the CSV feed.
     *
     * @return void
     */
    function smarty_gfg_exclude_patterns_callback() {
        $option = get_option('smarty_exclude_patterns');
        echo '<textarea name="smarty_exclude_patterns" rows="10" cols="50" class="large-text">' . esc_textarea($option) . '</textarea>';
        echo '<p class="description">' . __('Enter patterns to exclude from the CSV feed, one per line.', 'smarty-google-feed-generator') . '</p>';
    }
}

if (!function_exists('smarty_gfg_excluded_categories_callback')) {
    /**
     * Callback function to display the excluded categories field.
     */
    function smarty_gfg_excluded_categories_callback() {
        $option = get_option('smarty_excluded_categories', array());
        $categories = get_terms(array(
            'taxonomy' => 'product_cat',
            'hide_empty' => false,
        ));

        echo '<select name="smarty_excluded_categories[]" multiple="multiple" class="smarty-excluded-categories" style="width:50%;">';
        foreach ($categories as $category) {
            echo '<option value="' . esc_attr($category->term_id) . '" ' . (in_array($category->term_id, (array)$option) ? 'selected' : '') . '>' . esc_html($category->name) . '</option>';
        }
        echo '</select>';
        echo '<p class="description">' . __('Select categories to exclude from the feed.', 'smarty-google-feed-generator') . '</p>';
    }
}

if (!function_exists('smarty_gfg_custom_label_days_callback')) {
    /**
     * Render a dropdown for custom label days.
     *
     * This function outputs a `<select>` dropdown to allow users to set the number of days
     * for a custom label. The options range from 10 to 120 days.
     *
     * @param array $args An associative array containing the label key for the option.
     * @return void
     */
    function smarty_gfg_custom_label_days_callback($args) {
        $option = get_option($args['label'], 30);
        $days = [10, 20, 30, 60, 90, 120];
        echo '<select name="' . esc_attr($args['label']) . '">';
        foreach ($days as $day) {
            echo '<option value="' . esc_attr($day) . '" ' . selected($option, $day, false) . '>' . esc_html($day) . '</option>';
        }
        echo '</select>';
    }
}

if (!function_exists('smarty_gfg_custom_label_value_callback')) {
    /**
     * Render a text input field for custom label values.
     *
     * This function outputs an input field for entering a custom label value
     * and provides a description based on the specific label.
     *
     * @param array $args An associative array containing the label key for the option.
     * @return void
     */
    function smarty_gfg_custom_label_value_callback($args) {
        $option = get_option($args['label'], '');
        echo '<input type="text" name="' . esc_attr($args['label']) . '" value="' . esc_attr($option) . '" class="regular-text" />';

        // Add custom descriptions based on the label
        switch ($args['label']) {
            case 'smarty_custom_label_0_older_than_value':
                echo '<p class="description">Enter the value to label products older than the specified number of days.</p>';
                break;
            case 'smarty_custom_label_0_not_older_than_value':
                echo '<p class="description">Enter the value to label products not older than the specified number of days.</p>';
                break;
            case 'smarty_custom_label_1_most_ordered_value':
                echo '<p class="description">Enter the value to label the most ordered products in the last specified days.</p>';
                break;
            case 'smarty_custom_label_2_high_rating_value':
                echo '<p class="description">Enter the value to label products with high ratings.</p>';
                break;
            case 'smarty_custom_label_3_category_value':
                echo '<p class="description">Enter custom values for the categories separated by commas. <b>Example:</b> Tech, Apparel, Literature <br><small><em><strong>Important:</strong> <span style="color: #c51244;">Ensure these values are in the same order as the selected categories. </span></em></small></p>';
                break;
            case 'smarty_custom_label_4_sale_price_value':
                echo '<p class="description">Enter the value to label products with a sale price.</p>';
                break;
            default:
                echo '<p class="description">Enter a custom value for this label.</p>';
                break;
        }
    }
}

if (!function_exists('smarty_gfg_custom_label_category_callback')) {
    /**
     * Render a multi-select dropdown for custom label categories.
     *
     * This function outputs a multi-select dropdown to allow users to select product categories
     * for a custom label and provides a description for the selected categories.
     *
     * @param array $args An associative array containing the label key for the option.
     * @return void
     */
    function smarty_gfg_custom_label_category_callback($args) {
        $option = get_option($args['label'], []);
        $categories = get_terms([
            'taxonomy' => 'product_cat',
            'hide_empty' => false,
        ]);

        echo '<select name="' . esc_attr($args['label']) . '[]" multiple="multiple" class="smarty-excluded-categories">';
        foreach ($categories as $category) {
            echo '<option value="' . esc_attr($category->term_id) . '" ' . (in_array($category->term_id, (array) $option) ? 'selected' : '') . '>' . esc_html($category->name) . '</option>';
        }
        echo '</select>';

        // Add description for the category selection
        if ($args['label'] === 'smarty_custom_label_3_category') {
            echo '<p class="description">Select one or multiple categories from the list. <br><small><em><b>Important:</b> <span style="color: #c51244;">Ensure the values for each category are entered in the same order in the Category Value field.</span></em></small></p>';
        }
    }
}

if (!function_exists('smarty_gfg_convert_images_button_callback')) {
    /**
     * Render the "Convert WebP to PNG" button.
     *
     * This function outputs a button to trigger the manual conversion of WebP images
     * to PNG format for products in the feed.
     *
     * @return void
     */
    function smarty_gfg_convert_images_button_callback() {
        echo '<button class="button secondary smarty-convert-images-button" style="display: inline-block; margin-bottom: 10px;">' . __('Convert WebP to PNG', 'smarty-google-feed-generator') . '</button>';
    }
}

if (!function_exists('smarty_gfg_generate_feed_buttons_callback')) {
    /**
     * Render the buttons for manually generating feeds.
     *
     * This function outputs buttons for generating the product feed, reviews feed,
     * CSV export, and Bing feed manually from the plugin settings page.
     *
     * @return void
     */
    function smarty_gfg_generate_feed_buttons_callback() {
        echo '<table class="form-table" style="width: auto;">';
        echo '<tr><th>' . __('Feed Type', 'smarty-google-feed-generator') . '</th><th>' . __('Action', 'smarty-google-feed-generator') . '</th></tr>';

        echo '<tr>';
        echo '<td>' . __('Google Product Feed', 'smarty-google-feed-generator') . '</td>';
        echo '<td><button class="button secondary smarty-generate-feed-button" data-feed-action="generate_google_feed" style="display: inline-flex; align-items: center; gap: 5px;">' . __('Generate', 'smarty-google-feed-generator') . '<span class="dashicons dashicons-external"></span></button></td>';
        echo '</tr>';

        echo '<tr>';
        echo '<td>' . __('Google Reviews Feed', 'smarty-google-feed-generator') . '</td>';
        echo '<td><button class="button secondary smarty-generate-feed-button" data-feed-action="generate_google_reviews_feed" style="display: inline-flex; align-items: center; gap: 5px;">' . __('Generate', 'smarty-google-feed-generator') . '<span class="dashicons dashicons-external"></span></button></td>';
        echo '</tr>';

        echo '<tr>';
        echo '<td>' . __('CSV Export', 'smarty-google-feed-generator') . '</td>';
        echo '<td><button class="button secondary smarty-generate-feed-button" data-feed-action="generate_csv_export" style="display: inline-flex; align-items: center; gap: 5px;">' . __('Generate', 'smarty-google-feed-generator') . '<span class="dashicons dashicons-external"></span></button></td>';
        echo '</tr>';

        echo '<tr>';
        echo '<td>' . __('Bing Product Feed', 'smarty-google-feed-generator') . '</td>';
        echo '<td><button class="button secondary smarty-generate-feed-button" data-feed-action="generate_bing_feed" style="display: inline-flex; align-items: center; gap: 5px;">' . __('Generate', 'smarty-google-feed-generator') . '<span class="dashicons dashicons-external"></span></button></td>';
        echo '</tr>';

        echo '</table>';
    }
}

if (!function_exists('smarty_gfg_meta_title_field_callback')) {
    /**
     * Render the text input for the meta title field.
     *
     * This function outputs a text input field for setting the custom field name
     * used as the product title meta.
     *
     * @return void
     */
    function smarty_gfg_meta_title_field_callback() {
        $option = get_option('smarty_meta_title_field', 'meta-title');
        echo '<input type="text" name="smarty_meta_title_field" value="' . esc_attr($option) . '" class="regular-text" />';
        echo '<p class="description">' . __('Enter the custom field name for the product title meta.', 'smarty-google-feed-generator') . '</p>';
    }
}

if (!function_exists('smarty_gfg_meta_description_field_callback')) {
    /**
     * Render the text input for the meta description field.
     *
     * This function outputs a text input field for setting the custom field name
     * used as the product description meta.
     *
     * @return void
     */
    function smarty_gfg_meta_description_field_callback() {
        $option = get_option('smarty_meta_description_field', 'meta-description');
        echo '<input type="text" name="smarty_meta_description_field" value="' . esc_attr($option) . '" class="regular-text" />';
        echo '<p class="description">' . __('Enter the custom field name for the product description meta.', 'smarty-google-feed-generator') . '</p>';
    }
}

if (!function_exists('smarty_gfg_clear_cache_callback')) {
    /**
     * Render the checkbox for clearing the cache.
     *
     * This function outputs a checkbox to allow users to clear the cache whenever
     * the feed is generated, along with a note about its usage in production.
     *
     * @return void
     */
    function smarty_gfg_clear_cache_callback() {
        $option = get_option('smarty_clear_cache');
        echo '<input type="checkbox" name="smarty_clear_cache" value="1" ' . checked(1, $option, false) . ' />';
        echo '<p class="description">' . __('Check to clear the cache each time the feed is generated. <br><small><em><b>Important:</b> <span style="color: #c51244;">Remove this in production to utilize caching.</span></em></small>', 'smarty-google-feed-generator') . '</p>';
    }
}

if (!function_exists('smarty_gfg_cache_duration_callback')) {
    /**
     * Render the input field for cache duration.
     *
     * This function outputs a number input field for setting the cache duration
     * in hours.
     *
     * @return void
     */
    function smarty_gfg_cache_duration_callback() {
        $option = get_option('smarty_cache_duration', 12); // Default to 12 hours if not set
        echo '<input type="number" name="smarty_cache_duration" value="' . esc_attr($option) . '" />';
        echo '<p class="description">' . __('Set the cache duration in hours.', 'smarty-google-feed-generator') . '</p>';
    }
}

if (!function_exists('smarty_gfg_handle_ajax_convert_images')) {
    /**
     * Handle AJAX request for converting images.
     *
     * This function handles the AJAX request to convert the first WebP image
     * of each product in the feed to PNG format.
     *
     * @return void
     */
    function smarty_gfg_handle_ajax_convert_images() {
        check_ajax_referer('smarty_feed_generator_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('You do not have sufficient permissions to access this page.');
        }

        smarty_gfg_convert_first_webp_image_to_png();

        wp_send_json_success(__('The first WebP image of each product has been converted to PNG.', 'smarty-google-feed-generator'));
    }
    add_action('wp_ajax_smarty_convert_images', 'smarty_gfg_handle_ajax_convert_images');
}

if (!function_exists('smarty_gfg_convert_first_webp_image_to_png')) {
    /**
     * Convert the first WebP image of each product to PNG.
     *
     * This function processes all WooCommerce products to find their featured images.
     * If a featured image is in WebP format, it converts it to PNG format and updates
     * the product metadata to reflect the new image.
     *
     * @return void
     */
    function smarty_gfg_convert_first_webp_image_to_png() {
        $products = wc_get_products(array(
            'status' => 'publish',
            'limit'  => -1,
        ));

        foreach ($products as $product) {
            $image_id = $product->get_image_id();
            $file_path = get_attached_file($image_id);

            if ($file_path && preg_match('/\.webp$/', $file_path)) {
                $new_file_path = preg_replace('/\.webp$/', '.png', $file_path);

                if (smarty_gfg_convert_webp_to_png($file_path, $new_file_path)) {
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
    }
}

if (!function_exists('smarty_gfg_handle_ajax_generate_feed')) {
    /**
     * Handle AJAX requests to generate feeds.
     *
     * This function processes AJAX requests to generate different types of feeds
     * including the product feed, reviews feed, and CSV export. It validates the
     * nonce and user permissions before executing the requested action.
     *
     * @return void
     */
    function smarty_gfg_handle_ajax_generate_feed() {
        check_ajax_referer('smarty_feed_generator_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('You do not have sufficient permissions to access this page.');
        }

        $action = sanitize_text_field($_POST['feed_action']);
        switch ($action) {
            case 'generate_google_feed':
                smarty_gfg_generate_google_feed();
                wp_send_json_success('Product feed generated successfully.');
                break;
            case 'generate_google_reviews_feed':
                smarty_gfg_generate_google_reviews_feed();
                wp_send_json_success('Reviews feed generated successfully.');
                break;
            case 'generate_csv_export':
                smarty_gfg_generate_csv_export();
                wp_send_json_success('CSV export generated successfully.');
                break;
            case 'generate_bing_feed':
                smarty_gfg_generate_bing_feed();
                wp_send_json_success('Bing feed generated successfully.');
                break;
            default:
                wp_send_json_error('Invalid action.');
                break;
        }
    }
    add_action('wp_ajax_smarty_generate_feed', 'smarty_gfg_handle_ajax_generate_feed');
}

if (!function_exists('smarty_gfg_settings_page_content')) {
    /**
     * Render the settings page HTML.
     *
     * This function generates the HTML output for the settings page of the
     * Google Feed Generator plugin in the WordPress admin area.
     * It includes form fields for all registered settings and sections.
     *
     * @return void
     */
    function smarty_gfg_settings_page_content() {
        // Check user capabilities
        if (!current_user_can('manage_options')) {
            return;
        }

        // HTML
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Google Feed Generator | Settings', 'smarty-google-feed-generator'); ?></h1>
            <div id="smarty-gfg-settings-container">
                <div>
                    <form action="options.php" method="post">
                        <?php
                        settings_fields('smarty_feed_generator_settings');
                        do_settings_sections('smarty_feed_generator_settings');
                        submit_button(__('Save Settings', 'smarty-google-feed-generator'));
                        ?>
                    </form>
                </div>
                <div id="smarty-gfg-tabs-container">
                    <div>
                        <h2 class="smarty-gfg-nav-tab-wrapper">
                            <a href="#smarty-gfg-documentation" class="smarty-gfg-nav-tab smarty-gfg-nav-tab-active"><?php esc_html_e('Documentation', 'smarty-google-feed-generator'); ?></a>
                            <a href="#smarty-gfg-changelog" class="smarty-gfg-nav-tab"><?php esc_html_e('Changelog', 'smarty-google-feed-generator'); ?></a>
                        </h2>
                        <div id="smarty-gfg-documentation" class="smarty-gfg-tab-content active">
                            <div class="smarty-gfg-view-more-container">
                                <p><?php esc_html_e('Click "View More" to load the plugin documentation.', 'smarty-google-feed-generator'); ?></p>
                                <button id="smarty-gfg-load-readme-btn" class="button button-primary">
                                    <?php esc_html_e('View More', 'smarty-google-feed-generator'); ?>
                                </button>
                            </div>
                            <div id="smarty-gfg-readme-content" style="margin-top: 20px;"></div>
                        </div>
                        <div id="smarty-gfg-changelog" class="smarty-gfg-tab-content">
                            <div class="smarty-gfg-view-more-container">
                                <p><?php esc_html_e('Click "View More" to load the plugin changelog.', 'smarty-google-feed-generator'); ?></p>
                                <button id="smarty-gfg-load-changelog-btn" class="button button-primary">
                                    <?php esc_html_e('View More', 'smarty-google-feed-generator'); ?>
                                </button>
                            </div>
                            <div id="smarty-gfg-changelog-content" style="margin-top: 20px;"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
}

if (!function_exists('smarty_gfg_load_readme')) {
    /**
     * AJAX handler to load and parse the README.md content.
     */
    function smarty_gfg_load_readme() {
        check_ajax_referer('smarty_feed_generator_nonce', 'nonce');
    
        if (!current_user_can('manage_options')) {
            wp_send_json_error('You do not have sufficient permissions.');
        }
    
        $readme_path = plugin_dir_path(__FILE__) . 'README.md';
        if (file_exists($readme_path)) {
            // Include Parsedown library
            if (!class_exists('Parsedown')) {
                require_once plugin_dir_path(__FILE__) . 'libs/Parsedown.php';
            }
    
            $parsedown = new Parsedown();
            $markdown_content = file_get_contents($readme_path);
            $html_content = $parsedown->text($markdown_content);
    
            // Remove <img> tags from the content
            $html_content = preg_replace('/<img[^>]*>/', '', $html_content);
    
            wp_send_json_success($html_content);
        } else {
            wp_send_json_error('README.md file not found.');
        }
    }    
    add_action('wp_ajax_smarty_gfg_load_readme', 'smarty_gfg_load_readme');
}

if (!function_exists('smarty_gfg_load_changelog')) {
    /**
     * AJAX handler to load and parse the CHANGELOG.md content.
     */
    function smarty_gfg_load_changelog() {
        check_ajax_referer('smarty_feed_generator_nonce', 'nonce');
    
        if (!current_user_can('manage_options')) {
            wp_send_json_error('You do not have sufficient permissions.');
        }
    
        $changelog_path = plugin_dir_path(__FILE__) . 'CHANGELOG.md';
        if (file_exists($changelog_path)) {
            if (!class_exists('Parsedown')) {
                require_once plugin_dir_path(__FILE__) . 'libs/Parsedown.php';
            }
    
            $parsedown = new Parsedown();
            $markdown_content = file_get_contents($changelog_path);
            $html_content = $parsedown->text($markdown_content);
    
            wp_send_json_success($html_content);
        } else {
            wp_send_json_error('CHANGELOG.md file not found.');
        }
    }
    add_action('wp_ajax_smarty_gfg_load_changelog', 'smarty_gfg_load_changelog');
}

if (!function_exists('smarty_gfg_cleaned_google_product_category')) {
	/**
     * Retrieves the Google Product Category based on the plugin settings.
     *
     * This function fetches the Google Product Category as stored in the plugin settings.
     * If the "Use Google Category ID" option is checked, it returns the category ID.
     * Otherwise, it returns the category name, removing any preceding ID and hyphen.
     *
     * @return string The cleaned Google Product Category name or ID.
     */
    function smarty_gfg_cleaned_google_product_category() {
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
}

if (!function_exists('smarty_gfg_sanitize_category_values')) {
    /**
     * Function to sanitize the plugin settings on save.
     */
    function smarty_gfg_sanitize_category_values($input) {
        // Check if the input is an array
        if (is_array($input)) {
            return array_map('sanitize_text_field', array_map('trim', $input));
        } else {
            // If the input is a string, split it by commas, trim each value, and return as a comma-separated string
            $values = explode(',', $input);
            $values = array_map('sanitize_text_field', array_map('trim', $values));
            return implode(', ', $values);
        }
    }  
}

if (!function_exists('smarty_gfg_display_category_values_field')) {
    /**
     * Display the settings field with the values properly trimmed.
     */
    function smarty_gfg_display_category_values_field() {
        $category_values = get_option('smarty_custom_label_3_category_value', '');
        if (!is_array($category_values)) {
            $category_values = explode(',', $category_values);
        }
        $category_values = array_map('trim', $category_values);
        $category_values = implode(', ', $category_values);

        echo '<input type="text" id="smarty_custom_label_3_category_value" name="smarty_custom_label_3_category_value" value="' . esc_attr($category_values) . '" class="regular-text" />';
        echo '<p class="description">Enter custom values for the categories separated by commas. <strong>Example:</strong> Tech, Apparel, Literature</p>';
        echo '<p class="description"><strong>Important:</strong> Ensure these values are in the same order as the selected categories.</p>';
    }
    // Add the display callback when registering the setting field
    add_action('admin_init', function() {
        register_setting('smarty_settings', 'smarty_custom_label_3_category_value', [
            'sanitize_callback' => 'smarty_gfg_sanitize_category_values'
        ]);

        add_settings_field(
            'smarty_custom_label_3_category_value',
            __('Category Value', 'smarty'),
            'smarty_display_category_values_field',
            'smarty-settings-page',
            'smarty_settings_section'
        );
    });
}

if (!function_exists('smarty_gfg_get_custom_label_0')) {
    /**
     * Custom Label 0: Older Than X Days & Not Older Than Y Days
     */ 
    function smarty_gfg_get_custom_label_0($product) {
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
}

if (!function_exists('smarty_gfg_get_custom_label_1')) {
    /**
     * Custom Label 1: Most Ordered in Last Z Days 
     */ 
    function smarty_gfg_get_custom_label_1($product) {
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
}

if (!function_exists('smarty_gfg_get_custom_label_2')) {
    /**
     * Custom Label 2: High Rating
     */
    function smarty_gfg_get_custom_label_2($product) {
        $average_rating = $product->get_average_rating();
        $high_rating_value = get_option('smarty_custom_label_2_high_rating_value', 'high_rating');
        if ($average_rating >= 4) {
            return $high_rating_value;
        }
        return '';
    }
}

if (!function_exists('smarty_gfg_get_custom_label_3')) {
    /**
     * Custom Label 3: In Selected Category
     */
    function smarty_gfg_get_custom_label_3($product) {
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
}

if (!function_exists('smarty_gfg_get_custom_label_4')) {
    /**
     * Custom Label 4: Has Sale Price
     */
    function smarty_gfg_get_custom_label_4($product) {
        $excluded_categories = get_option('smarty_excluded_categories', array()); // Get excluded categories from settings
        $product_categories = wp_get_post_terms($product->get_id(), 'product_cat', array('fields' => 'ids'));

        if (!is_array($excluded_categories)) {
            $excluded_categories = explode(',', $excluded_categories); // Convert string to array if necessary
        }
    
        $product_categories = wp_get_post_terms($product->get_id(), 'product_cat', array('fields' => 'ids'));
    
        // Ensure $product_categories is an array to avoid type errors
        if (!is_array($product_categories)) {
            $product_categories = [];
        }

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
}

if (!function_exists('smarty_gfg_evaluate_criteria')) {
    /**
     * Evaluate product criteria and return the corresponding label.
     *
     * This function evaluates a set of criteria against a product's attributes
     * and returns a label if a match is found. The criteria should be provided as a JSON string
     * with each criterion containing an `attribute`, `value`, and `label`.
     *
     * Example criteria JSON:
     * [
     *     {
     *         "attribute": "custom_field_name",
     *         "value": "desired_value",
     *         "label": "Label to Return"
     *     }
     * ]
     *
     * @param WC_Product $product The WooCommerce product object.
     * @param string $criteria JSON string containing the criteria to evaluate.
     * @return string The label of the matching criterion, or an empty string if no match is found.
     */
    function smarty_gfg_evaluate_criteria($product, $criteria) {
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
