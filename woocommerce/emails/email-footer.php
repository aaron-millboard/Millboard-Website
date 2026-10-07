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
$mb_inbox = apply_filters( 'millboard_email_footer_email', get_option( 'woocommerce_email_from_address' ) );

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
													esc_html__( 'Questions about your order? Call %1$s or email %2$s.', 'granola' ),
													'<a href="tel:' . esc_attr( preg_replace( '/[^0-9+]/', '', $mb_phone ) ) . '">' . esc_html( $mb_phone ) . '</a>',
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
