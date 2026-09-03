<?php

namespace Theme\WooCommerce;

/**
 * Stamps ad-platform identifiers and a consent snapshot onto every order.
 *
 * Server-side conversion sending (Meta CAPI, GA4 Measurement Protocol) happens minutes
 * or hours after checkout, in a queue, with no browser attached. By then the cookies
 * that identify the visitor to Meta and Google are gone, and so is any way of knowing
 * what that particular customer agreed to. Both have to be captured while the order is
 * being created, or the send is either unattributable or unaccountable.
 *
 * Read from $_COOKIE rather than hidden form inputs, deliberately. The theme replaces
 * the stock checkout template with its own component, which fires none of WooCommerce's
 * five stamp actions - the bug that left every order with empty UTM attribution since
 * March 2026. Cookies are sent with the POST regardless of what the template renders,
 * so nothing here depends on the checkout markup, and a future template change cannot
 * silently break it.
 *
 * WHY EACH VALUE IS NEEDED
 *
 *  _fbp / _fbc          Meta's browser and click identifiers. Without them a CAPI event
 *                       has almost nothing to match on. Purchase currently carries NO
 *                       event match quality score at all in Events Manager, which is the
 *                       single clearest measurable this project has to move.
 *  GA4 client_id        The Measurement Protocol REQUIRES it. An MP event sent without
 *                       one is accepted and then stranded: no session, no attribution,
 *                       no join to anything. Treat a missing client_id as a hard failure
 *                       in verification, not a warning.
 *  GA4 session_id       Lets the server event join the session the customer was actually
 *                       in, instead of opening a synthetic one.
 *  gclid / gbraid /     Google Ads click identifiers, for offline/enhanced conversion
 *  wbraid               import. Note these arrive via the _gcl_* cookies, which is why a
 *                       tracking template that strands UTMs in the URL fragment does not
 *                       affect them - that is a separate, real problem.
 *
 * CONSENT IS CAPTURED AND ENFORCED HERE, NOT ONLY AT SEND TIME
 *
 * Server-side tracking does not exempt anyone from consent. Sending a non-consenting
 * customer's purchase to Meta from our server is still processing their data for
 * advertising. So this class does two things rather than one: it records what the
 * customer had agreed to at the moment they ordered, and it declines to store the ad
 * identifiers at all unless advertising consent was granted.
 *
 * Gating at capture rather than only at send matters. If we stored identifiers for
 * someone who refused and merely promised not to transmit them, the order record itself
 * would hold advertising data they declined. This way the record is clean by
 * construction, and "what did we hold, and on what basis" has a truthful answer for any
 * order, months later, without reconstructing anything.
 *
 * In practice a refusal usually means there is nothing to store anyway - measured on
 * production 3 Sep 2026, fbevents.js does not load at all in the UK before consent - but
 * the guard is explicit because "it happens not to be there" is not a control.
 *
 * @see ConsentFields for the checkout consent QUESTION. This class records the CMP's
 *      cookie state, which is a different thing: what the visitor allowed us to run.
 */
class OrderIdentity
{
    /**
     * Order meta holding the full captured payload.
     */
    public const META_KEY = '_mb_identity';

    /**
     * Order meta holding just the advertising-consent verdict, as a scalar.
     *
     * Duplicated out of the payload on purpose: the emitter and any reporting query
     * needs to filter on this without unserialising, and a meta_query cannot reach
     * inside an array.
     *
     * One of 'granted', 'denied' or 'unknown'.
     */
    public const META_AD_CONSENT = '_mb_ad_consent';

    public static function init()
    {
        // Classic checkout.
        add_action('woocommerce_checkout_order_created', [self::class, 'capture'], 10, 1);

        // Store API / block checkout, which does not fire the action above.
        add_action('woocommerce_store_api_checkout_order_processed', [self::class, 'capture'], 10, 1);
    }

    /**
     * Capture identifiers and consent onto the order.
     *
     * No scalar type hint on the parameter, and the whole body is wrapped: a fatal in
     * analytics capture must never be able to fail a checkout. A missed identifier costs
     * us one conversion's attribution; a fatal here costs the sale.
     *
     * @param mixed $order Expected to be a WC_Order, validated rather than type-hinted.
     */
    public static function capture($order)
    {
        try {
            if (! $order instanceof \WC_Order) {
                return;
            }

            // Idempotent. Both hooks can fire for one order depending on checkout route.
            if ($order->get_meta(self::META_KEY)) {
                return;
            }

            $consent = self::read_consent();

            $payload = [
                'captured_at' => gmdate('c'),
                'blog_id'     => get_current_blog_id(),
                'consent'     => $consent,
            ];

            // Ad identifiers are stored ONLY with advertising consent. See class docblock.
            if ('granted' === $consent['advertising']) {
                $payload['meta']   = self::read_meta_ids();
                $payload['google'] = self::read_google_ids();
            }

            // Analytics identifiers follow the analytics decision, which CMPs treat
            // separately from advertising and which GA4 needs even when Meta gets nothing.
            if ('granted' === $consent['analytics']) {
                $payload['ga4'] = self::read_ga4_ids();
            }

            $order->update_meta_data(self::META_KEY, $payload);
            $order->update_meta_data(self::META_AD_CONSENT, $consent['advertising']);
            $order->save();
        } catch (\Throwable $e) {
            // Swallow deliberately. Log for diagnosis, never surface at checkout.
            if (function_exists('wc_get_logger')) {
                wc_get_logger()->warning(
                    'OrderIdentity capture failed: ' . $e->getMessage(),
                    ['source' => 'mb-order-identity']
                );
            }
        }
    }

    /**
     * Normalise whichever CMP is running on this storefront into one verdict.
     *
     * Two CMPs are live and they disagree about almost everything. CookieYes runs the
     * UK/EU storefronts on an opt-IN model and writes a readable cookie. WPConsent runs
     * en-us only, on an opt-OUT CCPA model, where the ABSENCE of a decision means
     * allowed - the opposite default. Measured 3 Sep 2026: only 1.7% of US visitors ever
     * touch the banner, so the default is what applies to almost everyone there.
     *
     * Anything unrecognised returns 'unknown', which the emitter must treat as "do not
     * send". Failing closed is the only safe direction here.
     *
     * @return array{advertising:string, analytics:string, source:string, raw:string}
     */
    private static function read_consent()
    {
        // ---- CookieYes (UK/EU). Cookie is a flat comma-separated key:value list, e.g.
        // consentid:...,consent:yes,action:yes,necessary:yes,...,advertisement:yes,other:yes
        $cky = self::cookie('cookieyes-consent');

        if ('' !== $cky) {
            $pairs = [];
            foreach (explode(',', $cky) as $chunk) {
                $bits = explode(':', $chunk, 2);
                if (2 === count($bits)) {
                    $pairs[trim($bits[0])] = trim($bits[1]);
                }
            }

            $decided = isset($pairs['action']) && 'yes' === $pairs['action'];

            return [
                'advertising' => self::verdict($decided, ($pairs['advertisement'] ?? '') === 'yes'),
                'analytics'   => self::verdict($decided, ($pairs['analytics'] ?? '') === 'yes'),
                'source'      => 'cookieyes',
                // Kept verbatim so a consent claim can be evidenced, not just asserted.
                'raw'         => $cky,
            ];
        }

        // ---- WPConsent (en-us). Opt-out: no recorded decision means allowed.
        //
        // The exact preferences cookie name is not hardcoded because it is written by the
        // plugin's JS and is not stated anywhere in its PHP. Capturing the whole
        // wpconsent* family instead means a plugin rename cannot silently turn every US
        // order into 'unknown'. Pin the name during staging verification and tighten this.
        $wp = [];
        foreach (array_keys($_COOKIE) as $name) {
            if (0 === strpos($name, 'wpconsent')) {
                $wp[$name] = self::cookie($name);
            }
        }

        if ($wp) {
            $blob = strtolower(implode('|', $wp));

            // Only an explicit refusal overrides the opt-out default.
            $refused_ads   = false !== strpos($blob, 'marketing":false') || false !== strpos($blob, 'marketing=false');
            $refused_stats = false !== strpos($blob, 'statistics":false') || false !== strpos($blob, 'statistics=false');

            return [
                'advertising' => $refused_ads ? 'denied' : 'granted',
                'analytics'   => $refused_stats ? 'denied' : 'granted',
                'source'      => 'wpconsent',
                'raw'         => wp_json_encode($wp),
            ];
        }

        return [
            'advertising' => 'unknown',
            'analytics'   => 'unknown',
            'source'      => 'none',
            'raw'         => '',
        ];
    }

    /**
     * An undecided opt-in banner is not a grant. Only an actual choice can grant.
     */
    private static function verdict($decided, $allowed)
    {
        if (! $decided) {
            return 'unknown';
        }

        return $allowed ? 'granted' : 'denied';
    }

    /**
     * Meta's browser (_fbp) and click (_fbc) identifiers.
     *
     * _fbc only exists if the visitor arrived on an fbclid-tagged link at some point in
     * this browser. Its absence is normal and is not an error.
     */
    private static function read_meta_ids()
    {
        return array_filter([
            'fbp' => self::cookie('_fbp'),
            'fbc' => self::cookie('_fbc'),
        ]);
    }

    /**
     * Google Ads click identifiers, from the _gcl_* cookie family.
     *
     * Values look like "GCL.1712345678.Cj0KCQ..."; the identifier is the last segment.
     * _gcl_aw carries gclid, _gcl_gs carries gbraid, _gcl_dc the display click id.
     */
    private static function read_google_ids()
    {
        $out = [];

        foreach (['_gcl_aw' => 'gclid', '_gcl_gs' => 'gbraid', '_gcl_dc' => 'dclid'] as $cookie => $key) {
            $raw = self::cookie($cookie);
            if ('' === $raw) {
                continue;
            }
            $parts = explode('.', $raw);
            $value = end($parts);
            if ($value) {
                $out[$key] = $value;
            }
        }

        return $out;
    }

    /**
     * GA4 client_id and session_id.
     *
     * _ga looks like "GA1.1.1234567890.1234567890" and the client_id is the last two
     * segments joined, NOT the whole cookie - a very common way to produce Measurement
     * Protocol events that are accepted and then never attributed to anything.
     *
     * The per-stream cookie is _ga_<STREAM>, e.g. _ga_CDWNWLM5LZ for G-CDWNWLM5LZ. It is
     * matched by prefix rather than by hardcoded measurement ID so that adding a stream,
     * or changing one, does not quietly stop session stitching.
     * Format: "GS1.1.<session_id>.<session_number>....".
     */
    private static function read_ga4_ids()
    {
        $out = [];

        $ga = self::cookie('_ga');
        if ('' !== $ga) {
            $parts = explode('.', $ga);
            if (count($parts) >= 4) {
                $out['client_id'] = $parts[count($parts) - 2] . '.' . $parts[count($parts) - 1];
            }
        }

        foreach (array_keys($_COOKIE) as $name) {
            if (0 !== strpos($name, '_ga_')) {
                continue;
            }
            $parts = explode('.', self::cookie($name));
            if (count($parts) >= 3 && $parts[2]) {
                $out['session_id']  = $parts[2];
                $out['stream']      = substr($name, 4);
                break;
            }
        }

        return $out;
    }

    /**
     * One cookie, unslashed and cleaned. Returns '' when absent.
     */
    private static function cookie($name)
    {
        if (! isset($_COOKIE[$name])) {
            return '';
        }

        return sanitize_text_field(wp_unslash($_COOKIE[$name]));
    }
}
