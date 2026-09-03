<?php

namespace Theme\Analytics;

use Theme\WooCommerce\OrderIdentity;

/**
 * Turns a WooCommerce order into one normalised conversion event.
 *
 * Deliberately knows nothing about Meta or Google. It produces a single neutral shape that
 * each destination then renders into its own wire format, so the two can never drift apart on
 * what a conversion actually was - which is the whole problem this project exists to fix.
 * Today GA4 reads a dataLayer push, Google Ads scrapes the order total out of the rendered
 * page, and Meta fires a page-load tag, so no two of them agree on the same order.
 *
 * Pure and side-effect free, so it can be unit tested against fabricated orders without
 * WordPress, HubSpot or a network.
 *
 * TWO EVENT KINDS, NEVER MERGED
 *
 * A paid order is a purchase. A zero-value sample order is a lead. They stay separate because
 * samples outnumber paid orders roughly seven to one - 3,393 against 457 in August 2026 - so
 * folding them together would teach the ad platforms to optimise for sample-seekers and call
 * it revenue. The interim client-side work was written specifically to avoid that, and this
 * keeps the same rule.
 *
 * THE DEDUPLICATION KEY IS THE ORDER ID, NEVER THE ORDER NUMBER
 *
 * Meta discards a second event carrying an event_id it has already seen, silently, so a
 * colliding key looks exactly like a tracking gap rather than a bug. Order NUMBERS on this
 * site are not unique and not even stable: the numbering plugin's counter has been reset
 * backwards at least once, `get_order_number()` returns a prefixed order ID before the plugin
 * assigns and a prefixed counter value afterwards, and a bulk renumbering pass has rewritten
 * historical numbers. Order IDs are none of those things.
 *
 * They are, however, only unique PER SITE - each locale has its own `wp_<blog>_wc_orders`
 * table, so en-gb 21155 and fr-fr 21155 both exist. Hence the blog prefix.
 */
class EventPayload
{
    public const KIND_PURCHASE = 'purchase';
    public const KIND_LEAD     = 'lead';

    /**
     * International dialling codes, so a national phone number can be normalised to the
     * E.164-ish digits Meta expects. Keyed by the billing country actually seen on this
     * network's storefronts.
     *
     * A number stored as 07759384542 hashes to something completely different from
     * 447759384542, and Meta cannot tell you that you got it wrong - the match simply never
     * happens and match quality stays quietly low.
     */
    private const DIALLING_CODES = [
        'GB' => '44',
        'US' => '1',
        'CA' => '1',
        'DE' => '49',
        'FR' => '33',
        'IE' => '353',
        'AU' => '61',
    ];

    /**
     * @param mixed $order A WC_Order, validated rather than type-hinted.
     * @return array<string, mixed>|null Null when the order cannot produce a usable event.
     */
    public static function from_order($order)
    {
        if (! $order instanceof \WC_Order) {
            return null;
        }

        $identity = $order->get_meta(OrderIdentity::META_KEY);
        $identity = is_array($identity) ? $identity : [];

        $total    = (float) $order->get_total();
        $created  = $order->get_date_created();

        return [
            // Stable, unique, and immune to every way an order NUMBER can move.
            'event_id'     => self::event_id($order),

            'kind'         => $total > 0 ? self::KIND_PURCHASE : self::KIND_LEAD,
            'value'        => round($total, 2),
            'currency'     => strtoupper((string) $order->get_currency()),

            // Human-readable only. Never use this to key, match or deduplicate anything.
            'order_number' => (string) $order->get_order_number(),

            'occurred_at'  => $created ? $created->getTimestamp() : time(),

            // Meta custom conversions and GA4 audiences are routinely defined by URL rules. A
            // server event with no URL never matches them, so they would keep counting browser
            // events right up to cutover and then silently stop.
            'page_url'     => self::page_url($order),
            'items'        => self::items($order),
            'user'         => self::hashed_user($order),

            // Meta wants these raw, not hashed. They materially lift match quality and are
            // already on the order, so nothing extra had to be captured at checkout.
            'client'       => [
                'ip'         => (string) $order->get_customer_ip_address(),
                'user_agent' => (string) $order->get_customer_user_agent(),
            ],

            'identifiers'  => self::identifiers($identity),
            'consent'      => isset($identity['consent']) && is_array($identity['consent'])
                ? $identity['consent']
                : ['advertising' => 'unknown', 'analytics' => 'unknown', 'source' => 'none'],
        ];
    }

    /**
     * `<blog id>-<order id>`. Must match whatever the browser pixel sends as its eventID for
     * Meta's browser/server deduplication to work at all.
     */
    public static function event_id($order): string
    {
        return get_current_blog_id() . '-' . $order->get_id();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    /**
     * The order-received URL, WITHOUT its query string.
     *
     * WooCommerce appends `?key=wc_order_...`, which is the order key - the token that lets
     * anyone holding it view that order. It has no business being sent to an ad platform, and a
     * URL rule only ever matches on the path anyway.
     */
    private static function page_url($order): string
    {
        if (! method_exists($order, 'get_checkout_order_received_url')) {
            return '';
        }

        $url = (string) $order->get_checkout_order_received_url();
        $pos = strpos($url, '?');

        return false === $pos ? $url : substr($url, 0, $pos);
    }

    private static function items($order): array
    {
        $out = [];

        foreach ($order->get_items() as $item) {
            $product = $item->get_product();
            $qty     = (int) $item->get_quantity();

            $out[] = [
                // SKU where there is one. Falls back to the product ID so a deleted or
                // SKU-less product still produces a usable line rather than an empty string
                // that the platforms silently drop.
                'id'       => $product && $product->get_sku() ? $product->get_sku() : (string) $item->get_product_id(),
                'name'     => (string) $item->get_name(),
                'quantity' => $qty,
                'price'    => $qty > 0 ? round((float) $item->get_total() / $qty, 2) : 0.0,
            ];
        }

        return $out;
    }

    /**
     * Customer identifiers, SHA-256 hashed to Meta's normalisation rules.
     *
     * Keys use Meta's short names because that is the more demanding of the two formats;
     * GA4 user-provided data takes the same hashes under different names, so the destination
     * renames rather than re-hashes. Hashing twice with different rules is how you end up
     * with two match rates and no idea which is right.
     *
     * Empty values are dropped rather than hashed - the SHA-256 of an empty string is a
     * perfectly valid hash of nothing, and sending it degrades match quality while looking
     * like data.
     *
     * @return array<string, string>
     */
    private static function hashed_user($order): array
    {
        $country = strtoupper((string) $order->get_billing_country());

        $raw = [
            'em'      => strtolower(trim((string) $order->get_billing_email())),
            'ph'      => self::normalise_phone((string) $order->get_billing_phone(), $country),
            'fn'      => self::normalise_name((string) $order->get_billing_first_name()),
            'ln'      => self::normalise_name((string) $order->get_billing_last_name()),
            'ct'      => self::normalise_name((string) $order->get_billing_city()),
            'zp'      => strtolower(preg_replace('/\s+/', '', (string) $order->get_billing_postcode())),
            'country' => strtolower($country),
        ];

        $out = [];
        foreach ($raw as $key => $value) {
            if ('' === $value || null === $value) {
                continue;
            }
            $out[$key] = hash('sha256', $value);
        }

        return $out;
    }

    /**
     * Digits only, with the national trunk zero replaced by the country's dialling code.
     */
    private static function normalise_phone(string $phone, string $country): string
    {
        $digits = preg_replace('/\D+/', '', $phone);

        if ('' === $digits) {
            return '';
        }

        // Already international, e.g. 00447759... or +447759...
        if (0 === strpos($digits, '00')) {
            return substr($digits, 2);
        }

        $code = self::DIALLING_CODES[$country] ?? '';

        if ('' !== $code && 0 === strpos($digits, '0')) {
            return $code . substr($digits, 1);
        }

        // Leave anything already prefixed with its country code alone.
        if ('' !== $code && 0 === strpos($digits, $code)) {
            return $digits;
        }

        return '' !== $code ? $code . $digits : $digits;
    }

    /**
     * Lowercase, trimmed, punctuation and digits removed. Accents are kept: Meta hashes UTF-8
     * bytes, and stripping them would turn "Rosée" into a different person.
     */
    private static function normalise_name(string $value): string
    {
        $value = strtolower(trim($value));
        $value = preg_replace('/[\p{P}\p{N}]+/u', '', $value);

        return trim(preg_replace('/\s+/u', ' ', (string) $value));
    }

    /**
     * Click and browser identifiers captured at checkout. Absent when consent was not granted,
     * which is the intended outcome rather than an error.
     *
     * @param array<string, mixed> $identity
     * @return array<string, string>
     */
    private static function identifiers(array $identity): array
    {
        $meta   = isset($identity['meta']) && is_array($identity['meta']) ? $identity['meta'] : [];
        $google = isset($identity['google']) && is_array($identity['google']) ? $identity['google'] : [];
        $ga4    = isset($identity['ga4']) && is_array($identity['ga4']) ? $identity['ga4'] : [];

        return array_filter([
            'fbp'        => $meta['fbp'] ?? '',
            'fbc'        => $meta['fbc'] ?? '',
            'gclid'      => $google['gclid'] ?? '',
            'gbraid'     => $google['gbraid'] ?? '',
            'client_id'  => $ga4['client_id'] ?? '',
            'session_id' => $ga4['session_id'] ?? '',
        ]);
    }
}
