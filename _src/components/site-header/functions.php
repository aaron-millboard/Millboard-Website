<?php

namespace Granola\Components\SiteHeader;

/**
 * The residential site behind a /commercial/ one, or 0 when this IS residential.
 *
 * Every commercial site is `/<locale>/commercial/` and clones `/<locale>/`, so
 * dropping the last segment names its parent. Confirmed against all six on
 * 29 Oct 2026: en-gb, en-us, de-de, fr-fr, en-ie and en-au.
 *
 * WARNING: deliberately NOT reusing Granola\Components\Map\get_directory_source_blog(),
 * which applies the same rule. That one carries a `granola/map/
 * directory_source_blog` filter, so anyone retargeting the MAP would silently
 * move this header link too. Same rule, separate decision.
 */
function residential_blog_id(): int
{
    if (!\is_multisite()) {
        return 0;
    }

    $home = \rtrim((string) \wp_parse_url(\home_url(), PHP_URL_PATH), '/');

    if (!\str_ends_with($home, '/commercial')) {
        return 0;
    }

    $blog = (int) \get_blog_id_from_url(
        (string) \wp_parse_url(\home_url(), PHP_URL_HOST),
        \substr($home, 0, -\strlen('/commercial')) . '/'
    );

    // get_blog_id_from_url() gives 0 for a path that is not a site, and
    // pointing at ourselves would be a pointless switch_to_blog().
    return $blog !== \get_current_blog_id() ? $blog : 0;
}

/**
 * Where the header account icon should go.
 *
 * Aaron, 29 Oct 2026: on a commercial site it should land on the residential
 * equivalent, in every region. The commercial sites are clones and nobody
 * holds an account on one, so the icon led to a sign-in for a site with
 * nothing behind it.
 *
 * The URL is read from the TARGET site rather than assembled here, because the
 * account slug is per site: fr-fr may call it `mon-compte`, and a string built
 * from `/my-account/` would 404 there.
 *
 * Falls back to this site own account page whenever the parent cannot be
 * resolved or has no account page, which is the behaviour before this existed.
 */
function account_url(): string
{
    if (!\function_exists('wc_get_page_id')) {
        return (string) \home_url('/');
    }

    $own = static function (): string {
        $id = (int) \wc_get_page_id('myaccount');

        return $id > 0 ? (string) \get_the_permalink($id) : (string) \home_url('/');
    };

    $residential = residential_blog_id();

    if (!$residential) {
        return $own();
    }

    \switch_to_blog($residential);

    $id = (int) \wc_get_page_id('myaccount');
    $url = $id > 0 ? (string) \get_the_permalink($id) : '';

    \restore_current_blog();

    return '' !== $url ? $url : $own();
}

function filter_args(array $args): ?array
{
    // ---------------------------------------
    // Default arguments.
    // ---------------------------------------
    $args = array_merge([
        'content' => [],
        'classes' => [],
        'help_center_link' => null,
        'nav_split' => 0,
    ], $args);

    // ---------------------------------------
    // Required classes.
    // ---------------------------------------
    $args['classes'] = array_merge([
        'site-header',
    ], $args['classes']);

    // -------------------------------------------------------------------------
    // Where to break the primary nav either side of the wordmark.
    //
    // The redesigned header centres the wordmark inside the main nav rather than
    // sitting it at one end, so the menu renders in two halves. The split is
    // computed from however many top-level items the menu actually has, because
    // it is different per locale and editable in the admin: en-gb has six, which
    // breaks 3/3 exactly as the design draws it, but nothing here assumes that.
    //
    // The larger half goes on the LEFT. The right-hand group also carries the
    // search, account and basket icons, so giving the left the extra item keeps
    // the two sides closer to even.
    //
    // Deliberately a count, not a measurement. Balancing the halves by label
    // width instead was tried and reverted: it is more even in pixels and
    // plainly wrong to look at, because the icons do not read as nav items. It
    // put four links against two on fr-fr and moved en-ie and en-au around for
    // no visible gain. Where a locale's labels genuinely do not fit, the answer
    // is the label, not the arithmetic.
    // -------------------------------------------------------------------------
    $header_menu_locations = \get_nav_menu_locations();

    if (!empty($header_menu_locations['header'])) {
        $top_level = array_values(array_filter(
            (array) \wp_get_nav_menu_items($header_menu_locations['header'], ['output' => false]),
            function ($item) {
                return empty($item->menu_item_parent) || $item->menu_item_parent === '0';
            }
        ));

        $args['nav_split'] = (int) ceil(count($top_level) / 2);
    }

    if ($header_call_to_action_1 = get_field('header_call_to_action_1', 'option')) {
        $args['content']['call_to_action_1'] = $header_call_to_action_1;
        $args['content']['call_to_action_1']['classes'] = [
            'g-button',
            'g-button--solid',
            'site-header__call-to-action-1',
        ];
    }

    if ($header_help_center_link = get_field('header_help_center_link', 'option')) {
        $args['help_center_link'] = $header_help_center_link;
    }

    // ---------------------------------------
    // Custom.
    // ---------------------------------------

    // Count PRODUCTS, not individual units. get_cart_contents_count() sums
    // quantities, so a real order reads as 109 or 325 in the badge and looks like
    // the basket has run away with itself (Elon, Sep 2026). A deck genuinely is
    // hundreds of boards and screws; the number people want next to the icon is
    // how many lines they have to review.
    //
    // Keep this in step with `cartCount` in the product-samples component, which
    // writes the same badge after an add without a page load.
    $basket_count = 0;

    if (function_exists('WC') && WC()->cart) {
        // The previous multisite branch here switched to the blog it was already
        // on, so both halves did the same thing.
        $basket_count = count(WC()->cart->get_cart());
    }

    // The badge on its own announces as "Basket 8", which does not say what 8
    // counts. Put the meaning in the hidden label and mark the badge decorative,
    // so a screen reader hears it once and in full.
    //
    // SampleBasket.js::syncHeaderCount() updates BOTH of these after an AJAX add.
    // If you change the markup here, change it there too or the label goes stale
    // while the number moves.
    $basket_label = !empty($basket_count)
        ? sprintf(
            /* translators: %s: number of products in the basket. */
            _n('Basket, %s product', 'Basket, %s products', $basket_count, 'granola'),
            number_format_i18n($basket_count)
        )
        : __('Basket', 'granola');

    $basket_button_content = '<span class="visually-hidden site-header__basket-label">'
        . esc_html($basket_label)
        . '</span>';

    if (!empty($basket_count)) {
        $basket_button_content .= '<span class="site-header__basket-count" aria-hidden="true">'
            . esc_html(number_format_i18n($basket_count))
            . '</span>';
    }

    $args['content']['basket_button_content'] = $basket_button_content;

    // -------------------------------------------------------------------------
    // Return the filtered args.
    // -------------------------------------------------------------------------
    return $args;
}
