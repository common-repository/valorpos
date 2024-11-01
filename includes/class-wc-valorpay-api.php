<?php
/**
 * Valor Pay Woocommerce payment gateway.
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
 * Valor Pay Woocommerce payment gateway.
 *
 * This class defines all payment gateway related codes.
 *
 * @since      1.0.0
 * @package    Wc_Valorpay
 * @subpackage Wc_Valorpay/includes
 * @author     Valor PayTech LLC <isvsupport@valorpaytech.com>
 */
class WC_ValorPay_API {
	/**
	 * Sandbox payment URL
	 */
	const WC_VALORPAY_SANDBOX_URL = 'https://securelink-staging.valorpaytech.com:4430/';
	/**
	 * Live payment URL
	 */
	const WC_VALORPAY_URL = 'https://securelink.valorpaytech.com/';
	/**
	 * Sandbox OTP refund URL
	 */
	const WC_VALORPAY_REFUND_OTP_SANDBOX_URL = 'https://2fa-staging.valorpaytech.com:4430/?main_action=Manage2FA&operation=ecommRefund';
	/**
	 * Live OTP refund URL
	 */
	const WC_VALORPAY_REFUND_OTP_URL = 'https://2fa.valorpaytech.com/?main_action=Manage2FA&operation=ecommRefund';
	/**
	 * Create page token action
	 */
	const WC_VALORPAY_PAGE_TOKEN_ACTION = 'clientToken';
	/**
	 * Bin lookup action
	 */
	const WC_VALORPAY_BIN_LOOKUP_ACTION = 'binLookup';
	/**
	 * Card token action
	 */
	const WC_VALORPAY_CARD_TOKEN_ACTION = 'cardToken';
	/**
	 * Ecommerce Channel ID
	 */
	const WC_VALORPAY_ECOMM_CHANNEL = 'woocommerce';

	/**
	 * Gateway class.
	 *
	 * @var WC_ValorPay_Gateway
	 */
	protected $gateway;

	/**
	 * Constructor
	 *
	 * @param WC_ValorPay_Gateway $gateway Payment Gateway instance.
	 * @property WC_ValorPay_Gateway $gateway
	 *
	 * @since 1.0.0
	 */
	public function __construct( $gateway = null ) {
		$this->gateway = $gateway;
	}

	/**
	 * Get the API URL.
	 *
	 * @return string
	 *
	 * @since 1.0.0
	 */
	protected function get_valorpay_url() {
		$api_url = self::WC_VALORPAY_URL;

		if ( 'yes' === $this->gateway->sandbox ) {
			$api_url = self::WC_VALORPAY_SANDBOX_URL;
		}

		return $api_url;
	}

	/**
	 * Get the API Refund OTP URL.
	 *
	 * @return string
	 *
	 * @since 1.0.0
	 */
	protected function get_valorpay_refund_otp_url() {
		$api_url = self::WC_VALORPAY_REFUND_OTP_URL;

		if ( 'yes' === $this->gateway->sandbox ) {
			$api_url = self::WC_VALORPAY_REFUND_OTP_SANDBOX_URL;
		}

		return $api_url;
	}

	/**
	 * Place Order API Action
	 *
	 * @since 1.0.0
	 * @param WC_Order              $order Order Detail.
	 * @param float                 $amount Order Amount.
	 * @param WC_Payment_Token|null $card Card Info.
	 *
	 * @return object JSON response
	 */
	public function purchase( $order, $amount, $card ) {
		$payload  = $this->get_payload( $order, $amount, 'sale', $card );
		$response = $this->post_transaction( $payload );
		return $response;
	}

	/**
	 * Refund API Action
	 *
	 * @since 1.0.0
	 * @param WC_Order $order Order Detail.
	 * @param float    $amount Refund Amount.
	 *
	 * @return object JSON response
	 */
	public function refund( $order, $amount ) {
		$payload  = $this->get_payload( $order, $amount, 'refund' );
		$response = $this->post_transaction( $payload );
		return $response;
	}

	/**
	 * Renewal subscription Action, make a sale request.
	 *
	 * @since 7.5.0
	 * @param WC_Order $order Order Detail.
	 * @param float    $amount Refund Amount.
	 *
	 * @return object JSON response
	 */
	public function subscription_renewal( $order, $amount ) {
		$payload  = $this->get_payload( $order, $amount, 'subscription_renewal' );
		$response = $this->post_transaction( $payload );
		return $response;
	}

	/**
	 * Send two factor OTP function
	 *
	 * @since 1.0.0
	 *
	 * @param WC_Order $order Order Detail.
	 * @param float    $amount Refund Amount.
	 *
	 * @return object JSON response
	 */
	public function send_two_factor_auth_otp( $order, $amount ) {
		$payload  = $this->get_payload( $order, $amount, 'send_otp' );
		$response = $this->post_transaction( $payload, 'send_otp' );
		return $response;
	}

	/**
	 * Create checkout page token
	 *
	 * @since 7.4.0
	 * @return object JSON response
	 */
	public function create_checkout_page_token() {
		$payload  = array(
			'appid'    => $this->gateway->appid,
			'appkey'   => $this->gateway->appkey,
			'epi'      => $this->gateway->epi,
			'txn_type' => self::WC_VALORPAY_PAGE_TOKEN_ACTION,
		);
		$response = $this->post_transaction( wp_json_encode( $payload ) );
		return $response;
	}

	/**
	 * Get bin details
	 *
	 * @since 7.4.0
	 *
	 * @param string $bin_number Bin Number.
	 * @param string $client_token Client Token.
	 * @return object JSON response
	 */
	public function bin_lookup( $bin_number, $client_token ) {
		$payload  = array(
			'client_token' => $client_token,
			'bin'          => $bin_number,
			'epi'          => $this->gateway->epi,
			'txn_type'     => self::WC_VALORPAY_BIN_LOOKUP_ACTION,
		);
		$response = $this->post_transaction( wp_json_encode( $payload ), 'bin_lookup' );
		return $response;
	}

	/**
	 * Generate card token
	 *
	 * @since 7.6.0
	 *
	 * @param WC_Order $order Order Detail.
	 * @param array    $card_detail Card detail.
	 */
	public function generate_card_token( $order, $card_detail ) {
		$client_token_response = $this->create_checkout_page_token();
		if ( is_wp_error( $client_token_response ) || ! is_object( $client_token_response ) || ! isset( $client_token_response->clientToken ) ) { // phpcs:ignore
			$order->add_order_note( __( 'Payment error: Unable to generate client token.', 'wc-valorpay' ) );
			return array(
				'error' => __( 'Sorry, we\'re unable to create a card token right now.', 'wc-valorpay' ),
			);
		}
		$client_token = $client_token_response->clientToken; // phpcs:ignore
		$args         = array(
			'txn_type'     => self::WC_VALORPAY_CARD_TOKEN_ACTION,
			'epi'          => $this->gateway->epi,
			'client_token' => $client_token,
			'pan'          => $card_detail['card_num'],
			'expirydate'   => $card_detail['card_expiry'],
		);
		$request_url  = add_query_arg( $args, $this->get_valorpay_url() );
		$api_args     = array(
			'headers' => array(
				'Content-Type' => 'application/json',
			),
			'method'  => 'POST',
			'timeout' => 70,
		);
		$api_response = wp_remote_post( $request_url, $api_args );
		if ( is_wp_error( $api_response ) || empty( $api_response['body'] ) ) {
			$order->add_order_note( __( 'Payment error: Unable to generate card token.', 'wc-valorpay' ) );
			return array(
				'error' => __( 'Sorry, we\'re unable to create a card token right now.', 'wc-valorpay' ),
			);
		}
		$parsed_response = json_decode( preg_replace( '/\xEF\xBB\xBF/', '', $api_response['body'] ), true );
		if ( ! isset( $parsed_response['cardToken'] ) ) {
			$order->add_order_note( __( 'Payment error: Unable to generate card token.', 'wc-valorpay' ) );
			return array(
				'error' => __( 'Sorry, we\'re unable to create a card token right now.', 'wc-valorpay' ),
			);
		}
		return array(
			'token' => $parsed_response['cardToken'],
		);
	}

	/**
	 * Get payload for API
	 *
	 * @since 1.0.0
	 *
	 * @param WC_Order $order Order Detail.
	 * @param float    $amount Order Amount.
	 * @param string   $transaction_type Transaction Type.
	 * @param mixed    $card Card.
	 * @return string JSON response
	 */
	public function get_payload( $order, $amount, $transaction_type, $card = '' ) {
		$order_number = $order->get_id();
		$data         = array();
		if ( 'sale' === $transaction_type ) {
			$surcharge_indicator = ( 'yes' === $this->gateway->surcharge_indicator ) ? 1 : 0;
			$custom_fee          = 0;
			if ( 1 === $surcharge_indicator && 0 < count( $order->get_fees() ) ) {
				$cal_for_debit = ( 'D' === WC()->session->get( 'valor_card_type' ) ) && 'no' !== $this->gateway->surcharge_for_debit;
				if ( $cal_for_debit || ( 'D' !== WC()->session->get( 'valor_card_type' ) ) ) {
					foreach ( $order->get_fees() as $item_fee ) {
						if ( $item_fee->get_name() === $this->gateway->surcharge_label ) {
							$custom_fee = $item_fee->get_amount();
							$amount     = $amount - $custom_fee;
							break;
						}
					}
				}
			}
			$surcharge_data = array(
				'surchargeIndicator' => $surcharge_indicator,
				'surchargeAmount'    => $custom_fee,
			);

			$billing_first_name = wc_clean( $order->get_billing_first_name() );
			$billing_last_name  = wc_clean( $order->get_billing_last_name() );
			$billing_address    = wc_clean( $order->get_billing_address_1() );
			$billing_address2   = wc_clean( $order->get_billing_address_2() );
			$billing_city       = wc_clean( $order->get_billing_city() );
			$billing_state      = wc_clean( $order->get_billing_state() );
			$billing_postcode   = wc_clean( $order->get_billing_postcode() );
			$billing_country    = wc_clean( $order->get_billing_country() );
			$shipping_country   = wc_clean( $order->get_shipping_country() );
			if ( '' === $shipping_country ) {
				$shipping_country = $billing_country;
			}
			$billing_phone       = wc_clean( $order->get_billing_phone() );
			$billing_email       = wc_clean( $order->get_billing_email() );
			$tax_amount          = wc_clean( $order->get_total_tax() );
			$amount              = $amount - $tax_amount;
			$ip_address          = wc_clean( $order->get_customer_ip_address() );
			$valorpay_avs_zip    = ( isset( $_POST['valorpay_avs_zip'] ) && $_POST['valorpay_avs_zip'] ) ? sanitize_text_field( wp_unslash( $_POST['valorpay_avs_zip'] ) ) : wc_clean( substr( $billing_postcode, 0, 10 ) ); // phpcs:ignore
			$valorpay_avs_street = ( isset( $_POST['valorpay_avs_street'] ) && $_POST['valorpay_avs_street'] ) ? sanitize_text_field( wp_unslash( $_POST['valorpay_avs_street'] ) ) : wc_clean( $billing_address ); // phpcs:ignore
			$valorpay_terms      = ( isset( $_POST['valorpay_terms'] ) && $_POST['valorpay_terms'] ) ? 1 : 0; // phpcs:ignore

			$card_number = str_replace( ' ', '', ( isset( $_POST['wc_valorpay-card-number'] ) ) ? sanitize_text_field( wp_unslash( $_POST['wc_valorpay-card-number'] ) ) : '' ); // phpcs:ignore
			$data        = array(
				'appid'            => $this->gateway->appid,
				'appkey'           => $this->gateway->appkey,
				'epi'              => $this->gateway->epi,
				'txn_type'         => $this->gateway->payment_action,
				'amount'           => wc_clean( $amount ),
				'phone'            => $billing_phone,
				'email'            => $billing_email,
				'uid'              => $order_number,
				'tax_amount'       => number_format( $tax_amount, '2', '.', '' ),
				'ip'               => $ip_address,
				'address1'         => $valorpay_avs_street,
				'address2'         => wc_clean( $billing_address2 ),
				'city'             => wc_clean( substr( $billing_city, 0, 40 ) ),
				'state'            => wc_clean( substr( $billing_state, 0, 40 ) ),
				'zip'              => $valorpay_avs_zip,
				'billing_country'  => wc_clean( substr( $billing_country, 0, 60 ) ),
				'shipping_country' => wc_clean( substr( $shipping_country, 0, 60 ) ),
				'status'           => 'Y',
				'terms_checked'    => $valorpay_terms,
				'ecomm_channel'    => self::WC_VALORPAY_ECOMM_CHANNEL,
			) + $surcharge_data;

			// Additional Data.
			$additional_data = apply_filters( 'wc_valorpay_order_invoice_description', array(), $order );
			// Check if $additional_data is an array.
			if ( is_array( $additional_data ) ) {
				// Check if 'invoice_no' key exists and is not empty.
				if ( ! empty( $additional_data['invoice_no'] ) ) {
					$data['invoicenumber'] = $additional_data['invoice_no'];
				}
				// Check if 'order_desc' key exists and is not empty.
				if ( ! empty( $additional_data['order_description'] ) ) {
					$data['orderdescription'] = $additional_data['order_description'];
				}
			}

			if ( $card ) {
				$exp_date           = $card->get_expiry_month() . substr( $card->get_expiry_year(), -2 );
				$data['token']      = $card->get_token();
				$data['expirydate'] = wc_clean( $exp_date );
			} else {
				$cvv                    = isset( $_POST['wc_valorpay-card-cvc'] ) ? sanitize_text_field( wp_unslash( $_POST['wc_valorpay-card-cvc'] ) ) : ''; // phpcs:ignore
				$data['cardnumber']     = $card_number;
				$data['cvv']            = $cvv;
				$data['cardholdername'] = sprintf( '%s %s', wc_clean( $billing_first_name ), wc_clean( $billing_last_name ) );
				if ( isset( $_POST['wc_valorpay-card-expiry'] ) ) { // phpcs:ignore
					$exp_date_array     = explode( '/', sanitize_text_field( wp_unslash( $_POST['wc_valorpay-card-expiry'] ) ) ); // phpcs:ignore
					$exp_month          = trim( $exp_date_array[0] );
					$exp_year           = trim( $exp_date_array[1] );
					$exp_date           = $exp_month . substr( $exp_year, -2 );
					$data['expirydate'] = wc_clean( $exp_date );
				}
			}
			if ( 'yes' === $this->gateway->enable_l2_l3 ) {
				$l2_l3_data = $this->get_order_details( $order );
				if ( count( $l2_l3_data ) > 0 ) {
					$data['order_details'] = $l2_l3_data;
				}
				// For l2 and l3 invoice number required, if not set then set order id as invoice number.
				if ( ! isset( $data['invoicenumber'] ) || ! $data['invoicenumber'] ) {
					$data['invoicenumber'] = $order->get_id();
				}
			}
		} elseif ( 'refund' === $transaction_type ) {
			$valorpay_order_meta = $order->get_meta( '_valorpay_transaction' );
			$data                = array(
				'appid'              => $this->gateway->appid,
				'appkey'             => $this->gateway->appkey,
				'txn_type'           => $transaction_type,
				'amount'             => $amount,
				'token'              => $valorpay_order_meta['token'],
				'epi'                => $this->gateway->epi,
				'ip'                 => WC_Geolocation::get_ip_address(),
				'ref_txn_id'         => $order->get_transaction_id(),
				'auth_code'          => $valorpay_order_meta['approval_code'],
				'surchargeIndicator' => ( 'yes' === $this->gateway->surcharge_indicator ) ? 1 : 0,
				'otp'                => $order->get_meta( '_valorpay_2fa_otp' ),
				'uuid'               => $order->get_meta( '_valorpay_otp_uuid' ),
				'ecomm_channel'      => self::WC_VALORPAY_ECOMM_CHANNEL,
			);
			$order->delete_meta_data( '_valorpay_2fa_otp' );
			$order->delete_meta_data( '_valorpay_otp_uuid' );
			$order->save();
		} elseif ( 'send_otp' === $transaction_type ) {
			$data = array(
				'epi'    => $this->gateway->epi,
				'action' => 'ecomm_refund',
				'appid'  => $this->gateway->appid,
				'appkey' => $this->gateway->appkey,
				'amount' => $amount,
			);
		} elseif ( 'subscription_renewal' === $transaction_type ) {
			$surcharge_indicator = ( 'yes' === $this->gateway->surcharge_indicator ) ? 1 : 0;
			$subscriptions       = wcs_get_subscriptions_for_renewal_order( $order );
			$valorpay_card_type  = '';
			$valorpay_avs_zip    = '';
			$valorpay_avs_street = '';
			$valorpay_card_token = '';
			foreach ( $subscriptions as $subscription ) {
				$valorpay_card_type  = $subscription->get_meta( '_valorpay_card_type' );
				$valorpay_avs_zip    = $subscription->get_meta( '_valorpay_avs_zip' );
				$valorpay_avs_street = $subscription->get_meta( '_valorpay_avs_address' );
				$valorpay_card_token = $subscription->get_meta( '_valorpay_card_token' );
			}
			$is_debit_card = 'D' === $valorpay_card_type;
			$surcharge     = 0;
			$tax_amount    = $order->get_total_tax() ? wc_clean( $order->get_total_tax() ) : 0;
			$amount        = $amount - $tax_amount;
			if ( $surcharge_indicator ) {
				foreach ( $order->get_fees() as $item_fee ) {
					if ( $item_fee->get_name() === $this->gateway->surcharge_label ) {
						$surcharge = $item_fee->get_total();
						$amount    = $amount - $surcharge;
						break;
					}
				}
			}
			$billing_first_name = wc_clean( $order->get_billing_first_name() );
			$billing_last_name  = wc_clean( $order->get_billing_last_name() );
			$billing_address    = wc_clean( $order->get_billing_address_1() );
			$billing_address2   = wc_clean( $order->get_billing_address_2() );
			$billing_city       = wc_clean( $order->get_billing_city() );
			$billing_state      = wc_clean( $order->get_billing_state() );
			$billing_postcode   = wc_clean( $order->get_billing_postcode() );
			$billing_country    = wc_clean( $order->get_billing_country() );
			$shipping_country   = wc_clean( $order->get_shipping_country() );
			if ( '' === $shipping_country ) {
				$shipping_country = $billing_country;
			}
			$billing_phone = wc_clean( $order->get_billing_phone() );
			$billing_email = wc_clean( $order->get_billing_email() );
			$ip_address    = wc_clean( $order->get_customer_ip_address() );

			$valorpay_avs_zip    = $valorpay_avs_zip ? sanitize_text_field( wp_unslash( $valorpay_avs_zip ) ) : wc_clean( substr( $billing_postcode, 0, 10 ) ); // phpcs:ignore
			$valorpay_avs_street = $valorpay_avs_street ? sanitize_text_field( wp_unslash( $valorpay_avs_street ) ) : wc_clean( $billing_address ); // phpcs:ignore

			$data = array(
				'appid'              => $this->gateway->appid,
				'appkey'             => $this->gateway->appkey,
				'epi'                => $this->gateway->epi,
				'txn_type'           => $this->gateway->payment_action,
				'amount'             => wc_clean( round( $amount, 2 ) ),
				'phone'              => $billing_phone,
				'email'              => $billing_email,
				'uid'                => $order_number,
				'tax_amount'         => number_format( $tax_amount, '2', '.', '' ),
				'ip'                 => $ip_address,
				'address1'           => $valorpay_avs_street,
				'address2'           => wc_clean( $billing_address2 ),
				'city'               => wc_clean( substr( $billing_city, 0, 40 ) ),
				'state'              => wc_clean( substr( $billing_state, 0, 40 ) ),
				'zip'                => $valorpay_avs_zip,
				'billing_country'    => wc_clean( substr( $billing_country, 0, 60 ) ),
				'shipping_country'   => wc_clean( substr( $shipping_country, 0, 60 ) ),
				'token'              => $valorpay_card_token,
				'surchargeIndicator' => $surcharge_indicator,
				'surchargeAmount'    => sprintf( '%.2f', $surcharge ),
				'ecomm_channel'      => self::WC_VALORPAY_ECOMM_CHANNEL,
			);

		}

		return wp_json_encode( $data );
	}

	/**
	 * Call valor API
	 *
	 * @since 1.0.0
	 *
	 * @param string $payload JSON payload.
	 * @param string $transaction_type Transaction type.
	 * @return string|WP_Error JSON response or a WP_Error on failure.
	 */
	public function post_transaction( $payload, $transaction_type = '' ) {
		$args     = array(
			'headers' => array(
				'Content-Type' => 'application/json',
			),
			'body'    => $payload,
			'method'  => 'POST',
			'timeout' => 70,
		);
		$api_url  = ( 'send_otp' === $transaction_type ) ? $this->get_valorpay_refund_otp_url() : $this->get_valorpay_url();
		$response = wp_remote_post( $api_url, $args );
		if ( is_wp_error( $response ) || empty( $response['body'] ) ) {
			return new WP_Error( 'valorpay_error', __( 'There was a problem connecting to the payment gateway.', 'wc-valorpay' ) );
		}
		$parsed_response = json_decode( preg_replace( '/\xEF\xBB\xBF/', '', $response['body'] ) );
		if ( 'send_otp' === $transaction_type ) {
			if ( ! isset( $parsed_response->error_code ) ) {
				return new WP_Error( 'valorpay_error', __( 'There was a problem connecting to the payment gateway.', 'wc-valorpay' ) );
			}
			if ( 'S00' !== $parsed_response->error_code ) {
				$error_msg = sprintf( '%s', $parsed_response->error_desc );
				return new WP_Error( 'valorpay_error', $error_msg );
			}
		} elseif ( 'bin_lookup' === $transaction_type ) {
			if ( ! isset( $parsed_response->card_type ) ) {
				return new WP_Error( 'valorpay_error', __( 'There was a problem connecting to the payment gateway.', 'wc-valorpay' ) );
			}
		} else {
			if ( ! isset( $parsed_response->error_no ) ) {
				return new WP_Error( 'valorpay_error', __( 'There was a problem connecting to the payment gateway.', 'wc-valorpay' ) );
			}
			if ( 'S00' !== $parsed_response->error_no ) {
				$error_msg = sprintf( '%s ( %s )', $parsed_response->mesg, $parsed_response->desc );
				return new WP_Error( 'valorpay_error', $error_msg );
			}
		}
		return $parsed_response;
	}

	/**
	 * Get card type
	 *
	 * @since 1.0.0
	 *
	 * @param string $number Card number.
	 * @return string Card Type
	 */
	public function get_card_type( $number ) {
		if ( preg_match( '/^4\d{12}(\d{3})?(\d{3})?$/', $number ) ) {
			return 'Visa';
		} elseif ( preg_match( '/^3[47]\d{13}$/', $number ) ) {
			return 'American Express';
		} elseif ( preg_match( '/^(5[1-5]\d{4}|677189|222[1-9]\d{2}|22[3-9]\d{3}|2[3-6]\d{4}|27[01]\d{3}|2720\d{2})\d{10}$/', $number ) ) {
			return 'MasterCard';
		} elseif ( preg_match( '/^(6011|65\d{2}|64[4-9]\d)\d{12}|(62\d{14})$/', $number ) ) {
			return 'Discover';
		} elseif ( preg_match( '/^35(28|29|[3-8]\d)\d{12}$/', $number ) ) {
			return 'JCB';
		} elseif ( preg_match( '/^3(0[0-5]|[68]\d)\d{11}$/', $number ) ) {
			return 'Diners Club';
		} else {
			return 'Unknown';
		}
	}

	/**
	 * Generate card token
	 *
	 * @since 7.6.1
	 * @param object $card_detail Card detail.
	 * @return array
	 */
	public function add_payment_generate_card_token( $card_detail ) {
		// TODO: Make it as a single API call.
		// Create page token.
		$client_token_response = $this->create_checkout_page_token();
		if ( is_wp_error( $client_token_response ) || ! is_object( $client_token_response ) || ! isset( $client_token_response->clientToken ) ) { // phpcs:ignore
			return array(
				'error' => __( 'Sorry, we\'re unable to create a card token right now.', 'wc-valorpay' ),
			);
		}
		$client_token = $client_token_response->clientToken; // phpcs:ignore
		$card_no      = preg_replace( '/[^0-9 ]/', '', $card_detail->number );
		$bin_no       = substr( $card_no, 0, 6 );
		// Bin Lookup.
		$bin_lookup = $this->bin_lookup( $bin_no, $client_token );
		if ( is_wp_error( $bin_lookup ) || ! is_object( $bin_lookup ) || ! isset( $bin_lookup->card_type ) ) { // phpcs:ignore
			return array(
				'error' => __( 'Sorry, we\'re unable to create a card token right now.', 'wc-valorpay' ),
			);
		}
		$card_type  = $bin_lookup->card_type;
		$card_brand = isset( $bin_lookup->card_brand ) ? $bin_lookup->card_brand : '';

		// Token the card.
		$exp_month    = trim( $card_detail->exp_month );
		$exp_year     = trim( $card_detail->exp_year );
		$exp_date     = $exp_month . substr( $exp_year, -2 );
		$args         = array(
			'txn_type'     => self::WC_VALORPAY_CARD_TOKEN_ACTION,
			'epi'          => $this->gateway->epi,
			'client_token' => $client_token,
			'pan'          => $card_detail->number,
			'expirydate'   => $exp_date,
		);
		$request_url  = add_query_arg( $args, $this->get_valorpay_url() );
		$api_args     = array(
			'headers' => array(
				'Content-Type' => 'application/json',
			),
			'method'  => 'POST',
			'timeout' => 70,
		);
		$api_response = wp_remote_post( $request_url, $api_args );
		if ( is_wp_error( $api_response ) || empty( $api_response['body'] ) ) {
			return array(
				'error' => __( 'Sorry, we\'re unable to create a card token right now.', 'wc-valorpay' ),
			);
		}
		$parsed_response = json_decode( preg_replace( '/\xEF\xBB\xBF/', '', $api_response['body'] ), true );
		if ( ! isset( $parsed_response['cardToken'] ) ) {
			return array(
				'error' => __( 'Sorry, we\'re unable to create a card token right now.', 'wc-valorpay' ),
			);
		}
		return array(
			'token'      => $parsed_response['cardToken'],
			'card_brand' => $card_brand,
			'card_type'  => $card_type,
		);
	}

	/**
	 * Get order detail payload
	 *
	 * @since 7.7.0
	 *
	 * @param WC_Order $order Order Detail.
	 * @return array response
	 */
	public function get_order_details( $order ) {
		$order_details = array();
		if ( count( $order->get_items() ) > 0 ) {
			$product_line_items = array();
			foreach ( $order->get_items() as $item_id => $item ) {
				$product_name         = substr( $item->get_name(), 0, 50 );
				$product_id           = $item->get_product_id();
				$product              = $item->get_product();
				$sku                  = substr( $product->get_sku(), 0, 15 );
				$quantity_ordered     = $item->get_quantity();
				$tax                  = $item->get_subtotal_tax();
				$unit_cost            = $item->get_subtotal();
				$product_line_items[] = array(
					'name'      => $product_name,
					'code'      => $sku ? $sku : $product_id,
					'qty'       => $quantity_ordered,
					'unit_cost' => (float) $unit_cost,
					'tax'       => (float) $tax,
				);
			}
			$order_details['product_line_items'] = $product_line_items;
		}
		if ( count( $order->get_coupons() ) > 0 ) {
			$discounts = array();
			foreach ( $order->get_coupons() as $coupon_code ) {
				// Add to discounts array.
				$discounts[] = array(
					'name' => substr( $coupon_code->get_name(), 0, 50 ),
					'cost' => (float) $coupon_code->get_discount(),
				);
			}
			$order_details['discounts'] = $discounts;
		}
		return $order_details;
	}
}
