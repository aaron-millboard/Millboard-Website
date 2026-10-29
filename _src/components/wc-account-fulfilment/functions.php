<?php

/**
 * Sample fulfilment -- the queue, in My Account.
 *
 * The same three fields Theme\WooCommerce\SampleFulfilment puts on the orders
 * list, rendered where the agency can reach them without a wp-admin login.
 * That is the whole reason this exists: the agency is a supplier under an NDA,
 * not a member of staff, and nobody wants them behind the dashboard.
 *
 * ⚠️ This does NOT re-implement saving. Every edit posts to the SAME
 * `mb_fulfil_inline` action the admin screen uses, so the rule that entering a
 * dispatch date completes the order -- and the guard that stops 1,387
 * retrospective e-mails -- lives in one place and cannot drift. Read
 * SampleFulfilment::apply() before changing anything here.
 */

namespace Granola\Components\WC_Account_Fulfilment;

use Theme\WooCommerce\SampleFulfilment;

/** The account endpoint, so /my-account/fulfilment/ resolves. */
const ENDPOINT = 'fulfilment';

/**
 * Rows per page.
 *
 * 774 German orders on staging, 1,387 on production. The admin list this
 * replaces had WooCommerce's own paging; a bespoke table has to bring its own
 * or it is unusable on day one.
 */
const PER_PAGE = 25;

/**
 * Where the queue exists at all.
 *
 * Germany, because Germany sells samples only, has no Saltbox key and has
 * always been fulfilled off a spreadsheet. Everywhere else an order reaches
 * SAP and this panel would be noise.
 */
const SITES = ['/de-de/'];

/** Switch another market on without a deploy. */
const OPTION = 'millboard_fulfilment_enabled';

/**
 * Query arguments, read off the URL.
 *
 * Plain `$_GET`, so paging and searching work as links before any JavaScript
 * does, exactly as the Resources panel does it.
 *
 * @return array{search:string,outstanding:bool,historic:bool,page:int}
 */
function request(): array
{
    // phpcs:disable WordPress.Security.NonceVerification.Recommended -- read-only navigation.
    return [
        'search' => isset($_GET['mbfq']) ? \sanitize_text_field(\wp_unslash($_GET['mbfq'])) : '',
        // Outstanding-only is the DEFAULT, and `mbfall=1` turns it off. A
        // fulfilment queue whose first page is three years of completed
        // parcels is not a queue.
        'outstanding' => empty($_GET['mbfall']),
        /*
         * Orders from BEFORE this flow existed are hidden by default, and
         * `mbfold=1` brings them back.
         *
         * 1,414 German orders sit in the queue, and all but a handful were
         * fulfilled from the Google Sheet months ago. They were imported for
         * reference, not to be worked, so a queue that opens on three years of
         * finished parcels is one nobody can use. They are still one link
         * away, and nothing about the orders themselves is changed.
         */
        'historic' => !empty($_GET['mbfold']),
        'page' => \max(1, isset($_GET['mbfp']) ? \absint($_GET['mbfp']) : 1),
    ];
    // phpcs:enable
}

/**
 * Is this a market with a fulfilment queue?
 */
function is_fulfilment_site(): bool
{
    if (!\is_multisite()) {
        return true;
    }

    $site = \get_blog_details(\get_current_blog_id());
    $path = $site ? \trailingslashit((string) $site->path) : '/';

    $sites = SITES;

    if ('yes' === \get_option(OPTION)) {
        $sites[] = $path;
    }

    return \in_array($path, \apply_filters('millboard_fulfilment_sites', $sites), true);
}

/**
 * May this user work the queue?
 *
 * `edit_shop_orders` and nothing new, deliberately. It is the capability the
 * agency's role was given, the one German staff already hold, and the one
 * `mb_fulfil_inline` checks before it writes. Inventing a second capability
 * here would mean the panel and the save path could disagree about who is
 * allowed, and the way they would disagree is the panel showing someone a
 * table of customer addresses they cannot actually use.
 */
function can_work_fulfilment(?int $user_id = null): bool
{
    if (!is_fulfilment_site()) {
        return false;
    }

    return null === $user_id
        ? \current_user_can('edit_shop_orders')
        : \user_can($user_id, 'edit_shop_orders');
}

/**
 * One page of the queue.
 *
 * @param array{search:string,outstanding:bool,page:int} $request
 * @return array{orders:array<int,\WC_Order>,total:int,pages:int}
 */
function page_of_orders(array $request): array
{
    $args = [
        'limit' => PER_PAGE,
        'paged' => $request['page'],
        'paginate' => true,
        'status' => \array_keys(\wc_get_order_statuses()),
        'orderby' => 'date',
        'order' => 'DESC',
    ];

    if (!$request['historic']) {
        /*
         * SampleFulfilment::LIVE_FROM, not a date of its own.
         *
         * That constant already defines the line between "we started doing
         * this here" and "this was fulfilled from the sheet months ago", and
         * it is what suppress_retrospective_email() uses to decide whether
         * completing an order may mail the customer. Using the same date in
         * both places means the queue shows exactly the orders it is safe to
         * work, and one edit moves both.
         */
        $args['date_created'] = '>=' . SampleFulfilment::LIVE_FROM;
    }

    if ('' !== $request['search']) {
        $args['s'] = $request['search'];
    }

    if ($request['outstanding']) {
        /*
         * Not finished with: nobody has ticked it off.
         *
         * This used to key on the dispatch date, which made posting the parcel
         * the only way a line could leave -- and some orders end without a
         * parcel. A return, a bad address, a duplicate: the agency writes a
         * note and that is the end of it, and under the old rule the row stayed
         * in the queue forever.
         *
         * So the tick is now the only exit, and the date is just data. Typing a
         * date still completes the order and mails the customer their tracking,
         * exactly as before; it simply no longer decides what the queue shows.
         * That also gives the agency as long as it likes to type the tracking
         * number and a note, instead of racing a row that vanished when the
         * date went in.
         *
         * Both halves are needed. An order nobody has touched has no meta row
         * at all, and one that was unticked has an empty one; testing only for
         * the second would hide the entire queue.
         */
        $args['meta_query'] = [
            'relation' => 'OR',
            [
                'key' => SampleFulfilment::META_DONE,
                'compare' => 'NOT EXISTS',
            ],
            [
                'key' => SampleFulfilment::META_DONE,
                'value' => '',
                'compare' => '=',
            ],
        ];
    }

    $result = \wc_get_orders($args);

    // `paginate` gives an object; anything else means the query fell back, and
    // an empty table beats a fatal.
    if (!\is_object($result) || !isset($result->orders)) {
        return ['orders' => [], 'total' => 0, 'pages' => 0];
    }

    return [
        'orders' => \array_filter((array) $result->orders, static fn($o): bool => $o instanceof \WC_Order),
        'total' => (int) ($result->total ?? 0),
        'pages' => (int) ($result->max_num_pages ?? 0),
    ];
}

/**
 * The panel's own URL with one argument changed.
 *
 * Keeps the search term while paging, and the page while searching, without
 * every link in the template rebuilding the whole query string.
 *
 * @param array<string,string|int|null> $args
 */
function panel_url(array $args = []): string
{
    $base = \wc_get_account_endpoint_url(ENDPOINT);
    $current = request();

    $query = \array_filter(
        \array_merge(
            [
                'mbfq' => $current['search'],
                'mbfall' => $current['outstanding'] ? null : 1,
                'mbfold' => $current['historic'] ? 1 : null,
                'mbfp' => $current['page'] > 1 ? $current['page'] : null,
            ],
            $args
        ),
        static fn($v): bool => null !== $v && '' !== $v
    );

    return $query ? \add_query_arg($query, $base) : $base;
}

/**
 * The customer, as one line.
 *
 * ⚠️ German customer data, shown to an external agency. That is covered by the
 * DPIA and NDA already in place for them, and it is the minimum the job needs:
 * you cannot post a parcel without a name and an address.
 */
function customer_line(\WC_Order $order): string
{
    /*
     * The German checkout asks for a salutation and stores it as `anrede`,
     * populated on every order. The agency writes it on the parcel, so it
     * belongs at the front of the name rather than nowhere.
     */
    $title = \trim((string) $order->get_meta('anrede'));
    $name = \trim($order->get_formatted_billing_full_name());

    if ('' === $name) {
        $name = \trim((string) $order->get_billing_company());
    }

    if ('' === $name) {
        return \__('Kein Name auf der Bestellung', 'granola');
    }

    return '' !== $title ? $title . ' ' . $name : $name;
}

/**
 * The company, where there is one.
 *
 * Its own line between the person and the street, which is how a German
 * address is written and how the label will be printed.
 */
function company_line(\WC_Order $order): string
{
    $company = \trim((string) $order->get_shipping_company());

    return '' !== $company ? $company : \trim((string) $order->get_billing_company());
}

/**
 * The shipping address, flattened for a table cell.
 */
function address_line(\WC_Order $order): string
{
    $address = $order->get_formatted_shipping_address();

    if ('' === (string) $address) {
        $address = $order->get_formatted_billing_address();
    }

    $parts = \preg_split('/\s*<br\s*\/?>\s*/i', (string) $address) ?: [];
    $parts = \array_values(\array_filter(\array_map('trim', $parts)));

    /*
     * Drop the name and company lines.
     *
     * WooCommerce's formatted address opens with the recipient and their
     * company, and this column already prints both above. Leaving them here
     * showed every name twice, which the agency reads past rather than
     * through.
     */
    $drop = [
        \trim($order->get_formatted_shipping_full_name()),
        \trim($order->get_formatted_billing_full_name()),
        \trim((string) $order->get_shipping_company()),
        \trim((string) $order->get_billing_company()),
    ];

    $drop = \array_filter($drop);

    $parts = \array_values(\array_filter(
        $parts,
        static fn(string $line): bool => !\in_array($line, $drop, true)
    ));

    return \implode(', ', $parts);
}

/**
 * What was ordered, as "3 x Enhanced Grain …" lines.
 *
 * @return array<int,string>
 */
function item_lines(\WC_Order $order): array
{
    $lines = [];

    foreach ($order->get_items() as $item) {
        $lines[] = \sprintf('%d &times; %s', (int) $item->get_quantity(), $item->get_name());
    }

    return $lines;
}
