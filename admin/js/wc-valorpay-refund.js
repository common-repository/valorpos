/**
 * On checkout update fee on payment method change
 *
 * @package    Wc_Valorpay
 * @subpackage Wc_Valorpay/admin
 */

(function( $ ) {
	'use strict';

	/**
	 * Valor 2FA Popup.
	 */
	// TODO: Improve the coding standard.
	var isTimerActive     = false;
	var otpTimerInSeconds = 120;
	var interVal;
	/**
	 * Refund related actions.
	 */
	$( document ).ready(
		function() {
			$( "#woocommerce-order-items" ).on(
				'click',
				'button.refund-items',
				function () {
					$( 'button.do-api-refund' ).removeClass( 'do-api-refund' ).addClass( 'do-api-refund-valorpay' ).before( '<span id="valorpay-refund-load" class="spinner" style="display: none;"></span><button type="button" class="button button-primary do-api-refund" style="display: none;"></button>' );
				}
			);
			$( document ).on(
				'click',
				'button.do-api-refund-valorpay',
				function(e) {
					e.preventDefault();
					var self = $( this );
					self.prop( 'disabled', true );
					$( "#valorpay-refund-load" ).show().addClass( "is-active" );
					valorpay_2fa_otp.generate_otp().then(
						function([result, data]) {
							$( "#valorpay-refund-load" ).hide().removeClass( "is-active" );
							self.prop( 'disabled', false );
							if (result) {
								$( self ).WCBackboneModal(
									{
										template: 'wc-modal-add-valorpay-popup',
										variable : data
									}
								);
								valorpay_otp_timer.timer_completed();
								valorpay_otp_timer.start_timer( otpTimerInSeconds, $( '#valor-otp-timer' ) );
							} else if (result != null) {
								$( '.do-api-refund' ).trigger( 'click' );
							}
						}
					).catch(
						function(err) {
							// Run this when promise was rejected via reject().
							console.log( err )
						}
					);
					return false;
				}
			);
			$( document ).on(
				'click',
				'button#btn-valor-otp',
				function(e) {
					$( '#btn-valor-otp-submit' ).trigger( 'click' );
				}
			);
			$( document ).on(
				'click',
				'button#btn-valor-resend',
				function(e) {
					var self = $( this );
					self.prop( 'disabled', true );
					$( "#valor-resend-load" ).show().addClass( "is-active" );
					if ( ! isTimerActive) {
						valorpay_2fa_otp.generate_otp().then(
							function([result, data]) {
								if (result) {
									valorpay_otp_timer.timer_completed();
									valorpay_otp_timer.start_timer( otpTimerInSeconds, $( '#valor-otp-timer' ) );
									valorpay_popup_buttons.toggle();
								}
								$( "#valor-resend-load" ).hide().removeClass( "is-active" );
								self.prop( 'disabled', false );
							}
						).catch(
							function(err) {
								// Run this when promise was rejected via reject().
								console.log( err )
							}
						);
					}
				}
			);
			$( document ).on(
				'submit',
				'form#valorpay-2fa-form',
				function(e) {
					e.preventDefault();
					var self      = $( this );
					var otp_value = $( 'input#valorpay_otp' ).val();
					$.ajax(
						{
							type : "post",
							dataType : "json",
							url : valorpay_refund_ajax_object.ajax_url,
							data : {
								order_id: valorpay_refund_ajax_object.order_id,
								action: valorpay_refund_ajax_object.set_otp.ajax_action,
								nonce: valorpay_refund_ajax_object.set_otp.ajax_nonce,
								valorpay_2fa_otp: otp_value
							},
							success: function(response) {
								if (response.success) {
									$( '.modal-close' ).trigger( 'click' );
									$( '.do-api-refund' ).trigger( 'click' );
								} else {
									alert( response.data.message );
								}
							},
							error: function(err) {
							}
						}
					);
				}
			);
		}
	);
	/**
	 * Generate OTP for two factor authentication.
	 */
	var valorpay_2fa_otp = {
		generate_otp: function() {
			return new Promise(
				function(resolve, reject) {
					var refund_amount = $( 'input#refund_amount' ).val();
					var showPopup     = false;
					$.ajax(
						{
							type : "post",
							dataType : "json",
							url : valorpay_refund_ajax_object.ajax_url,
							data : {
								order_id: valorpay_refund_ajax_object.order_id,
								action: valorpay_refund_ajax_object.is_2fa.ajax_action,
								nonce: valorpay_refund_ajax_object.is_2fa.ajax_nonce,
								refund_amount: refund_amount
							},
							success: function(response) {
								if (response.success) {
									if (response.data.show_popup) {
										isTimerActive = true;
										resolve( [true, response.data] );
									} else {
										resolve( [false, response.data] );
									}
								} else {
									alert( response.data.message )
									resolve( [null, response.data] );
								}
							},
							error: function(err) {
								reject( err ) // Reject the promise and go to catch().
							}
						}
					);
				}
			);
		}
	}

	/**
	 * Timer start and stop function
	 */
	var valorpay_otp_timer = {
		start_timer: function(duration, display) {
			if ( ! isNaN( duration )) {
				var timer = duration, minutes, seconds;
				var _this = this;
				$( '.valorpay-otp-expire-msg' ).show();
				interVal = setInterval(
					function () {
						minutes = parseInt( timer / 60, 10 );
						seconds = parseInt( timer % 60, 10 );

						minutes = minutes < 10 ? "0" + minutes : minutes;
						seconds = seconds < 10 ? "0" + seconds : seconds;

						$( display ).html( '<b>' + minutes + 'm : ' + seconds + 's' + '</b>' );
						if (--timer < 0) {
							timer = duration;
							_this.timer_completed();
							valorpay_popup_buttons.toggle();
							$( display ).empty();
							clearInterval( interVal )
						}
					},
					1000
				);
			}
		},
		timer_completed: function() {
			clearInterval( interVal );
			$( '.valorpay-otp-expire-msg' ).hide();
			$( '#valor-otp-timer' ).html( '' );
			isTimerActive = false;
		}
	}
	/**
	 * Show/hide popup buttons
	 */
	var valorpay_popup_buttons = {
		toggle: function() {
			$( "#btn-valor-otp" ).toggle();
			$( "#btn-valor-resend" ).toggle();
		}
	}
})( jQuery );
