<?php
/**
 * Lost password reset form.
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/myaccount/form-reset-password.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 9.2.0
 */

defined( 'ABSPATH' ) || exit;

do_action( 'woocommerce_before_reset_password_form' );

/*
 * ⚠️ TELL THE BROWSER WHICH USERNAME IT IS SAVING, OR IT WILL GUESS WRONG.
 *
 * This form has two autocomplete="new-password" fields and, as WooCommerce
 * ships it, nothing carrying autocomplete="username". A password manager
 * therefore guesses, and the only name-shaped value here is the hidden
 * reset_login, so it saves the WP user_login.
 *
 * That is how a partner ended up with "info-10" autofilled on a field
 * labelled Email address. PortalImport builds a login from the part of the
 * address before the @, plus a number when that is taken, so every partner on
 * an info@ address became info, info-2 ... info-93. 242 of the 1,095 carry a
 * numbered login and none of them has any reason to see it: the sign-in form
 * asks for the email, and WordPress accepts an email there.
 *
 * So the email is declared as the username and the manager saves that
 * instead. The field is readonly and off-screen -- it exists to be read by
 * the browser, not filled in by a person -- and it is NOT display:none,
 * because managers skip fields they consider invisible.
 *
 * Fixes it for whoever has not set a password yet. Anyone who already has
 * keeps the old credential in their manager until they sign in with their
 * email once; nothing here can reach into a browser that already saved it.
 */
$mb_reset_user  = isset( $args['login'] ) ? get_user_by( 'login', $args['login'] ) : null;
$mb_reset_email = $mb_reset_user instanceof WP_User ? $mb_reset_user->user_email : '';
?>

<form method="post" class="woocommerce-ResetPassword lost_reset_password">

	<p><?php echo apply_filters( 'woocommerce_reset_password_message', esc_html__( 'Enter a new password below.', 'woocommerce' ) ); ?></p><?php // @codingStandardsIgnoreLine ?>

	<?php if ( '' !== $mb_reset_email ) : ?>
		<label for="mb-reset-username" class="visually-hidden"><?php esc_html_e( 'Email address', 'granola' ); ?></label>
		<input
			type="text"
			id="mb-reset-username"
			name="mb_reset_username"
			value="<?php echo esc_attr( $mb_reset_email ); ?>"
			autocomplete="username"
			readonly
			tabindex="-1"
			class="visually-hidden"
		/>
	<?php endif; ?>

	<p class="woocommerce-form-row woocommerce-form-row--first form-row form-row-first">
		<label for="password_1"><?php esc_html_e( 'New password', 'woocommerce' ); ?>&nbsp;<span class="required" aria-hidden="true">*</span><span class="visually-hidden"><?php esc_html_e( 'Required', 'woocommerce' ); ?></span></label>
		<input type="password" class="woocommerce-Input woocommerce-Input--text input-text" name="password_1" id="password_1" autocomplete="new-password" required aria-required="true" />
	</p>
	<p class="woocommerce-form-row woocommerce-form-row--last form-row form-row-last">
		<label for="password_2"><?php esc_html_e( 'Re-enter new password', 'woocommerce' ); ?>&nbsp;<span class="required" aria-hidden="true">*</span><span class="visually-hidden"><?php esc_html_e( 'Required', 'woocommerce' ); ?></span></label>
		<input type="password" class="woocommerce-Input woocommerce-Input--text input-text" name="password_2" id="password_2" autocomplete="new-password" required aria-required="true" />
	</p>

	<input type="hidden" name="reset_key" value="<?php echo esc_attr( $args['key'] ); ?>" />
	<input type="hidden" name="reset_login" value="<?php echo esc_attr( $args['login'] ); ?>" />

	<div class="clear"></div>

	<?php do_action( 'woocommerce_resetpassword_form' ); ?>

	<p class="woocommerce-form-row form-row">
		<input type="hidden" name="wc_reset_password" value="true" />
		<button type="submit" class="woocommerce-Button button<?php echo esc_attr( wc_wp_theme_get_element_class_name( 'button' ) ? ' ' . wc_wp_theme_get_element_class_name( 'button' ) : '' ); ?>" value="<?php esc_attr_e( 'Save', 'woocommerce' ); ?>"><?php esc_html_e( 'Save', 'woocommerce' ); ?></button>
	</p>

	<?php wp_nonce_field( 'reset_password', 'woocommerce-reset-password-nonce' ); ?>

</form>
<?php
do_action( 'woocommerce_after_reset_password_form' );

