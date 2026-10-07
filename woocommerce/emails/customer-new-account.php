<?php
/**
 * New account email.
 *
 * Overrides woocommerce/templates/emails/customer-new-account.php.
 *
 * The set-password variant is what partners receive, so the button text follows
 * whether WooCommerce generated a password or is asking them to choose one.
 *
 * @package Millboard
 */

defined( 'ABSPATH' ) || exit;

do_action( 'woocommerce_email_header', $email_heading, $email );

$mb_set_password = isset( $set_password_url ) && $set_password_url;
$mb_link         = $mb_set_password ? $set_password_url : wc_get_page_permalink( 'myaccount' );

?>
<p class="mb-eyebrow"><?php esc_html_e( 'Your account', 'granola' ); ?></p>
<h1><?php echo esc_html( $email_heading ); ?></h1>

<p>
	<?php
	/* translators: %s: customer username. */
	printf( esc_html__( 'Hi %s,', 'granola' ), esc_html( $user_login ) );
	?>
</p>

<p>
	<?php
	printf(
		/* translators: %1$s: site name, %2$s: username. */
		esc_html__( 'Your %1$s account has been created. Your username is %2$s.', 'granola' ),
		esc_html( $blogname ),
		'<strong>' . esc_html( $user_login ) . '</strong>'
	);
	?>
</p>

<p><?php esc_html_e( 'From your account you can view your orders, manage your addresses and change your password.', 'granola' ); ?></p>

<table class="mb-button" role="presentation" cellspacing="0" cellpadding="0" border="0">
	<tr>
		<td bgcolor="#151716">
			<a href="<?php echo esc_url( $mb_link ); ?>">
				<?php
				echo $mb_set_password
					? esc_html__( 'Choose your password', 'granola' )
					: esc_html__( 'Go to your account', 'granola' );
				?>
			</a>
		</td>
	</tr>
</table>

<?php

if ( $additional_content ) {
	echo wp_kses_post( wpautop( wptexturize( $additional_content ) ) );
}

do_action( 'woocommerce_email_footer', $email );
