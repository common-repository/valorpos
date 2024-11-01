<?php
/**
 * Provide a admin area view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @link       https://valorpaytech.com
 * @since      1.0.0
 *
 * @package    Wc_Valorpay
 * @subpackage Wc_Valorpay/admin/partials
 */

?>

<!-- This file should primarily consist of HTML with a little bit of PHP. -->
<script type="text/template" id="tmpl-wc-modal-add-valorpay-popup">
	<div class="wc-backbone-modal">
		<div class="wc-backbone-modal-content">
			<section class="wc-backbone-modal-main" role="main">
				<header class="wc-backbone-modal-header">
					<h1><?php echo esc_html_e( 'Two Factor Authentication', 'wc-valorpay' ); ?></h1>
					<button class="modal-close modal-close-link dashicons dashicons-no-alt">
						<span class="screen-reader-text">Close modal panel</span>
					</button>
				</header>
				<article>
					<form id="valorpay-2fa-form" action="" method="post">
						<table id="valorpay-2fa-form-table">
							<tbody>
								<tr>
									<td>
										<label for="valorpay_otp"><?php echo esc_html_e( 'Enter OTP', 'wc-valorpay' ); ?></label>
										<input type="text" pattern="\d*" maxlength="6" id="valorpay_otp" name="valorpay_2fa_otp" autocomplete="off" class="required" required />
									</td>
									<td class="valorpay-otp-expire-msg" style=" display: none; ">
										<?php echo esc_html_e( 'OTP Expires in', 'wc-valorpay' ); ?>
										<span id="valor-otp-timer"></span>
									</td>
								</tr>
							</tbody>
						</table>
						<p id="valorpay-2fa-additional">{{{ data.additional_html }}}</p>
						<button type="submit" id="btn-valor-otp-submit" style="display: none;"></button>
					</form>
				</article>
				<footer>
					<div class="inner">
						<button id="btn-valor-otp" class="button button-primary button-large button-valor-submit"><?php echo esc_html_e( 'Submit', 'wc-valorpay' ); ?></button>
						<button id="btn-valor-resend" class="button button-primary button-large button-valor-resend" style="display: none;"><?php echo esc_html_e( 'Resend OTP', 'wc-valorpay' ); ?></button>
						<span id="valor-resend-load" class="spinner" style="display: none;"></span>
					</div>
				</footer>
			</section>
		</div>
	</div>
	<div class="wc-backbone-modal-backdrop modal-close"></div>
</script>
