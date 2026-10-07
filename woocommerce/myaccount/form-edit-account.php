<?php

/**
 * Account details.
 *
 * Overridden from WooCommerce to match the 2026 account design: three ruled
 * sections (details, password, keeping in touch) of underline fields.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 9.7.0
 */

defined('ABSPATH') || exit;

use function Granola\Components\WC_Account\is_marketing_opted_in;

/**
 * Hook - woocommerce_before_edit_account_form.
 *
 * @since 2.6.0
 */
do_action('woocommerce_before_edit_account_form');

$updated = get_user_meta($user->ID, 'last_update', true);

?>

<h2 class="mb-account-panel__title"><?php esc_html_e('Account details', 'granola'); ?></h2>

<p class="mb-account-panel__intro">
    <?php esc_html_e('The details we hold for you, and how your name appears on reviews and in your account.', 'granola'); ?>
</p>

<form class="woocommerce-EditAccountForm edit-account" action="" method="post" <?php do_action('woocommerce_edit_account_form_tag'); ?>>

    <?php do_action('woocommerce_edit_account_form_start'); ?>

    <h3 class="mb-account-section mb-account-section--accent"><?php esc_html_e('Your details', 'granola'); ?></h3>

    <div class="mb-account-fields">
        <p class="mb-account-field woocommerce-form-row form-row">
            <label for="account_first_name">
                <?php esc_html_e('First name', 'woocommerce'); ?>
                <span class="required" aria-hidden="true">*</span>
            </label>
            <input type="text" name="account_first_name" id="account_first_name" autocomplete="given-name" value="<?php echo esc_attr($user->first_name); ?>" aria-required="true" />
        </p>

        <p class="mb-account-field woocommerce-form-row form-row">
            <label for="account_last_name">
                <?php esc_html_e('Last name', 'woocommerce'); ?>
                <span class="required" aria-hidden="true">*</span>
            </label>
            <input type="text" name="account_last_name" id="account_last_name" autocomplete="family-name" value="<?php echo esc_attr($user->last_name); ?>" aria-required="true" />
        </p>

        <p class="mb-account-field woocommerce-form-row form-row">
            <label for="account_display_name">
                <?php esc_html_e('Display name', 'woocommerce'); ?>
                <span class="required" aria-hidden="true">*</span>
            </label>
            <input type="text" name="account_display_name" id="account_display_name" aria-describedby="account_display_name_description" value="<?php echo esc_attr($user->display_name); ?>" aria-required="true" />
            <span class="mb-account-field__note" id="account_display_name_description">
                <?php esc_html_e('How your name appears in your account and on reviews.', 'granola'); ?>
            </span>
        </p>

        <p class="mb-account-field woocommerce-form-row form-row">
            <label for="account_email">
                <?php esc_html_e('Email address', 'woocommerce'); ?>
                <span class="required" aria-hidden="true">*</span>
            </label>
            <input type="email" name="account_email" id="account_email" autocomplete="email" value="<?php echo esc_attr($user->user_email); ?>" aria-required="true" />
        </p>

        <?php
        /**
         * Hook where additional fields should be rendered.
         *
         * @since 8.7.0
         */
        do_action('woocommerce_edit_account_form_fields');
        ?>
    </div>

    <h3 class="mb-account-section mb-account-section--accent mb-account-section--spaced"><?php esc_html_e('Password', 'granola'); ?></h3>

    <div class="mb-account-fields">
        <p class="mb-account-field woocommerce-form-row form-row">
            <label for="password_current"><?php esc_html_e('Current password', 'granola'); ?></label>
            <input type="password" name="password_current" id="password_current" autocomplete="off" placeholder="<?php esc_attr_e('Leave blank to keep it', 'granola'); ?>" />
        </p>

        <p class="mb-account-field woocommerce-form-row form-row">
            <label for="password_1"><?php esc_html_e('New password', 'granola'); ?></label>
            <input type="password" name="password_1" id="password_1" autocomplete="new-password" placeholder="<?php esc_attr_e('Leave blank to keep it', 'granola'); ?>" />
        </p>

        <p class="mb-account-field woocommerce-form-row form-row">
            <label for="password_2"><?php esc_html_e('Confirm new password', 'granola'); ?></label>
            <input type="password" name="password_2" id="password_2" autocomplete="new-password" />
        </p>
    </div>

    <h3 class="mb-account-section mb-account-section--accent mb-account-section--spaced"><?php esc_html_e('Keeping in touch', 'granola'); ?></h3>

    <label class="mb-account-check" for="millboard_marketing_opt_in">
        <input
            type="checkbox"
            name="millboard_marketing_opt_in"
            id="millboard_marketing_opt_in"
            value="1"
            <?php checked(is_marketing_opted_in($user->ID)); ?>
        />
        <span><?php esc_html_e('Send me occasional inspiration, new shades and project stories. No more than once a month.', 'granola'); ?></span>
    </label>

    <?php
    /**
     * My Account edit account form.
     *
     * @since 2.6.0
     */
    do_action('woocommerce_edit_account_form');
    ?>

    <div class="mb-account-actions mb-account-actions--ruled">
        <?php wp_nonce_field('save_account_details', 'save-account-details-nonce'); ?>

        <button type="submit" class="mb-account-btn" name="save_account_details" value="<?php esc_attr_e('Save changes', 'woocommerce'); ?>">
            <?php esc_html_e('Save changes', 'woocommerce'); ?>
        </button>

        <?php if ($updated) : ?>
            <span class="mb-account-actions__note">
                <?php
                /* translators: %s: the date the account was last updated. */
                printf(esc_html__('Last updated %s', 'granola'), esc_html(date_i18n('j F Y', (int) $updated)));
                ?>
            </span>
        <?php endif; ?>

        <input type="hidden" name="action" value="save_account_details" />
    </div>

    <?php do_action('woocommerce_edit_account_form_end'); ?>
</form>

<?php do_action('woocommerce_after_edit_account_form');
