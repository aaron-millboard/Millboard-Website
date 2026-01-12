<?php

// get endpoint slug
$endpoints = wc_get_account_menu_items();
$current_endpoint = '';

// exclude logout from endpoints
unset($endpoints['customer-logout']);
// exclude downloads if disabled
unset($endpoints['downloads']);

foreach ($endpoints as $endpoint => $label) {
    if (wc_is_current_account_menu_item($endpoint)) {
        $current_endpoint = $endpoint;
        break;
    }
}

?>

<div class="account">

    <nav class="account__nav" aria-label="<?php esc_html_e('Account pages', 'woocommerce'); ?>">
        <ul class="account__nav__items">
            <?php foreach ($endpoints as $endpoint => $label) : ?>
                <li class="account__nav__item<?php echo wc_is_current_account_menu_item($endpoint) ? ' account__nav__item--active' : ''; ?>">
                    <a href="<?php echo esc_url(wc_get_account_endpoint_url($endpoint)); ?>" <?php echo wc_is_current_account_menu_item($endpoint) ? 'aria-current="page"' : ''; ?>>
                        <?php echo esc_html($label); ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>

        <a href="<?php echo esc_url(wp_logout_url(home_url())); ?>" class="account__nav__logout">
            <?php esc_html_e('Logout', 'granola'); ?>
        </a>
    </nav>

    <div class="account__content account__content--<?php echo esc_attr($current_endpoint); ?>">
        <?php do_action('woocommerce_account_content'); ?>
    </div>

</div>