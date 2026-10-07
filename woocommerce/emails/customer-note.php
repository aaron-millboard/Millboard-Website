<?php
/**
 * Customer note email.
 *
 * Overrides woocommerce/templates/emails/customer-note.php.
 *
 * @package Millboard
 */

defined( 'ABSPATH' ) || exit;

do_action( 'woocommerce_email_header', $email_heading, $email );

$mb_first = $order->get_billing_first_name();

?>
<p class="mb-eyebrow"><?php esc_html_e( 'Order update', 'granola' ); ?></p>
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
		esc_html__( 'Our team has added a note to order #%s:', 'granola' ),
		esc_html( $order->get_order_number() )
	);
	?>
</p>

<p class="mb-quote"><?php echo wp_kses_post( wpautop( wptexturize( $customer_note ) ) ); ?></p>

<?php

do_action( 'woocommerce_email_order_details', $order, $sent_to_admin, $plain_text, $email );

do_action( 'woocommerce_email_order_meta', $order, $sent_to_admin, $plain_text, $email );

do_action( 'woocommerce_email_customer_details', $order, $sent_to_admin, $plain_text, $email );

if ( $additional_content ) {
	echo wp_kses_post( wpautop( wptexturize( $additional_content ) ) );
}

do_action( 'woocommerce_email_footer', $email );
