<?php
/**
 * Fired during plugin activation
 *
 * @link       https://valorpaytech.com
 * @since      1.0.0
 *
 * @package    Wc_Valorpay
 * @subpackage Wc_Valorpay/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    Wc_Valorpay
 * @subpackage Wc_Valorpay/includes
 * @author     Valor PayTech LLC <isvsupport@valorpaytech.com>
 */
class Wc_Valorpay_Gateway_Loader {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param    string $plugin_name       The name of this plugin.
	 * @param    string $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version     = $version;

	}

	/**
	 * Load payment gateway.
	 *
	 * @since    1.0.0
	 */
	public function load_payment_gateway() {

		if ( ! class_exists( 'WC_Payment_Gateway' ) || ! class_exists( 'WC_Payment_Gateway_CC' ) ) {
			return;
		}
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-wc-valorpay-gateway.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-wc-valorpay-api.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-wc-valorpay-gateway-addons.php';

	}

	/**
	 * Add Valor Pay Gateway method into method list.
	 *
	 * @param  array $methods Payment methods.
	 * @return array
	 */
	public function add_gateway( $methods ) {
		$methods[] = 'WC_ValorPay_Gateway';
		if ( class_exists( 'WC_Subscriptions_Order' ) ) {
			$methods[] = 'WC_ValorPay_Gateway_Addons';
		}
		return $methods;
	}

}
