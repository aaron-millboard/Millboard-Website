<?php

/**
 * Sample ordering -- fitting the mb-sof widget to this site.
 */

namespace Granola\Components\WC_Account_Sample_Ordering;

/**
 * Does the widget have a catalogue to show?
 *
 * Used so the panel can explain an empty catalogue in the account's own voice
 * rather than leaving the widget's bare paragraph. The call is cheap: the
 * result is held in the widget's own 12-hour transient.
 */
function has_catalogue(): bool
{
    if (!\function_exists('mb_sof_get_catalogue')) {
        return false;
    }

    return (bool) \mb_sof_get_catalogue();
}

/**
 * ─────────────────────────────────────────────────────────────────────────────
 * WHY THIS TAB IS EMPTY ON MILLBOARD.COM, AND WHAT WOULD FILL IT
 *
 * The widget decides what belongs in the catalogue by looking for the word
 * "sample" in a product's name, which is how the HubSpot catalogue was
 * written. Nothing on this site matches: 0 of the 65 products in the
 * decking-samples and cladding-samples categories have "sample" in the name.
 *
 * `mb_sof_in_scope` exists to widen that, and taking membership from the
 * product category instead does let all 65 through -- the classifier sorts
 * every one of them correctly into four of the 22 categories. That was built
 * and then taken out again, because what comes through is not samples:
 *
 *   - 45 of the 65 are board PARENTS. Their variations are `full` (the board
 *     itself) plus `small` and `large`, and it is small/large that carry the
 *     real sample SKUs -- AME105A, AME305A and the other 70 AM* variations.
 *     The widget queries parents only, so it cannot see them. WooCommerce
 *     refuses add_to_cart() on a variable parent, which is the only reason a
 *     rep gets an error rather than a full board in the basket.
 *
 *   - the other 20 are Modello Contour and Linear boards, which are simple
 *     products and so DO add to the basket: 3600mm boards, 13kg, £141-£148
 *     each. They sit in decking-samples because Modello has no sample SKU yet,
 *     which the widget's own category list already notes. Offering those as
 *     free samples is the one outcome worse than an empty tab.
 *
 * So the category is not "products that are samples", it is "products that
 * have samples". Sourcing this catalogue needs the widget to query the sample
 * VARIATIONS (the AM* SKUs) rather than parent products, and to pass a
 * variation id to add_to_cart(). That is a change to mb_sof_query_products()
 * and mb_sof_catalogue_by_id() in the package, not something that can be
 * filtered from out here -- there is no hook on the query.
 *
 * Until then the widget's own scoping stands, the tab shows the empty state
 * below, and nothing can be ordered by mistake.
 * ─────────────────────────────────────────────────────────────────────────────
 */

/**
 * Is this request the sample tool posting an order?
 *
 * The widget posts back to its own URL with `mb_sof_action=add` and handles it
 * on template_redirect. Everything below keys off that, so none of it can
 * touch a basket a customer filled from the shop.
 */
function is_tool_submit(): bool
{
    // phpcs:ignore WordPress.Security.NonceVerification.Missing -- the widget verifies its own nonce before adding anything; this only decides whose basket rules apply.
    return isset($_POST['mb_sof_action']) && 'add' === $_POST['mb_sof_action'];
}

const CART_FLAG = 'millboard_sample_order';

/**
 * Mark every line the tool puts in the basket.
 *
 * Also keeps them as their own cart line: WooCommerce hashes this data into
 * the line key, so a free staff sample never merges with the same variation a
 * customer added at its real price.
 */
\add_filter('woocommerce_add_cart_item_data', function (array $data): array {
    if (is_tool_submit()) {
        $data[CART_FLAG] = true;
    }

    return $data;
}, 10, 1);

/**
 * Staff pay nothing, whatever the sample costs in the shop.
 *
 * The small (100mm) samples are already £0. The large (300mm) ones carry a
 * real price, and Aaron's call is that both go out free when a rep sends them,
 * so the price is zeroed on the flagged lines only. The same variation bought
 * from the shop is untouched.
 */
\add_action('woocommerce_before_calculate_totals', function ($cart): void {
    if (!$cart instanceof \WC_Cart) {
        return;
    }

    foreach ($cart->get_cart() as $item) {
        if (empty($item[CART_FLAG]) || empty($item['data']) || !$item['data'] instanceof \WC_Product) {
            continue;
        }

        $item['data']->set_price(0);
    }
}, 20);

/**
 * Say so on the order, so fulfilment and the office can tell these apart.
 */
\add_filter('woocommerce_get_item_data', function (array $item_data, array $cart_item): array {
    if (!empty($cart_item[CART_FLAG])) {
        // `display` as well as `value`: the theme's cart-item-data template
        // renders $data['display'], so value alone prints an empty row.
        $item_data[] = [
            'key' => \__('Sample order', 'granola'),
            'value' => \__('Sent by the Millboard team', 'granola'),
            'display' => \__('Sent by the Millboard team', 'granola'),
        ];
    }

    return $item_data;
}, 10, 2);

\add_action('woocommerce_checkout_create_order_line_item', function ($item, $key, $values): void {
    if (!empty($values[CART_FLAG]) && \is_object($item) && \method_exists($item, 'add_meta_data')) {
        $item->add_meta_data(\__('Sample order', 'granola'), \__('Sent by the Millboard team', 'granola'));
    }
}, 10, 3);

/**
 * The three-free-sample cap does not apply to the team.
 *
 * `MAX_SAMPLES = 3` in the product-samples component is a commercial rule for
 * customers, enforced on woocommerce_add_to_cart_validation. A rep sending a
 * customer a spread of colours is the case it was never about, so this runs
 * after it (priority 99) and lets the tool's own lines through.
 *
 * It is deliberately NOT a change to MAX_SAMPLES: that constant still governs
 * every ordinary add-to-cart on every locale, which is what it is for.
 */
\add_filter('woocommerce_add_to_cart_validation', function ($passed) {
    return is_tool_submit() ? true : $passed;
}, 99);

/**
 * The fallback ceiling, for a SKU the limits file does not name.
 *
 * Real limits are per SKU and come from `sample-ordering/data/sample-limits.json`
 * (see mb_sof_limit_for_sku). This only catches anything added to WooCommerce
 * that the portal export did not list. 10 is the conservative choice: the
 * portal's own values run 1, 3, 5, 10, 22 and 30, so an unknown line gets a
 * middling cap rather than the most generous one.
 *
 * ⚠️ The limits themselves are being audited. Replacing that JSON file is how
 * the reviewed numbers land; nothing here needs to change for it.
 */
\add_filter('mb_sof_max_qty', fn(): int => 10);

/**
 * The product categories that mark a line as POS / marketing stock.
 *
 * ⚠️ NOT YET POPULATED. The 84 POS lines in the portal export do not exist as
 * WooCommerce products, so nothing carries this category today and the filter
 * below removes nothing. The import that creates them has to set it, or
 * installers will see POS the moment those products appear.
 *
 * @return string[]
 */
function get_pos_category_slugs(): array
{
    return (array) \apply_filters('millboard/account/pos_category_slugs', ['marketing-pos']);
}

const POS_TRANSIENT = 'millboard_account_pos_ids';

/**
 * Product and variation ids that are POS, as a lookup map.
 *
 * @return array<int, true>
 */
function get_pos_ids(): array
{
    static $ids = null;

    if (\is_array($ids)) {
        return $ids;
    }

    $cached = \get_transient(POS_TRANSIENT);

    if (\is_array($cached)) {
        $ids = $cached;
        return $ids;
    }

    $slugs = get_pos_category_slugs();
    $ids = [];

    if ($slugs) {
        $posts = \get_posts([
            'post_type' => 'product',
            'post_status' => 'publish',
            'numberposts' => -1,
            'fields' => 'ids',
            'tax_query' => [[
                'taxonomy' => 'product_cat',
                'field' => 'slug',
                'terms' => $slugs,
            ]],
        ]);

        foreach ((array) $posts as $post_id) {
            $ids[(int) $post_id] = true;

            // A POS parent's variations are POS too.
            $product = \wc_get_product($post_id);

            if ($product instanceof \WC_Product_Variable) {
                foreach ($product->get_children() as $child) {
                    $ids[(int) $child] = true;
                }
            }
        }
    }

    \set_transient(POS_TRANSIENT, $ids, 12 * HOUR_IN_SECONDS);

    return $ids;
}

\add_action('save_post_product', __NAMESPACE__ . '\\flush_pos_ids');
\add_action('deleted_post', __NAMESPACE__ . '\\flush_pos_ids');
\add_action('woocommerce_update_product', __NAMESPACE__ . '\\flush_pos_ids');

function flush_pos_ids(): void
{
    \delete_transient(POS_TRANSIENT);
}

/**
 * Hide POS lines from anyone who may not order them.
 *
 * Installers have no point of sale to stock, so POS is distributors and staff
 * only. This runs on the way out of the catalogue, after its shared cache, so
 * one viewer's permissions can never be cached and served to another.
 */
\add_filter('mb_sof_catalogue', function (array $items): array {
    if (\Granola\Components\WC_Account\can_order_pos()) {
        return $items;
    }

    $pos = get_pos_ids();

    if (!$pos) {
        return $items;
    }

    return \array_values(\array_filter(
        $items,
        static fn(array $item): bool => !isset($pos[(int) ($item['id'] ?? 0)])
    ));
});

/**
 * Stop the widget fetching Archivo and Hanken Grotesk from Google.
 *
 * Its stylesheet is registered with `mb-sof-fonts` as a dependency, so simply
 * dequeuing the fonts would not work: WordPress re-enqueues a dependency of an
 * enqueued handle. Re-registering the handle with no source keeps the
 * dependency satisfied and prints nothing -- the documented way to neutralise
 * a dependency rather than break it.
 *
 * The two font custom properties are re-pointed at F37 Ginger in this
 * component's stylesheet, which is what the handover suggests alongside
 * dropping the request.
 *
 * Priority 20, after the widget registers its assets at the default 10.
 */
\add_action('wp_enqueue_scripts', function (): void {
    if (!\wp_style_is('mb-sof-fonts', 'registered')) {
        return;
    }

    \wp_deregister_style('mb-sof-fonts');
    \wp_register_style('mb-sof-fonts', false, [], null);
}, 20);
