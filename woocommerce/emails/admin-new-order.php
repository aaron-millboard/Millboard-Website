<?php
/**
 * New order email, to fulfilment.
 *
 * Overrides woocommerce/templates/emails/admin-new-order.php.
 *
 * Internal, so no lifestyle photograph: the header falls back to the olive
 * hairline for this email id. Ops fulfil from this message, so the order table
 * is left entirely to the core hook and the summary above it stays a plain
 * two-column table rather than anything decorative.
 *
 * "Placed by" only appears for portal orders, where a Millboard rep ordered on
 * a customer's behalf. It is the one piece of context ops cannot get from the
 * addresses.
 *
 * @package Millboard
 */

defined( 'ABSPATH' ) || exit;

do_action( 'woocommerce_email_header', $email_heading, $email );

$mb_name      = trim( $order->get_formatted_billing_full_name() );
$mb_placed_by = (string) $order->get_meta( '_millboard_sample_placed_by' );
$mb_on_behalf = (string) $order->get_meta( '_millboard_sample_on_behalf_of' );
$mb_phone     = $order->get_billing_phone();
$mb_email     = $order->get_billing_email();

?>
<h1><?php echo esc_html( $email_heading ); ?></h1>

<p>
	<?php
	if ( $mb_name ) {
		/* translators: %s: customer name. */
		printf( esc_html__( 'You’ve received the following order from %s.', 'granola' ), esc_html( $mb_name ) );
	} else {
		esc_html_e( 'You’ve received the following order.', 'granola' );
	}
	?>
</p>

<table class="mb-detail" role="presentation" cellspacing="0" cellpadding="0" border="0">
	<?php if ( $mb_placed_by ) : ?>
		<tr>
			<th scope="row"><?php esc_html_e( 'Placed by', 'granola' ); ?></th>
			<td>
				<?php
				echo esc_html( $mb_placed_by );
				echo ' ';
				esc_html_e( '(partner portal, on behalf of customer)', 'granola' );

				if ( $mb_on_behalf ) {
					echo '<br />';
					printf(
						/* translators: %s: the Millboard colleague the order was placed for. */
						esc_html__( 'On behalf of %s', 'granola' ),
						esc_html( $mb_on_behalf )
					);
				}
				?>
			</td>
		</tr>
	<?php endif; ?>
	<?php if ( $mb_email ) : ?>
		<tr>
			<th scope="row"><?php esc_html_e( 'Email', 'granola' ); ?></th>
			<td><a href="mailto:<?php echo esc_attr( $mb_email ); ?>"><?php echo esc_html( $mb_email ); ?></a></td>
		</tr>
	<?php endif; ?>
	<?php if ( $mb_phone ) : ?>
		<tr>
			<th scope="row"><?php esc_html_e( 'Phone', 'granola' ); ?></th>
			<td><?php echo esc_html( $mb_phone ); ?></td>
		</tr>
	<?php endif; ?>
</table>

<?php

do_action( 'woocommerce_email_order_details', $order, $sent_to_admin, $plain_text, $email );

do_action( 'woocommerce_email_order_meta', $order, $sent_to_admin, $plain_text, $email );

do_action( 'woocommerce_email_customer_details', $order, $sent_to_admin, $plain_text, $email );

if ( $additional_content ) {
	echo wp_kses_post( wpautop( wptexturize( $additional_content ) ) );
}

do_action( 'woocommerce_email_footer', $email );
