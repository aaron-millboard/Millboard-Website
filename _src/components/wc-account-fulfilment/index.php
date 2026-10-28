<?php

/**
 * Sample fulfilment -- the queue.
 *
 * A German order, everything needed to post it, and the three fields the
 * agency fills in. The three cells are rendered by SampleFulfilment::column(),
 * the same call the admin list makes, so both screens produce identical markup
 * and the one editor script drives both.
 */

use Theme\WooCommerce\SampleFulfilment;

use function Granola\Components\WC_Account_Fulfilment\address_line;
use function Granola\Components\WC_Account_Fulfilment\customer_line;
use function Granola\Components\WC_Account_Fulfilment\item_lines;
use function Granola\Components\WC_Account_Fulfilment\page_of_orders;
use function Granola\Components\WC_Account_Fulfilment\panel_url;
use function Granola\Components\WC_Account_Fulfilment\request;

$mb_request = request();
$mb_page = page_of_orders($mb_request);
$mb_orders = $mb_page['orders'];

/*
 * The editor, enqueued here rather than on `wp_enqueue_scripts`, because this
 * panel only renders inside the account endpoint and there is no cheaper way
 * to know that than being here. It registers into the footer, so a late
 * enqueue still prints.
 */
SampleFulfilment::enqueue_editor();

?>
<div class="mb-fulfilment">

	<form class="mb-fulfilment__filters" method="get" action="<?php echo esc_url(panel_url(['mbfq' => null, 'mbfp' => null])); ?>">
		<?php
		/*
		 * The endpoint is a path, not a query argument, so there is nothing
		 * hidden to carry here -- but the "show all" state IS a query
		 * argument, and a GET form drops anything not in it.
		 */
		if (!$mb_request['outstanding']) :
			?>
			<input type="hidden" name="mbfall" value="1">
		<?php endif; ?>

		<label class="mb-fulfilment__search">
			<span class="screen-reader-text"><?php esc_html_e('Search orders', 'granola'); ?></span>
			<input
				type="search"
				name="mbfq"
				value="<?php echo esc_attr($mb_request['search']); ?>"
				placeholder="<?php esc_attr_e('Name, e-mail, town or order number', 'granola'); ?>"
			>
		</label>

		<button type="submit" class="mb-fulfilment__go"><?php esc_html_e('Search', 'granola'); ?></button>

		<?php if ('' !== $mb_request['search']) : ?>
			<a class="mb-fulfilment__clear" href="<?php echo esc_url(panel_url(['mbfq' => null, 'mbfp' => null])); ?>">
				<?php esc_html_e('Clear', 'granola'); ?>
			</a>
		<?php endif; ?>

		<span class="mb-fulfilment__toggle">
			<?php if ($mb_request['outstanding']) : ?>
				<a href="<?php echo esc_url(panel_url(['mbfall' => 1, 'mbfp' => null])); ?>">
					<?php esc_html_e('Show dispatched as well', 'granola'); ?>
				</a>
			<?php else : ?>
				<a href="<?php echo esc_url(panel_url(['mbfall' => null, 'mbfp' => null])); ?>">
					<?php esc_html_e('Show only what still needs sending', 'granola'); ?>
				</a>
			<?php endif; ?>
		</span>
	</form>

	<p class="mb-fulfilment__count">
		<?php
		printf(
			/* translators: %s: a number of orders. */
			esc_html(_n('%s order', '%s orders', $mb_page['total'], 'granola')),
			esc_html(number_format_i18n($mb_page['total']))
		);

		if ($mb_request['outstanding']) {
			echo ' ';
			esc_html_e('still to send.', 'granola');
		}
		?>
	</p>

	<?php if (empty($mb_orders)) : ?>

		<p class="mb-fulfilment__empty">
			<?php
			if ('' !== $mb_request['search']) {
				esc_html_e('Nothing matches that search.', 'granola');
			} elseif ($mb_request['outstanding']) {
				esc_html_e('Nothing waiting. Every order has a dispatch date.', 'granola');
			} else {
				esc_html_e('No orders yet.', 'granola');
			}
			?>
		</p>

	<?php else : ?>

		<div class="mb-fulfilment__scroll">
			<table class="mb-fulfilment__table">
				<thead>
					<tr>
						<th scope="col"><?php esc_html_e('Bestellung', 'granola'); ?></th>
						<th scope="col"><?php esc_html_e('Empfänger', 'granola'); ?></th>
						<th scope="col"><?php esc_html_e('Muster', 'granola'); ?></th>
						<th scope="col"><?php esc_html_e('Versanddatum', 'granola'); ?></th>
						<th scope="col"><?php esc_html_e('Sendungsnummer', 'granola'); ?></th>
						<th scope="col"><?php esc_html_e('Anmerkungen', 'granola'); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ($mb_orders as $mb_order) : ?>
						<tr>
							<th scope="row" class="mb-fulfilment__order">
								<span class="mb-fulfilment__number">#<?php echo esc_html($mb_order->get_order_number()); ?></span>
								<span class="mb-fulfilment__date">
									<?php echo esc_html(wc_format_datetime($mb_order->get_date_created())); ?>
								</span>
								<span class="mb-fulfilment__status mb-fulfilment__status--<?php echo esc_attr($mb_order->get_status()); ?>">
									<?php echo esc_html(wc_get_order_status_name($mb_order->get_status())); ?>
								</span>
							</th>

							<td class="mb-fulfilment__who">
								<span class="mb-fulfilment__name"><?php echo esc_html(customer_line($mb_order)); ?></span>
								<span class="mb-fulfilment__address"><?php echo esc_html(address_line($mb_order)); ?></span>
								<?php if ($mb_order->get_billing_phone()) : ?>
									<span class="mb-fulfilment__phone"><?php echo esc_html($mb_order->get_billing_phone()); ?></span>
								<?php endif; ?>
							</td>

							<td class="mb-fulfilment__items">
								<ul>
									<?php foreach (item_lines($mb_order) as $mb_line) : ?>
										<li><?php echo wp_kses($mb_line, ['span' => []]); ?></li>
									<?php endforeach; ?>
								</ul>
							</td>

							<?php
							/*
							 * The agency's three fields. Printed by the class
							 * that owns them, so this table and the admin list
							 * cannot drift apart, and the editor script finds
							 * the markup it expects on both.
							 */
							foreach (['mb_versanddatum', 'mb_sendungsnummer', 'mb_anmerkungen'] as $mb_field) :
								?>
								<td class="mb-fulfilment__field">
									<?php SampleFulfilment::column($mb_field, $mb_order); ?>
								</td>
							<?php endforeach; ?>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>

		<?php if ($mb_page['pages'] > 1) : ?>
			<nav class="mb-fulfilment__pages" aria-label="<?php esc_attr_e('Order pages', 'granola'); ?>">
				<?php if ($mb_request['page'] > 1) : ?>
					<a href="<?php echo esc_url(panel_url(['mbfp' => $mb_request['page'] - 1])); ?>">
						<?php esc_html_e('Previous', 'granola'); ?>
					</a>
				<?php endif; ?>

				<span>
					<?php
					printf(
						/* translators: 1: current page number, 2: total pages. */
						esc_html__('Page %1$s of %2$s', 'granola'),
						esc_html(number_format_i18n($mb_request['page'])),
						esc_html(number_format_i18n($mb_page['pages']))
					);
					?>
				</span>

				<?php if ($mb_request['page'] < $mb_page['pages']) : ?>
					<a href="<?php echo esc_url(panel_url(['mbfp' => $mb_request['page'] + 1])); ?>">
						<?php esc_html_e('Next', 'granola'); ?>
					</a>
				<?php endif; ?>
			</nav>
		<?php endif; ?>

	<?php endif; ?>

</div>
