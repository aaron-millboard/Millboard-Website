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

        // Editing in the list itself, which is the point of the feature.
        \add_action('admin_enqueue_scripts', [self::class, 'admin_assets']);
        \add_action('wp_ajax_mb_fulfil_inline', [self::class, 'ajax_inline']);

        // The agency works the queue in My Account and has no business behind
        // the dashboard. See keep_out_of_admin().
        \add_action('admin_init', [self::class, 'keep_out_of_admin']);
        \add_filter('show_admin_bar', [self::class, 'hide_admin_bar']);

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
                $out['mb_anmerkungen'] = \__('Anmerkungen', 'granola');
            }
        }

        // If the status column is not where we expected, still show them.
        if (!isset($out['mb_versanddatum'])) {
            $out['mb_versanddatum'] = \__('Versanddatum', 'granola');
            $out['mb_sendungsnummer'] = \__('Sendungsnummer', 'granola');
            $out['mb_anmerkungen'] = \__('Anmerkungen', 'granola');
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

        $map = [
            'mb_versanddatum' => [self::META_DISPATCHED_ON, 'date'],
            'mb_sendungsnummer' => [self::META_TRACKING, 'text'],
            'mb_anmerkungen' => [self::META_NOTES, 'text'],
        ];

        if (!isset($map[$column])) {
            return;
        }

        [$meta_key, $type] = $map[$column];
        $raw = (string) $order->get_meta($meta_key);

        // Shown formatted, edited raw. A German rep reading the list wants
        // 26.10.2026; the input underneath has to be the ISO value or the
        // browser's date control refuses it.
        $shown = ('date' === $type && '' !== $raw)
            ? \date_i18n(\get_option('date_format'), (int) \strtotime($raw))
            : $raw;

        \printf(
            '<button type="button" class="mb-fulfil-cell" data-order="%d" data-field="%s" data-type="%s" data-raw="%s" title="%s">%s</button>',
            $order->get_id(),
            \esc_attr($meta_key),
            \esc_attr($type),
            \esc_attr($raw),
            \esc_attr__('Click to edit', 'granola'),
            '' !== $shown
                ? \esc_html($shown)
                : '<span class="mb-fulfil-empty" aria-hidden="true">&mdash;</span>'
                    . '<span class="screen-reader-text">' . \esc_html__('Empty, click to edit', 'granola') . '</span>'
        );
    }

    /**
     * The inline editor: the whole point of this feature.
     *
     * The agency's job is a list of parcels, not 200 order screens. Opening an
     * order to type a tracking number is what the Google Sheet never made them
     * do, and the first thing they would complain about.
     *
     * A button, not a contenteditable or a permanent input: a button is
     * already focusable, already reachable by keyboard, and already announced
     * as something you activate. The script swaps it for a real input on
     * click and puts it back on save.
     */
    public static function admin_assets(string $hook): void
    {
        if (!self::is_orders_screen($hook) || !\current_user_can('edit_shop_orders')) {
            return;
        }

        self::enqueue_editor();

        \wp_register_style('mb-fulfil-inline', false, [], null);
        \wp_enqueue_style('mb-fulfil-inline');
        \wp_add_inline_style('mb-fulfil-inline', self::inline_style());
    }

    /**
     * The inline editor, for any screen that renders the cells.
     *
     * The My Account panel calls this too. The script is vanilla and reads
     * everything it needs off the cell's own data attributes, so the same copy
     * drives the admin list and the front-end queue, and there is one place
     * where "what happens when you type in a cell" is defined.
     *
     * No styles here: the admin list wants to look like wp-admin and the
     * account panel wants to look like the account, so each brings its own.
     */
    public static function enqueue_editor(): void
    {
        if (\wp_script_is('mb-fulfil-inline', 'enqueued')) {
            return;
        }

        \wp_register_script('mb-fulfil-inline', '', [], null, true);
        \wp_enqueue_script('mb-fulfil-inline');

        \wp_localize_script('mb-fulfil-inline', 'mbFulfil', [
            'ajax' => \admin_url('admin-ajax.php'),
            'nonce' => \wp_create_nonce('mb_fulfil_inline'),
            'saving' => \__('Saving…', 'granola'),
            'failed' => \__('Could not save. Reload and try again.', 'granola'),
        ]);

        \wp_add_inline_script('mb-fulfil-inline', self::inline_script());
    }

    /**
     * Send the agency back to the front end.
     *
     * They hold `edit_shop_orders` because that is what working an order
     * needs, and that capability by itself opens wp-admin. Aaron's condition
     * for giving an outside supplier accounts at all was that they never get
     * near the dashboard, so the capability stays and the door closes.
     *
     * Staff are untouched: anyone with `manage_woocommerce` -- shop managers
     * and administrators -- keeps wp-admin exactly as before. Only an account
     * actually carrying the fulfilment role is redirected.
     *
     * ⚠️ `wp_doing_ajax()` first. `admin_init` fires on admin-ajax.php, so
     * without it this would redirect the agency's own save request and every
     * edit they made would silently fail.
     */
    public static function keep_out_of_admin(): void
    {
        if (\wp_doing_ajax() || \wp_doing_cron() || !\is_user_logged_in()) {
            return;
        }

        if (!self::is_agency_account() || !\function_exists('wc_get_account_endpoint_url')) {
            return;
        }

        \wp_safe_redirect(\wc_get_account_endpoint_url('fulfilment'));
        exit;
    }

    /**
     * Hide the admin bar for the agency, since it only links where they cannot go.
     *
     * @param bool $show
     */
    public static function hide_admin_bar($show)
    {
        return self::is_agency_account() ? false : $show;
    }

    /**
     * An account whose job is fulfilment and nothing else.
     */
    private static function is_agency_account(): bool
    {
        if (!\is_user_logged_in() || \current_user_can('manage_woocommerce')) {
            return false;
        }

        $user = \wp_get_current_user();

        return \in_array(self::ROLE, (array) $user->roles, true);
    }

    private static function is_orders_screen(string $hook): bool
    {
        return \str_contains($hook, 'wc-orders') || 'edit.php' === $hook;
    }

    private static function inline_style(): string
    {
        return '.mb-fulfil-cell{background:none;border:1px dashed transparent;padding:2px 6px;cursor:text;'
            . 'font:inherit;color:inherit;text-align:left;min-width:7em;border-radius:2px}'
            . '.mb-fulfil-cell:hover,.mb-fulfil-cell:focus{border-color:#2271b1;background:#fff}'
            . '.mb-fulfil-empty{color:#8c8f94}'
            . '.mb-fulfil-input{width:100%;min-width:7em}'
            . '.mb-fulfil-busy{opacity:.5}';
    }

    private static function inline_script(): string
    {
        // Delegated, because WooCommerce redraws this table on filter and sort
        // and a listener bound to a cell would be lost with it.
        return <<<'JS'
(function () {
    document.addEventListener('click', function (e) {
        var cell = e.target.closest ? e.target.closest('.mb-fulfil-cell') : null;
        if (!cell || cell.dataset.editing === '1') { return; }
        e.preventDefault();

        cell.dataset.editing = '1';
        var input = document.createElement('input');
        input.type = cell.dataset.type === 'date' ? 'date' : 'text';
        input.className = 'mb-fulfil-input';
        input.value = cell.dataset.raw || '';
        cell.replaceWith(input);
        input.focus();

        var done = false;
        function finish(save) {
            if (done) { return; }
            done = true;
            var next = input.value;
            if (!save || next === (cell.dataset.raw || '')) { input.replaceWith(cell); cell.dataset.editing = ''; return; }

            input.disabled = true;
            input.classList.add('mb-fulfil-busy');

            var body = new URLSearchParams({
                action: 'mb_fulfil_inline',
                nonce: mbFulfil.nonce,
                order: cell.dataset.order,
                field: cell.dataset.field,
                value: next
            });

            fetch(mbFulfil.ajax, { method: 'POST', credentials: 'same-origin', body: body })
                .then(function (r) { return r.json(); })
                .then(function (p) {
                    if (!p || !p.success) { throw new Error('failed'); }
                    cell.dataset.raw = p.data.raw;
                    cell.innerHTML = p.data.html;
                    input.replaceWith(cell);
                    cell.dataset.editing = '';
                    // Entering a date completes the order, so the row's status
                    // is now stale. Saying so beats showing a lie.
                    if (p.data.status_changed) { location.reload(); }
                })
                .catch(function () {
                    window.alert(mbFulfil.failed);
                    input.replaceWith(cell);
                    cell.dataset.editing = '';
                });
        }

        input.addEventListener('blur', function () { finish(true); });
        input.addEventListener('keydown', function (ev) {
            if (ev.key === 'Enter') { ev.preventDefault(); finish(true); }
            if (ev.key === 'Escape') { ev.preventDefault(); finish(false); }
        });
    });
}());
JS;
    }

    /**
     * Save one cell.
     *
     * Reads the other two fields off the order rather than trusting the page,
     * so an inline edit can never blank a field the editor could not see.
     */
    public static function ajax_inline(): void
    {
        if (!\check_ajax_referer('mb_fulfil_inline', 'nonce', false)) {
            \wp_send_json_error(['message' => 'bad nonce'], 403);
        }

        if (!\current_user_can('edit_shop_orders')) {
            \wp_send_json_error(['message' => 'not permitted'], 403);
        }

        $order = \wc_get_order(isset($_POST['order']) ? \absint($_POST['order']) : 0);
        $field = isset($_POST['field']) ? \sanitize_text_field(\wp_unslash($_POST['field'])) : '';
        $value = isset($_POST['value']) ? \sanitize_text_field(\wp_unslash($_POST['value'])) : '';

        $allowed = [self::META_DISPATCHED_ON, self::META_TRACKING, self::META_NOTES];

        if (!$order instanceof \WC_Order || !\in_array($field, $allowed, true)) {
            \wp_send_json_error(['message' => 'unknown order or field'], 400);
        }

        $before = $order->get_status();

        $current = [
            self::META_DISPATCHED_ON => (string) $order->get_meta(self::META_DISPATCHED_ON),
            self::META_TRACKING => (string) $order->get_meta(self::META_TRACKING),
            self::META_NOTES => (string) $order->get_meta(self::META_NOTES),
        ];

        $current[$field] = $value;

        self::apply(
            $order,
            $current[self::META_DISPATCHED_ON],
            $current[self::META_TRACKING],
            $current[self::META_NOTES]
        );

        $fresh = \wc_get_order($order->get_id());

        $shown = (self::META_DISPATCHED_ON === $field && '' !== $value)
            ? \date_i18n(\get_option('date_format'), (int) \strtotime($value))
            : $value;

        \wp_send_json_success([
            'raw' => $value,
            'html' => '' !== $shown
                ? \esc_html($shown)
                : '<span class="mb-fulfil-empty" aria-hidden="true">&mdash;</span>',
            'status_changed' => $fresh instanceof \WC_Order && $fresh->get_status() !== $before,
        ]);
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

        self::apply(
            $order,
            isset($_POST[self::META_DISPATCHED_ON])
                ? \sanitize_text_field(\wp_unslash($_POST[self::META_DISPATCHED_ON]))
                : '',
            isset($_POST[self::META_TRACKING])
                ? \sanitize_text_field(\wp_unslash($_POST[self::META_TRACKING]))
                : '',
            isset($_POST[self::META_NOTES])
                ? \sanitize_textarea_field(\wp_unslash($_POST[self::META_NOTES]))
                : ''
        );
    }

    /**
     * Write the three fields and complete the order if a date has appeared.
     *
     * ⚠️ The ONLY place this is done. The meta box and the inline editor both
     * come through here, because two copies of "entering a date completes the
     * order and mails the customer" would eventually disagree, and the way
     * they would disagree is one of them mailing when it should not.
     *
     * @param string $date     Dispatch date, or '' to clear.
     * @param string $tracking Tracking number.
     * @param string $notes    Free text.
     */
    public static function apply(\WC_Order $order, string $date, string $tracking, string $notes): void
    {
        $had_date = '' !== (string) $order->get_meta(self::META_DISPATCHED_ON);

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
