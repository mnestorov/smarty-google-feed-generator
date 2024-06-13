<?php

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @link       https://smartystudio.net
 * @since      1.0.0
 *
 * @package    Smarty_Google_Feed_Generator
 * @subpackage Smarty_Google_Feed_Generator/admin/partials
 * @author     Smarty Studio | Martin Nestorov
 */
class Smarty_Gfg_Activator {

	/**
	 * This function will be executed when the plugin is activated.
	 *
	 * @since    1.7.0
	 */
	public static function activate() {
		// Check plugin compatibility
		smarty_check_compatibility();

        
    }
}