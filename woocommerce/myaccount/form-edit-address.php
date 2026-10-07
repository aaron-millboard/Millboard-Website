<?php

/**
 * Edit address form.
 *
 * Overridden from WooCommerce to match the 2026 account design: the hairline
 * panel with underline fields, a solid Save and a quiet Cancel back to the
 * address cards.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 9.3.0
 */

defined('ABSPATH') || exit;

$page_title = ('billing' === $load_address)
    ? esc_html__('Edit billing address', 'granola')
    : esc_html__('Edit shipping address', 'granola');

do_action('woocommerce_before_edit_account_address_form');
?>

<?php if (!$load_address) : ?>

    <?php wc_get_template('myaccount/my-address.php'); ?>

<?php else : ?>

    <h2 class="mb-account-panel__title"><?php esc_html_e('Addresses', 'granola'); ?></h2>

    <p class="mb-account-panel__intro">
        <?php esc_html_e('These addresses are used at checkout by default. You can always change them as you order.', 'granola'); ?>
    </p>

    <form class="mb-account-address-form" method="post" novalidate>

        <h3 class="mb-account-address-form__title">
            <?php echo esc_html(apply_filters('woocommerce_my_account_edit_address_title', $page_title, $load_address)); ?>
        </h3>

        <div class="woocommerce-address-fields">
            <?php do_action("woocommerce_before_edit_address_form_{$load_address}"); ?>

            <div class="mb-account-fields mb-account-fields--tight woocommerce-address-fields__field-wrapper">
                <?php
                foreach ($address as $key => $field) {
                    // Every field gets the panel's own wrapper class so the
                    // underline styling applies to WooCommerce's markup too.
                    $field['class'] = array_merge(
                        (array) ($field['class'] ?? []),
                        ['mb-account-field']
                    );

                    // Address lines and company read across the full width
                    // rather than being squeezed into a column.
                    if (in_array($key, ['billing_address_1', 'billing_address_2', 'shipping_address_1', 'shipping_address_2'], true)) {
                        $field['class'][] = 'mb-account-field--wide';
                    }

                    woocommerce_form_field($key, $field, wc_get_post_data_by_key($key, $field['value']));
                }
                ?>
            </div>

            <?php do_action("woocommerce_after_edit_address_form_{$load_address}"); ?>

            <div class="mb-account-actions">
                <button
                    type="submit"
                    class="mb-account-btn"
                    name="save_address"
                    value="<?php esc_attr_e('Save address', 'woocommerce'); ?>"
                >
                    <?php esc_html_e('Save address', 'woocommerce'); ?>
                </button>

                <a class="mb-account-cancel" href="<?php echo esc_url(wc_get_endpoint_url('edit-address', '', wc_get_page_permalink('myaccount'))); ?>">
                    <?php esc_html_e('Cancel', 'granola'); ?>
                </a>

                <?php wp_nonce_field('woocommerce-edit_address', 'woocommerce-edit-address-nonce'); ?>
                <input type="hidden" name="action" value="edit_address" />
            </div>
        </div>

    </form>

<?php endif; ?>

<?php do_action('woocommerce_after_edit_account_address_form');
