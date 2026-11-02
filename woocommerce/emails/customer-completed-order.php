<?php
/**
 * Completed order email, to the customer.
 *
 * Overrides woocommerce/templates/emails/customer-completed-order.php.
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
<p class="mb-eyebrow"><?php esc_html_e( 'Order complete', 'granola' ); ?></p>
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
		esc_html__( 'We’ve finished processing order #%s. The details are below for your records.', 'granola' ),
		esc_html( $order->get_order_number() )
	);
	?>
</p>
<?php
/*
 * The tracking number, where the fulfilment team has entered one.
 *
 * Aaron, 2 Nov 2026: "we need it to have the tracking number please as we know
 * it as the fulfilment company will have entered it."
 *
 * It is the same `_mb_sendungsnummer` the German dispatch queue writes, so
 * this needs no second field and no second place to keep it up to date. Only
 * German sample orders carry it today, which is why there is no locale test
 * here: the block appears exactly when there is a number to show, and stays
 * away on every order and every market that has none.
 *
 * ⚠️ The deep link was checked against TNT's live site on 2 Nov 2026, not
 * taken from memory: `?searchType=con&cons=<number>` is read and searched for.
 * Linking the bare tracking page would make the customer copy the number
 * across by hand.
 */
$mb_tracking = class_exists( 'Theme\WooCommerce\SampleFulfilment' )
	? trim( (string) $order->get_meta( Theme\WooCommerce\SampleFulfilment::META_TRACKING ) )
	: '';

if ( '' !== $mb_tracking ) :
	$mb_track_url = add_query_arg(
		array(
			'searchType' => 'con',
			'cons'       => rawurlencode( $mb_tracking ),
		),
		'https://www.tnt.com/express/en_gb/site/shipping-tools/tracking.html'
	);
	?>
	<p>
		<?php
		printf(
			/* translators: %s: the carrier's tracking number. */
			esc_html__( 'Tracking number: %s', 'granola' ),
			esc_html( $mb_tracking )
		);
		?>
		<br>
		<a href="<?php echo esc_url( $mb_track_url ); ?>"><?php esc_html_e( 'Track your parcel with TNT', 'granola' ); ?></a>
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
