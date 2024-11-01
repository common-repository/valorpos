<?php
/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://valorpaytech.com
 * @since      1.0.0
 *
 * @package    Wc_Valorpay
 * @subpackage Wc_Valorpay/includes
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
 * @package    Wc_Valorpay
 * @subpackage Wc_Valorpay/includes
 * @author     Valor PayTech LLC <isvsupport@valorpaytech.com>
 */
class Wc_Valorpay {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      Wc_Valorpay_Loader    $loader    Maintains and registers all hooks for the plugin.
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
		if ( defined( 'WC_VALORPAY_VERSION' ) ) {
			$this->version = WC_VALORPAY_VERSION;
		} else {
			$this->version = '1.0.0';
		}
		$this->plugin_name = 'wc-valorpay';

		$this->load_dependencies();
		$this->set_locale();
		$this->set_valorpay_gateway();
		$this->define_admin_hooks();
		$this->define_public_hooks();

	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - Wc_Valorpay_Loader. Orchestrates the hooks of the plugin.
	 * - Wc_Valorpay_I18n. Defines internationalization functionality.
	 * - Wc_Valorpay_Admin. Defines all hooks for the admin area.
	 * - Wc_Valorpay_Public. Defines all hooks for the public side of the site.
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
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-wc-valorpay-loader.php';

		/**
		 * The class responsible for common gateway related functions
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-wc-valorpay-gateway-loader.php';

		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-wc-valorpay-i18n.php';

		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-wc-valorpay-admin.php';

		/**
		 * The class responsible for defining all actions that occur in the public-facing
		 * side of the site.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-wc-valorpay-public.php';

		$this->loader = new Wc_Valorpay_Loader();

	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the Wc_Valorpay_I18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_locale() {

		$plugin_i18n = new Wc_Valorpay_I18n();

		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );

	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_valorpay_gateway() {

		$gateway_loader = new Wc_Valorpay_Gateway_Loader( $this->get_plugin_name(), $this->get_version() );
		$this->loader->add_action( 'plugins_loaded', $gateway_loader, 'load_payment_gateway' );
		$this->loader->add_filter( 'woocommerce_payment_gateways', $gateway_loader, 'add_gateway' );

	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_admin_hooks() {

		$plugin_admin = new Wc_Valorpay_Admin( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts_and_styles' );
		$this->loader->add_filter( 'plugin_action_links_' . WC_VALORPAY_BASENAME, $plugin_admin, 'add_action_links' );
		$this->loader->add_action( 'woocommerce_after_settings_checkout', $plugin_admin, 'valorpay_admin_payment_failed_tracker' );
		$this->loader->add_action( 'wp_ajax_valorpay_remove_ip', $plugin_admin, 'valorpay_remove_ip' );
		$this->loader->add_action( 'woocommerce_admin_order_items_after_line_items', $plugin_admin, 'valorpay_add_refund_otp_popup', 20 );
		$this->loader->add_action( 'wp_ajax_valorpay_is_2fa_enable', $plugin_admin, 'valorpay_is_2fa_enable' );
		$this->loader->add_action( 'wp_ajax_valorpay_set_otp', $plugin_admin, 'valorpay_set_otp' );

	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_public_hooks() {

		$plugin_public = new Wc_Valorpay_Public( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts_and_styles' );
		$this->loader->add_action( 'woocommerce_cart_calculate_fees', $plugin_public, 'valorpay_custom_surcharge' );
		$this->loader->add_action( 'woocommerce_before_checkout_form', $plugin_public, 'valorpay_before_checkout_form' );
		$this->loader->add_action( 'woocommerce_review_order_before_payment', $plugin_public, 'valorpay_update_token_card_type' );
		$this->loader->add_filter( 'woocommerce_available_payment_gateways', $plugin_public, 'valorpay_disable_payment_gateway_failed_orders' );
		$this->loader->add_action( 'wc_ajax_valorpay_create_page_token', $plugin_public, 'valorpay_create_page_token' );
		$this->loader->add_action( 'wc_ajax_valorpay_bin_lookup', $plugin_public, 'valorpay_bin_lookup' );
		$this->loader->add_action( 'wc_ajax_valorpay_token_card_type', $plugin_public, 'valorpay_token_card_type' );

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
	 * @return    Wc_Valorpay_Loader    Orchestrates the hooks of the plugin.
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

