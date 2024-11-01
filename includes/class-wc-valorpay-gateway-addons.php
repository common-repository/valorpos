<?php
/**
 * Valor Pay Woocommerce payment gateway.
 *
 * @link       https://valorpaytech.com
 * @since       7.5.0
 *
 * @package    Wc_Valorpay
 * @subpackage Wc_Valorpay/includes
 */

defined( 'ABSPATH' ) || exit;
/**
 * Valor Pay Woocommerce payment gateway addon.
 *
 * This class defines all addon related codes.
 *
 * @since      7.5.0
 * @package    Wc_Valorpay
 * @subpackage Wc_Valorpay/includes
 * @author     Valor PayTech LLC <isvsupport@valorpaytech.com>
 */
class WC_ValorPay_Gateway_Addons extends WC_ValorPay_Gateway {
	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    7.5.0
	 * @access   protected
	 * @var      Wc_Valorpay_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;
	/**
	 * WC_ValorPay_Gateway_Addons constructor.
	 */
	public function __construct() {
		parent::__construct();
		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-wc-valorpay-loader.php';
		$this->loader = new Wc_Valorpay_Loader();
		$this->loader->add_filter( 'woocommerce_subscription_payment_meta', $this, 'add_subscription_payment_meta', 10, 2 );
		$this->loader->add_action( 'woocommerce_subscription_validate_payment_meta_' . $this->id, $this, 'validate_subscription_payment_meta', 10, 2 );
		$this->loader->add_action( 'woocommerce_scheduled_subscription_payment_' . $this->id, $this, 'scheduled_subscription_payment', 10, 2 );
		$this->loader->run();
	}

	/**
	 * Include the payment meta data required to process automatic recurring payments so that store managers can
	 * manually set up automatic recurring payments for a customer via the Edit Subscriptions screen in 2.0+.
	 *
	 * @since    7.5.0
	 * @param    array           $payment_details associative array of meta data required for automatic payments.
	 * @param    WC_Subscription $subscription An instance of a subscription object.
	 * @return    array
	 */
	public function add_subscription_payment_meta( $payment_details, $subscription ) {
		$payment_details[ $this->id ] = array(
			'post_meta' => array(
				'_valorpay_card_token' => array(
					'value' => $subscription->get_meta( '_valorpay_card_token', true ),
					'label' => __( 'ValorPay Card Token', 'wc-valorpay' ),
				),
				'_valorpay_card_type'  => array(
					'value' => $subscription->get_meta( '_valorpay_card_type', true ),
					'label' => __( 'ValorPay Card Type', 'wc-valorpay' ),
				),
			),
		);
		return $payment_details;
	}

	/**
	 * Validates subscription payment metadata.
	 *
	 * @since    7.5.0
	 * @param array           $payment_meta Array containing metadata.
	 * @param WC_Subscription $subscription An instance of a subscription object.
	 *
	 * @throws Exception Error if metadata failed check.
	 */
	public function validate_subscription_payment_meta( $payment_meta, $subscription ) {
		if ( ! isset( $payment_meta['post_meta']['_valorpay_card_token']['value'] ) || empty( $payment_meta['post_meta']['_valorpay_card_token']['value'] ) ) {
			throw new Exception( __( 'ValorPay Card Token is required.', 'wc-valorpay' ) );
		}
		if ( ! isset( $payment_meta['post_meta']['_valorpay_card_type']['value'] ) || empty( $payment_meta['post_meta']['_valorpay_card_type']['value'] ) ) {
			throw new Exception( __( 'ValorPay Card Type is required.', 'wc-valorpay' ) );
		}
		if ( $payment_meta['post_meta']['_valorpay_card_type']['value'] && ! in_array( $payment_meta['post_meta']['_valorpay_card_type']['value'], array( 'D', 'C' ), true ) ) {
			throw new Exception( __( 'Invalid ValorPay Card Type. Please select either "D" for Debit or "C" for Credit.', 'wc-valorpay' ) );
		}
		$valorpay_card_type  = $subscription->get_meta( '_valorpay_card_type' );
		$valorpay_card_token = $subscription->get_meta( '_valorpay_card_token' );
		$new_card_type       = $payment_meta['post_meta']['_valorpay_card_type']['value'];
		$new_card_token      = $payment_meta['post_meta']['_valorpay_card_token']['value'];
		if ( $valorpay_card_type !== $new_card_type ) {
			$subscription->add_order_note( __( 'ValorPay Card type updated.', 'wc-valorpay' ) );
		}
		if ( $valorpay_card_token !== $new_card_token ) {
			$subscription->add_order_note( __( 'ValorPay Card token updated.', 'wc-valorpay' ) );
		}
	}

	/**
	 * Process a subscription renewal payment
	 *
	 * @since    7.5.0
	 * @param float     $amount_to_charge float The amount to charge.
	 * @param \WC_Order $renewal_order Renewal order.
	 *
	 * @return void
	 */
	public function scheduled_subscription_payment( $amount_to_charge, $renewal_order ) {
		if ( $renewal_order->get_payment_method() === $this->id ) {
			$valorpay_api = new WC_ValorPay_API( $this );
			$response     = $valorpay_api->subscription_renewal( $renewal_order, $amount_to_charge );
			if ( is_wp_error( $response ) ) {
				$error_message = sprintf(
					/* translators: %s: Response error message. */
					__( 'Payment processing failed. Reason: %s', 'wc-valorpay' ),
					$response->get_error_message()
				);

				$renewal_order->update_status( 'failed', $error_message );
			} else {
				$trans_id        = $response->txnid;
				$tran_meta       = array(
					'transaction_id' => $response->txnid,
					'payment_action' => 'sale',
					'token'          => $response->token,
					'rrn'            => $response->rrn,
					'approval_code'  => $response->approval_code,
				);
				$amount_approved = number_format( $amount_to_charge, '2', '.', '' );
				$renewal_order->payment_complete( $trans_id );
				$renewal_order->add_order_note(
					sprintf(
						/* translators: 1: Error Message, 2: Amount, 3: Line Break, 4: Approval Code, 5: Line Break, 6: RRN Number. */
						__( 'Valor Pay completed for %1$s.%2$s <strong>Approval Code:</strong> %3$s.%4$s <strong>RRN:</strong> %5$s', 'wc-valorpay' ),
						$amount_approved,
						"\n\n",
						$response->approval_code,
						"\n\n",
						$response->rrn
					)
				);
				$renewal_order->update_meta_data( '_valorpay_transaction', $tran_meta );
				$renewal_order->save_meta_data();
				$renewal_order->save();
				WC_Subscriptions_Manager::process_subscription_payments_on_order( $renewal_order );
			}
		}
	}
}
