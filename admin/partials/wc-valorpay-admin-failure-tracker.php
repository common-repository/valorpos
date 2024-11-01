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
<div id="valorpay-tracker">
	<h2><?php esc_html_e( 'Payment Failed Tracker', 'wc-valorpay' ); ?></h2>
	<table class="widefat" cellspacing="0">
		<thead>
			<tr>
				<th><?php esc_html_e( 'IP', 'wc-valorpay' ); ?></th>
				<th><?php esc_html_e( 'Failed Date', 'wc-valorpay' ); ?></th>
				<th><?php esc_html_e( 'Failed Count', 'wc-valorpay' ); ?></th>
				<th><?php esc_html_e( 'Blocked', 'wc-valorpay' ); ?></th>
				<th><?php esc_html_e( 'Action', 'wc-valorpay' ); ?></th>
			</tr>
		</thead>
		<?php if ( ! empty( $payment_failed_tracker ) ) : ?>
			<tbody>
			<?php foreach ( $payment_failed_tracker as $ip => $tracker_info ) : ?>
				<tr>
					<td><?php echo esc_html( $ip ); ?></td>
					<td><?php echo esc_html( wp_date( 'M j, Y g:i a', $tracker_info['last_failed'] ) ); ?></td>
					<td><?php echo esc_html( $tracker_info['count'] ); ?></td>
					<td>
						<?php if ( $tracker_info['block_payment'] ) : ?>
							<mark class="valorpay-tracker-status tracker-blocked"><span><?php esc_html_e( 'Yes', 'wc-valorpay' ); ?></span></mark>
						<?php else : ?>
							<mark class="valorpay-tracker-status"><span><?php esc_html_e( 'No', 'wc-valorpay' ); ?></span></mark>
						<?php endif; ?>
					</td>
					<td><button class="button valorpay-remove-ip" data-ip="<?php echo esc_attr( $ip ); ?>"><?php esc_html_e( 'Remove', 'wc-valorpay' ); ?></button></td>
				</tr>
			<?php endforeach; ?>
				<tr class="valorpay-no-msg" style="display: none;">
					<td><?php esc_html_e( 'No failed payment', 'wc-valorpay' ); ?></td>
				</tr>
			</tbody>
		<?php else : ?>
			<tbody>
				<tr>
					<td><?php esc_html_e( 'No failed payment', 'wc-valorpay' ); ?></td>
				</tr>
			</tbody>
		<?php endif; ?>
	</table>
</div>
