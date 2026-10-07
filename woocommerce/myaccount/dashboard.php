<?php

/**
 * My Account dashboard.
 *
 * Overridden from WooCommerce to match the 2026 account design: a greeting,
 * three quick actions, a tracker for whatever order is still moving, the last
 * three orders, and the two summary columns.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 4.4.0
 */

defined('ABSPATH') || exit;

use function Granola\Components\WC_Account\get_order_steps;
use function Granola\Components\WC_Account\get_order_summary;
use function Granola\Components\WC_Account\get_trackable_order;
use function Granola\Components\WC_Account\get_visible_statuses;
use function Granola\Components\WC_Account\is_closed;

$user_id = get_current_user_id();
$customer = new WC_Customer($user_id);

$orders_url = wc_get_endpoint_url('orders', '', wc_get_page_permalink('myaccount'));
$addresses_url = wc_get_endpoint_url('edit-address', '', wc_get_page_permalink('myaccount'));
$details_url = wc_get_endpoint_url('edit-account', '', wc_get_page_permalink('myaccount'));

// Reuse the header's own samples link so the two never drift apart.
$samples_link = get_field('header_call_to_action_1', 'option');
$samples_url = is_array($samples_link) && !empty($samples_link['url'])
    ? $samples_link['url']
    : wc_get_page_permalink('shop');

$recent_orders = wc_get_orders([
    'customer_id' => $user_id,
    'limit' => 3,
    'orderby' => 'date',
    'order' => 'DESC',
    'status' => get_visible_statuses(),
]);

$tracked = get_trackable_order($user_id);

$quick_actions = [
    [
        'label' => __('Order samples', 'granola'),
        'note' => __('Feel the grain before you commit.', 'granola'),
        'url' => $samples_url,
    ],
    [
        'label' => __('Track an order', 'granola'),
        'note' => __('See where your boards are today.', 'granola'),
        'url' => $orders_url,
    ],
    [
        'label' => __('Account details', 'granola'),
        'note' => __('Name, email and password.', 'granola'),
        'url' => $details_url,
    ],
];

?>

<div class="mb-account-dash">

    <h2 class="mb-account-dash__greeting">
        <?php
        /* translators: %s: the customer's first name. */
        printf(esc_html__('Hello, %s', 'granola'), esc_html($customer->get_first_name() ?: $customer->get_display_name()));
        ?>
    </h2>

    <p class="mb-account-dash__intro">
        <?php esc_html_e('Everything for your Millboard projects in one place: recent orders, the addresses we deliver to, and the details we hold for you.', 'granola'); ?>
    </p>

    <div class="mb-account-dash__actions">
        <?php foreach ($quick_actions as $action) : ?>
            <a class="mb-account-dash__action" href="<?php echo esc_url($action['url']); ?>">
                <span class="mb-account-dash__action-head">
                    <span class="mb-account-dash__action-label"><?php echo esc_html($action['label']); ?></span>
                    <span class="mb-account-dash__action-arrow" aria-hidden="true">&rarr;</span>
                </span>
                <span class="mb-account-dash__action-note"><?php echo esc_html($action['note']); ?></span>
            </a>
        <?php endforeach; ?>
    </div>

    <?php
    // The tracker only appears when something is actually in progress. An
    // inert bar on a customer with nothing outstanding is furniture.
    if ($tracked instanceof WC_Order) :
        $steps = get_order_steps($tracked);
        ?>
        <section class="mb-account-dash__tracker">
            <div class="mb-account-dash__tracker-head">
                <h3 class="mb-account-dash__tracker-title">
                    <?php
                    /* translators: %s: order number. */
                    printf(esc_html__('%s in progress', 'granola'), '#' . esc_html($tracked->get_order_number()));
                    ?>
                </h3>
                <a class="mb-account-link" href="<?php echo esc_url($tracked->get_view_order_url()); ?>">
                    <?php esc_html_e('Order detail', 'granola'); ?>
                </a>
            </div>

            <p class="mb-account-dash__tracker-meta">
                <?php
                echo wp_kses_post(get_order_summary($tracked));
                echo ' &middot; ';
                /* translators: %s: the date the order was placed. */
                printf(esc_html__('placed %s', 'granola'), esc_html(wc_format_datetime($tracked->get_date_created(), 'j F Y')));
                ?>
            </p>

            <?php if ($steps) : ?>
                <div class="mb-account-steps">
                    <?php foreach ($steps as $step) : ?>
                        <div class="mb-account-steps__step<?php echo $step['reached'] ? ' is-reached' : ''; ?>">
                            <span class="mb-account-steps__dot" aria-hidden="true"></span>
                            <div class="mb-account-steps__label"><?php echo esc_html($step['label']); ?></div>
                            <div class="mb-account-steps__when"><?php echo esc_html($step['when']); ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    <?php endif; ?>

    <?php if ($recent_orders) : ?>
        <div class="mb-account-panel__head">
            <h3 class="mb-account-dash__tracker-title"><?php esc_html_e('Recent orders', 'granola'); ?></h3>
            <a class="mb-account-link" href="<?php echo esc_url($orders_url); ?>">
                <?php esc_html_e('View all orders', 'granola'); ?>
            </a>
        </div>

        <div class="mb-account-orders">
            <div class="mb-account-orders__list">
                <?php foreach ($recent_orders as $order) : ?>
                    <a class="mb-account-orders__row" href="<?php echo esc_url($order->get_view_order_url()); ?>">
                        <span class="mb-account-orders__id">#<?php echo esc_html($order->get_order_number()); ?></span>
                        <span class="mb-account-orders__date"><?php echo esc_html(wc_format_datetime($order->get_date_created(), 'j F Y')); ?></span>
                        <span class="mb-account-orders__summary"><?php echo wp_kses_post(get_order_summary($order)); ?></span>
                        <span class="mb-account-orders__status">
                            <span class="mb-account-pill<?php echo is_closed($order) ? ' mb-account-pill--closed' : ''; ?>">
                                <?php echo esc_html(wc_get_order_status_name($order->get_status())); ?>
                            </span>
                        </span>
                        <span class="mb-account-orders__total"><?php echo wp_kses_post($order->get_formatted_order_total()); ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <div class="mb-account-dash__columns">
        <div>
            <h3 class="mb-account-section"><?php esc_html_e('Delivering to', 'granola'); ?></h3>

            <?php $shipping = wc_get_account_formatted_address('shipping'); ?>

            <p class="mb-account-dash__summary">
                <?php
                echo $shipping
                    ? wp_kses_post($shipping)
                    : esc_html__('You have not added a delivery address yet.', 'granola');
                ?>
            </p>

            <a class="mb-account-link" href="<?php echo esc_url($addresses_url); ?>">
                <?php esc_html_e('Manage addresses', 'granola'); ?>
            </a>
        </div>

        <div>
            <h3 class="mb-account-section"><?php esc_html_e('Your details', 'granola'); ?></h3>

            <p class="mb-account-dash__summary">
                <?php echo esc_html(trim($customer->get_first_name() . ' ' . $customer->get_last_name())); ?><br>
                <?php echo esc_html($customer->get_email()); ?><br>
                <?php
                $registered = $customer->get_date_created();
                if ($registered) {
                    /* translators: %s: the month and year the customer registered. */
                    printf(esc_html__('Member since %s', 'granola'), esc_html(wc_format_datetime($registered, 'F Y')));
                }
                ?>
            </p>

            <a class="mb-account-link" href="<?php echo esc_url($details_url); ?>">
                <?php esc_html_e('Edit account details', 'granola'); ?>
            </a>
        </div>
    </div>

</div>

<?php

/**
 * My Account dashboard.
 *
 * @since 2.6.0
 */
do_action('woocommerce_account_dashboard');
