<?php

namespace Granola\Components\ProductSamples;

/**
 * The maximum number of free samples a visitor may add to the basket.
 */
const MAX_SAMPLES = 3;

/**
 * Whether the AJAX sample basket is switched on for the current site.
 *
 * Set per site on the Product Settings options page so it can be piloted in one
 * market before a wider rollout. Off by default, which leaves the original
 * full-page-reload behaviour untouched.
 *
 * @return bool
 */
function ajax_sample_basket_enabled(): bool
{
    static $enabled = null;

    // This is now read on the add-to-cart path, which runs earlier than template
    // render, so never assume ACF is loaded. Return false without caching, so a
    // premature call cannot pin the answer for the rest of the request.
    if (!\function_exists('get_field')) {
        return false;
    }

    if ($enabled === null) {
        $enabled = (bool) \apply_filters(
            'granola/product_samples/ajax_basket_enabled',
            (bool) \get_field('enable_ajax_sample_basket', 'options')
        );
    }

    return $enabled;
}

/**
 * Whether sample cards should treat adding the sample as the primary action.
 *
 * Hides the "View product" overlay label and hands the card-wide click area to
 * the sample button rather than the heading link. Set per site on the Product
 * Settings options page. Off by default.
 *
 * @return bool
 */
function sample_tile_add_focus(): bool
{
    static $focus = null;

    if ($focus === null) {
        $focus = (bool) \apply_filters(
            'granola/product_samples/sample_tile_add_focus',
            (bool) \get_field('sample_tile_add_focus', 'options')
        );
    }

    return $focus;
}

/**
 * Enqueue and configure the sample basket script.
 *
 * Called from the sample button rather than a component render hook, because on
 * product category archives the buttons are built directly by the card
 * component and never render the product-samples component itself.
 *
 * @return void
 */
function enqueue_assets(): void
{
    static $enqueued = false;

    if ($enqueued || !ajax_sample_basket_enabled()) {
        return;
    }

    $enqueued = true;

    \Granola\Component::enqueue_script_by_filename('product-samples');

    \wp_localize_script('product-samples-scripts', 'granolaProductSamples', [
        'ajaxUrl' => \admin_url('admin-ajax.php'),
        'nonce' => \wp_create_nonce('granola_sample_toggle'),
        'cartUrl' => \function_exists('wc_get_cart_url') ? \wc_get_cart_url() : '',
        'maxSamples' => MAX_SAMPLES,
        'i18n' => [
            'added' => \__('Added to basket', 'granola'),
            // Reuses the same string the server-rendered button uses, so the
            // existing translations apply and the two states stay identical.
            // translators: 1: HTML opening tag. 2: Product place in basket. 3: HTML closing tag.
            'remove' => sprintf(\__('Remove %1$s%2$s/3%3$s', 'granola'), '<strong>', '{position}', '</strong>'),
            'viewBasket' => \__('View basket', 'granola'),
            // translators: 1: Number of samples chosen. 2: Maximum number of samples.
            'chosen' => sprintf(\__('%1$s of %2$s samples chosen', 'granola'), '{count}', MAX_SAMPLES),
            'error' => \__('Sorry, that sample could not be updated. Please try again.', 'granola'),
            // translators: %s: Maximum number of free samples.
            'limit' => sprintf(\__('You have reached the limit of %s free samples', 'granola'), MAX_SAMPLES),
        ],
    ]);
}

/**
 * Map the free samples currently in the cart to their position in the basket.
 *
 * Mirrors the ordering used by the sample button so the "n/3" labels stay
 * consistent between the server-rendered and AJAX-updated states.
 *
 * @return array<int, array{position: int, cart_item_key: string}> Keyed by product/variation ID.
 */
function get_cart_sample_positions(): array
{
    $cart = WC()->cart;

    if (empty($cart)) {
        return [];
    }

    $positions = [];
    $position = 1;

    foreach ($cart->get_cart() as $cart_item_key => $cart_item) {
        $cart_product_obj = get_cart_item_product($cart_item);

        // Only free samples are numbered and capped. Large samples are unlimited.
        if (empty($cart_product_obj) || !\Theme\WooCommerce\Utils::is_free_sample($cart_product_obj)) {
            continue;
        }

        $product_id = $cart_product_obj->get_id();

        $positions[(int) $product_id] = [
            'position' => $position,
            'cart_item_key' => $cart_item_key,
        ];

        $position++;
    }

    return $positions;
}

/**
 * Add or remove a single sample and return the resulting basket state.
 *
 * @return void Responds with JSON and exits.
 */
function ajax_toggle_sample(): void
{
    \check_ajax_referer('granola_sample_toggle', 'nonce');

    if (!ajax_sample_basket_enabled()) {
        \wp_send_json_error(['message' => \__('Not enabled.', 'granola')], 403);
    }

    if (empty(WC()->cart)) {
        \wp_send_json_error(['message' => \__('Basket unavailable.', 'granola')], 500);
    }

    $product_id = isset($_POST['product_id']) ? absint($_POST['product_id']) : 0;
    $toggle = isset($_POST['toggle']) ? \sanitize_key($_POST['toggle']) : '';

    if (empty($product_id) || !in_array($toggle, ['add', 'remove'], true)) {
        \wp_send_json_error(['message' => \__('Invalid request.', 'granola')], 400);
    }

    $product = \wc_get_product($product_id);

    if (empty($product) || !\Theme\WooCommerce\Utils::is_free_sample($product)) {
        \wp_send_json_error(['message' => \__('That product is not a sample.', 'granola')], 400);
    }

    \wc_clear_notices();

    if ($toggle === 'add') {
        // Enforce the limit here rather than relying only on the validation
        // filter. That filter receives the parent product ID, so its
        // is_free_sample() check does not recognise a sample and lets it through.
        if (count(get_cart_sample_positions()) >= MAX_SAMPLES) {
            \wp_send_json_error([
                'message' => sprintf(
                    // translators: %s: Maximum number of samples.
                    \__('You can only add a maximum of %s free samples', 'granola'),
                    MAX_SAMPLES
                ),
                'state' => format_sample_state(),
            ], 409);
        }

        // Pass the variation as the product ID, matching the ?add-to-cart link
        // this replaces, so cart items keep the same shape either way.
        $added = WC()->cart->add_to_cart($product_id, 1);

        if (empty($added)) {
            $notices = \wc_get_notices('error');
            \wc_clear_notices();

            \wp_send_json_error([
                'message' => !empty($notices[0]['notice'])
                    ? \wp_strip_all_tags($notices[0]['notice'])
                    : \__('That sample could not be added.', 'granola'),
                // Send the real state too, so a full basket still reconciles the
                // buttons rather than leaving them out of step.
                'state' => format_sample_state(),
            ], 409);
        }
    } else {
        $existing = get_cart_sample_positions();

        if (empty($existing[$product_id]['cart_item_key'])) {
            \wp_send_json_error(['message' => \__('That sample is not in the basket.', 'granola')], 404);
        }

        WC()->cart->remove_cart_item($existing[$product_id]['cart_item_key']);
    }

    \wc_clear_notices();

    \wp_send_json_success(format_sample_state());
}

/**
 * Describe the current sample basket for the client.
 *
 * @return array{count: int, samples: array<int, int>, full: bool, cartCount: int}
 */
function format_sample_state(): array
{
    $positions = get_cart_sample_positions();
    $cart = WC()->cart;

    return [
        'count' => count($positions),
        'full' => count($positions) >= MAX_SAMPLES,
        // Everything in the basket, not just samples, so the header count can be
        // kept in step without a page load.
        'cartCount' => !empty($cart) ? (int) $cart->get_cart_contents_count() : 0,
        'samples' => array_map(function ($sample) {
            return $sample['position'];
        }, $positions),
    ];
}

function filter_args(array $args): ?array
{
    // -------------------------------------------------------------------------
    // Default arguments.
    // -------------------------------------------------------------------------
    $args = array_merge([
        'classes' => [],
        'product_id' => \get_the_ID(),
        'samples' => [],
    ], $args);

    if (empty($args['product_id'])) {
        return null;
    }

    $product = \wc_get_product($args['product_id']);

    if (!($product instanceof \WC_Product_Variable)) {
        return null;
    }

    $args['samples'] = get_product_samples($product);

    // ---------------------------------------
    // Bail early - return null for no output.
    // ---------------------------------------
    if (empty($args['samples'])) {
        return null;
    }

    // -------------------------------------------------------------------------
    // Required classes.
    // -------------------------------------------------------------------------
    $args['classes'] = array_merge([
        'product-samples',
    ], $args['classes']);

    // -------------------------------------------------------------------------
    // Return the filtered args.
    // -------------------------------------------------------------------------
    return $args;
}

function get_product_samples($wc_product)
{
    $default_product = get_product_default_variation($wc_product);

    // Bail early - no default set/found. Don't show any samples.
    if (empty($default_product)) {
        return [];
    }

    $default_product_id = $default_product->get_id();
    $product_variations = $wc_product->get_available_variations('objects');

    $samples = array_filter($product_variations, function ($variation) use ($default_product_id) {
        return $variation->get_id() !== $default_product_id;
    });

    return array_map(function ($sample) {
        return [
            'product' => $sample,
        ];
    }, $samples);
}

function get_product_default_variation($wc_product)
{
    $default_attributes = $wc_product->get_default_attributes();

    // ->find_matching_product_variation() needs term slugs of matching
    // attributes array to be prefixed with 'attribute_'
    $prefixed_slugs = array_map(function ($pa_name) {
        return 'attribute_' . $pa_name;
    }, array_keys($default_attributes));

    $default_attributes = array_combine($prefixed_slugs, $default_attributes);
    $default_variation_id = ( new \WC_Product_Data_Store_CPT() )->find_matching_product_variation($wc_product, $default_attributes);

    return \wc_get_product($default_variation_id);
}

/**
 * Determine whether a sample product should be added to the basket.
 *
 * @param bool $passed
 * @param integer $product_id Product ID being validated.
 * @param integer $quantity Quantity added to the cart.
 * @return bool True if the item passed validation.
 */
function sample_product_add_to_cart_validation($add_to_cart, $product_id = 0, $qty = 1, $variation_id = 0)
{
    // Do NOT declare parameter types here. WooCommerce fires this filter with
    // three, four or five arguments depending on the handler, and
    // WC_Form_Handler::add_to_cart_handler_variable() passes an empty string for
    // $variation_id when the request carried no variation. An `int` hint makes
    // that a fatal TypeError on every add-to-cart, in every locale.
    $product_id   = \absint($product_id);
    $variation_id = \absint($variation_id);
    $qty          = (int) $qty;

    // Deliberately NOT gated behind the German pilot. The three sample cap is a
    // commercial rule for every locale, and gating it here meant only Germany
    // enforced it. The gate existed solely to contain the 5 Aug 2026 TypeError,
    // which the untyped signature above and the instanceof guard below fix at
    // source. Do not reintroduce it without also finding somewhere else to put
    // the cap.

    // WooCommerce passes the parent ID as $product_id for variable products, so
    // prefer the variation when one is supplied. Without this the check below
    // sees the parent, which is never a free sample, and lets everything through.
    $product = \wc_get_product($variation_id ?: $product_id);

    // wc_get_product() returns false, not null, for an ID it cannot load, and
    // is_free_sample() accepts ?WC_Product, so false would be another TypeError.
    if (!$product instanceof \WC_Product) {
        return $add_to_cart;
    }

    // Bail early - not a free sample. Large samples carry a price and are
    // deliberately unlimited, so only free ones are capped.
    if (!\Theme\WooCommerce\Utils::is_free_sample($product)) {
        return $add_to_cart;
    }

    // Count the number of samples in the cart.
    $sample_count = get_cart_sample_count();

    if ($sample_count + $qty > MAX_SAMPLES) {
        wc_clear_notices();
        \wc_add_notice(
            sprintf(
                // translators: %s: Maximum number of free samples.
                \__('You can only add a maximum of %s free samples', 'granola'),
                MAX_SAMPLES
            ),
            'error'
        );
        $add_to_cart = false;
    }

    return $add_to_cart;
}

/**
 * Count the number of free "sample" products in the cart.
 *
 * A sample product is a variation product that isn't the default variation and has a price of 0.
 *
 * @return integer The number of free "sample" products in the cart.
 */
function get_cart_sample_count(): int
{
    $cart = WC()->cart;

    if (empty($cart)) {
        return 0;
    }

    // Count the number of free samples in the cart.
    return array_reduce($cart->get_cart(), function ($samples_quantity, $cart_item) {
        // Samples added via ?add-to-cart={variation} are stored with the
        // variation as product_id and variation_id 0, so fall back to
        // product_id rather than skipping the item entirely.
        $product = get_cart_item_product($cart_item);

        // Bail early - only free samples count. Large samples carry a price and
        // are deliberately unlimited. is_free_sample() already checks the price,
        // so there is no need to look at line_total.
        if (empty($product) || !\Theme\WooCommerce\Utils::is_free_sample($product)) {
            return $samples_quantity;
        }

        // Carry the quantity of samples.
        return $samples_quantity + (int) ($cart_item['quantity'] ?? 1);
    }, 0);
}

/**
 * Resolve the product a cart item refers to.
 *
 * Sample links pass the variation ID as add-to-cart, which WooCommerce stores as
 * product_id with variation_id 0. Anything added as parent + variation stores
 * both. This handles either shape.
 *
 * @param array $cart_item A WooCommerce cart item.
 * @return \WC_Product|null
 */
function get_cart_item_product(array $cart_item): ?\WC_Product
{
    $id = !empty($cart_item['variation_id']) ? $cart_item['variation_id'] : ($cart_item['product_id'] ?? 0);

    if (empty($id)) {
        return null;
    }

    $product = \wc_get_product($id);

    return $product instanceof \WC_Product ? $product : null;
}
