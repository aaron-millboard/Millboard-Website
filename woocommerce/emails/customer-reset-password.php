<?php
/**
 * Password reset email.
 *
 * Overrides woocommerce/templates/emails/customer-reset-password.php.
 *
 * ⚠ The partner-portal launch mails this to roughly 1,235 people who did not
 * ask for it, which reads like phishing and is why the handoff proposes a
 * separate launch email with its own trigger. That is not built yet. Note also
 * that WordPress reset keys expire after 24 hours by default, so a bulk send
 * needs `password_reset_expiration` raising or most links will be dead on
 * arrival.
 *
 * @package Millboard
 */

defined( 'ABSPATH' ) || exit;

do_action( 'woocommerce_email_header', $email_heading, $email );

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
		/* translators: %s: username. */
		esc_html__( 'We received a request to reset the password for the account %s. Use the button below to choose a new one.', 'granola' ),
		'<strong>' . esc_html( $user_login ) . '</strong>'
	);
	?>
</p>

<table class="mb-button" role="presentation" cellspacing="0" cellpadding="0" border="0">
	<tr>
		<td bgcolor="#151716">
			<a href="<?php echo esc_url( add_query_arg( array( 'key' => $reset_key, 'id' => $user_id ), wc_get_endpoint_url( 'lost-password', '', wc_get_page_permalink( 'myaccount' ) ) ) ); ?>">
				<?php esc_html_e( 'Choose a new password', 'granola' ); ?>
			</a>
		</td>
	</tr>
</table>

<p class="mb-small"><?php esc_html_e( 'The link works once and expires after 24 hours. If you didn’t ask for this, you can ignore this email and your password won’t change.', 'granola' ); ?></p>

<?php

if ( $additional_content ) {
	echo wp_kses_post( wpautop( wptexturize( $additional_content ) ) );
}

do_action( 'woocommerce_email_footer', $email );
