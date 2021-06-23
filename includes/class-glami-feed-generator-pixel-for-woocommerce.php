<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://www.glami.eco/
 * @since      1.0.0
 *
 * @package    Glami_Feed_Generator_Pixel_For_Woocommerce
 * @subpackage Glami_Feed_Generator_Pixel_For_Woocommerce/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    Glami_Feed_Generator_Pixel_For_Woocommerce
 * @subpackage Glami_Feed_Generator_Pixel_For_Woocommerce/includes
 * @author     GLAMI <info@glami.cz>
 */
class Glami_Feed_Generator_Pixel_For_Woocommerce {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      Glami_Feed_Generator_Pixel_For_Woocommerce_Loader    $loader    Maintains and registers all hooks for the plugin.
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
		if ( defined( 'GLAMI_FEED_GENERATOR_PIXEL_FOR_WOOCOMMERCE_VERSION' ) ) {
			$this->version = GLAMI_FEED_GENERATOR_PIXEL_FOR_WOOCOMMERCE_VERSION;
		} else {
			$this->version = '1.0.0';
		}
		$this->plugin_name = 'glami-feed-generator-pixel-for-woocommerce';

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
	 * - Glami_Feed_Generator_Pixel_For_Woocommerce_Loader. Orchestrates the hooks of the plugin.
	 * - Glami_Feed_Generator_Pixel_For_Woocommerce_i18n. Defines internationalization functionality.
	 * - Glami_Feed_Generator_Pixel_For_Woocommerce_Admin. Defines all hooks for the admin area.
	 * - Glami_Feed_Generator_Pixel_For_Woocommerce_Public. Defines all hooks for the public side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies() {

		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-glami-feed-generator-pixel-for-woocommerce-loader.php';

		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-glami-feed-generator-pixel-for-woocommerce-i18n.php';

		/**
		 * The class responsible for defining integration functionality
		 * of the plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-glami-feed-generator-pixel-for-woocommerce-integration.php';

		/**
		 * The class responsible for defining engine functionality
		 * of the plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-glami-feed-generator-pixel-for-woocommerce-engine.php';

		/**
		 * The class responsible for defining cron functionality
		 * of the plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-glami-feed-generator-pixel-for-woocommerce-cron.php';

		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-glami-feed-generator-pixel-for-woocommerce-admin.php';

		/**
		 * The class responsible for defining all actions that occur in the public-facing
		 * side of the site.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-glami-feed-generator-pixel-for-woocommerce-public.php';

		$this->loader = new Glami_Feed_Generator_Pixel_For_Woocommerce_Loader();

	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the Glami_Feed_Generator_Pixel_For_Woocommerce_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_locale() {

		$plugin_i18n = new Glami_Feed_Generator_Pixel_For_Woocommerce_i18n();

		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );

	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_admin_hooks() {
		$plugin_admin = new Glami_Feed_Generator_Pixel_For_Woocommerce_Admin( $this->get_plugin_name(), $this->get_version() );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );
		$this->loader->add_filter( 'woocommerce_integrations', $plugin_admin, 'add_integration');
		$this->loader->add_action( Glami_Feed_Generator_Pixel_For_Woocommerce_Cron::GLAMI_FEED_GENERATOR_FOR_WOOCOMMERCE_CRON_HOOK, $plugin_admin, 'glami_feed_run_cron_event' );
		$this->loader->add_action('product_cat_add_form_fields', $plugin_admin, 'glami_category_mapping_taxonomy_add_new_meta_field', 10, 1);
		$this->loader->add_action('product_cat_edit_form_fields', $plugin_admin, 'glami_category_mapping_taxonomy_edit_meta_field', 10, 1);
		$this->loader->add_action('edited_product_cat', $plugin_admin, 'save_taxonomy_custom_meta', 10, 1);
		$this->loader->add_action('create_product_cat', $plugin_admin, 'save_taxonomy_custom_meta', 10, 1);
		$this->loader->add_action('wp_ajax_glami_feed_run_ajax_event', $plugin_admin, 'glami_feed_run_ajax_event', 10, 1);
	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_public_hooks() {
		$plugin_public = new Glami_Feed_Generator_Pixel_For_Woocommerce_Public( $this->get_plugin_name(), $this->get_version() );
        $this->loader->add_action( 'wp_print_footer_scripts', $plugin_public, 'glami_output_analytics_tracking_script');
        $this->loader->add_action( 'woocommerce_after_add_to_cart_button', $plugin_public, 'glami_load_add_to_cart_analytics');
        $this->loader->add_action( 'woocommerce_thankyou', $plugin_public, 'glami_load_ecommerce_analytics');
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
	 * @return    Glami_Feed_Generator_Pixel_For_Woocommerce_Loader    Orchestrates the hooks of the plugin.
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
