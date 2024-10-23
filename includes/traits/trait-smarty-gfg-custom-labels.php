<?php
/**
 * Trait to handle Custom Labels-related functionality for product feeds.
 *
 * @link       https://github.com/mnestorov
 * @since      1.0.0
 *
 * @package    Smarty_Google_Feed_Generator
 * @subpackage Smarty_Google_Feed_Generator/includes/traits
 * @author     Smarty Studio | Martin Nestorov
 */
trait Smarty_Gfg_Custom_Labels_Trait {

    /**
     * Register settings and fields related to WooCommerce Product Category.
     * 
     * @since    1.0.0
     * @param string $options_group The options group name.
     * @param string $section_id The section ID for settings fields.
     * @param string $page The settings page where fields are displayed.
     */
    public function gfg_register_custom_labels_settings($options_group, $section_id, $page) {
        $custom_labels = [
			'Custom Label 0' => ['logic', 'value', 'days', 'categories'],
			'Custom Label 1' => ['logic', 'value', 'days', 'categories'],
			'Custom Label 2' => ['logic', 'value', 'days', 'categories'],
			'Custom Label 3' => ['logic', 'value', 'days', 'categories'],
			'Custom Label 4' => ['logic', 'value', 'days', 'categories'],
		];
		
		foreach ($custom_labels as $label => $fields) {
			foreach ($fields as $field) {
				$option_name = 'smarty_gfg_' . strtolower(str_replace(' ', '_', $label)) . '_' . $field;
				register_setting($options_group, $option_name);
			}
		}

        add_settings_field(
			'smarty_gfg_custom_labels_table',                                   // ID of the field
			__('Custom Labels', 'smarty-google-feed-generator'),               	// Title of the field
			array($this, 'gfg_custom_labels_table_cb'),                         // Callback function to display the field
			$page,                                  	                        // Page on which to add the field
			$section_id                                 	                    // Section to which this field belongs
		);
    }

    /**
     * @since    1.0.0
     */
	public function gfg_custom_labels_table_cb() {
		$logics = [
			'older_than_days'       => __('Older Than Days', 'smarty-google-feed-generator'),
			'not_older_than_days'   => __('Not Older Than Days', 'smarty-google-feed-generator'),
			'most_ordered_days'     => __('Most Ordered in Last Days', 'smarty-google-feed-generator'),
			'high_rating_value'     => __('High Rating Value', 'smarty-google-feed-generator'),
			'category'              => __('In Selected Category', 'smarty-google-feed-generator'),
			'has_sale_price'        => __('Has Sale Price', 'smarty-google-feed-generator'),
		];
	
		$custom_labels = [
			'Custom Label 0' => ['logic', 'value', 'days', 'categories'],
			'Custom Label 1' => ['logic', 'value', 'days', 'categories'],
			'Custom Label 2' => ['logic', 'value', 'days', 'categories'],
			'Custom Label 3' => ['logic', 'value', 'days', 'categories'],
			'Custom Label 4' => ['logic', 'value', 'days', 'categories'],
		];
	
		echo '<p>' . __('Configure the logic and values for custom labels.', 'smarty-google-feed-generator') . '</p>';
		echo '<table class="form-table user-friendly-table"><tbody>';
		echo '<tr><th>' . __('Label Names', 'smarty-google-feed-generator') . '</th><th>' . __('Predefined Logic', 'smarty-google-feed-generator') . '</th><th>' . __('Parameter Fields', 'smarty-google-feed-generator') . '</th><th>' . __('Value Fields', 'smarty-google-feed-generator') . '</th></tr>';
	
		foreach ($custom_labels as $label => $details) {
			$logic_option = get_option('smarty_gfg_' . strtolower(str_replace(' ', '_', $label)) . '_logic', '');
			$value_option = get_option('smarty_gfg_' . strtolower(str_replace(' ', '_', $label)) . '_value', '');
			$days_option = get_option('smarty_gfg_' . strtolower(str_replace(' ', '_', $label)) . '_days', '');
			$categories_option = get_option('smarty_gfg_' . strtolower(str_replace(' ', '_', $label)) . '_categories', []);
	
			// Ensure $categories_option is an array
			if (!is_array($categories_option)) {
				$categories_option = explode(',', $categories_option);
			}
	
			echo '<tr>';
			echo '<td>' . esc_html($label) . '</td>';
			echo '<td>';
			echo '<select name="smarty_gfg_' . strtolower(str_replace(' ', '_', $label)) . '_logic" class="smarty-gfg-custom-label-logic ">';
			foreach ($logics as $key => $logic) {
				$selected = $logic_option == $key ? 'selected' : '';
				echo '<option value="' . esc_attr($key) . '" ' . $selected . '>' . esc_html($logic) . '</option>';
			}
			echo '</select>';
			echo '</td>';
			echo '<td class="custom-label-input">';
			if (in_array($logic_option, ['older_than_days', 'not_older_than_days', 'most_ordered_days'])) {
				echo '<input type="number" name="smarty_gfg_' . strtolower(str_replace(' ', '_', $label)) . '_days" value="' . esc_attr($days_option) . '" class="small-text" />';
			} elseif (in_array($logic_option, ['high_rating_value', 'has_sale_price'])) {
				echo '<input type="number" name="smarty_gfg_' . strtolower(str_replace(' ', '_', $label)) . '_value" value="' . esc_attr($value_option) . '" class="small-text" />';
			} elseif ($logic_option === 'category') {
				echo '<select name="smarty_gfg_' . strtolower(str_replace(' ', '_', $label)) . '_categories[]" multiple="multiple" class="select2" style="width:50%;">';
				foreach (get_terms(['taxonomy' => 'product_cat', 'hide_empty' => false]) as $category) {
					$selected = in_array($category->term_id, $categories_option) ? 'selected' : '';
					echo '<option value="' . esc_attr($category->term_id) . '" ' . $selected . '>' . esc_html($category->name) . '</option>';
				}
				echo '</select>';
			} else {
				echo '<input type="text" name="smarty_gfg_' . strtolower(str_replace(' ', '_', $label)) . '_default" value="' . esc_attr($value_option) . '" class="regular-text" />';
			}
			echo '</td>';
			echo '<td class="custom-label-value">';
			echo '<input type="text" name="smarty_gfg_' . strtolower(str_replace(' ', '_', $label)) . '_value" value="' . esc_attr($value_option) . '" class="regular-text" />';
			echo '</td>';
			echo '</tr>';
		}
	
		echo '</tbody></table>';
	}
}