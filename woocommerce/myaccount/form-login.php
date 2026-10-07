<?php

/**
 * Login / register.
 *
 * Overridden from WooCommerce to render the 2026 split-screen design. Both
 * forms, the notices and every nonce live in the component.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 9.9.0
 */

defined('ABSPATH') || exit;

echo \Granola\Component::get('wc-account-auth');
