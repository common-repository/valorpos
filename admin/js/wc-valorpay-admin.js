/**
 * On checkout update fee on payment method change
 *
 * @package    Wc_Valorpay
 * @subpackage Wc_Valorpay/admin
 */

(function( $ ) {
	'use strict';
	$(
		function() {

			$( 'input#woocommerce_wc_valorpay_disable_payment_on_failed' ).on(
				'change',
				function (e) {
					if ( $( this ).is( ':checked' ) ) {
						$( this ).closest( 'tr' ).next( 'tr' ).show();
						$( this ).closest( 'tr' ).next().next( 'tr' ).show();
						$( 'div#valorpay-tracker' ).show();
					} else {
						$( this ).closest( 'tr' ).next( 'tr' ).hide();
						$( this ).closest( 'tr' ).next().next( 'tr' ).hide();
						$( 'div#valorpay-tracker' ).hide();
					}
				}
			).trigger( 'change' );
			$( 'a#valorpay-goto-tracker' ).on(
				'click',
				function (e) {
					e.preventDefault();
					if ($( 'input#woocommerce_wc_valorpay_disable_payment_on_failed' ).is( ':checked' )) {
						$( 'html, body' ).animate(
							{
								scrollTop: $( "div#valorpay-tracker" ).offset().top
							},
							2000
						);
					}
				}
			)
			$( 'button.valorpay-remove-ip' ).on(
				'click',
				function (e) {

					if (window.confirm( valorpay_settings_ajax_object.confirm_msg )) {
						var currentItem = $( this );
						$.ajax(
							{
								type : "post",
								dataType : "json",
								url: valorpay_settings_ajax_object.ajax_url,
								data : {
									action: valorpay_settings_ajax_object.ajax_action,
									nonce: valorpay_settings_ajax_object.ajax_nonce,
									valorpay_track_ip:currentItem.data( "ip" )
								},
								success: function(response) {
									if (response.success) {
										currentItem.closest( "tr" ).remove();
										if ( ! response.data.total_count) {
											$( '.valorpay-no-msg' ).show();
										}
									} else {
										alert( response.data.message );
									}
								}
							}
						);
					}
				}
			)
		}
	);

})( jQuery );
