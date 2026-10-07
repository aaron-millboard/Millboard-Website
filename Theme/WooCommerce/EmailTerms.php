<?php

namespace Theme\WooCommerce;

/**
 * Show the terms of sale that actually apply to the recipient.
 *
 * Aaron, 6 Oct 2026: "the terms and conditions link needs to be either b2b or
 * b2c depending on if they are a homeowner (b2c) persona or any other persona
 * (b2b)."
 *
 * The footer setting currently links to BOTH, which leaves the customer to work
 * out which contract they bought under. It also hardcodes a full URL, so the
 * staging setting points at staging and the production one has to be kept in
 * step by hand. Both are fixed here: the links are resolved from the pages
 * themselves, so they are right in every environment.
 *
 * WHAT DECIDES IT. `website_persona` on the order, the same field the checkout
 * and the sample form collect. "My Own Home" and "Homeowner" are consumers;
 * every other value is a business. That is Aaron's rule, and "Other" falls on
 * the business side by it.
 *
 * WHEN IT IS UNKNOWN, BOTH ARE SHOWN. Plenty of orders carry no persona at all,
 * and asserting business terms to a consumer would be worse than saying nothing
 * useful. Showing both is exactly what happens today, so an unclassifiable
 * order is no worse off than before.
 */
class EmailTerms
{
    /** Paths to the two contracts, resolved rather than hardcoded. */
    public const PATH_B2C = 'trust-centre/millboard-uk-consumer-terms-and-conditions-of-sale-b2c';
    public const PATH_B2B = 'trust-centre/millboard-uk-business-terms-and-conditions-of-sale-b2b';

    /** Personas that buy as a consumer. Everything else is a business. */
    public const CONSUMER_PERSONAS = ['my own home', 'homeowner'];

    public static function init(): void
    {
        // After EmailUnsubscribe so the ordering in the footer stays stable.
        \add_filter('woocommerce_email_footer_text', [__CLASS__, 'correct_terms'], 9, 2);
    }

    /**
     * Replace whatever terms links the setting carries with the right one.
     *
     * Rewritten rather than appended so this is idempotent and survives the
     * setting being edited: any anchor pointing at either contract is removed
     * first, then the applicable one is put back. That also means it does the
     * right thing whether or not someone tidies the setting afterwards.
     *
     * @param mixed $text
     * @param mixed $email
     * @return mixed
     */
    public static function correct_terms($text, $email = null)
    {
        if (!\is_string($text) || '' === $text) {
            return $text;
        }

        $b2c = self::url(self::PATH_B2C);
        $b2b = self::url(self::PATH_B2B);

        if (!$b2c && !$b2b) {
            return $text;
        }

        // Same null-argument problem as the unsubscribe link: the filter's
        // second argument is null on a real send, so use the email captured
        // from the footer action instead.
        if (!$email instanceof \WC_Email) {
            $email = EmailUnsubscribe::current_email();
        }

        $order = $email instanceof \WC_Email ? ($email->object ?? null) : null;
        $text = self::strip_links($text, \array_filter([$b2c, $b2b]));

        $links = [];

        foreach (self::applicable($order instanceof \WC_Order ? $order : null) as $which) {
            $url = 'b2c' === $which ? $b2c : $b2b;

            if (!$url) {
                continue;
            }

            $label = 'b2c' === $which
                ? \__('Terms and Conditions of Sale (B2C)', 'granola')
                : \__('Terms and Conditions of Sale (B2B)', 'granola');

            $links[] = '<a href="' . \esc_url($url) . '" style="color:inherit;">' . \esc_html($label) . '</a>';
        }

        /*
         * Tidy first, then decide. Stripping the contracts can leave a dangling
         * separator or <br> behind, and an account email adds nothing back, so
         * the cleanup has to run whether or not there is anything to append.
         */
        $text = \rtrim($text);
        $text = \preg_replace('/(\s*(\|\s*)|(<br\s*\/?>\s*))+$/i', '', $text);

        if (!$links) {
            return $text;
        }

        return $text . '<br />' . \implode(' | ', $links);
    }

    /**
     * Which contract applies.
     *
     * @return string[] One of b2c or b2b, or both when it cannot be decided.
     */
    public static function applicable(?\WC_Order $order): array
    {
        /*
         * NO ORDER, NO TERMS OF SALE.
         *
         * Aaron, 7 Oct 2026, on seeing both contracts in the portal launch
         * email: "why did it add these on this email, don't they only apply to
         * ones where someone has purchased, this is just an account email."
         *
         * He is right. These are contracts of SALE. On an account email -- new
         * account, password reset, the portal launch -- nothing has been
         * bought, so neither contract governs anything and printing both is
         * noise on the emails that can least afford it.
         *
         * This is distinct from an ORDER whose persona is unknown, below, where
         * a sale genuinely happened and we simply cannot tell which contract
         * applies. That case still shows both.
         */
        if (!$order instanceof \WC_Order) {
            return [];
        }

        $persona = \trim((string) $order->get_meta('website_persona'));

        if ('' === $persona) {
            return (array) \apply_filters('millboard_email_terms_unknown', ['b2c', 'b2b'], $order);
        }

        $consumers = (array) \apply_filters('millboard_email_consumer_personas', self::CONSUMER_PERSONAS);

        return \in_array(\strtolower($persona), \array_map('strtolower', $consumers), true)
            ? ['b2c']
            : ['b2b'];
    }

    /**
     * Remove any anchor whose href is one of these, leaving the rest alone.
     *
     * @param string[] $urls
     */
    private static function strip_links(string $text, array $urls): string
    {
        foreach ($urls as $url) {
            // The trailing separator goes with the link, so removing one does
            // not leave a stranded pipe behind.
            $pattern = '#<a\b[^>]*href=["\']' . \preg_quote(\untrailingslashit($url), '#') . '/?["\'][^>]*>.*?</a>\s*(\|\s*)?#is';
            $text = (string) \preg_replace($pattern, '', $text);
        }

        return $text;
    }

    private static function url(string $path): string
    {
        $page = \get_page_by_path($path);

        if (!$page) {
            return '';
        }

        $url = \get_permalink($page);

        return \is_string($url) ? $url : '';
    }
}
