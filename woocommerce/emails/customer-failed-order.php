<?php
/**
 * Failed order email, to the customer.
 *
 * Overrides woocommerce/templates/emails/customer-failed-order.php.
 *
 * Copy from the design handoff. The order table, meta and addresses are left to
 * the core hooks so Order Essentials and the sample item markers keep coming
 * through unchanged.
 *
 * @package Millboard
 */

defined( 'ABSPATH' ) || exit;

do_action( 'woocommerce_email_header', $email_heading, $email );

$mb_first = $order->get_billing_first_name();

?>
<p class="mb-eyebrow"><?php esc_html_e( 'Payment unsuccessful', 'granola' ); ?></p>
<h1><?php echo esc_html( $email_heading ); ?></h1>

<p>
	<?php
	if ( $mb_first ) {
		/* translators: %s: customer first name. */
		printf( esc_html__( 'Hi %s,', 'granola' ), esc_html( $mb_first ) );
	} else {
		esc_html_e( 'Hi,', 'granola' );
	}
	?>
</p>

<p>
	<?php
	printf(
		/* translators: %s: order number. */
		esc_html__( 'Unfortunately we couldn’t take payment for order #%s, so it hasn’t been placed. Nothing has been charged. You can try again from your basket, or call us and we’ll help.', 'granola' ),
		esc_html( $order->get_order_number() )
	);
	?>
</p>
<?php

do_action( 'woocommerce_email_order_details', $order, $sent_to_admin, $plain_text, $email );

do_action( 'woocommerce_email_order_meta', $order, $sent_to_admin, $plain_text, $email );

do_action( 'woocommerce_email_customer_details', $order, $sent_to_admin, $plain_text, $email );


if ( $additional_content ) {
	echo wp_kses_post( wpautop( wptexturize( $additional_content ) ) );
}

do_action( 'woocommerce_email_footer', $email );
