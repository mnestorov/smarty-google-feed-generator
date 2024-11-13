<?php
/**
 * Trait to handle WooCommerce category mapping-related functionality for product feeds.
 *
 * @link       https://github.com/mnestorov/smarty-google-feed-generator
 * @since      1.0.0
 *
 * @package    Smarty_Google_Feed_Generator
 * @subpackage Smarty_Google_Feed_Generator/includes/traits
 * @author     Smarty Studio | Martin Nestorov
 */
trait Smarty_Gfg_Woo_Category_Mapping_Trait {

    /**
     * Initialize the trait and set up AJAX handlers.
     * 
     * @since    1.0.0
     */
    public function gfg_init_woo_category_mapping_trait() {
        add_action('wp_ajax_smarty_gfg_get_woocommerce_categories', array($this, 'gfg_handle_ajax_get_woocommerce_categories'));
    }

    /**
     * Register settings and fields related to WooCommerce Product Category.
     * 
     * @since    1.0.0
     * @param string $options_group The options group name.
     * @param string $section_id The section ID for settings fields.
     * @param string $page The settings page where fields are displayed.
     */
    public function gfg_register_woo_category_settings($options_group, $section_id, $page) {
        register_setting($options_group, 'smarty_gfg_woo_category_mapping', array($this, 'gfg_sanitize_category_mapping'));

        add_settings_field(
            'smarty_gfg_woo_category_mapping', 
            __('Category Mapping', 'smarty-google-feed-generator'), 
            array($this, 'gfg_woo_category_mapping_fields_cb'), 
            $page, 
            $section_id
        );
    }

    /**
     * Sanitize category mapping settings.
     * 
	 * @since    1.0.0
     */
	public function gfg_sanitize_category_mapping($input) {
		$sanitized_input = array();
		foreach ($input as $key => $value) {
			$sanitized_input[$key] = sanitize_text_field($value);
		}
		return $sanitized_input;
	}

    /**
	 * @since    1.0.0
	 */
    public function gfg_woo_category_mapping_fields_cb() {
        $category_mapping = get_option('smarty_gfg_woo_category_mapping', array());
		echo '<p class="description">' . __('Map your categories to the categories of your selected channel.', 'smarty-google-feed-generator') . '</p>';
        echo '<table class="form-table user-friendly-table"><tbody>';
        echo '<tr><th>' . __('WooCommerce Category', 'smarty-google-feed-generator') . '</th><th>' . __('Google Product Category', 'smarty-google-feed-generator') . '</th></tr>';
    
        $woocommerce_categories = get_terms(array('taxonomy' => 'product_cat', 'hide_empty' => false));
    
        foreach ($woocommerce_categories as $category) {
            $google_category = isset($category_mapping[$category->term_id]) ? $category_mapping[$category->term_id] : '';
            echo '<tr>';
            echo '<td>' . esc_html($category->name) . '</td>';
            echo '<td>';
            echo '<select name="smarty_gfg_category_mapping[' . esc_attr($category->term_id) . ']" class="smarty-select2-ajax">';
            echo '<option value="">' . __('Select a Google Category', 'smarty-google-feed-generator') . '</option>';
            foreach ($this->gfg_get_google_product_categories() as $google_cat) {
                $selected = $google_category === $google_cat ? 'selected' : '';
                echo '<option value="' . esc_attr($google_cat) . '" ' . $selected . '>' . esc_html($google_cat) . '</option>';
            }
            echo '</select>';
			echo '</td>';
            echo '</tr>';
		}
    
        echo '</tbody></table>';
	}

    /**
	 * AJAX handler to load Woocommerce Product Categories.
	 * 
	 * @since    1.0.0
	 */
	public function gfg_handle_ajax_get_woocommerce_categories() {
        check_ajax_referer('smarty_feed_generator_nonce', 'nonce');
    
        if (!current_user_can('manage_options')) {
            wp_send_json_error('You do not have sufficient permissions to access this page.');
        }
    
        $categories = get_terms([
            'taxonomy' => 'product_cat',
            'hide_empty' => false,
        ]);
    
        if (is_wp_error($categories)) {
            wp_send_json_error('Error fetching categories');
        }
    
        wp_send_json_success($categories);
    }
}