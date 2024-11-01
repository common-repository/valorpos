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

defined( 'ABSPATH' ) || exit;
/**
 * Valor Pay Woocommerce payment gateway.
 *
 * This class defines all payment gateway related codes.
 *
 * @since      1.0.0
 * @package    Wc_Valorpay
 * @subpackage Wc_Valorpay/includes
 * @author     Valor PayTech LLC <isvsupport@valorpaytech.com>
 * @uses WC_Payment_Gateway_CC
 */
class WC_ValorPay_Gateway extends WC_Payment_Gateway_CC {

	const GATEWAY_ID                         = 'wc_valorpay';
	const WC_VALORPAY_MAX_SURCHARGE_PERCENT  = 10;
	const WC_VALORPAY_MAX_SURCHARGE_FLATRATE = 100;

	/**
	 * Sandbox.
	 *
	 * @var string
	 */
	public $sandbox;

	/**
	 * Valor Pay App ID.
	 *
	 * @var string
	 */
	public $appid;

	/**
	 * Valor Pay App Key.
	 *
	 * @var string
	 */
	public $appkey;

	/**
	 * Valor Pay EPI.
	 *
	 * @var string
	 */
	public $epi;

	/**
	 * Valor Pay payment action.
	 *
	 * @var string
	 */
	public $payment_action;

	/**
	 * Valor Pay surcharge indicator.
	 *
	 * @var string
	 */
	public $surcharge_indicator;

	/**
	 * Valor Pay surcharge label.
	 *
	 * @var string
	 */
	public $surcharge_label;

	/**
	 * Valor Pay surcharge type.
	 *
	 * @var string
	 */
	public $surcharge_type;

	/**
	 * Valor Pay surcharge percentage.
	 *
	 * @var string
	 */
	public $surcharge_percentage;

	/**
	 * Valor Pay surcharge flat rate.
	 *
	 * @var string
	 */
	public $surcharge_flat_rate;

	/**
	 * Valor Pay surcharge for debit.
	 *
	 * @var string
	 */
	public $surcharge_for_debit;

	/**
	 * Valor Pay Card Types.
	 *
	 * @var array
	 */
	public $cardtypes;

	/**
	 * Valor Pay AVS Type.
	 *
	 * @var string
	 */
	public $avs_type;

	/**
	 * Valor Pay Disable payment.
	 *
	 * @var string
	 */
	public $disable_payment_on_failed;

	/**
	 * Valor Pay Decline count.
	 *
	 * @var string
	 */
	public $disable_payment_decline_count;

	/**
	 * Valor Pay decline time.
	 *
	 * @var string
	 */
	public $disable_payment_decline_time;

	/**
	 * Valor Pay card type allowed.
	 *
	 * @var string
	 */
	public $card_type_allowed;

	/**
	 * Valor Pay enable L2 & L3.
	 *
	 * @var string
	 */
	public $enable_l2_l3;

	/**
	 * Constructor
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		$this->id                 = self::GATEWAY_ID;
		$this->has_fields         = true;
		$this->method_title       = __( 'ValorPay Plugin', 'wc-valorpay' );
		$this->method_description = __( 'Take payments via Valorpay.', 'wc-valorpay' );
		// Load the form fields.
		$this->init_form_fields();
		// Load the settings.
		$this->init_settings();
		// Define the supported features.
		$this->supports = array(
			'products',
			'refunds',
			'tokenization',
			'subscriptions',
			'subscription_cancellation',
			'subscription_suspension',
			'subscription_reactivation',
			'subscription_amount_changes',
			'subscription_date_changes',
			'multiple_subscriptions',
		);
		// Define user set variables.
		$this->enabled                       = $this->get_option( 'enabled' );
		$this->title                         = $this->get_option( 'title' );
		$this->sandbox                       = $this->get_option( 'sandbox' );
		$this->appid                         = $this->get_option( 'appid' );
		$this->appkey                        = $this->get_option( 'appkey' );
		$this->epi                           = $this->get_option( 'epi' );
		$this->payment_action                = $this->get_option( 'payment_action' );
		$this->surcharge_indicator           = $this->get_option( 'surcharge_indicator' );
		$this->surcharge_label               = $this->get_option( 'surcharge_label' );
		$this->surcharge_type                = $this->get_option( 'surcharge_type' );
		$this->surcharge_percentage          = $this->get_option( 'surcharge_percentage' );
		$this->surcharge_flat_rate           = $this->get_option( 'surcharge_flat_rate' );
		$this->surcharge_for_debit           = $this->get_option( 'surcharge_for_debit' );
		$this->cardtypes                     = $this->get_option( 'cardtypes' );
		$this->avs_type                      = $this->get_option( 'avs_type' );
		$this->disable_payment_on_failed     = $this->get_option( 'disable_payment_on_failed' );
		$this->disable_payment_decline_count = $this->get_option( 'disable_payment_decline_count' );
		$this->disable_payment_decline_time  = $this->get_option( 'disable_payment_decline_time' );
		$this->card_type_allowed             = $this->get_option( 'card_type_allowed' );
		$this->enable_l2_l3                  = $this->get_option( 'enable_l2_l3' );

		// Add test mode warning if sandbox.
		if ( 'yes' === $this->sandbox ) {
			$this->description = __( 'TEST MODE ENABLED. Use test card number 4111111111111111 with 999 as CVC and a future expiration date.', 'wc-valorpay' );
		}

		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
		add_action( 'admin_notices', array( $this, 'wc_valorpay_admin_notices' ) );
	}

	/**
	 * Admin notices
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function wc_valorpay_admin_notices() {
		$valid_valorpay_currency = in_array( get_woocommerce_currency(), array( 'USD' ), true );
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}
		if ( ! $valid_valorpay_currency && 'yes' === $this->enabled ) {
			?>
			<div class="notice notice-warning">
				<p>
					<b>
						<?php esc_html_e( 'Unsupported currency:', 'wc-valorpay' ); ?>
						<?php esc_html( ' ' . get_woocommerce_currency() ); ?>
					</b>
					<?php esc_html_e( 'Valor Pay accepts only USD.', 'wc-valorpay' ); ?>
				</p>
			</div>
			<?php
		}
		if ( 'no' === $this->enabled || 'yes' === $this->sandbox ) {
			return;
		}
		// Show message if APP ID is empty in live mode .
		if ( ! $this->appid ) {
			echo '<div class="error"><p>' . sprintf(
				/* translators: %s: Settings URL. */
				esc_html__( 'Valor Pay error: The APP ID is required.  %s', 'wc-valorpay' ),
				' <a href="' . esc_url( admin_url( 'admin.php?page=wc-settings&tab=checkout&section=wc_valorpay' ) ) . '">' . esc_html__( 'Click here to update your Valor Pay settings.', 'wc-valorpay' ) . '</a>'
			) . '</p></div>';

			return;
		}

		// Show message if APP Key is empty in live mode .
		if ( ! $this->appkey ) {
			echo '<div class="error"><p>' . sprintf(
				/* translators: %s: Settings URL. */
				esc_html__( 'Valor Pay error: The APP KEY is required.  %s', 'wc-valorpay' ),
				' <a href="' . esc_url( admin_url( 'admin.php?page=wc-settings&tab=checkout&section=wc_valorpay' ) ) . '">' . esc_html__( 'Click here to update your Valor Pay settings.', 'wc-valorpay' ) . '</a>'
			) . '</p></div>';
			return;
		}
		// Show message if APP EPI is empty in live mode .
		if ( ! $this->epi ) {
			echo '<div class="error"><p>' . sprintf(
				/* translators: %s: Settings URL. */
				esc_html__( 'Valor Pay error: The EPI is required.  %s', 'wc-valorpay' ),
				' <a href="' . esc_url( admin_url( 'admin.php?page=wc-settings&tab=checkout&section=wc_valorpay' ) ) . '">' . esc_html__( 'Click here to update your Valor Pay settings.', 'wc-valorpay' ) . '</a>'
			) . '</p></div>';
			return;
		}
	}

	/**
	 * Administrator area options
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function admin_options() {
		echo '<h3><img src="' . esc_url( WC_HTTPS::force_https_url( WC_VALORPAY_URL . 'admin/images/ValorPay.png' ) ) . '" alt="ValorPay" /></h3>';
		parent::admin_options();
	}

	/**
	 * Builds our payment fields area - including tokenization fields for logged
	 * in users, and the actual payment fields.
	 *
	 * @since 1.0.0
	 */
	public function payment_fields() {
		if ( $this->description ) {
			echo wp_kses_post( apply_filters( 'wc_valorpay_description', wpautop( wptexturize( $this->description ) ) ) );
		}
		if ( 'debit' === $this->card_type_allowed ) {
			echo '<div class="woocommerce-info" style="margin-bottom:unset;">' . esc_html__( 'Only debit cards are allowed', 'wc-valorpay' ) . '</div>';
		} elseif ( 'credit' === $this->card_type_allowed ) {
			echo '<div class="woocommerce-info" style="margin-bottom:unset;">' . esc_html__( 'Only credit cards are allowed', 'wc-valorpay' ) . '</div>';
		}
		parent::payment_fields();
		if ( ! is_add_payment_method_page() ) {
			$this->valorpay_avs_form();
			$this->valorpay_acknowledgement_form();
		}
	}
	/**
	 * Luhn check.
	 *
	 * @since 7.3.0
	 * @param  string $account_number Account Number.
	 * @return bool
	 */
	public function luhn_check( $account_number ) {
		for ( $sum = 0, $i = 0, $ix = strlen( $account_number ); $i < $ix - 1; $i++ ) {
			$weight = substr( $account_number, $ix - ( $i + 2 ), 1 ) * ( 2 - ( $i % 2 ) );
			$sum   += $weight < 10 ? $weight : $weight - 9;

		}
		if ( 0 !== $sum ) {
			return ( (int) substr( $account_number, $ix - 1 ) ) === ( ( 10 - $sum % 10 ) % 10 );
		} else {
			return false;
		}
	}
	/**
	 * Get card information.
	 *
	 * @since 7.3.0
	 * @return object
	 */
	private function get_posted_card() {
		$card_number    = isset( $_POST['wc_valorpay-card-number'] ) ? wc_clean( $_POST['wc_valorpay-card-number'] ) : ''; // phpcs:ignore
		$card_cvc       = isset( $_POST['wc_valorpay-card-cvc'] ) ? wc_clean( $_POST['wc_valorpay-card-cvc'] ) : ''; // phpcs:ignore
		$card_expiry    = isset( $_POST['wc_valorpay-card-expiry'] ) ? wc_clean( $_POST['wc_valorpay-card-expiry'] ) : ''; // phpcs:ignore
		$card_number    = str_replace( array( ' ', '-' ), '', $card_number );
		$card_expiry    = array_map( 'trim', explode( '/', $card_expiry ) );
		$card_exp_month = str_pad( $card_expiry[0], 2, '0', STR_PAD_LEFT );
		$card_exp_year  = isset( $card_expiry[1] ) ? $card_expiry[1] : '';
		if ( 2 === strlen( $card_exp_year ) ) {
			$card_exp_year += 2000;
		}
		return (object) array(
			'number'    => $card_number,
			'type'      => '',
			'cvc'       => $card_cvc,
			'exp_month' => $card_exp_month,
			'exp_year'  => $card_exp_year,
		);
	}

	/**
	 * Validate frontend fields.
	 *
	 * Validate payment fields on the frontend.
	 *
	 * @since 7.3.0
	 * @throws \Exception If the card information is invalid.
	 * @return bool
	 */
	public function validate_fields() {
		try {
			$payment_failed_tracker = get_option( WC_VALORPAY_FAILED_PAYMENT_TRACKER, array() );
			$customer_ip            = WC_Geolocation::get_ip_address();
			if ( isset( $payment_failed_tracker[ $customer_ip ] ) && $payment_failed_tracker[ $customer_ip ]['block_payment'] ) {
				throw new Exception( __( 'The payment gateway is disabled due to multiple failed transactions.', 'wc-valorpay' ) );
			}
			$is_debit_card = 'D' === WC()->session->get( 'valor_card_type' );
			if ( 'debit' === $this->card_type_allowed && ! $is_debit_card ) {
				throw new Exception( __( 'Only debit cards are allowed.', 'wc-valorpay' ) );
			} elseif ( 'credit' === $this->card_type_allowed && $is_debit_card ) {
				throw new Exception( __( 'Only credit cards are allowed.', 'wc-valorpay' ) );
			}
			$valorpay_avs_type = ( isset( $_POST['valorpay_avs_type'] ) ) ? sanitize_text_field( wp_unslash( $_POST['valorpay_avs_type'] ) ) : ''; // phpcs:ignore
			if ( 'zip' === $valorpay_avs_type || 'zipandaddress' === $valorpay_avs_type ) {
				if ( ! isset( $_POST['valorpay_avs_zip'] ) || ! sanitize_text_field( wp_unslash( $_POST['valorpay_avs_zip'] ) ) ) { // phpcs:ignore
					throw new Exception( __( 'Zip Code is required.', 'wc-valorpay' ) );
				}
				if ( isset( $_POST['valorpay_avs_zip'] ) && sanitize_text_field( wp_unslash( $_POST['valorpay_avs_zip'] ) ) && ! preg_match( '/^([a-zA-Z0-9_-]){4,6}$/', sanitize_text_field( wp_unslash( $_POST['valorpay_avs_zip'] ) ) ) ) { // phpcs:ignore
					throw new Exception( __( 'Enter a valid Zip Code.', 'wc-valorpay' ) );
				}
			}
			if ( 'address' === $valorpay_avs_type || 'zipandaddress' === $valorpay_avs_type ) {
				if ( ! isset( $_POST['valorpay_avs_street'] ) || ! sanitize_text_field( wp_unslash( $_POST['valorpay_avs_street'] ) ) ) { // phpcs:ignore
					throw new Exception( __( 'Street Address is required.', 'wc-valorpay' ) );
				}
				if ( isset( $_POST['valorpay_avs_street'] ) && strlen( sanitize_text_field( wp_unslash( $_POST['valorpay_avs_street'] ) ) ) > 25 ) { // phpcs:ignore
					throw new Exception( __( 'Enter a valid Street Address.', 'wc-valorpay' ) );
				}
			}
			if ( isset( $_POST['wc-wc_valorpay-payment-token'] ) && 'new' !== wc_clean( $_POST['wc-wc_valorpay-payment-token'] ) ) { // phpcs:ignore
				return true;
			}
			$card          = $this->get_posted_card();
			$current_year  = gmdate( 'Y' );
			$current_month = gmdate( 'n' );

			if ( empty( $card->number ) || ! ctype_digit( $card->number ) || strlen( $card->number ) < 12 || strlen( $card->number ) > 19 ) {
				throw new Exception( __( 'Card number is invalid', 'wc-valorpay' ) );
			}

			if ( ! ( $this->luhn_check( $card->number ) ) ) {
				throw new Exception( __( 'Not a valid card', 'wc-valorpay' ) );
			}
			if ( empty( $card->exp_month ) || empty( $card->exp_year ) || ! ctype_digit( (string) $card->exp_month ) || ! ctype_digit( (string) $card->exp_year ) || $card->exp_month > 12 || $card->exp_month < 1 || $card->exp_year < $current_year || ( $card->exp_year === $current_year && $card->exp_month < $current_month ) ) {
				throw new Exception( __( 'Card number  expired', 'wc-valorpay' ) );
			}
			if ( ! ctype_digit( $card->cvc ) ) {
				throw new Exception( __( 'Card security code is invalid (only digits are allowed)', 'wc-valorpay' ) );
			}

			return true;
		} catch ( Exception $e ) {
			wc_add_notice( $e->getMessage(), 'error' );
			return false;
		}
	}

	/**
	 * Output field name HTML
	 *
	 * Gateways which support tokenization do not require names - we don't want the data to post to the server.
	 *
	 * @since 1.0.0
	 * @param  string $name Field name.
	 * @return string
	 */
	public function field_name( $name ) {
		return ' name="' . esc_attr( $this->id . '-' . $name ) . '" ';
	}

	/**
	 * Valor Pay Terms and Conditions checkbox.
	 *
	 * @since 7.1.0
	 */
	public function valorpay_acknowledgement_form() {
		woocommerce_form_field(
			'valorpay_terms',
			array(
				'type'  => 'checkbox',
				'class' => array( 'input-checkbox' ),
				'label' => sprintf(
						/* translators: 1: Terms and Conditions URL. */
					__( 'I agree to the <a href="%s" target="_blank">Terms and Conditions</a>', 'wc-valorpay' ),
					esc_url(
						'https://valorpaytech.com/privacy-policy/'
					)
				),
			),
			1
		);
	}

	/**
	 * Address Verification Service Form .
	 *
	 * @since 1.0.0
	 */
	public function valorpay_avs_form() {
		echo '<fieldset>';
		woocommerce_form_field(
			'valorpay_avs_type',
			array(
				'type' => 'hidden',
			),
			$this->avs_type
		);
		$avs_zip_class    = ( 'zipandaddress' === $this->avs_type ) ? 'form-row-first' : 'form-row-wide';
		$avs_street_class = ( 'zipandaddress' === $this->avs_type ) ? 'form-row-last' : 'form-row-wide';
		if ( 'zip' === $this->avs_type || 'zipandaddress' === $this->avs_type ) {
			woocommerce_form_field(
				'valorpay_avs_zip',
				array(
					'type'        => 'text',
					'label'       => __( 'Zip Code', 'wc-valorpay' ),
					'placeholder' => __( 'Zip Code', 'wc-valorpay' ),
					'required'    => true,
					'maxlength'   => 6,
					'class'       => array( $avs_zip_class ),
				),
				''
			);
		}
		if ( 'address' === $this->avs_type || 'zipandaddress' === $this->avs_type ) {
			woocommerce_form_field(
				'valorpay_avs_street',
				array(
					'type'        => 'text',
					'label'       => __( 'Street Address', 'wc-valorpay' ),
					'placeholder' => __( 'Street Address', 'wc-valorpay' ),
					'required'    => true,
					'maxlength'   => 25,
					'class'       => array( $avs_street_class ),
				),
				''
			);
		}
		echo '</fieldset>';
	}

	/**
	 * Init payment gateway settings form fields
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function init_form_fields() {
		$this->form_fields = array(
			'enabled'                       => array(
				'title'       => __( 'Enable/Disable', 'wc-valorpay' ),
				'label'       => __( 'Enable Valor Pay', 'wc-valorpay' ),
				'type'        => 'checkbox',
				'description' => '',
				'default'     => 'no',
			),
			'title'                         => array(
				'title'       => __( 'Title', 'wc-valorpay' ),
				'type'        => 'text',
				'description' => __( 'This controls the title which the user sees during checkout.', 'wc-valorpay' ),
				'default'     => __( 'Valor Pay', 'wc-valorpay' ),
				'desc_tip'    => true,
			),
			'sandbox'                       => array(
				'title'       => __( 'Use Sandbox', 'wc-valorpay' ),
				'label'       => __( 'Enable sandbox mode - live payments will not be taken if enabled.', 'wc-valorpay' ),
				'type'        => 'checkbox',
				'description' => '',
				'default'     => 'no',
			),
			'appid'                         => array(
				'title'       => __( 'APP ID', 'wc-valorpay' ),
				'type'        => 'text',
				'description' => __( 'Please email isvsupport@valorpaytech.com to get to know about your APP ID, ( In demo mode APP ID is not needed )', 'wc-valorpay' ),
				'default'     => '',
			),
			'appkey'                        => array(
				'title'       => __( 'APP KEY', 'wc-valorpay' ),
				'type'        => 'text',
				'description' => __( 'Please email isvsupport@valorpaytech.com to get to know about your APP KEY, ( In demo mode APP KEY is not needed )', 'wc-valorpay' ),
				'default'     => '',
			),
			'epi'                           => array(
				'title'       => __( 'EPI', 'wc-valorpay' ),
				'type'        => 'text',
				'description' => __( 'Please email isvsupport@valorpaytech.com to get to know about your EPI ID, ( In demo mode EPI ID is not needed )', 'wc-valorpay' ),
				'default'     => '',
			),
			'card_type_allowed'             => array(
				'title'       => __( 'Allowed Card Type', 'wc-valorpay' ),
				'type'        => 'select',
				'class'       => 'chosen_select',
				'description' => __( 'Select the allowed card type for transactions', 'wc-valorpay' ),
				'css'         => 'width: 100px;',
				'options'     => array(
					'both'   => __( 'Both', 'wc-valorpay' ),
					'credit' => __( 'Credit', 'wc-valorpay' ),
					'debit'  => __( 'Debit', 'wc-valorpay' ),
				),
				'default'     => 'both',
			),
			'payment_action'                => array(
				'title'   => __( 'Payment Method', 'wc-valorpay' ),
				'type'    => 'select',
				'class'   => 'chosen_select',
				'css'     => 'width: 100px;',
				'options' => array(
					'sale' => 'Sale',
					'auth' => 'Auth Only',
				),
			),
			'surcharge_indicator'           => array(
				'title'       => __( 'Surcharge Mode', 'wc-valorpay' ),
				'label'       => __( 'Enable Surcharge Mode', 'wc-valorpay' ),
				'type'        => 'checkbox',
				'description' => __( 'Enable only if you want all transactions to be fall on surcharge mode, Merchant must have got an Surcharge MID inorder to work', 'wc-valorpay' ),
				'default'     => '',
			),
			'surcharge_type'                => array(
				'title'   => __( 'Surcharge Type', 'wc-valorpay' ),
				'label'   => __( 'Enable Surcharge Mode', 'wc-valorpay' ),
				'type'    => 'select',
				'default' => 'percentage',
				'options' => array(
					'percentage' => 'Surcharge %',
					'flatrate'   => 'Flat Rate $',
				),
			),
			'surcharge_label'               => array(
				'title'   => __( 'Surcharge Label', 'wc-valorpay' ),
				'label'   => __( 'Surcharge Label', 'wc-valorpay' ),
				'type'    => 'text',
				'default' => 'Surcharge Fee',
			),
			'surcharge_percentage'          => array(
				'title'       => __( 'Surcharge %', 'wc-valorpay' ),
				'type'        => 'decimal',
				'default'     => 0,
				'max'         => '10',
				'description' => __( 'Percentage will apply only on enabling on surcharge Indicator to true and Surcharge type is set fo Surcharge %', 'wc-valorpay' ),
			),
			'surcharge_flat_rate'           => array(
				'title'       => __( 'Flat Rate $', 'wc-valorpay' ),
				'type'        => 'decimal',
				'default'     => 0,
				'description' => __( 'Flat rate  will apply only on if Enable surcharge mode is true and Surcharge type is set to Flat Rate $', 'wc-valorpay' ),
			),
			'surcharge_for_debit'           => array(
				'title'       => __( 'Surcharge For Debit', 'wc-valorpay' ),
				'label'       => __( 'Enable Surcharge For Debit', 'wc-valorpay' ),
				'type'        => 'checkbox',
				'description' => __( 'Enable surcharge for debit', 'wc-valorpay' ),
				'default'     => 'yes',
			),
			'avs_type'                      => array(
				'title'       => __( 'AVS', 'wc-valorpay' ),
				'type'        => 'select',
				'default'     => 'percentage',
				'class'       => 'wc-enhanced-select',
				'options'     => array(
					'none'          => 'None',
					'zip'           => 'Zip Only',
					'address'       => 'Address Only',
					'zipandaddress' => 'Zip & Address',
				),
				'description' => __( 'The address verification service will add a text field to the checkout page based on the above option.', 'wc-valorpay' ),
			),
			'enable_l2_l3'                  => array(
				'title'       => __( 'Enable L2 & L3 Processing', 'wc-valorpay' ),
				'label'       => __( 'Enable L2 & L3 Processing', 'wc-valorpay' ),
				'type'        => 'checkbox',
				'description' => __( 'Enable L2 & L3 processing for detailed data', 'wc-valorpay' ),
				'default'     => 'no',
			),
			'disable_payment_on_failed'     => array(
				'title'       => __( 'Payment Failed Tracker', 'wc-valorpay' ),
				'label'       => __( 'Enable Protection', 'wc-valorpay' ),
				'type'        => 'checkbox',
				'description' => sprintf(
					/* translators: 1: Tracker URL. */
					__( 'Disable the payment method in the checkout page for failed transactions. <a id="valorpay-goto-tracker" href="%s">Unblock IP</a>', 'wc-valorpay' ),
					esc_url( '#valor-pos-tracker' )
				),
				'default'     => 'no',
			),
			'disable_payment_decline_count' => array(
				'title'   => __( 'Declined Transaction Count', 'wc-valorpay' ),
				'desc'    => __( 'Number of declined transaction count.', 'wc-valorpay' ),
				'class'   => 'wc-enhanced-select',
				'type'    => 'select',
				'options' => array(
					'3' => __( '3', 'wc-valorpay' ),
					'5' => __( '5', 'wc-valorpay' ),
					'6' => __( '6', 'wc-valorpay' ),
				),
			),
			'disable_payment_decline_time'  => array(
				'title'   => __( 'Block Payment For', 'wc-valorpay' ),
				'desc'    => __( 'Minutes to block payment gateway in checkout.', 'wc-valorpay' ),
				'class'   => 'wc-enhanced-select',
				'type'    => 'select',
				'options' => array(
					'1'    => __( '1 min', 'wc-valorpay' ),
					'5'    => __( '5 min', 'wc-valorpay' ),
					'10'   => __( '10 min', 'wc-valorpay' ),
					'60'   => __( '1 hour', 'wc-valorpay' ),
					'180'  => __( '3 hour', 'wc-valorpay' ),
					'300'  => __( '5 hour', 'wc-valorpay' ),
					'600'  => __( '10 hour', 'wc-valorpay' ),
					'1440' => __( '1 day', 'wc-valorpay' ),
				),
			),
			'cardtypes'                     => array(
				'title'    => __( 'Accepted Cards', 'wc-valorpay' ),
				'type'     => 'multiselect',
				'class'    => 'chosen_select',
				'css'      => 'width: 350px;',
				'desc_tip' => __( 'Select the card types to accept.', 'wc-valorpay' ),
				'options'  => array(
					'visa'       => 'Visa',
					'mastercard' => 'MasterCard',
					'amex'       => 'American Express',
					'discover'   => 'Discover',
					'jcb'        => 'JCB',
					'diners'     => 'Diners Club',
				),
				'default'  => array( 'visa', 'mastercard', 'amex', 'discover' ),
			),
		);
	}

	/**
	 * Surcharge admin validation.
	 *
	 * @since 7.2.0
	 */
	public function process_admin_options() {
		$is_error  = false;
		$post_data = $this->get_post_data();
		if ( isset( $post_data['woocommerce_wc_valorpay_surcharge_percentage'] ) && $post_data['woocommerce_wc_valorpay_surcharge_percentage'] > self::WC_VALORPAY_MAX_SURCHARGE_PERCENT ) {
			$is_error = true;
			/* translators: 1: Maximum percentage. */
			WC_Admin_Settings::add_error( sprintf( __( 'Surcharge percentage cannot be more than %s', 'wc-valorpay' ), self::WC_VALORPAY_MAX_SURCHARGE_PERCENT ) );
		}
		if ( isset( $post_data['woocommerce_wc_valorpay_surcharge_flat_rate'] ) && $post_data['woocommerce_wc_valorpay_surcharge_flat_rate'] > self::WC_VALORPAY_MAX_SURCHARGE_FLATRATE ) {
			$is_error = true;
			/* translators: 1: Maximum flat rate. */
			WC_Admin_Settings::add_error( sprintf( __( 'Surcharge flat rate cannot be more than %s', 'wc-valorpay' ), self::WC_VALORPAY_MAX_SURCHARGE_FLATRATE ) );
		}
		if ( ! $is_error ) {
			parent::process_admin_options();
		}
	}

	/**
	 * Get payment card icon
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_icon() {
		$icon = '';
		if ( is_array( $this->cardtypes ) ) {
			$card_types = $this->cardtypes;
			foreach ( $card_types as $card_type ) {
				$icon .= '<img src="' . esc_url( WC_HTTPS::force_https_url( WC()->plugin_url() . '/assets/images/icons/credit-cards/' . $card_type . '.svg' ) ) . '" alt="' . $card_type . '" width="40" height="25" style="width: 40px; height: 25px;" />';
			}
		}
		return apply_filters( 'woocommerce_gateway_icon', $icon, $this->id );
	}

	/**
	 * Payment process function.
	 *
	 * @access public
	 * @since 4.0.2
	 * @param mixed $order_id Order ID.
	 * @throws Exception When a payment failed.
	 */
	public function process_payment( $order_id ) {
		try {
			global $woocommerce;
			$order  = wc_get_order( $order_id );
			$amount = $order->get_total();
			$card   = null;

			if ( isset( $_POST['wc-wc_valorpay-payment-token'] ) && 'new' !== sanitize_text_field( wp_unslash( $_POST['wc-wc_valorpay-payment-token'] ) ) ) { // phpcs:ignore
				$token_id = sanitize_text_field( wp_unslash( $_POST['wc-wc_valorpay-payment-token'] ) ); // phpcs:ignore
				$card     = WC_Payment_Tokens::get( $token_id );
				// Return if card does not belong to current user.
				if ( $card->get_user_id() !== get_current_user_id() ) {
					throw new Exception( __( 'Invalid card information.', 'wc-valorpay' ) );
				}
			}

			$valorpay_api = new WC_ValorPay_API( $this );
			// Check if subscription order or if order total is zero, then add the card token to subscriptions.
			if ( class_exists( 'WC_Subscriptions_Order' ) && WC_Subscriptions_Order::order_contains_subscription( $order->get_id() ) && '0.00' === $amount ) {
				if ( $card ) {
					$subscription_payload              = new stdClass();
					$subscription_payload->token       = $card->get_token();
					$subscription_payload->expiry_date = $card->get_expiry_month() . substr( $card->get_expiry_year(), -2 );
					$is_debit_card                     = 'D' === WC()->session->get( 'valor_card_type' );
				} else {
					$card_number = str_replace( ' ', '', ( isset( $_POST['wc_valorpay-card-number'] ) ) ? sanitize_text_field( wp_unslash( $_POST['wc_valorpay-card-number'] ) ) : '' ); // phpcs:ignore
					$exp_date_array = explode( '/', sanitize_text_field( wp_unslash( $_POST['wc_valorpay-card-expiry'] ) ) ); // phpcs:ignore
					$exp_month           = trim( $exp_date_array[0] );
					$exp_year            = trim( $exp_date_array[1] );
					$exp_date            = $exp_month . substr( $exp_year, -2 );
					$card_detail         = array(
						'card_num'    => $card_number,
						'card_expiry' => $exp_date,
					);
					$card_token_response = $valorpay_api->generate_card_token( $order, $card_detail );
					if ( isset( $card_token_response['error'] ) ) {
						throw new Exception( $card_token_response['error'] );
					}
					$subscription_payload              = new stdClass();
					$subscription_payload->token       = $card_token_response['token'];
					$subscription_payload->expiry_date = $exp_month . substr( $exp_year, -2 );
					$is_debit_card                     = 'D' === WC()->session->get( 'valor_card_type' );
				}
				$this->update_subscription_card_token( $order, $subscription_payload, $is_debit_card );
				$order->payment_complete();
				$order->add_order_note( __( 'Valor Pay: Card token added to subscription.', 'wc-valorpay' ) );
				return array(
					'result'   => 'success',
					'redirect' => $this->get_return_url( $order ),
				);
			}
			// Call the sale or authorization API.
			$response = $valorpay_api->purchase( $order, $amount, $card );
			if ( isset( $response->error_no ) && 'S00' === $response->error_no ) {
				$trans_id = $response->txnid;
				$order->payment_complete( $trans_id );
				$woocommerce->cart->empty_cart();
				$amount_approved = number_format( $amount, '2', '.', '' );
				$message         = 'auth' === $this->payment_action ? 'authorized' : 'completed';
				$order->add_order_note(
					sprintf(
						/* translators: 1: Error Message, 2: Amount, 3: Line Break, 4: Approval Code, 5: Line Break, 6: RRN Number. */
						__( 'Valor Pay %1$s for %2$s.%3$s <strong>Approval Code:</strong> %4$s.%5$s <strong>RRN:</strong> %6$s', 'wc-valorpay' ),
						$message,
						$amount_approved,
						"\n\n",
						$response->approval_code,
						"\n\n",
						$response->rrn
					)
				);
				// Save the card if possible .
				if ( is_user_logged_in() && isset( $_POST['wc-wc_valorpay-new-payment-method'] ) && sanitize_text_field( wp_unslash( $_POST['wc-wc_valorpay-new-payment-method'] ) ) && isset( $_POST['wc_valorpay-card-expiry'] ) && sanitize_text_field( wp_unslash( $_POST['wc_valorpay-card-expiry'] ) ) ) { // phpcs:ignore
					$exp_date_array = explode( '/', sanitize_text_field( wp_unslash( $_POST['wc_valorpay-card-expiry'] ) ) ); // phpcs:ignore
					$exp_month           = trim( $exp_date_array[0] );
					$exp_year            = trim( $exp_date_array[1] );
					$exp_date            = $exp_month . substr( $exp_year, -2 );
					$response->card_type = isset( $response->card_brand ) && $response->card_brand ? $response->card_brand : $valorpay_api->get_card_type( str_replace( ' ', '', sanitize_text_field( wp_unslash( $_POST['wc_valorpay-card-number'] ) ) ) ); // phpcs:ignore
					$this->save_card( $response, $exp_date, ( 'D' === WC()->session->get( 'valor_card_type' ) ) ? 1 : 0 );
				}

				$tran_meta = array(
					'transaction_id' => $response->txnid,
					'payment_action' => $this->payment_action,
					'token'          => $response->token,
					'rrn'            => $response->rrn,
					'approval_code'  => $response->approval_code,
				);
				$order->add_meta_data( '_valorpay_transaction', $tran_meta );
				$order->save();
				$is_debit_card = 'D' === WC()->session->get( 'valor_card_type' );
				if ( class_exists( 'WC_Subscriptions_Order' ) && WC_Subscriptions_Order::order_contains_subscription( $order->get_id() ) ) {
					$this->update_subscription_card_token( $order, $response, $is_debit_card );
				}
				$this->removed_failed_count_by_ip();
				// Return thankyou redirect.
				return array(
					'result'   => 'success',
					'redirect' => $this->get_return_url( $order ),
				);
			} else {
				if ( isset( $response->errors['valorpay_error'][0] ) ) {
					/* translators: %s: API Error Message. */
					$order->add_order_note( sprintf( __( 'Payment error: %s', 'wc-valorpay' ), $response->errors['valorpay_error'][0] ) );
					$this->add_failed_count_by_ip( $order );
					/* translators: %s: API Error Message. */
					throw new Exception( sprintf( __( 'Payment error: %s', 'wc-valorpay' ), $response->errors['valorpay_error'][0] ) );
				}
				throw new Exception( __( 'Unable to process the transaction using Valor Pay, please try again.', 'wc-valorpay' ) );
			}
		} catch ( Exception $e ) {
			wc_add_notice( $e->getMessage(), 'error' );

			return array(
				'result'   => 'fail',
				'redirect' => '',
			);
		}
	}

	/**
	 * Save subscrption card token.
	 *
	 * @param WC_Order $order Order.
	 * @param object   $response Response.
	 * @param bool     $is_debit_card Is Debit Card.
	 * @return void
	 */
	protected function update_subscription_card_token( $order, $response, $is_debit_card ) {
		$subscriptions = wcs_get_subscriptions_for_order( $order );
		foreach ( $subscriptions as $subscription ) {
			$valorpay_avs_zip    = ( isset( $_POST['valorpay_avs_zip'] ) && $_POST['valorpay_avs_zip'] ) ? sanitize_text_field( wp_unslash( $_POST['valorpay_avs_zip'] ) ) : ''; // phpcs:ignore
			$valorpay_avs_street = ( isset( $_POST['valorpay_avs_street'] ) && $_POST['valorpay_avs_street'] ) ? sanitize_text_field( wp_unslash( $_POST['valorpay_avs_street'] ) ) : ''; // phpcs:ignore
			$card_expiry         = isset( $response->expiry_date ) ? str_replace( '/', '', $response->expiry_date ) : '';
			$subscription->update_meta_data( '_valorpay_card_token', $response->token );
			$subscription->update_meta_data( '_valorpay_card_type', $is_debit_card ? 'D' : 'C' );
			if ( $card_expiry ) {
				$subscription->update_meta_data( '_valorpay_card_expiry', $card_expiry );
			}
			if ( $valorpay_avs_zip ) {
				$subscription->update_meta_data( '_valorpay_avs_zip', $valorpay_avs_zip );
			}
			if ( $valorpay_avs_street ) {
				$subscription->update_meta_data( '_valorpay_avs_address', $valorpay_avs_street );
			}
			$subscription->save_meta_data();
			$subscription->save();
		}
	}

	/**
	 * Save card.
	 *
	 * @param object $response Response.
	 * @param string $exp_date Expiry Date.
	 * @param int    $is_debit Is Debit Card.
	 * @return void
	 */
	protected function save_card( $response, $exp_date, $is_debit ) {
		if ( $response->token ) {
			$token = new WC_Payment_Token_CC();
			$token->set_card_type( strtolower( $response->card_type ) );
			$token->set_gateway_id( self::GATEWAY_ID );
			$token->set_token( $response->token );
			$token->set_last4( substr( $response->pan, -4 ) );
			$token->set_expiry_month( substr( $exp_date, 0, 2 ) );
			$token->set_expiry_year( '20' . substr( $exp_date, -2 ) );
			$token->set_user_id( get_current_user_id() );
			$token->add_meta_data( 'is_debit', $is_debit );
			$token->save();
		}
	}

	/**
	 * Track failed order count
	 *
	 * @since 1.0.0
	 *
	 * @param WC_Order $order Order object.
	 * @return bool
	 */
	public function add_failed_count_by_ip( $order ) {
		if ( 'yes' !== $this->disable_payment_on_failed ) {
			return false;
		}
		$customer_ip            = WC_Geolocation::get_ip_address();
		$payment_failed_tracker = get_option( WC_VALORPAY_FAILED_PAYMENT_TRACKER, array() );
		if ( isset( $payment_failed_tracker[ $customer_ip ] ) ) {
			$increment_count                        = $payment_failed_tracker[ $customer_ip ]['count'] + 1;
			$payment_failed_tracker[ $customer_ip ] = array(
				'count'         => $increment_count,
				'last_failed'   => time(),
				'block_payment' => ( $increment_count >= $this->disable_payment_decline_count ),
			);
			if ( $payment_failed_tracker[ $customer_ip ]['block_payment'] ) {
				$block_time = $payment_failed_tracker[ $customer_ip ]['last_failed'] + ( $this->disable_payment_decline_time * MINUTE_IN_SECONDS );
				$order->add_order_note(
					sprintf(
						/* translators: 1: Disable Time, 2: Unblock IP URL, 3: Customer IP. */
						__( 'Valor Pay method is disabled for %1$s. <a target="_blank" href="%2$s">To Unblock IP</a> %3$s', 'wc-valorpay' ),
						human_time_diff( $payment_failed_tracker[ $customer_ip ]['last_failed'], $block_time ),
						admin_url( 'admin.php?page=wc-settings&tab=checkout&section=wc_valorpay#valorpay-tracker' ),
						$customer_ip
					)
				);
			}
		} else {
			$payment_failed_tracker[ $customer_ip ] = array(
				'count'         => 1,
				'last_failed'   => time(),
				'block_payment' => false,
			);
		}
		update_option( WC_VALORPAY_FAILED_PAYMENT_TRACKER, $payment_failed_tracker );
		return true;
	}

	/**
	 * Remove IP from tracker on success.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public function removed_failed_count_by_ip() {
		$customer_ip            = WC_Geolocation::get_ip_address();
		$payment_failed_tracker = get_option( WC_VALORPAY_FAILED_PAYMENT_TRACKER, array() );
		if ( ! isset( $payment_failed_tracker[ $customer_ip ] ) ) {
			return false;
		}
		unset( $payment_failed_tracker[ $customer_ip ] );
		update_option( WC_VALORPAY_FAILED_PAYMENT_TRACKER, $payment_failed_tracker );
		return true;
	}

	/**
	 * Can the order be refunded via PayPal?
	 *
	 * @since 1.0.0
	 *
	 * @param  WC_Order $order Order object.
	 * @return bool
	 */
	public function can_refund_order( $order ) {
		$has_api_details     = $this->appid && $this->appkey && $this->epi;
		$valorpay_order_meta = $order->get_meta( '_valorpay_transaction' );
		return $order && $order->get_transaction_id() && $has_api_details && is_array( $valorpay_order_meta ) && 'sale' === $valorpay_order_meta['payment_action'];
	}

	/**
	 * Process refund function.
	 *
	 * @access public
	 * @since 1.0.0
	 * @param int    $order_id Order Id.
	 * @param float  $amount Order Amount.
	 * @param string $reason Refund reason.
	 * @return bool|WP_Error
	 */
	public function process_refund( $order_id, $amount = null, $reason = '' ) {
		$order = wc_get_order( $order_id );
		if ( ! $this->can_refund_order( $order ) ) {
			return new WP_Error( 'error', __( 'Refund failed.', 'wc-valorpay' ) );
		}
		$valorpay_api = new WC_ValorPay_API( $this );
		$response     = array();
		$response     = $valorpay_api->refund( $order, $amount );
		if ( is_wp_error( $response ) ) {
			return $response;
		}
		if ( isset( $response->error_no ) && 'S00' === $response->error_no ) {
			$order->add_order_note(
				sprintf(
					/* translators: 1: Amount, 2: Line Break, 3: Approval code, 4: Line Break, 5: RRN Code */
					__( 'Valor Pay Refund for %1$s.%2$s <strong>Approval Code:</strong> %3$s.%4$s <strong>RRN:</strong> %5$s', 'wc-valorpay' ),
					$response->amount,
					"\n\n",
					$response->desc,
					"\n\n",
					$response->rrn
				)
			);
			return true;
		}
		return false;
	}

	/**
	 * Add payment method via account screen
	 *
	 * @since 7.5.0
	 * @return array
	 */
	public function add_payment_method() {
		$result = array();
		try {
			$valorpay_api = new WC_ValorPay_API( $this );
			$card         = $this->get_posted_card();
			$response     = $valorpay_api->add_payment_generate_card_token( $card );
			if ( isset( $response['token'] ) ) {
				$card_info            = new stdClass();
				$card_info->card_type = $response['card_brand'] ? $response['card_brand'] : $valorpay_api->get_card_type( str_replace( ' ', '', sanitize_text_field( wp_unslash( $card->number ) ) ) );
				$card_info->token     = $response['token'];
				$card_info->pan       = $card->number;
				$card_expiry          = $card->exp_month . substr( $card->exp_year, -2 );
				$is_debit             = 'D' === $response['card_type'] ? 1 : 0;
				$this->save_card( $card_info, $card_expiry, $is_debit );
				$result['result']   = 'success';
				$result['redirect'] = wc_get_account_endpoint_url( 'payment-methods' );
			} else {
				$result['result'] = 'failure';
			}
		} catch ( Exception $error ) {
			$result['result'] = 'failure';
		}
		return $result;
	}

}
