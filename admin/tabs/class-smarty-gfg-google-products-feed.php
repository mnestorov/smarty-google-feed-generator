<?php

/**
 * The Google Products Feed-specific functionality of the plugin.
 *
 * @link       https://smartystudio.net
 * @since      1.0.0
 *
 * @package    Smarty_Google_Feed_Generator
 * @subpackage Smarty_Google_Feed_Generator/admin/tabs
 * @author     Smarty Studio | Martin Nestorov
 */
class Smarty_Gfg_Google_Products_Feed {

    use Smarty_Gfg_Google_Category_Trait;
    use Smarty_Gfg_Woo_Category_Mapping_Trait;
    use Smarty_Gfg_Custom_Labels_Trait;

    /**
	 * The tsv/csv columns description.
	 * 
	 * @since    1.0.0
	 * @access   private
	 */
	private $column_descriptions = array();

    /**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
        $this->init_woo_category_mapping_trait();
		
        // Initialize column descriptions with translations
        $this->column_descriptions = array(
            'ID'                        			=> __('Your products unique identifier.', 'smarty-google-feed-generator'),
            'MPN'                       			=> __('The Manufacturer Part Number for the product.', 'smarty-google-feed-generator'),
            'GTIN'									=> __('A Global Trade Item Number (GTIN) is a unique and internationally recognized identifier for a product.', 'smarty-google-feed-generator'),
			'Title'                     			=> __('The title of the product.', 'smarty-google-feed-generator'),
            'Description'               			=> __('The description of the product.', 'smarty-google-feed-generator'),
            'Product Type'              			=> __('The product type or category.', 'smarty-google-feed-generator'),
            'Link'                      			=> __('The URL of the product page.', 'smarty-google-feed-generator'),
            'Mobile Link'               			=> __('The mobile URL of the product page.', 'smarty-google-feed-generator'),
            'Image Link'                			=> __('The URL of the product image.', 'smarty-google-feed-generator'),
            'Additional Image Link'     			=> __('The URL of an additional image for your product.', 'smarty-google-feed-generator'),
            'Google Product Category'   			=> __('Google-defined product category for your product.', 'smarty-google-feed-generator'),
            'Price'                    			 	=> __('Your products price.', 'smarty-google-feed-generator'),
            'Sale Price'                			=> __('Your products sale price.', 'smarty-google-feed-generator'),
            'Bundle'                    			=> __('Indicates a product is a merchant-defined custom group of different products featuring one main product.', 'smarty-google-feed-generator'),
            'Brand'                     			=> __('Your products brand name.', 'smarty-google-feed-generator'),
            'Condition'                 			=> __('The condition of your product at time of sale.', 'smarty-google-feed-generator'),
            'Multipack'                 			=> __('The number of identical products sold within a merchant-defined multipack.', 'smarty-google-feed-generator'),
            'Color'                     			=> __('Your products color(s).', 'smarty-google-feed-generator'),
            'Gender'                    			=> __('The gender for which your product is intended.', 'smarty-google-feed-generator'),
            'Material'                  			=> __('Your products fabric or material.', 'smarty-google-feed-generator'),
            'Size'                      			=> __('Your products size.', 'smarty-google-feed-generator'),
            'Size Type'                 			=> __('Your apparel products cut.', 'smarty-google-feed-generator'),
            'Size System'               			=> __('The country of the size system used by your product.', 'smarty-google-feed-generator'),
            'Availability'              			=> __('Your products availability.', 'smarty-google-feed-generator'),
            'Custom Label'              			=> __('Label that you assign to a product to help organize bidding and reporting in Shopping campaigns.', 'smarty-google-feed-generator'),
			'Excluded Destination'					=> __('A setting that you can use to exclude a product from participating in a specific type of advertising campaign.', 'smarty-google-feed-generator'),
			'Included Destination'					=> __('A setting that you can use to include a product in a specific type of advertising campaign.', 'smarty-google-feed-generator'),
			'Excluded Countries for Shopping Ads' 	=> __('A setting that allows you to exclude countries where your products are advertised on Shopping ads.', 'smarty-google-feed-generator'),
			'Shipping'								=> __('Your products shipping cost, shipping speeds, and the locations your product ships to.', 'smarty-google-feed-generator'),
		);
	}

    /**
	 * Initializes the Google Reviews Feed settings by registering the settings, sections, and fields.
	 *
	 * @since    1.0.0
	 */
    public function settings_init() {
        register_setting('smarty_gfg_options_google_feed', 'smarty_google_feed_country');
        register_setting('smarty_gfg_options_google_feed', 'smarty_google_include_product_variations');
		register_setting('smarty_gfg_options_google_feed', 'smarty_google_feed_interval');
        register_setting('smarty_gfg_options_google_feed', 'smarty_google_exclude_patterns', 'sanitize_textarea_field');
		register_setting('smarty_gfg_options_google_feed', 'smarty_google_excluded_categories');
        register_setting('smarty_gfg_options_google_feed', 'smarty_google_exclude_xml_columns');
		register_setting('smarty_gfg_options_google_feed', 'smarty_google_exclude_csv_columns');
		register_setting('smarty_gfg_options_google_feed', 'smarty_google_condition');
		register_setting('smarty_gfg_options_google_feed', 'smarty_google_size_system');
		register_setting('smarty_gfg_options_google_feed', 'smarty_google_excluded_destination', array($this, 'sanitize_excluded_destination'));
		register_setting('smarty_gfg_options_google_feed', 'smarty_google_included_destination', array($this, 'sanitize_included_destination'));
		register_setting('smarty_gfg_options_google_feed', 'smarty_google_excluded_countries_for_shopping_ads');

        add_settings_section(
			'smarty_gfg_section_google_feed',									// ID of the section
			__('Google Products Feed', 'smarty-google-feed-generator'),			// Title of the section
			array($this, 'section_tab_google_feed_cb'),							// Callback function that fills the section with the desired content
			'smarty_gfg_options_google_feed'									// Page on which to add the section
		);

		add_settings_field(
            'smarty_google_feed_country',										// ID of the field
            __('Country', 'smarty-google-feed-generator'),						// Title of the field
            array($this, 'google_products_feed_country_cb'),					// Callback function to display the field
            'smarty_gfg_options_google_feed',									// Page on which to add the field
            'smarty_gfg_section_google_feed'									// Section to which this field belongs
        );

		add_settings_field(
            'smarty_google_include_product_variations',							// ID of the field
            __('Include Product Variations', 'smarty-google-feed-generator'),	// Title of the field
            array($this, 'google_products_include_product_variations_cb'),		// Callback function to display the field
            'smarty_gfg_options_google_feed',									// Page on which to add the field
            'smarty_gfg_section_google_feed'									// Section to which this field belongs
        );

		add_settings_field(
            'smarty_google_feed_interval',                                      // ID of the field
            __('Cron Job / Refresh Interval', 'smarty-google-feed-generator'),  // Title of the field
            array($this, 'google_feed_interval_cb'),                            // Callback function to display the field
            'smarty_gfg_options_google_feed',                           		// Page on which to add the field
            'smarty_gfg_section_google_feed'                            		// Section to which this field belongs
        );

        $this->register_google_category_settings('smarty_gfg_options_google_feed', 'smarty_gfg_section_google_feed', 'smarty_gfg_options_google_feed');

		add_settings_field(
			'smarty_google_exclude_patterns',                                   // ID of the field
			__('Exclude Patterns', 'smarty-google-feed-generator'),         	// Title of the field
			array($this,'google_exclude_patterns_cb'),                          // Callback function to display the field
			'smarty_gfg_options_google_feed',                               	// Page on which to add the field
			'smarty_gfg_section_google_feed'                                    // Section to which this field belongs
		);
	
		add_settings_field(
			'smarty_google_excluded_categories',                                // ID of the field
			__('Excluded Categories', 'smarty-google-feed-generator'),      	// Title of the field
			array($this,'google_excluded_categories_cb'),                       // Callback function to display the field
			'smarty_gfg_options_google_feed',                               	// Page on which to add the field
			'smarty_gfg_section_google_feed'                                    // Section to which this field belongs
		);

        $this->register_woo_category_settings('smarty_gfg_options_google_feed', 'smarty_gfg_section_google_feed', 'smarty_gfg_options_google_feed');

		add_settings_field(
			'smarty_google_exclude_xml_columns', 								// ID of the field
			__('XML Columns', 'smarty-google-feed-generator'),  				// Title of the section
			array($this,'google_exclude_xml_columns_cb'), 						// Callback function to display the field
			'smarty_gfg_options_google_feed', 									// Page on which to add the section
			'smarty_gfg_section_google_feed'									// Section to which this field belongs
		);

		add_settings_field(
			'smarty_google_exclude_csv_columns', 								// ID of the field
			__('CSV Columns', 'smarty-google-feed-generator'), 					// Title of the field
			array($this,'google_exclude_csv_columns_cb'), 						// Callback function to display the field
			'smarty_gfg_options_google_feed', 									// Page on which to add the field
			'smarty_gfg_section_google_feed'									// Section to which this field belongs
		);

		add_settings_field(
			'smarty_google_optional_attributes_table',							// ID of the field
			__('Optional Attributes', 'smarty-google-feed-generator'),			// Title of the field
			array($this, 'google_optional_attributes_table_cb'),				// Callback function to display the field
			'smarty_gfg_options_google_feed',									// Page on which to add the field
			'smarty_gfg_section_google_feed'									// Section to which this field belongs
		);

		$this->register_custom_labels_settings('smarty_gfg_options_google_feed', 'smarty_gfg_section_google_feed', 'smarty_gfg_options_google_feed');
    }

    /**
     * Function to sanitize excluded destination on save.
     * 
     * @since    1.0.0
     */
	public function sanitize_excluded_destination($input) {
		if (is_array($input)) {
			return array_map('sanitize_text_field', $input);
		}
		return [];
	}
	
	/**
     * Function to sanitize included destination on save.
     * 
     * @since    1.0.0
     */
	public function sanitize_included_destination($input) {
		if (is_array($input)) {
			return array_map('sanitize_text_field', $input);
		}
		return [];
	}

    /**
	 * Callback function for the Google section field.
	 *
	 * @since    1.0.0
	 */
	public function section_tab_google_feed_cb() {
		echo '<p>' . __('Main column options for the Google Products feed.', 'smarty-google-feed-generator') . '</p>';
	}

    /**
	 *  @since    1.0.0
	 */
	public function google_products_feed_country_cb() {
        $selected_country = get_option('smarty_google_feed_country', '');
        $woocommerce_countries = WC()->countries->get_countries();

        echo '<select name="smarty_google_feed_country" class="select2 smarty-country-select">';
        echo '<option value="">' . __('Select a Country', 'smarty-google-feed-generator') . '</option>';
        foreach ($woocommerce_countries as $code => $name) {
            $selected = $code === $selected_country ? 'selected' : '';
            echo '<option value="' . esc_attr($code) . '" ' . $selected . '>' . esc_html($name) . '</option>';
        }
        echo '</select>';
        echo '<p class="description">' . __('Select the country for which you want to generate the feed.', 'smarty-google-feed-generator') . '</p>';
    }

    /**
	 *  @since    1.0.0
	 */
	public function google_products_include_product_variations_cb() {
        $option = get_option('smarty_google_include_product_variations', 'no');
        echo '<input type="radio" name="smarty_google_include_product_variations" value="yes" ' . checked($option, 'yes', false) . ' /> ' . __('Yes', 'smarty-google-feed-generator');
        echo '<input type="radio" name="smarty_google_include_product_variations" value="no" ' . checked($option, 'no', false) . ' style="margin-left: 10px;" /> ' . __('No', 'smarty-google-feed-generator');
        echo '<p class="description">' . __('Select whether to include product variations in the feed.', 'smarty-google-feed-generator') . '</p>';
    }

	/**
     * Callback function for the Google Products Feed schedule interval.
     * 
     * @since    1.0.0
     */
    public function google_feed_interval_cb() {
        $interval = get_option('smarty_google_feed_interval', 'no_refresh');
        $options = [
            'no_refresh'  => __('No Refresh', 'smarty-google-feed-generator'),
            'hourly'      => __('Hourly', 'smarty-google-feed-generator'),
            'daily'       => __('Daily', 'smarty-google-feed-generator'),
            'twicedaily'  => __('Twice a day', 'smarty-google-feed-generator')
        ];

        echo '<select name="smarty_google_feed_interval">';
        foreach ($options as $value => $label) {
            $selected = $value === $interval ? 'selected' : '';
            echo '<option value="' . esc_attr($value) . '" ' . $selected . '>' . esc_html($label) . '</option>';
        }
        echo '</select>';
        echo '<p class="description">' . __('Select how often the Google Products feed should be refreshed.', 'smarty-google-feed-generator') . '</p>';
    }

    /**
     * Callback function for the Exclude Patterns field.
     * 
     * @since    1.0.0
     */
	public function google_exclude_patterns_cb() {
		$option = get_option('smarty_google_exclude_patterns');
		echo '<textarea name="smarty_google_exclude_patterns" rows="10" cols="50" class="large-text">' . esc_textarea($option) . '</textarea>';
		echo '<p class="description">' . __('Enter URL patterns to exclude from the TSV/CSV feed, one per line.', 'smarty-google-feed-generator') . '</p>';
	}
	
	/**
	 * Callback function to display the excluded categories field.
	 * 
	 * @since    1.0.0
	 */
	public function google_excluded_categories_cb() {
		$option = get_option('smarty_google_excluded_categories', array());
		$categories = get_terms(array(
			'taxonomy' => 'product_cat',
			'hide_empty' => false,
		));
	
		echo '<select name="smarty_google_excluded_categories[]" multiple="multiple" class="smarty-excluded-categories" style="width:50%;">';
		foreach ($categories as $category) {
			echo '<option value="' . esc_attr($category->term_id) . '" ' . (in_array($category->term_id, (array)$option) ? 'selected' : '') . '>' . esc_html($category->name) . '</option>';
		}
		echo '</select>';
		echo '<p class="description">' . __('Select product categories to exclude from the feed.', 'smarty-google-feed-generator') . '</p>';
	}

    /**
	 * A common method to render the columns for both XML and CSV settings.
	 *
	 * @since    1.0.0
	 */
	public function google_render_columns($option_name, $columns, $disabled_columns, $type) {
		$options = get_option($option_name, array());
	
		// Ensure $options is an array
		if (!is_array($options)) {
			$options = array();
		}
	
		echo '<p>' . __('Exclude columns from the TSV/CSV and XML feeds.', 'smarty-google-feed-generator') . '</p>';
		echo '<table class="form-table user-friendly-table"><tbody>';
		echo '<tr><th>' . __('Columns', 'smarty-google-feed-generator') . '</th></tr>';
	
		$column_count = count($columns);
		$columns_per_row = 5; // Adjust this value to change the number of columns per row
	
		echo '<tr><td>';
		echo '<table>';
	
		for ($i = 0; $i < $column_count; $i += $columns_per_row) {
			echo '<tr>';
			for ($j = 0; $j < $columns_per_row; $j++) {
				$index = $i + $j;
				if ($index < $column_count) {
					$column = $columns[$index];
					$checked = in_array($column, $options) ? 'checked' : '';
					$disabled = in_array($column, $disabled_columns) ? 'disabled' : '';
					$description = isset($this->column_descriptions[$column]) ? $this->column_descriptions[$column] : '';
	
					echo '<td><span class="tooltip dashicons dashicons-info"><span class="tooltiptext">' . esc_html($description) . '</span></span><label><input type="checkbox" id="' . esc_attr($column) . '" name="' . esc_attr($option_name) . '[]" value="' . esc_attr($column) . '" ' . $checked . ' ' . $disabled . '> ' . esc_html($column) . '</label></td>';
				} else {
					echo '<td></td>'; // Fill empty cells if columns are less than columns_per_row
				}
			}
			echo '</tr>';
		}
	
		echo '</table>';
		echo '</td></tr>';
	
		echo '</tbody></table>';
	}

	/**
     * @since    1.0.0
     */
	public function google_exclude_xml_columns_cb() {
		$feed_type = get_option('smarty_feed_type', 'google');
		list($columns, $disabled_columns) = $this->google_get_feed_columns($feed_type);
		$this->google_render_columns('smarty_google_exclude_xml_columns', $columns, $disabled_columns, 'xml');
	}
	
	/**
     * @since    1.0.0
     */
	public function google_exclude_csv_columns_cb() {
		$feed_type = get_option('smarty_feed_type', 'google');
		list($columns, $disabled_columns) = $this->google_get_feed_columns($feed_type);
		$this->google_render_columns('smarty_google_exclude_csv_columns', $columns, $disabled_columns, 'csv');
	}

    /**
	 *  @since    1.0.0
	 */
	public function google_optional_attributes_table_cb() {
		// Fetch current options
		$condition = get_option('smarty_google_condition', 'new');
		$excluded_destinations = get_option('smarty_google_excluded_destination', array());
		$included_destinations = get_option('smarty_google_included_destination', array());
		$excluded_country = get_option('smarty_google_excluded_countries_for_shopping_ads', '');
		$size_system = get_option('smarty_google_size_system', '');
	
		// Ensure arrays are properly formatted
		if (!is_array($excluded_destinations)) {
			$excluded_destinations = array();
		}
		if (!is_array($included_destinations)) {
			$included_destinations = array();
		}

		// Condition options
		$condition_options = [
			'new' => __('New', 'smarty-google-feed-generator'),
			'refurbished' => __('Refurbished', 'smarty-google-feed-generator'),
			'used' => __('Used', 'smarty-google-feed-generator'),
		];
	
		// WooCommerce countries
		$woocommerce_countries = WC()->countries->get_countries();
	
		// Destination options
		$destinations = ['Shopping_ads', 'Buy_on_Google_listings', 'Display_ads', 'Local_inventory_ads', 'Free_listings', 'Free_local_listings', 'YouTube_Shopping'];
	
		// Size system options
		$size_systems = ['US', 'UK', 'EU', 'DE', 'FR', 'JP', 'CN', 'IT', 'BR', 'MEX', 'AU'];
		
		echo '<p>' . __('Manage additional optional attributes for Google Products feed.', 'smarty-google-feed-generator') . '</p>';
		echo '<table class="form-table user-friendly-table"><tbody>';
		echo '<tr><th>' . __('Attributes', 'smarty-google-feed-generator') . '</th><th>' . __('Values', 'smarty-google-feed-generator') . '</th></tr>';

		// Condition
		echo '<tr>';
		echo '<td>' . __('Condition', 'smarty-google-feed-generator') . '</td>';
		echo '<td>';
		echo '<table><tr>';
		foreach ($condition_options as $value => $label) {
			$checked = $condition === $value ? 'checked' : '';
			echo '<td><label><input type="radio" name="smarty_google_condition" value="' . esc_attr($value) . '" ' . $checked . '> ' . esc_html($label) . '</label></td>';
		}
		echo '</tr></table>';
		echo '</td>';
		echo '</tr>';
	
		// Excluded Destination
		echo '<tr>';
		echo '<td>' . __('Excluded Destination', 'smarty-google-feed-generator') . '</td>';
		echo '<td>';
		$half_count = ceil(count($destinations) / 2);
		echo '<table><tr>';
		for ($i = 0; $i < $half_count; $i++) {
			$destination = $destinations[$i];
			$checked = in_array($destination, $excluded_destinations) ? 'checked' : '';
			echo '<td><label><input type="checkbox" name="smarty_google_excluded_destination[]" value="' . esc_attr($destination) . '" ' . $checked . '> ' . esc_html(str_replace('_', ' ', $destination)) . '</label></td>';
		}
		echo '</tr><tr>';
		for ($i = $half_count; $i < count($destinations); $i++) {
			$destination = $destinations[$i];
			$checked = in_array($destination, $excluded_destinations) ? 'checked' : '';
			echo '<td><label><input type="checkbox" name="smarty_google_excluded_destination[]" value="' . esc_attr($destination) . '" ' . $checked . '> ' . esc_html(str_replace('_', ' ', $destination)) . '</label></td>';
		}
		echo '</tr></table>';
		echo '</td>';
		echo '</tr>';
	
		// Included Destination
		echo '<tr>';
		echo '<td>' . __('Included Destination', 'smarty-google-feed-generator') . '</td>';
		echo '<td>';
		echo '<table><tr>';
		for ($i = 0; $i < $half_count; $i++) {
			$destination = $destinations[$i];
			$checked = in_array($destination, $included_destinations) ? 'checked' : '';
			echo '<td><label><input type="checkbox" name="smarty_google_included_destination[]" value="' . esc_attr($destination) . '" ' . $checked . '> ' . esc_html(str_replace('_', ' ', $destination)) . '</label></td>';
		}
		echo '</tr><tr>';
		for ($i = $half_count; $i < count($destinations); $i++) {
			$destination = $destinations[$i];
			$checked = in_array($destination, $included_destinations) ? 'checked' : '';
			echo '<td><label><input type="checkbox" name="smarty_google_included_destination[]" value="' . esc_attr($destination) . '" ' . $checked . '> ' . esc_html(str_replace('_', ' ', $destination)) . '</label></td>';
		}
		echo '</tr></table>';
		echo '</td>';
		echo '</tr>';
	
		// Excluded Countries for Shopping Ads
		echo '<tr>';
		echo '<td>' . __('Excluded Countries for Shopping Ads', 'smarty-google-feed-generator') . '</td>';
		echo '<td>';
		echo '<select name="smarty_google_excluded_countries_for_shopping_ads" class="smarty-excluded-countries">';
		echo '<option value="">' . __('Select a Country', 'smarty-google-feed-generator') . '</option>';
		foreach ($woocommerce_countries as $code => $name) {
			$selected = $code === $excluded_country ? 'selected' : '';
			echo '<option value="' . esc_attr($code) . '" ' . $selected . '>' . esc_html($name) . '</option>';
		}
		echo '</select>';
		echo '</td>';
		echo '</tr>';
	
		// Size System
		echo '<tr>';
		echo '<td>' . __('Size System', 'smarty-google-feed-generator') . '</td>';
		echo '<td>';
		$half_count = ceil(count($size_systems) / 2);
		echo '<table><tr>';
		for ($i = 0; $i < $half_count; $i++) {
			$system = $size_systems[$i];
			$checked = $system === $size_system ? 'checked' : '';
			echo '<td><label><input type="radio" name="smarty_google_size_system" value="' . esc_attr($system) . '" ' . $checked . '> ' . esc_html($system) . '</label></td>';
		}
		echo '</tr><tr>';
		for ($i = $half_count; $i < count($size_systems); $i++) {
			$system = $size_systems[$i];
			$checked = $system === $size_system ? 'checked' : '';
			echo '<td><label><input type="radio" name="smarty_google_size_system" value="' . esc_attr($system) . '" ' . $checked . '> ' . esc_html($system) . '</label></td>';
		}
		echo '</tr></table>';
		echo '</td>';
		echo '</tr>';
	
		echo '</tbody></table>';
	}

    /**
	 * Get the appropriate column names and excluded columns based on the feed type.
	 * 
     * @since    1.0.0
     */
	public function google_get_feed_columns($feed_type) {
		$columns = array(
			'ID', 
			'MPN', 
			'GTIN',
			'Title', 
			'Description', 
			'Product Type', 
			'Link',
			'Mobile Link', 
			'Image Link', 
			'Additional Image Link', 
			'Google Product Category',
			'Price', 
			'Sale Price', 
			'Bundle', 
			'Brand', 
			'Condition', 
			'Multipack', 
			'Color',
			'Gender', 
			'Material', 
			'Size', 
			'Size Type', 
			'Size System', 
			'Availability',
			'Custom Label 0', 
			'Custom Label 1', 
			'Custom Label 2', 
			'Custom Label 3', 
			'Custom Label 4',
			'Excluded Destination',
			'Included Destination',
			'Excluded Countries for Shopping Ads',
			'Shipping',
		);
	
		$disabled_columns = array(
			'ID', 
			'MPN',
			'Title', 
			'Description', 
			'Product Type', 
			'Link',
			'Image Link', 
			'Additional Image Link',
			'Google Product Category',
			'Price', 
			'Sale Price', 
			'Bundle', 
			'Brand', 
			'Condition',
			'Availability',
		);
	
		return array($columns, $disabled_columns);
	}

    /**
     * @since    1.0.0
     */
	private function google_is_field_excluded($field) {
		$exclude_xml_columns = get_option('smarty_google_exclude_xml_columns', array());
		$exclude_csv_columns = get_option('smarty_google_exclude_csv_columns', array());
	
		// Ensure these options are arrays
		if (!is_array($exclude_xml_columns)) {
			$exclude_xml_columns = array();
		}
		if (!is_array($exclude_csv_columns)) {
			$exclude_csv_columns = array();
		}
	
		return in_array($field, $exclude_xml_columns) && in_array($field, $exclude_csv_columns);
	}
}