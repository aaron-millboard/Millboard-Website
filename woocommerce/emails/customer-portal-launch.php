<?php
/**
 * Partner portal launch email.
 *
 * Says what actually happened rather than claiming the recipient requested
 * something, because they did not: this arrives unprompted. The username and
 * company give them something to recognise, and the closing paragraph gives
 * them a way to check it is genuine without clicking anything.
 *
 * @package Millboard
 */

defined( 'ABSPATH' ) || exit;

do_action( 'woocommerce_email_header', $email_heading, $email );

$mb_user    = $user instanceof WP_User ? $user : null;
$mb_name    = Theme\Emails\PortalLaunch::greeting_name( $mb_user );
$mb_company = $mb_user ? (string) get_user_meta( $mb_user->ID, 'millboard_company', true ) : '';
$mb_link    = $mb_user ? Theme\Emails\PortalLaunch::reset_url( $mb_user, $reset_key ) : '';
$mb_staff   = Theme\Emails\PortalLaunch::is_staff( $mb_user );

?>
<p class="mb-eyebrow"><?php esc_html_e( 'Partner portal', 'granola' ); ?></p>
<h1><?php echo esc_html( $email_heading ); ?></h1>

<p>
	<?php
	if ( $mb_name ) {
		/* translators: %s: partner's first name. */
		printf( esc_html__( 'Hi %s,', 'granola' ), esc_html( $mb_name ) );
	} else {
		esc_html_e( 'Hi,', 'granola' );
	}
	?>
</p>

<p><?php esc_html_e( 'We’ve set up an account for you on the new Millboard partner portal. To sign in for the first time, choose a password using the button below.', 'granola' ); ?></p>

<?php if ( $mb_staff ) : ?>
	<p><?php esc_html_e( 'Sample ordering is open now. Once you’ve set your password you’ll find it in My Account.', 'granola' ); ?></p>
	<p><?php esc_html_e( 'The brand assets side of the portal is still being built. Please keep using the existing portal for artwork and imagery for now, and we’ll let you know when it moves across.', 'granola' ); ?></p>
<?php endif; ?>

<table class="mb-detail" role="presentation" cellspacing="0" cellpadding="0" border="0">
	<?php if ( $mb_user ) : ?>
		<tr>
			<th scope="row"><?php esc_html_e( 'Username', 'granola' ); ?></th>
			<td><?php echo esc_html( $mb_user->user_login ); ?></td>
		</tr>
	<?php endif; ?>
	<?php if ( $mb_company ) : ?>
		<tr>
			<th scope="row"><?php esc_html_e( 'Company', 'granola' ); ?></th>
			<td><?php echo esc_html( $mb_company ); ?></td>
		</tr>
	<?php endif; ?>
</table>

<?php if ( $mb_link ) : ?>
	<table class="mb-button" role="presentation" cellspacing="0" cellpadding="0" border="0">
		<tr>
			<td bgcolor="#151716">
				<a href="<?php echo esc_url( $mb_link ); ?>"><?php esc_html_e( 'Choose your password', 'granola' ); ?></a>
			</td>
		</tr>
	</table>
<?php endif; ?>

<p class="mb-small">
	<?php
	printf(
		/* translators: %s: Millboard telephone number as a link. */
		esc_html__( 'The link works once. If it has expired, use “Forgotten password” on the sign-in page and we’ll send a new one. If you weren’t expecting this, call your Millboard contact or %s.', 'granola' ),
		'<a href="tel:+442476439943">+44 (0) 24 7643 9943</a>'
	);
	?>
</p>

<?php

if ( $additional_content ) {
	echo wp_kses_post( wpautop( wptexturize( $additional_content ) ) );
}

do_action( 'woocommerce_email_footer', $email );
