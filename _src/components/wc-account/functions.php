<?php

/**
 * My Account -- data layer.
 *
 * Everything the account templates need that is not markup: which panels exist,
 * who is allowed to see them, and how a WooCommerce order status becomes the
 * four-step progress bar in the design.
 */

namespace Granola\Components\WC_Account;

/**
 * The two custom panels, keyed by endpoint slug. Neither is a WooCommerce
 * endpoint out of the box, so hooks.php registers them and this component
 * renders them from partials.
 */
const ENDPOINT_BRAND_ASSETS = 'brand-assets';
const ENDPOINT_SAMPLE_ORDERING = 'sample-ordering';

/**
 * Is the current user a member of the Millboard team?
 *
 * The design badges Brand assets as "Millboard team", and Sample ordering sends
 * samples out with pricing bypassed, so both are staff surfaces rather than
 * customer ones. Two tests, because neither alone is enough: a rep signing in
 * with their work address is team even on a plain customer account, and an
 * editor or shop manager is team whatever address they used.
 *
 * Filterable, so the rule changes in one place if the definition ever moves.
 */
function is_team_member(?int $user_id = null): bool
{
    $user_id = $user_id ?: \get_current_user_id();

    if (!$user_id) {
        return false;
    }

    $user = \get_userdata($user_id);
    $is_team = false;

    if ($user instanceof \WP_User) {
        $domain = \strtolower((string) \substr((string) \strrchr($user->user_email, '@'), 1));
        $is_team = 'millboard.com' === $domain || \user_can($user, 'edit_posts');
    }

    return (bool) \apply_filters('millboard/account/is_team_member', $is_team, $user_id);
}

/**
 * What a non-team user sees if they type a team panel's URL.
 *
 * The navigation never links here, so this is the answer to a guessed address
 * rather than anything a customer meets in normal use.
 */
function render_no_access(): string
{
    return '<div class="mb-account-empty">'
        . '<span class="mb-account-empty__rule" aria-hidden="true"></span>'
        . '<h2 class="mb-account-empty__title">' . \esc_html__('Not available on this account', 'granola') . '</h2>'
        . '<p class="mb-account-empty__body">' . \esc_html__('This part of the account is for the Millboard team. If you think you should have it, let us know and we will put it right.', 'granola') . '</p>'
        . '</div>';
}

/**
 * The four progress steps in the design, resolved against a real order.
 *
 * WooCommerce has no dispatch state and this store runs no tracking plugin, so
 * Dispatched and Delivered both hang off `completed`. That is the honest
 * mapping rather than an invented one: the bar starts telling the truth about
 * dispatch the day there is dispatch data to read, and until then it never
 * claims a step that has not happened.
 *
 * Cancelled, refunded, failed and unpaid orders get no bar at all. A progress
 * track implies progress, and those orders are not progressing.
 *
 * @return array<int, array{label: string, when: string, reached: bool}>
 */
function get_order_steps(\WC_Order $order): array
{
    $status = $order->get_status();

    if (\in_array($status, ['cancelled', 'refunded', 'failed', 'pending'], true)) {
        return [];
    }

    $reached = 1;

    if (\in_array($status, ['processing', 'on-hold', 'completed'], true)) {
        $reached = 2;
    }

    if ('completed' === $status) {
        $reached = 4;
    }

    $format = static function ($date): string {
        return $date ? \wc_format_datetime($date, 'j F Y') : '';
    };

    $created = $format($order->get_date_created());
    $paid = $format($order->get_date_paid()) ?: $created;
    $completed = $format($order->get_date_completed());

    $steps = [
        ['label' => \__('Placed', 'granola'), 'when' => $created],
        ['label' => \__('Processing', 'granola'), 'when' => $paid],
        ['label' => \__('Dispatched', 'granola'), 'when' => $completed],
        ['label' => \__('Delivered', 'granola'), 'when' => $completed],
    ];

    foreach ($steps as $index => $step) {
        $is_reached = ($index + 1) <= $reached;

        $steps[$index]['reached'] = $is_reached;
        $steps[$index]['when'] = $is_reached ? $step['when'] : \__('Pending', 'granola');
    }

    return $steps;
}

/**
 * Order statuses that are still moving. The dashboard tracker and the Open tab
 * both read this, so there is one definition of "live" rather than two.
 *
 * @return string[]
 */
function get_open_statuses(): array
{
    return (array) \apply_filters('millboard/account/open_statuses', ['pending', 'processing', 'on-hold']);
}

/**
 * Is this order finished, in the sense the status pill means?
 *
 * The design draws an olive border on anything still live and a plain hairline
 * on anything closed, so the list and the detail read the same test.
 */
function is_closed(\WC_Order $order): bool
{
    return !\in_array($order->get_status(), get_open_statuses(), true);
}

/**
 * A one-line summary of an order's contents: "3 items | Sample pack".
 *
 * The design shows a count and then something human. The something human is the
 * first line item, which is what a customer recognises the order by.
 */
function get_order_summary(\WC_Order $order): string
{
    $count = $order->get_item_count() - $order->get_item_count_refunded();

    /* translators: %s: number of items in the order. */
    $summary = \sprintf(\_n('%s item', '%s items', $count, 'granola'), \number_format_i18n($count));

    $items = $order->get_items();
    $first = \is_array($items) && $items ? \reset($items) : false;

    if ($first) {
        $name = $first->get_name();

        if ($count > 1) {
            /* translators: %s: name of the first product in the order. */
            $name = \sprintf(\__('%s and more', 'granola'), $name);
        }

        $summary .= ' &middot; ' . $name;
    }

    return $summary;
}

/**
 * Every order status a customer can see, drafts excluded.
 *
 * @return string[]
 */
function get_visible_statuses(): array
{
    $statuses = \array_map(
        static fn(string $status): string => \str_replace('wc-', '', $status),
        \array_keys(\wc_get_order_statuses())
    );

    return \array_values(\array_diff($statuses, ['checkout-draft']));
}

/**
 * How many orders the customer has in each tab, so the list can say "3 of 12".
 *
 * Two counting queries rather than one query per status, and `paginate` so
 * WooCommerce runs a COUNT instead of hydrating the rows. An account with
 * several hundred orders is ordinary here, and a footer line does not justify
 * pulling every one of them into memory to call count() on the array.
 *
 * @return array{all: int, open: int, closed: int}
 */
function get_order_counts(int $user_id): array
{
    $counts = ['all' => 0, 'open' => 0, 'closed' => 0];

    if (!$user_id) {
        return $counts;
    }

    $open = get_open_statuses();
    $closed = \array_values(\array_diff(get_visible_statuses(), $open));

    $total = static function (array $statuses) use ($user_id): int {
        if (!$statuses) {
            return 0;
        }

        $result = \wc_get_orders([
            'customer_id' => $user_id,
            'status' => $statuses,
            'limit' => 1,
            'paginate' => true,
            'return' => 'ids',
        ]);

        return isset($result->total) ? (int) $result->total : 0;
    };

    $counts['open'] = $total($open);
    $counts['closed'] = $total($closed);
    $counts['all'] = $counts['open'] + $counts['closed'];

    return $counts;
}

/**
 * The tabs above the orders list. Plain links, so the list works without JS and
 * every filter is a URL someone can bookmark or paste to support.
 *
 * @return array<int, array{key: string, label: string, url: string, current: bool}>
 */
function get_order_tabs(string $current): array
{
    $base = \wc_get_endpoint_url('orders', '', \wc_get_page_permalink('myaccount'));

    $tabs = [
        'all' => \__('All', 'granola'),
        'open' => \__('Open', 'granola'),
        'closed' => \__('Delivered', 'granola'),
    ];

    $out = [];

    foreach ($tabs as $key => $label) {
        $out[] = [
            'key' => $key,
            'label' => $label,
            'url' => 'all' === $key ? $base : \add_query_arg('show', $key, $base),
            'current' => $key === $current,
        ];
    }

    return $out;
}

/**
 * Which orders tab is showing. Reads the query string, defaults to all.
 */
function get_current_order_tab(): string
{
    // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only list filter.
    $show = isset($_GET['show']) ? \sanitize_key(\wp_unslash($_GET['show'])) : 'all';

    return \in_array($show, ['all', 'open', 'closed'], true) ? $show : 'all';
}

/**
 * The customer's most recent order that is still moving, for the dashboard
 * tracker. Null when everything they have is closed, which is the case the
 * design handles by simply not drawing the panel.
 */
function get_trackable_order(int $user_id): ?\WC_Order
{
    if (!$user_id) {
        return null;
    }

    $orders = \wc_get_orders([
        'customer_id' => $user_id,
        'status' => get_open_statuses(),
        'limit' => 1,
        'orderby' => 'date',
        'order' => 'DESC',
    ]);

    $order = \is_array($orders) && $orders ? \reset($orders) : null;

    return $order instanceof \WC_Order ? $order : null;
}

/**
 * The marketing preference shown on Account details.
 *
 * Stored per user rather than per order: the checkout consent fields in
 * Theme\WooCommerce\ConsentFields record what was agreed at the moment of an
 * order, which is an evidence trail and must not be rewritten from here.
 */
const MARKETING_META_KEY = 'millboard_marketing_opt_in';

function is_marketing_opted_in(int $user_id): bool
{
    return 'yes' === \get_user_meta($user_id, MARKETING_META_KEY, true);
}
