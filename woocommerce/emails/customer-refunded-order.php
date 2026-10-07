<?php
/**
 * Refunded order email, to the customer.
 *
 * Overrides woocommerce/templates/emails/customer-refunded-order.php.
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
<p class="mb-eyebrow"><?php esc_html_e( 'Order refunded', 'granola' ); ?></p>
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
		esc_html__( 'We’ve refunded order #%s in full. The details are below for your records.', 'granola' ),
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
