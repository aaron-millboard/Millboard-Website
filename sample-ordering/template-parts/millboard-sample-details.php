<?php
/**
 * Step two of a sample order: who it is for, and where it goes.
 *
 * From Theme\WooCommerce\SampleOrderCheckout::render_details():
 *   $lines array<int,int> product/variation id => quantity, already validated
 *   $units int            total units
 *
 * THIS IS THE CHECKOUT'S FORM, NOT A NEW ONE.
 *
 * Every row is WooCommerce's own markup -- `p.form-row` carrying form-row-wide,
 * form-row-first or form-row-last, a label, and the control inside
 * `span.woocommerce-input-wrapper` -- because the account page is wrapped in
 * `.woocommerce` and wc-checkout's main.scss styles `.woocommerce .form-row`
 * from the global bundle. Emitting what checkout emits means the boxed field,
 * the label and the required marker all arrive already styled, with no second
 * opinion about what a Millboard form looks like.
 *
 * Widths follow checkout's rule exactly: a field is half the row only as one of
 * a first/last pair, otherwise it is full width. Checkout runs town, county and
 * postcode full width one under another, so this does too.
 *
 * The address rows keep WooCommerce's `<key>_field` ids and `billing_*` input
 * names: the Addressy/Loqate script positions its postcode lookup against those
 * ids and binds its autocomplete to the inputs by name. The country select has
 * to be present for the same reason, because the Addressy init reads it to
 * drive the search.
 *
 * @package Millboard
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Theme\WooCommerce\SampleOrderCheckout as Checkout;

$mb_persona   = Checkout::checkout_options( 'website_persona' );
$mb_size      = Checkout::checkout_options( 'project-size' );
$mb_start     = Checkout::checkout_options( 'project-start-time' );
$mb_staff     = Checkout::staff_options();
$mb_countries = function_exists( 'WC' ) ? WC()->countries->get_allowed_countries() : array( 'GB' => 'United Kingdom' );

/**
 * One row, in WooCommerce's shape.
 *
 * @param string $key      Field key: the id, the name, and `<key>_field` on the wrapper.
 * @param string $label    Visible label.
 * @param string $width    'wide', 'first' or 'last'.
 * @param string $control  Control markup, already escaped by its builder.
 * @param bool   $required Adds the marker, the validate class and the attribute.
 * @param string $note     Optional hint under the control.
 */
$mb_row = static function ( string $key, string $label, string $width, string $control, bool $required = false, string $note = '' ): void {
	printf(
		'<p class="form-row form-row-%1$s%2$s" id="%3$s_field">%4$s<span class="woocommerce-input-wrapper">%5$s</span>%6$s</p>',
		esc_attr( $width ),
		$required ? ' validate-required' : '',
		esc_attr( $key ),
		sprintf(
			'<label for="%1$s"%2$s>%3$s%4$s</label>',
			esc_attr( $key ),
			$required ? ' class="required_field"' : '',
			esc_html( $label ),
			$required ? '&nbsp;<span class="required" aria-hidden="true">*</span>' : ''
		),
		$control, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped by the builders below.
		'' !== $note ? '<span class="mb-sof-details__note">' . esc_html( $note ) . '</span>' : ''
	);
};

$mb_input = static function ( string $key, bool $required = false, string $placeholder = '', string $type = 'text' ): string {
	return sprintf(
		'<input type="%1$s" class="input-text" name="%2$s" id="%2$s" value=""%3$s%4$s>',
		esc_attr( $type ),
		esc_attr( $key ),
		$required ? ' required aria-required="true"' : '',
		'' !== $placeholder ? ' placeholder="' . esc_attr( $placeholder ) . '"' : ''
	);
};

/**
 * @param array<string,string> $options value => label
 */
$mb_select = static function ( string $key, array $options, bool $required = false, string $placeholder = '', string $class = '' ): string {
	$out = sprintf(
		'<select name="%1$s" id="%1$s" class="select %2$s"%3$s>',
		esc_attr( $key ),
		esc_attr( $class ),
		$required ? ' required aria-required="true"' : ''
	);

	if ( '' !== $placeholder ) {
		$out .= '<option value="">' . esc_html( $placeholder ) . '</option>';
	}

	foreach ( $options as $mb_value => $mb_text ) {
		$out .= sprintf(
			'<option value="%s"%s>%s</option>',
			esc_attr( $mb_value ),
			'GB' === $mb_value && 'billing_country' === $key ? ' selected' : '',
			esc_html( $mb_text )
		);
	}

	return $out . '</select>';
};
?>
<div class="mb-sof-details">

	<h2 class="mb-account-panel__title"><?php esc_html_e( 'Delivery details', 'millboard' ); ?></h2>
	<p class="mb-account-panel__intro">
		<?php
		printf(
			/* translators: %1$s: number of product lines, %2$s: number of units. */
			esc_html__( '%1$s product lines, %2$s units. Tell us who the samples are for and where they go.', 'millboard' ),
			'<strong>' . esc_html( number_format_i18n( count( $lines ) ) ) . '</strong>',
			'<strong>' . esc_html( number_format_i18n( $units ) ) . '</strong>'
		);
		?>
	</p>

	<form id="mb-sof-details-form" class="mb-sof-details__form" method="post" action="<?php echo esc_url( mb_sof_form_url() ); ?>">
		<?php wp_nonce_field( Checkout::NONCE, 'mb_sof_nonce' ); ?>
		<input type="hidden" name="mb_sof_action" value="place">

		<?php foreach ( $lines as $mb_id => $mb_qty ) : ?>
			<input type="hidden" name="qty[<?php echo esc_attr( $mb_id ); ?>]" value="<?php echo esc_attr( $mb_qty ); ?>">
		<?php endforeach; ?>

		<div class="mb-sof-details__section">
			<div class="mb-sof-details__section-header">
				<h3><?php esc_html_e( 'Customer', 'millboard' ); ?></h3>
				<p><?php esc_html_e( 'Who the samples are for.', 'millboard' ); ?></p>
			</div>

			<div class="mb-sof-details__fields">
				<?php
				$mb_row( 'customer_first_name', __( 'First name', 'millboard' ), 'first', $mb_input( 'customer_first_name', true ), true );
				$mb_row( 'customer_last_name', __( 'Last name', 'millboard' ), 'last', $mb_input( 'customer_last_name', true ), true );

				// The customer's own address, not the rep's. This is the key
				// HubSpot matches the contact on, so without it the order is
				// filed against whoever happened to be logged in.
				$mb_row(
					'customer_email',
					__( 'Email address', 'millboard' ),
					'first',
					$mb_input( 'customer_email', true, '', 'email' ),
					true,
					__( 'The customer’s own address. This is what creates their record.', 'millboard' )
				);

				$mb_row( 'billing_company', __( 'Company name', 'millboard' ), 'last', $mb_input( 'billing_company' ) );

				$mb_row(
					'follow_up',
					__( 'Does this customer require a follow-up from Millboard?', 'millboard' ),
					$mb_staff ? 'first' : 'wide',
					$mb_select(
						'follow_up',
						array(
							'Yes' => __( 'Yes', 'millboard' ),
							'No'  => __( 'No', 'millboard' ),
						),
						true,
						__( 'Please select', 'millboard' )
					),
					true
				);

				if ( $mb_staff ) {
					$mb_row(
						'on_behalf_of',
						__( 'Order on behalf of', 'millboard' ),
						'last',
						$mb_select( 'on_behalf_of', array_combine( $mb_staff, $mb_staff ), false, __( 'Myself', 'millboard' ) )
					);
				}
				?>

				<?php
				// Marketing opt-OUT, not opt-in: ticking it means do not market.
				// The wording is Legal's and is read live from the shop
				// checkout, so the two cannot drift into saying different
				// things about the same consent. The name is the checkout's
				// misnomer, kept because it is the key HubSpot is mapped to.
				$mb_consent = Checkout::checkout_label( Checkout::MARKETING_OPT_OUT );
				?>
				<?php if ( '' !== $mb_consent ) : ?>
					<p class="form-row form-row-wide" id="<?php echo esc_attr( Checkout::MARKETING_OPT_OUT ); ?>_field">
						<span class="woocommerce-input-wrapper">
							<label class="checkbox">
								<input type="checkbox" class="input-checkbox" name="<?php echo esc_attr( Checkout::MARKETING_OPT_OUT ); ?>" id="<?php echo esc_attr( Checkout::MARKETING_OPT_OUT ); ?>" value="1">
								<?php echo wp_kses_post( $mb_consent ); ?>
							</label>
						</span>
					</p>
				<?php endif; ?>
			</div>
		</div>

		<div class="mb-sof-details__section">
			<div class="mb-sof-details__section-header">
				<h3><?php esc_html_e( 'The project', 'millboard' ); ?></h3>
				<p><?php esc_html_e( 'What the samples are being considered for.', 'millboard' ); ?></p>
			</div>

			<div class="mb-sof-details__fields">
				<?php
				$mb_row( 'website_persona', __( 'This project is to be installed at', 'millboard' ), 'wide', $mb_select( 'website_persona', $mb_persona, true ), true );
				$mb_row( 'project-size', __( 'Project size', 'millboard' ), 'first', $mb_select( 'project-size', $mb_size ) );
				$mb_row( 'project-start-time', __( 'When will they start the project?', 'millboard' ), 'last', $mb_select( 'project-start-time', $mb_start ) );
				?>

				<p class="form-row form-row-wide" id="project_type_field">
					<span class="mb-sof-details__group-label"><?php esc_html_e( 'Project type', 'millboard' ); ?></span>
					<span class="woocommerce-input-wrapper mb-sof-details__checks">
						<?php foreach ( array( 'Decking Project', 'Cladding Project', 'Other Project' ) as $mb_type ) : ?>
							<label class="checkbox">
								<input type="checkbox" class="input-checkbox" name="project_type[]" value="<?php echo esc_attr( $mb_type ); ?>">
								<?php echo esc_html( $mb_type ); ?>
							</label>
						<?php endforeach; ?>
					</span>
				</p>
			</div>
		</div>

		<div class="mb-sof-details__section">
			<div class="mb-sof-details__section-header">
				<h3><?php esc_html_e( 'Delivery address', 'millboard' ); ?></h3>
				<p><?php esc_html_e( 'Where the samples are sent.', 'millboard' ); ?></p>
			</div>

			<div class="mb-sof-details__fields">
				<?php
				$mb_row( 'billing_country', __( 'Country/Region', 'millboard' ), 'wide', $mb_select( 'billing_country', $mb_countries, false, '', 'country_to_state country_select' ) );
				$mb_row( 'billing_address_1', __( 'Street address', 'millboard' ), 'wide', $mb_input( 'billing_address_1', true, __( 'Start typing the customer address…', 'millboard' ) ), true );
				$mb_row( 'billing_address_2', __( 'Apartment, suite, unit, etc.', 'millboard' ), 'wide', $mb_input( 'billing_address_2' ) );
				$mb_row( 'billing_city', __( 'Town/City', 'millboard' ), 'wide', $mb_input( 'billing_city' ) );
				$mb_row( 'billing_state', __( 'County', 'millboard' ), 'wide', $mb_input( 'billing_state' ) );
				$mb_row( 'billing_postcode', __( 'Postcode', 'millboard' ), 'wide', $mb_input( 'billing_postcode', true ), true );
				?>
			</div>
		</div>

		<div class="mb-sof-details__section">
			<div class="mb-sof-details__section-header">
				<h3><?php esc_html_e( 'Internal', 'millboard' ); ?></h3>
				<p><?php esc_html_e( 'Sales use only. This is not seen by logistics.', 'millboard' ); ?></p>
			</div>

			<div class="mb-sof-details__fields">
				<p class="form-row form-row-wide" id="sales_comments_field">
					<label for="sales_comments"><?php esc_html_e( 'Comments', 'millboard' ); ?></label>
					<span class="woocommerce-input-wrapper">
						<textarea name="sales_comments" id="sales_comments" class="input-text" rows="4"></textarea>
					</span>
				</p>
			</div>
		</div>

		<div class="mb-sof-details__actions">
			<button type="submit" class="mb-account-btn"><?php esc_html_e( 'Place sample order', 'millboard' ); ?></button>
			<a class="mb-account-btn mb-account-btn--ghost" href="<?php echo esc_url( mb_sof_form_url() ); ?>"><?php esc_html_e( 'Back to the catalogue', 'millboard' ); ?></a>
			<span class="mb-sof-details__count">
				<?php
				printf(
					/* translators: %1$s: number of product lines, %2$s: number of units. */
					esc_html__( '%1$s lines, %2$s units', 'millboard' ),
					esc_html( number_format_i18n( count( $lines ) ) ),
					esc_html( number_format_i18n( $units ) )
				);
				?>
			</span>
		</div>
	</form>
</div>
