<?php

/**
 * Order to contact sync.
 *
 * Runs on a scheduled single event rather than inline at checkout. Checkout must
 * never be slowed down or broken by an outbound API call, and CRM Perks needs a
 * moment to create the contact before we can patch it.
 */

declare(strict_types=1);

namespace Millboard\HubSpotOrderFields;

if (!defined('ABSPATH')) {
    exit;
}

final class Sync
{
    private const HOOK = 'millboard_hsof_sync_order';

    /** Delays, in seconds, for the initial attempt and each retry. */
    private const SCHEDULE = [90, 600, 3600];

    private const MAX_ATTEMPTS = 3;

    public static function init(): void
    {
        // Fires once the order exists and its meta has been written.
        add_action('woocommerce_checkout_order_processed', [self::class, 'queue'], 20, 1);

        // Covers orders created by other routes, e.g. the Store API or admin.
        add_action('woocommerce_new_order', [self::class, 'queue'], 20, 1);

        add_action(self::HOOK, [self::class, 'run'], 10, 1);
    }

    /**
     * Schedule the first attempt for an order.
     *
     * @param int|string $order_id
     */
    public static function queue($order_id): void
    {
        $order_id = (int) $order_id;

        if ($order_id <= 0) {
            return;
        }

        if (wp_next_scheduled(self::HOOK, [$order_id])) {
            return;
        }

        wp_schedule_single_event(time() + self::SCHEDULE[0], self::HOOK, [$order_id]);
    }

    /**
     * Attempt the sync for one order.
     *
     * @param int|string $order_id
     */
    public static function run($order_id): void
    {
        $order_id = (int) $order_id;
        $order    = wc_get_order($order_id);

        if (!$order instanceof \WC_Order) {
            return;
        }

        if ($order->get_meta(SYNCED_META, true) === 'yes') {
            return;
        }

        $email = trim((string) $order->get_billing_email());

        if ($email === '' || !is_email($email)) {
            log_line('warning', 'Order has no usable billing email, skipping', ['order' => $order_id]);
            return;
        }

        $mapped = Mapper::from_order($order);

        foreach ($mapped['skipped'] as $property => $value) {
            // This is the case we care most about catching. An unmapped value
            // means the checkout offers an option the mapping does not know, and
            // sending it would be silently discarded by HubSpot.
            log_line('warning', 'Unmapped checkout value, not sent', [
                'order'    => $order_id,
                'property' => $property,
                'value'    => $value,
            ]);
        }

        if ($mapped['properties'] === []) {
            $order->update_meta_data(SYNCED_META, 'yes');
            $order->save();
            log_line('info', 'No custom fields present on order, nothing to sync', ['order' => $order_id]);
            return;
        }

        $result   = Client::patch_contact_by_email($email, $mapped['properties']);
        $attempts = (int) $order->get_meta(ATTEMPT_META, true);

        if ($result['result'] === Client::RESULT_OK) {
            $order->update_meta_data(SYNCED_META, 'yes');
            $order->delete_meta_data(ATTEMPT_META);
            $order->save();

            log_line('info', 'Synced ' . count($mapped['properties']) . ' field(s) to contact', [
                'order'      => $order_id,
                'properties' => array_keys($mapped['properties']),
            ]);

            return;
        }

        if ($result['result'] === Client::RESULT_NO_TOKEN) {
            log_line('error', 'No HubSpot token configured, sync disabled', ['order' => $order_id]);
            return;
        }

        // Retryable: contact not created yet, throttled, or a transient upstream error.
        $retryable = in_array($result['result'], [Client::RESULT_NOT_FOUND, Client::RESULT_RATE_LIMIT], true);
        $attempts++;

        if ($retryable && $attempts < self::MAX_ATTEMPTS) {
            $order->update_meta_data(ATTEMPT_META, (string) $attempts);
            $order->save();

            wp_schedule_single_event(time() + self::SCHEDULE[$attempts], self::HOOK, [$order_id]);

            log_line('info', 'Retry scheduled (' . $result['result'] . ')', [
                'order'   => $order_id,
                'attempt' => $attempts,
            ]);

            return;
        }

        $order->update_meta_data(ATTEMPT_META, (string) $attempts);
        $order->save();

        log_line('error', 'Sync failed: ' . $result['message'], [
            'order'  => $order_id,
            'status' => $result['status'],
        ]);
    }

    /**
     * Backfill historic orders.
     *
     * WooCommerce holds these fields on every order, so this recovers records
     * that CRM Perks never transmitted at all, which a HubSpot workflow reading
     * the Orders object cannot reach.
     *
     * @return array{processed: int, synced: int, skipped: int, failed: int}
     */
    public static function backfill(int $limit = 50, bool $include_already_synced = false): array
    {
        // Deliberately no meta_query here. It is not dependable through
        // wc_get_orders once High-Performance Order Storage is in play, so the
        // already-synced check is done in PHP below. Over-fetch to compensate.
        $ids = wc_get_orders([
            'limit'   => $limit * 4,
            'orderby' => 'date',
            'order'   => 'DESC',
            'return'  => 'ids',
        ]);

        $summary = ['processed' => 0, 'synced' => 0, 'skipped' => 0, 'failed' => 0];

        foreach ($ids as $id) {
            if ($summary['processed'] >= $limit) {
                break;
            }

            $order = wc_get_order($id);

            if (!$order instanceof \WC_Order) {
                continue;
            }

            if (!$include_already_synced && $order->get_meta(SYNCED_META, true) === 'yes') {
                continue;
            }

            $summary['processed']++;

            $email  = trim((string) $order->get_billing_email());
            $mapped = Mapper::from_order($order);

            if ($email === '' || !is_email($email) || $mapped['properties'] === []) {
                $summary['skipped']++;
                continue;
            }

            $result = Client::patch_contact_by_email($email, $mapped['properties']);

            if ($result['result'] === Client::RESULT_OK) {
                $order->update_meta_data(SYNCED_META, 'yes');
                $order->save();
                $summary['synced']++;
            } else {
                $summary['failed']++;
            }

            // Stay well inside HubSpot's private-app limit of 100 requests per
            // 10 seconds. Backfills are not urgent.
            usleep(150000);
        }

        log_line('info', 'Backfill run complete', $summary);

        return $summary;
    }
}
