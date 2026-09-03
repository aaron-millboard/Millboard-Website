<?php

namespace Theme\Analytics\Destination;

use Theme\Analytics\ConversionEmitter;
use Theme\Analytics\EventPayload;

/**
 * Meta Conversions API.
 *
 * `event_id` must be identical to the `eventID` the browser pixel sends for the same order, or
 * Meta counts both. It is `<blog id>-<order id>` - see EventPayload for why the order NUMBER
 * cannot be used.
 *
 * The point of this destination is not only volume. Purchase currently has NO event match
 * quality score in Events Manager, because the browser pixel sends no customer identifiers at
 * all, and Meta's own panel flags ad spend affected by low data quality. Hashed email, phone,
 * name, city and postcode from the order record are what produce a score.
 *
 * Credentials come from wp-config constants, never from the database and never from git:
 *   MB_META_CAPI_TOKEN       required
 *   MB_META_DATASET_ID       optional, defaults to the live site pixel
 *   MB_META_TEST_EVENT_CODE  required in validate mode
 */
class MetaCapi
{
    private const GRAPH_VERSION = 'v21.0';
    private const DEFAULT_DATASET = '239673430701675';

    /**
     * Meta's event names. `Lead` for a zero-value sample order, so that sample volume never
     * lands in Purchase and teaches the ad sets to optimise for sample-seekers.
     */
    private const EVENT_NAMES = [
        EventPayload::KIND_PURCHASE => 'Purchase',
        EventPayload::KIND_LEAD     => 'Lead',
    ];

    /**
     * Render the wire body. Pure, so the format can be tested without a network.
     *
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public static function build_body(array $payload, string $mode): array
    {
        $identifiers = $payload['identifiers'] ?? [];

        // Hashed fields go as arrays; fbp, fbc, IP and user agent must NOT be hashed.
        $user_data = [];
        foreach (($payload['user'] ?? []) as $key => $hash) {
            $user_data[$key] = [$hash];
        }
        if (! empty($identifiers['fbp'])) {
            $user_data['fbp'] = $identifiers['fbp'];
        }
        if (! empty($identifiers['fbc'])) {
            $user_data['fbc'] = $identifiers['fbc'];
        }
        if (! empty($payload['client']['ip'])) {
            $user_data['client_ip_address'] = $payload['client']['ip'];
        }
        if (! empty($payload['client']['user_agent'])) {
            $user_data['client_user_agent'] = $payload['client']['user_agent'];
        }

        $contents = [];
        foreach (($payload['items'] ?? []) as $item) {
            $contents[] = [
                'id'         => (string) $item['id'],
                'quantity'   => (int) $item['quantity'],
                'item_price' => (float) $item['price'],
            ];
        }

        $event = [
            'event_name'    => self::EVENT_NAMES[$payload['kind']] ?? 'Lead',
            'event_time'    => (int) $payload['occurred_at'],
            'event_id'      => (string) $payload['event_id'],
            'action_source' => 'website',
            // Without this, any custom conversion defined by a URL rule silently fails to match.
            'event_source_url' => (string) ($payload['page_url'] ?? ''),
            'user_data'     => $user_data,
            'custom_data'   => array_filter([
                'currency'     => $payload['currency'],
                'value'        => (float) $payload['value'],
                'contents'     => $contents,
                'content_type' => $contents ? 'product' : null,
                // Carried so a human reconciling Events Manager against WooCommerce has the
                // order number to hand. Never used for matching.
                'order_id'     => $payload['order_number'],
            ], static function ($v) {
                return null !== $v && [] !== $v;
            }),
        ];

        $body = ['data' => [$event]];

        if (ConversionEmitter::MODE_VALIDATE === $mode) {
            $body['test_event_code'] = self::test_event_code();
        }

        return $body;
    }

    /**
     * @param array<string, mixed> $payload
     * @return array{ok: bool, detail: string}
     */
    public static function send(array $payload, string $mode): array
    {
        $token = \defined('MB_META_CAPI_TOKEN') ? (string) \MB_META_CAPI_TOKEN : '';
        if ('' === $token) {
            return ['ok' => false, 'detail' => 'MB_META_CAPI_TOKEN is not defined'];
        }

        if (ConversionEmitter::MODE_VALIDATE === $mode && '' === self::test_event_code()) {
            // Refuse rather than fall back to a live send. A validate run that quietly became
            // real is the one outcome this mode exists to prevent.
            return ['ok' => false, 'detail' => 'validate mode needs MB_META_TEST_EVENT_CODE'];
        }

        $url = sprintf(
            'https://graph.facebook.com/%s/%s/events?access_token=%s',
            self::GRAPH_VERSION,
            rawurlencode(self::dataset_id()),
            rawurlencode($token)
        );

        $response = \wp_remote_post($url, [
            'timeout' => 20,
            'headers' => ['Content-Type' => 'application/json'],
            'body'    => \wp_json_encode(self::build_body($payload, $mode)),
        ]);

        if (\is_wp_error($response)) {
            return ['ok' => false, 'detail' => $response->get_error_message()];
        }

        $code = (int) \wp_remote_retrieve_response_code($response);
        $body = (string) \wp_remote_retrieve_body($response);

        if ($code >= 200 && $code < 300) {
            $decoded = json_decode($body, true);
            $received = is_array($decoded) && isset($decoded['events_received']) ? $decoded['events_received'] : '?';

            return ['ok' => true, 'detail' => 'events_received=' . $received];
        }

        return ['ok' => false, 'detail' => 'HTTP ' . $code . ' ' . substr($body, 0, 200)];
    }

    private static function dataset_id(): string
    {
        return \defined('MB_META_DATASET_ID') ? (string) \MB_META_DATASET_ID : self::DEFAULT_DATASET;
    }

    private static function test_event_code(): string
    {
        return \defined('MB_META_TEST_EVENT_CODE') ? (string) \MB_META_TEST_EVENT_CODE : '';
    }
}
