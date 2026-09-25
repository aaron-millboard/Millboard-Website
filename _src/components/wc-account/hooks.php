<?php

/**
 * My Account -- wiring.
 *
 * Registers the two custom panels, arranges the navigation in the order the
 * design draws it, filters the orders list by the chosen tab, and saves the
 * marketing preference from Account details.
 */

namespace Granola\Components\WC_Account;

/**
 * Register the custom endpoints so /my-account/brand-assets/ resolves.
 *
 * Rewrite rules are only flushed when the registered set actually changes, so
 * this costs nothing on a normal request but still survives a deploy that adds
 * a panel. Flushing on every load would rewrite the option on all 13,000 hits.
 */
\add_action('init', function (): void {
    $endpoints = [ENDPOINT_BRAND_ASSETS, get_sample_endpoint()];

    foreach ($endpoints as $endpoint) {
        \add_rewrite_endpoint($endpoint, EP_PAGES);
    }

    $signature = \md5(\implode('|', $endpoints));

    if (\get_option('millboard_account_endpoints') !== $signature) {
        \flush_rewrite_rules(false);
        \update_option('millboard_account_endpoints', $signature, false);
    }
});

/**
 * Let WooCommerce treat the custom endpoints as account queries.
 */
\add_filter('woocommerce_get_query_vars', function (array $vars): array {
    $vars[ENDPOINT_BRAND_ASSETS] = ENDPOINT_BRAND_ASSETS;
    $vars[get_sample_endpoint()] = get_sample_endpoint();

    return $vars;
});

/**
 * One gate, not two.
 *
 * The widget has its own `mb_sof_user_can_order` filter deciding who sees its
 * tab and may submit. Pointing it at the same capability this component uses
 * means access is decided in one place, and their handover's first blocking
 * question is answered.
 */
\add_filter('mb_sof_user_can_order', fn(): bool => can_order_samples());

/**
 * The navigation, in the design's order.
 *
 * Downloads is dropped: nothing on this store is a downloadable product, so the
 * panel is always empty. Payment methods is left to WooCommerce, which removes
 * it by itself when no gateway supports saving a card, so it appears on staging
 * and production and stays hidden on a local install with no gateway keys.
 */
\add_filter('woocommerce_account_menu_items', function (array $items): array {
    unset($items['downloads']);

    $logout = $items['customer-logout'] ?? null;
    unset($items['customer-logout']);

    $sample = get_sample_endpoint();

    // Each panel behind its own capability, so a distributor and an installer
    // can differ from each other and from staff.
    if (can_view_brand_assets()) {
        $items[ENDPOINT_BRAND_ASSETS] = \__('Brand assets', 'granola');
    } else {
        unset($items[ENDPOINT_BRAND_ASSETS]);
    }

    if (can_order_samples()) {
        // The widget adds its own item under this same key, labelled from
        // MB_SOF_LABEL ("Sample Ordering"). Setting it here rather than only
        // when absent means one item either way, with the design's sentence
        // case rather than the widget's title case.
        $items[$sample] = \__('Sample ordering', 'granola');
    } else {
        // Not permitted: make sure the widget's own item cannot survive.
        unset($items[$sample]);
    }

    $order = [
        'dashboard',
        'orders',
        'edit-address',
        'payment-methods',
        'edit-account',
        ENDPOINT_BRAND_ASSETS,
        $sample,
    ];

    $sorted = [];

    foreach ($order as $key) {
        if (isset($items[$key])) {
            $sorted[$key] = $items[$key];
            unset($items[$key]);
        }
    }

    // Anything a plugin added that is not in the design keeps its place at the
    // end rather than disappearing.
    $sorted = \array_merge($sorted, $items);

    if (null !== $logout) {
        $sorted['customer-logout'] = $logout;
    }

    return $sorted;
}, 20);

/**
 * Render the custom panels.
 *
 * Both re-check the capability. The menu filter already hides them, but an
 * endpoint is a URL and a URL can be typed, so the gate has to live on the
 * thing being protected rather than on the link to it.
 */
\add_action('woocommerce_account_' . ENDPOINT_BRAND_ASSETS . '_endpoint', function (): void {
    echo can_view_brand_assets()
        ? \Granola\Component::get('wc-account-brand-assets')
        : render_no_access();
});

/**
 * The sample panel.
 *
 * The widget registers its own callback on this action to print the form
 * bare. That is cleared and the form rendered inside this component's chrome
 * instead, because the widget deliberately dropped its own <h1> on the
 * assumption that WooCommerce prints a tab heading -- and here it does not,
 * every panel prints its own. Left alone the tab would arrive headingless.
 *
 * Done on wp_loaded, after every plugin and the theme have registered and
 * before anything renders. Clearing from inside the action would be too late:
 * priority 10 has already printed by the time a later priority runs.
 */
\add_action('wp_loaded', function (): void {
    $hook = 'woocommerce_account_' . get_sample_endpoint() . '_endpoint';

    \remove_all_actions($hook);

    \add_action($hook, function (): void {
        echo can_order_samples()
            ? \Granola\Component::get('wc-account-sample-ordering')
            : render_no_access();
    });
});

/**
 * Give the custom panels a page title, so the browser tab and any breadcrumb
 * read correctly rather than falling back to the bare "My account".
 */
\add_filter('woocommerce_endpoint_' . ENDPOINT_BRAND_ASSETS . '_title', fn(): string => \__('Brand assets', 'granola'));
\add_filter('woocommerce_endpoint_' . ENDPOINT_SAMPLE_ORDERING . '_title', fn(): string => \__('Sample ordering', 'granola'));

/**
 * Filter the orders list to the chosen tab.
 *
 * WooCommerce builds the query in its own shortcode, so the tab is applied here
 * rather than in the template. Leaving the status key alone for "all" keeps
 * WooCommerce's own default, which already excludes drafts and failed payments.
 */
\add_filter('woocommerce_my_account_my_orders_query', function (array $args): array {
    $tab = get_current_order_tab();

    if ('open' === $tab) {
        $args['status'] = get_open_statuses();
    }

    if ('closed' === $tab) {
        $args['status'] = \array_values(\array_diff(get_visible_statuses(), get_open_statuses()));
    }

    return $args;
});

/**
 * The account draws its own masthead, so the default page header is dropped.
 *
 * site-main injects that header itself when the content has no header block,
 * rather than it being a block on the page, so this filters site-main's args
 * after its own filter_args has run instead of filtering a block render.
 *
 * Scoped hard to the account page: every other page keeps its header.
 */
\add_filter('granola/component/site-main', function (?array $args): ?array {
    if (!\is_array($args)) {
        return $args;
    }

    if (!\function_exists('is_account_page') || !\is_account_page()) {
        return $args;
    }

    unset($args['header']);

    return $args;
}, 20);

/**
 * Body classes, so the account can escape the default block container.
 *
 * The shell sets its own 1440px measure and the sign-in screen is full bleed,
 * but `.blocks > *` caps every child at the standard content width. Two
 * classes rather than one because the two states want different things.
 */
\add_filter('body_class', function (array $classes): array {
    if (!\function_exists('is_account_page') || !\is_account_page()) {
        return $classes;
    }

    $classes[] = 'is-mb-account';
    $classes[] = \is_user_logged_in() ? 'is-mb-account-panel' : 'is-mb-account-auth';

    return $classes;
});

/**
 * Save the marketing preference from Account details.
 *
 * An unchecked checkbox sends nothing, so the absence of the field is a
 * deliberate "no" rather than a missing value. The nonce is WooCommerce's own,
 * already verified by save_account_details before this action fires.
 */
\add_action('woocommerce_save_account_details', function (int $user_id): void {
    // phpcs:ignore WordPress.Security.NonceVerification.Missing -- verified by WooCommerce before this action.
    $opted_in = !empty($_POST['millboard_marketing_opt_in']);

    \update_user_meta($user_id, MARKETING_META_KEY, $opted_in ? 'yes' : 'no');
});

/**
 * Registration sends a generated password by email by default, which leaves the
 * design's password field with nothing to do. When a password field is posted,
 * honour it.
 */
\add_filter('woocommerce_new_customer_data', function (array $data): array {
    // phpcs:ignore WordPress.Security.NonceVerification.Missing -- verified by WooCommerce's register handler.
    if (!empty($_POST['password'])) {
        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- passwords are hashed, not sanitised.
        $data['user_pass'] = (string) \wp_unslash($_POST['password']);
    }

    return $data;
});

/**
 * Keep what the registration form collected.
 *
 * The design asks for a first and last name and a marketing preference, none of
 * which WooCommerce stores on its own. The name is written to both the WordPress
 * profile and the billing fields, so checkout prefills and the dashboard has a
 * name to greet them by.
 */
\add_action('woocommerce_created_customer', function (int $customer_id): void {
    // phpcs:ignore WordPress.Security.NonceVerification.Missing -- verified by WooCommerce's register handler.
    $post = \wp_unslash($_POST);

    $first = isset($post['billing_first_name']) ? \sanitize_text_field($post['billing_first_name']) : '';
    $last = isset($post['billing_last_name']) ? \sanitize_text_field($post['billing_last_name']) : '';

    if ($first || $last) {
        $update = [
            'ID' => $customer_id,
            'first_name' => $first,
            'last_name' => $last,
        ];

        $full_name = \trim($first . ' ' . $last);

        // Only set a display name when there is one to set; passing an empty
        // string here would replace the generated one with nothing.
        if ('' !== $full_name) {
            $update['display_name'] = $full_name;
        }

        \wp_update_user($update);

        \update_user_meta($customer_id, 'billing_first_name', $first);
        \update_user_meta($customer_id, 'billing_last_name', $last);
        \update_user_meta($customer_id, 'shipping_first_name', $first);
        \update_user_meta($customer_id, 'shipping_last_name', $last);
    }

    \update_user_meta($customer_id, MARKETING_META_KEY, empty($post['millboard_marketing_opt_in']) ? 'no' : 'yes');
});
