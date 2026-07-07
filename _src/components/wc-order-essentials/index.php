<?php

defined('ABSPATH') || exit;

$essentials_context = \Granola\Components\WC_OrderEssentials\get_cart_step_context();
$essentials_project_type = isset($essentials_context['project_type']) ? (string) $essentials_context['project_type'] : 'residential';
$essentials_recommendations = isset($essentials_context['recommendations']) && is_array($essentials_context['recommendations']) ? $essentials_context['recommendations'] : [];
$has_essentials = !empty($essentials_context['has_recommendations']);
$has_outstanding_essentials = !empty($essentials_context['has_outstanding_recommendations']);
$essentials_disclaimer_url = isset($essentials_context['disclaimer_url']) ? (string) $essentials_context['disclaimer_url'] : '';
$essentials_summary_label_singular = __('selected item', 'granola');
$essentials_summary_label_plural = __('selected items', 'granola');
$essentials_selected_count = 0;
$essentials_selected_total = 0.0;

// Notices here.
\Granola\Components\WC_OrderEssentials\render_before_cart();
?>

<section class="cart__order-essentials-page" aria-labelledby="order-essentials-heading">
    <form class="cart woocommerce-cart-form" action="<?php echo esc_url(\Theme\WooCommerce\OrderEssentials::get_order_essentials_url()); ?>" method="post" data-currency-code="<?php echo esc_attr(get_woocommerce_currency()); ?>">

        <header class="cart__order-essentials__header">
            <h1 class="cart__order-essentials__title"><?php esc_html_e('Order essentials', 'granola'); ?></h1>

            <h2 class="cart__order-essentials__subtitle" id="order-essentials-heading">
                <?php esc_html_e('Complete your deck', 'granola'); ?>
            </h2>

            <p class="cart__order-essentials__intro">
                <?php esc_html_e('Based on the items in your basket we have identified products and quantities commonly required to complete your project.', 'granola'); ?>
            </p>
        </header>

        <?php if ($has_essentials) : ?>
            <fieldset class="cart__order-essentials__project-type">
                <div class="cart__order-essentials__project-type-legend">
                    <span class="cart__order-essentials__project-type-legend-label">
                        <?php esc_html_e('Select your project type', 'granola'); ?>
                    </span>
                    <label class="cart__order-essentials__project-type-option">
                        <input type="radio" name="millboard_order_essentials_project_type" value="residential" <?php checked($essentials_project_type, 'residential'); ?> onchange="this.form.querySelector('[name=millboard_refresh_essentials]').click()">
                        <span class="cart__order-essentials__project-type-label"><?php esc_html_e('Residential', 'granola'); ?></span>
                    </label>
                    <label class="cart__order-essentials__project-type-option">
                        <input type="radio" name="millboard_order_essentials_project_type" value="commercial" <?php checked($essentials_project_type, 'commercial'); ?> onchange="this.form.querySelector('[name=millboard_refresh_essentials]').click()">
                        <span class="cart__order-essentials__project-type-label"><?php esc_html_e('Commercial', 'granola'); ?></span>
                    </label>
                    <button type="submit" class="button" name="millboard_refresh_essentials" value="1" hidden><?php esc_html_e('Update recommendations', 'granola'); ?></button>
                </div>
            </fieldset>

            <div class="cart__order-essentials__items">
                <?php foreach ($essentials_recommendations as $essentials_item) :
                    $essentials_product_id = isset($essentials_item['product_id']) ? (int) $essentials_item['product_id'] : 0;
                    $essentials_name = isset($essentials_item['product_name']) ? (string) $essentials_item['product_name'] : '';
                    $essentials_url = isset($essentials_item['product_url']) ? (string) $essentials_item['product_url'] : '';
                    $essentials_image = isset($essentials_item['product_image']) ? (string) $essentials_item['product_image'] : '';
                    $essentials_recommended_qty = isset($essentials_item['recommended_qty']) ? (int) $essentials_item['recommended_qty'] : 0;
                    $essentials_in_cart_qty = isset($essentials_item['in_cart_qty']) ? (int) $essentials_item['in_cart_qty'] : 0;
                    $essentials_missing_qty = isset($essentials_item['missing_qty']) ? (int) $essentials_item['missing_qty'] : 0;
                    $essentials_default_add_qty = isset($essentials_item['default_add_qty']) ? (int) $essentials_item['default_add_qty'] : 0;
                    $essentials_reason = isset($essentials_item['reason']) ? (string) $essentials_item['reason'] : '';
                    $essentials_unit_price = \Granola\Components\WC_OrderEssentials\resolve_unit_price($essentials_product_id);
                    $essentials_is_selected = $essentials_missing_qty > 0 || $essentials_in_cart_qty > 0;
                    $essentials_is_in_basket = $essentials_in_cart_qty > 0;
                    $essentials_qty_to_add = max(0, $essentials_default_add_qty);

                    if ($essentials_is_selected) {
                        $essentials_selected_count++;
                        $essentials_selected_total += ($essentials_unit_price * $essentials_qty_to_add);
                    }

                    if ($essentials_product_id < 1 || $essentials_name === '') {
                        continue;
                    }
                    ?>
                    <article class="cart__order-essentials__item<?php echo $essentials_is_selected ? ' is-selected' : ''; ?>">
                        <div class="cart__order-essentials__item-select">
                            <label>
                                <input
                                    type="checkbox"
                                    name="millboard_essentials_selected[<?php echo esc_attr($essentials_product_id); ?>]"
                                    value="1"
                                    <?php checked($essentials_is_selected); ?>
                                    data-essentials-select
                                >
                            </label>
                        </div>

                        <?php if ($essentials_image !== '') : ?>
                            <div class="cart__order-essentials__item-image"><?php echo $essentials_image; ?></div>
                        <?php endif; ?>

                        <div class="cart__order-essentials__item-details">
                            <div class="cart__order-essentials__item-name">
                                <?php if ($essentials_url !== '') : ?>
                                    <a href="<?php echo esc_url($essentials_url); ?>"><?php echo esc_html($essentials_name); ?></a>
                                <?php else : ?>
                                    <?php echo esc_html($essentials_name); ?>
                                <?php endif; ?>
                            </div>

                            <div class="cart__order-essentials__item-meta">
                                <?php
                                echo esc_html(
                                    sprintf(
                                        '%1$s%2$.2f each',
                                        get_woocommerce_currency_symbol(),
                                        number_format($essentials_unit_price, 2)
                                    )
                                );
                                ?>
                            </div>

                            <?php if ($essentials_reason !== '') : ?>
                                <div class="cart__order-essentials__item-reason"><?php echo esc_html($essentials_reason); ?></div>
                            <?php endif; ?>

                            <?php if ($essentials_missing_qty < 1) : ?>
                                <div class="cart__order-essentials__item-status"><?php esc_html_e('You already have the recommended quantity in your basket.', 'granola'); ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="cart__order-essentials__item-qty">
                            <div>
                                <label for="millboard-essentials-qty-<?php echo esc_attr($essentials_product_id); ?>"><?php esc_html_e('Recommended', 'granola'); ?></label>
                                <input
                                    id="millboard-essentials-qty-<?php echo esc_attr($essentials_product_id); ?>"
                                    type="number"
                                    min="0"
                                    step="1"
                                    name="millboard_essentials_qty[<?php echo esc_attr($essentials_product_id); ?>]"
                                    value="<?php echo esc_attr($essentials_qty_to_add); ?>"
                                    data-essentials-qty
                                    data-unit-price="<?php echo esc_attr(wc_format_decimal($essentials_unit_price, 6)); ?>"
                                >
                            </div>
                                
                            <?php if ($essentials_is_in_basket) : ?>
                                <button type="submit" class="g-button g-button--solid cart__order-essentials__item-action" name="millboard_remove_essential_item" value="<?php echo esc_attr($essentials_product_id); ?>"><?php esc_html_e('Remove', 'granola'); ?></button>
                            <?php else : ?>
                                <button type="submit" class="g-button cart__order-essentials__item-action" name="millboard_add_essential_item" value="<?php echo esc_attr($essentials_product_id); ?>"><?php esc_html_e('Add item', 'granola'); ?></button>
                            <?php endif; ?>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>

            <div class="cart__order-essentials__summary" aria-live="polite">
                <p class="cart__order-essentials__summary-count">
                    <span data-essentials-summary-count><?php echo esc_html($essentials_selected_count); ?></span>
                    <span
                        data-essentials-summary-label
                        data-singular="<?php echo esc_attr($essentials_summary_label_singular); ?>"
                        data-plural="<?php echo esc_attr($essentials_summary_label_plural); ?>"
                    >
                        <?php echo esc_html(_n('selected item', 'selected items', $essentials_selected_count, 'granola')); ?>
                    </span>
                </p>
                <p class="cart__order-essentials__summary-total">
                    <span><?php esc_html_e('Total:', 'granola'); ?></span>
                    <strong data-essentials-summary-total><?php echo wp_kses_post(wc_price($essentials_selected_total)); ?></strong>
                </p>
            </div>

            <?php if ($essentials_disclaimer_url !== '') : ?>
                <p class="cart__order-essentials__disclaimer">
                    <span class="cart__order-essentials__disclaimer-icon" aria-hidden="true">!</span>
                    <span class="cart__order-essentials__disclaimer-text">
                        <?php
                        printf(
                            /* translators: %s: installation guides URL */
                            wp_kses(
                                __('These suggestions are based on what is currently in your basket, are intended to help guide your project, and reflect our <a href="%s" target="_blank" rel="noopener noreferrer">Installation Guides</a>. Please review quantities, suitability, and compatibility before completing your order. We are unable to accept responsibility for shortfalls or surplus materials resulting from these suggestions.', 'granola'),
                                ['a' => ['href' => [], 'target' => [], 'rel' => []]]
                            ),
                            esc_url($essentials_disclaimer_url)
                        );
                        ?>
                    </span>
                </p>
            <?php endif; ?>

            <div class="cart__order-essentials__actions">
                <button type="submit" class="g-button" name="millboard_add_all_essentials" value="1" <?php disabled(!$has_outstanding_essentials); ?>><?php esc_html_e('Add ALL essentials', 'granola'); ?></button>
                <button type="submit" class="g-button g-button--solid cart__order-essentials__action-primary" name="millboard_add_selected_essentials" value="1"><?php esc_html_e('Add selected to basket', 'granola'); ?></button>
                <button type="submit" class="g-button g-button--secondary cart__order-essentials__action-secondary" name="millboard_continue_to_basket" value="1"><?php esc_html_e('Continue without essentials', 'granola'); ?></button>
            </div>
        <?php else : ?>
            <p><?php esc_html_e('No essentials are currently recommended for your basket.', 'granola'); ?></p>
            <div class="cart__order-essentials__actions">
                <button type="submit" class="button" name="millboard_continue_to_basket" value="1"><?php esc_html_e('Continue to basket', 'granola'); ?></button>
            </div>
        <?php endif; ?>

        <?php wp_nonce_field('woocommerce-cart', 'woocommerce-cart-nonce'); ?>
    </form>
</section>

<?php \Granola\Components\WC_OrderEssentials\render_after_cart(); ?>
