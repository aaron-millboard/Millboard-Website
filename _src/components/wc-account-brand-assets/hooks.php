<?php

/**
 * Assets -- wiring.
 */

namespace Granola\Components\WC_Account_Brand_Assets;

/*
 * Live search and paging.
 *
 * ⚠️ There is deliberately NO `wp_ajax_nopriv_` twin. The panel is gated to a
 * named allow-list and this endpoint renders the same signed Canto links the
 * panel does, so a logged-out caller must not reach it at all. Registering the
 * nopriv action would hand the library to anyone who could guess the action
 * name, whatever the handler then checked.
 */
\add_action('wp_ajax_' . AJAX_ACTION, __NAMESPACE__ . '\\ajax_page');
