<?php
/**
 * Email footer.
 *
 * Overrides woocommerce/templates/emails/email-footer.php.
 *
 * ⚠ THE FILTER BELOW MUST KEEP BOTH ARGUMENTS.
 *
 *     apply_filters( 'woocommerce_email_footer_text', $email_footer_text, $email )
 *
 * `$email` is what makes a per-recipient footer possible, and two things depend
 * on it: Theme\WooCommerce\EmailUnsubscribe appends the signed unsubscribe link
 * that Compliance requires on every order confirmation, and
 * Theme\WooCommerce\EmailTerms swaps the terms of sale for the consumer or
 * business contract depending on the order's persona. Drop the second argument
 * and both silently disappear.
 *
 * @package Millboard
 */

defined( 'ABSPATH' ) || exit;

/*
 * WC_Emails::email_footer() passes NOTHING to this template, so `$email` is
 * always null here, in core as much as in this override. The email is captured
 * from the woocommerce_email_footer action instead.
 */
$mb_email = $email ?? null;

if ( ! $mb_email instanceof WC_Email && class_exists( 'Theme\WooCommerce\EmailUnsubscribe' ) ) {
	$mb_email = Theme\WooCommerce\EmailUnsubscribe::current_email();
}

// Contact line first; the legal links and the unsubscribe come from the filter.
$mb_phone = apply_filters( 'millboard_email_footer_phone', '+44 (0) 24 7643 9943' );
/*
 * enquiries@, not the WooCommerce from-address.
 *
 * The from-address is order.fulfilment.gb@millboard.com, a fulfilment inbox.
 * Using it here sent every customer to the wrong team, and on a password reset
 * or the portal launch it is nonsense: there is no order for fulfilment to
 * look up. The design handoff specified enquiries@ throughout and that is what
 * this should always have been.
 */
$mb_inbox = apply_filters( 'millboard_email_footer_email', 'enquiries@millboard.com' );

/*
 * (0) IS A TRUNK PREFIX AND HAS TO GO BEFORE THE DIGITS ARE COLLAPSED.
 *
 * Stripping everything that is not a digit or a plus turns
 * "+44 (0) 24 7643 9943" into "+4402476439943", which is not a dialable
 * number: the 0 is what you use INSTEAD of +44 when dialling domestically,
 * never as well as it. Every transactional email carried that link.
 *
 * The number stays correct as text beside it, so the information was never
 * wrong, only the thing you tap on a phone. Found 7 Oct 2026 by rendering the
 * launch email on production and reading every href before sending it.
 */
$mb_tel = preg_replace( '/[^0-9+]/', '', preg_replace( '/\(\s*0\s*\)/', '', $mb_phone ) );

// Whether this email is about an order at all, which decides how the contact
// line is worded below.
$mb_has_order = $mb_email instanceof WC_Email && ( $mb_email->object ?? null ) instanceof WC_Order;

?>
						</td>
					</tr>
					<tr>
						<td style="padding:0">
							<table id="template_footer" role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
								<tr>
									<td>
										<?php if ( $mb_phone || $mb_inbox ) : ?>
											<p>
												<?php
												printf(
													/* translators: %1$s: telephone link, %2$s: email link. */
													/*
													 * "Questions about your order?" only where there IS an
													 * order. An account email — new account, password reset,
													 * the portal launch — has none, and asking about an order
													 * the recipient never placed is the same fault as showing
													 * them a contract of sale.
													 */
													$mb_has_order
														/* translators: %1$s: telephone link, %2$s: email link. */
														? esc_html__( 'Questions about your order? Call %1$s or email %2$s.', 'granola' )
														/* translators: %1$s: telephone link, %2$s: email link. */
														: esc_html__( 'Any questions? Call %1$s or email %2$s.', 'granola' ),
													'<a href="tel:' . esc_attr( $mb_tel ) . '">' . esc_html( $mb_phone ) . '</a>',
													'<a href="mailto:' . esc_attr( $mb_inbox ) . '">' . esc_html( $mb_inbox ) . '</a>'
												);
												?>
											</p>
										<?php endif; ?>

										<?php
										// BOTH ARGUMENTS. See the note at the top of this file.
										echo wp_kses_post( wpautop( wptexturize( apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ), $mb_email ) ) ) );
										?>
									</td>
								</tr>
							</table>
						</td>
					</tr>
				</table>
			</div>
		</td>
	</tr>
</table>
</body>
</html>
