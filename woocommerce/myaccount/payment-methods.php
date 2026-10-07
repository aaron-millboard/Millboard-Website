<?php

/**
 * Payment methods.
 *
 * Overridden from WooCommerce to match the 2026 account design: a hairline row
 * per saved card with a Default or Backup marker, or the empty panel.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 8.9.0
 */

defined('ABSPATH') || exit;

$saved_methods = wc_get_customer_saved_methods_list(get_current_user_id());
$has_methods = (bool) $saved_methods;

do_action('woocommerce_before_account_payment_methods', $has_methods);

?>

<h2 class="mb-account-panel__title"><?php esc_html_e('Payment methods', 'granola'); ?></h2>

<p class="mb-account-panel__intro">
    <?php esc_html_e('Save a card to make repeat orders quicker. Card details are held securely by our payment provider: we never store them ourselves.', 'granola'); ?>
</p>

<?php if ($has_methods) : ?>

    <div class="mb-account-cards">
        <?php foreach ($saved_methods as $type => $methods) : ?>
            <?php foreach ($methods as $method) : ?>
                <div class="mb-account-cards__row">
                    <span class="mb-account-cards__brand">
                        <?php
                        echo esc_html(
                            !empty($method['method']['brand'])
                                ? wc_get_credit_card_type_label($method['method']['brand'])
                                : __('Card', 'granola')
                        );
                        ?>
                    </span>

                    <span class="mb-account-cards__number">
                        <?php
                        if (!empty($method['method']['last4'])) {
                            /* translators: %s: the last four digits of the card. */
                            printf(esc_html__('Ending %s', 'granola'), esc_html($method['method']['last4']));
                        } else {
                            esc_html_e('Saved method', 'granola');
                        }
                        ?>
                    </span>

                    <span class="mb-account-cards__expiry">
                        <?php if (!empty($method['expires']) && 'N/A' !== $method['expires']) : ?>
                            <?php
                            /* translators: %s: the card expiry date. */
                            printf(esc_html__('Expires %s', 'granola'), esc_html($method['expires']));
                            ?>
                        <?php endif; ?>
                    </span>

                    <span class="mb-account-cards__flag">
                        <span class="mb-account-pill<?php echo empty($method['is_default']) ? ' mb-account-pill--closed' : ''; ?>">
                            <?php
                            echo empty($method['is_default'])
                                ? esc_html__('Backup', 'granola')
                                : esc_html__('Default', 'granola');
                            ?>
                        </span>
                    </span>

                    <span class="mb-account-cards__actions">
                        <?php foreach ((array) $method['actions'] as $key => $action) : ?>
                            <a class="mb-account-cards__action <?php echo esc_attr(sanitize_html_class($key)); ?>" href="<?php echo esc_url($action['url']); ?>">
                                <?php echo esc_html($action['name']); ?>
                            </a>
                        <?php endforeach; ?>
                    </span>
                </div>
            <?php endforeach; ?>
        <?php endforeach; ?>
    </div>

<?php else : ?>

    <div class="mb-account-empty">
        <span class="mb-account-empty__rule" aria-hidden="true"></span>
        <h3 class="mb-account-empty__title"><?php esc_html_e('No saved methods', 'granola'); ?></h3>
        <p class="mb-account-empty__body">
            <?php esc_html_e('Nothing saved yet. Add a card now, or save one at checkout the next time you order.', 'granola'); ?>
        </p>
    </div>

<?php endif; ?>

<?php do_action('woocommerce_after_account_payment_methods', $has_methods); ?>

<?php if (WC()->payment_gateways->get_available_payment_gateways()) : ?>
    <p class="mb-account-actions">
        <a class="mb-account-btn mb-account-btn--ghost" href="<?php echo esc_url(wc_get_endpoint_url('add-payment-method')); ?>">
            <?php esc_html_e('Add payment method', 'woocommerce'); ?>
        </a>
    </p>
<?php endif; ?>
