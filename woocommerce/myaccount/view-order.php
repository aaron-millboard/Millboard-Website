<?php

/**
 * View order.
 *
 * Overridden from WooCommerce to match the 2026 account design. The layout
 * below replaces `woocommerce_order_details_table`, which core hangs off the
 * `woocommerce_view_order` action, so that one callback is unhooked before the
 * action fires. The action itself still runs: plugins add things like tracking
 * numbers and re-order buttons there and should keep their slot.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 10.1.0
 */

defined('ABSPATH') || exit;

use function Granola\Components\WC_Account\get_order_steps;
use function Granola\Components\WC_Account\get_order_summary;
use function Granola\Components\WC_Account\is_closed;

$notes = $order->get_customer_order_notes();
$steps = get_order_steps($order);
$orders_url = wc_get_endpoint_url('orders', '', wc_get_page_permalink('myaccount'));
$contact = get_page_by_path('contact-us');

?>

<div class="mb-account-order">

    <a class="mb-account-back" href="<?php echo esc_url($orders_url); ?>">
        <span aria-hidden="true">&lsaquo;</span>
        <?php esc_html_e('All orders', 'granola'); ?>
    </a>

    <div class="mb-account-order__head">
        <h2 class="mb-account-order__title">
            <?php
            /* translators: %s: order number. */
            printf(esc_html__('Order %s', 'granola'), '#' . esc_html($order->get_order_number()));
            ?>
        </h2>
        <span class="mb-account-pill<?php echo is_closed($order) ? ' mb-account-pill--closed' : ''; ?>">
            <?php echo esc_html(wc_get_order_status_name($order->get_status())); ?>
        </span>
    </div>

    <p class="mb-account-order__meta">
        <?php
        /* translators: %s: the date the order was placed. */
        printf(esc_html__('Placed %s', 'granola'), esc_html(wc_format_datetime($order->get_date_created(), 'j F Y')));
        echo ' &middot; ';
        echo wp_kses_post(get_order_summary($order));
        ?>
    </p>

    <?php if ($steps) : ?>
        <div class="mb-account-order__steps">
            <?php foreach ($steps as $step) : ?>
                <div class="mb-account-order__step<?php echo $step['reached'] ? ' is-reached' : ''; ?>">
                    <span class="mb-account-order__step-dot" aria-hidden="true"></span>
                    <div class="mb-account-order__step-label"><?php echo esc_html($step['label']); ?></div>
                    <div class="mb-account-order__step-when"><?php echo esc_html($step['when']); ?></div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <h3 class="mb-account-section"><?php esc_html_e('Items', 'granola'); ?></h3>

    <?php
    foreach ($order->get_items() as $item_id => $item) :
        $product = $item->get_product();
        $is_visible = $product && $product->is_visible();
        $thumbnail = $product ? $product->get_image('thumbnail', ['class' => ''], false) : '';
        ?>
        <div class="mb-account-order__item">
            <span class="mb-account-order__thumb" aria-hidden="true"><?php echo wp_kses_post($thumbnail); ?></span>

            <span class="mb-account-order__item-text">
                <span class="mb-account-order__item-name">
                    <?php if ($is_visible) : ?>
                        <a href="<?php echo esc_url($product->get_permalink()); ?>"><?php echo wp_kses_post($item->get_name()); ?></a>
                    <?php else : ?>
                        <?php echo wp_kses_post($item->get_name()); ?>
                    <?php endif; ?>
                </span>

                <?php
                // Variation attributes and any item meta, which is where the
                // board's colour and finish live.
                $meta = wc_display_item_meta($item, ['echo' => false, 'before' => '', 'after' => '', 'separator' => ' &middot; ']);

                if ($meta) :
                    ?>
                    <span class="mb-account-order__item-sub"><?php echo wp_kses_post(wp_strip_all_tags($meta)); ?></span>
                <?php endif; ?>
            </span>

            <span class="mb-account-order__item-qty">&times;<?php echo esc_html($item->get_quantity()); ?></span>
            <span class="mb-account-order__item-price"><?php echo wp_kses_post($order->get_formatted_line_subtotal($item)); ?></span>
        </div>
    <?php endforeach; ?>

    <div class="mb-account-order__totals">
        <?php
        $totals = $order->get_order_item_totals();
        $last = $totals ? array_key_last($totals) : null;

        foreach ((array) $totals as $key => $total) :
            $is_grand = $key === $last;
            ?>
            <div class="mb-account-order__total-row<?php echo $is_grand ? ' mb-account-order__total-row--grand' : ''; ?>">
                <span class="mb-account-order__total-label"><?php echo esc_html(rtrim($total['label'], ':')); ?></span>
                <span class="mb-account-order__total-value"><?php echo wp_kses_post($total['value']); ?></span>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="mb-account-order__columns">
        <?php if ($order->has_shipping_address()) : ?>
            <div>
                <h3 class="mb-account-section"><?php esc_html_e('Shipping address', 'granola'); ?></h3>
                <address class="mb-account-order__address"><?php echo wp_kses_post($order->get_formatted_shipping_address()); ?></address>
            </div>
        <?php endif; ?>

        <div>
            <h3 class="mb-account-section"><?php esc_html_e('Billing address', 'granola'); ?></h3>
            <address class="mb-account-order__address">
                <?php echo wp_kses_post($order->get_formatted_billing_address(esc_html__('Not provided', 'granola'))); ?>
            </address>
        </div>

        <div>
            <h3 class="mb-account-section"><?php esc_html_e('Need a hand?', 'granola'); ?></h3>
            <p class="mb-account-order__address"><?php esc_html_e('Our team is here Monday to Friday, 8am to 5pm.', 'granola'); ?></p>
            <?php if ($contact) : ?>
                <a class="mb-account-link" href="<?php echo esc_url(get_permalink($contact)); ?>">
                    <?php esc_html_e('Contact support about this order', 'granola'); ?>
                </a>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($notes) : ?>
        <h3 class="mb-account-section mb-account-section--spaced"><?php esc_html_e('Order updates', 'granola'); ?></h3>
        <ol class="mb-account-order__notes">
            <?php foreach ($notes as $note) : ?>
                <li class="mb-account-order__note">
                    <span class="mb-account-order__note-date">
                        <?php echo esc_html(date_i18n('j F Y', strtotime($note->comment_date))); ?>
                    </span>
                    <div class="mb-account-order__note-body">
                        <?php echo wpautop(wptexturize(wp_kses_post($note->comment_content))); ?>
                    </div>
                </li>
            <?php endforeach; ?>
        </ol>
    <?php endif; ?>

</div>

<?php

// Core hangs its own order table off this action, which would print the whole
// order again beneath the layout above. Unhook that one callback and let the
// action run, so plugins keep their slot.
remove_action('woocommerce_view_order', 'woocommerce_order_details_table', 10);

do_action('woocommerce_view_order', $order_id);
