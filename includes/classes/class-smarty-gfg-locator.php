<?php

/**
 * The core plugin class.
 *
 * This is used to define attributes, functions, internationalization used across
 * both the admin-specific hooks, and public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @link       https://smartystudio.net
 * @since      1.0.0
 *
 * @package    Smarty_Google_Feed_Generator
 * @subpackage Smarty_Google_Feed_Generator/admin/partials
 * @author     Smarty Studio | Martin Nestorov
 */
class Smarty_Gfg_Locator {

	/**
	 * The loader that's responsible for maintaining and registering all hooks
	 * that power the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      Smarty_Gfg_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		if (defined('GFG_VERSION')) {
			$this->version = GFG_VERSION;
		} else {
			$this->version = '1.0.0';
		}

		$this->plugin_name = 'smarty_google_feed_generator';

		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();
		$this->define_public_hooks();
	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - Smarty_Gfg_Loader. Orchestrates the hooks of the plugin.
	 * - Smarty_Gfg_i18n. Defines internationalization functionality.
	 * - Smarty_Gfg_Admin. Defines all hooks for the admin area.
	 * - Smarty_Gfg_Public. Defines all hooks for the public side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies() {
		/**
		 * The class responsible for orchestrating the actions and filters of the core plugin.
		 */
		require_once plugin_dir_path(dirname(__FILE__)) . 'classes/class-smarty-gfg-loader.php';

		/**
		 * The class responsible for defining internationalization functionality of the plugin.
		 */
		require_once plugin_dir_path(dirname(__FILE__)) . 'classes/class-smarty-gfg-i18n.php';

		/**
		 * The class responsible for interacting with the API.
		 */
		require_once plugin_dir_path(dirname(__FILE__)) . 'classes/class-smarty-gfg-api.php';

		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once plugin_dir_path(dirname(__FILE__)) . '../admin/class-smarty-gfg-admin.php';

		/**
		 * The class responsible for defining all actions that occur in the public-facing side of the site.
		 */
		require_once plugin_dir_path(dirname(__FILE__)) . '../public/class-smarty-gfg-public.php';

		// Run the loader
		$this->loader = new Smarty_Gfg_Loader();
	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the Smarty_Gfg_I18n class in order to set the domain and to
	 * register the hook with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_locale() {
		$plugin_i18n = new Smarty_Gfg_i18n();

		$this->loader->add_action('plugins_loaded', $plugin_i18n, 'load_plugin_textdomain');
	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_admin_hooks() {
		$plugin_admin = new Smarty_Gfg_Admin($this->get_plugin_name(), $this->get_version());

		$this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_styles');
		$this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts');
		$this->loader->add_action('admin_menu', $plugin_admin, 'add_settings_page');
		$this->loader->add_action('admin_init', $plugin_admin, 'settings_init');
		$this->loader->add_action('wp_ajax_smarty_clear_logs', $plugin_admin, 'handle_ajax_clear_logs');
		$this->loader->add_action('wp_ajax_smarty_convert_images', $plugin_admin, 'handle_ajax_convert_images');
		$this->loader->add_action('wp_ajax_smarty_convert_all_webp_images_to_png', $plugin_admin, 'handle_ajax_convert_all_images');
		$this->loader->add_action('wp_ajax_smarty_generate_feed', $plugin_admin, 'handle_ajax_generate_feed');
		$this->loader->add_action('wp_ajax_smarty_load_google_categories', $plugin_admin, 'handle_ajax_load_google_categories');
		$this->loader->add_action('woocommerce_admin_process_product_object', $plugin_admin, 'convert_and_update_product_image', 10, 1);
		$this->loader->add_action('admin_notices', $plugin_admin, 'success_notice');
		$this->loader->add_action('admin_notices', $plugin_admin, 'admin_notice');
		$this->loader->add_action('updated_option', $plugin_admin, 'handle_license_status_check', 10, 3);
	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_public_hooks() {
		$plugin_public = new Smarty_Gfg_Public($this->get_plugin_name(), $this->get_version());
		
		$this->loader->add_action('template_redirect', $plugin_public, 'handle_template_redirect');
		$this->loader->add_action('init', $plugin_public, 'add_rewrite_rules');
		$this->loader->add_filter('query_vars', $plugin_public, 'add_query_vars');
		$this->loader->add_action('smarty_generate_google_feed', $plugin_public, 'generate_google_feed');
		$this->loader->add_action('smarty_generate_google_reviews_feed', $plugin_public, 'generate_google_reviews_feed');
		$this->loader->add_action('smarty_generate_bing_feed',  $plugin_public, 'generate_bing_feed');
		$this->loader->add_action('woocommerce_new_product', $plugin_public, 'invalidate_feed_cache');
    	$this->loader->add_action('woocommerce_update_product', $plugin_public, 'invalidate_feed_cache');
		$this->loader->add_action('before_delete_post', $plugin_public, 'invalidate_feed_cache_on_delete');
		$this->loader->add_action('comment_post', $plugin_public, 'invalidate_review_feed_cache', 10, 2);
		$this->loader->add_action('edit_comment', $plugin_public, 'invalidate_review_feed_cache');
		$this->loader->add_action('deleted_comment', $plugin_public, 'invalidate_review_feed_cache');
		$this->loader->add_action('wp_set_comment_status', $plugin_public, 'invalidate_review_feed_cache');
		$this->loader->add_action('save_post_product', $plugin_public, 'handle_product_change');
		$this->loader->add_action('deleted_post', $plugin_public, 'handle_product_change');
	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     1.0.0
	 * @return    Smarty_Gfg_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}
}