<?php

/**
 * The Google Products Feed-public specific functionality of the plugin.
 *
 * @link       https://smartystudio.net
 * @since      1.0.0
 *
 * @package    smarty_google_products_feed_Generator
 * @subpackage smarty_google_products_feed_Generator/admin/tabs
 * @author     Smarty Studio | Martin Nestorov
 */
class Smarty_Gfg_Google_Products_Feed_Public {

    /**
     * Generates the custom Google product feed.
     * 
     * This function is designed to generate a custom Google product feed for WooCommerce products. 
     * It uses WordPress and WooCommerce functions to build an XML feed that conforms to Google's specifications for product feeds.
     * 
     * @since    1.0.0
     */
    public function generate_google_products_feed() {
        // Add log entries
        Smarty_Gfg_Activity_Logging::add_activity_log('Generated Google Products Feed');

        // Start output buffering to prevent any unwanted output
        ob_start();

        // Set the content type to XML for the output
        header('Content-Type: application/xml; charset=utf-8');

        // Check if the clear cache option is enabled
        if (get_option('smarty_clear_cache')) {
            delete_transient('smarty_google_products_feed');
        }

        // Attempt to retrieve the cached version of the feed
        $cached_feed = get_transient('smarty_google_products_feed');
        if ($cached_feed !== false) {
            echo $cached_feed; // Output the cached feed and stop processing if it exists
            ob_end_flush();
            exit;
        }

        // Check if WooCommerce is active before proceeding
        if (class_exists('WooCommerce')) {
            // Get excluded categories and columns from settings
            $excluded_categories = get_option('smarty_excluded_categories', array());
            $excluded_columns = get_option('smarty_exclude_xml_columns', array());
            //_gfg_write_logs('Excluded Categories: ' . print_r($excluded_categories, true));

            // Check if including product variations is enabled
            $include_variations = get_option('smarty_include_product_variations', 'no') === 'yes';

            // Set up arguments for querying products, excluding certain categories
            $args = array(
                'status'       => 'publish',              // Only fetch published products
                'stock_status' => 'instock',              // Only fetch products that are in stock
                'limit'        => -1,                     // Fetch all products that match criteria
                'orderby'      => 'date',                 // Order by date
                'order'        => 'DESC',                 // In descending order
                'type'          => $include_variations ? ['simple', 'variable'] : ['simple'], // Include both simple and variable products if enabled
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
            //_gfg_write_logs('Product Query Args: ' . print_r($args, true));
            //_gfg_write_logs('Products: ' . print_r($products, true));

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
                //_gfg_write_logs('Processing Product: ' . print_r($product->get_data(), true));
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
                        $this->add_google_product_details($dom, $item, $product, $variation, $excluded_columns);
                    }
                } else {
                    // Process simple products similarly
                    $item = $dom->createElement('item');
                    $feed->appendChild($item);

                    // Convert the first WebP image to PNG if needed
                    Smarty_Gfg_Admin::convert_first_webp_image_to_png($product);

                    // Add product details as child nodes
                    $this->add_google_product_details($dom, $item, $product, null, $excluded_columns);
                }
            }

            // Save and output the XML
            $feed_content = $dom->saveXML();
            //_gfg_write_logs('Feed Content: ' . $feed_content);

            if ($feed_content) {
                $cache_duration = get_option('smarty_cache_duration', 12); // Default to 12 hours if not set
                set_transient('smarty_google_products_feed', $feed_content, $cache_duration * HOUR_IN_SECONDS);

                echo $feed_content;
                ob_end_flush();
                exit; // Ensure the script stops here to prevent further output that could corrupt the feed
            } else {
                ob_end_clean();
                //_gfg_write_logs('Failed to generate feed content.');
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
	public function add_google_product_details($dom, $item, $product, $variation = null, $excluded_columns = array()) {
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
        $item->appendChild($dom->createElementNS($gNamespace, 'g:mpn', $sku));
        $item->appendChild($dom->createElementNS($gNamespace, 'title', htmlspecialchars($product->get_name())));
        
        // Add description, using meta description if available or fallback to short description
        $meta_description = get_post_meta($product->get_id(), get_option('smarty_meta_description_field', 'meta-description'), true);
        $description = !empty($meta_description) ? $meta_description : $product->get_short_description();
        $item->appendChild($dom->createElementNS($gNamespace, 'description', htmlspecialchars(strip_tags($description))));
		
        // Add product categories and category mapping
        $categories = wp_get_post_terms($product->get_id(), 'product_cat');
        $category_mapping = get_option('smarty_category_mapping', array());
        $mapped_category = '';

        if (!empty($categories) && !is_wp_error($categories)) {
            foreach ($categories as $category) {
                if (isset($category_mapping[$category->term_id])) {
                    $mapped_category = $category_mapping[$category->term_id];
                    break;
                }
            }
            
            if (!empty($mapped_category)) {
                $item->appendChild($dom->createElementNS($gNamespace, 'g:product_type', htmlspecialchars($mapped_category)));
            } else {
                $category_names = array_map(function($term) { return $term->name; }, $categories);
                $item->appendChild($dom->createElementNS($gNamespace, 'g:product_type', htmlspecialchars(join(' > ', $category_names))));
            }
        }

        // Add product link
        $item->appendChild($dom->createElementNS($gNamespace, 'link', get_permalink($product->get_id())));

		// Add image link
        $item->appendChild($dom->createElementNS($gNamespace, 'g:image_link', wp_get_attachment_url($image_id)));

		// Add additional images
        $gallery_ids = $product->get_gallery_image_ids();
        foreach ($gallery_ids as $gallery_id) {
            $item->appendChild($dom->createElementNS($gNamespace, 'g:additional_image_link', wp_get_attachment_url($gallery_id)));
        }

        // Add Google product categories
        $google_product_category = self::get_cleaned_google_product_category();
        if ($google_product_category) {
            $item->appendChild($dom->createElementNS($gNamespace, 'g:google_product_category', htmlspecialchars($google_product_category)));
        }

		// Add price and sale price details
        $item->appendChild($dom->createElementNS($gNamespace, 'g:price', htmlspecialchars($price . ' ' . get_woocommerce_currency())));

        if ($is_on_sale) {
            $item->appendChild($dom->createElementNS($gNamespace, 'g:sale_price', htmlspecialchars($sale_price . ' ' . get_woocommerce_currency())));
        }

        // Check if the product has the "bundle" tag
        $is_bundle = 'no';
        $product_tags = wp_get_post_terms($product->get_id(), 'product_tag', array('fields' => 'slugs'));
        if (in_array('bundle', $product_tags)) {
            $is_bundle = 'yes';
        }
        $item->appendChild($dom->createElementNS($gNamespace, 'g:is_bundle', $is_bundle));
		
        // Add brand
        $brand = get_bloginfo('name'); // Use the site name as the brand
        $item->appendChild($dom->createElementNS($gNamespace, 'g:brand', htmlspecialchars($brand)));
		
        // Use the condition value from the settings
        $condition = get_option('smarty_condition', 'new');
        $item->appendChild($dom->createElementNS($gNamespace, 'g:condition', htmlspecialchars($condition)));

        // Add multipack
		if (!in_array('Multipack', $excluded_columns)) {
            $multipack = '';
            $item->appendChild($dom->createElementNS($gNamespace, 'g:multipack', $multipack));
        }

        // Add color
		if (!in_array('Color', $excluded_columns)) {
            $color = '';
            $item->appendChild($dom->createElementNS($gNamespace, 'g:color', $color));
        }

        // Add gender
		if (!in_array('Gender', $excluded_columns)) {
            $gender = '';
            $item->appendChild($dom->createElementNS($gNamespace, 'g:gender', $gender));
        }

        // Add material
        if (!in_array('Material', $excluded_columns)) {
            $material = '';
            $item->appendChild($dom->createElementNS($gNamespace, 'g:material', $material));
        }

        // Add size
        if (!in_array('Size', $excluded_columns)) {
            $size = '';
            $item->appendChild($dom->createElementNS($gNamespace, 'g:size', $size));
        }

        // Add size type
        if (!in_array('Size Type', $excluded_columns)) {
            $size_type = '';
            $item->appendChild($dom->createElementNS($gNamespace, 'g:size_type', $size_type));
        }

        // Add size system
        if (!in_array('Size System', $excluded_columns)) {
            $size_system = get_option('smarty_size_system', '');
            if (!empty($size_system)) {
                $item->appendChild($dom->createElementNS($gNamespace, 'g:size_system', htmlspecialchars($size_system)));
            }
        }
		
		// Add availability
        $availability = $is_in_stock ? 'in_stock' : 'out_of_stock';
        $item->appendChild($dom->createElementNS($gNamespace, 'g:availability', $availability));

		// Add custom labels
		if (!in_array('Custom Label 0', $excluded_columns)) {
            $item->appendChild($dom->createElementNS($gNamespace, 'g:custom_label_0', Smarty_Gfg_Public::get_custom_label($product, 'custom_label_0')));
        }

        if (!in_array('Custom Label 1', $excluded_columns)) {
            $item->appendChild($dom->createElementNS($gNamespace, 'g:custom_label_1', Smarty_Gfg_Public::get_custom_label($product, 'custom_label_1')));
        }

        if (!in_array('Custom Label 2', $excluded_columns)) {
            $item->appendChild($dom->createElementNS($gNamespace, 'g:custom_label_2', Smarty_Gfg_Public::get_custom_label($product, 'custom_label_2')));
        }

        if (!in_array('Custom Label 3', $excluded_columns)) {
            $item->appendChild($dom->createElementNS($gNamespace, 'g:custom_label_3', Smarty_Gfg_Public::get_custom_label($product, 'custom_label_3')));
        }
        
        if (!in_array('Custom Label 4', $excluded_columns)) {
            $item->appendChild($dom->createElementNS($gNamespace, 'g:custom_label_4', Smarty_Gfg_Public::get_custom_label($product, 'custom_label_4')));
        }

        // Add excluded destinations
        if (!in_array('Excluded Destination', $excluded_columns)) {
            $excluded_destinations = get_option('smarty_excluded_destination', []);
            foreach ($excluded_destinations as $excluded_destination) {
                $item->appendChild($dom->createElementNS($gNamespace, 'g:excluded_destination', htmlspecialchars($excluded_destination)));
            }
        }

        // Add included destinations
        if (!in_array('Included Destination', $excluded_columns)) {
            $included_destinations = get_option('smarty_included_destination', []);
            foreach ($included_destinations as $included_destination) {
                $item->appendChild($dom->createElementNS($gNamespace, 'g:included_destination', htmlspecialchars($included_destination)));
            }
        }

        // Add excluded countries for shopping ads
        if (!in_array('Excluded Countries for Shopping Ads', $excluded_columns)) {
            $shopping_ads_excluded_country = get_option('smarty_excluded_countries_for_shopping_ads', '');
            if (!empty($shopping_ads_excluded_country)) {
                $countries = explode(',', $shopping_ads_excluded_country);
                foreach ($countries as $country) {
                    $item->appendChild($dom->createElementNS($gNamespace, 'g:shopping_ads_excluded_country', htmlspecialchars(trim($country))));
                }
            }
        }

        // Add shipping details
        if (!in_array('Shipping', $excluded_columns)) {
            $shipping_cost = Smarty_Gfg_Public::get_shipping_cost();
            if ($shipping_cost !== false) {
                $shipping_element = $dom->createElementNS($gNamespace, 'g:shipping');
                $shipping_price = $dom->createElementNS($gNamespace, 'g:price', $shipping_cost . ' ' . get_woocommerce_currency());
    
                $shipping_country = $dom->createElementNS($gNamespace, 'g:country', WC()->countries->get_base_country()); // Get the store's base country
                $shipping_service = $dom->createElementNS($gNamespace, 'g:service', 'Flat Rate'); // Set shipping service
    
                $shipping_element->appendChild($shipping_price);
                $shipping_element->appendChild($shipping_country);
                $shipping_element->appendChild($shipping_service);
    
                $item->appendChild($shipping_element);
            }
        }
	}

    /**
     * Generates a CSV file export of products for Google Merchant Center or similar services.
     * 
     * This function handles generating a downloadable CSV file that includes details about products
     * filtered based on certain criteria such as stock status and product category.
     * 
     * @since    1.0.0
     */
    public function generate_google_csv_export() {
        // Add log entries
        Smarty_Gfg_Activity_Logging::add_activity_log('Generating CSV Export File');

        // Set headers to force download and define the file name
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="woocommerce-products.csv"');

        // Open PHP output stream as a writable file
        $handle = fopen('php://output', 'w');
        
        // Check if the file handle is valid
        if ($handle === false) {
            wp_die('Failed to open output stream for CSV export'); // Kill the script and display message if handle is invalid
        }

        // Get excluded columns from settings
        $excluded_columns = get_option('smarty_exclude_csv_columns', array());

        // Define the columns and map headers based on user settings
        $csv_columns = array(
            'ID', 'MPN', 'GTIN', 'Title', 'Description', 'Product Type', 'Link', 'Mobile Link', 'Image Link',
            'Additional Image Link', 'Google Product Category', 'Price', 'Sale Price', 'Bundle', 'Brand',
            'Condition', 'Multipack', 'Color', 'Gender', 'Material', 'Size', 'Size Type', 'Size System',
            'Availability', 'Custom Label 0', 'Custom Label 1', 'Custom Label 2', 'Custom Label 3', 
            'Custom Label 4', 'Excluded Destination', 'Included Destination', 'Excluded Countries for Shopping Ads', 'Shipping'
        );

        // Create the header row for the CSV
        $headers = array();
        foreach ($csv_columns as $column) {
            if (!in_array($column, $excluded_columns)) {
                $headers[] = $column;
            }
        }

        // Write the header row to the CSV file
        fputcsv($handle, $headers);
        //_gfg_write_logs('CSV Headers: ' . print_r($headers, true));

        // Get excluded categories from settings
        $excluded_categories = get_option('smarty_excluded_categories', array());
        //_gfg_write_logs('Excluded Categories: ' . print_r($excluded_categories, true));

        // Check if including product variations is enabled
        $include_variations = get_option('smarty_include_product_variations', 'no') === 'yes';

        // Prepare arguments for querying products excluding specific categories
        $args = array(
            'status'        => 'publish', // Only fetch published products
            'stock_status'  => 'instock', // Only fetch products that are in stock
            'limit'         => -1,        // Fetch all products that match criteria
            'orderby'       => 'date',    // Order by date
            'order'         => 'DESC',    // In descending order
            'type'          => $include_variations ? ['simple', 'variable'] : ['simple'], // Include both simple and variable products if enabled
            'tax_query'     => array(
                array(
                    'taxonomy'  => 'product_cat',
                    'field'     => 'term_id',
                    'terms'     => $excluded_categories,
                    'operator'  => 'NOT IN', // Exclude products from these categories
                ),
            ),
        );

        // Retrieve products using the defined arguments
        $products = wc_get_products($args);
        //_gfg_write_logs('Product Query Args: ' . print_r($args, true));
        //_gfg_write_logs('Products: ' . count($products) . ' products fetched');

        // Get exclude patterns from settings and split into array
        $exclude_patterns_raw = get_option('smarty_exclude_patterns');
        $exclude_patterns = !empty($exclude_patterns_raw) ? preg_split('/\r\n|\r|\n/', $exclude_patterns_raw) : array();

        // Get category mappings from settings
        $category_mapping = get_option('smarty_category_mapping', array());

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
            Smarty_Gfg_Admin::convert_first_webp_image_to_png($product);

            // Prepare product data for the CSV
            $id = $product->get_id();
            $regular_price = $product->get_regular_price();
            $sale_price = $product->get_sale_price() ?: ''; // Fallback to empty string if no sale price

            // Check if category mappings exist for the product categories
            $product_categories = wp_get_post_terms($id, 'product_cat', array('fields' => 'ids'));
            $mapped_category = '';
            foreach ($product_categories as $cat_id) {
                if (isset($category_mapping[$cat_id]) && !empty($category_mapping[$cat_id])) {
                    //_gfg_write_logs('Category Mappings: ' . print_r($category_mapping, true));
                    $mapped_category = $category_mapping[$cat_id];
                    break;
                }
            }

            // Use mapped category if found, otherwise use product categories
            if (!empty($mapped_category)) {
                $google_product_category_mapped = $mapped_category;
            } else {
                $categories = wp_get_post_terms($id, 'product_cat', array('fields' => 'names'));
                $categories = !empty($categories) ? implode(', ', $categories) : '';
                $google_product_category_mapped = $categories;
            }

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
            $google_product_category = self::get_cleaned_google_product_category(); // Get Google category from plugin settings
            //_gfg_write_logs('Google Product Category: ' . $google_product_category); // Debugging line
            if ($google_category_as_id) {
                $google_product_category = explode('-', $google_product_category)[0]; // Get only the ID part
                //_gfg_write_logs('Google Product Category ID: ' . $google_product_category); // Debugging line
            }

            // Check if the product has the "bundle" tag
            $is_bundle = 'no';
            $product_tags = wp_get_post_terms($product->get_id(), 'product_tag', array('fields' => 'slugs'));
            if (in_array('bundle', $product_tags)) {
                $is_bundle = 'yes';
            }

            $brand = get_bloginfo('name');

            // Use the condition value from the settings
            $condition = get_option('smarty_condition', 'new');

            // Use the size system value from the settings
            $size_system = get_option('smarty_size_system', '');

            // Custom Labels
            $custom_label_0 = Smarty_Gfg_Public::get_custom_label($product, 'custom_label_0');
            $custom_label_1 = Smarty_Gfg_Public::get_custom_label($product, 'custom_label_1');
            $custom_label_2 = Smarty_Gfg_Public::get_custom_label($product, 'custom_label_2');
            $custom_label_3 = Smarty_Gfg_Public::get_custom_label($product, 'custom_label_3');
            $custom_label_4 = Smarty_Gfg_Public::get_custom_label($product, 'custom_label_4');

            $excluded_destinations = get_option('smarty_excluded_destination', []);
            $included_destinations = get_option('smarty_included_destination', []);
            $shopping_ads_excluded_country = get_option('smarty_excluded_countries_for_shopping_ads', '');

            // Get shipping cost
            $shipping_cost = Smarty_Gfg_Public::get_shipping_cost();
            $base_country = WC()->countries->get_base_country(); // Get the store's base country

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
                    
                    // Prepare row data excluding the columns that should be hidden
                    $row = array();
                    foreach ($csv_columns as $column) {
                        if (!in_array($column, $excluded_columns)) {
                            switch ($column) {
                                case 'ID':
                                    $row[] = $id;
                                    break;
                                case 'MPN':
                                    $row[] = $sku;
                                    break;
                                case 'GTIN':
                                    $row[] = '';
                                case 'Title':
                                    $row[] = $name;
                                    break;
                                case 'Description':
                                    $row[] = $description;
                                    break;
                                case 'Product Type':
                                    $row[] = $google_product_category_mapped;
                                    break;
                                case 'Link':
                                case 'Mobile Link':
                                    $row[] = $product_link;
                                    break;
                                case 'Image Link':
                                    $row[] = $image_link;
                                    break;
                                case 'Additional Image Link':
                                    $row[] = $additional_image_link;
                                    break;
                                case 'Google Product Category':
                                    $row[] = $google_product_category;
                                    break;
                                case 'Price':
                                    $row[] = $variation_price;
                                    break;
                                case 'Sale Price':
                                    $row[] = $variation_sale_price;
                                    break;
                                case 'Bundle':
                                    $row[] = $is_bundle;
                                    break;
                                case 'Brand':
                                    $row[] = $brand;
                                    break;
                                case 'Condition':
                                    $row[] = $condition;
                                    break;
                                case 'Multipack':
                                    $row[] = '';
                                    break;
                                case 'Color':
                                    $row[] = '';
                                    break;
                                case 'Gender':
                                    $row[] = '';
                                    break;
                                case 'Material':
                                    $row[] = '';
                                    break;
                                case 'Size':
                                    $row[] = '';
                                    break;
                                case 'Size Type':
                                    $row[] = '';
                                    break;
                                case 'Size System':
                                    $row[] = $size_system;
                                    break;
                                case 'Availability':
                                    $row[] = $availability;
                                    break;
                                case 'Custom Label 0':
                                    $row[] = $custom_label_0;
                                    break;
                                case 'Custom Label 1':
                                    $row[] = $custom_label_1;
                                    break;
                                case 'Custom Label 2':
                                    $row[] = $custom_label_2;
                                    break;
                                case 'Custom Label 3':
                                    $row[] = $custom_label_3;
                                    break;
                                case 'Custom Label 4':
                                    $row[] = $custom_label_4;
                                    break;
                                case 'Excluded Destination':
                                    $row[] = implode(', ', $excluded_destinations);
                                    break;
                                case 'Included Destination':
                                    $row[] = implode(', ', $included_destinations);
                                    break;
                                case 'Excluded Countries for Shopping Ads':
                                    $row[] = $shopping_ads_excluded_country;
                                    break;
                                case 'Shipping':
                                    $row[] = $shipping_cost !== false ? $shipping_cost . ' ' . get_woocommerce_currency() . ' (' . $base_country . ')' : '';
                                    break;
                                default:
                                    $row[] = ''; // Default case for any other columns
                            }
                        }
                    }
                    //_gfg_write_logs('Variable Product Row Data: ' . print_r($row, true));
                }
            } else {
                // Prepare row for a simple product
                $sku = $product->get_sku();
                $row = array();
                foreach ($csv_columns as $column) {
                    if (!in_array($column, $excluded_columns)) {
                        switch ($column) {
                            case 'ID':
                                $row[] = $id;
                                break;
                            case 'MPN':
                                $row[] = $sku;
                                break;
                            case 'GTIN':
                                $row[] = '';
                                break;
                            case 'Title':
                                $row[] = $name;
                                break;
                            case 'Description':
                                $row[] = $description;
                                break;
                            case 'Product Type':
                                $row[] = $google_product_category_mapped;
                                break;
                            case 'Link':
                            case 'Mobile Link':
                                $row[] = $product_link;
                                break;
                            case 'Image Link':
                                $row[] = $image_link;
                                break;
                            case 'Additional Image Link':
                                $row[] = $additional_image_link;
                                break;
                            case 'Google Product Category':
                                $row[] = $google_product_category;
                                break;
                            case 'Price':
                                $row[] = $regular_price;
                                break;
                            case 'Sale Price':
                                $row[] = $sale_price;
                                break;
                            case 'Bundle':
                                $row[] = $is_bundle;
                                break;
                            case 'Brand':
                                $row[] = $brand;
                                break;
                            case 'Condition':
                                $row[] = $condition;
                                break;
                            case 'Multipack':
                                $row[] = '';
                                break;
                            case 'Color':
                                $row[] = '';
                                break;
                            case 'Gender':
                                $row[] = '';
                                break;
                            case 'Material':
                                $row[] = '';
                                break;
                            case 'Size':
                                $row[] = '';
                                break;
                            case 'Size Type':
                                $row[] = '';
                                break;
                            case 'Size System':
                                $row[] = $size_system;
                                break;
                            case 'Availability':
                                $row[] = $availability;
                                break;
                            case 'Custom Label 0':
                                $row[] = $custom_label_0;
                                break;
                            case 'Custom Label 1':
                                $row[] = $custom_label_1;
                                break;
                            case 'Custom Label 2':
                                $row[] = $custom_label_2;
                                break;
                            case 'Custom Label 3':
                                $row[] = $custom_label_3;
                                break;
                            case 'Custom Label 4':
                                $row[] = $custom_label_4;
                                break;
                            case 'Excluded Destination':
                                $row[] = implode(', ', $excluded_destinations);
                                break;
                            case 'Included Destination':
                                $row[] = implode(', ', $included_destinations);
                                break;
                            case 'Excluded Countries for Shopping Ads':
                                $row[] = $shopping_ads_excluded_country;
                                break;
                            case 'Shipping':
                                $row[] = $shipping_cost !== false ? $shipping_cost . ' ' . get_woocommerce_currency() . ' (' . $base_country . ')' : '';
                                break;
                            default:
                                $row[] = ''; // Default case for any other columns
                        }
                    }
                }
                //_gfg_write_logs('Simple Product Row Data: ' . print_r($row, true));
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
     * Regenerates the feed and saves it to a transient or a file.
     * 
     * This function is designed to refresh the product feed by querying the latest product data from WooCommerce,
     * constructing an XML feed, and saving it either to a transient for fast access or directly to a file.
     * 
     * @since    1.0.0
     */
    public function regenerate_google_products_feed() {
        // Add log entries
        Smarty_Gfg_Activity_Logging::add_activity_log('Regenerated Google Products Feed');

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

        // Retrieve category mapping from settings
        $category_mapping = get_option('smarty_category_mapping', array());
    
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
                    $item->addChild('g:mpn', $variation->get_sku(), $gNamespace);
                    $item->addChild('title', htmlspecialchars($product->get_name()), $gNamespace);

                    // Description: Use main product description or fallback to the short description if empty
                    $description = $product->get_description();
                    if (empty($description)) {
                        $description = $product->get_short_description();
                    }
    
                    if (!empty($description)) {
                        // Ensure that HTML tags are removed and properly encoded
                        $item->addChild('description', htmlspecialchars(strip_tags($description)), $gNamespace);
                    } else {
                        $item->addChild('description', 'No description available', $gNamespace);
                    }

                    // Categories: Compile a list from the product's categories
                    $categories = wp_get_post_terms($product->get_id(), 'product_cat');
                    $mapped_category = '';
                    if (!empty($categories) && !is_wp_error($categories)) {
                        foreach ($categories as $category) {
                            if (isset($category_mapping[$category->term_id])) {
                                $mapped_category = $category_mapping[$category->term_id];
                                break;
                            }
                        }
                        
                        if (!empty($mapped_category)) {
                            $item->addChild('g:product_type', htmlspecialchars($mapped_category), $gNamespace);
                        } else {
                            $category_names = array_map(function($term) { return $term->name; }, $categories);
                            $item->addChild('g:product_type', htmlspecialchars(join(' > ', $category_names)), $gNamespace);
                        }
                    }

                    // Product link
                    $item->addChild('link', get_permalink($product->get_id()), $gNamespace);
                    
                    // Product images
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

                    // Custom labels
                    $item->addChild('g:custom_label_0', Smarty_Gfg_Public::get_custom_label($product, 'custom_label_0'), $gNamespace);
                    $item->addChild('g:custom_label_1', Smarty_Gfg_Public::get_custom_label($product, 'custom_label_1'), $gNamespace);
                    $item->addChild('g:custom_label_2', Smarty_Gfg_Public::get_custom_label($product, 'custom_label_2'), $gNamespace);
                    $item->addChild('g:custom_label_3', Smarty_Gfg_Public::get_custom_label($product, 'custom_label_3'), $gNamespace);
                    $item->addChild('g:custom_label_4', Smarty_Gfg_Public::get_custom_label($product, 'custom_label_4'), $gNamespace);

                    // Shipping
                    $shipping_cost = Smarty_Gfg_Public::get_shipping_cost();
                    if ($shipping_cost !== false) {
                        $shipping_element = $item->addChild('g:shipping');
                        $shipping_element->addChild('g:price', htmlspecialchars($shipping_cost . ' ' . get_woocommerce_currency()), $gNamespace);
    
                        $base_country = WC()->countries->get_base_country(); // Get the store's base country
                        $shipping_element->addChild('g:country', htmlspecialchars($base_country), $gNamespace);
    
                        $shipping_element->addChild('g:service', 'Flat Rate', $gNamespace);
                    }
                }
            } else {
                // Handle simple products
                $item = $xml->addChild('item');
                $gNamespace = 'http://base.google.com/ns/1.0';
                $item->addChild('g:id', $product->get_id(), $gNamespace);
                $item->addChild('g:mpn', $product->get_sku(), $gNamespace);
                $item->addChild('title', htmlspecialchars($product->get_name()), $gNamespace);
                
                // Description: Use main product description or fallback to the short description if empty
                $description = $product->get_description();
                if (empty($description)) {
                    $description = $product->get_short_description();
                }
    
                if (!empty($description)) {
                    // Ensure that HTML tags are removed and properly encoded
                    $item->addChild('description', htmlspecialchars(strip_tags($description)), $gNamespace);
                } else {
                    $item->addChild('description', 'No description available', $gNamespace);
                }

                // Categories: Compile a list from the product's categories
                $categories = wp_get_post_terms($product->get_id(), 'product_cat');
                if (!empty($categories) && !is_wp_error($categories)) {
                    $category_names = array_map(function($term) { return $term->name; }, $categories);
                    $item->addChild('g:product_type', htmlspecialchars(join(' > ', $category_names)), $gNamespace);
                }

                // Product link
                $item->addChild('link', get_permalink($product->get_id()), $gNamespace);
    
                // Product images
                $item->addChild('g:image_link', wp_get_attachment_url($product->get_image_id()), $gNamespace);
                $gallery_ids = $product->get_gallery_image_ids();
                foreach ($gallery_ids as $gallery_id) {
                    $item->addChild('g:additional_image_link', wp_get_attachment_url($gallery_id), $gNamespace);
                }
    
                // Pricing: Regular and sale prices
                $item->addChild('g:price', htmlspecialchars($product->get_price() . ' ' . get_woocommerce_currency()), $gNamespace);
                if ($product->is_on_sale() && !empty($product->get_sale_price())) {
                    $item->addChild('g:sale_price', htmlspecialchars($product->get_sale_price() . ' ' . get_woocommerce_currency()), $gNamespace);
                }
    
                // Custom labels
                $item->addChild('g:custom_label_0', Smarty_Gfg_Public::get_custom_label($product, 'custom_label_0'), $gNamespace);
                $item->addChild('g:custom_label_1', Smarty_Gfg_Public::get_custom_label($product, 'custom_label_1'), $gNamespace);
                $item->addChild('g:custom_label_2', Smarty_Gfg_Public::get_custom_label($product, 'custom_label_2'), $gNamespace);
                $item->addChild('g:custom_label_3', Smarty_Gfg_Public::get_custom_label($product, 'custom_label_3'), $gNamespace);
                $item->addChild('g:custom_label_4', Smarty_Gfg_Public::get_custom_label($product, 'custom_label_4'), $gNamespace);

                // Shipping
                $shipping_cost = Smarty_Gfg_Public::get_shipping_cost();
                if ($shipping_cost !== false) {
                    $shipping_element = $item->addChild('g:shipping');
                    $shipping_element->addChild('g:price', htmlspecialchars($shipping_cost . ' ' . get_woocommerce_currency()), $gNamespace);
    
                    $base_country = WC()->countries->get_base_country(); // Get the store's base country
                    $shipping_element->addChild('g:country', htmlspecialchars($base_country), $gNamespace);
    
                    $shipping_element->addChild('g:service', 'Flat Rate', $gNamespace);
                }
            }
        }
    
        // Save the generated XML content to a transient or a file for later use
        $feed_content = $xml->asXML();
        $cache_duration = get_option('smarty_cache_duration', 12); // Default to 12 hours if not set
        set_transient('smarty_google_products_feed', $feed_content, $cache_duration * HOUR_IN_SECONDS); // Cache the feed using WordPress transients
        file_put_contents(WP_CONTENT_DIR . '/uploads/smarty_google_products_feed.xml', $feed_content); // Optionally save the feed to a file in the WP uploads directory
    }

    /**
     * Invalidate cache or regenerate feed when a product is created, updated, or deleted.
     * 
     * TODO: We need to made this to run manually.
     * 
     * @since    1.0.0
     * @param int $product_id The ID of the product that has been created, updated, or deleted.
     */
    public function invalidate_google_products_feed_cache($product_id) {
        // Add log entries
        Smarty_Gfg_Activity_Logging::add_activity_log('Invalidate Google Products Feed');

        // Check if the post is a 'product'
        if (get_post_type($product_id) === 'product') {
            // Invalidate Google feed cache
            delete_transient('smarty_google_products_feed');

            // Regenerate the feeds immediately to update the feed file
            //$this->regenerate_google_products_feed();
        }
    }

    /**
     * Invalidates the cached Google product feed and optionally regenerates it upon the deletion of a product.
     * 
     * This function ensures that when a product is deleted from WooCommerce, any cached version of the feed
     * does not continue to include data related to the deleted product.
     * 
     * TODO: We need to made this to run manually.
     *
     * @since    1.0.0
     * @param int $post_id The ID of the post (product) being deleted.
     */
    public function invalidate_google_products_feed_cache_on_delete($post_id) {
        // Add log entries
        Smarty_Gfg_Activity_Logging::add_activity_log('Invalidate Google Products Feed on delete');

        // Check if the post being deleted is a product
        if (get_post_type($post_id) === 'product') {
            // Invalidate Google feed cache
            delete_transient('smarty_google_products_feed');
            
            // Regenerate the feeds immediately to update the feed file
            //$this->regenerate_google_products_feed();
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
    public static function get_cleaned_google_product_category() {
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
     * Handle the cron scheduling of the feed.
     * 
     * @since    1.0.0
     */
    public function schedule_google_products_feed_generation() {
        $interval = get_option('smarty_google_feed_interval', 'daily');
        _gfg_write_logs('Smarty_Gfg_Google_Products_Feed_Public: Google Products Feed Interval: ' . $interval);

        $timestamp = wp_next_scheduled('smarty_generate_google_products_feed');

        // Only reschedule if the interval has changed or the event is not scheduled
        if (!$timestamp || wp_get_schedule('smarty_generate_google_products_feed') !== $interval) {
            if ($timestamp) {
                wp_unschedule_event($timestamp, 'smarty_generate_google_products_feed');
                _gfg_write_logs('Smarty_Gfg_Google_Products_Feed_Public: Unscheduled existing Google Products Feed event.');
            }

            if ($interval !== 'no_refresh') {
                wp_schedule_event(time(), $interval, 'smarty_generate_google_products_feed');
                _gfg_write_logs('Smarty_Gfg_Google_Products_Feed_Public: Scheduled new Google Products Feed event.');
            }
        } else {
            _gfg_write_logs('Smarty_Gfg_Google_Products_Feed_Public: Google Products Feed event is already scheduled with the correct interval.');
        }
    }
}