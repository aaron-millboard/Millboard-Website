<?php
/**
 * Processing order email, to the customer.
 *
 * Overrides woocommerce/templates/emails/customer-processing-order.php.
 *
 * One template covers both of the handoff's variants. The sample version is not
 * a separate email, it is this one with the free-sample copy and the note
 * saying who placed it, shown only when the portal wrote that meta. The order
 * table, meta and addresses are left to the core hooks so Order Essentials and
 * the sample item markers keep coming through.
 *
 * @package Millboard
 */

defined( 'ABSPATH' ) || exit;

do_action( 'woocommerce_email_header', $email_heading, $email );

$mb_is_sample = 'yes' === $order->get_meta( '_millboard_sample_order' );
$mb_placed_by = (string) $order->get_meta( '_millboard_sample_placed_by' );
$mb_first     = $order->get_billing_first_name();

?>
<p class="mb-eyebrow"><?php esc_html_e( 'Order received', 'granola' ); ?></p>
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

<?php if ( $mb_is_sample ) : ?>
	<p><?php esc_html_e( 'We’ve received your sample order and our team is getting it ready. You’ll hear from us again when it has been dispatched.', 'granola' ); ?></p>
<?php else : ?>
	<p><?php esc_html_e( 'We’ve received your order and our team is getting it ready. You’ll hear from us again when it has been dispatched.', 'granola' ); ?></p>
<?php endif; ?>

<?php
/*
 * Only when the portal recorded who placed it. A customer who ordered for
 * themselves should never be told somebody did it for them.
 */
if ( $mb_placed_by ) :
	?>
	<p class="mb-note">
		<?php
		printf(
			/* translators: %s: name of the Millboard rep who placed the order. */
			esc_html__( 'This order was placed for you by %s at Millboard. Your details are only used to deliver it.', 'granola' ),
			'<strong>' . esc_html( $mb_placed_by ) . '</strong>'
		);
		?>
	</p>
	<?php
endif;

do_action( 'woocommerce_email_order_details', $order, $sent_to_admin, $plain_text, $email );

do_action( 'woocommerce_email_order_meta', $order, $sent_to_admin, $plain_text, $email );

do_action( 'woocommerce_email_customer_details', $order, $sent_to_admin, $plain_text, $email );

if ( $additional_content ) {
	echo wp_kses_post( wpautop( wptexturize( $additional_content ) ) );
}

do_action( 'woocommerce_email_footer', $email );
