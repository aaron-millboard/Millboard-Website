<?php

namespace Theme\WooCommerce;

/**
 * Stops a checkout being turned into a dead order when the browser has not attached
 * any payment details for a Stripe gateway.
 *
 * ## The problem this exists to solve
 *
 * `WC_Stripe_UPE_Payment_Gateway::prepare_payment_information_from_request()` throws
 * "Payment method ID is missing from the request." when the shopper is not paying with
 * a saved token and neither `wc-stripe-payment-method` nor `wc-stripe-confirmation-token`
 * arrived in the POST. By the time that throw happens WooCommerce has ALREADY created
 * the order, so the outcome is an order stuck at Failed, a "Failed order" e-mail, and a
 * customer looking at "Your payment details were not submitted" with no idea what to do.
 * They cannot retry into the same order, and measured over September 2026 only 6 of 43
 * such customers on en-us ever came back and completed a purchase.
 *
 * The plugin's own comment on that throw lists the causes, and none of them are the
 * shopper's fault or recoverable after the fact: "it is also reached when Stripe.js
 * fails to load, when the payment element is remounting, and on tampered requests."
 *
 * ## What this does
 *
 * Runs the SAME test the gateway runs, but on `woocommerce_after_checkout_validation`,
 * which fires inside `WC_Checkout::process_checkout()` BEFORE `create_order()`. So the
 * identical situation produces a normal checkout validation error instead of a dead
 * order: nothing is created, no e-mail is sent, and the shopper stays on the checkout
 * with their basket and their typed details intact and can complete the payment field
 * and press the button again.
 *
 * ## Why this cannot make things worse
 *
 * The condition below is deliberately a SUBSET of the gateway's. Every request it
 * blocks is one the gateway was certain to throw on a moment later, so no checkout that
 * can currently succeed is affected. It is narrowed further than the gateway in two
 * places (express checkout, and a missing cart), both of which fail OPEN. When changing
 * anything here, keep that direction: this may only ever be narrower than
 * `prepare_payment_information_from_request()`, never broader.
 *
 * ⚠️ This is a safety net, not the cure. It converts a silent lost order into a visible,
 * recoverable error. It does NOT make the payment element load for shoppers whose
 * browser is not rendering it, which on en-us is concentrated in the Facebook and
 * Instagram in-app browsers.
 *
 * @see https://plugins.svn.wordpress.org/woocommerce-gateway-stripe/tags/11.0.0/includes/payment-methods/class-wc-stripe-upe-payment-gateway.php
 */
class StripePaymentGuard
{
    /**
     * Log source. One file, low volume (single figures a day across the network), and
     * worth keeping: it is the only record that the guard fired, because a blocked
     * checkout leaves no order behind to count.
     */
    private const LOG_SOURCE = 'mb-stripe-payment-guard';

    public static function init(): void
    {
        \add_action('woocommerce_after_checkout_validation', [__CLASS__, 'validate'], 10, 2);
    }

    /**
     * @param array<string, mixed> $data   Posted checkout data, already sanitised by WooCommerce.
     * @param \WP_Error            $errors Checkout errors. Adding to this stops order creation.
     */
    public static function validate($data, $errors): void
    {
        if (!$errors instanceof \WP_Error) {
            return;
        }

        // Another validator has already failed the checkout. Adding a second message
        // about payment details would just be noise on top of "Billing postcode is a
        // required field", and the shopper has to fix that one first regardless.
        if ($errors->has_errors()) {
            return;
        }

        if (!self::gateway_will_reject((array) $data)) {
            return;
        }

        self::log_blocked((array) $data);

        $errors->add(
            'mb_stripe_payment_details_missing',
            \__(
                'Your payment details were not completed, so your order has not been placed and you have not been charged. Please enter your card details in the payment section above and select Place order again. If the payment section is empty, reload this page and try once more.',
                'millboard'
            )
        );
    }

    /**
     * True when `prepare_payment_information_from_request()` is certain to throw
     * "Payment method ID is missing from the request." for this request.
     *
     * Mirrors the gateway exactly. Each early `return false` below is a case where the
     * gateway would NOT throw, or where we cannot be sure, and so must be let through.
     *
     * @param array<string, mixed> $data Posted checkout data.
     */
    private static function gateway_will_reject(array $data): bool
    {
        $payment_method = self::chosen_gateway($data);

        // Not a Stripe gateway, so none of this applies. Matched on the `stripe` prefix
        // because the check is shared by every Stripe gateway (stripe, stripe_klarna,
        // stripe_affirm, stripe_link and the rest), exactly as the gateway's own
        // `get_selected_payment_method_type_from_request()` does.
        if (strpos($payment_method, 'stripe') !== 0) {
            return false;
        }

        // Nothing to pay means the gateway is never asked to prepare a payment. A free
        // sample basket must not be blocked. Fails open when the cart is unavailable.
        $cart = \function_exists('WC') && \WC() ? \WC()->cart : null;
        if (!$cart || !$cart->needs_payment()) {
            return false;
        }

        // Saved card. The gateway takes the token branch and never reaches the throw.
        // Key and 'new' comparison copied from
        // `WC_Stripe_Payment_Gateway::is_using_saved_payment_method()`.
        $token_key = 'wc-' . $payment_method . '-payment-token';
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- WooCommerce verifies the checkout nonce before this hook.
        $token = isset($_POST[$token_key]) ? \wc_clean(\wp_unslash($_POST[$token_key])) : null;
        if ($token !== null && $token !== 'new') {
            return false;
        }

        // Apple Pay / Google Pay / Amazon Pay hand the payment over by a different route.
        // Not enabled on any Millboard locale today, so this is untested here and is
        // deliberately a fail-open: if one is ever switched on, the guard steps aside
        // rather than blocking a payment method it does not understand.
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- see above.
        if (!empty($_POST['express_checkout_type']) || !empty($_POST['express_payment_type'])) {
            return false;
        }

        // The two fields the gateway accepts as proof that the browser attached a
        // payment. Either one present means it will not throw.
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- see above.
        $payment_method_id = \sanitize_text_field(\wp_unslash($_POST['wc-stripe-payment-method'] ?? ''));
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- see above.
        $confirmation_token = $_POST['wc-stripe-confirmation-token'] ?? '';

        return '' === \trim($payment_method_id) && empty($confirmation_token);
    }

    /**
     * The gateway the shopper chose.
     *
     * Prefers WooCommerce's own parsed value over the raw POST: by this hook it has
     * already been through `get_posted_data()`. Falls back to the POST key the gateway
     * itself reads so the two can never disagree about which gateway is being tested.
     *
     * @param array<string, mixed> $data Posted checkout data.
     */
    private static function chosen_gateway(array $data): string
    {
        if (!empty($data['payment_method']) && \is_string($data['payment_method'])) {
            return $data['payment_method'];
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- WooCommerce verifies the checkout nonce before this hook.
        return isset($_POST['payment_method']) ? \wc_clean(\wp_unslash($_POST['payment_method'])) : '';
    }

    /**
     * Record that a checkout was stopped.
     *
     * Deliberately records no personal data: the point is to count these and to see
     * which locale and gateway they land on, not to identify the shopper. The user
     * agent is the exception and is kept because the whole reason this guard exists is
     * that the failures cluster in particular browsers, and without it we would be back
     * to inferring that from orders that no longer get created.
     *
     * @param array<string, mixed> $data Posted checkout data.
     */
    private static function log_blocked(array $data): void
    {
        if (!\function_exists('wc_get_logger')) {
            return;
        }

        \wc_get_logger()->warning(
            'Checkout blocked before order creation: no Stripe payment method in the request.',
            [
                'source' => self::LOG_SOURCE,
                'gateway' => self::chosen_gateway($data),
                'blog_id' => \function_exists('get_current_blog_id') ? \get_current_blog_id() : 0,
                'user_agent' => isset($_SERVER['HTTP_USER_AGENT'])
                    ? \substr(\sanitize_text_field(\wp_unslash($_SERVER['HTTP_USER_AGENT'])), 0, 255)
                    : '',
            ]
        );
    }
}
