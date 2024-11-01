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
class Wc_Valorpay_Activator {

	/**
	 * Import payment settings.
	 *
	 * Import old valorpay plugin setting to new.
	 *
	 * @since    1.0.0
	 */
	public static function activate() {
		$new_plugin_options = get_option( 'woocommerce_wc_valorpay_settings', array() );
		$old_plugin_options = get_option( 'woocommerce_valorpos_settings', array() );
		$old_keys           = array(
			'surchargeIndicator'  => 'surcharge_indicator',
			'surchargeLabel'      => 'surcharge_label',
			'surchargeType'       => 'surcharge_type',
			'surchargePercentage' => 'surcharge_percentage',
			'surchargeFlatRate'   => 'surcharge_flat_rate',
		);
		if ( ! $new_plugin_options && $old_plugin_options ) {
			foreach ( $old_keys as $key => $val ) {
				if ( isset( $old_plugin_options[ $key ] ) ) {
					$old_plugin_options[ $val ] = $old_plugin_options[ $key ];
					unset( $old_plugin_options[ $key ] );
				}
			}
			update_option( 'woocommerce_wc_valorpay_settings', $old_plugin_options );
		}
	}

}
