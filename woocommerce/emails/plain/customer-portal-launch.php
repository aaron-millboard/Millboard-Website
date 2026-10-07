<?php
/**
 * Partner portal launch email, plain text.
 *
 * @package Millboard
 */

defined( 'ABSPATH' ) || exit;

$mb_user    = $user instanceof WP_User ? $user : null;
$mb_name    = Theme\Emails\PortalLaunch::greeting_name( $mb_user );
$mb_company = $mb_user ? (string) get_user_meta( $mb_user->ID, 'millboard_company', true ) : '';
$mb_link    = $mb_user ? Theme\Emails\PortalLaunch::reset_url( $mb_user, $reset_key ) : '';
$mb_staff   = Theme\Emails\PortalLaunch::is_staff( $mb_user );

echo "= " . esc_html( wp_strip_all_tags( $email_heading ) ) . " =\n\n";

if ( $mb_name ) {
	/* translators: %s: partner's first name. */
	printf( esc_html__( 'Hi %s,', 'granola' ) . "\n\n", esc_html( $mb_name ) );
} else {
	echo esc_html__( 'Hi,', 'granola' ) . "\n\n";
}

echo esc_html__( 'We’ve set up an account for you on the new Millboard partner portal. To sign in for the first time, choose a password using the link below.', 'granola' ) . "\n\n";

if ( $mb_staff ) {
	echo esc_html__( 'Sample ordering is open now. Once you have set your password you will find it in My Account.', 'granola' ) . "\n\n";
	echo esc_html__( 'The brand assets side of the portal is still being built. Please keep using the existing portal for artwork and imagery for now, and we will let you know when it moves across.', 'granola' ) . "\n\n";
}

if ( $mb_user ) {
	echo esc_html__( 'Username:', 'granola' ) . ' ' . esc_html( $mb_user->user_login ) . "\n";
}

if ( $mb_company ) {
	echo esc_html__( 'Company:', 'granola' ) . ' ' . esc_html( $mb_company ) . "\n";
}

if ( $mb_link ) {
	echo "\n" . esc_url_raw( $mb_link ) . "\n\n";
}

echo esc_html__( 'The link works once. If it has expired, use "Forgotten password" on the sign-in page and we will send a new one. If you were not expecting this, call your Millboard contact or +44 (0) 24 7643 9943.', 'granola' ) . "\n\n";

if ( $additional_content ) {
	echo esc_html( wp_strip_all_tags( wptexturize( $additional_content ) ) ) . "\n\n";
}

echo esc_html( apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ), $email ) );
