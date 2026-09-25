<?php

/**
 * My Account -- the shell.
 *
 * Masthead, navigation and the panel WooCommerce has routed to. Every panel's
 * own markup lives in the matching template under woocommerce/myaccount/.
 */

use function Granola\Components\WC_Account\is_team_member;

$user = \wp_get_current_user();

$endpoints = \wc_get_account_menu_items();
unset($endpoints['customer-logout']);

?>

<div class="mb-account">

    <header class="mb-account__masthead">
        <div class="mb-account__masthead-title">
            <span class="mb-account__rule" aria-hidden="true"></span>
            <h1 class="mb-account__heading"><?php esc_html_e('My account', 'granola'); ?></h1>
        </div>

        <?php if ($user->exists()) : ?>
            <div class="mb-account__identity">
                <span class="mb-account__identity-label"><?php esc_html_e('Signed in as', 'granola'); ?></span>
                <span class="mb-account__identity-value"><?php echo esc_html($user->user_email); ?></span>
            </div>
        <?php endif; ?>
    </header>

    <div class="mb-account__body">

        <nav class="mb-account__nav" aria-label="<?php esc_attr_e('Account pages', 'granola'); ?>">
            <ul class="mb-account__nav-list">
                <?php foreach ($endpoints as $endpoint => $label) :
                    $is_current = wc_is_current_account_menu_item($endpoint);
                    ?>
                    <li class="mb-account__nav-item<?php echo $is_current ? ' is-current' : ''; ?>">
                        <a
                            class="mb-account__nav-link"
                            href="<?php echo esc_url(wc_get_account_endpoint_url($endpoint)); ?>"
                            <?php echo $is_current ? 'aria-current="page"' : ''; ?>
                        >
                            <span class="mb-account__nav-bar" aria-hidden="true"></span>
                            <span class="mb-account__nav-label"><?php echo esc_html($label); ?></span>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>

            <a class="mb-account__logout" href="<?php echo esc_url(wc_logout_url(wc_get_page_permalink('myaccount'))); ?>">
                <?php esc_html_e('Log out', 'granola'); ?>
                <span class="mb-account__logout-chevron" aria-hidden="true">&rsaquo;</span>
            </a>
        </nav>

        <div class="mb-account__main">
            <?php do_action('woocommerce_account_content'); ?>
        </div>

    </div>

</div>
