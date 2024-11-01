<?php
/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://valorpaytech.com
 * @since             1.0.0
 * @package           Wc_Valorpay
 *
 * @wordpress-plugin
 * Plugin Name:       Valor Pay
 * Plugin URI:        https://valorpaytech.com
 * Description:       Adds the Valor Payment Gateway to WooCommerce.
 * Version:           7.7.2
 * Author:            Valor Paytech LLC
 * Author URI:        https://valorpaytech.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       wc-valorpay
 * Domain Path:       /languages
 * WC requires at least: 4.5
 * WC tested up to: 7.4
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'WC_VALORPAY_VERSION', '7.7.2' );
// Directory i.e. /home/user/public_html...
define( 'WC_VALORPAY_DIR', plugin_dir_path( __FILE__ ) );
// Get plugin URL.
define( 'WC_VALORPAY_URL', plugin_dir_url( __FILE__ ) );
// Plugin Basename, for settings page.
define( 'WC_VALORPAY_BASENAME', plugin_basename( __FILE__ ) );
// Option to track payment failed for Valor Pay.
define( 'WC_VALORPAY_FAILED_PAYMENT_TRACKER', 'valorpay_payment_failed_tracker' ); // NOTE: Don't change this.

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-wc-valorpay-activator.php
 */
function activate_wc_valorpay() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-wc-valorpay-activator.php';
	Wc_Valorpay_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-wc-valorpay-deactivator.php
 */
function deactivate_wc_valorpay() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-wc-valorpay-deactivator.php';
	Wc_Valorpay_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_wc_valorpay' );
register_deactivation_hook( __FILE__, 'deactivate_wc_valorpay' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-wc-valorpay.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_wc_valorpay() {

	$plugin = new Wc_Valorpay();
	$plugin->run();

}
run_wc_valorpay();

add_action(
	'before_woocommerce_init',
	function() {
		if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
		}
	}
);
