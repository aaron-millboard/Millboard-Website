<?php

/**
 * Orders list.
 *
 * Overridden from WooCommerce to match the 2026 account design: the All / Open
 * / Delivered tabs, then a five-column list that folds to three rows on narrow
 * screens. The tabs are plain links filtered in the component's hooks.php, so
 * the list works without JavaScript and each filter is a shareable URL.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 9.5.0
 */

defined('ABSPATH') || exit;

use function Granola\Components\WC_Account\get_current_order_tab;
use function Granola\Components\WC_Account\get_order_counts;
use function Granola\Components\WC_Account\get_order_summary;
use function Granola\Components\WC_Account\get_order_tabs;
use function Granola\Components\WC_Account\is_closed;

do_action('woocommerce_before_account_orders', $has_orders);

$current_tab = get_current_order_tab();
$tabs = get_order_tabs($current_tab);
$counts = get_order_counts(get_current_user_id());
$shown = $has_orders ? count($customer_orders->orders) : 0;

?>

<div class="mb-account-orders">

    <h2 class="mb-account-panel__title"><?php esc_html_e('Orders', 'granola'); ?></h2>

    <p class="mb-account-panel__intro">
        <?php esc_html_e('Every order and sample request placed with your email address.', 'granola'); ?>
        <?php
        printf(
            /* translators: %1$s: opening link tag, %2$s: closing link tag. */
            esc_html__('Ordered with us before you had an account? %1$sGet in touch%2$s and we will link those orders here.', 'granola'),
            '<a href="' . esc_url(get_permalink(get_page_by_path('contact-us'))) . '">',
            '</a>'
        );
        ?>
    </p>

    <?php if (!$counts['all']) : ?>

        <div class="mb-account-empty">
            <span class="mb-account-empty__rule" aria-hidden="true"></span>
            <h3 class="mb-account-empty__title"><?php esc_html_e('No orders yet', 'granola'); ?></h3>
            <p class="mb-account-empty__body">
                <?php esc_html_e('Your orders will gather here. Start with a few samples: the grain and the weight tell you more than any photograph can.', 'granola'); ?>
            </p>
            <?php
            $samples_link = get_field('header_call_to_action_1', 'option');
            $samples_url = is_array($samples_link) && !empty($samples_link['url'])
                ? $samples_link['url']
                : wc_get_page_permalink('shop');
            ?>
            <a class="mb-account-btn mb-account-btn--ghost" href="<?php echo esc_url($samples_url); ?>">
                <?php esc_html_e('Order samples', 'granola'); ?>
            </a>
        </div>

    <?php else : ?>

        <div class="mb-account-orders__tabs">
            <?php foreach ($tabs as $tab) : ?>
                <a
                    class="mb-account-orders__tab<?php echo $tab['current'] ? ' is-current' : ''; ?>"
                    href="<?php echo esc_url($tab['url']); ?>"
                    <?php echo $tab['current'] ? 'aria-current="true"' : ''; ?>
                >
                    <?php echo esc_html($tab['label']); ?>
                </a>
            <?php endforeach; ?>
        </div>

        <?php if ($has_orders) : ?>

            <div class="mb-account-orders__head" aria-hidden="true">
                <span><?php esc_html_e('Order', 'granola'); ?></span>
                <span><?php esc_html_e('Date', 'granola'); ?></span>
                <span><?php esc_html_e('Items', 'granola'); ?></span>
                <span><?php esc_html_e('Status', 'granola'); ?></span>
                <span><?php esc_html_e('Total', 'granola'); ?></span>
            </div>

            <div class="mb-account-orders__list mb-account-orders__list--flush">
                <?php foreach ($customer_orders->orders as $customer_order) :
                    $order = wc_get_order($customer_order);

                    if (!$order instanceof WC_Order) {
                        continue;
                    }
                    ?>
                    <a
                        class="mb-account-orders__row"
                        href="<?php echo esc_url($order->get_view_order_url()); ?>"
                        aria-label="<?php
                            /* translators: %s: order number. */
                            echo esc_attr(sprintf(__('View order number %s', 'woocommerce'), $order->get_order_number()));
                        ?>"
                    >
                        <span class="mb-account-orders__id">#<?php echo esc_html($order->get_order_number()); ?></span>
                        <span class="mb-account-orders__date">
                            <time datetime="<?php echo esc_attr($order->get_date_created()->date('c')); ?>">
                                <?php echo esc_html(wc_format_datetime($order->get_date_created(), 'j F Y')); ?>
                            </time>
                        </span>
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

            <p class="mb-account-orders__count">
                <?php
                printf(
                    /* translators: %1$s: orders on this page, %2$s: total orders on the account. */
                    esc_html__('Showing %1$s of %2$s orders.', 'granola'),
                    esc_html(number_format_i18n($shown)),
                    esc_html(number_format_i18n($counts['all']))
                );
                ?>
            </p>

            <?php do_action('woocommerce_before_account_orders_pagination'); ?>

            <?php if (1 < $customer_orders->max_num_pages) : ?>
                <div class="mb-account-orders__pagination">
                    <?php if (1 !== $current_page) : ?>
                        <a class="mb-account-btn mb-account-btn--ghost" href="<?php echo esc_url(wc_get_endpoint_url('orders', $current_page - 1)); ?>">
                            <?php esc_html_e('Previous', 'woocommerce'); ?>
                        </a>
                    <?php endif; ?>

                    <?php if (intval($customer_orders->max_num_pages) !== $current_page) : ?>
                        <a class="mb-account-btn mb-account-btn--ghost" href="<?php echo esc_url(wc_get_endpoint_url('orders', $current_page + 1)); ?>">
                            <?php esc_html_e('Next', 'woocommerce'); ?>
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

        <?php else : ?>

            <?php
            // The account has orders, just none in this tab.
            ?>
            <div class="mb-account-empty">
                <span class="mb-account-empty__rule" aria-hidden="true"></span>
                <h3 class="mb-account-empty__title"><?php esc_html_e('Nothing in this view', 'granola'); ?></h3>
                <p class="mb-account-empty__body">
                    <?php esc_html_e('No orders match this filter at the moment.', 'granola'); ?>
                </p>
                <a class="mb-account-btn mb-account-btn--ghost" href="<?php echo esc_url($tabs[0]['url']); ?>">
                    <?php esc_html_e('Show all orders', 'granola'); ?>
                </a>
            </div>

        <?php endif; ?>

    <?php endif; ?>

</div>

<?php do_action('woocommerce_after_account_orders', $has_orders);
