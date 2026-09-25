<?php

namespace Theme\WooCommerce;

/**
 * Which locales may show a monetary figure.
 *
 * Only en-gb and fr-fr sell. Everywhere else the site shows what a product is
 * and offers a free sample, and pricing is handled by the regional distributor.
 * The live product pages already behave that way: only en-gb and fr-fr render an
 * add to cart button, and en-us, de-de, en-ie and en-au render no visible price.
 *
 * ⚠️ en-au is not a preference, it is an agreement. Concept Materials is the
 * exclusive Australian distributor and owns Australian retail pricing. They
 * escalated on 16 Sep 2026 that the AU site was showing UK prices converted to
 * AUD. The rule since is that NO monetary figure appears on any /en-au/ page:
 * not AUD, not GBP, not a per square metre rate, not a ratio.
 *
 * ⚠️ The product data is NOT a safe signal. en-us, en-ie and en-au all hold real
 * prices and report is_purchasable() as true, they are simply not rendered. So
 * this is an explicit list, and it is the one place to change if a region starts
 * or stops selling.
 */
class RegionalPricing
{
    /**
     * The locales that may display prices and sell.
     */
    private const SELLING_LOCALES = ['en-gb', 'fr-fr'];

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
