<?php
/**
 * Email header.
 *
 * Overrides woocommerce/templates/emails/email-header.php.
 *
 * The outer padding sits on `#wrapper_cell`, the containing table cell, not on
 * the `#wrapper` div: Outlook ignores padding on a div and the email would sit
 * edge to edge.
 *
 * The heading is deliberately NOT printed here. Each template prints its own
 * eyebrow and `<h1>`, so the band is just the logo and the photograph. The
 * `woocommerce_email_header` action is kept regardless, because plugins hang
 * off it.
 *
 * `new_order` goes to fulfilment rather than a customer, so it gets the olive
 * hairline instead of the lifestyle strip.
 *
 * @package Millboard
 */

defined( 'ABSPATH' ) || exit;

/*
 * WC_Emails::email_header() receives only the heading, so `$email` is never
 * passed to this template and is always null here. Without the fallback the id
 * below is empty, the new_order test never matches, and the internal
 * notification gets a lifestyle photograph it is not supposed to have.
 */
$mb_email = $email ?? null;

if ( ! $mb_email instanceof WC_Email && class_exists( 'Theme\WooCommerce\EmailUnsubscribe' ) ) {
	$mb_email = Theme\WooCommerce\EmailUnsubscribe::current_email();
}

$mb_email_id = $mb_email instanceof WC_Email ? $mb_email->id : '';

/**
 * Which emails show the photograph. Everything customer-facing does; the
 * internal notifications get the hairline.
 */
$mb_show_strip = ! in_array( $mb_email_id, apply_filters( 'millboard_email_no_header_strip', array( 'new_order', 'failed_order', 'cancelled_order', 'admin_payment_gateway_enabled' ) ), true );

// Settings first, so marketing can swap the logo without a deploy; the theme
// copy is the fallback so the email is never logo-less on a fresh install.
$mb_logo = get_option( 'woocommerce_email_header_image' );

if ( ! $mb_logo ) {
	$mb_logo = get_theme_file_uri( 'assets/images/emails/logo-primary-color.png' );
}

/**
 * The lifestyle strip. A theme asset rather than a media-library upload, so it
 * needs no per-environment step and cannot point at the wrong hostname.
 */
$mb_strip = apply_filters( 'millboard_email_header_strip', get_theme_file_uri( 'assets/images/emails/header-garden.jpg' ), $mb_email_id );

?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<meta name="x-apple-disable-message-reformatting" />
<title><?php echo get_bloginfo( 'name', 'display' ); ?></title>
</head>
<body <?php echo is_rtl() ? 'rightmargin' : 'leftmargin'; ?>="0" marginwidth="0" topmargin="0" marginheight="0" offset="0">
<table id="outer_wrapper" role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" bgcolor="#dedcd2">
	<tr>
		<td id="wrapper_cell" align="center" valign="top">
			<div id="wrapper">
				<table id="template_container" role="presentation" cellspacing="0" cellpadding="0" border="0" width="600" bgcolor="#f9f7f1" align="center">
					<tr>
						<td id="template_header_image">
							<img src="<?php echo esc_url( $mb_logo ); ?>" width="150" alt="<?php echo esc_attr( get_bloginfo( 'name', 'display' ) ); ?>" />
						</td>
					</tr>
					<?php if ( $mb_show_strip && $mb_strip ) : ?>
						<tr>
							<td id="template_header_strip">
								<?php /* Decorative, so the alt is empty and the email still reads with images blocked. */ ?>
								<img src="<?php echo esc_url( $mb_strip ); ?>" width="600" alt="" />
							</td>
						</tr>
					<?php else : ?>
						<tr>
							<td id="template_rule"><div></div></td>
						</tr>
					<?php endif; ?>
					<tr>
						<td id="body_content_inner">
							<?php
							/*
							 * NOT do_action( 'woocommerce_email_header' ) here.
							 *
							 * WC_Emails::email_header() is what is hooked to that action, and
							 * it is what loads THIS file. Calling it from inside the template
							 * re-enters it until PHP runs out of memory and every email
							 * fatals. The action belongs in each email template, which is
							 * where it is.
							 *
							 * The heading is not printed here either: each template renders
							 * its own eyebrow and <h1>, so the band is just logo and photo.
							 */
							?>
