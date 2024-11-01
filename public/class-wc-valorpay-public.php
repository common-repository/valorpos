<?php
/**
 * The public-facing functionality of the plugin.
 *
 * @link       https://valorpaytech.com
 * @since      1.0.0
 *
 * @package    Wc_Valorpay
 * @subpackage Wc_Valorpay/public
 */

defined( 'ABSPATH' ) || exit;
/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @package    Wc_Valorpay
 * @subpackage Wc_Valorpay/public
 * @author     Valor PayTech LLC <isvsupport@valorpaytech.com>
 */
class Wc_Valorpay_Public {

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
	 * @param    string $plugin_name       The name of the plugin.
	 * @param    string $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version     = $version;

	}

	/**
	 * Register the JavaScript and CSS for the public area.
	 *
	 * @since    1.0.0
	 */
	/**
	 * Register the JavaScript and CSS for the public area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts_and_styles() {
		if ( is_checkout() ) {
			$all_payment_methods = WC()->payment_gateways()->payment_gateways();
			if ( ! isset( $all_payment_methods[ WC_ValorPay_Gateway::GATEWAY_ID ] ) ) {
				return;
			}

			wp_enqueue_style( $this->plugin_name, plugins_url( 'css/wc-valorpay-checkout.css', __FILE__ ), array(), $this->version );
			wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/wc-valorpay-checkout.js', array( 'jquery' ), $this->version, false );
			wp_localize_script(
				$this->plugin_name,
				'valorpay_checkout_object',
				array(
					'ajax_url'              => admin_url( 'admin-ajax.php' ),
					'wc_ajax_action'        => WC_AJAX::get_endpoint( '%%endpoint_url%%' ),
					'ct_ajax_action'        => 'valorpay_create_page_token',
					'ct_ajax_nonce'         => wp_create_nonce( 'valorpay_create_page_token' ),
					'bl_ajax_action'        => 'valorpay_bin_lookup',
					'bl_ajax_nonce'         => wp_create_nonce( 'valorpay_bin_lookup' ),
					'card_type_ajax_action' => 'valorpay_token_card_type',
					'card_type_ajax_nonce'  => wp_create_nonce( 'valorpay_token_card_type' ),
					'error_card'            => __( 'Please enter a card number', 'wc-valorpay' ),
					'invalid_card'          => __( 'Invalid card number', 'wc-valorpay' ),
					'expiry_card'           => __( 'Card is expired', 'wc-valorpay' ),
					'invalid_expiry'        => __( 'Please enter card expiry date', 'wc-valorpay' ),
					'error_cvv'             => __( 'Please enter a CVC', 'wc-valorpay' ),
					'invalid_cvv'           => __( 'Invalid CVC length', 'wc-valorpay' ),
					'avs_zip_error'         => __( 'Please enter a zip code', 'wc-valorpay' ),
					'invalid_avs_zip_error' => __( 'Invalid zip code', 'wc-valorpay' ),
					'avs_street_error'      => __( 'Please enter a street address', 'wc-valorpay' ),
					'card_token_error'      => __( 'Unable to process payment. Please try again.', 'wc-valorpay' ),
				)
			);
		}
	}

	/**
	 * Add custom surcharge
	 *
	 * @since 1.0.0
	 * @param WC_Cart $cart Cart item.
	 * @return void
	 */
	public function valorpay_custom_surcharge( $cart ) {
		$valorpay = new WC_ValorPay_Gateway();
		// Check if it's an admin request and not an AJAX request.
		if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
			return;
		}
		$woocommerce    = WC();
		$payment_method = $woocommerce->session->get( 'chosen_payment_method' );

		if ( 'wc_valorpay' !== $payment_method || 'no' === $valorpay->surcharge_indicator || 'no' === $valorpay->enabled ) {
			return;
		}
		if ( 'D' === WC()->session->get( 'valor_card_type' ) && 'no' === $valorpay->surcharge_for_debit ) {
			return;
		}

		$surcharge_value = 0;
		$surcharge_amt   = 0;
		$is_flate_rate   = false;
		if ( 'percentage' === $valorpay->surcharge_type ) {
			$surcharge_value = sprintf( '%.3f', $valorpay->surcharge_percentage );
			$surcharge_amt   = ( $woocommerce->cart->cart_contents_total + $woocommerce->cart->shipping_total ) * $surcharge_value / 100.00;
		} else {
			$surcharge_value = sprintf( '%.3f', $valorpay->surcharge_flat_rate );
			$surcharge_amt   = $surcharge_value;
			$is_flate_rate   = true;
		}
		if ( $surcharge_amt ) {
			$woocommerce->cart->add_fee( "{$valorpay->surcharge_label}", $surcharge_amt );
		}
		if ( ! empty( $cart->recurring_cart_key ) ) {
			if ( ! $is_flate_rate ) {
				$surcharge_amt = ( $cart->cart_contents_total + $cart->shipping_total ) * $surcharge_value / 100.00;
			}
			if ( $surcharge_amt ) {
				$cart->add_fee( "{$valorpay->surcharge_label}", $surcharge_amt );
			}
		}
	}
	/**
	 * Check if current currency accept by Valor Pay.
	 *
	 * @since 1.0.0
	 *
	 * @return bool Valorpay supported currency.
	 */
	protected function valorpay_support_currency() {
		return in_array( get_woocommerce_currency(), array( 'USD' ), true );
	}

	/**
	 * Restrict payment gateway for multiple failed order.
	 *
	 * @since 1.0.0
	 *
	 * @param array $available_gateways The currently available payment gateways.
	 * @return array All of the available payment gateways.
	 */
	public function valorpay_disable_payment_gateway_failed_orders( $available_gateways ) {
		if ( is_admin() || ! isset( $available_gateways[ WC_ValorPay_Gateway::GATEWAY_ID ] ) ) {
			return $available_gateways;
		}
		// Only support USD.
		if ( ! $this->valorpay_support_currency() ) {
			unset( $available_gateways[ WC_ValorPay_Gateway::GATEWAY_ID ] );
		}
		$payment_failed_tracker = get_option( WC_VALORPAY_FAILED_PAYMENT_TRACKER, array() );
		$customer_ip            = WC_Geolocation::get_ip_address();
		if ( isset( $payment_failed_tracker[ $customer_ip ] ) && $payment_failed_tracker[ $customer_ip ]['block_payment'] ) {
			unset( $available_gateways[ WC_ValorPay_Gateway::GATEWAY_ID ] );
		}
		return $available_gateways;
	}

	/**
	 * Restrict payment gateway for multiple failed order.
	 *
	 * @since 1.0.0
	 */
	public function valorpay_before_checkout_form() {
		if ( ! WC()->payment_gateways() ) {
			return false;
		}
		$all_payment_methods    = WC()->payment_gateways()->payment_gateways();
		$payment_failed_tracker = get_option( WC_VALORPAY_FAILED_PAYMENT_TRACKER, array() );
		if ( isset( $all_payment_methods[ WC_ValorPay_Gateway::GATEWAY_ID ] ) && $this->valorpay_support_currency() ) {
			$valorpay = $all_payment_methods[ WC_ValorPay_Gateway::GATEWAY_ID ];
			if ( 'yes' === $valorpay->enabled && 'yes' === $valorpay->disable_payment_on_failed ) {
				$customer_ip = WC_Geolocation::get_ip_address();
				if ( isset( $payment_failed_tracker[ $customer_ip ] ) ) {
					$current_ip_tracker = $payment_failed_tracker[ $customer_ip ];
					if ( $current_ip_tracker['block_payment'] && $current_ip_tracker['count'] < $valorpay->disable_payment_decline_count ) {
						$payment_failed_tracker[ $customer_ip ]['block_payment'] = false;
						update_option( WC_VALORPAY_FAILED_PAYMENT_TRACKER, $payment_failed_tracker );
					} elseif ( $current_ip_tracker['count'] >= $valorpay->disable_payment_decline_count ) {
						$current_time = time();
						$failed_time  = $current_ip_tracker['last_failed'];
						$elapsed      = round( abs( $current_time - $failed_time ) / MINUTE_IN_SECONDS, 2 );
						if ( $elapsed <= $valorpay->disable_payment_decline_time ) {
							wc_add_notice( __( 'Valor Pay is disabled due to multiple payment failures.', 'wc-valorpay' ), 'notice' );
							$payment_failed_tracker[ $customer_ip ]['block_payment'] = true;
						} else {
							unset( $payment_failed_tracker[ $customer_ip ] );
						}
						update_option( WC_VALORPAY_FAILED_PAYMENT_TRACKER, $payment_failed_tracker );
					}
				}
			} elseif ( $payment_failed_tracker ) {
				// In case if failed tracker inactive.
				update_option( WC_VALORPAY_FAILED_PAYMENT_TRACKER, array() );
			}
		} elseif ( $payment_failed_tracker ) {
			// In case if payment id disabled remove tracker.
			update_option( WC_VALORPAY_FAILED_PAYMENT_TRACKER, array() );
		}
	}

	/**
	 * Fetch card type for the default save token.
	 *
	 * @since 7.4.0
	 */
	public function valorpay_update_token_card_type() {
		$current_user_id = get_current_user_id();
		if ( $current_user_id ) {
			$token = WC_Payment_Tokens::get_customer_default_token( $current_user_id );
			if ( ! $token || null === $token ) {
				WC()->session->__unset( 'valor_card_type' );
			} elseif ( 'wc_valorpay' === $token->get_gateway_id() ) {
				$card_type = $token->get_meta( 'is_debit' ) === '1' ? 'D' : 'C';
				// Check if card type not same.
				if ( WC()->session->get( 'valor_card_type' ) !== $card_type ) {
					WC()->session->set( 'valor_card_type', $card_type );
				}
			} else {
				WC()->session->__unset( 'valor_card_type' );
			}
		} else {
			WC()->session->__unset( 'valor_card_type' );
		}
	}

	/**
	 * Create page token.
	 *
	 * @since 7.4.0
	 */
	public function valorpay_create_page_token() {
		if ( isset( $_SERVER['REQUEST_METHOD'] ) && 'POST' !== $_SERVER['REQUEST_METHOD'] ) {
			wp_send_json_error( array( 'message' => __( 'Invalid request method.', 'wc-valorpay' ) ) );
		}
		if ( ! isset( $_REQUEST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_REQUEST['nonce'] ) ), 'valorpay_create_page_token' ) ) {
			wp_send_json_error( 'invalid_nonce' );
		}
		$all_payment_methods = WC()->payment_gateways()->payment_gateways();
		if ( ! isset( $all_payment_methods[ WC_ValorPay_Gateway::GATEWAY_ID ] ) ) {
			wp_send_json_error( array( 'message' => __( 'Payment gateway missing.', 'wc-valorpay' ) ) );
		}
		$valorpay     = $all_payment_methods[ WC_ValorPay_Gateway::GATEWAY_ID ];
		$valorpay_api = new WC_ValorPay_API( $valorpay );
		$api_response = $valorpay_api->create_checkout_page_token();
		if ( is_wp_error( $api_response ) || ! is_object( $api_response ) || ! isset( $api_response->clientToken ) ) { // phpcs:ignore
			wp_send_json_error( array( 'message' => __( 'Unable to create page token.', 'wc-valorpay' ) ) );
		} else {
			wp_send_json_success( array( 'token' => $api_response->clientToken ) ); // phpcs:ignore
		}
	}

	/**
	 * Bin lookup.
	 *
	 * @since 7.4.0
	 * @throws Exception If error.
	 */
	public function valorpay_bin_lookup() {
		try {
			if ( isset( $_SERVER['REQUEST_METHOD'] ) && 'POST' !== $_SERVER['REQUEST_METHOD'] ) {
				wp_send_json_error( array( 'message' => __( 'Invalid request method.', 'wc-valorpay' ) ) );
			}
			if ( ! isset( $_REQUEST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_REQUEST['nonce'] ) ), 'valorpay_bin_lookup' ) ) {
				wp_send_json_error( 'invalid_nonce' );
			}
			if ( ! isset( $_REQUEST['client_token'] ) || ! isset( $_REQUEST['bin'] ) ) {
				wp_send_json_error( array( 'message' => __( 'Invalid request.', 'wc-valorpay' ) ) );
			}
			$all_payment_methods = WC()->payment_gateways()->payment_gateways();
			if ( ! isset( $all_payment_methods[ WC_ValorPay_Gateway::GATEWAY_ID ] ) ) {
				wp_send_json_error( array( 'message' => __( 'Payment gateway missing.', 'wc-valorpay' ) ) );
			}
			$need_update  = false;
			$valorpay     = $all_payment_methods[ WC_ValorPay_Gateway::GATEWAY_ID ];
			$valorpay_api = new WC_ValorPay_API( $valorpay );
			$api_response = $valorpay_api->bin_lookup( sanitize_text_field( wp_unslash( $_REQUEST['bin'] ) ), sanitize_text_field( wp_unslash( $_REQUEST['client_token'] ) ) );
			if ( is_wp_error( $api_response ) ) { // phpcs:ignore
				throw new Exception( 'Error' );
			}
			$card_type = $api_response->card_type;
			// If card type same as previous value, no need to update.
			if ( WC()->session->get( 'valor_card_type' ) !== $card_type ) {
				WC()->session->set( 'valor_card_type', $api_response->card_type );
				$need_update = true;
			}
			wp_send_json_success( $need_update );
		} catch ( Exception $ex ) {
			wp_send_json_error( array( 'message' => __( 'Unable to lookup bin.', 'wc-valorpay' ) ) );
		}
	}

	/**
	 * Get token card type.
	 *
	 * @since 7.4.0
	 * @throws Exception If error.
	 */
	public function valorpay_token_card_type() {
		try {
			if ( isset( $_SERVER['REQUEST_METHOD'] ) && 'POST' !== $_SERVER['REQUEST_METHOD'] ) {
				wp_send_json_error( array( 'message' => __( 'Invalid request method.', 'wc-valorpay' ) ) );
			}
			if ( ! isset( $_REQUEST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_REQUEST['nonce'] ) ), 'valorpay_token_card_type' ) ) {
				wp_send_json_error( 'invalid_nonce' );
			}
			if ( ! isset( $_REQUEST['token_id'] ) ) {
				wp_send_json_error( array( 'message' => __( 'Invalid request.', 'wc-valorpay' ) ) );
			}
			$need_update = false;
			$token_id    = sanitize_text_field( wp_unslash( $_REQUEST['token_id'] ) );
			if ( 'new' !== $token_id ) {
				$payment_token = WC_Payment_Tokens::get( $token_id );
				if ( $payment_token ) {
					$card_type = $payment_token->get_meta( 'is_debit' ) === '1' ? 'D' : 'C';
					// Check if the payment token exists.
					if ( WC()->session->get( 'valor_card_type' ) !== $card_type ) {
						WC()->session->set( 'valor_card_type', $card_type );
						$need_update = true;
					}
				}
			} else {
				WC()->session->__unset( 'valor_card_type' );
				$need_update = true;
			}
			wp_send_json_success( $need_update );
		} catch ( Exception $ex ) {
			wp_send_json_error( array( 'message' => __( 'Unable to set card type.', 'wc-valorpay' ) ) );
		}
	}
}
