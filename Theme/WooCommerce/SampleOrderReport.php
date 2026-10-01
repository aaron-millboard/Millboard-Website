<?php

namespace Theme\WooCommerce;

/**
 * "Sample ordering" inside WooCommerce Analytics.
 *
 * Aaron's ask, 2 Oct 2026. With no cost and no budget behind the sample tool,
 * the only remaining guard is being able to see the ordering: who is placing
 * how much, and whether anyone is well clear of the pack.
 *
 * WHY THIS IS ITS OWN PAGE RATHER THAN A FILTER ON AN EXISTING REPORT.
 * Analytics reads from WooCommerce's own lookup tables, which its importer
 * fills from orders and which know nothing about `_millboard_sample_*`. There
 * is no supported way to filter Orders or Products by meta the importer never
 * saw. So this registers a page in the Analytics nav and serves it from its
 * own endpoint, which is also why it is live rather than dependent on an
 * import having run.
 *
 * Everything it reports was written at checkout by the wc-account-sample-
 * ordering component, including the role and company, so a report of June
 * still reads correctly after somebody changes role or leaves.
 */
class SampleOrderReport
{
    public const PAGE_ID = 'millboard-sample-ordering';
    public const PATH = '/analytics/sample-ordering';

    public const REST_NAMESPACE = 'millboard/v1';
    public const REST_ROUTE = '/sample-orders';

    /** Order meta written at checkout. */
    public const META_FLAG = '_millboard_sample_order';
    public const META_LINES = '_millboard_sample_lines';
    public const META_UNITS = '_millboard_sample_units';
    public const META_ROLE = '_millboard_sample_role';
    public const META_STAFF = '_millboard_sample_staff';
    public const META_COMPANY = '_millboard_sample_company';

    public static function init(): void
    {
        \add_action('admin_menu', [__CLASS__, 'register_page']);
        \add_action('rest_api_init', [__CLASS__, 'register_rest']);
        \add_action('admin_enqueue_scripts', [__CLASS__, 'enqueue'], 20);
    }

    /**
     * Add the page to the Analytics menu.
     *
     * `parent` puts it inside Analytics rather than beside it, and `path` is
     * the client-side route the JS registers against.
     */
    public static function register_page(): void
    {
        if (!\function_exists('wc_admin_register_page')) {
            return;
        }

        \wc_admin_register_page([
            'id' => self::PAGE_ID,
            'title' => \__('Sample ordering', 'granola'),
            'parent' => 'woocommerce-analytics',
            'path' => self::PATH,
            'capability' => 'view_woocommerce_reports',
        ]);
    }

    // ------------------------------------------------------------------ assets

    /**
     * Load the report only on a WooCommerce Admin screen.
     *
     * The theme's shared admin bundle is enqueued everywhere and carries no
     * dependencies. This needs wc-components and friends, and has no business
     * loading on the post editor, so it is registered separately.
     */
    public static function enqueue(): void
    {
        if (!\function_exists('wc_admin_register_page')) {
            return;
        }

        $screen = \function_exists('get_current_screen') ? \get_current_screen() : null;

        if (!$screen || false === \strpos((string) $screen->id, 'woocommerce')) {
            return;
        }

        if (!\current_user_can('view_woocommerce_reports')) {
            return;
        }

        \add_filter(
            'granola/partial/wc-account-sample-ordering/enqueue_script_dependencies',
            [__CLASS__, 'script_dependencies']
        );

        /*
         * IN THE FOOTER, AND THIS IS NOT COSMETIC.
         *
         * The component helper enqueues in the HEAD by default. Declaring
         * wc-components as a dependency then drags WooCommerce's whole admin
         * script chain, wc-settings included, into the head with it, ahead of
         * where WooCommerce attaches the settings data. The app then boots
         * with window.wcSettings undefined and dies on "Cannot read
         * properties of undefined (reading 'admin')", which took out EVERY
         * Analytics screen, not only this one.
         *
         * WooCommerce prints its own admin scripts in the footer. Anything
         * joining that graph has to do the same.
         */
        \add_filter(
            'granola/partial/wc-account-sample-ordering/enqueue_script_in_footer',
            '__return_true'
        );

        \Granola\Component::enqueue_script_by_filename('wc-account-sample-ordering', 'analytics');

        \wp_localize_script('wc-account-sample-ordering-scripts', 'MB_SOF_REPORT', [
            'path' => self::PATH,
            'rest' => self::REST_NAMESPACE . self::REST_ROUTE,
        ]);
    }

    /**
     * @param mixed $deps
     * @return string[]
     */
    public static function script_dependencies($deps): array
    {
        return [
            'wp-hooks',
            'wp-element',
            'wp-i18n',
            'wp-api-fetch',
            'wc-components',
        ];
    }

    // ------------------------------------------------------------------- data

    public static function register_rest(): void
    {
        \register_rest_route(self::REST_NAMESPACE, self::REST_ROUTE, [
            'methods' => 'GET',
            'callback' => [__CLASS__, 'rest_report'],
            'permission_callback' => static fn(): bool => \current_user_can('view_woocommerce_reports'),
            'args' => [
                'after' => ['type' => 'string', 'required' => false],
                'before' => ['type' => 'string', 'required' => false],
            ],
        ]);
    }

    /**
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response|\WP_Error
     */
    public static function rest_report($request)
    {
        if (!\function_exists('wc_get_orders')) {
            return new \WP_Error('mb_no_woocommerce', 'WooCommerce is not available', ['status' => 500]);
        }

        $after = (string) $request->get_param('after');
        $before = (string) $request->get_param('before');

        // A sane default rather than every order ever: the last 90 days.
        if ('' === $after) {
            $after = \gmdate('Y-m-d', \strtotime('-90 days'));
        }

        return \rest_ensure_response(self::report($after, $before));
    }

    /**
     * Aggregate by company, which is the unit Aaron watches.
     *
     * @return array<string, mixed>
     */
    public static function report(string $after, string $before = ''): array
    {
        $args = [
            'limit' => -1,
            'type' => 'shop_order',
            'status' => \array_keys(\wc_get_order_statuses()),
            'meta_query' => [[
                'key' => self::META_FLAG,
                'value' => 'yes',
                'compare' => '=',
            ]],
            'return' => 'objects',
        ];

        if ('' !== $after) {
            $args['date_created'] = '' !== $before
                ? $after . '...' . $before
                : '>=' . $after;
        }

        $orders = \wc_get_orders($args);
        $rows = [];
        $totals = ['orders' => 0, 'lines' => 0, 'units' => 0];

        foreach ((array) $orders as $order) {
            if (!$order instanceof \WC_Order) {
                continue;
            }

            $company = (string) $order->get_meta(self::META_COMPANY);
            $role = (string) $order->get_meta(self::META_ROLE);
            $staff = 'yes' === (string) $order->get_meta(self::META_STAFF);
            $lines = (int) $order->get_meta(self::META_LINES);
            $units = (int) $order->get_meta(self::META_UNITS);

            if ('' === $company) {
                $name = \trim($order->get_billing_first_name() . ' ' . $order->get_billing_last_name());
                $company = '' !== $name ? $name : \__('(no company)', 'granola');
            }

            $key = \strtolower($company) . '|' . $role;

            if (!isset($rows[$key])) {
                $rows[$key] = [
                    'company' => $company,
                    'role' => $role,
                    'staff' => $staff,
                    'orders' => 0,
                    'lines' => 0,
                    'units' => 0,
                    'last' => '',
                ];
            }

            $rows[$key]['orders']++;
            $rows[$key]['lines'] += $lines;
            $rows[$key]['units'] += $units;

            $created = $order->get_date_created();
            $date = $created ? $created->date('Y-m-d') : '';

            if ($date > $rows[$key]['last']) {
                $rows[$key]['last'] = $date;
            }

            $totals['orders']++;
            $totals['lines'] += $lines;
            $totals['units'] += $units;
        }

        // Heaviest first: the whole point is spotting whoever is well clear.
        \usort($rows, static fn(array $a, array $b): int => $b['units'] <=> $a['units']);

        return [
            'rows' => \array_values($rows),
            'totals' => $totals,
            'range' => ['after' => $after, 'before' => $before],
        ];
    }
}
