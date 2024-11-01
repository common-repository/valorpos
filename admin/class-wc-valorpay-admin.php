<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://valorpaytech.com
 * @since      1.0.0
 *
 * @package    Wc_Valorpay
 * @subpackage Wc_Valorpay/admin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Wc_Valorpay
 * @subpackage Wc_Valorpay/admin
 * @author     Valor PayTech LLC <isvsupport@valorpaytech.com>
 */
class Wc_Valorpay_Admin {

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
	 * @param    string $plugin_name The name of this plugin.
	 * @param    string $version The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version     = $version;

	}
	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts_and_styles() {
		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Wc_Valorpay_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Wc_Valorpay_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		// Get admin screen id.
		$screen    = get_current_screen();
		$screen_id = isset( $screen->id ) ? $screen->id : '';

		if ( in_array( $screen_id, array( 'shop_order', 'woocommerce_page_wc-orders' ), true ) ) {
			// Load order .
			$order = wc_get_order();
			// Load JS file only for valor payment method .
			if ( $order && WC_ValorPay_Gateway::GATEWAY_ID === $order->get_payment_method() ) {
				wp_register_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/wc-valorpay-refund.js', array( 'jquery' ), $this->version, false );
				wp_enqueue_script( $this->plugin_name );
				wp_localize_script(
					$this->plugin_name,
					'valorpay_refund_ajax_object',
					array(
						'ajax_url' => admin_url( 'admin-ajax.php' ),
						'is_2fa'   => array(
							'ajax_action' => 'valorpay_is_2fa_enable',
							'ajax_nonce'  => wp_create_nonce( 'is_2fa_action' ),
						),
						'set_otp'  => array(
							'ajax_action' => 'valorpay_set_otp',
							'ajax_nonce'  => wp_create_nonce( 'valorpay_set_otp_action' ),
						),
						'order_id' => $order->get_id(),
					)
				);
			}
		}

		if ( 'woocommerce_page_wc-settings' === $screen_id ) {

			wp_register_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/wc-valorpay-admin.js', array( 'jquery' ), $this->version, false );
			wp_enqueue_script( $this->plugin_name );
			wp_localize_script(
				$this->plugin_name,
				'valorpay_settings_ajax_object',
				array(
					'ajax_url'    => admin_url( 'admin-ajax.php' ),
					'ajax_action' => 'valorpay_remove_ip',
					'ajax_nonce'  => wp_create_nonce( 'valorpay_remove_ip' ),
					'confirm_msg' => __( 'Are you sure you want to remove?', 'wc-valorpay' ),
				)
			);
			wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/wc-valorpay-admin.css', array(), $this->version );
		}

	}

	/**
	 * Add settings action link to the plugins page.
	 *
	 * @since    1.0.0
	 *
	 * @param array $links Settings links.
	 */
	public function add_action_links( $links ) {

		return array_merge(
			array(
				'settings' => '<a href="' . admin_url( 'admin.php?page=wc-settings&tab=checkout&section=wc_valorpay' ) . '">' . __( 'Settings', 'wc-valorpay' ) . '</a>',
			),
			$links
		);

	}

	/**
	 * Admin valor pay Payment failed tracker.
	 *
	 * @since 1.0.0
	 *
	 * @return void|bool.
	 */
	public function valorpay_admin_payment_failed_tracker() {
		$current_section = ! isset( $_GET['section'] ) ? '' : sanitize_title( wp_unslash( $_GET['section'] ) ); // phpcs:ignore
		if ( WC_ValorPay_Gateway::GATEWAY_ID === $current_section ) {
			$payment_failed_tracker = get_option( WC_VALORPAY_FAILED_PAYMENT_TRACKER, array() );
			if ( ! WC()->payment_gateways() ) {
				return false;
			}
			$all_payment_methods = WC()->payment_gateways()->payment_gateways();
			if ( isset( $all_payment_methods[ WC_ValorPay_Gateway::GATEWAY_ID ] ) && ! empty( $payment_failed_tracker ) ) {
				$valorpay_settings = $all_payment_methods[ WC_ValorPay_Gateway::GATEWAY_ID ];
				if ( 'yes' === $valorpay_settings->disable_payment_on_failed ) {
					foreach ( $payment_failed_tracker as $ip => $tracker_info ) {
						if ( $tracker_info['block_payment'] && $tracker_info['count'] < $valorpay_settings->disable_payment_decline_count ) {
							$payment_failed_tracker[ $ip ]['block_payment'] = false;
						} elseif ( $tracker_info['count'] >= $valorpay_settings->disable_payment_decline_count ) {
							$current_time = time();
							$failed_time  = $tracker_info['last_failed'];
							$elapsed      = round( abs( $current_time - $failed_time ) / MINUTE_IN_SECONDS, 2 );
							if ( $elapsed <= $valorpay_settings->disable_payment_decline_time ) {
								$payment_failed_tracker[ $ip ]['block_payment'] = true;
							} else {
								unset( $payment_failed_tracker[ $ip ] );
							}
						}
					}
					update_option( WC_VALORPAY_FAILED_PAYMENT_TRACKER, $payment_failed_tracker );
				}
			}
			include_once 'partials/wc-valorpay-admin-failure-tracker.php';
		}
	}

	/**
	 * Admin Valor Pay Payment tracker.
	 *
	 * @since 1.0.0
	 *
	 * @return void.
	 */
	public function valorpay_remove_ip() {
		$ip_address = ( ! empty( $_REQUEST['valorpay_track_ip'] ) ) ? sanitize_text_field( wp_unslash( $_REQUEST['valorpay_track_ip'] ) ) : '';
		if ( ! isset( $_REQUEST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_REQUEST['nonce'] ) ), 'valorpay_remove_ip' ) ) {
			wp_send_json_error( 'invalid_nonce' );
		}
		if ( ! $ip_address ) {
			wp_send_json_error( array( 'message' => __( 'Invalid Request.', 'wc-valorpay' ) ) );
		}
		try {
			$payment_failed_tracker = get_option( WC_VALORPAY_FAILED_PAYMENT_TRACKER, array() );
			if ( isset( $payment_failed_tracker[ $ip_address ] ) ) {
				unset( $payment_failed_tracker[ $ip_address ] );
				update_option( WC_VALORPAY_FAILED_PAYMENT_TRACKER, $payment_failed_tracker );
			}
			wp_send_json_success( array( 'total_count' => count( $payment_failed_tracker ) ) );
		} catch ( \Exception $e ) {
			wp_send_json_error( array( 'message' => __( 'Error Occurred.', 'wc-valorpay' ) ) );
		}
	}

	/**
	 * Add refund OTP popup
	 *
	 * @since 1.0.0
	 *
	 * @param int $order_id Order Id.
	 * @return void
	 */
	public function valorpay_add_refund_otp_popup( $order_id ) {
		$order = wc_get_order( $order_id );
			// Add Popup template only if is paid using valor.
		if ( WC_ValorPay_Gateway::GATEWAY_ID === $order->get_payment_method() ) {
			include_once 'partials/wc-valorpay-admin-order-refund.php';
		}
	}

	/**
	 * Ajax action to check if 2FA is enabled in valor
	 *
	 * @since 5.0.0
	 */
	public function valorpay_is_2fa_enable() {
		$api_response = array(
			'show_popup'      => false,
			'message'         => '',
			'additional_html' => '',
		);
		if ( empty( $_REQUEST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_REQUEST['nonce'] ) ), 'is_2fa_action' ) ) {
			wp_send_json_error( 'invalid_nonce' );
		}
		if ( ! isset( $_POST['order_id'] ) || ! sanitize_text_field( wp_unslash( $_POST['order_id'] ) ) ) {
			$api_response['message'] = __( 'Order Id Missing.', 'wc-valorpay' );
			wp_send_json_error( $api_response );
		}
		if ( ! isset( $_POST['refund_amount'] ) || ! sanitize_text_field( wp_unslash( $_POST['refund_amount'] ) ) || sanitize_text_field( wp_unslash( $_POST['refund_amount'] ) ) <= 0 ) {
			$api_response['message'] = __( 'Enter a valid refund amount.', 'wc-valorpay' );
			wp_send_json_error( $api_response );
		}
		try {
			$order_id        = sanitize_text_field( wp_unslash( $_POST['order_id'] ) );
			$order           = wc_get_order( intval( $order_id ) );
			$payment_gateway = wc_get_payment_gateway_by_order( $order );
			// If 2FA is active then send OTP.
			$valorpay_api = new WC_ValorPay_API( $payment_gateway );
			$otp_response = $valorpay_api->send_two_factor_auth_otp( $order, sanitize_text_field( wp_unslash( $_POST['refund_amount'] ) ) );
			if ( is_wp_error( $otp_response ) ) {
				wp_send_json_error( array( 'message' => $otp_response->get_error_message() ) );
			}
			if ( $otp_response->status ) {
				$is_enable_2fa = $otp_response->response->is_enable_2fa;
				$uuid          = $otp_response->response->uuid;
				$order->update_meta_data( '_valorpay_otp_uuid', $uuid );
				$order->save();
				if ( $is_enable_2fa && $uuid ) {
					$reference_no               = $otp_response->response->reference_no;
					$masked_email               = $otp_response->response->emailId;
					$masked_phone               = $otp_response->response->phoneNumber;
					$api_response['show_popup'] = true;
					/* translators: 1: Email Address, 2: Mobile Number */
					$api_response['additional_html'] = '<span>' . sprintf( __( 'OTP sent to your registered Email Address %1$s and Mobile Number %2$s', 'wc-valorpay' ), '<b>' . $masked_email . '</b>', '<b>' . $masked_phone . '</b>' ) . ' </span>';
				}
			}
			wp_send_json_success( $api_response );
		} catch ( \Exception $e ) {
			wp_send_json_error( array( 'message' => __( 'Error Occurred.', 'wc-valorpay' ) ) );
		}
	}

	/**
	 * Ajax action to save the entered OTP to order
	 *
	 * @since 5.0.0
	 */
	public function valorpay_set_otp() {
		$api_response = array(
			'message' => '',
		);
		if ( empty( $_REQUEST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_REQUEST['nonce'] ) ), 'valorpay_set_otp_action' ) ) {
			wp_send_json_error( 'invalid_nonce' );
		}
		if ( ! isset( $_POST['order_id'] ) || ! isset( $_POST['valorpay_2fa_otp'] ) ) {
			$api_response['message'] = __( 'Invalid Request.', 'wc-valorpay' );
			wp_send_json_error( $api_response );
		}
		$valorpay_2fa_otp = sanitize_text_field( wp_unslash( $_POST['valorpay_2fa_otp'] ) );
		if ( ! is_numeric( $valorpay_2fa_otp ) || strlen( (string) ( $valorpay_2fa_otp ) ) !== 6 ) {
			$api_response['message'] = __( 'Enter Valid OTP.', 'wc-valorpay' );
			wp_send_json_error( $api_response );
		}
		try {
			$order = wc_get_order( intval( $_POST['order_id'] ) );
			$order->update_meta_data( '_valorpay_2fa_otp', $valorpay_2fa_otp );
			$order->save();
			wp_send_json_success( $api_response );
		} catch ( \Exception $e ) {
			wp_send_json_error( array( 'message' => __( 'Error Occurred.', 'wc-valorpay' ) ) );
		}
	}


}
