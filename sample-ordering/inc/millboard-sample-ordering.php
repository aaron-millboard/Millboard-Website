<?php
/**
 * Millboard Internal Sample Ordering — WooCommerce My Account tab.
 *
 * A bulk sample-ordering grid: 22 category accordions over the site's own
 * WooCommerce sample products, with quantity steppers and search. Submitting
 * adds every selected line to the WooCommerce basket and sends the user to the
 * cart, so checkout, fulfilment and order records are all WooCommerce's.
 *
 * Load this from the child theme's functions.php:
 *
 *     require_once get_stylesheet_directory() . '/inc/millboard-sample-ordering.php';
 *
 * @package Millboard
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Including this file twice would re-declare the functions below, which is a
// fatal error. Bail out rather than trusting every caller to use require_once.
if ( defined( 'MB_SOF_LOADED' ) ) {
	return;
}
define( 'MB_SOF_LOADED', true );

/**
 * ─────────────────────────────────────────────────────────────────────────────
 * CONFIGURE ME
 *
 * Every constant is wrapped in a defined() check, so any of them can instead
 * be set in wp-config.php and this file left untouched.
 * ─────────────────────────────────────────────────────────────────────────────
 */

/**
 * Optional: slug of a WooCommerce product category holding the sample
 * products. Set this and the catalogue query is restricted to that category,
 * which is faster and far more precise than the name-based fallback.
 *
 * Left empty, products are matched by name — anything containing "sample",
 * plus the two brochure SKUs whitelisted in mb-sof-catalogue.php.
 */
if ( ! defined( 'MB_SOF_PRODUCT_CAT' ) ) {
	define( 'MB_SOF_PRODUCT_CAT', '' );
}

if ( ! defined( 'MB_SOF_VERSION' ) ) {
	define( 'MB_SOF_VERSION', '2.0.0' );
}

/** URL slug of the tab: /my-account/sample-ordering/ */
if ( ! defined( 'MB_SOF_ENDPOINT' ) ) {
	define( 'MB_SOF_ENDPOINT', 'sample-ordering' );
}

/** Label shown in the My Account menu and as the tab heading. */
if ( ! defined( 'MB_SOF_LABEL' ) ) {
	define( 'MB_SOF_LABEL', 'Sample Ordering' );
}

require_once __DIR__ . '/mb-sof-catalogue.php';
require_once __DIR__ . '/mb-sof-basket.php';

/**
 * Who may see the tab and submit an order.
 *
 * Every logged-in user by default. On a WooCommerce site that means every
 * customer with an account, which is almost certainly too broad for an
 * internal tool — restrict it from functions.php:
 *
 *     add_filter( 'mb_sof_user_can_order', function () {
 *         return current_user_can( 'edit_shop_orders' );
 *     } );
 *
 * @return bool
 */
function mb_sof_user_can_order() {
	return (bool) apply_filters( 'mb_sof_user_can_order', is_user_logged_in() );
}

/**
 * Register the rewrite endpoint, and flush the rules once per version.
 *
 * A child theme has no activation hook, so the flush is guarded by an option
 * rather than run on every request — flush_rewrite_rules() is expensive.
 */
function mb_sof_add_endpoint() {
	add_rewrite_endpoint( MB_SOF_ENDPOINT, EP_ROOT | EP_PAGES );

	if ( get_option( 'mb_sof_rewrite_version' ) !== MB_SOF_VERSION ) {
		flush_rewrite_rules( false );
		update_option( 'mb_sof_rewrite_version', MB_SOF_VERSION );
	}
}
add_action( 'init', 'mb_sof_add_endpoint' );

/**
 * Tell WooCommerce the query var exists, otherwise the endpoint 404s.
 *
 * @param array $vars Query vars.
 * @return array
 */
function mb_sof_query_vars( $vars ) {
	$vars[ MB_SOF_ENDPOINT ] = MB_SOF_ENDPOINT;
	return $vars;
}
add_filter( 'woocommerce_get_query_vars', 'mb_sof_query_vars', 0 );

/**
 * Insert the menu item, keeping "Logout" last.
 *
 * @param array $items Existing menu items.
 * @return array
 */
function mb_sof_account_menu_items( $items ) {
	if ( ! mb_sof_user_can_order() ) {
		return $items;
	}

	$logout = isset( $items['customer-logout'] ) ? $items['customer-logout'] : null;
	unset( $items['customer-logout'] );

	$items[ MB_SOF_ENDPOINT ] = MB_SOF_LABEL;

	if ( null !== $logout ) {
		$items['customer-logout'] = $logout;
	}

	return $items;
}
add_filter( 'woocommerce_account_menu_items', 'mb_sof_account_menu_items' );

/**
 * Heading WooCommerce prints above the tab content.
 *
 * @return string
 */
function mb_sof_endpoint_title() {
	return MB_SOF_LABEL;
}
add_filter( 'woocommerce_endpoint_' . MB_SOF_ENDPOINT . '_title', 'mb_sof_endpoint_title' );

/**
 * Register assets. Nothing is enqueued until the form actually renders.
 */
function mb_sof_register_assets() {
	/*
	 * MILLBOARD EDIT — the only change to this file from the IT handover.
	 *
	 * Upstream this reads `/assets/millboard-sample-ordering/`. In this theme
	 * `assets/` is webpack's output directory: it is gitignored, and the build
	 * runs with `clean: true`, so anything placed there is deleted on the next
	 * build and never reaches a deploy. The whole package therefore lives in
	 * `sample-ordering/` instead, which is committed and left alone.
	 *
	 * Keep this edit when taking a new drop from IT; nothing else in the
	 * package needs changing, the sibling `inc/` and `template-parts/` paths
	 * are resolved with __DIR__ and travel fine.
	 */
	$base = get_stylesheet_directory_uri() . '/sample-ordering/assets/';
	$path = get_stylesheet_directory() . '/sample-ordering/assets/';

	$ver = static function ( $file ) use ( $path ) {
		return file_exists( $path . $file ) ? filemtime( $path . $file ) : MB_SOF_VERSION;
	};

	wp_register_style(
		'mb-sof-fonts',
		'https://fonts.googleapis.com/css2?family=Archivo:wght@400;500;600;700&family=Hanken+Grotesk:wght@400;500;600&display=swap',
		array(),
		null
	);
	wp_register_style( 'mb-sof', $base . 'sample-ordering.css', array( 'mb-sof-fonts' ), $ver( 'sample-ordering.css' ) );
	wp_register_script( 'mb-sof', $base . 'sample-ordering.js', array(), $ver( 'sample-ordering.js' ), true );
}
add_action( 'wp_enqueue_scripts', 'mb_sof_register_assets' );

/**
 * Render the sample ordering form.
 *
 * Also usable directly from any template:
 *
 *     if ( function_exists( 'mb_sof_render' ) ) { mb_sof_render(); }
 */
function mb_sof_render() {
	if ( ! mb_sof_user_can_order() ) {
		echo '<p>' . esc_html__( 'You do not have access to sample ordering.', 'millboard' ) . '</p>';
		return;
	}

	if ( ! function_exists( 'WC' ) ) {
		echo '<p>' . esc_html__( 'WooCommerce is not active, so samples cannot be ordered.', 'millboard' ) . '</p>';
		return;
	}

	$catalogue = mb_sof_get_catalogue();

	if ( empty( $catalogue ) ) {
		echo '<p>' . esc_html__( 'No sample products were found in the catalogue.', 'millboard' ) . '</p>';
		if ( current_user_can( 'manage_woocommerce' ) ) {
			echo '<p><em>' . esc_html__( 'Admin note: check that sample products are published, and that MB_SOF_PRODUCT_CAT matches a real product category slug.', 'millboard' ) . '</em></p>';
		}
		return;
	}

	wp_enqueue_style( 'mb-sof' );
	wp_enqueue_script( 'mb-sof' );

	wp_add_inline_script(
		'mb-sof',
		'window.MB_SOF_DATA = ' . wp_json_encode(
			array(
				'catalogue' => $catalogue,
				'categories' => mb_sof_category_order(),
				'maxQty'    => mb_sof_max_qty(),
				'cartUrl'   => wc_get_cart_url(),
			)
		) . ';',
		'before'
	);

	$show_coverage = current_user_can( 'manage_woocommerce' )
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only diagnostic.
		&& ! empty( $_GET['mb_sof_debug'] );

	include __DIR__ . '/../template-parts/millboard-sample-ordering.php';
}
add_action( 'woocommerce_account_' . MB_SOF_ENDPOINT . '_endpoint', 'mb_sof_render' );

/**
 * Shortcode alias, handy for testing on an ordinary page before the tab goes
 * live: [millboard_sample_ordering]
 *
 * @return string
 */
function mb_sof_shortcode() {
	static $rendered = false;

	if ( $rendered ) {
		// The form uses fixed element ids, so only one instance per page.
		return '<!-- millboard_sample_ordering: already rendered on this page -->';
	}
	$rendered = true;

	ob_start();
	mb_sof_render();
	return ob_get_clean();
}
add_shortcode( 'millboard_sample_ordering', 'mb_sof_shortcode' );

/**
 * Warn in the admin when WooCommerce is missing, rather than leaving someone
 * to work out why the tab never appears.
 */
function mb_sof_admin_notice() {
	if ( class_exists( 'WooCommerce' ) || ! current_user_can( 'activate_plugins' ) ) {
		return;
	}
	echo '<div class="notice notice-error"><p>'
		. esc_html__( 'Millboard Sample Ordering: WooCommerce is not active. The sample ordering tab cannot work without it.', 'millboard' )
		. '</p></div>';
}
add_action( 'admin_notices', 'mb_sof_admin_notice' );
