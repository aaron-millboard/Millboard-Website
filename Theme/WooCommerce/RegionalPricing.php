<?php

namespace Theme\WooCommerce;

/**
 * Which locales may show a monetary figure.
 *
 * en-gb, en-ie and fr-fr sell. en-us, de-de and en-au do not: there the site
 * shows what a product is and offers a free sample, and pricing is handled by
 * the regional distributor.
 *
 * ⚠️ en-au is not a preference, it is an agreement. Concept Materials is the
 * exclusive Australian distributor and owns Australian retail pricing. They
 * escalated on 16 Sep 2026 that the AU site was showing UK prices converted to
 * AUD. The rule since is that NO monetary figure appears on any /en-au/ page:
 * not AUD, not GBP, not a per square metre rate, not a ratio.
 *
 * ⚠️ THIS LIST IS A COMMERCIAL FACT, CONFIRMED BY AARON. Do not try to derive it
 * from the site or the data, because every available signal lies:
 *
 *  - `is_purchasable()` is TRUE on en-us, en-ie and en-au alike.
 *  - Scraping the rendered page for a currency symbol finds "£0.00" on de-de and
 *    fr-fr, which is the third-party Deck Planner widget, GBP-only on every
 *    language route, not a product price.
 *  - Counting `single_add_to_cart_button` reports 0 for en-ie, which does sell.
 *
 * So it is an explicit list, and it is the one place to change when a region
 * starts or stops selling.
 */
class RegionalPricing
{
    /**
     * The locales that may display prices and sell.
     */
    private const SELLING_LOCALES = ['en-gb', 'en-ie', 'fr-fr'];

    /**
     * Whether this site may show a monetary figure at all.
     *
     * @return bool
     */
    public static function shows_pricing(): bool
    {
        return in_array(self::current_locale(), self::SELLING_LOCALES, true);
    }

    /**
     * The locale segment of the current site, for example "en-gb".
     *
     * Read from the home URL path rather than from a blog id, because ids
     * differ between environments and the path does not.
     *
     * @return string The locale, or an empty string.
     */
    public static function current_locale(): string
    {
        $path = (string) \wp_parse_url((string) \get_option('home'), PHP_URL_PATH);
        $segments = array_values(array_filter(explode('/', $path)));

        return isset($segments[0]) ? strtolower($segments[0]) : '';
    }
}
