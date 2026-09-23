<?php
/**
 * Add a sample selection to the WooCommerce basket.
 *
 * The form posts back to its own URL and is handled on template_redirect
 * rather than through admin-post.php. That is deliberate: admin-post.php runs
 * in admin context, where WooCommerce does not initialise the cart, so
 * WC()->cart would be null. On template_redirect the cart is already loaded.
 *
 * @package Millboard
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Most units allowed on a single line. */
function mb_sof_max_qty() {
	return (int) apply_filters( 'mb_sof_max_qty', 99 );
}

/**
 * Handle the submitted selection, then send the user to the basket.
 */
function mb_sof_maybe_handle_submit() {
	// phpcs:ignore WordPress.Security.NonceVerification.Missing -- checked immediately below.
	if ( empty( $_POST['mb_sof_action'] ) || 'add' !== $_POST['mb_sof_action'] ) {
		return;
	}

	if ( ! isset( $_POST['mb_sof_nonce'] )
		|| ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['mb_sof_nonce'] ) ), 'mb_sof_add' ) ) {
		wp_die( esc_html__( 'That form has expired. Please go back and try again.', 'millboard' ), '', array( 'response' => 403 ) );
	}

	if ( ! mb_sof_user_can_order() ) {
		wp_die( esc_html__( 'You do not have access to sample ordering.', 'millboard' ), '', array( 'response' => 403 ) );
	}

	if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
		wp_die( esc_html__( 'The basket is unavailable. Please try again.', 'millboard' ), '', array( 'response' => 500 ) );
	}

	// phpcs:ignore WordPress.Security.NonceVerification.Missing -- verified above.
	$raw = isset( $_POST['qty'] ) && is_array( $_POST['qty'] ) ? wp_unslash( $_POST['qty'] ) : array();

	// Only ids that are genuinely in the sample catalogue may be added. Without
	// this, a forged post could drop any product in the shop into the basket.
	$allowed = mb_sof_catalogue_by_id();
	$max     = mb_sof_max_qty();

	$lines = array();
	foreach ( $raw as $id => $qty ) {
		$id  = absint( $id );
		$qty = absint( $qty );

		if ( ! $id || ! $qty || ! isset( $allowed[ $id ] ) ) {
			continue;
		}
		$lines[ $id ] = min( $qty, $max );
	}

	if ( empty( $lines ) ) {
		wc_add_notice( __( 'No samples were selected.', 'millboard' ), 'error' );
		wp_safe_redirect( mb_sof_form_url() );
		exit;
	}

	$added  = 0;
	$units  = 0;
	$failed = array();

	foreach ( $lines as $id => $qty ) {
		// add_to_cart() merges quantities for a product already in the basket,
		// which is the behaviour we want on a repeat submission.
		$result = WC()->cart->add_to_cart( $id, $qty );

		if ( $result ) {
			++$added;
			$units += $qty;
		} else {
			$failed[] = $allowed[ $id ]['sku'] . ' — ' . $allowed[ $id ]['name'];
		}
	}

	if ( $failed ) {
		// Say exactly what did not go in. A silently short basket is the failure
		// mode this whole flow is built to avoid.
		wc_add_notice(
			sprintf(
				/* translators: %1$s: number of failed lines, %2$s: list of SKUs and names. */
				__( '%1$s sample line(s) could not be added and are not in your basket: %2$s', 'millboard' ),
				number_format_i18n( count( $failed ) ),
				esc_html( implode( '; ', $failed ) )
			),
			'error'
		);
	}

	if ( $added ) {
		wc_add_notice(
			sprintf(
				/* translators: %1$s: number of product lines, %2$s: total units. */
				__( 'Added %1$s sample line(s), %2$s units in total, to your basket.', 'millboard' ),
				number_format_i18n( $added ),
				number_format_i18n( $units )
			),
			'success'
		);
		wp_safe_redirect( wc_get_cart_url() );
		exit;
	}

	// Nothing made it in — keep them on the form rather than showing an empty basket.
	wp_safe_redirect( mb_sof_form_url() );
	exit;
}
add_action( 'template_redirect', 'mb_sof_maybe_handle_submit' );

/**
 * URL of the page holding the form, for redirecting back to it.
 *
 * @return string
 */
function mb_sof_form_url() {
	if ( function_exists( 'wc_get_account_endpoint_url' ) ) {
		return wc_get_account_endpoint_url( MB_SOF_ENDPOINT );
	}
	// Shortcode on an ordinary page: come back to wherever we were posted from.
	$ref = wp_get_referer();
	return $ref ? $ref : home_url( '/' );
}
