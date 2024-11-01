/**
 * On checkout update fee on payment method change
 *
 * @package    Wc_Valorpay
 * @subpackage Wc_Valorpay/public
 */

(function ($) {
	"use strict";
	var wc_valorpay_form = {
		validCard: false,
		validCardExpiry: false,
		validCvc: false,
		checkoutFormClass: "form.woocommerce-checkout",
		cardNumberInput: "#wc_valorpay-card-number",
		cardExpiryInput: "#wc_valorpay-card-expiry",
		cardCvvInput: "#wc_valorpay-card-cvc",
		clientTokenInit: false,
		clientToken: "",
		tokenGenerateInterval: null,
		tokenExpiryMin: 5,
		prevSelectedToken: false,
		/**
		 * Initialize.
		 */
		init: function () {
			// On payment method switch update checkout.
			$( document.body ).on(
				"change",
				'input[name="payment_method"]',
				this.updateCheckout
			);
			$( document.body ).on(
				"click",
				'input[name="wc-wc_valorpay-payment-token"]',
				this.surChargeCalculation
			);
			this.validateCardForm();
			this.createPageToken();
			this.startReCreatePageToken();
			$( wc_valorpay_form.checkoutFormClass ).on(
				"checkout_place_order_wc_valorpay",
				this.valorPayPlaceOrder
			);
		},
		/**
		 * On payment gateway change update the checkout.
		 */
		updateCheckout: function () {
			$( document.body ).trigger( "update_checkout" );
		},
		/**
		 * On token switch
		 */
		surChargeCalculation: function () {
			const checkedValue  = $('input[name="wc-wc_valorpay-payment-token"]:checked').val();
			if(wc_valorpay_form.prevSelectedToken !=  checkedValue) {
				if((checkedValue === 'new' && !$( wc_valorpay_form.cardNumberInput ).val()) || checkedValue !== 'new') {
					wc_valorpay_form.tokenCardType(checkedValue);
				} else {
					$( wc_valorpay_form.cardNumberInput ).trigger( "blur" );
				}
				wc_valorpay_form.prevSelectedToken = checkedValue;
			}
		},
		/**
		 * Block checkout page UI.
		 */
		block: function () {
			$( wc_valorpay_form.checkoutFormClass ).block(
				{
					message: null,
					overlayCSS: {
						background: "#fff",
						opacity: 0.6,
					},
				}
			);
		},
		/**
		 * Unblock checkout page UI.
		 */
		unblock: function () {
			$( wc_valorpay_form.checkoutFormClass ).unblock();
		},
		/**
		 * Create page or client token.
		 */
		createPageToken: function () {
			$.ajax(
				{
					type: "POST",
					url: valorpay_checkout_object.wc_ajax_action
					.toString()
					.replace( "%%endpoint_url%%", valorpay_checkout_object.ct_ajax_action ),
					data: {
						nonce: valorpay_checkout_object.ct_ajax_nonce,
					},
					success: function (response) {
						if (response.success) {
							wc_valorpay_form.clientToken = response.data.token;
						}
					},
				}
			);
		},
		/**
		 * Bin lookup.
		 */
		binLookUp: function () {
			var cardNumber = $( wc_valorpay_form.cardNumberInput )
			.val()
			.replace( / /g, "" );
			$.ajax(
				{
					type: "POST",
					url: valorpay_checkout_object.wc_ajax_action
					.toString()
					.replace( "%%endpoint_url%%", valorpay_checkout_object.bl_ajax_action ),
					data: {
						nonce: valorpay_checkout_object.bl_ajax_nonce,
						client_token: wc_valorpay_form.clientToken,
						bin: cardNumber.substring(0, 6)
					},
					success: function (response) {
						if (response.success && response.data) {
							wc_valorpay_form.updateCheckout();
						}
					},
				}
			);
		},
		/**
		 * Token card type.
		 */
		tokenCardType: function (tokenId) {
			$.ajax(
				{
					type: "POST",
					url: valorpay_checkout_object.wc_ajax_action
					.toString()
					.replace( "%%endpoint_url%%", valorpay_checkout_object.card_type_ajax_action ),
					data: {
						nonce: valorpay_checkout_object.card_type_ajax_nonce,
						token_id: tokenId,
					},
					success: function (response) {
						if (response.success && response.data) {
							wc_valorpay_form.updateCheckout();
						}
					},
				}
			);
		},
		/**
		 * Recreate page token on every token expiry minutes.
		 */
		startReCreatePageToken: function () {
			wc_valorpay_form.tokenGenerateInterval = setInterval(
				function () {
					wc_valorpay_form.createPageToken();
				},
				wc_valorpay_form.tokenExpiryMin * 60 * 1000
			);
		},
		/**
		 * Convert expiry date to MMYY format.
		 */
		convertToMMYY: function (dateString) {
			// Remove any leading/trailing spaces.
			dateString = dateString.trim();

			// Split the string into month and year parts.
			var parts = dateString.split( "/" );
			var month = parseInt( parts[0] );
			var year  = parseInt( parts[1] );

			// Extract the year part based on the input format.
			if (year < 100) {
				// Format is MM/YY .
				year += 2000; // Assuming years below 100 are in the 21st century.
			}

			// Format the month and year as MMYY .
			var formattedDate = ("0" + month).slice( -2 ) + ("0" + year).slice( -2 );

			return formattedDate;
		},
		/**
		 * Validate card checkout field.
		 */
		validateCardForm: function () {
			this.validateCardNumber();
			this.validateCardExpiry();
			this.validateCardCvv();
		},
		/**
		 * Add error to form field.
		 *
		 * @param {jQuery} currentEle The current element.
		 * @param {string} errorMsg Error Message.
		 */
		addErrorMessage: function (currentEle, errorMsg) {
			currentEle.addClass( "error-class" );
			if ( ! currentEle.next().hasClass( "error-message" )) {
				$(
					'<span class="error-message" style="color:red;">' +
					errorMsg +
					"</span>"
				).insertAfter( currentEle );
				currentEle.closest( ".form-row" ).addClass( "woocommerce-invalid" );
			}
		},
		/**
		 * Remove error from the form field.
		 *
		 * @param {jQuery} currentEle The current element.
		 */
		removeErrorMessage: function (currentEle) {
			currentEle.removeClass( "error-class" );
			currentEle.next( ".error-message" ).remove();
			currentEle.closest( ".form-row" ).removeClass( "woocommerce-invalid" );
		},
		/**
		 * Validate card number field.
		 */
		validateCardNumber: function () {
			$( "body" ).on(
				"blur",
				this.cardNumberInput,
				function () {
					var cardNum = $( this ).val().replace( / /g, "" );
					var isValid = wc_valorpay_form.luhnCheck( cardNum );

					if (cardNum === "") {
						wc_valorpay_form.addErrorMessage(
							$( this ),
							valorpay_checkout_object.error_card
						);
						wc_valorpay_form.validCard = false;
					} else if ( ! isValid) {
						wc_valorpay_form.addErrorMessage(
							$( this ),
							valorpay_checkout_object.invalid_card
						);
						wc_valorpay_form.validCard = false;
					} else {
						wc_valorpay_form.removeErrorMessage( $( this ) );
						wc_valorpay_form.validCard = true;
						wc_valorpay_form.binLookUp();
					}
				}
			);
			$( "body" ).on(
				"focus",
				this.cardNumberInput,
				function () {
					if ( ! $( this ).val()) {
						wc_valorpay_form.removeErrorMessage( $( this ) );
					}
				}
			);
		},
		/**
		 * Validate card expiry field.
		 */
		validateCardExpiry: function () {
			$( "body" ).on(
				"blur",
				this.cardExpiryInput,
				function () {
					var expiry = $( this ).val().replace( / /g, "" );
					if (expiry === "") {
						wc_valorpay_form.addErrorMessage(
							$( this ),
							valorpay_checkout_object.invalid_expiry
						);
						wc_valorpay_form.validCardExpiry = false;
					} else {
						var parts = expiry.split( "/" );
						var month = parseInt( parts[0], 10 );
						var year  = parseInt( parts[1], 10 );
						if (year < 100) {
							year += 2000;
						}
						if ( ! wc_valorpay_form.checkCardExpiry( month, year )) {
							wc_valorpay_form.addErrorMessage(
								$( this ),
								valorpay_checkout_object.expiry_card
							);
							wc_valorpay_form.validCardExpiry = false;
						} else {
							wc_valorpay_form.removeErrorMessage( $( this ) );
							wc_valorpay_form.validCardExpiry = true;
						}
					}
				}
			);

			$( "body" ).on(
				"focus",
				this.cardExpiryInput,
				function () {
					wc_valorpay_form.removeErrorMessage( $( this ) );
				}
			);
		},
		/**
		 * Validate card CVV field.
		 */
		validateCardCvv: function () {
			$( "body" ).on(
				"blur",
				this.cardCvvInput,
				function () {
					var cvcNum = $( this ).val().trim();
					if (cvcNum === "") {
						wc_valorpay_form.addErrorMessage(
							$( this ),
							valorpay_checkout_object.error_cvv
						);
						wc_valorpay_form.validCvc = false;
					} else if (cvcNum.length != 3 && cvcNum.length != 4) {
						wc_valorpay_form.addErrorMessage(
							$( this ),
							valorpay_checkout_object.invalid_cvv
						);
						wc_valorpay_form.validCvc = false;
					} else {
						wc_valorpay_form.removeErrorMessage( $( this ) );
						wc_valorpay_form.validCvc = true;
					}
				}
			);
			$( "body" ).on(
				"focus",
				this.cardCvvInput,
				function () {
					wc_valorpay_form.removeErrorMessage( $( this ) );
				}
			);
		},
		/**
		 * Validate field on place order
		 */
		valorPayPlaceOrder: function (event) {
			var cardNumb  = $( wc_valorpay_form.cardNumberInput ).val();
			const isNewCard = $( "#wc-wc_valorpay-payment-token-new" );
			if (isNewCard.length && ! isNewCard.is( ":checked" )) {
				var avsErrorIsValidSave = wc_valorpay_form.avsValidation();
				if (avsErrorIsValidSave) {
                    wc_valorpay_form.scrollToValorForm();
                    return false;
				}
                return true;
			}
			if (cardNumb === "") {
				$( wc_valorpay_form.cardNumberInput ).trigger( "blur" );
			} else {
				$( wc_valorpay_form.cardNumberInput ).trigger( "blur" );
				$( wc_valorpay_form.cardExpiryInput ).trigger( "blur" );
				$( wc_valorpay_form.cardCvvInput ).trigger( "blur" );
			}
			if (wc_valorpay_form.validCard === false) {
				wc_valorpay_form.scrollToValorForm();
				return false;
			}
			if (wc_valorpay_form.validCardExpiry === false) {
				wc_valorpay_form.scrollToValorForm();
				return false;
			}
			if (wc_valorpay_form.validCvc === false) {
				wc_valorpay_form.scrollToValorForm();
				return false;
			}

			if (cardNumb !== "") {
				var avsErrorIsValid = wc_valorpay_form.avsValidation();
				if (avsErrorIsValid) {
                    wc_valorpay_form.scrollToValorForm();
					return false;
				}
				return true;
			}
		},
		/**
		 * Validate Address Verification Service
		 */
		avsValidation: function () {
			var hasError = false;
			if ($( 'input[name="valorpay_avs_zip"]' ).length) {
				wc_valorpay_form.removeErrorMessage(
					$( 'input[name="valorpay_avs_zip"]' )
				);
				if ($( 'input[name="valorpay_avs_zip"]' ).val() === "") {
						wc_valorpay_form.addErrorMessage(
							$( 'input[name="valorpay_avs_zip"]' ),
							valorpay_checkout_object.avs_zip_error
						);
						hasError = true;
				} else if ($( 'input[name="valorpay_avs_zip"]' ).val().length < 4) {
					wc_valorpay_form.addErrorMessage(
						$( 'input[name="valorpay_avs_zip"]' ),
						valorpay_checkout_object.invalid_avs_zip_error
					);
					hasError = true;
				}
				$( 'input[name="valorpay_avs_zip"]' ).focus(
					function () {
						wc_valorpay_form.removeErrorMessage(
							$( 'input[name="valorpay_avs_zip"]' )
						);
					}
				);
			}

			if ($( 'input[name="valorpay_avs_street"]' ).length) {
				wc_valorpay_form.removeErrorMessage(
					$( 'input[name="valorpay_avs_street"]' )
				);
				if ($( 'input[name="valorpay_avs_street"]' ).val() === "") {
					wc_valorpay_form.addErrorMessage(
						$( 'input[name="valorpay_avs_street"]' ),
						valorpay_checkout_object.avs_street_error
					);
					hasError = true;
				}
				$( 'input[name="valorpay_avs_street"]' ).focus(
					function () {
						wc_valorpay_form.removeErrorMessage(
							$( 'input[name="valorpay_avs_street"]' )
						);
					}
				);
			}

			return hasError;
		},
		/**
		 * Check if the card expiry.
		 *
		 * @param {number} month Card month
		 * @param {number} year Card year
		 * @return {boolean}
		 */
		checkCardExpiry: function (month, year) {
			var currentTime = new Date();
			var expiry      = new Date( year, month, 1 );
			if (expiry < currentTime) {
				return false;
			}
			return true;
		},
		/**
		 * Luhn check.
		 *
		 * @param {string} num Card number
		 * @return {boolean}
		 */
		luhnCheck: function (num) {
			var digit, digits, odd, sum, _i, _len;
			odd    = true;
			sum    = 0;
			digits = (num + "").split( "" ).reverse();
			for (_i = 0, _len = digits.length; _i < _len; _i++) {
				digit = digits[_i];
				digit = parseInt( digit, 10 );
				if ((odd = ! odd)) {
					digit *= 2;
				}
				if (digit > 9) {
					digit -= 9;
				}
				sum += digit;
			}
			return sum % 10 === 0.0;
		},
		/**
		 * Scroll to payment form.
		 */
		scrollToValorForm: function () {
			$( "html, body" ).animate(
				{
					scrollTop: $( "#wc-wc_valorpay-cc-form" ).offset().top,
				},
				1000
			);
		},
	};
	$( document ).ready(
		function () {
			// Initialize the wc_test_form object.
			wc_valorpay_form.init();
		}
	);
})( jQuery );
