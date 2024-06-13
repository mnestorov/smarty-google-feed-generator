<?php

/**
 * Define the internationalization functionality.
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @link       https://smartystudio.net
 * @since      1.0.0
 *
 * @package    Smarty_Google_Feed_Generator
 * @subpackage Smarty_Google_Feed_Generator/admin/partials
 * @author     Smarty Studio | Martin Nestorov
 */
class Smarty_Gfg_I18n {

	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since    1.7.0
	 */
	public function load_plugin_textdomain() {
        load_plugin_textdomain('smarty-google-feed-generator', false, dirname(dirname(plugin_basename(__FILE__))) . '/languages/');
    }
}