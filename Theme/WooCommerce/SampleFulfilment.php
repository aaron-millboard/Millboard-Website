<?php

namespace Theme\WooCommerce;

/**
 * Sample fulfilment: the queue a third-party agency works from.
 *
 * Germany sells samples only, and it has NO Saltbox key, so a German order
 * reaches no SAP company. Fulfilment has therefore always happened outside the
 * shop: a HubSpot workflow copies each order into a Google Sheet, the agency
 * fills in dispatch details there, and nothing comes back. This replaces the
 * sheet, so the dispatch details live on the order they belong to.
 *
 * ⚠️ NOT a new screen. The agency works WooCommerce's own orders list, because
 * it already has search, filtering, sorting, pagination and HPOS support that
 * a bespoke table would have to grow one complaint at a time. What is added is
 * three fields, three columns, a role, and a dispatch action.
 *
 * The three fields are the sheet's, by their own names, so nobody has to learn
 * a second vocabulary mid-handover:
 *
 *   Versanddatum    dispatch date
 *   Sendungsnummer  tracking number
 *   Anmerkungen     notes
 *
 * ⚠️⚠️ READ `dispatch()` BEFORE TOUCHING THE STATUS. 1,387 German orders sit in
 * `wc-processing` and always have, because nothing ever moved them. Marking
 * those complete in bulk would send 1,387 dispatch e-mails to real customers
 * for parcels sent months ago. `suppress_retrospective_email()` is what stops
 * that, and it is not optional.
 */
class SampleFulfilment
{
    /** The agency's role. Deliberately not an editor or shop manager. */
    public const ROLE = 'millboard_fulfilment';

    /** Order meta, named after the sheet's own columns. */
    public const META_DISPATCHED_ON = '_mb_versanddatum';
    public const META_TRACKING = '_mb_sendungsnummer';
    public const META_NOTES = '_mb_anmerkungen';

    /**
     * Orders created before this never trigger a dispatch e-mail.
     *
     * Everything older is history the agency already has, imported for
     * reference rather than to be worked. The date is the line between "we
     * started doing this here" and "this was fulfilled from the sheet months
     * ago, and the customer does not want telling again".
     */
    public const LIVE_FROM = '2026-10-26';

    public static function init(): void
    {
        \add_action('init', [self::class, 'register_role']);

        // The agency's own columns on the orders list.
        \add_filter('manage_woocommerce_page_wc-orders_columns', [self::class, 'columns'], 20);
        \add_action('manage_woocommerce_page_wc-orders_custom_column', [self::class, 'column'], 10, 2);

        // And the box they type into.
        \add_action('add_meta_boxes', [self::class, 'meta_box']);
        \add_action('woocommerce_process_shop_order_meta', [self::class, 'save'], 10, 1);

        // ⚠️ The guard. See the class docblock.
        \add_filter('woocommerce_email_enabled_customer_completed_order', [self::class, 'suppress_retrospective_email'], 10, 2);
    }

    /**
     * The agency can work orders and nothing else.
     *
     * No `manage_woocommerce`, so they get no settings, no reports, no
     * products and no coupons. They are a supplier with a job to do, not an
     * administrator, and the NDA covering them is about customer addresses
     * rather than the shop's configuration.
     *
     * Roles are per site on multisite, so giving this only on de-de is what
     * keeps them out of every other market.
     */
    public static function register_role(): void
    {
        if (\get_role(self::ROLE)) {
            return;
        }

        \add_role(self::ROLE, 'Sample fulfilment', [
            'read' => true,
            'edit_shop_orders' => true,
            'read_shop_order' => true,
            'read_private_shop_orders' => true,
            'edit_others_shop_orders' => true,
            'edit_published_shop_orders' => true,
        ]);
    }

    /**
     * @param array<string,string> $columns
     * @return array<string,string>
     */
    public static function columns(array $columns): array
    {
        // After the status, which is what the agency scans down.
        $out = [];

        foreach ($columns as $key => $label) {
            $out[$key] = $label;

            if ('order_status' === $key) {
                $out['mb_versanddatum'] = \__('Versanddatum', 'granola');
                $out['mb_sendungsnummer'] = \__('Sendungsnummer', 'granola');
            }
        }

        // If the status column is not where we expected, still show them.
        if (!isset($out['mb_versanddatum'])) {
            $out['mb_versanddatum'] = \__('Versanddatum', 'granola');
            $out['mb_sendungsnummer'] = \__('Sendungsnummer', 'granola');
        }

        return $out;
    }

    public static function column(string $column, $order): void
    {
        if (!$order instanceof \WC_Order) {
            $order = \wc_get_order($order);
        }

        if (!$order instanceof \WC_Order) {
            return;
        }

        if ('mb_versanddatum' === $column) {
            $value = (string) $order->get_meta(self::META_DISPATCHED_ON);

            echo $value
                ? \esc_html(\date_i18n(\get_option('date_format'), \strtotime($value)))
                : '<span aria-hidden="true">&mdash;</span><span class="screen-reader-text">'
                    . \esc_html__('Not dispatched', 'granola') . '</span>';

            return;
        }

        if ('mb_sendungsnummer' === $column) {
            $value = (string) $order->get_meta(self::META_TRACKING);

            echo $value ? \esc_html($value) : '<span aria-hidden="true">&mdash;</span>';
        }
    }

    public static function meta_box(): void
    {
        $screen = \class_exists('\Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController')
            && \wc_get_container()
                ->get(\Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController::class)
                ->custom_orders_table_usage_is_enabled()
            ? \wc_get_page_screen_id('shop-order')
            : 'shop_order';

        \add_meta_box(
            'mb-sample-fulfilment',
            \__('Fulfilment', 'granola'),
            [self::class, 'render_meta_box'],
            $screen,
            'side',
            'high'
        );
    }

    public static function render_meta_box($post_or_order): void
    {
        $order = $post_or_order instanceof \WC_Order ? $post_or_order : \wc_get_order($post_or_order->ID ?? 0);

        if (!$order instanceof \WC_Order) {
            return;
        }

        \wp_nonce_field('mb_fulfilment_save', 'mb_fulfilment_nonce');

        $fields = [
            self::META_DISPATCHED_ON => [\__('Versanddatum', 'granola'), 'date'],
            self::META_TRACKING => [\__('Sendungsnummer', 'granola'), 'text'],
        ];

        foreach ($fields as $key => [$label, $type]) {
            \printf(
                '<p><label for="%1$s"><strong>%2$s</strong></label>'
                    . '<input type="%3$s" id="%1$s" name="%1$s" value="%4$s" class="widefat" /></p>',
                \esc_attr($key),
                \esc_html($label),
                \esc_attr($type),
                \esc_attr((string) $order->get_meta($key))
            );
        }

        \printf(
            '<p><label for="%1$s"><strong>%2$s</strong></label>'
                . '<textarea id="%1$s" name="%1$s" rows="3" class="widefat">%3$s</textarea></p>',
            \esc_attr(self::META_NOTES),
            \esc_html__('Anmerkungen', 'granola'),
            \esc_textarea((string) $order->get_meta(self::META_NOTES))
        );

        echo '<p class="description">'
            . \esc_html__('Saving a dispatch date marks this order complete and tells the customer it is on its way.', 'granola')
            . '</p>';
    }

    /**
     * Save the three fields, and complete the order the moment a date appears.
     *
     * Completing here rather than behind a separate button is deliberate: the
     * agency's job is "I sent it", and asking them to record that twice is how
     * the two states drift apart.
     */
    public static function save(int $order_id): void
    {
        if (!isset($_POST['mb_fulfilment_nonce'])
            || !\wp_verify_nonce(\sanitize_key(\wp_unslash($_POST['mb_fulfilment_nonce'])), 'mb_fulfilment_save')) {
            return;
        }

        $order = \wc_get_order($order_id);

        if (!$order instanceof \WC_Order) {
            return;
        }

        $had_date = '' !== (string) $order->get_meta(self::META_DISPATCHED_ON);

        $date = isset($_POST[self::META_DISPATCHED_ON])
            ? \sanitize_text_field(\wp_unslash($_POST[self::META_DISPATCHED_ON]))
            : '';
        $tracking = isset($_POST[self::META_TRACKING])
            ? \sanitize_text_field(\wp_unslash($_POST[self::META_TRACKING]))
            : '';
        $notes = isset($_POST[self::META_NOTES])
            ? \sanitize_textarea_field(\wp_unslash($_POST[self::META_NOTES]))
            : '';

        $order->update_meta_data(self::META_DISPATCHED_ON, $date);
        $order->update_meta_data(self::META_TRACKING, $tracking);
        $order->update_meta_data(self::META_NOTES, $notes);
        $order->save();

        // Only on the transition, and only forwards. Re-saving an order that
        // already carried a date must not complete it a second time.
        if (!$had_date && '' !== $date && $order->has_status('processing')) {
            $order->update_status(
                'completed',
                \sprintf(
                    /* translators: %s: the tracking number, or a dash. */
                    \__('Dispatched by the fulfilment team. Tracking: %s', 'granola'),
                    $tracking ?: '-'
                )
            );
        }
    }

    /**
     * ⚠️ The guard that stops 1,387 e-mails.
     *
     * Every German order ever placed sits in `wc-processing`, because Germany
     * has no Saltbox and nothing has ever moved one on. The day someone
     * selects all of them and marks them complete -- by hand, in a bulk
     * action, or in a tidy-up script written a year from now -- WooCommerce
     * would send a dispatch e-mail to every one of those customers about a
     * parcel they received months ago.
     *
     * So the completed-order e-mail is refused for any order placed before
     * this flow existed. New orders are unaffected: they are created after
     * LIVE_FROM and mail normally.
     *
     * This is a backstop, not the mechanism. save() only completes an order on
     * the transition, so the ordinary path never reaches this. It is here for
     * the paths nobody has thought of yet.
     */
    public static function suppress_retrospective_email(bool $enabled, $order): bool
    {
        if (!$enabled || !$order instanceof \WC_Order) {
            return $enabled;
        }

        $created = $order->get_date_created();

        if (!$created) {
            return $enabled;
        }

        if ($created->getTimestamp() >= \strtotime(self::LIVE_FROM . ' 00:00:00')) {
            return $enabled;
        }

        // Older than the flow. Mail only if this one really was dispatched
        // through here, which an order fulfilled from the sheet never was.
        return '' !== (string) $order->get_meta(self::META_DISPATCHED_ON);
    }
}
