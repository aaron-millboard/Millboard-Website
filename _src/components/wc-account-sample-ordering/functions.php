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
