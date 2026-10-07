<?php

namespace Theme\WooCommerce;

/**
 * An unsubscribe link in the order confirmation, that actually unsubscribes.
 *
 * Compliance, 6 Oct 2026: the partner portal lets a rep enter a customer's
 * details and order samples on their behalf, so the customer receives a
 * confirmation they never asked for. That is acceptable "as long as there is an
 * unsubscribe link on the email the user gets from us to confirm their order".
 *
 * WHY THIS IS BUILT RATHER THAN LINKED TO HUBSPOT.
 * HubSpot's preferences centre is keyed on a token it mints when IT sends an
 * email. The generic URL, with no token, redirects to a fallback that tells the
 * visitor to find a more recent email -- it offers no way to self-serve. So a
 * static link in a WooCommerce email cannot work, and a link carrying somebody
 * else's token would hand every recipient one customer's preferences.
 *
 * The other route, letting HubSpot send the confirmation, gets a correct token
 * for free but makes a transactional receipt depend on the recipient being a
 * marketing contact. An opted-out customer could stop receiving order
 * confirmations, which is a worse failure than the one being fixed.
 *
 * NO PERSONAL DATA IN THE URL. The link carries the order id and WooCommerce's
 * own `order_key`, never the email address, because query strings end up in
 * access logs and referrer headers. The key is already the secret guarding
 * order-received and view-order links, so this grants nothing new: anyone who
 * can read the email can already see the order.
 *
 * GET NEVER UNSUBSCRIBES. Outlook link protection and mail security scanners
 * fetch every URL in a message. A one-click GET would silently unsubscribe
 * people who never touched it, so the link shows a confirmation and only a
 * POST acts.
 */
class EmailUnsubscribe
{
    /** The path the link points at. */
    public const ROUTE = 'email-preferences';

    /** Query var the rewrite rule sets. */
    public const VAR = 'mb_unsubscribe';

    public const NONCE = 'mb_email_unsubscribe';

    /** Where HubSpot records it. The portal's own workflows act on this. */
    public const PROPERTY = 'opt_out_marketing';

    public static function init(): void
    {
        \add_action('init', [__CLASS__, 'add_route']);
        \add_filter('query_vars', [__CLASS__, 'query_vars']);
        \add_action('template_redirect', [__CLASS__, 'maybe_render'], 1);
        \add_filter('woocommerce_email_footer_text', [__CLASS__, 'append_link'], 10, 2);

        // Before WC_Emails::email_footer() at 10, so the email is known by the
        // time the footer template runs. See remember_email().
        \add_action('woocommerce_email_footer', [__CLASS__, 'remember_email'], 1);

        // The header template has the same problem: WC_Emails::email_header()
        // takes only the heading, so `$email` is null there too. Every email
        // template passes the email as the action's SECOND argument, so it can
        // be captured the same way.
        \add_action('woocommerce_email_header', [__CLASS__, 'remember_email_from_header'], 1, 2);
    }

    // --------------------------------------------------- which email is this

    /**
     * The email currently being rendered.
     *
     * WHY THIS EXISTS. `woocommerce_email_footer_text` is documented as taking
     * the email as a second argument, and WooCommerce's own footer template
     * passes one, but it is ALWAYS NULL:
     *
     *     public function email_footer() {
     *         wc_get_template( 'emails/email-footer.php' );   // no args
     *     }
     *
     * Nothing is handed to the template, so core's `$email = $email ?? null`
     * resolves to null and so does ours. A filter relying on that argument
     * silently does nothing on every real send, which is exactly what happened
     * here until the emails were rendered rather than unit-tested.
     *
     * The `woocommerce_email_footer` ACTION does carry the email, because every
     * template passes it. Capturing it at priority 1 means it is available by
     * the time WooCommerce loads the footer template at 10.
     *
     * @var \WC_Email|null
     */
    private static $current_email = null;

    /**
     * @param mixed $email
     */
    public static function remember_email($email = null): void
    {
        self::$current_email = $email instanceof \WC_Email ? $email : null;
    }

    /**
     * The same capture, from the header action, where the email is the SECOND
     * argument. Only overwrites when one is actually supplied, so a template
     * that passes nothing cannot blank out what a previous one set.
     *
     * @param mixed $heading
     * @param mixed $email
     */
    public static function remember_email_from_header($heading = '', $email = null): void
    {
        if ($email instanceof \WC_Email) {
            self::$current_email = $email;
        }
    }

    public static function current_email(): ?\WC_Email
    {
        return self::$current_email;
    }

    // ------------------------------------------------------------- the route

    public static function add_route(): void
    {
        \add_rewrite_rule('^' . self::ROUTE . '/?$', 'index.php?' . self::VAR . '=1', 'top');

        // Flushing on every load would rewrite the option on every request. The
        // signature changes only when the rule does.
        if (\get_option('millboard_unsubscribe_route') !== self::ROUTE) {
            \flush_rewrite_rules(false);
            \update_option('millboard_unsubscribe_route', self::ROUTE, false);
        }
    }

    /**
     * @param array<string> $vars
     * @return array<string>
     */
    public static function query_vars(array $vars): array
    {
        $vars[] = self::VAR;

        return $vars;
    }

    // -------------------------------------------------------------- the link

    /**
     * Append the link to the footer of a customer order email.
     *
     * The filter hands over the email object, which is what makes a per
     * recipient link possible at all -- the footer itself is one global setting
     * shared by every message.
     *
     * Only customer-facing emails that carry an order get a link. The ops
     * notification does not need one, and an email with no order has no key to
     * identify anybody with.
     *
     * @param mixed $text
     * @param mixed $email
     * @return mixed
     */
    public static function append_link($text, $email = null)
    {
        // The filter's own argument is null on every real send, so fall back to
        // the email captured from the footer action. See current_email().
        if (!$email instanceof \WC_Email) {
            $email = self::current_email();
        }

        if (!$email instanceof \WC_Email || !$email->is_customer_email()) {
            return $text;
        }

        $extra = [];

        /*
         * The unsubscribe link is keyed on the order, so only an email that
         * carries one can have it. Account emails -- new account, password
         * reset, the portal launch -- have no order to identify anybody with,
         * and are account administration rather than marketing anyway.
         */
        $order = $email->object ?? null;

        if ($order instanceof \WC_Order && $order->get_billing_email()) {
            $extra[] = \sprintf(
                '<a href="%s" style="color:inherit;">%s</a>',
                \esc_url(self::link($order)),
                \esc_html__('Unsubscribe from marketing emails', 'granola')
            );
        }

        /*
         * The privacy policy is not conditional on an order. It used to sit
         * after an early return, so every account email went out without it --
         * including the portal launch, which goes to people who never signed up
         * and is exactly where it is most warranted.
         */
        $policy = \get_privacy_policy_url();

        if ($policy) {
            $extra[] = '<a href="' . \esc_url($policy) . '" style="color:inherit;">'
                . \esc_html__('Privacy policy', 'granola') . '</a>';
        }

        if (!$extra) {
            return $text;
        }

        return $text . ' | ' . \implode(' | ', $extra);
    }

    public static function link(\WC_Order $order): string
    {
        return \add_query_arg(
            [
                'order' => $order->get_id(),
                'key' => $order->get_order_key(),
            ],
            \home_url('/' . self::ROUTE . '/')
        );
    }

    /**
     * The order a request is for, or null when it does not check out.
     *
     * Compared with hash_equals so a wrong key cannot be narrowed down by
     * timing.
     */
    public static function requested_order(): ?\WC_Order
    {
        // phpcs:disable WordPress.Security.NonceVerification.Recommended -- the order key is the credential; the nonce guards the POST.
        $id = isset($_GET['order']) ? \absint($_GET['order']) : 0;
        $key = isset($_GET['key']) ? \sanitize_text_field(\wp_unslash($_GET['key'])) : '';
        // phpcs:enable

        if (!$id || '' === $key || !\function_exists('wc_get_order')) {
            return null;
        }

        $order = \wc_get_order($id);

        if (!$order instanceof \WC_Order) {
            return null;
        }

        return \hash_equals($order->get_order_key(), $key) ? $order : null;
    }

    // ------------------------------------------------------------- rendering

    public static function maybe_render(): void
    {
        if (!\get_query_var(self::VAR)) {
            return;
        }

        $order = self::requested_order();
        $state = 'invalid';

        if ($order instanceof \WC_Order) {
            $state = self::already_done($order) ? 'already' : 'confirm';

            if (self::is_submission()) {
                $state = self::unsubscribe($order) ? 'done' : 'error';
            }
        }

        // A virtual page: without this WordPress serves it as a 404 and search
        // engines are told the confirmation does not exist.
        global $wp_query;
        $wp_query->is_404 = false;
        \status_header(200);
        \nocache_headers();

        // Never let this be indexed. It is a one-off action page keyed to an
        // order, and it should not turn up in a search result.
        \add_filter('wp_robots', 'wp_robots_no_robots');

        // Without this the document title falls back to whatever the main query
        // happened to resolve to, which is the blog.
        \add_filter('pre_get_document_title', static function () use ($state): string {
            $title = 'done' === $state
                ? \__('You have been unsubscribed', 'granola')
                : \__('Email preferences', 'granola');

            return $title . ' | ' . \get_bloginfo('name');
        });

        self::render($state, $order);
        exit;
    }

    private static function is_submission(): bool
    {
        if ('POST' !== ($_SERVER['REQUEST_METHOD'] ?? '')) {
            return false;
        }

        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- wp_verify_nonce sanitises.
        $nonce = isset($_POST['_wpnonce']) ? \wp_unslash($_POST['_wpnonce']) : '';

        return (bool) \wp_verify_nonce($nonce, self::NONCE);
    }

    private static function render(string $state, ?\WC_Order $order): void
    {
        $template = \locate_template('template-parts/email-preferences.php');

        // The design pass owns the markup. Until it lands, this renders a plain
        // but complete page rather than nothing, because a dead unsubscribe
        // link is the one outcome Compliance cannot have.
        if ($template) {
            \get_header();
            include $template;
            \get_footer();

            return;
        }

        \get_header();

        $masked = $order instanceof \WC_Order ? self::mask($order->get_billing_email()) : '';

        echo '<div class="blocks"><div style="max-width:40rem;margin:4rem auto;padding:0 1rem;">';

        switch ($state) {
            case 'confirm':
                echo '<h1>' . \esc_html__('Unsubscribe from marketing emails', 'granola') . '</h1>';
                /* translators: %s: partially hidden email address. */
                echo '<p>' . \sprintf(\esc_html__('This will stop Millboard sending marketing emails to %s. You will still receive emails about orders you place.', 'granola'), '<strong>' . \esc_html($masked) . '</strong>') . '</p>';
                echo '<form method="post">';
                \wp_nonce_field(self::NONCE);
                echo '<button type="submit" class="btn-primary">' . \esc_html__('Confirm unsubscribe', 'granola') . '</button>';
                echo '</form>';
                break;

            case 'done':
                echo '<h1>' . \esc_html__('You have been unsubscribed', 'granola') . '</h1>';
                /* translators: %s: partially hidden email address. */
                echo '<p>' . \sprintf(\esc_html__('Millboard will no longer send marketing emails to %s. Emails about orders you place will still be sent.', 'granola'), '<strong>' . \esc_html($masked) . '</strong>') . '</p>';
                break;

            case 'already':
                echo '<h1>' . \esc_html__('Already unsubscribed', 'granola') . '</h1>';
                echo '<p>' . \esc_html__('This address is already opted out of marketing emails. Nothing further is needed.', 'granola') . '</p>';
                break;

            case 'error':
                echo '<h1>' . \esc_html__('We could not complete that', 'granola') . '</h1>';
                echo '<p>' . \esc_html__('Something went wrong on our side and your preference was not saved. Please email us and we will do it for you.', 'granola') . '</p>';
                echo '<p><a href="mailto:' . \esc_attr(self::contact_address()) . '">' . \esc_html(self::contact_address()) . '</a></p>';
                break;

            default:
                echo '<h1>' . \esc_html__('This link is no longer valid', 'granola') . '</h1>';
                echo '<p>' . \esc_html__('Use the link from a recent Millboard email, or email us and we will update your preferences.', 'granola') . '</p>';
                echo '<p><a href="mailto:' . \esc_attr(self::contact_address()) . '">' . \esc_html(self::contact_address()) . '</a></p>';
        }

        echo '</div></div>';

        \get_footer();
    }

    /**
     * Enough of the address to recognise, not enough to harvest.
     */
    public static function mask(string $email): string
    {
        $at = \strrpos($email, '@');

        if (false === $at || $at < 1) {
            return '';
        }

        $name = \substr($email, 0, $at);
        $domain = \substr($email, $at);

        return \mb_substr($name, 0, 1) . \str_repeat('*', \max(1, \mb_strlen($name) - 1)) . $domain;
    }

    private static function contact_address(): string
    {
        return (string) \apply_filters('millboard_unsubscribe_contact', \get_option('woocommerce_email_from_address', ''));
    }

    // -------------------------------------------------------------- the deed

    private static function already_done(\WC_Order $order): bool
    {
        return 'yes' === $order->get_meta('_millboard_unsubscribed');
    }

    /**
     * Record the opt-out in HubSpot.
     *
     * Writes the same property the portal's own workflows already act on,
     * rather than opting out of a hardcoded list of subscription ids: HubSpot
     * holds the list, "Sets Marketing Contact and Subscription for Orders"
     * knows what to do with it, and a list copied into theme code would rot the
     * first time marketing adds a subscription.
     */
    public static function unsubscribe(\WC_Order $order): bool
    {
        $email = $order->get_billing_email();

        if (!$email || !\is_email($email)) {
            return false;
        }

        $token = \defined('MILLBOARD_HUBSPOT_TOKEN') ? (string) \constant('MILLBOARD_HUBSPOT_TOKEN') : '';

        if ('' === $token) {
            self::log('no MILLBOARD_HUBSPOT_TOKEN defined, cannot record the opt-out');

            return false;
        }

        $response = \wp_remote_request(
            'https://api.hubapi.com/crm/v3/objects/contacts/' . \rawurlencode($email) . '?idProperty=email',
            [
                'method' => 'PATCH',
                'timeout' => 15,
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                    'Content-Type' => 'application/json',
                ],
                'body' => \wp_json_encode([
                    'properties' => [
                        self::PROPERTY => 'true',
                    ],
                ]),
            ]
        );

        if (\is_wp_error($response)) {
            self::log('request failed: ' . $response->get_error_message());

            return false;
        }

        $code = (int) \wp_remote_retrieve_response_code($response);

        if ($code < 200 || $code > 299) {
            self::log('HubSpot returned ' . $code . ': ' . \wp_remote_retrieve_body($response));

            return false;
        }

        // So a repeat visit says "already done" rather than calling out again,
        // and so there is a record on the order that this was asked for.
        $order->update_meta_data('_millboard_unsubscribed', 'yes');
        $order->update_meta_data('_millboard_unsubscribed_at', \gmdate('c'));
        $order->save();

        $order->add_order_note(\__('Customer unsubscribed from marketing emails using the link in their order email.', 'granola'));

        return true;
    }

    private static function log(string $message): void
    {
        if (\function_exists('wc_get_logger')) {
            \wc_get_logger()->warning($message, ['source' => 'millboard-unsubscribe']);
        }
    }
}
