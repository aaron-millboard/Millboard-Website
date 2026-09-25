<?php

/**
 * Sign in / create account -- supporting data.
 */

namespace Granola\Components\WC_Account_Auth;

/**
 * The sign-in screen is built from the account's own furniture -- the fields,
 * the buttons, the checkbox -- but the account shell itself never renders on
 * this page, so its stylesheet is not enqueued by the usual route. Ask for it
 * directly rather than keeping a second copy of the same rules here.
 */
\add_filter('granola/component/wc-account-auth', function (?array $args): ?array {
    \Granola\Component::enqueue_style_by_filename('wc-account');

    return $args;
});

/**
 * The photograph behind the sign-in screen.
 *
 * Chosen in the admin under Options > General rather than hard-coded, because
 * an attachment ID is per-site: the same picture has a different ID on local,
 * staging and production, and on every locale's blog. Picking it per site is
 * the only version of this that survives a deploy.
 *
 * Returns an attachment ID, or 0 when nothing is set -- the template then
 * falls back to the Wenge ground the design puts behind the image.
 */
function get_hero_image_id(): int
{
    $id = 0;

    if (\function_exists('get_field')) {
        $field = \get_field('account_auth_image', 'option');

        if (\is_array($field)) {
            $id = (int) ($field['ID'] ?? 0);
        } elseif (\is_numeric($field)) {
            $id = (int) $field;
        }
    }

    return (int) \apply_filters('millboard/account/auth_image_id', $id);
}

/**
 * Register the field on the existing General options page.
 */
\add_action('acf/init', function (): void {
    if (!\function_exists('acf_add_local_field_group')) {
        return;
    }

    \acf_add_local_field_group([
        'key' => 'group_account_auth',
        'title' => \__('My Account: sign-in screen', 'granola'),
        'fields' => [
            [
                'key' => 'field_account_auth_image',
                'label' => \__('Sign-in photograph', 'granola'),
                'name' => 'account_auth_image',
                'type' => 'image',
                'instructions' => \__('The lifestyle photograph beside the sign-in form. Landscape or portrait both work; it is cropped to fill. Leave empty to show a plain Wenge panel.', 'granola'),
                'return_format' => 'id',
                'preview_size' => 'medium',
                'library' => 'all',
            ],
            [
                'key' => 'field_account_auth_quote',
                'label' => \__('Sign-in strapline', 'granola'),
                'name' => 'account_auth_quote',
                'type' => 'textarea',
                'rows' => 3,
                'instructions' => \__('The line set over the photograph.', 'granola'),
                'default_value' => \__('Your account holds every order, every sample and every address, so the next project begins where the last one left off.', 'granola'),
            ],
        ],
        'location' => [
            [
                [
                    'param' => 'options_page',
                    'operator' => '==',
                    'value' => 'acf-options-general',
                ],
            ],
        ],
        'position' => 'normal',
        'style' => 'default',
        'active' => true,
    ]);
});

/**
 * The line over the photograph.
 */
function get_hero_quote(): string
{
    $quote = '';

    if (\function_exists('get_field')) {
        $quote = (string) \get_field('account_auth_quote', 'option');
    }

    if ('' === \trim($quote)) {
        $quote = \__('Your account holds every order, every sample and every address, so the next project begins where the last one left off.', 'granola');
    }

    return $quote;
}

/**
 * Which tab opens first.
 *
 * A failed registration comes back to this page with WooCommerce's notices, so
 * the register tab has to reopen or the customer loses sight of what went
 * wrong. WooCommerce does not flag which form failed, so the marker is a query
 * arg the register form carries in its own action URL.
 */
function get_initial_tab(): string
{
    // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- chooses which tab opens, nothing more.
    $requested = isset($_GET['tab']) ? \sanitize_key(\wp_unslash($_GET['tab'])) : '';

    if ('register' === $requested && registration_enabled()) {
        return 'register';
    }

    return 'signin';
}

function registration_enabled(): bool
{
    return 'yes' === \get_option('woocommerce_enable_myaccount_registration');
}
