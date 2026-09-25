<?php
/**
 * Markup for the Millboard sample ordering form.
 *
 * Expects, from mb_sof_render():
 *   $catalogue     array[] Classified sample products.
 *   $show_coverage bool    Render the admin coverage panel.
 *
 * The accordions themselves are built by JavaScript from window.MB_SOF_DATA,
 * so this template is only the shell.
 *
 * Quantities are held in a JavaScript object, not in these inputs: search
 * re-renders the accordion bodies, which would destroy the inputs for
 * non-matching products and silently drop them from the submission. On submit
 * the script writes one hidden input per line into #mb-sof-lines.
 *
 * @package Millboard
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="mb-sof" id="mb-sof">

	<?php if ( ! empty( $show_coverage ) ) : ?>
		<?php $coverage = mb_sof_coverage(); ?>
		<div class="mb-sof-coverage">
			<h3><?php esc_html_e( 'Catalogue coverage (admins only)', 'millboard' ); ?></h3>
			<p>
				<?php
				printf(
					/* translators: %s: number of products found. */
					esc_html__( '%s sample products found in WooCommerce.', 'millboard' ),
					'<strong>' . esc_html( number_format_i18n( $coverage['found'] ) ) . '</strong>'
				);
				?>
				<?php if ( $coverage['expected'] ) : ?>
					<?php
					printf(
						/* translators: %1$s: expected count, %2$s: missing count. */
						esc_html__( ' Expected %1$s; %2$s missing.', 'millboard' ),
						esc_html( number_format_i18n( $coverage['expected'] ) ),
						'<strong>' . esc_html( number_format_i18n( count( $coverage['missing'] ) ) ) . '</strong>'
					);
					?>
				<?php else : ?>
					<em><?php esc_html_e( 'No expected-SKU list is configured, so nothing can be reported as missing.', 'millboard' ); ?></em>
				<?php endif; ?>
			</p>

			<?php if ( ! empty( $coverage['missing'] ) ) : ?>
				<p><?php esc_html_e( 'Missing SKUs:', 'millboard' ); ?></p>
				<p class="mb-sof-coverage-skus"><?php echo esc_html( implode( ', ', $coverage['missing'] ) ); ?></p>
			<?php endif; ?>

			<table class="mb-sof-coverage-table">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Category', 'millboard' ); ?></th>
						<th><?php esc_html_e( 'Products', 'millboard' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $coverage['by_category'] as $cat => $count ) : ?>
						<tr<?php echo 0 === $count ? ' class="is-empty"' : ''; ?>>
							<td><?php echo esc_html( $cat ); ?></td>
							<td><?php echo esc_html( number_format_i18n( $count ) ); ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	<?php endif; ?>

	<div class="page-wrap">

		<div class="page-title">
			<p><?php esc_html_e( 'Place sample orders for your customers through this easy to use tool.', 'millboard' ); ?></p>
		</div>

		<form
			id="mb-sof-form"
			method="post"
			action="<?php echo esc_url( mb_sof_form_url() ); ?>"
		>
			<?php wp_nonce_field( 'mb_sof_add', 'mb_sof_nonce' ); ?>
			<input type="hidden" name="mb_sof_action" value="add">

			<!-- Populated by JavaScript immediately before submit. -->
			<div id="mb-sof-lines" hidden></div>

			<!-- Search and accordion controls -->
			<div class="toolbar">
				<div class="search-wrap" id="search-wrap">
					<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
					<label class="screen-reader-text" for="search-input">
						<?php esc_html_e( 'Search samples by name or SKU', 'millboard' ); ?>
					</label>
					<input
						class="search-input"
						id="search-input"
						type="search"
						autocomplete="off"
						placeholder="<?php esc_attr_e( 'Search by name or SKU…', 'millboard' ); ?>"
					>
					<button type="button" class="search-clear" id="search-clear" aria-label="<?php esc_attr_e( 'Clear search', 'millboard' ); ?>">&times;</button>
				</div>
				<button type="button" class="btn-link" id="btn-expand-all"><?php esc_html_e( 'Expand all', 'millboard' ); ?></button>
				<button type="button" class="btn-link" id="btn-collapse-all"><?php esc_html_e( 'Collapse all', 'millboard' ); ?></button>
				<button type="button" class="btn-link" id="btn-clear-order"><?php esc_html_e( 'Clear order', 'millboard' ); ?></button>
			</div>

			<!-- Categories -->
			<div id="accordion-container"></div>

			<div class="no-results" id="no-results">
				<?php esc_html_e( 'No products match your search.', 'millboard' ); ?>
			</div>

			<!-- Sticky basket bar -->
			<div class="order-bar" id="order-bar">
				<div class="order-bar-info">
					<div class="order-bar-stat">
						<strong id="bar-units">0</strong>
						<?php esc_html_e( 'Total units', 'millboard' ); ?>
					</div>
					<div class="order-bar-stat">
						<strong id="bar-lines">0</strong>
						<?php esc_html_e( 'Product lines', 'millboard' ); ?>
					</div>
				</div>
				<button type="submit" class="btn-primary" id="btn-submit" disabled>
					<?php esc_html_e( 'Add to basket', 'millboard' ); ?>
				</button>
			</div>

		</form>
	</div>

</div>
