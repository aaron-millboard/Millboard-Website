<?php

namespace Theme\Analytics\Destination;

use Theme\Analytics\ConversionEmitter;
use Theme\Analytics\EventPayload;

/**
 * GA4 Measurement Protocol.
 *
 * `client_id` is REQUIRED and is treated as a hard failure when missing, not a warning. The
 * Measurement Protocol accepts an event without a usable one and then strands it: no session,
 * no attribution, no join to anything the reports show. It fails silently and looks like it
 * worked, which is the worst possible failure mode for this project and exactly the class of
 * bug it was commissioned to fix. Better a logged rejection we can see.
 *
 * It comes from the `_ga` cookie as its LAST TWO segments, captured at checkout - not the whole
 * cookie value, which is the usual way to produce accepted-but-orphaned events.
 *
 * GA4 HAS NO EVENT DEDUPLICATION. There is no `event_id` equivalent: whatever is sent is
 * counted. So while the browser purchase tag is still live, every server event is a double
 * count. That is why validate mode posts to `/debug/mp/collect`, which runs the same validation
 * and returns `validationMessages` WITHOUT recording anything.
 *
 * Credentials come from wp-config constants, never from the database and never from git:
 *   MB_GA4_API_SECRET       required
 *   MB_GA4_MEASUREMENT_ID   optional, defaults to the live web stream
 */
class Ga4Measurement
{
    private const LIVE_ENDPOINT = 'https://www.google-analytics.com/mp/collect';
    private const DEBUG_ENDPOINT = 'https://www.google-analytics.com/debug/mp/collect';
    private const DEFAULT_MEASUREMENT_ID = 'G-CDWNWLM5LZ';

    /**
     * `generate_lead` for a zero-value sample order. Kept out of `purchase` so revenue reporting
     * and paid-campaign optimisation never see sample volume as sales.
     */
    private const EVENT_NAMES = [
        EventPayload::KIND_PURCHASE => 'purchase',
        EventPayload::KIND_LEAD     => 'generate_lead',
    ];

    /**
     * Render the wire body. Pure, so the format can be tested without a network.
     *
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public static function build_body(array $payload): array
    {
        $identifiers = $payload['identifiers'] ?? [];

        $items = [];
        foreach (($payload['items'] ?? []) as $item) {
            $items[] = [
                'item_id'   => (string) $item['id'],
                'item_name' => (string) $item['name'],
                'quantity'  => (int) $item['quantity'],
                'price'     => (float) $item['price'],
            ];
        }

        $params = [
            // GA4 deduplicates purchases by transaction_id within a property, so this must be
            // the same stable key everywhere rather than the mutable order number.
            'transaction_id' => (string) $payload['event_id'],
            'value'          => (float) $payload['value'],
            'currency'       => (string) $payload['currency'],
            'items'          => $items,
            // Without this GA4 attributes the event to no session and reports it as having
            // zero engagement.
            'engagement_time_msec' => 1,
        ];

        if (! empty($payload['page_url'])) {
            // Keeps URL-scoped audiences, created events and reporting matching the server
            // event exactly as they matched the browser one.
            $params['page_location'] = (string) $payload['page_url'];
        }

        if (! empty($identifiers['session_id'])) {
            $params['session_id'] = (string) $identifiers['session_id'];
        }

        return [
            'client_id'        => (string) ($identifiers['client_id'] ?? ''),
            'timestamp_micros' => (int) $payload['occurred_at'] * 1000000,
            'non_personalized_ads' => false,
            'events' => [[
                'name'   => self::EVENT_NAMES[$payload['kind']] ?? 'generate_lead',
                'params' => $params,
            ]],
        ];
    }

    /**
     * @param array<string, mixed> $payload
     * @return array{ok: bool, detail: string}
     */
    public static function send(array $payload, string $mode): array
    {
        $secret = \defined('MB_GA4_API_SECRET') ? (string) \MB_GA4_API_SECRET : '';
        if ('' === $secret) {
            return ['ok' => false, 'detail' => 'MB_GA4_API_SECRET is not defined'];
        }

        $body = self::build_body($payload);

        if ('' === $body['client_id']) {
            return ['ok' => false, 'detail' => 'no GA4 client_id on the order - refusing to send an unattributable event'];
        }

        $endpoint = ConversionEmitter::MODE_LIVE === $mode ? self::LIVE_ENDPOINT : self::DEBUG_ENDPOINT;

        $url = \add_query_arg([
            'measurement_id' => self::measurement_id(),
            'api_secret'     => $secret,
        ], $endpoint);

        $response = \wp_remote_post($url, [
            'timeout' => 20,
            'headers' => ['Content-Type' => 'application/json'],
            'body'    => \wp_json_encode($body),
        ]);

        if (\is_wp_error($response)) {
            return ['ok' => false, 'detail' => $response->get_error_message()];
        }

        $code = (int) \wp_remote_retrieve_response_code($response);
        $raw  = (string) \wp_remote_retrieve_body($response);

        if ($code < 200 || $code >= 300) {
            return ['ok' => false, 'detail' => 'HTTP ' . $code . ' ' . substr($raw, 0, 200)];
        }

        // The live endpoint answers 204 with an empty body whatever it thinks of the payload -
        // it will happily accept nonsense. Only the debug endpoint tells you anything, which is
        // the whole reason validate mode exists.
        if (ConversionEmitter::MODE_LIVE !== $mode) {
            $decoded  = json_decode($raw, true);
            $messages = is_array($decoded) && isset($decoded['validationMessages'])
                ? $decoded['validationMessages']
                : [];

            if (! empty($messages)) {
                return ['ok' => false, 'detail' => 'validation: ' . substr((string) \wp_json_encode($messages), 0, 250)];
            }

            return ['ok' => true, 'detail' => 'debug endpoint accepted, 0 validation messages'];
        }

        return ['ok' => true, 'detail' => 'HTTP ' . $code];
    }

    private static function measurement_id(): string
    {
        return \defined('MB_GA4_MEASUREMENT_ID') ? (string) \MB_GA4_MEASUREMENT_ID : self::DEFAULT_MEASUREMENT_ID;
    }
}
