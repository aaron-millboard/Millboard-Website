<?php

/**
 * Sample stock -- what is left, and when to reorder.
 *
 * The second half of the German fulfilment spreadsheet. `Musterversand`
 * replaced its `DE Samples` tab (the dispatch queue); this replaces its
 * `Sample KPI` tab, which the agency used to know when a colour was running
 * low and needed ordering.
 *
 * ⚠️ THE SPREADSHEET IS THE SPEC, and its formulas were DERIVED FROM ITS OWN
 * NUMBERS rather than guessed. Checked against `Millboard Germany Samples
 * (2).xlsx` row by row:
 *
 *   weekly rate   = requests in the window / WINDOW_WEEKS
 *   weeks left    = stock / weekly rate            (infinite when rate is 0)
 *   need          = (TARGET_WEEKS x weekly rate) - stock, never below 0
 *   boxes         = need / pieces per box
 *
 * TARGET_WEEKS is 12 because that is the only figure that reproduces the
 * sheet: Antique Oak 12x6-54 = 18, Burnt Cedar 12x3.1-0 = 37, Salt Blue
 * 12x1.6-3 = 16. docs/sample-stock-selfcheck.php asserts a dozen of its real
 * rows, so a change that stops matching the sheet fails rather than quietly
 * reordering the wrong amount.
 *
 * ⚠️ ONE DELIBERATE DIFFERENCE FROM THE SHEET. Its "Boxes" column divides by
 * 10, 11 or 17, which are the pack sizes of the 100mm samples, so it only
 * ever tracked those. Aaron, 18 Nov 2026, asked for "one per colour per
 * size", so the 300mm samples get their own rows with their own pack sizes
 * (3 and 5). The pack size is read from the product title rather than a map
 * per range, so a new range needs no code.
 *
 * ⚠️ Demand is COUNTED FROM REAL ORDERS, not typed in. In the sheet somebody
 * tallied the request column by hand. The German orders are in WordPress now,
 * so the only thing left for the agency to enter is the stock figure, which
 * is the one number the site cannot know.
 */

namespace Granola\Components\WC_Account_Stock;

use Theme\WooCommerce\SampleCatalogueImport;

/** The account endpoint, so /mein-konto/bestand/ resolves. */
const ENDPOINT = 'stock';

/**
 * Where the stock screen exists at all.
 *
 * Germany, for the same reason the dispatch queue is German-only: it is the
 * market fulfilled by an outside agency off a spreadsheet.
 */
const SITES = ['/de-de/'];

/** Switch another market on without a deploy. */
const OPTION = 'millboard_stock_enabled';

/** sku => units in hand. The only figure a person types. */
const STOCK_OPTION = 'millboard_sample_stock';

/** How many weeks of orders make up "recent" demand. Matches the sheet. */
const WINDOW_WEEKS = 8;

/** How many weeks of cover a reorder aims at. Matches the sheet. */
const TARGET_WEEKS = 12;

/** Below this many weeks left, it is an emergency. */
const WEEKS_EMERGENCY = 6;

/** Below this many weeks left, order now. */
const WEEKS_ORDER = 8;

/** Within this proportion of the previous rate, demand counts as steady. */
const TREND_BAND = 0.10;

/**
 * Is the stock screen offered on this site?
 */
function is_stock_site(): bool
{
    $site = \get_blog_details(\get_current_blog_id());
    $path = $site ? \trailingslashit((string) $site->path) : '/';

    $sites = SITES;

    if ('yes' === \get_option(OPTION)) {
        $sites[] = $path;
    }

    return \in_array($path, \apply_filters('millboard_stock_sites', $sites), true);
}

/**
 * May this person see and edit the stock screen?
 *
 * The same people who work the dispatch queue: the agency, and German staff.
 * Deliberately reuses that gate rather than inventing a second answer to the
 * same question.
 */
function can_work_stock(?int $user_id = null): bool
{
    if (!is_stock_site()) {
        return false;
    }

    return \Granola\Components\WC_Account_Fulfilment\can_work_fulfilment($user_id);
}

/**
 * How many pieces come in one box of this sample, from its title.
 *
 * Titles read "Enhanced Grain - 10 x 100mm Limed Oak" or "Millboard Envello
 * Shadow Line Sample Box A - 17 x 100mm Antique Oak". The first number is the
 * count, the second the length.
 *
 * ⚠️ Returns 0 when the title does not say, and the caller shows a dash
 * rather than a box figure. Guessing a pack size would have the agency order
 * the wrong quantity, which is worse than not answering.
 */
function pieces_per_box(string $title): int
{
    if (\preg_match('/(\d+)\s*x\s*(\d+)\s*mm/i', $title, $m)) {
        return (int) $m[1];
    }

    return 0;
}

/**
 * The sample length in millimetres, so rows can be grouped by size.
 */
function sample_length(string $title): int
{
    if (\preg_match('/(\d+)\s*x\s*(\d+)\s*mm/i', $title, $m)) {
        return (int) $m[2];
    }

    return 0;
}

/**
 * Everything the numbers say about one line.
 *
 * PURE: no WordPress, no database, no clock. That is what lets
 * docs/sample-stock-selfcheck.php run it against the spreadsheet's own rows
 * in plain PHP, which is the only way to know the formulas still match the
 * thing they were copied from.
 *
 * @return array{rate:float,previous:float,trend:string,weeks:?float,status:string,need:int,boxes:?float}
 */
function metrics(int $stock, int $recent, int $previous, int $per_box): array
{
    $rate = \round($recent / WINDOW_WEEKS, 1);
    $was = \round($previous / WINDOW_WEEKS, 1);

    // No demand at all is its own answer. Dividing by it is not.
    if ($rate <= 0) {
        return [
            'rate' => 0.0,
            'previous' => $was,
            'trend' => 'none',
            'weeks' => null,
            'status' => 'none',
            'need' => 0,
            'boxes' => null,
        ];
    }

    $weeks = \round($stock / $rate, 1);
    $need = (int) \max(0, \round((TARGET_WEEKS * $rate) - $stock));

    if ($weeks < WEEKS_EMERGENCY) {
        $status = 'emergency';
    } elseif ($weeks < WEEKS_ORDER) {
        $status = 'order';
    } else {
        $status = 'ok';
    }

    /*
     * ⚠️ A BAND, not a comparison, and the sheet is why.
     *
     * Enhanced Grain Ashwood went 8.0 to 7.4 and the sheet calls it Stable;
     * Ebony Grey went 4.1 to 3.6, a SMALLER drop, and it calls that Down. So
     * it is not absolute. As a proportion it lands at 10%: Board & Batten+
     * Jarrah at exactly -10% reads Stable, Ebony Grey at -12.2% reads Down.
     *
     * Straight equality would have marked a dozen steady colours as falling
     * and had the agency chasing noise.
     */
    if ($was <= 0) {
        $trend = $rate > 0 ? 'up' : 'stable';
    } elseif (\abs($rate - $was) / $was <= TREND_BAND) {
        $trend = 'stable';
    } else {
        $trend = $rate > $was ? 'up' : 'down';
    }

    return [
        'rate' => $rate,
        'previous' => $was,
        'trend' => $trend,
        'weeks' => $weeks,
        'status' => $status,
        'need' => $need,
        'boxes' => $per_box > 0 ? \round($need / $per_box, 2) : null,
    ];
}

/**
 * Every sample product on this site, as sku => title.
 *
 * ⚠️ Read from the portal-SKU meta, NOT from mb_sof_catalogue_by_id(). That
 * catalogue is built per viewer and cached in a shared transient, so the rows
 * would change depending on who opened the screen and what the POS allow-list
 * says. A stock list has to be the same list for everyone counting the same
 * shelf.
 *
 * @return array<string,string>
 */
function samples(): array
{
    $cached = \get_transient('millboard_stock_samples');

    if (\is_array($cached)) {
        return $cached;
    }

    global $wpdb;

    $rows = $wpdb->get_results($wpdb->prepare(
        "SELECT pm.post_id, pm.meta_value AS sku, p.post_title, p.post_parent
           FROM {$wpdb->postmeta} pm
           JOIN {$wpdb->posts} p ON p.ID = pm.post_id
          WHERE pm.meta_key = %s
            AND pm.meta_value <> ''
            AND p.post_status IN ('publish', 'private')",
        SampleCatalogueImport::META_PORTAL_SKU
    ));

    $out = [];

    foreach ((array) $rows as $row) {
        $sku = \strtoupper(\trim((string) $row->sku));

        if ('' === $sku || isset($out[$sku])) {
            continue;
        }

        $title = (string) $row->post_title;

        // A variation's own title is "Product - Attribute"; the parent's is
        // the readable one, and the pack size lives there.
        if ($row->post_parent) {
            $parent = \get_post((int) $row->post_parent);

            if ($parent && \strlen((string) $parent->post_title) > \strlen($title)) {
                $title = (string) $parent->post_title;
            }
        }

        $out[$sku] = $title;
    }

    \ksort($out);
    \set_transient('millboard_stock_samples', $out, 6 * HOUR_IN_SECONDS);

    return $out;
}

/**
 * Units ordered per SKU between two dates.
 *
 * ⚠️ Orders are HPOS, so the dates come from `wc_orders`, not `posts`. The
 * SKU is matched through the line's product or variation id rather than its
 * name: an item's name is a snapshot of the product title AT THE TIME OF THE
 * ORDER, so a renamed colour would split into two rows and both would be
 * wrong.
 *
 * @return array<string,int> sku => units
 */
function demand(string $from, string $to): array
{
    global $wpdb;

    $orders = $wpdb->prefix . 'wc_orders';
    $items = $wpdb->prefix . 'woocommerce_order_items';
    $itemmeta = $wpdb->prefix . 'woocommerce_order_itemmeta';

    $rows = $wpdb->get_results($wpdb->prepare(
        "SELECT UPPER(TRIM(sku.meta_value)) AS sku, SUM(qty.meta_value + 0) AS units
           FROM {$orders} o
           JOIN {$items} oi ON oi.order_id = o.id AND oi.order_item_type = 'line_item'
           JOIN {$itemmeta} qty ON qty.order_item_id = oi.order_item_id AND qty.meta_key = '_qty'
           JOIN {$itemmeta} pid ON pid.order_item_id = oi.order_item_id
                               AND pid.meta_key IN ('_variation_id', '_product_id')
                               AND pid.meta_value <> '0'
           JOIN {$wpdb->postmeta} sku ON sku.post_id = pid.meta_value
                                     AND sku.meta_key = %s
                                     AND sku.meta_value <> ''
          WHERE o.type = 'shop_order'
            AND o.status NOT IN ('wc-cancelled', 'wc-failed', 'trash')
            AND o.date_created_gmt >= %s
            AND o.date_created_gmt < %s
          GROUP BY sku",
        SampleCatalogueImport::META_PORTAL_SKU,
        $from,
        $to
    ));

    $out = [];

    foreach ((array) $rows as $row) {
        $out[(string) $row->sku] = (int) $row->units;
    }

    return $out;
}

/**
 * The stock figures somebody has typed, as sku => units.
 *
 * @return array<string,int>
 */
function stock_levels(): array
{
    $stored = \get_option(STOCK_OPTION, []);

    if (!\is_array($stored)) {
        return [];
    }

    $out = [];

    foreach ($stored as $sku => $units) {
        $sku = \strtoupper(\trim((string) $sku));

        if ('' !== $sku) {
            $out[$sku] = (int) $units;
        }
    }

    return $out;
}

/**
 * The whole table, ready to render.
 *
 * @return array<int,array<string,mixed>>
 */
function rows(): array
{
    $samples = samples();
    $stock = stock_levels();

    $now = \current_time('mysql', true);
    $window = WINDOW_WEEKS * 7;

    $recent_from = \gmdate('Y-m-d H:i:s', \strtotime($now . ' -' . $window . ' days'));
    $previous_from = \gmdate('Y-m-d H:i:s', \strtotime($now . ' -' . ($window * 2) . ' days'));

    $recent = demand($recent_from, $now);
    $previous = demand($previous_from, $recent_from);

    $rows = [];

    foreach ($samples as $sku => $title) {
        $per_box = pieces_per_box($title);

        $rows[] = \array_merge(
            [
                'sku' => $sku,
                'title' => $title,
                'length' => sample_length($title),
                'per_box' => $per_box,
                'stock' => $stock[$sku] ?? 0,
                'recent' => $recent[$sku] ?? 0,
            ],
            metrics($stock[$sku] ?? 0, $recent[$sku] ?? 0, $previous[$sku] ?? 0, $per_box)
        );
    }

    /*
     * Worst first. The whole point of the screen is "what do I need to order",
     * and a list that opens on everything that is fine buries the four lines
     * that are not.
     */
    $order = ['emergency' => 0, 'order' => 1, 'ok' => 2, 'none' => 3];

    \usort($rows, static function (array $a, array $b) use ($order): int {
        return [$order[$a['status']] ?? 9, $a['weeks'] ?? PHP_INT_MAX, $a['title']]
            <=> [$order[$b['status']] ?? 9, $b['weeks'] ?? PHP_INT_MAX, $b['title']];
    });

    return $rows;
}

/**
 * Save the typed stock figures.
 *
 * Only ever writes SKUs that are actually samples on this site, so a posted
 * field for anything else is dropped rather than stored and shown later.
 *
 * @param array<string,mixed> $posted
 */
function save_stock(array $posted): int
{
    $samples = samples();
    $current = stock_levels();
    $changed = 0;

    foreach ($posted as $sku => $value) {
        $sku = \strtoupper(\trim((string) $sku));

        if (!isset($samples[$sku]) || '' === \trim((string) $value)) {
            continue;
        }

        $units = \max(0, (int) $value);

        if (($current[$sku] ?? null) !== $units) {
            $current[$sku] = $units;
            ++$changed;
        }
    }

    if ($changed) {
        \update_option(STOCK_OPTION, $current, false);
        \update_option(STOCK_OPTION . '_updated', \current_time('mysql', true), false);
    }

    return $changed;
}

/**
 * When the stock figures were last touched, for the "as at" line.
 */
function stock_updated(): string
{
    return (string) \get_option(STOCK_OPTION . '_updated', '');
}
