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
 *   weeks left    = stock / weekly rate            (none when the rate is 0)
 *   need          = (TARGET_WEEKS x weekly rate) - stock, never below 0
 *   boxes         = need / pieces per box
 *
 * TARGET_WEEKS is 12 because that is the only figure that reproduces the
 * sheet: Antique Oak 12x6-54 = 18, Burnt Cedar 12x3.1-0 = 37, Salt Blue
 * 12x1.6-3 = 16. docs/sample-stock-selfcheck.php asserts a dozen of its real
 * rows, so a change that stops matching the sheet fails rather than quietly
 * reordering the wrong amount.
 *
 * ⚠️⚠️ THERE ARE TWO SEPARATE SAMPLE PRODUCT SETS ON de-de, AND THE FIRST
 * VERSION OF THIS FILE COUNTED THE WRONG ONE. Every row read zero demand on
 * staging, and it was not staging being quiet -- the join could never match:
 *
 *   the staff portal catalogue   13258  AA10B    "Enhanced Grain - 10 x 100mm
 *                                                 Golden Oak"
 *                                                a BOX OF TEN the agency
 *                                                REORDERS, 284 products,
 *                                                carries _millboard_portal_sku
 *
 *   what customers actually      4731   MDE176G  "Enhanced Grain 176mm Golden
 *   order                                         Oak", a variable product
 *                                                whose `handmuster` VARIATION
 *                                                is ONE FREE SAMPLE. Carries
 *                                                no portal SKU at all.
 *
 * The agency's shelf holds the second kind and is replenished in boxes of the
 * first. So demand is counted against the CUSTOMER products, and the portal
 * catalogue is used for one thing only: how many pieces come in a reorder box.
 * The spreadsheet's rows name the customer products exactly -- "Enhanced Grain
 * Golden Oak", "Weathered Oak Vintage", "Board & Batten+ Golden Oak".
 *
 * ⚠️ WHICH SAMPLES ARE COUNTED HAS CHANGED ONCE, AND THE SHEET WAS RIGHT
 * FIRST TIME. Its "Boxes" column divides by 10, 11 or 17, the pack sizes of
 * the 100mm samples, so it only ever tracked those. On 18 Nov Aaron asked
 * for "one per colour per size" and the 300mm samples got rows of their own.
 * On 24 Nov, with neither the 300mm samples nor the Enhanced Grain 126mm
 * range actually available in Germany, both came off the ordering list and
 * off this screen with them: counting an empty shelf is worse than not
 * asking. See stocked(), which is filtered rather than fixed so the next
 * market can hold a different set.
 *
 * ⚠️ Demand is COUNTED FROM REAL ORDERS, not typed in. In the sheet somebody
 * tallied the request column by hand. The German orders are in WordPress now,
 * so the only thing left for the agency to enter is the stock figure, which
 * is the one number the site cannot know.
 */

namespace Granola\Components\WC_Account_Stock;

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

/** row key => units in hand. The only figure a person types. */
const STOCK_OPTION = 'millboard_sample_stock';

/**
 * ⚠️ THE CACHE KEYS CARRY A VERSION, AND IT MUST BE BUMPED WHENEVER THE SHAPE
 * OF A CACHED ROW CHANGES.
 *
 * These hold derived structures, not scalars. A deploy that changes the shape
 * while keeping the key leaves the old shape in the cache for six hours, and
 * the screen fatals on the first field that moved -- for everyone, with no
 * way to tell from the error that a stale transient is the cause.
 */
const SAMPLES_TRANSIENT = 'millboard_stock_samples_v2';
const PACKS_TRANSIENT = 'millboard_stock_packs_v2';

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

/** The product attribute that says which sample something is. */
const SIZE_ATTRIBUTE = 'pa_sample-size';

/**
 * The attribute value that means "the actual board", not a sample.
 *
 * 266 of the 464 variations on de-de are this. They are the thing the sample
 * is cut from, so counting them as stock would invent hundreds of empty rows.
 */
const SIZE_FULL = 'full';

/**
 * Order statuses that are not demand.
 *
 * A draft checkout is an abandoned basket, not a request, and counting one
 * would have the agency reorder against samples nobody ever asked for.
 */
const DEAD_STATUSES = ['wc-cancelled', 'wc-failed', 'wc-checkout-draft', 'wc-pending', 'trash'];

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
 * One sample size, spelled the one way.
 *
 * ⚠️ THE SLUG CHURNED, AND AN ORDER LINE KEEPS WHATEVER WAS CURRENT WHEN IT
 * WAS PLACED. The German orders carry three spellings of the same thing:
 * `handmuster` (2020 lines), `hand` (60) and `small` (3). Keying on the raw
 * value would split one colour into three rows and under-report every one of
 * them, which is the same class of mistake as matching on the item name.
 */
function canonical_size(string $size): string
{
    $size = \strtolower(\trim($size));

    $aliases = \apply_filters('millboard_stock_size_aliases', [
        'hand' => 'handmuster',
        'small' => 'handmuster',
        'large' => 'grosse-muster',
        'gross-muster' => 'grosse-muster',
        'grosse_muster' => 'grosse-muster',
    ]);

    return (string) ($aliases[$size] ?? $size);
}

/**
 * How long a piece of this sample is, in millimetres.
 *
 * From the sample SKUs, which encode it: `AME105G` is the 100mm hand sample
 * of Enhanced Grain Golden Oak, `AME305G` the 300mm one. The slugs are the
 * German terms for the same two things, `Handmuster` and `Große Muster`.
 *
 * ⚠️ Returns 0 for anything else, and a row with no length gets no pack size
 * and shows a dash. A guessed length picks a guessed pack size, and the
 * agency orders the wrong quantity.
 */
function size_length(string $size): int
{
    $lengths = \apply_filters('millboard_stock_size_lengths', [
        'handmuster' => 100,
        'grosse-muster' => 300,
    ]);

    return (int) ($lengths[canonical_size($size)] ?? 0);
}

/**
 * A name reduced to the part worth matching on.
 *
 * Lower case, entities decoded, everything but letters and digits dropped, so
 * "Board &amp; Batten+" and "Envello Board & Batten" both become
 * `boardbatten`. That is what lets a customer product's own category find its
 * reorder box in the portal catalogue without a hand-written map of ranges.
 */
function match_key(string $text): string
{
    $text = \html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');

    return (string) \preg_replace('/[^a-z0-9]+/', '', \strtolower($text));
}

/**
 * Pieces per reorder box, as [match key][length in mm] => pieces.
 *
 * ⚠️ DERIVED FROM THE PORTAL CATALOGUE'S OWN TITLES, not written down here,
 * because the pack size is a commercial fact that belongs to the product and
 * a copy of it in theme code goes stale silently. Checked on de-de staging,
 * 18 Nov 2026 -- every range resolves to exactly one pack size per length:
 *
 *   Enhanced Grain / Weathered Oak / Lasta-Grip   10 x 100mm   3 x 300mm
 *   Envello Shadow Line+                          17 x 100mm   5 x 300mm
 *   Envello Board & Batten+                       11 x 100mm   3 x 300mm
 *
 * ⚠️ A leading number above 20 is a BOARD DIMENSION, not a pack: titles like
 * "100 x 150 x 32mm Bullnosed Board" and "220 x 110mm" are shaped the same.
 * Reading those as pack sizes is what broke an earlier pass over this
 * catalogue, so they are dropped rather than parsed.
 *
 * @return array<string,array<int,int>>
 */
function pack_sizes(): array
{
    $cached = \get_transient(PACKS_TRANSIENT);

    if (\is_array($cached)) {
        return $cached;
    }

    global $wpdb;

    $titles = $wpdb->get_col(
        "SELECT DISTINCT COALESCE(parent.post_title, p.post_title)
           FROM {$wpdb->postmeta} pm
           JOIN {$wpdb->posts} p ON p.ID = pm.post_id
      LEFT JOIN {$wpdb->posts} parent ON parent.ID = p.post_parent
          WHERE pm.meta_key = '_millboard_portal_sku'
            AND pm.meta_value <> ''"
    );

    /* key => length => pieces => how many titles said so */
    $seen = [];

    foreach ((array) $titles as $title) {
        if (!\preg_match('/(\d+)\s*x\s*(\d+)\s*mm/i', (string) $title, $m)) {
            continue;
        }

        $pieces = (int) $m[1];
        $length = (int) $m[2];

        if ($pieces < 1 || $pieces > 20 || $length < 1) {
            continue;
        }

        $seen[match_key((string) $title)][$length][$pieces] = true;
    }

    /*
     * Fold the titles into one answer per (range, length). A title is matched
     * by CONTAINING a range's key, so this stays keyed by the whole title and
     * the lookup does the containing -- that way a new range needs no code.
     */
    $out = [];

    foreach ($seen as $haystack => $lengths) {
        foreach ($lengths as $length => $counts) {
            // Two different pack sizes for one title is not an answer.
            $out[$haystack][$length] = 1 === \count($counts)
                ? (int) \array_key_first($counts)
                : 0;
        }
    }

    \set_transient(PACKS_TRANSIENT, $out, 6 * HOUR_IN_SECONDS);

    return $out;
}

/**
 * How many pieces come in one reorder box of this sample.
 *
 * The customer product knows its range through its own categories; the portal
 * catalogue knows the pack size. This is the join between them, and it is a
 * substring match on normalised names because the two sets share no key.
 *
 * ⚠️ Returns 0 -- a dash on screen -- whenever the answer is not exactly one
 * number: no length, no matching portal line, or two portal lines that
 * disagree. Short category names are skipped because "126mm" would match any
 * title with that board width in it, which is a coincidence, not a range.
 *
 * @param string[] $categories
 */
function pieces_per_box(array $categories, int $length): int
{
    if ($length < 1) {
        return 0;
    }

    $packs = pack_sizes();
    $found = [];

    foreach ($categories as $category) {
        $key = match_key($category);

        if (\strlen($key) < 6) {
            continue;
        }

        foreach ($packs as $haystack => $lengths) {
            if (!isset($lengths[$length]) || $lengths[$length] < 1) {
                continue;
            }

            if (false !== \strpos($haystack, $key)) {
                $found[$lengths[$length]] = true;
            }
        }
    }

    return 1 === \count($found) ? (int) \array_key_first($found) : 0;
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
 * Is this a sample the market actually holds, and therefore counts?
 *
 * Aaron, 24 Nov 2026: neither the 300mm samples nor the Enhanced Grain 126mm
 * range is available in Germany. They came off the ordering list the same
 * day, and a stock screen that still asks the agency to count them is asking
 * about an empty shelf.
 *
 * ⚠️ TWO DIFFERENT MEASUREMENTS. 300mm is a sample piece LENGTH, carried by
 * the `grosse-muster` size. 126mm is a board WIDTH, the name of an Enhanced
 * Grain range, and it sits in the product title. Matching one rule against
 * both would take out the wrong rows.
 *
 * Both lists are filters rather than literals in the loop, because the next
 * market to want this screen will stock a different set, and because turning
 * one back on should be one line rather than an edit to the query.
 */
function stocked(string $size, string $title): bool
{
    $sizes = \apply_filters('millboard_stock_sizes', ['handmuster']);

    if (!\in_array(canonical_size($size), (array) $sizes, true)) {
        return false;
    }

    foreach ((array) \apply_filters('millboard_stock_excluded_widths', ['126mm']) as $width) {
        if (false !== \stripos($title, (string) $width)) {
            return false;
        }
    }

    return true;
}

/**
 * Where a range sits in the order the agency wants to read it.
 *
 * Aaron, 24 Nov 2026: Enhanced Grain, Board & Batten, Shadowline, Weathered
 * Oak, Lasta-Grip. The same order the sample-ordering tab uses, so somebody
 * moving between the two screens finds things in the same place.
 *
 * ⚠️ Matched on the NORMALISED title, because the real ones carry a board
 * width, a registered-trade mark and a plus sign: "Enhanced Grain 176mm
 * Golden Oak", "Lasta-Grip® 200mm Golden Oak", "Board & Batten+ Ashwood".
 *
 * Anything not on the list sorts after, in its own alphabetical block,
 * rather than being dropped or silently landing first.
 */
function range_rank(string $title): int
{
    $key = (string) \preg_replace('/[^a-z0-9]+/', '', \strtolower(
        \html_entity_decode($title, ENT_QUOTES | ENT_HTML5, 'UTF-8')
    ));

    $order = \apply_filters('millboard_stock_range_order', [
        'enhancedgrain',
        'boardbatten',
        'shadowline',
        'weatheredoak',
        'lastagrip',
    ]);

    foreach (\array_values($order) as $i => $needle) {
        if (false !== \strpos($key, $needle)) {
            return $i;
        }
    }

    return \count($order);
}

/**
 * The key one row is stored and posted under.
 *
 * The PARENT product id, not a SKU and not a variation id. A variation id
 * churns -- two of the variations the German orders point at have already
 * been deleted and rebuilt -- and a SKU is editable in admin, which would
 * orphan the stock figure somebody typed. The parent id is the one thing that
 * survives both.
 */
function row_key(int $parent_id, string $size): string
{
    return $parent_id . ':' . canonical_size($size);
}

/**
 * Every sample a customer can order on this site, as key => details.
 *
 * ⚠️ ONE ROW PER COLOUR PER SIZE, which is what the parent product and the
 * sample-size attribute are between them: the parent IS the colour, the
 * attribute IS the size. Built from DISTINCT pairs because a parent carries
 * up to six duplicate variations of the same size, left behind by repeated
 * imports, and counting variations would show the same colour six times.
 *
 * @return array<string,array<string,mixed>>
 */
function samples(): array
{
    $cached = \get_transient(SAMPLES_TRANSIENT);

    if (\is_array($cached)) {
        return $cached;
    }

    global $wpdb;

    $rows = $wpdb->get_results($wpdb->prepare(
        "SELECT v.post_parent AS parent_id,
                pm.meta_value AS size,
                parent.post_title AS title,
                COALESCE(vsku.meta_value, '') AS vsku,
                COALESCE(psku.meta_value, '') AS psku
           FROM {$wpdb->posts} v
           JOIN {$wpdb->postmeta} pm ON pm.post_id = v.ID
                                    AND pm.meta_key = %s
           JOIN {$wpdb->posts} parent ON parent.ID = v.post_parent
      LEFT JOIN {$wpdb->postmeta} vsku ON vsku.post_id = v.ID
                                      AND vsku.meta_key = '_sku'
      LEFT JOIN {$wpdb->postmeta} psku ON psku.post_id = v.post_parent
                                      AND psku.meta_key = '_sku'
          WHERE v.post_type = 'product_variation'
            AND pm.meta_value NOT IN ('', %s)
            AND parent.post_status = 'publish'",
        'attribute_' . SIZE_ATTRIBUTE,
        SIZE_FULL
    ));

    /*
     * Gathered first, built second, because a parent carries up to six
     * duplicate variations of one size and the SKU worth showing may be on
     * any of them.
     */
    $gathered = [];

    foreach ((array) $rows as $row) {
        $parent_id = (int) $row->parent_id;
        $size = canonical_size((string) $row->size);
        $key = row_key($parent_id, $size);
        $vsku = \trim((string) $row->vsku);

        // Not stocked here, so not counted here.
        if (!stocked($size, (string) $row->title)) {
            continue;
        }

        $gathered[$key]['parent'] = $parent_id;
        $gathered[$key]['size'] = $size;
        $gathered[$key]['title'] = (string) $row->title;
        $gathered[$key]['psku'] = \trim((string) $row->psku);

        /*
         * The sample's OWN SKU is what identifies the piece on the shelf --
         * AMCBF105X, not the parent's MCBF360X, which the Handmuster and the
         * Große Muster row would otherwise both show. Variations that just
         * repeat the parent's SKU carry no extra information, so they are not
         * candidates.
         */
        if ('' !== $vsku && $vsku !== \trim((string) $row->psku)) {
            $gathered[$key]['candidates'][$vsku] = true;
        }
    }

    $out = [];

    foreach ($gathered as $key => $g) {
        $candidates = \array_keys($g['candidates'] ?? []);
        $length = size_length($g['size']);

        $categories = \wp_get_post_terms($g['parent'], 'product_cat', ['fields' => 'names']);
        $categories = \is_wp_error($categories) ? [] : (array) $categories;

        $out[$key] = [
            'key' => $key,
            'parent' => $g['parent'],
            'size' => $g['size'],
            // Two variations disagreeing is not an answer: fall back, never pick.
            'sku' => 1 === \count($candidates) ? $candidates[0] : $g['psku'],
            'title' => $g['title'],
            'length' => $length,
            'per_box' => pieces_per_box($categories, $length),
        ];
    }

    \uasort($out, static fn(array $a, array $b): int => [$a['title'], $a['length']] <=> [$b['title'], $b['length']]);

    \set_transient(SAMPLES_TRANSIENT, $out, 6 * HOUR_IN_SECONDS);

    return $out;
}

/**
 * Units ordered per row key between two dates.
 *
 * ⚠️ Orders are HPOS, so the dates come from `wc_orders`, not `posts`.
 *
 * ⚠️ Counted from the LINE'S OWN META, not from the variation it points at.
 * Every German sample line carries `_product_id` and `pa_sample-size`
 * itself -- 2083 of 2083 -- so a line still counts after its variation has
 * been deleted. Joining through the variation instead silently lost 22 of the
 * 43 units of Weathered Oak Embered, which would have halved that colour's
 * rate and told the agency to order half what it needs.
 *
 * @return array<string,int> row key => units
 */
function demand(string $from, string $to): array
{
    global $wpdb;

    $orders = $wpdb->prefix . 'wc_orders';
    $items = $wpdb->prefix . 'woocommerce_order_items';
    $itemmeta = $wpdb->prefix . 'woocommerce_order_itemmeta';

    $dead = \implode(',', \array_fill(0, \count(DEAD_STATUSES), '%s'));

    $sql = $wpdb->prepare(
        "SELECT pid.meta_value AS parent_id,
                sz.meta_value AS size,
                SUM(qty.meta_value + 0) AS units
           FROM {$orders} o
           JOIN {$items} oi ON oi.order_id = o.id AND oi.order_item_type = 'line_item'
           JOIN {$itemmeta} qty ON qty.order_item_id = oi.order_item_id
                               AND qty.meta_key = '_qty'
           JOIN {$itemmeta} pid ON pid.order_item_id = oi.order_item_id
                               AND pid.meta_key = '_product_id'
                               AND pid.meta_value <> '0'
           JOIN {$itemmeta} sz ON sz.order_item_id = oi.order_item_id
                              AND sz.meta_key = %s
                              AND sz.meta_value <> ''
          WHERE o.type = 'shop_order'
            AND o.status NOT IN ({$dead})
            AND o.date_created_gmt >= %s
            AND o.date_created_gmt < %s
          GROUP BY pid.meta_value, sz.meta_value",
        \array_merge([SIZE_ATTRIBUTE], DEAD_STATUSES, [$from, $to])
    );

    $out = [];

    foreach ((array) $wpdb->get_results($sql) as $row) {
        $size = canonical_size((string) $row->size);

        if (SIZE_FULL === $size) {
            continue;
        }

        // The three spellings of one size sum into one row, never three.
        $key = row_key((int) $row->parent_id, $size);
        $out[$key] = ($out[$key] ?? 0) + (int) $row->units;
    }

    return $out;
}

/**
 * The stock figures somebody has typed, as row key => units.
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

    foreach ($stored as $key => $units) {
        $key = \trim((string) $key);

        if ('' !== $key) {
            $out[$key] = (int) $units;
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

    $labels = \apply_filters('millboard_stock_size_labels', [
        'handmuster' => \__('Handmuster', 'granola'),
        'grosse-muster' => \__('Große Muster', 'granola'),
    ]);

    $rows = [];

    foreach ($samples as $key => $sample) {
        $counted = \array_key_exists($key, $stock);
        $have = $counted ? (int) $stock[$key] : 0;

        $metrics = metrics($have, $recent[$key] ?? 0, $previous[$key] ?? 0, (int) $sample['per_box']);

        /*
         * ⚠️ NOT COUNTED IS NOT THE SAME AS COUNTED AND FOUND EMPTY.
         *
         * Stock is the one figure the site cannot work out, so every row
         * starts with nothing stored. Feeding that to metrics() as a zero
         * gives zero weeks of cover and marks the row Dringend, so on the day
         * this screen opens the agency would see every colour it sells
         * screaming urgent -- 40 of them on production -- and not one of
         * those warnings would mean anything. After the first real emergency
         * is buried in that, nobody reads the column again.
         *
         * Demand is still known and still shown. It is the cover, the
         * reorder quantity and the status that cannot be answered without a
         * count, so those say so instead of guessing.
         */
        if (!$counted && 'none' !== $metrics['status']) {
            $metrics['status'] = 'uncounted';
            $metrics['weeks'] = null;
            $metrics['need'] = 0;
            $metrics['boxes'] = null;
        }

        $rows[] = \array_merge(
            $sample,
            [
                'size_label' => (string) ($labels[$sample['size']] ?? $sample['size']),
                'counted' => $counted,
                'stock' => $have,
                'recent' => $recent[$key] ?? 0,
            ],
            $metrics
        );
    }

    /*
     * ⚠️ A FIXED ORDER BY RANGE, NOT WORST-FIRST.
     *
     * This used to sort by urgency, on the reasoning that the screen answers
     * "what do I need to order". Aaron, 24 Nov 2026, asked for the ranges in
     * a set order instead, and the counting is why: somebody walking the
     * shelf works through it in a fixed sequence, and a list that reorders
     * itself between visits means finding each colour again every time. The
     * urgent lines are still called out by the Dringend count at the top and
     * by the colour of the status, which is what that is for.
     */
    \usort($rows, static function (array $a, array $b): int {
        return [range_rank($a['title']), $a['title'], $a['length']]
            <=> [range_rank($b['title']), $b['title'], $b['length']];
    });

    return $rows;
}

/**
 * Save the typed stock figures.
 *
 * Only ever writes keys that are actually samples on this site, so a posted
 * field for anything else is dropped rather than stored and shown later.
 *
 * @param array<string,mixed> $posted
 */
function save_stock(array $posted): int
{
    $samples = samples();
    $current = stock_levels();
    $changed = 0;

    foreach ($posted as $key => $value) {
        $key = \trim((string) $key);

        if (!isset($samples[$key]) || '' === \trim((string) $value)) {
            continue;
        }

        $units = \max(0, (int) $value);

        if (($current[$key] ?? null) !== $units) {
            $current[$key] = $units;
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

/**
 * Forget the cached sample list and pack sizes.
 *
 * Both are derived from products, so a catalogue change has to be able to
 * reach them without waiting six hours.
 */
function flush_cache(): void
{
    \delete_transient(SAMPLES_TRANSIENT);
    \delete_transient(PACKS_TRANSIENT);
}
