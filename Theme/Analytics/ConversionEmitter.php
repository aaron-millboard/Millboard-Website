<?php

namespace Theme\Analytics;

use Theme\Utils\Environment;
use Theme\Analytics\Destination\MetaCapi;
use Theme\Analytics\Destination\Ga4Measurement;

/**
 * Sends each order's conversion to Meta and GA4 from the server, once, on consent.
 *
 * The browser cannot be relied on for this. Measured on production 3 Sep 2026: Meta received
 * Purchase events for 43% of orders while 68% of visitors accepted cookies, so roughly a
 * quarter of orders are consented conversions we are entitled to send and lose anyway - to
 * consent-gate timing, ad blockers, Safari's cookie lifetime and people closing the tab.
 * Purchase also carries no event match quality score at all in Events Manager, because the
 * browser pixel has no customer identifiers to offer.
 *
 * WHAT THIS DELIBERATELY IS NOT
 *
 * It is not a way around consent. Sending a non-consenting customer's purchase from our server
 * is still processing their data for advertising. Eligibility is decided per destination from
 * the consent snapshot stored on the order at checkout, and `unknown` fails closed. The prize
 * is delivering the people who DID agree, completely and exactly once, with hashed identifiers
 * that give Meta something to match on.
 *
 * THREE MODES, AND STAGING CAN NEVER REACH THE LIVE ONE
 *
 *   off       nothing is sent. The default, so deploying this does not start traffic.
 *   validate  GA4 goes to /debug/mp/collect, which validates and returns errors without
 *             recording anything; Meta carries a test_event_code, so events appear only in
 *             Test Events. Nothing reaches reporting or optimisation.
 *   live      the real endpoints.
 *
 * Off production the mode is forced to `validate` whatever the option says, using the same
 * host check as the HubSpot write guard. An environment that can be cloned must not be able to
 * write to live datasets by having the wrong row in its database.
 *
 * `validate` also exists because GA4 has NO event deduplication. While the browser tag is still
 * live, anything the server records is a double count. Cutover order matters: prove it in
 * validate, then disable the browser purchase tag and switch to live in the same change.
 *
 * QUEUED, NEVER INLINE
 *
 * Dispatch runs on Action Scheduler, which WooCommerce already ships. Nothing about an
 * analytics send belongs in the request that takes someone's money: a slow API call would sit
 * in front of the customer, and a fatal would fail the sale. Every send is wrapped as well.
 *
 * Each destination records its own sent flag, so a retry cannot double-send one and re-send the
 * other, and so a failure in one never blocks the other.
 */
class ConversionEmitter
{
    public const ACTION = 'mb_sst_send_conversion';
    public const OPTION_MODE = 'mb_sst_mode';

    public const MODE_OFF = 'off';
    public const MODE_VALIDATE = 'validate';
    public const MODE_LIVE = 'live';

    /**
     * Order meta prefix for the per-destination sent flag, e.g. `_mb_sst_sent_meta`.
     */
    private const SENT_PREFIX = '_mb_sst_sent_';

    private const ATTEMPT_PREFIX = '_mb_sst_attempts_';

    /**
     * Retry schedule in seconds. Deliberately short and finite: a conversion that is hours late
     * is worth little to optimisation, and an unbounded retry against a rejected payload just
     * fills the queue.
     */
    private const BACKOFF = [120, 600, 3600];

    public static function init(): void
    {
        // Fires once the order is real, regardless of whether the browser ever reached the
        // confirmation page. The browser-side tags key off that page, which is one of the ways
        // they lose conversions.
        \add_action('woocommerce_order_status_processing', [__CLASS__, 'schedule'], 20, 1);
        \add_action('woocommerce_order_status_completed', [__CLASS__, 'schedule'], 20, 1);

        \add_action(self::ACTION, [__CLASS__, 'dispatch'], 10, 2);
    }

    /**
     * @return array<string, class-string>
     */
    public static function destinations(): array
    {
        return [
            'meta' => MetaCapi::class,
            'ga4'  => Ga4Measurement::class,
        ];
    }

    /**
     * Effective mode. Never returns `live` off production.
     */
    public static function mode(): string
    {
        $mode = (string) \get_option(self::OPTION_MODE, self::MODE_OFF);

        if (! in_array($mode, [self::MODE_OFF, self::MODE_VALIDATE, self::MODE_LIVE], true)) {
            $mode = self::MODE_OFF;
        }

        if (self::MODE_LIVE === $mode && ! Environment::is_production()) {
            return self::MODE_VALIDATE;
        }

        return $mode;
    }

    /**
     * Queue one job per eligible destination.
     *
     * @param mixed $order_id
     */
    public static function schedule($order_id): void
    {
        try {
            if (self::MODE_OFF === self::mode()) {
                return;
            }

            $order = \wc_get_order($order_id);
            if (! $order instanceof \WC_Order) {
                return;
            }

            foreach (array_keys(self::destinations()) as $key) {
                if (! self::is_eligible($order, $key)) {
                    continue;
                }

                if (! function_exists('as_schedule_single_action')) {
                    // Action Scheduler missing is a WooCommerce-level problem, not ours. Send
                    // inline rather than silently dropping the conversion, still wrapped.
                    self::dispatch($order->get_id(), $key);
                    continue;
                }

                if (\as_has_scheduled_action(self::ACTION, [$order->get_id(), $key])) {
                    continue;
                }

                \as_schedule_single_action(time() + 60, self::ACTION, [$order->get_id(), $key], 'millboard-sst');
            }
        } catch (\Throwable $e) {
            self::log('schedule failed for order ' . (is_scalar($order_id) ? $order_id : '?') . ': ' . $e->getMessage());
        }
    }

    /**
     * Send one order to one destination.
     *
     * @param mixed $order_id
     * @param mixed $destination
     */
    public static function dispatch($order_id, $destination = ''): void
    {
        try {
            $mode = self::mode();
            if (self::MODE_OFF === $mode) {
                return;
            }

            $destinations = self::destinations();
            $key = (string) $destination;
            if (! isset($destinations[$key])) {
                return;
            }

            $order = \wc_get_order($order_id);
            if (! $order instanceof \WC_Order || ! self::is_eligible($order, $key)) {
                return;
            }

            $payload = EventPayload::from_order($order);
            if (null === $payload) {
                return;
            }

            /** @var class-string $class */
            $class  = $destinations[$key];
            $result = $class::send($payload, $mode);

            if (! empty($result['ok'])) {
                $order->update_meta_data(self::SENT_PREFIX . $key, [
                    'at'     => gmdate('c'),
                    'mode'   => $mode,
                    'detail' => (string) ($result['detail'] ?? ''),
                ]);
                $order->save();
                self::log(sprintf('%s ok for order %d (%s): %s', $key, $order->get_id(), $mode, $result['detail'] ?? ''));

                return;
            }

            self::retry($order, $key, (string) ($result['detail'] ?? 'unknown error'));
        } catch (\Throwable $e) {
            self::log('dispatch failed: ' . $e->getMessage());
        }
    }

    /**
     * A destination is eligible when it has not already sent, and when the consent recorded on
     * the order at checkout covers it. `unknown` is not a grant.
     */
    private static function is_eligible(\WC_Order $order, string $destination): bool
    {
        if ($order->get_meta(self::SENT_PREFIX . $destination)) {
            return false;
        }

        $identity = $order->get_meta(\Theme\WooCommerce\OrderIdentity::META_KEY);
        $consent  = is_array($identity) && isset($identity['consent']) ? $identity['consent'] : [];

        // Meta is advertising. GA4 measurement is analytics. CMPs treat them separately and so
        // must we - somebody can reasonably allow measurement and refuse advertising.
        $needed = ('meta' === $destination) ? 'advertising' : 'analytics';

        return 'granted' === ($consent[$needed] ?? 'unknown');
    }

    private static function retry(\WC_Order $order, string $destination, string $why): void
    {
        $key      = self::ATTEMPT_PREFIX . $destination;
        $attempts = (int) $order->get_meta($key);

        if ($attempts >= count(self::BACKOFF) || ! function_exists('as_schedule_single_action')) {
            self::log(sprintf('%s GIVING UP on order %d after %d attempts: %s', $destination, $order->get_id(), $attempts, $why));

            return;
        }

        $delay = self::BACKOFF[$attempts];
        $order->update_meta_data($key, $attempts + 1);
        $order->save();

        \as_schedule_single_action(time() + $delay, self::ACTION, [$order->get_id(), $destination], 'millboard-sst');
        self::log(sprintf('%s retry %d for order %d in %ds: %s', $destination, $attempts + 1, $order->get_id(), $delay, $why));
    }

    private static function log(string $message): void
    {
        if (function_exists('wc_get_logger')) {
            \wc_get_logger()->info($message, ['source' => 'millboard-sst']);
        }
    }
}
