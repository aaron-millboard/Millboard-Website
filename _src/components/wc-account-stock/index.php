<?php

/**
 * Sample stock -- what is left, and what to reorder.
 *
 * The `Sample KPI` tab of the German fulfilment spreadsheet, as a screen. The
 * agency types one number per line, the stock they have counted; everything
 * else is worked out from real orders.
 *
 * ⚠️ The copy is written in German, not English awaiting a translation, for
 * the same reason the dispatch queue's is: everyone who can open this panel
 * is German, Millboard's own staff on de-de and the fulfilment agency. If a
 * second market ever wants this screen, these strings move to English source
 * and go through Loco.
 */

use function Granola\Components\WC_Account_Stock\rows;
use function Granola\Components\WC_Account_Stock\stock_updated;

$mb_rows = rows();

$mb_counts = ['emergency' => 0, 'order' => 0, 'uncounted' => 0, 'ok' => 0, 'none' => 0];

foreach ($mb_rows as $mb_row) {
    ++$mb_counts[$mb_row['status']];
}

$mb_labels = [
    'emergency' => __('Dringend', 'granola'),
    'order' => __('Jetzt bestellen', 'granola'),
    /*
     * Not counted is its own answer, not a zero. See the note in rows():
     * treating an uncounted row as empty marks every colour urgent on the
     * day this screen opens and the column stops meaning anything.
     */
    'uncounted' => __('Nicht gezählt', 'granola'),
    'ok' => __('OK', 'granola'),
    'none' => __('Keine Nachfrage', 'granola'),
];

$mb_trends = [
    'up' => '&uarr;',
    'down' => '&darr;',
    'stable' => '&rarr;',
    'none' => '&ndash;',
];

$mb_updated = stock_updated();

?>
<div class="mb-stock">

    <p class="mb-stock__intro">
        Bestand der Muster. Tragen Sie ein, wie viele Stück Sie gezählt haben.
        Verbrauch, Reichweite und Nachbestellmenge werden aus den tatsächlichen
        Bestellungen der letzten 8 Wochen berechnet.
    </p>

    <?php if ($mb_updated) : ?>
        <p class="mb-stock__updated">
            <?php
            printf(
                /* translators: %s: date and time the stock figures were last saved. */
                esc_html__('Bestand zuletzt aktualisiert: %s', 'granola'),
                esc_html(wp_date('d.m.Y, H:i', strtotime($mb_updated)))
            );
            ?>
        </p>
    <?php endif; ?>

    <?php
    // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- display only, set by our own redirect.
    $mb_saved = isset($_GET['mbstock']) ? sanitize_key(wp_unslash($_GET['mbstock'])) : '';
    ?>
    <?php if ('saved' === $mb_saved) : ?>
        <p class="mb-stock__saved" role="status">Bestand gespeichert.</p>
    <?php elseif ('none' === $mb_saved) : ?>
        <p class="mb-stock__saved" role="status">Keine Änderungen zu speichern.</p>
    <?php endif; ?>

    <?php if ($mb_counts['emergency'] || $mb_counts['order']) : ?>
        <p class="mb-stock__alert" role="status">
            <?php
            printf(
                /* translators: 1: how many are urgent, 2: how many to order now. */
                esc_html__('%1$d dringend, %2$d jetzt bestellen.', 'granola'),
                (int) $mb_counts['emergency'],
                (int) $mb_counts['order']
            );
            ?>
        </p>
    <?php endif; ?>

    <?php if ($mb_counts['uncounted']) : ?>
        <p class="mb-stock__note" role="status">
            <?php
            printf(
                /* translators: %d: how many samples have no counted stock figure yet. */
                esc_html__(
                    '%d Muster ohne Bestandsangabe. Reichweite und Nachbestellmenge '
                    . 'lassen sich erst berechnen, wenn der Bestand eingetragen ist.',
                    'granola'
                ),
                (int) $mb_counts['uncounted']
            );
            ?>
        </p>
    <?php endif; ?>

    <?php if (!$mb_rows) : ?>

        <p class="mb-stock__empty">
            Es sind keine Muster hinterlegt. Sobald der Musterkatalog auf dieser
            Seite eingerichtet ist, erscheinen sie hier.
        </p>

    <?php else : ?>

        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="mb-stock__form">
            <?php wp_nonce_field('millboard_stock_save'); ?>
            <input type="hidden" name="action" value="millboard_stock_save" />

            <table class="mb-stock__table">
                <thead>
                    <tr>
                        <th scope="col">Muster</th>
                        <th scope="col" class="mb-stock__num">Bestand</th>
                        <th scope="col" class="mb-stock__num">Anfragen<br><span>8 Wochen</span></th>
                        <th scope="col" class="mb-stock__num">Pro Woche</th>
                        <th scope="col" class="mb-stock__num">Trend</th>
                        <th scope="col" class="mb-stock__num">Reichweite<br><span>Wochen</span></th>
                        <th scope="col">Status</th>
                        <th scope="col" class="mb-stock__num">Nachbestellen</th>
                        <th scope="col" class="mb-stock__num">Kartons</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                foreach ($mb_rows as $mb_row) :
                    /*
                     * The field is keyed on the row key (parent product id and
                     * sample size), never the SKU: a SKU is editable in admin
                     * and would orphan the figure somebody typed. The id is
                     * the same key with the colon swapped out, so it stays a
                     * sane CSS selector.
                     */
                    $mb_id = 'mb-stock-' . str_replace(':', '-', (string) $mb_row['key']);
                    ?>
                    <tr class="mb-stock__row mb-stock__row--<?php echo esc_attr($mb_row['status']); ?>">

                        <th scope="row" class="mb-stock__item">
                            <span class="mb-stock__title"><?php echo esc_html($mb_row['title']); ?></span>
                            <span class="mb-stock__size"><?php echo esc_html($mb_row['size_label']); ?></span>
                            <span class="mb-stock__sku"><?php echo esc_html($mb_row['sku']); ?></span>
                        </th>

                        <td class="mb-stock__num">
                            <label class="screen-reader-text" for="<?php echo esc_attr($mb_id); ?>">
                                <?php
                                printf(
                                    /* translators: 1: the sample name, 2: the sample size. */
                                    esc_html__('Bestand für %1$s, %2$s', 'granola'),
                                    esc_html($mb_row['title']),
                                    esc_html($mb_row['size_label'])
                                );
                                ?>
                            </label>
                            <input
                                type="number"
                                min="0"
                                step="1"
                                inputmode="numeric"
                                id="<?php echo esc_attr($mb_id); ?>"
                                name="stock[<?php echo esc_attr($mb_row['key']); ?>]"
                                <?php
                                /*
                                 * Empty, not 0, when nobody has counted this
                                 * one. A 0 in the box reads as "counted, and
                                 * there are none left", which is a different
                                 * and much more alarming thing.
                                 */
                                ?>
                                value="<?php echo $mb_row['counted'] ? esc_attr((string) $mb_row['stock']) : ''; ?>"
                                placeholder="&ndash;"
                                class="mb-stock__input"
                            />
                        </td>

                        <td class="mb-stock__num"><?php echo esc_html((string) $mb_row['recent']); ?></td>
                        <td class="mb-stock__num"><?php echo esc_html(number_format_i18n($mb_row['rate'], 1)); ?></td>

                        <td class="mb-stock__num mb-stock__trend mb-stock__trend--<?php echo esc_attr($mb_row['trend']); ?>">
                            <?php echo wp_kses_post($mb_trends[$mb_row['trend']] ?? ''); ?>
                        </td>

                        <td class="mb-stock__num">
                            <?php echo null === $mb_row['weeks'] ? '&ndash;' : esc_html(number_format_i18n($mb_row['weeks'], 1)); ?>
                        </td>

                        <td>
                            <span class="mb-stock__status mb-stock__status--<?php echo esc_attr($mb_row['status']); ?>">
                                <?php echo esc_html($mb_labels[$mb_row['status']]); ?>
                            </span>
                        </td>

                        <td class="mb-stock__num mb-stock__need">
                            <?php echo $mb_row['need'] > 0 ? esc_html((string) $mb_row['need']) : '&ndash;'; ?>
                        </td>

                        <td class="mb-stock__num">
                            <?php
                            /*
                             * A dash, not a 0, when the pack size is unknown.
                             * A 0 would read as "no boxes needed" on a line
                             * that may well need several.
                             */
                            echo null === $mb_row['boxes'] || $mb_row['need'] <= 0
                                ? '&ndash;'
                                : esc_html(number_format_i18n($mb_row['boxes'], 2));
                            ?>
                        </td>

                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>

            <p class="mb-stock__actions">
                <button type="submit" class="button mb-stock__save">
                    Bestand speichern
                </button>
            </p>
        </form>

    <?php endif; ?>

</div>
