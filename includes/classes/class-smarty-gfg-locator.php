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
 * @subpackage Smarty_Google_Feed_Generator/includes/classes
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
		 * Trait to handle Google Products category-related functionality for product feeds.
		 */
		require_once plugin_dir_path(dirname(__FILE__)) . 'traits/trait-smarty-gfg-google-category.php';

		/**
		 * Trait to handle WooCommerce category mapping-related functionality for product feeds.
		 */
		require_once plugin_dir_path(dirname(__FILE__)) . 'traits/trait-smarty-gfg-woo-category-mapping.php';

		/**
		 * Trait to handle Custom Labels-related functionality for product feeds.
		 */
		require_once plugin_dir_path(dirname(__FILE__)) . 'traits/trait-smarty-gfg-custom-labels.php';

		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once plugin_dir_path(dirname(__FILE__)) . '../admin/class-smarty-gfg-admin.php';

		/**
		 * The class responsible for Google Products Feed functionality in the admin area.
		 */
		require_once plugin_dir_path(dirname(__FILE__)) . '../admin/tabs/class-smarty-gfg-google-products-feed.php';

		/**
		 * The class responsible for Google Reviews Feed functionality in the admin area.
		 */
		require_once plugin_dir_path(dirname(__FILE__)) . '../admin/tabs/class-smarty-gfg-google-reviews-feed.php';

		/**
		 * The class responsible for Google Reviews Feed functionality in the admin area.
		 */
		require_once plugin_dir_path(dirname(__FILE__)) . '../admin/tabs/class-smarty-gfg-bing-products-feed.php';

		/**
		 * The class responsible for Activity & Logging functionality in the admin area.
		 */
		require_once plugin_dir_path(dirname(__FILE__)) . '../admin/tabs/class-smarty-gfg-activity-logging.php';

		/**
		 * The class responsible for License functionality in the admin area.
		 */
		require_once plugin_dir_path(dirname(__FILE__)) . '../admin/tabs/class-smarty-gfg-license.php';

		/**
		 * The class responsible for defining all actions that occur in the public-facing side of the site.
		 */
		require_once plugin_dir_path(dirname(__FILE__)) . '../public/class-smarty-gfg-public.php';

		/**
		 * The class responsible for defining all actions that occur in the Google Products feed public-facing side of the site.
		 */
		require_once plugin_dir_path(dirname(__FILE__)) . '../public/feeds/class-smarty-gfg-google-products-feed-public.php';

		/**
		 * The class responsible for defining all actions that occur in the Google Reviews feed public-facing side of the site.
		 */
		require_once plugin_dir_path(dirname(__FILE__)) . '../public/feeds/class-smarty-gfg-google-reviews-feed-public.php';

		/**
		 * The class responsible for defining all actions that occur in the Bing Products feed public-facing side of the site.
		 */
		require_once plugin_dir_path(dirname(__FILE__)) . '../public/feeds/class-smarty-gpg-bing-products-feed-public.php';

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
		
		$plugin_google_products_feed = new Smarty_Gfg_Google_Products_Feed();
		$plugin_google_reviews_feed = new Smarty_Gfg_Google_Reviews_Feed();
		$plugin_bing_products_feed = new Smarty_Gfg_Bing_Products_Feed();
		$plugin_activity_logging = new Smarty_Gfg_Activity_Logging();
		$plugin_license = new Smarty_Gfg_License();

		$this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_styles');
		$this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts');
		$this->loader->add_action('admin_menu', $plugin_admin, 'gfg_add_settings_page');
		$this->loader->add_action('admin_init', $plugin_admin, 'gfg_settings_init');
		$this->loader->add_action('wp_ajax_smarty_gfg_convert_images', $plugin_admin, 'gfg_handle_ajax_convert_images');
		$this->loader->add_action('wp_ajax_smarty_gfg_convert_all_webp_images_to_png', $plugin_admin, 'gfg_handle_ajax_convert_all_images');
		$this->loader->add_action('woocommerce_admin_process_product_object', $plugin_admin, 'gfg_convert_and_update_product_image', 10, 1);
		$this->loader->add_filter('cron_schedules', $plugin_admin, 'gfg_custom_cron_intervals');
		$this->loader->add_action('admin_notices', $plugin_admin, 'gfg_success_notice');

		// Register hooks for Google Products Feed
		$this->loader->add_action('admin_init', $plugin_google_products_feed, 'gfg_gpf_settings_init');
		$this->loader->add_action('wp_ajax_smarty_gfg_load_google_categories', $plugin_google_products_feed, 'gfg_handle_ajax_load_google_categories');

		// Register hooks for Google Reviews Feed
		$this->loader->add_action('admin_init', $plugin_google_reviews_feed, 'gfg_grf_settings_init');

		// Register hooks for Bing Products Feed
		$this->loader->add_action('admin_init', $plugin_bing_products_feed, 'gfg_bpf_settings_init');

		// Register hooks for Activity & Logging
		$this->loader->add_action('admin_init', $plugin_activity_logging, 'gfg_al_settings_init');
        $this->loader->add_action('wp_ajax_smarty_gfg_clear_logs', $plugin_activity_logging, 'gfg_handle_ajax_clear_logs');

		// Register hooks for License management
		$this->loader->add_action('admin_init', $plugin_license, 'gfg_l_settings_init');
		$this->loader->add_action('updated_option', $plugin_license, 'gfg_handle_license_status_check', 10, 3);
		$this->loader->add_action('admin_notices', $plugin_license, 'gfg_license_notice');
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

		$plugin_google_products_feed = new Smarty_Gfg_Google_Products_Feed_Public();
		$plugin_google_reviews_feed = new Smarty_Gfg_Google_Reviews_Feed_Public();
		$plugin_bing_products_feed = new Smarty_Gfg_Bing_Products_Feed_Public();
		
		$this->loader->add_action('template_redirect', $plugin_public, 'gfg_handle_template_redirect');
		$this->loader->add_action('init', $plugin_public, 'gfg_add_rewrite_rules');
		$this->loader->add_filter('query_vars', $plugin_public, 'gfg_add_query_vars');
		$this->loader->add_action('save_post_product', $plugin_public, 'gfg_handle_product_change');
		$this->loader->add_action('deleted_post', $plugin_public, 'gfg_handle_product_change');
	
		// Register hooks for Google Products Feed
		$this->loader->add_action('init', $plugin_google_products_feed, 'gfg_schedule_google_products_feed_generation');
		$this->loader->add_action('smarty_gfg_generate_google_products_feed', $plugin_google_products_feed, 'gfg_generate_google_products_feed');
		$this->loader->add_action('woocommerce_new_product', $plugin_google_products_feed, 'gfg_invalidate_google_products_feed_cache');
    	$this->loader->add_action('woocommerce_update_product', $plugin_google_products_feed, 'gfg_invalidate_google_products_feed_cache');
		$this->loader->add_action('before_delete_post', $plugin_google_products_feed, 'gfg_invalidate_google_products_feed_cache_on_delete');
		
		// Register hooks for Google Reviews Feed
		$this->loader->add_action('init', $plugin_google_reviews_feed, 'gfg_schedule_google_reviews_feed_generation');
		$this->loader->add_action('smarty_gfg_generate_google_reviews_feed', $plugin_google_reviews_feed, 'gfg_generate_google_reviews_feed');
		$this->loader->add_action('comment_post', $plugin_google_reviews_feed, 'gfg_invalidate_google_reviews_feed_cache', 10, 2);
		$this->loader->add_action('edit_comment', $plugin_google_reviews_feed, 'gfg_invalidate_google_reviews_feed_cache');
		$this->loader->add_action('deleted_comment', $plugin_google_reviews_feed, 'gfg_invalidate_google_reviews_feed_cache');
		$this->loader->add_action('wp_set_comment_status', $plugin_google_reviews_feed, 'gfg_invalidate_google_reviews_feed_cache');

		// Register hooks for Bing Products Feed
		$this->loader->add_action('smarty_gfg_generate_bing_products_feed', $plugin_bing_products_feed, 'gfg_generate_bing_products_feed');	
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