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
 * The attribute holding a board's shade.
 *
 * WooCommerce calls it "Colour" and the redesign calls it shade; they are the
 * same thing. Named here so the order list is not the place someone has to
 * discover that.
 */
const SHADE_TAXONOMY = 'pa_colour';

/**
 * Sites the sample tool runs on, by site path.
 *
 * Aaron, 6 Oct 2026: "this entire sample ordering tab is only for the UK site."
 * Until now that was true only by accident -- the portal users were imported to
 * en-gb and nowhere else -- and nothing in the code said so. The capability is
 * granted per site on multisite, so a distributor on en-gb genuinely has no
 * role on en-us, but an administrator has capabilities everywhere: browsing
 * /en-us/my-account/ showed the tab, with a catalogue built from THAT site's
 * products.
 *
 * `/en-gb/commercial/` is deliberately absent. It is a separate site with its
 * own path, so matching the full path rather than a prefix keeps the commercial
 * clone out without a second condition.
 */
const SAMPLE_ORDERING_SITES = ['/en-gb/'];

/**
 * The per-site switch, so a market is turned on without a deploy.
 *
 * The docblock below has always said adding a market should be a setting, but
 * nothing hooked the filter, so until now it was a constant and a deploy. With
 * France and the US both wanted and Ireland behind them, that is three
 * production deploys for three `'/xx-xx/'` strings.
 *
 *   wp option update millboard_sample_ordering_enabled yes --url=<site>/fr-fr/
 *   wp option delete millboard_sample_ordering_enabled --url=<site>/fr-fr/
 *
 * en-gb stays in the constant rather than moving to an option: the UK must not
 * be switchable off by a stray option write, and a fresh clone keeps working
 * with no setup.
 */
const SAMPLE_ORDERING_OPTION = 'millboard_sample_ordering_enabled';

/**
 * Is the current site one the sample tool runs on?
 *
 * The constant is the floor and the option only ever ADDS a market. Nothing
 * here can take en-gb away.
 */
function is_sample_ordering_site(): bool
{
    // A single-site install has nothing to gate against, and the tests and any
    // local non-multisite copy should not be silently empty.
    if (!\is_multisite()) {
        return true;
    }

    $site = \get_blog_details(\get_current_blog_id());
    $path = $site ? \trailingslashit((string) $site->path) : '/';

    $sites = SAMPLE_ORDERING_SITES;

    if ('yes' === \get_option(SAMPLE_ORDERING_OPTION)) {
        $sites[] = $path;
    }

    return \in_array($path, \apply_filters('millboard_sample_ordering_sites', $sites), true);
}

/**
 * May this user reach the Canto brand library?
 *
 * Capability, not role name and not email domain. The previous test was "has
 * a millboard.com address, or can edit_posts", which let 22 people in and had
 * no way to describe a distributor or an installer -- both of whom need these
 * tools and are not staff. See Theme\Accounts\Roles for the map.
 */
function can_view_brand_assets(?int $user_id = null): bool
{
    if (!user_can_mb(\Theme\Accounts\Roles::CAP_BRAND_ASSETS, $user_id)) {
        return false;
    }

    return user_in_allow_list('millboard_asset_viewers', $user_id, false);
}

/**
 * Is this person on a named allow-list?
 *
 * Three panels are now gated this way, so the check lives once. Each list is
 * an option holding e-mail addresses, because that is what whoever maintains
 * it will recognise, and it can be changed without a deploy:
 *
 *   wp option update <option> --format=json '["a@millboard.com"]' --url=<site>/en-gb/
 *
 * ⚠ `$when_empty` is NOT the same for every list, on purpose:
 *
 *   millboard_pos_orderers    TRUE  - a working feature being narrowed, so an
 *                                     empty list means everyone and deploying
 *                                     the gate changed nothing for anyone.
 *   millboard_asset_viewers   FALSE - switched back on for a named pair while
 *   millboard_projects_viewers        the thing behind it is unfinished, so a
 *                                     mistake must hide the tab rather than
 *                                     show it to all 99 staff.
 */
function user_in_allow_list(string $option, ?int $user_id, bool $when_empty): bool
{
    $user_id = $user_id ?: \get_current_user_id();

    if (!$user_id) {
        return false;
    }

    $allowed = \array_filter(\array_map(
        static fn($email): string => \strtolower(\trim((string) $email)),
        (array) \get_option($option, [])
    ));

    if (!$allowed) {
        return $when_empty;
    }

    $user = \get_userdata($user_id);

    return $user instanceof \WP_User
        && \in_array(\strtolower($user->user_email), $allowed, true);
}

/**
 * May this person see the Projects tab?
 *
 * It links somewhere outside the site, so there is no capability and no
 * endpoint to protect: the list IS the permission. It also does not render
 * at all until `millboard_projects_url` is set, because a menu item that
 * goes nowhere is worse than no menu item.
 */
function can_view_projects(?int $user_id = null): bool
{
    return '' !== projects_url()
        && user_in_allow_list('millboard_projects_viewers', $user_id, false);
}

/**
 * Where Projects points. Empty until someone sets it.
 */
function projects_url(): string
{
    $url = \trim((string) \get_option('millboard_projects_url', ''));

    return \esc_url_raw($url);
}

/**
 * The account menu, split into the groups the design asks for.
 *
 * "Your account" is whatever WooCommerce offers. "Tools" is the three panels
 * this theme adds on top. Membership is decided by naming the tools rather
 * than the account items, so anything WooCommerce introduces later lands in
 * the first group, which is where an unrecognised endpoint belongs.
 *
 * ⚠️ The caller shows the group HEADINGS only when there is more than one
 * group, and this returns a single unlabelled group when Tools is empty. A
 * customer therefore sees one plain list of five, exactly as before. That is
 * not only cosmetic: headings on a one-group menu would advertise that other
 * groups exist for other people.
 *
 * Order within each group is WooCommerce's, which hooks.php has already set.
 *
 * @param array<string,string> $endpoints
 * @return array<int,array{label: string, items: array<string,string>}>
 */
function nav_groups(array $endpoints): array
{
    $tools = \array_intersect_key(
        $endpoints,
        \array_flip([ENDPOINT_BRAND_ASSETS, ENDPOINT_SAMPLE_ORDERING, 'projects'])
    );

    if (!$tools) {
        return [['label' => '', 'items' => $endpoints]];
    }

    return [
        ['label' => \__('Your account', 'granola'), 'items' => \array_diff_key($endpoints, $tools)],
        ['label' => \__('Tools', 'granola'), 'items' => $tools],
    ];
}

/**
 * May this user send samples?
 *
 * Site first, then capability. hooks.php points the widget's own
 * `mb_sof_user_can_order` filter at this function and the panel is rendered
 * behind it, so SampleOrderCheckout's gate and the widget's submit handler
 * both inherit the restriction: one place decides, which is what the component
 * already promised by calling itself "one gate, not two".
 */
function can_order_samples(?int $user_id = null): bool
{
    return is_sample_ordering_site() && user_can_mb(\Theme\Accounts\Roles::CAP_ORDER_SAMPLES, $user_id);
}

/**
 * May this user order POS and marketing stock?
 *
 * Distributors and staff hold the capability: an installer has no point of
 * sale to stock, and POS comes out of a distributor's marketing budget.
 *
 * STAFF are narrowed further by the `millboard_pos_orderers` option, a list
 * of e-mail addresses, because the teaser boards, presenter packs and
 * literature are real stock that gets picked and shipped and not every member
 * of the team should be able to send one out.
 *
 * An absent or empty option means every member of staff, which is exactly
 * today's behaviour, so this changes nothing until the list is filled in.
 * Fail-open is deliberate and is the opposite of the choice made for the
 * per-SKU limits: an empty limit tier falls back to a TIGHTER number, whereas
 * an empty allow-list here would lock the whole team out of a working feature
 * before anyone has supplied the names. Set it without a deploy:
 *
 *   wp option update millboard_pos_orderers --format=json
 *     '["a@millboard.com","b@millboard.com"]' --url=<site>/en-gb/
 *
 * Distributors are deliberately untouched: POS comes out of their own
 * marketing budget, so it is not ours to ration.
 */
function can_order_pos(?int $user_id = null): bool
{
    if (!user_can_mb(\Theme\Accounts\Roles::CAP_ORDER_POS, $user_id)) {
        return false;
    }

    $user_id = $user_id ?: \get_current_user_id();

    if (!\Theme\Accounts\Roles::is_staff($user_id)) {
        return true;
    }

    return user_in_allow_list('millboard_pos_orderers', $user_id, true);
}

/**
 * One capability check, so every panel asks the question the same way.
 */
function user_can_mb(string $capability, ?int $user_id = null): bool
{
    $user_id = $user_id ?: \get_current_user_id();

    if (!$user_id) {
        return false;
    }

    return (bool) \apply_filters(
        'millboard/account/user_can',
        \user_can($user_id, $capability),
        $capability,
        $user_id
    );
}

/**
 * Is this user Millboard staff rather than a partner?
 *
 * Wording only: the "Millboard team" badge should not sit on a distributor's
 * screen when they are not Millboard. Access is decided by the capabilities
 * above, never by this.
 */
function is_staff(?int $user_id = null): bool
{
    return \Theme\Accounts\Roles::is_staff($user_id);
}

/**
 * Is the IT team's sample ordering widget installed?
 *
 * It ships as six files required from the child theme's functions.php and
 * defines MB_SOF_VERSION on load. Detected rather than assumed, because the
 * account section has to work both before and after it lands.
 */
function sample_widget_installed(): bool
{
    return \defined('MB_SOF_VERSION') || \shortcode_exists(get_sample_shortcode());
}

/**
 * The widget's shortcode tag.
 */
function get_sample_shortcode(): string
{
    return (string) \apply_filters('millboard/account/sample_ordering_shortcode', 'millboard_sample_ordering');
}

/**
 * The endpoint slug the sample panel lives at.
 *
 * The widget declares its own as MB_SOF_ENDPOINT, defaulting to the same
 * `sample-ordering` this component uses. Reading the constant when it exists
 * means both halves agree on one URL instead of racing to register two.
 */
function get_sample_endpoint(): string
{
    return \defined('MB_SOF_ENDPOINT') ? (string) \MB_SOF_ENDPOINT : ENDPOINT_SAMPLE_ORDERING;
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
 * The board shades in an order, as swatches, at most three.
 *
 * What a customer actually recognises an order by is the colour of the board,
 * which the one-line summary can only spell out in words. Three chips at the
 * head of the row say "the grey one" faster than any sentence.
 *
 * ⚠️ The swatch is the TERM's own image, not a hex value. The redesign's
 * prototype used a hand-written table of twelve approximate hex tones, which
 * would be a second source of truth for product colour and would drift from
 * the real boards the first time one is retouched. The images are already in
 * `pa_colour` term meta and are what the shop renders everywhere else.
 *
 * A shade with no swatch image is skipped rather than drawn as an empty box.
 *
 * @return array<int,array{name: string, src: string}>
 */
function order_shade_swatches(\WC_Order $order, int $limit = 3): array
{
    $found = [];

    foreach ($order->get_items() as $item) {
        if (!$item instanceof \WC_Order_Item_Product) {
            continue;
        }

        $product = $item->get_product();

        if (!$product instanceof \WC_Product) {
            continue;
        }

        $term = null;

        // A variation records the shade it was bought in on itself, as a slug.
        // Reading the parent's terms instead would give whichever colour
        // happens to be first, so a Jarrah order could show an Ashwood chip.
        if ($product instanceof \WC_Product_Variation) {
            $slug = (string) ($product->get_attributes()[SHADE_TAXONOMY] ?? '');
            $term = '' !== $slug ? \get_term_by('slug', $slug, SHADE_TAXONOMY) : null;
        }

        if (!$term instanceof \WP_Term) {
            $terms = \wc_get_product_terms(
                $product->get_parent_id() ?: $product->get_id(),
                SHADE_TAXONOMY,
                ['fields' => 'all']
            );

            $term = \is_array($terms) && $terms ? $terms[0] : null;
        }

        if (!$term instanceof \WP_Term || isset($found[$term->term_id])) {
            continue;
        }

        $image = (int) \get_term_meta($term->term_id, 'image', true);
        $src = $image ? (string) \wp_get_attachment_image_url($image, 'thumbnail') : '';

        if ('' === $src) {
            continue;
        }

        $found[$term->term_id] = ['name' => $term->name, 'src' => $src];

        if (\count($found) >= $limit) {
            break;
        }
    }

    return \array_values($found);
}

/**
 * Those swatches as markup, so both order lists stay one line each.
 *
 * Decorative on purpose. The summary beside it already names the shade in
 * words ("Enhanced Grain 126mm Antique Oak and more"), so announcing the chips
 * as well would read the same colour out twice. The title attribute is there
 * for a sighted pointer user, not for assistive technology.
 */
function order_shade_chips(\WC_Order $order, int $limit = 3): string
{
    $swatches = order_shade_swatches($order, $limit);

    if (!$swatches) {
        return '';
    }

    $out = '<span class="mb-account-orders__chips" aria-hidden="true">';

    foreach ($swatches as $swatch) {
        // An <img>, not a background-image. Both templates pass this through
        // wp_kses_post, which runs inline styles through safecss_filter_attr
        // and is free to drop a url(), so a background would have risked
        // rendering three empty boxes. An img needs no inline style at all.
        $out .= \sprintf(
            '<img class="mb-account-orders__chip" src="%s" alt="" title="%s" width="44" height="44" loading="lazy" />',
            \esc_url($swatch['src']),
            \esc_attr($swatch['name'])
        );
    }

    return $out . '</span>';
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
