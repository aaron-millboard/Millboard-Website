<?php

defined('ABSPATH') || exit;

$essentials_context = \Granola\Components\WC_OrderEssentials\get_cart_step_context();
$essentials_project_type = isset($essentials_context['project_type']) ? (string) $essentials_context['project_type'] : 'residential';
$essentials_recommendations = isset($essentials_context['recommendations']) && is_array($essentials_context['recommendations']) ? $essentials_context['recommendations'] : [];
$has_essentials = !empty($essentials_context['has_recommendations']);
$has_outstanding_essentials = !empty($essentials_context['has_outstanding_recommendations']);
$essentials_recommendation_source_label = isset($essentials_context['recommendation_source_label']) ? (string) $essentials_context['recommendation_source_label'] : '';
$essentials_disclaimer_url = isset($essentials_context['disclaimer_url']) ? (string) $essentials_context['disclaimer_url'] : '';
$essentials_show_added_modal = !empty($essentials_context['show_added_modal']);
$essentials_basket_kind = isset($essentials_context['basket_kind']) ? (string) $essentials_context['basket_kind'] : '';
$essentials_summary_label_singular = __('selected item', 'granola');
$essentials_summary_label_plural = __('selected items', 'granola');
$essentials_selected_count = 0;
$essentials_selected_total = 0.0;

// Only the items STILL missing belong in the warning. Listing every
// recommendation meant that after adding them all, the modal went on naming them
// as though they were absent.
$names = [];

foreach ($essentials_recommendations as $essentials_item) {
    if ((int) ($essentials_item['missing_qty'] ?? 0) < 1) {
        continue;
    }

    $essentials_name = isset($essentials_item['product_name']) ? (string) $essentials_item['product_name'] : '';
    if ($essentials_name !== '') {
        $names[] = $essentials_name;
    }
}

$count = count($names);

if ($count === 0) {
    $names_array = '';
} elseif ($count === 1) {
    $names_array = $names[0];
} else {
    $last = array_pop($names);
    $names_array = implode(', ', $names) . ' and ' . $last;
}

// Notices here.
\Granola\Components\WC_OrderEssentials\render_before_cart();
?>

<section class="cart__order-essentials-page" aria-labelledby="order-essentials-heading">
    <form class="cart woocommerce-cart-form" action="<?php echo esc_url(\Theme\WooCommerce\OrderEssentials::get_order_essentials_url()); ?>" method="post" data-currency-code="<?php echo esc_attr(get_woocommerce_currency()); ?>">

        <header class="cart__order-essentials__header">
            <h1 class="cart__order-essentials__title"><?php esc_html_e('Order essentials', 'granola'); ?></h1>

            <h2 class="cart__order-essentials__subtitle" id="order-essentials-heading">
                <?php
                switch ($essentials_basket_kind) {
                    case 'decking':
                        esc_html_e('Complete your deck', 'granola');
                        break;
                    case 'cladding':
                        esc_html_e('Complete your cladding', 'granola');
                        break;
                    default:
                        esc_html_e('Complete your project', 'granola');
                }
                ?>
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
                    <div class="cart__order-essentials__project-type-options">
                        <label class="cart__order-essentials__project-type-option">
                            <input type="radio" name="millboard_order_essentials_project_type" value="residential" <?php checked($essentials_project_type, 'residential'); ?> onchange="this.form.querySelector('[name=millboard_refresh_essentials]').click()">
                            <span class="cart__order-essentials__project-type-label"><?php esc_html_e('Residential', 'granola'); ?></span>
                        </label>
                        <label class="cart__order-essentials__project-type-option">
                            <input type="radio" name="millboard_order_essentials_project_type" value="commercial" <?php checked($essentials_project_type, 'commercial'); ?> onchange="this.form.querySelector('[name=millboard_refresh_essentials]').click()">
                            <span class="cart__order-essentials__project-type-label"><?php esc_html_e('Commercial', 'granola'); ?></span>
                        </label>
                    </div>
                    <button type="submit" class="button" name="millboard_refresh_essentials" value="1" hidden><?php esc_html_e('Update recommendations', 'granola'); ?></button>
                </div>
            </fieldset>

            <?php
            // DuoLift component counts and post heights come from a lookup on the
            // finished floor level, which cannot be derived from the basket. Only ask
            // when the basket actually contains a DuoLift build or posts.
            if (!empty($essentials_context['ffl_needed'])) :
                $essentials_ffl = isset($essentials_context['ffl']) ? (int) $essentials_context['ffl'] : 0;
                $essentials_ffl_missing = !empty($essentials_context['ffl_missing']);
                $essentials_ffl_out_of_range = !empty($essentials_context['ffl_out_of_range']);
                ?>
                <fieldset class="cart__order-essentials__ffl">
                    <div class="cart__order-essentials__ffl-inner">
                        <label class="cart__order-essentials__ffl-label" for="millboard-order-essentials-ffl">
                            <?php esc_html_e('Finished floor level (mm)', 'granola'); ?>
                        </label>

                        <p class="cart__order-essentials__ffl-help">
                            <?php esc_html_e('The height from the base to the top of your finished deck. We need this to work out how many subframe supports and risers your project takes.', 'granola'); ?>
                        </p>

                        <input
                            type="number"
                            inputmode="numeric"
                            id="millboard-order-essentials-ffl"
                            class="cart__order-essentials__ffl-input"
                            name="millboard_order_essentials_ffl"
                            value="<?php echo $essentials_ffl > 0 ? esc_attr((string) $essentials_ffl) : ''; ?>"
                            min="1"
                            max="1140"
                            step="1"
                            placeholder="<?php esc_attr_e('e.g. 250', 'granola'); ?>"
                            aria-describedby="millboard-order-essentials-ffl-help"
                        >

                        <?php
                        // Pads sit between the joist and the cradle, so they are only
                        // relevant to a DuoLift build, and they are not sold for the
                        // France configurations.
                        if (!empty($essentials_context['acoustic_pads_offered'])) : ?>
                            <label class="cart__order-essentials__ffl-acoustic">
                                <input type="checkbox" name="millboard_order_essentials_acoustic_pads" value="1" <?php checked(!empty($essentials_context['acoustic_pads'])); ?>>
                                <span><?php esc_html_e('Include acoustic separation pads', 'granola'); ?></span>
                            </label>
                        <?php endif; ?>

                        <button type="submit" class="button" name="millboard_refresh_essentials" value="1">
                            <?php esc_html_e('Update recommendations', 'granola'); ?>
                        </button>

                        <?php if ($essentials_ffl_missing) : ?>
                            <p class="cart__order-essentials__ffl-notice" id="millboard-order-essentials-ffl-help" role="status">
                                <?php esc_html_e('Enter your finished floor level so we can include the right subframe supports.', 'granola'); ?>
                            </p>
                        <?php elseif ($essentials_ffl_out_of_range) : ?>
                            <p class="cart__order-essentials__ffl-notice cart__order-essentials__ffl-notice--warning" id="millboard-order-essentials-ffl-help" role="alert">
                                <?php esc_html_e('That finished floor level is outside the range we can calculate supports for. Please contact us and we will size the subframe for you.', 'granola'); ?>
                            </p>
                        <?php elseif (!empty($essentials_context['project_area'])) : ?>
                            <p class="cart__order-essentials__ffl-notice" id="millboard-order-essentials-ffl-help">
                                <?php echo esc_html(sprintf(
                                    /* translators: %s: project area in square metres. */
                                    __('Based on a project area of %s m² from the boards in your basket.', 'granola'),
                                    (string) $essentials_context['project_area']
                                )); ?>
                            </p>
                        <?php endif; ?>
                    </div>
                </fieldset>
            <?php endif; ?>

            <div class="cart__order-essentials__items">
                <?php if ($essentials_recommendation_source_label !== '') : ?>
                    <p class="cart__order-essentials__recommendation-source-label"><?php esc_html_e('Recommendations based on:', 'granola'); ?></p>
                    <p class="cart__order-essentials__recommendation-source"><?php echo esc_html($essentials_recommendation_source_label); ?></p>
                <?php endif; ?>

                <?php foreach ($essentials_recommendations as $essentials_item) :
                    $essentials_product_id = isset($essentials_item['product_id']) ? (int) $essentials_item['product_id'] : 0;
                    $essentials_name = isset($essentials_item['product_name']) ? (string) $essentials_item['product_name'] : '';
                    $essentials_url = isset($essentials_item['product_url']) ? (string) $essentials_item['product_url'] : '';
                    $essentials_image = isset($essentials_item['product_image']) ? (string) $essentials_item['product_image'] : '';
                    $essentials_recommended_qty = isset($essentials_item['recommended_qty']) ? (int) $essentials_item['recommended_qty'] : 0;
                    $essentials_in_cart_qty = isset($essentials_item['in_cart_qty']) ? (int) $essentials_item['in_cart_qty'] : 0;
                    $essentials_missing_qty = isset($essentials_item['missing_qty']) ? (int) $essentials_item['missing_qty'] : 0;
                    $essentials_default_add_qty = isset($essentials_item['default_add_qty']) ? (int) $essentials_item['default_add_qty'] : 0;
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

                        <div class="cart__order-essentials__item-info">
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

                                <?php if ($essentials_missing_qty < 1) : ?>
                                    <div class="cart__order-essentials__item-status"><?php esc_html_e('You already have the recommended quantity in your basket.', 'granola'); ?></div>
                                <?php endif; ?>
                            </div>

                            <div class="cart__order-essentials__item-qty">
                                <div class="cart__order-essentials__item-qty-control">
                                    <label for="millboard-essentials-qty-<?php echo esc_attr($essentials_product_id); ?>"><?php esc_html_e('Recommended', 'granola'); ?></label>
                                    <div class="cart__order-essentials__qty-control" data-essentials-qty-control>
                                        <button
                                            type="button"
                                            class="cart__order-essentials__qty-button"
                                            data-essentials-qty-adjust="decrement"
                                            aria-label="<?php echo esc_attr(sprintf(__('Decrease quantity for %s', 'granola'), $essentials_name)); ?>"
                                        >
                                            -
                                        </button>
                                        <input
                                            id="millboard-essentials-qty-<?php echo esc_attr($essentials_product_id); ?>"
                                            class="cart__order-essentials__qty-input"
                                            type="number"
                                            min="0"
                                            step="1"
                                            name="millboard_essentials_qty[<?php echo esc_attr($essentials_product_id); ?>]"
                                            value="<?php echo esc_attr($essentials_qty_to_add); ?>"
                                            inputmode="numeric"
                                            data-essentials-qty
                                            data-unit-price="<?php echo esc_attr(wc_format_decimal($essentials_unit_price, 6)); ?>"
                                        >
                                        <button
                                            type="button"
                                            class="cart__order-essentials__qty-button"
                                            data-essentials-qty-adjust="increment"
                                            aria-label="<?php echo esc_attr(sprintf(__('Increase quantity for %s', 'granola'), $essentials_name)); ?>"
                                        >
                                            +
                                        </button>
                                    </div>
                                </div>
                                    
                                <?php if ($essentials_is_in_basket) : ?>
                                    <button type="submit" class="g-button g-button--solid cart__order-essentials__item-action" name="millboard_remove_essential_item" value="<?php echo esc_attr($essentials_product_id); ?>"><?php esc_html_e('Remove', 'granola'); ?></button>
                                <?php else : ?>
                                    <button type="submit" class="g-button cart__order-essentials__item-action" name="millboard_add_essential_item" value="<?php echo esc_attr($essentials_product_id); ?>"><?php esc_html_e('Add item', 'granola'); ?></button>
                                <?php endif; ?>
                            </div>
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
                                __('These suggestions are based on what is currently in your basket and follow our <a href="%s" target="_blank" rel="noopener noreferrer">Installation Guides</a>. They are intended to help you plan your project. Please review quantities, suitability, and compatibility before completing your order. We are unable to accept responsibility for shortfalls or surplus materials resulting from these suggestions.', 'granola'),
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
                <button type="submit" class="g-button g-button--secondary cart__order-essentials__action-secondary" name="millboard_continue_to_basket" value="1" data-essentials-open-modal="continue"><?php esc_html_e('Continue without essentials', 'granola'); ?></button>
            </div>

            <div
                class="cart__order-essentials-modal<?php echo $essentials_show_added_modal ? ' is-active' : ''; ?>"
                data-essentials-modal
                aria-hidden="<?php echo $essentials_show_added_modal ? 'false' : 'true'; ?>"
            >
                <div class="cart__order-essentials-modal__overlay" data-essentials-close-modal></div>
                <div
                    class="cart__order-essentials-modal__dialog"
                    role="dialog"
                    aria-modal="true"
                    aria-labelledby="order-essentials-modal-title"
                >
                    <button type="button" class="cart__order-essentials-modal__close" data-essentials-close-modal>
                        <span><?php esc_html_e('Close', 'granola'); ?></span>
                        <span aria-hidden="true">&times;</span>
                    </button>

                    <div data-essentials-modal-panel="added" <?php echo $essentials_show_added_modal ? '' : 'hidden'; ?>>
                        <p class="cart__order-essentials-modal__notice">
                            <?php esc_html_e('Essentials have been added to your basket.', 'granola'); ?>
                        </p>
                        <h2 class="cart__order-essentials-modal__title" id="order-essentials-modal-title">
                            <?php esc_html_e('Essentials added to basket', 'granola'); ?>
                        </h2>
                        <p class="cart__order-essentials-modal__intro">
                            <?php esc_html_e('Please check the quantities suit your project before you check out.', 'granola'); ?>
                        </p>
                    </div>

                    <div data-essentials-modal-panel="continue" <?php echo $essentials_show_added_modal ? 'hidden' : ''; ?>>
                        <h2 class="cart__order-essentials-modal__title" id="order-essentials-modal-title-continue">
                            <?php esc_html_e('Continue without essentials?', 'granola'); ?>
                        </h2>
                        <p class="cart__order-essentials-modal__intro">
                            <?php
                            // The parts differ by product. Only mention a sub-frame when
                            // there is decking in the basket, since cladding has none,
                            // and a mixed basket does need one.
                            switch ($essentials_basket_kind) {
                                case 'decking':
                                    esc_html_e('Decking needs the right fixings, sub-frame and finishing pieces to install correctly.', 'granola');
                                    break;
                                case 'cladding':
                                    esc_html_e('Cladding needs the right fixings and finishing pieces to install correctly.', 'granola');
                                    break;
                                case 'both':
                                    esc_html_e('Decking and cladding both need the right fixings and finishing pieces to install correctly, and your deck needs a sub-frame.', 'granola');
                                    break;
                                default:
                                    esc_html_e('Your project needs the right fixings and finishing pieces to install correctly.', 'granola');
                            }
                            ?>
                            <?php esc_html_e('You can carry on without adding our suggested essentials. We just want to make sure you have considered it.', 'granola'); ?>
                        </p>
                    </div>

                    <?php if ($has_outstanding_essentials && $names_array !== '') : ?>
                        <div class="cart__order-essentials-modal__warning">
                            <span class="cart__order-essentials-modal__warning-icon" aria-hidden="true">!</span>
                            <span>
                                <strong><?php esc_html_e('Heads up.', 'granola'); ?></strong>
                                <?php echo wp_kses_post(sprintf(__('Without %s your order may not meet our installation guidelines and the Millboard warranty.', 'granola'), $names_array)); ?>
                            </span>
                        </div>
                    <?php endif; ?>

                    <?php if ($has_outstanding_essentials) : ?>
                        <label class="cart__order-essentials-modal__ack">
                            <input type="checkbox" data-essentials-modal-ack>
                            <span>
                                <?php
                                if ($essentials_basket_kind === 'cladding') {
                                    esc_html_e('I understand the recommended essentials have not been added to my order, and accept responsibility for sourcing the correct fixings for my project. This note will be saved against my order.', 'granola');
                                } else {
                                    esc_html_e('I understand the recommended essentials have not been added to my order, and accept responsibility for sourcing the correct fixing and sub-frame for my project. This note will be saved against my order.', 'granola');
                                }
                                ?>
                            </span>
                        </label>
                    <?php endif; ?>

                    <div class="cart__order-essentials-modal__actions">
                        <button type="submit" class="g-button g-button--solid" name="millboard_continue_to_basket" value="1" <?php disabled($has_outstanding_essentials); ?> data-essentials-modal-submit>
                            <?php esc_html_e('Continue to basket', 'granola'); ?>
                        </button>
                        <button type="button" class="g-button g-button--secondary" data-essentials-close-modal>
                            <?php esc_html_e('Back to essentials', 'granola'); ?>
                        </button>
                    </div>
                </div>
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
