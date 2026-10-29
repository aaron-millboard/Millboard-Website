<?php

/**
 * Sample fulfilment -- the queue.
 *
 * A German order, everything needed to post it, and the three fields the
 * agency fills in. The three cells are rendered by SampleFulfilment::column(),
 * the same call the admin list makes, so both screens produce identical markup
 * and the one editor script drives both.
 *
 * ⚠️ The copy is written in German, not English awaiting a translation.
 * Everyone who can open this panel is German -- Millboard's own staff on
 * de-de and the fulfilment agency -- and every field in it is already named
 * after the spreadsheet column it replaces. A single-market screen does not
 * need a translation round-trip to be readable by the only people allowed in.
 *
 * The consequence, so nobody is surprised by it: switching another market on
 * through `millboard_fulfilment_enabled` or the `millboard_fulfilment_sites`
 * filter would show that market German. If a second market ever wants this,
 * these strings move to English source and go through Loco.
 */

use Theme\WooCommerce\SampleFulfilment;

use function Granola\Components\WC_Account_Fulfilment\address_line;
use function Granola\Components\WC_Account_Fulfilment\company_line;
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

		<?php if ($mb_request['historic']) : ?>
			<input type="hidden" name="mbfold" value="1">
		<?php endif; ?>

		<label class="mb-fulfilment__search">
			<span class="screen-reader-text"><?php esc_html_e('Bestellungen durchsuchen', 'granola'); ?></span>
			<input
				type="search"
				name="mbfq"
				value="<?php echo esc_attr($mb_request['search']); ?>"
				placeholder="<?php esc_attr_e('Name, E-Mail, Ort oder Bestellnummer', 'granola'); ?>"
			>
		</label>

		<button type="submit" class="mb-account-btn"><?php esc_html_e('Suchen', 'granola'); ?></button>

		<?php if ('' !== $mb_request['search']) : ?>
			<a class="mb-account-btn mb-account-btn--ghost" href="<?php echo esc_url(panel_url(['mbfq' => null, 'mbfp' => null])); ?>">
				<?php esc_html_e('Zurücksetzen', 'granola'); ?>
			</a>
		<?php endif; ?>

		<span class="mb-fulfilment__toggle">
			<?php if ($mb_request['outstanding']) : ?>
				<a class="mb-account-link" href="<?php echo esc_url(panel_url(['mbfall' => 1, 'mbfp' => null])); ?>">
					<?php esc_html_e('Erledigte ebenfalls anzeigen', 'granola'); ?>
				</a>
			<?php else : ?>
				<a class="mb-account-link" href="<?php echo esc_url(panel_url(['mbfall' => null, 'mbfp' => null])); ?>">
					<?php esc_html_e('Nur offene Bestellungen anzeigen', 'granola'); ?>
				</a>
			<?php endif; ?>

			<?php
			/*
			 * The older orders are one link away rather than gone. They were
			 * fulfilled from the spreadsheet and are here for reference, so
			 * hiding them by default is what makes this a queue -- but the
			 * agency still needs to be able to look one up.
			 */
			if ($mb_request['historic']) :
				?>
				<a class="mb-account-link" href="<?php echo esc_url(panel_url(['mbfold' => null, 'mbfp' => null])); ?>">
					<?php esc_html_e('Ältere Bestellungen ausblenden', 'granola'); ?>
				</a>
			<?php else : ?>
				<a class="mb-account-link" href="<?php echo esc_url(panel_url(['mbfold' => 1, 'mbfp' => null])); ?>">
					<?php
					printf(
						/* translators: %s: the date this flow went live, e.g. 26.10.2026. */
						esc_html__('Auch Bestellungen vor dem %s anzeigen', 'granola'),
						esc_html(date_i18n(get_option('date_format'), (int) strtotime(SampleFulfilment::LIVE_FROM)))
					);
					?>
				</a>
			<?php endif; ?>
		</span>
	</form>

	<p class="mb-fulfilment__count">
		<?php
		if ($mb_request['outstanding']) {
			printf(
				/* translators: %s: a number of orders. */
				esc_html(_n('%s Bestellung noch offen.', '%s Bestellungen noch offen.', $mb_page['total'], 'granola')),
				esc_html(number_format_i18n($mb_page['total']))
			);
		} else {
			printf(
				/* translators: %s: a number of orders. */
				esc_html(_n('%s Bestellung.', '%s Bestellungen.', $mb_page['total'], 'granola')),
				esc_html(number_format_i18n($mb_page['total']))
			);
		}
		?>
	</p>

	<?php if (empty($mb_orders)) : ?>

		<p class="mb-fulfilment__empty">
			<?php
			if ('' !== $mb_request['search']) {
				esc_html_e('Keine Treffer für diese Suche.', 'granola');
			} elseif ($mb_request['outstanding']) {
				esc_html_e('Nichts offen. Alle Bestellungen sind erledigt.', 'granola');
			} else {
				esc_html_e('Noch keine Bestellungen.', 'granola');
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
						<th scope="col"><?php esc_html_e('Erledigt', 'granola'); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php
					foreach ($mb_orders as $mb_order) :
						// Greyed once it has been ticked off, which is only ever visible
						// in the "show everything" view -- the queue itself has none.
						$mb_done = '' !== (string) $mb_order->get_meta(SampleFulfilment::META_DONE);
						?>
						<tr<?php echo $mb_done ? ' class="is-done"' : ''; ?>>
							<th scope="row" class="mb-fulfilment__order">
								<span class="mb-fulfilment__number">#<?php echo esc_html($mb_order->get_order_number()); ?></span>
								<span class="mb-fulfilment__date">
									<?php echo esc_html(wc_format_datetime($mb_order->get_date_created())); ?>
								</span>
								<span class="mb-fulfilment__status mb-fulfilment__status--<?php echo esc_attr($mb_order->get_status()); ?>" data-mb-status="<?php echo esc_attr($mb_order->get_id()); ?>">
									<?php echo esc_html(wc_get_order_status_name($mb_order->get_status())); ?>
								</span>
							</th>

							<?php
							/*
							 * Read in the order a parcel label is written:
							 * salutation and name, company, street, then the
							 * ways to reach them. The e-mail is here so the
							 * search box can find an order by it, which is how
							 * the agency is asked for one.
							 */
							$mb_company = company_line($mb_order);
							?>
							<td class="mb-fulfilment__who">
								<span class="mb-fulfilment__name"><?php echo esc_html(customer_line($mb_order)); ?></span>

								<?php if ('' !== $mb_company) : ?>
									<span class="mb-fulfilment__company"><?php echo esc_html($mb_company); ?></span>
								<?php endif; ?>

								<span class="mb-fulfilment__address"><?php echo esc_html(address_line($mb_order)); ?></span>

								<?php if ($mb_order->get_billing_email()) : ?>
									<span class="mb-fulfilment__email"><?php echo esc_html($mb_order->get_billing_email()); ?></span>
								<?php endif; ?>

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

							<?php
							/*
							 * The tick, and the only way a line leaves this list.
							 *
							 * Deliberately separate from the dispatch date. Most orders are
							 * posted and get a date; some end without a parcel and get only
							 * a note, and both need a way out of the queue.
							 */
							?>
							<td class="mb-fulfilment__tick">
								<?php SampleFulfilment::done_button($mb_order); ?>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>

		<?php if ($mb_page['pages'] > 1) : ?>
			<nav class="mb-fulfilment__pages" aria-label="<?php esc_attr_e('Seiten', 'granola'); ?>">
				<?php if ($mb_request['page'] > 1) : ?>
					<a class="mb-account-link" href="<?php echo esc_url(panel_url(['mbfp' => $mb_request['page'] - 1])); ?>">
						<?php esc_html_e('Zurück', 'granola'); ?>
					</a>
				<?php endif; ?>

				<span>
					<?php
					printf(
						/* translators: 1: current page number, 2: total pages. */
						esc_html__('Seite %1$s von %2$s', 'granola'),
						esc_html(number_format_i18n($mb_request['page'])),
						esc_html(number_format_i18n($mb_page['pages']))
					);
					?>
				</span>

				<?php if ($mb_request['page'] < $mb_page['pages']) : ?>
					<a class="mb-account-link" href="<?php echo esc_url(panel_url(['mbfp' => $mb_request['page'] + 1])); ?>">
						<?php esc_html_e('Weiter', 'granola'); ?>
					</a>
				<?php endif; ?>
			</nav>
		<?php endif; ?>

	<?php endif; ?>

</div>
