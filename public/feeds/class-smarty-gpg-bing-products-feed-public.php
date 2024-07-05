<?php

/**
 * The Bing Products Feed-public specific functionality of the plugin.
 *
 * @link       https://smartystudio.net
 * @since      1.0.0
 *
 * @package    Smarty_Google_Feed_Generator
 * @subpackage Smarty_Google_Feed_Generator/admin/tabs
 * @author     Smarty Studio | Martin Nestorov
 */
class Smarty_Gfg_Bing_Products_Feed_Public {

   /**
     * Generates the Bing Shopping feed in XML or TXT format.
     *
     * @since    1.0.0
     * @param    string    $format     The format of the feed, either 'xml' or 'txt'.
     */
    public function gfg_generate_bing_products_feed($format = 'xml') {
        // Check if WooCommerce is active before proceeding
        if (!class_exists('WooCommerce')) {
            echo '<error>WooCommerce is not active.</error>';
            exit;
        }
    
        // Set headers and open the handle based on the format
        if ($format === 'xml') {
            // Add log entries
            Smarty_Gfg_Activity_Logging::gfg_add_activity_log('Generated Bing XML Feed');

            header('Content-Type: application/xml; charset=utf-8');
            $dom = new DOMDocument('1.0', 'UTF-8');
            $dom->formatOutput = true;
            $feed = $dom->createElement('feed');
            $dom->appendChild($feed);
        } else if ($format === 'txt') {
            // Add log entries
            Smarty_Gfg_Activity_Logging::gfg_add_activity_log('Generated Bing TXT Feed');

            header('Content-Type: text/plain; charset=utf-8');
            header('Content-Disposition: attachment; filename="bing-shopping-feed.txt"');
            $handle = fopen('php://output', 'w');
        } else {
            echo '<error>Invalid format specified.</error>';
            exit;
        }
    
        // Get excluded categories and columns from settings
        $excluded_categories = get_option('smarty_gfg_bing_excluded_categories', array());
        $excluded_columns = get_option('smarty_gfg_bing_exclude_xml_columns', array());

        // Check if including product variations is enabled
        $include_variations = get_option('smarty_gfg_bing_include_product_variations', 'no') === 'yes';
    
        // Set up arguments for querying products, excluding certain categories
        $args = array(
            'status'       => 'publish',
            'stock_status' => 'instock',
            'limit'        => -1,
            'orderby'      => 'date',
            'order'        => 'DESC',
            'type'         => $include_variations ? ['simple', 'variable'] : ['simple'], // Include both simple and variable products if enabled
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
    
        // Loop through each product to add details to the feed
        foreach ($products as $product) {
            if ($product->is_type('variable')) {
                // Get all variations if product is variable
                $variations = $product->get_children();
    
                if (!empty($variations)) {
                    foreach ($variations as $variation_id) {
                        $variation = wc_get_product($variation_id);
    
                        // Convert the first WebP image to PNG if needed
                        Smarty_Gfg_Admin::gfg_convert_first_webp_image_to_png($product);
    
                        // Add product details based on format
                        if ($format === 'xml') {
                            $item = $dom->createElement('item');
                            $feed->appendChild($item);
                            $this->gfg_add_bing_product_details($item, $product, $variation, $excluded_columns, 'xml');
                        } else if ($format === 'txt') {
                            $this->gfg_add_bing_product_details($handle, $product, $variation, $excluded_columns, 'txt');
                        }
                    }
                }
            } else {
                // Convert the first WebP image to PNG if needed
                Smarty_Gfg_Admin::gfg_convert_first_webp_image_to_png($product);
    
                // Add product details based on format
                if ($format === 'xml') {
                    $item = $dom->createElement('item');
                    $feed->appendChild($item);
                    $this->gfg_add_bing_product_details($item, $product, null, $excluded_columns, 'xml');
                } else if ($format === 'txt') {
                    $this->gfg_add_bing_product_details($handle, $product, null, $excluded_columns, 'txt');
                }
            }
        }
    
        // Output and clean up based on format
        if ($format === 'xml') {
            echo $dom->saveXML();
        } else if ($format === 'txt') {
            fclose($handle);
        }
    
        exit;
    }

    /**
     * Adds Bing product details to the feed item node.
     *
     * @since    1.0.0
     * @param mixed $output The DOMDocument instance for XML or file handle for TXT.
     * @param WC_Product $product The WooCommerce product instance.
     * @param WC_Product $variation Optional. The variation instance if the product is variable.
     * @param array $excluded_columns Columns to be excluded.
     * @param string $format The format of the feed, either 'xml' or 'txt'.
     */
    public function gfg_add_bing_product_details($output, $product, $variation = null, $excluded_columns = array(), $format = 'xml') {
        $data = [];
    
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
    
        $data['id'] = $id;
        
        $data['mpn'] = $sku;
        
        $data['title'] = htmlspecialchars($product->get_name());
        
        $meta_description = get_post_meta($product->get_id(), get_option('smarty_gfg_meta_description_field', 'meta-description'), true);
        $description = !empty($meta_description) ? $meta_description : $product->get_short_description();
        $data['description'] = htmlspecialchars(strip_tags($description));
        
        $google_product_category = Smarty_Gfg_Google_Products_Feed_Public::gfg_get_cleaned_google_product_category();
        if ($google_product_category) {
            $data['product_category'] = htmlspecialchars($google_product_category);
        }

        $categories = wp_get_post_terms($product->get_id(), 'product_cat');
        if (!empty($categories) && !is_wp_error($categories)) {
            $category_names = array_map(function($term) { return $term->name; }, $categories);
            $data['product_type'] = htmlspecialchars(join(' > ', $category_names));
        }

        $data['link'] = get_permalink($product->get_id());
        $data['image_link'] = wp_get_attachment_url($image_id);
    
        $gallery_ids = $product->get_gallery_image_ids();
        $additional_images = [];
        foreach ($gallery_ids as $gallery_id) {
            $additional_images[] = wp_get_attachment_url($gallery_id);
        }
        $data['additional_image_link'] = join(',', $additional_images);
    
        $data['price'] = htmlspecialchars($price . ' ' . get_woocommerce_currency());
    
        if ($is_on_sale) {
            $data['sale_price'] = htmlspecialchars($sale_price . ' ' . get_woocommerce_currency());
        }
    
        $brand = get_bloginfo('name');
        $data['brand'] = htmlspecialchars($brand);
    
        $condition = get_option('smarty_gfg_bing_condition', 'new');
        $data['condition'] = htmlspecialchars($condition);
    
        if (!in_array('Multipack', $excluded_columns)) {
            $data['multipack'] = '';
        }
    
        if (!in_array('Color', $excluded_columns)) {
            $data['color'] = '';
        }
    
        if (!in_array('Gender', $excluded_columns)) {
            $data['gender'] = '';
        }
    
        if (!in_array('Material', $excluded_columns)) {
            $data['material'] = '';
        }
    
        if (!in_array('Size', $excluded_columns)) {
            $data['size'] = '';
        }
    
        if (!in_array('Size Type', $excluded_columns)) {
            $data['size_type'] = '';
        }
    
        if (!in_array('Size System', $excluded_columns)) {
            $size_system = get_option('smarty_gfg_bing_size_system', '');
            if (!empty($size_system)) {
                $data['size_system'] = htmlspecialchars($size_system);
            }
        }
    
        $availability = $is_in_stock ? 'in stock' : 'out of stock';
        $data['availability'] = $availability;
    
        if (!in_array('Custom Label 0', $excluded_columns)) {
            $data['custom_label_0'] = Smarty_Gfg_Public::gfg_get_custom_label($product, 'custom_label_0');
        }
    
        if (!in_array('Custom Label 1', $excluded_columns)) {
            $data['custom_label_1'] = Smarty_Gfg_Public::gfg_get_custom_label($product, 'custom_label_1');
        }
    
        if (!in_array('Custom Label 2', $excluded_columns)) {
            $data['custom_label_2'] = Smarty_Gfg_Public::gfg_get_custom_label($product, 'custom_label_2');
        }
    
        if (!in_array('Custom Label 3', $excluded_columns)) {
            $data['custom_label_3'] = Smarty_Gfg_Public::gfg_get_custom_label($product, 'custom_label_3');
        }
    
        if (!in_array('Custom Label 4', $excluded_columns)) {
            $data['custom_label_4'] = Smarty_Gfg_Public::gfg_get_custom_label($product, 'custom_label_4');
        }

        $brand = get_bloginfo('name');
        $data['seller_name'] = htmlspecialchars($brand);

        if (!in_array('Excluded Countries for Shopping Ads', $excluded_columns)) {
            $shopping_ads_excluded_country = get_option('smarty_gfg_bing_shopping_ads_excluded_country', '');
            if (!empty($shopping_ads_excluded_country)) {
                $countries = explode(',', $shopping_ads_excluded_country);
                foreach ($countries as $country) {
                    $data['shopping_ads_excluded_country'][] = htmlspecialchars(trim($country));
                }
            }
        }
    
        if (!in_array('Shipping', $excluded_columns)) {
            $shipping_cost = Smarty_Gfg_Public::gfg_get_shipping_cost();
            if ($shipping_cost !== false) {
                $shipping_data = [
                    'price' => $shipping_cost . ' ' . get_woocommerce_currency(),
                    'country' => WC()->countries->get_base_country(),
                    'service' => 'Flat Rate'
                ];
                $data['shipping'] = $shipping_data;
            }
        }
    
        // Output the data based on the format
        if ($format === 'xml') {
            $item = $output->ownerDocument->createElement('item');
            foreach ($data as $key => $value) {
                if ($key === 'shipping' && isset($value['price'], $value['country'], $value['service'])) {
                    $shipping_element = $output->ownerDocument->createElement('shipping');
                    $shipping_price = $output->ownerDocument->createElement('price', $value['price']);
                    $shipping_country = $output->ownerDocument->createElement('country', $value['country']);
                    $shipping_service = $output->ownerDocument->createElement('service', $value['service']);
                    $shipping_element->appendChild($shipping_price);
                    $shipping_element->appendChild($shipping_country);
                    $shipping_element->appendChild($shipping_service);
                    $item->appendChild($shipping_element);
                } elseif ($key === 'shopping_ads_excluded_country') {
                    foreach ($value as $country) {
                        $item->appendChild($output->ownerDocument->createElement($key, $country));
                    }
                } else {
                    $item->appendChild($output->ownerDocument->createElement($key, $value));
                }
            }
            $output->appendChild($item);
        } else if ($format === 'txt') {
            $row = [];
            foreach ($data as $key => $value) {
                if ($key === 'shipping' && is_array($value)) {
                    $row[] = implode('|', $value);
                } elseif ($key === 'shopping_ads_excluded_country') {
                    $row[] = implode(',', $value);
                } else {
                    $row[] = $value;
                }
            }
            fputcsv($output, $row, "\t");
        }
    }

    /**
     * Regenerates the Bing feed and saves it to a transient or a file.
     * 
     * This function is designed to refresh the Bing product feed by querying the latest product data from WooCommerce,
     * constructing an XML feed, and saving it either to a transient for fast access or directly to a file.
     * 
     * @since    1.0.0
     */
    public static function gfg_regenerate_bing_products_feed() {
        // Add log entries
        Smarty_Gfg_Activity_Logging::gfg_add_activity_log('Regenerated Bing Feed');

        // Fetch products from WooCommerce that are published and in stock
        $products = wc_get_products(array(
            'status'        => 'publish',
            'stock_status'  => 'instock',
            'limit'         => -1,          // No limit to ensure all qualifying products are included
            'orderby'       => 'date',      // Order by product date
            'order'         => 'DESC',      // Order in descending order
        ));

        // Initialize XML structure with a root element
        $xml = new SimpleXMLElement('<feed/>');

        // Iterate through each product to populate the feed
        foreach ($products as $product) {
            if ($product->is_type('variable')) {
                // Handle variable products, which can have multiple variations
                foreach ($product->get_children() as $child_id) {
                    $variation = wc_get_product($child_id);
                    $item = $xml->addChild('item');

                    // Add basic product and variation details
                    $item->addChild('id', $variation->get_id());
                    $item->addChild('mpn', $variation->get_sku());
                    $item->addChild('title', htmlspecialchars($product->get_name()));

                    // Description: Use main product description or fallback to the short description if empty
                    $description = $product->get_description();
                    if (empty($description)) {
                        $description = $product->get_short_description();
                    }

                    if (!empty($description)) {
                        // Ensure that HTML tags are removed and properly encoded
                        $item->addChild('description', htmlspecialchars(strip_tags($description)));
                    } else {
                        $item->addChild('description', 'No description available');
                    }

                    // Categories: Compile a list from the product's categories
                    $categories = wp_get_post_terms($product->get_id(), 'product_cat');
                    if (!empty($categories) && !is_wp_error($categories)) {
                        $category_names = array_map(function($term) { return $term->name; }, $categories);
                        $item->addChild('product_type', htmlspecialchars(join(' > ', $category_names)));
                    }

                    // Product link
                    $item->addChild('link', get_permalink($product->get_id()));

                    // Product images
                    $item->addChild('image_link', wp_get_attachment_url($product->get_image_id()));

                    // Variation specific image, if different from the main product image
                    $image_id = $variation->get_image_id() ? $variation->get_image_id() : $product->get_image_id();
                    $variationImageURL = wp_get_attachment_url($variation->get_image_id());
                    $mainImageURL = wp_get_attachment_url($product->get_image_id());
                    if ($variationImageURL !== $mainImageURL) {
                        $item->addChild('image_link', wp_get_attachment_url($image_id));
                    }

                    // Additional images: Loop through gallery if available
                    $gallery_ids = $product->get_gallery_image_ids();
                    foreach ($gallery_ids as $gallery_id) {
                        $item->addChild('additional_image_link', wp_get_attachment_url($gallery_id));
                    }

                    // Pricing: Regular and sale prices
                    $item->addChild('price', htmlspecialchars($variation->get_regular_price() . ' ' . get_woocommerce_currency()));
                    if ($variation->is_on_sale()) {
                        $item->addChild('sale_price', htmlspecialchars($variation->get_sale_price() . ' ' . get_woocommerce_currency()));
                    }

                    // Custom labels
                    $item->addChild('custom_label_0', Smarty_Gfg_Public::gfg_get_custom_label($product, 'custom_label_0'));
                    $item->addChild('custom_label_1', Smarty_Gfg_Public::gfg_get_custom_label($product, 'custom_label_1'));
                    $item->addChild('custom_label_2', Smarty_Gfg_Public::gfg_get_custom_label($product, 'custom_label_2'));
                    $item->addChild('custom_label_3', Smarty_Gfg_Public::gfg_get_custom_label($product, 'custom_label_3'));
                    $item->addChild('custom_label_4', Smarty_Gfg_Public::gfg_get_custom_label($product, 'custom_label_4'));

                    // Shipping
                    $shipping_cost = Smarty_Gfg_Public::gfg_get_shipping_cost();
                    if ($shipping_cost !== false) {
                        $shipping_element = $item->addChild('shipping');
                        $shipping_element->addChild('price', htmlspecialchars($shipping_cost . ' ' . get_woocommerce_currency()));

                        $base_country = WC()->countries->get_base_country(); // Get the store's base country
                        $shipping_element->addChild('country', htmlspecialchars($base_country));

                        $shipping_element->addChild('service', 'Flat Rate');
                    }
                }
            } else {
                // Handle simple products
                $item = $xml->addChild('item');
                $item->addChild('id', $product->get_id());
                $item->addChild('mpn', $product->get_sku());
                $item->addChild('title', htmlspecialchars($product->get_name()));

                // Description: Use main product description or fallback to the short description if empty
                $description = $product->get_description();
                if (empty($description)) {
                    $description = $product->get_short_description();
                }

                if (!empty($description)) {
                    // Ensure that HTML tags are removed and properly encoded
                    $item->addChild('description', htmlspecialchars(strip_tags($description)));
                } else {
                    $item->addChild('description', 'No description available');
                }

                // Categories: Compile a list from the product's categories
                $categories = wp_get_post_terms($product->get_id(), 'product_cat');
                if (!empty($categories) && !is_wp_error($categories)) {
                    $category_names = array_map(function($term) { return $term->name; }, $categories);
                    $item->addChild('product_type', htmlspecialchars(join(' > ', $category_names)));
                }

                // Product link
                $item->addChild('link', get_permalink($product->get_id()));

                // Product images
                $item->addChild('image_link', wp_get_attachment_url($product->get_image_id()));
                $gallery_ids = $product->get_gallery_image_ids();
                foreach ($gallery_ids as $gallery_id) {
                    $item->addChild('additional_image_link', wp_get_attachment_url($gallery_id));
                }

                // Pricing: Regular and sale prices
                $item->addChild('price', htmlspecialchars($product->get_price() . ' ' . get_woocommerce_currency()));
                if ($product->is_on_sale() && !empty($product->get_sale_price())) {
                    $item->addChild('sale_price', htmlspecialchars($product->get_sale_price() . ' ' . get_woocommerce_currency()));
                }

                // Custom labels
                $item->addChild('custom_label_0', Smarty_Gfg_Public::gfg_get_custom_label($product, 'custom_label_0'));
                $item->addChild('custom_label_1', Smarty_Gfg_Public::gfg_get_custom_label($product, 'custom_label_1'));
                $item->addChild('custom_label_2', Smarty_Gfg_Public::gfg_get_custom_label($product, 'custom_label_2'));
                $item->addChild('custom_label_3', Smarty_Gfg_Public::gfg_get_custom_label($product, 'custom_label_3'));
                $item->addChild('custom_label_4', Smarty_Gfg_Public::gfg_get_custom_label($product, 'custom_label_4'));

                // Shipping
                $shipping_cost = Smarty_Gfg_Public::gfg_get_shipping_cost();
                if ($shipping_cost !== false) {
                    $shipping_element = $item->addChild('shipping');
                    $shipping_element->addChild('price', htmlspecialchars($shipping_cost . ' ' . get_woocommerce_currency()));

                    $base_country = WC()->countries->get_base_country(); // Get the store's base country
                    $shipping_element->addChild('country', htmlspecialchars($base_country));

                    $shipping_element->addChild('service', 'Flat Rate');
                }
            }
        }

        // Save the generated XML content to a transient or a file for later use
        $feed_content = $xml->asXML();
        $cache_duration = get_option('smarty_gfg_cache_duration', 12); // Default to 12 hours if not set
        set_transient('smarty_bing_products_feed', $feed_content, $cache_duration * HOUR_IN_SECONDS);  // Cache the feed using WordPress transients
        file_put_contents(WP_CONTENT_DIR . '/uploads/smarty_bing_products_feed.xml', $feed_content);   // Optionally save the feed to a file in the WP uploads directory
    }

    /**
     * Invalidate cache or regenerate feed when a product is created, updated, or deleted.
     * 
     * @since    1.0.0
     * @param int $product_id The ID of the product that has been created, updated, or deleted.
     */
    public static function gfg_invalidate_bing_products_feed_cache($product_id) {
        // Check if the post is a 'product'
        if (get_post_type($product_id) === 'product') {
            // Invalidate Bing feed cache
            delete_transient('smarty_bing_products_feed');

            // Regenerate the feeds
            $this->gfg_regenerate_bing_products_feed();
        }
    }

    /**
     * Invalidates the cached Bing product feed and optionally regenerates it upon the deletion of a product.
     * 
     * This function ensures that when a product is deleted from WooCommerce, any cached version of the feed
     * does not continue to include data related to the deleted product.
     *
     * @since    1.0.0
     * @param int $post_id The ID of the post (product) being deleted.
     */
    public function gfg_invalidate_bing_products_feed_cache_on_delete($post_id) {
        // Check if the post being deleted is a product
        if (get_post_type($post_id) === 'product') {
            // Invalidate Bing feed cache
            delete_transient('smarty_bing_products_feed');

            // Regenerate the feeds immediately to update the feed file
            $this->gfg_regenerate_bing_products_feed();
        }
    }
}