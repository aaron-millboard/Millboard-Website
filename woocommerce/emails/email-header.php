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

$mb_email    = $email ?? null;
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
							/**
							 * Kept because plugins hook it. The heading markup lives in each
							 * template, so nothing is printed here by default.
							 */
							do_action( 'woocommerce_email_header', $email_heading, $mb_email );
							?>
