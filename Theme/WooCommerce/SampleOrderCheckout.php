<?php

namespace Theme\WooCommerce;

/**
 * Finish a sample order inside My Account, instead of in the shop basket.
 *
 * Aaron, 6 Oct 2026: "can it not keep them in the my account page and check
 * them out there instead of redirecting to the basket page on the site?"
 *
 * WHY THIS BYPASSES THE BASKET ENTIRELY RATHER THAN TIDYING THE CHECKOUT.
 * These orders are always £0 and never take payment, so everything the shop
 * checkout exists to do — payment, shipping rates, tax, order essentials,
 * terms — is machinery this does not need. Going around it is less code than
 * suppressing it, and it fixes something the basket route got wrong anyway: a
 * rep's sample selection used to land in their OWN shopping basket, mixed in
 * with whatever they were buying. Nothing here touches WC()->cart.
 *
 * TWO STEPS, ONE URL. The catalogue posts to itself and this renders the
 * details step in place of it, so the selection never has to be stashed
 * anywhere and a refresh cannot half-place an order.
 *
 * FIELD REUSE. Three questions already exist on the shop checkout and are read
 * from it at run time rather than copied, so the options cannot drift apart:
 * `project-start-time`, `project-size` and `website_persona`.
 *
 * ⚠️ `website_persona` is Aaron's explicit choice for "this project is to be
 * installed at", and it carries the checkout's options (My Own Home,
 * Hospitality, Leisure, Other, Residential Development), NOT the old portal's
 * (My Home, My Client's Home, A Commercial Project). There is no "My Client's
 * Home" equivalent, so expect reps to reach for "Other".
 *
 * ADDRESS LOOKUP comes free. WooCommerce Address Validation (Addressy, which is
 * Loqate) already enqueues on account pages — `is_account_page()` is in its own
 * condition — and its script binds by standard field id. So the address inputs
 * are named exactly as WooCommerce names them, including the country select the
 * Addressy init reads to drive its search. No second key, nothing to configure.
 */
class SampleOrderCheckout
{
    public const NONCE = 'mb_sof_checkout';

    /** Order meta written by this form. */
    public const META = [
        'follow_up' => '_millboard_sample_follow_up',
        'on_behalf_of' => '_millboard_sample_on_behalf_of',
        'sales_comments' => '_millboard_sample_sales_comments',
    ];

    /** Checkout fields reused verbatim, so the options cannot drift. */
    public const REUSED = ['website_persona', 'project-size', 'project-start-time'];

    /**
     * The sample marker, stored in ENGLISH and translated on display.
     *
     * It used to be written as __('Sample order') => __('Sent by the Millboard
     * team'), which froze whatever locale the rep happened to be in INTO the
     * order record. A German customer then read English in their confirmation
     * because the rep placing it was on en-gb, and no later translation could
     * reach it: the words were data by then, not a string.
     *
     * Stored flat and translated by the display filters in init() instead, so
     * the 821 orders already carrying the English literal come out in the
     * reader's language too. Checked 2 Nov 2026: every stored row is this
     * literal, 818 on en-gb and 3 on de-de, so nothing is left behind.
     *
     * ⚠️ DO NOT wrap these in __() at the point of writing. That is the bug.
     */
    public const SAMPLE_MARKER_KEY = 'Sample order';
    public const SAMPLE_MARKER_VALUE = 'Sent by the Millboard team';

    /** Who actually placed it. Not billing, because billing is the customer. */
    public const META_PLACED_BY = '_millboard_sample_placed_by';
    public const META_PLACED_BY_EMAIL = '_millboard_sample_placed_by_email';

    /**
     * The follow-up answer in HubSpot's own vocabulary.
     *
     * `no_follow_up` is an enumeration of exactly "Checked" and "Not Checked",
     * and it means the OPPOSITE of what the form asks, so "Yes" and "No" are
     * both an invalid value AND the wrong way round. Translating here rather
     * than leaving it to whoever wires the mapping means the feed can point
     * straight at this key and be right.
     */
    public const META_NO_FOLLOW_UP = '_millboard_sample_no_follow_up';

    /**
     * Marketing opt-OUT, under the key the shop checkout already writes.
     *
     * Ticked means "do not market to me". Named opt-IN by the Checkout Field
     * Editor, which is a misnomer this follows rather than fixes, because the
     * key is what the HubSpot feeds are mapped against and 7,085 existing
     * orders use it.
     */
    public const MARKETING_OPT_OUT = 'marketing-opt-in';

    /**
     * Addresses that are ours, not a customer's.
     *
     * Aaron, 25 Nov 2026: staff were entering their own address when they had
     * no address for the customer. That is not a tidiness problem. CRM Perks
     * feed #8165 keys the HubSpot contact on `_billing_email`, so a rep's own
     * address overwrites the REP's contact record with the customer's name.
     *
     * ⚠️ `.co.uk` AND `.com`. 75 network users are on millboard.com and 47
     * on millboard.co.uk, and several reps hold one of each, so blocking only
     * the .com leaves the obvious way round it open. Checked on production,
     * 25 Nov 2026.
     *
     * millboardcayman.ky and sgc-agency.com also appear among users but are a
     * distributor and an agency rather than us, so they are left alone.
     */
    public const INTERNAL_EMAIL_DOMAINS = [
        'millboard.com',
        'millboard.co.uk',
        'elmdenegroup.co.uk',
    ];

    /**
     * Shortest and longest run of digits a real number can be.
     *
     * 15 is E.164's hard ceiling for an international number. 9 is the
     * shortest realistic national number across the markets this form runs
     * in (UK, Ireland, France, Germany, US, Australia), so it rejects "." and
     * "12345" without rejecting anybody's actual landline.
     */
    public const PHONE_MIN_DIGITS = 9;
    public const PHONE_MAX_DIGITS = 15;

    /**
     * The one-use token each rendering of the details form carries, and the
     * option-name prefix its claim is kept under. See claim_submission().
     */
    private const CLAIM_PREFIX = 'mb_sof_claim_';
    private const TOKEN_PATTERN = '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/';

    public static function init(): void
    {
        \add_action('init', [__CLASS__, 'take_over_submit'], 20);
        \add_action('template_redirect', [__CLASS__, 'maybe_place'], 5);
        \add_filter('woocommerce_email_recipient_customer_processing_order', [__CLASS__, 'mail_customer_and_rep'], 10, 2);
        \add_filter('woocommerce_email_headers', [__CLASS__, 'reply_to_the_rep'], 10, 3);
        \add_action('woocommerce_email_order_meta', [__CLASS__, 'email_sales_comments'], 10, 4);

        /*
         * Turn the Addressy/Loqate lookup on for this one screen.
         *
         * The plugin gates its whole front end on is_validation_required(),
         * which is checkout and edit-address only, so simply naming the inputs
         * the WooCommerce way was not enough: nothing was enqueued at all. It
         * offers this filter for exactly this, so the existing key, the
         * existing field mapping and the existing postcode lookup all come
         * across with two lines instead of a second Loqate integration.
         */
        \add_filter('wc_address_validation_validation_required', [__CLASS__, 'enable_address_lookup']);

        /*
         * Translate the sample marker when it is SHOWN, not when it is stored.
         *
         * Aaron, 2 Nov 2026: "sample order send by the millboard team needs
         * fixing too", on a German order confirmation.
         *
         * Display-time is what makes this reach orders already placed. The
         * words sit in wc_order_itemmeta as a key and a value, so an order
         * written last month cannot be re-translated at the source; it can
         * only be re-read.
         */
        \add_filter('woocommerce_order_item_display_meta_key', [__CLASS__, 'translate_marker_key'], 10, 2);
        \add_filter('woocommerce_order_item_display_meta_value', [__CLASS__, 'translate_marker_value'], 10, 2);
    }

    /**
     * @param mixed $display_key
     * @param mixed $meta
     * @return mixed
     */
    public static function translate_marker_key($display_key, $meta = null)
    {
        return self::SAMPLE_MARKER_KEY === $display_key
            ? \__('Sample order', 'granola')
            : $display_key;
    }

    /**
     * @param mixed $display_value
     * @param mixed $meta
     * @return mixed
     */
    public static function translate_marker_value($display_value, $meta = null)
    {
        /*
         * wc_display_item_meta runs the value through wpautop, so by the time
         * it reaches here it can be wrapped in a paragraph. Compare on the
         * trimmed, tag-stripped text and hand back a bare string, which is
         * what the unwrapped value was anyway.
         */
        $plain = \trim(\wp_strip_all_tags((string) $display_value));

        return self::SAMPLE_MARKER_VALUE === $plain
            ? \__('Sent by the Millboard team', 'granola')
            : $display_value;
    }

    /**
     * @param mixed $required
     * @return bool
     */
    public static function enable_address_lookup($required): bool
    {
        return (bool) $required || self::is_details_request();
    }

    /**
     * Stop the package sending the selection to the basket.
     *
     * On init at 20, because the package registers its handler when its file
     * loads and that has to have happened first.
     */
    public static function take_over_submit(): void
    {
        \remove_action('template_redirect', 'mb_sof_maybe_handle_submit');
    }

    // ------------------------------------------------------------- routing

    private static function posted_action(): string
    {
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- verified before anything is acted on.
        return isset($_POST['mb_sof_action']) ? \sanitize_text_field(\wp_unslash($_POST['mb_sof_action'])) : '';
    }

    /**
     * Has the catalogue just been submitted, with something in it?
     *
     * Asked by the wc-account component, which is the only thing rendering this
     * tab: it calls remove_all_actions() on the endpoint and prints the panel
     * itself, so there is no callback left to unhook and a branch there is both
     * simpler and harder to break than hook surgery from out here.
     */
    /**
     * The cheap half of the question, with no side effects.
     *
     * Kept separate because the address-lookup filter runs several times a
     * request and must not build the catalogue or push a notice to do it.
     */
    public static function is_details_request(): bool
    {
        return 'add' === self::posted_action() && self::nonce_ok('mb_sof_add') && self::can_order();
    }

    public static function wants_details(): bool
    {
        if (!self::is_details_request()) {
            return false;
        }

        if (!self::selection()) {
            \wc_add_notice(\__('No samples were selected.', 'granola'), 'error');

            return false;
        }

        return true;
    }

    public static function maybe_place(): void
    {
        if ('place' !== self::posted_action()) {
            return;
        }

        if (!self::nonce_ok(self::NONCE)) {
            \wp_die(\esc_html__('That form has expired. Please go back and try again.', 'granola'), '', ['response' => 403]);
        }

        if (!self::can_order()) {
            \wp_die(\esc_html__('You do not have access to sample ordering.', 'granola'), '', ['response' => 403]);
        }

        self::place_order();
    }

    private static function nonce_ok(string $action): bool
    {
        return isset($_POST['mb_sof_nonce'])
            && \wp_verify_nonce(\sanitize_text_field(\wp_unslash($_POST['mb_sof_nonce'])), $action);
    }

    private static function can_order(): bool
    {
        return \function_exists('mb_sof_user_can_order') && \mb_sof_user_can_order();
    }

    /**
     * The selection, validated against the catalogue and the per-SKU caps.
     *
     * Posted quantities are a claim, not a fact. An id that is not in THIS
     * viewer's catalogue cannot be ordered at all, which is what stops a forged
     * post dropping any product in the shop into an order, and the ceiling is
     * re-applied here rather than trusted from the browser.
     *
     * @return array<int,int> product/variation id => quantity
     */
    public static function selection(): array
    {
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- callers verify first.
        $raw = isset($_POST['qty']) && \is_array($_POST['qty']) ? \wp_unslash($_POST['qty']) : [];
        $allowed = \function_exists('mb_sof_catalogue_by_id') ? \mb_sof_catalogue_by_id() : [];
        $lines = [];

        foreach ((array) $raw as $id => $qty) {
            $id = \absint($id);
            $qty = \absint($qty);

            if (!$id || !$qty || !isset($allowed[$id])) {
                continue;
            }

            $max = isset($allowed[$id]['max_qty'])
                ? (int) $allowed[$id]['max_qty']
                : (\function_exists('mb_sof_max_qty') ? (int) \mb_sof_max_qty() : 0);

            if ($max < 1) {
                continue;
            }

            $lines[$id] = \min($qty, $max);
        }

        return $lines;
    }

    // ------------------------------------------------------------ the order

    /**
     * The token this rendering of the form was given, or '' if it had none.
     */
    private static function posted_token(): string
    {
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- maybe_place() verified the nonce before this is reached.
        return isset($_POST['mb_sof_token']) ? \sanitize_text_field(\wp_unslash($_POST['mb_sof_token'])) : '';
    }

    /**
     * Take this form's one-use token, or report that somebody already has.
     *
     * The nonce cannot do this job: WordPress nonces stay valid for hours, so
     * every repeat post of the same form used to be another order, whether it
     * was a second click while the page was still answering, a second tab, or
     * Back and Place again. 25 Nov 2026: UK-34933, UK-34934 and UK-34935, one
     * second apart, the same customer and the same three samples.
     *
     * The claim is an INSERT IGNORE on the options table's unique key, which is
     * the lock WordPress core takes for its own upgrader. Two posts landing in
     * the same millisecond still have exactly one winner, which a transient
     * (read, then write) cannot promise.
     *
     * A post with no usable token is let through, and so is a database error.
     * The first is a page opened before this shipped; refusing it would lose a
     * real order to stop a duplicate. One small row stays per order, and rows
     * older than two days are cleared whenever a new one is claimed.
     */
    public static function claim_submission(string $token): bool
    {
        if (!\preg_match(self::TOKEN_PATTERN, $token)) {
            return true;
        }

        global $wpdb;

        $inserted = $wpdb->query($wpdb->prepare(
            "INSERT IGNORE INTO {$wpdb->options} (option_name, option_value, autoload) VALUES (%s, %d, 'no')",
            self::CLAIM_PREFIX . $token,
            \time()
        ));

        // 0 rows: somebody else holds it. false is a database error.
        if (0 === $inserted) {
            return false;
        }

        $wpdb->query($wpdb->prepare(
            "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s AND option_value < %d",
            $wpdb->esc_like(self::CLAIM_PREFIX) . '%',
            \time() - 2 * \DAY_IN_SECONDS
        ));

        return true;
    }

    /**
     * Hand the token back when no order was made, so a retry from the same page
     * is not told it was already placed.
     */
    public static function release_submission(string $token): void
    {
        if (!\preg_match(self::TOKEN_PATTERN, $token)) {
            return;
        }

        global $wpdb;

        $wpdb->delete($wpdb->options, ['option_name' => self::CLAIM_PREFIX . $token]);
    }

    private static function place_order(): void
    {
        $lines = self::selection();

        if (!$lines) {
            \wc_add_notice(\__('No samples were selected.', 'granola'), 'error');
            self::back();
        }

        $posted = self::posted();
        $missing = self::missing_required($posted);

        if ($missing) {
            foreach ($missing as $label) {
                /* translators: %s: field label. */
                \wc_add_notice(\sprintf(\__('%s is required.', 'granola'), $label), 'error');
            }

            self::back();
        }

        // Claimed only now, after every rejection above. A post refused for a
        // missing field must not burn its token: Back, fix the field and Place
        // again has to still work.
        $token = self::posted_token();

        if (!self::claim_submission($token)) {
            \wc_add_notice(\__('That sample order was already placed, so it was not sent a second time.', 'granola'), 'notice');
            self::back();
        }

        $order = \wc_create_order(['customer_id' => \get_current_user_id()]);

        if (\is_wp_error($order)) {
            self::release_submission($token);
            \wc_add_notice(\__('The order could not be created. Please try again.', 'granola'), 'error');
            self::back();
        }

        foreach ($lines as $id => $qty) {
            $product = \wc_get_product($id);

            if (!$product instanceof \WC_Product) {
                continue;
            }

            $item_id = $order->add_product($product, $qty, ['subtotal' => 0, 'total' => 0]);
            $item = $item_id ? $order->get_item($item_id) : null;

            if ($item) {
                // The same markers the basket route wrote, so the Analytics
                // report and anything else counting samples sees these too.
                $item->add_meta_data(self::SAMPLE_MARKER_KEY, self::SAMPLE_MARKER_VALUE);
                $item->add_meta_data('_millboard_sample_order', 'yes', true);
                $item->save();
            }
        }

        self::apply_address($order, $posted);
        self::apply_shipping_line($order);
        self::apply_meta($order, $posted, $lines);

        /*
         * The lawful basis for contacting this customer, written by the SAME
         * resolver the shop checkout uses rather than a second copy of the
         * rules. It no-ops on a locale ConsentFields is not configured for,
         * so the UK and US are untouched.
         *
         * On France this records all eight keys the live CRM Perks feeds are
         * already mapped to, including the consent date and the wording
         * version the customer agreed to, which is what makes the record
         * demonstrable rather than merely asserted.
         */
        ConsentFields::record_permission($order, $posted);

        $order->set_created_via('millboard-sample-ordering');
        $order->calculate_totals(false);
        $order->update_status('processing', \__('Sample order placed from My Account.', 'granola'));

        \wc_add_notice(
            \sprintf(
                /* translators: %s: order number. */
                \__('Sample order %s placed. It is with fulfilment, nothing further is needed from you.', 'granola'),
                $order->get_order_number()
            ),
            'success'
        );

        self::back();
    }

    /**
     * A zero-cost "Free shipping" line, the same one a free basket produces.
     *
     * This route never touches the cart, so no shipping method is ever chosen
     * and the order reaches SAP with an empty `shipping_lines` array, while
     * every other free order on the site carries
     * `millboard_zero_cost_shipping` / "Free shipping". That absence is what
     * SAP had nothing to map after the 7 Oct launch.
     *
     * Zero, always. We never charge for samples.
     */
    private static function apply_shipping_line(\WC_Order $order): void
    {
        $item = new \WC_Order_Item_Shipping();

        $item->set_method_id(SampleShipping::ZERO_COST_RATE_ID);
        $item->set_instance_id(0);
        $item->set_method_title(\__('Free shipping', 'granola'));
        $item->set_total(0);

        $order->add_item($item);
    }

    /**
     * @param array<string,string> $p
     */
    private static function apply_address(\WC_Order $order, array $p): void
    {
        foreach (['billing', 'shipping'] as $type) {
            $order->{"set_{$type}_first_name"}($p['customer_first_name'] ?? '');
            $order->{"set_{$type}_last_name"}($p['customer_last_name'] ?? '');
            $order->{"set_{$type}_company"}($p['billing_company'] ?? '');
            $order->{"set_{$type}_address_1"}($p['billing_address_1'] ?? '');
            $order->{"set_{$type}_address_2"}($p['billing_address_2'] ?? '');
            $order->{"set_{$type}_city"}($p['billing_city'] ?? '');
            $order->{"set_{$type}_state"}($p['billing_state'] ?? '');
            $order->{"set_{$type}_postcode"}($p['billing_postcode'] ?? '');
            $order->{"set_{$type}_country"}($p['billing_country'] ?? 'GB');
            $order->{"set_{$type}_phone"}($p['billing_phone'] ?? '');
        }

        /*
         * THE BILLING EMAIL IS THE CUSTOMER'S, AND IT HAS TO BE.
         *
         * This used to be the rep's address, on the reasoning that they are the
         * account holder and WooCommerce wants somewhere to send order mail.
         * That reproduced the bug Aaron reported on the old portal: CRM Perks
         * feed "UK Live Contact" (#8165) has primary_key = email and maps
         * email <- _billing_email, firstname <- _billing_first_name, and it
         * fires on the `processing` status this form sets. With the rep's
         * address in there, every sample order upserted the REP's contact and
         * overwrote their name with the customer's.
         *
         * So billing is the customer throughout, and the rep is recorded in
         * meta by apply_meta(). Order mail is redirected back to the rep by
         * mail_customer_and_rep() rather than by misfiling the address.
         */
        $email = \sanitize_email($p['customer_email'] ?? '');

        if ($email && \is_email($email)) {
            $order->set_billing_email($email);
        }
    }

    /**
     * Keep the admin notification's Reply-To deliverable.
     *
     * WooCommerce sets Reply-To on new_order, cancelled_order and failed_order
     * to the BILLING address, which on a sample order is now the customer. The
     * mail transport rejects the WHOLE message when that address will not
     * resolve, so one typo in a rep-entered email silently stops fulfilment
     * being told an order exists.
     *
     * Not theoretical: staging order UK-28624, 6 Oct 2026, logged
     * `Invalid "Reply-To" e-mail address` and the ops "New order" email never
     * left. Before the billing address became the customer's it was always a
     * millboard.com address, so this could not happen — it arrived with that
     * change and belongs to it.
     *
     * Replying to the rep is the right behaviour anyway: they placed the order
     * and they hold the customer relationship.
     *
     * @param mixed $header
     * @param mixed $email_id
     * @param mixed $order
     * @return mixed
     */
    public static function reply_to_the_rep($header, $email_id = '', $order = null)
    {
        if (!$order instanceof \WC_Order) {
            return $header;
        }

        if ('yes' !== (string) $order->get_meta(SampleOrderReport::META_FLAG)) {
            return $header;
        }

        $rep = (string) $order->get_meta(self::META_PLACED_BY_EMAIL);

        if (!$rep || !\is_email($rep)) {
            return $header;
        }

        $name = \trim((string) $order->get_meta(self::META_PLACED_BY));

        // Drop WooCommerce's own line rather than appending a second one: two
        // Reply-To headers is undefined behaviour, and some transports reject it.
        $header = \preg_replace('/^Reply-to:.*\r?\n?/mi', '', (string) $header);

        return $header . 'Reply-to: ' . ('' !== $name ? $name . ' <' . $rep . '>' : $rep) . "\r\n";
    }

    /**
     * Send the "processing" email to the customer AND the rep.
     *
     * Aaron, 6 Oct 2026, asked for both: the customer is told their samples are
     * coming, and the rep keeps a record of what they sent. WooCommerce mails
     * the billing address on its own, which is now the customer, so all this
     * adds is the rep.
     *
     * Only sample orders are touched. Everything else on the shop keeps
     * WooCommerce's own recipient untouched.
     *
     * @param mixed $recipient Comma-separated, per WooCommerce.
     * @param mixed $order
     * @return mixed
     */
    public static function mail_customer_and_rep($recipient, $order = null)
    {
        if (!$order instanceof \WC_Order) {
            return $recipient;
        }

        if ('yes' !== (string) $order->get_meta(SampleOrderReport::META_FLAG)) {
            return $recipient;
        }

        $rep = (string) $order->get_meta(self::META_PLACED_BY_EMAIL);

        if (!$rep || !\is_email($rep)) {
            return $recipient;
        }

        $to = \array_filter(\array_map('trim', \explode(',', (string) $recipient)));

        // A rep ordering samples for themselves would otherwise be sent two
        // copies of the same email.
        foreach ($to as $existing) {
            if (0 === \strcasecmp($existing, $rep)) {
                return $recipient;
            }
        }

        $to[] = $rep;

        return \implode(',', $to);
    }

    /**
     * Put the rep's internal notes on the STAFF order email.
     *
     * Aaron, 24 Nov 2026: notes added on the internal sample form should
     * reach the people who pack and send the order. They were being captured
     * and then read by nobody until somebody opened the dispatch queue --
     * SampleFulfilment seeds its own notes field from them, which is useful
     * on screen and no use at all to whoever is working from the email.
     *
     * ⚠️ GATED ON $sent_to_admin, WHICH IS THE WHOLE POINT. These are things
     * a rep writes about a customer, not to them: "chase before the Tuesday
     * visit", "second request, first never arrived", "key account, handle
     * carefully". On the customer's own confirmation they would range from
     * odd to embarrassing. WooCommerce passes true only for the admin-facing
     * mails, which is exactly the set that should see them.
     *
     * Every site, not just de-de: the field exists on every internal sample
     * form and the reason holds wherever one is placed.
     *
     * @param mixed $order
     * @param bool  $sent_to_admin
     * @param bool  $plain_text
     * @param mixed $email
     */
    public static function email_sales_comments($order, $sent_to_admin = false, $plain_text = false, $email = null): void
    {
        if (!$sent_to_admin || !$order instanceof \WC_Order) {
            return;
        }

        $notes = \trim((string) $order->get_meta(self::META['sales_comments']));

        if ('' === $notes) {
            return;
        }

        $heading = \__('Internal notes', 'granola');

        if ($plain_text) {
            echo "\n\n" . \esc_html(\strtoupper($heading)) . "\n";
            echo \esc_html($notes) . "\n";

            return;
        }

        /*
         * Inline styles because an email has no stylesheet, and wpautop so a
         * rep who pressed Return twice does not get one run-on paragraph.
         */
        \printf(
            '<div style="margin:16px 0;padding:12px 14px;border-left:3px solid #8a4b00;background:#fdf6ec">'
                . '<p style="margin:0 0 6px;font-weight:600;color:#8a4b00">%s</p>'
                . '<div style="margin:0;color:#333">%s</div>'
                . '</div>',
            \esc_html($heading),
            \wp_kses_post(\wpautop($notes))
        );
    }

    /**
     * @param array<string,string> $p
     * @param array<int,int> $lines
     */
    private static function apply_meta(\WC_Order $order, array $p, array $lines): void
    {
        foreach (self::META as $key => $meta_key) {
            if ('' !== ($p[$key] ?? '')) {
                $order->update_meta_data($meta_key, $p[$key]);
            }
        }

        // The reused checkout fields keep their own keys, so HubSpot and any
        // existing report read them exactly as they read a shop order.
        foreach (self::REUSED as $key) {
            if ('' !== ($p[$key] ?? '')) {
                $order->update_meta_data($key, $p[$key]);
            }
        }

        // What SampleOrderReport counts. Written here as well as on the basket
        // route, because this path never goes near woocommerce_checkout_*.
        $order->update_meta_data(SampleOrderReport::META_FLAG, 'yes');
        $order->update_meta_data(SampleOrderReport::META_LINES, (string) \count($lines));
        $order->update_meta_data(SampleOrderReport::META_UNITS, (string) \array_sum($lines));

        // The form asks whether a follow-up IS wanted; HubSpot's property
        // records whether one is NOT. See META_NO_FOLLOW_UP.
        $follow_up = (string) ($p['follow_up'] ?? '');

        if ('' !== $follow_up) {
            $order->update_meta_data(self::META_NO_FOLLOW_UP, self::no_follow_up_value($follow_up));
        }

        // Only written when ticked, because that is exactly what the shop
        // checkout does: there is no '0' row anywhere in the 7,085 orders
        // carrying this key, so an absent row is the "no objection" state and
        // writing one would not mean what the existing data means.
        if ('1' === (string) ($p[self::MARKETING_OPT_OUT] ?? '')) {
            $order->update_meta_data(self::MARKETING_OPT_OUT, '1');
        }

        $user = \wp_get_current_user();

        if ($user instanceof \WP_User && $user->ID) {
            // Billing is the customer now, so this is the only record of who
            // actually sent it. Ops and the report both need it.
            $order->update_meta_data(self::META_PLACED_BY, (string) $user->display_name);
            $order->update_meta_data(self::META_PLACED_BY_EMAIL, (string) $user->user_email);

            $order->update_meta_data(SampleOrderReport::META_ROLE, (string) (\reset($user->roles) ?: ''));
            $order->update_meta_data(
                SampleOrderReport::META_STAFF,
                \Theme\Accounts\Roles::is_staff($user->ID) ? 'yes' : 'no'
            );

            $company = (string) \get_user_meta($user->ID, 'millboard_company', true);

            if ('' !== $company) {
                $order->update_meta_data(SampleOrderReport::META_COMPANY, $company);
            }
        }
    }

    // -------------------------------------------------------------- posted

    /**
     * @return array<string,string>
     */
    public static function posted(): array
    {
        $out = [];
        $keys = \array_merge(
            \array_keys(self::META),
            self::REUSED,
            [
                'customer_first_name', 'customer_last_name', 'customer_email', 'billing_company',
                self::MARKETING_OPT_OUT,
                'billing_address_1', 'billing_address_2', 'billing_city',
                'billing_state', 'billing_postcode', 'billing_country', 'billing_phone',
                /*
                 * The consent pair, so ConsentFields::record_permission() can
                 * resolve exactly what it resolves at checkout. Absent on a
                 * locale that does not render them, which is harmless: the
                 * resolver no-ops where it is not configured.
                 */
                ConsentFields::AUDIENCE_FIELD,
                ConsentFields::CONSENT_FIELD,
            ]
        );

        foreach ($keys as $k) {
            // phpcs:ignore WordPress.Security.NonceVerification.Missing -- callers verify first.
            $v = isset($_POST[$k]) ? \wp_unslash($_POST[$k]) : '';

            if (\is_array($v)) {
                // Project type is checkboxes; keep it readable rather than serialised.
                $out[$k] = \implode(', ', \array_map('sanitize_text_field', $v));
                continue;
            }

            if ('sales_comments' === $k) {
                $out[$k] = \sanitize_textarea_field($v);
                continue;
            }

            $out[$k] = 'customer_email' === $k
                ? \sanitize_email($v)
                : \sanitize_text_field($v);
        }

        return $out;
    }

    /**
     * Server-side backstop. The form carries `required`, so the browser stops
     * the ordinary case; this is what a forged or scripted post meets.
     *
     * @param array<string,string> $p
     * @return string[] Labels of anything required and missing.
     */
    public static function missing_required(array $p): array
    {
        $required = [
            'follow_up' => \__('Does this customer require a follow-up from Millboard?', 'granola'),
            'customer_first_name' => \__('Customer first name', 'granola'),
            'customer_last_name' => \__('Customer last name', 'granola'),
            'customer_email' => \__('Customer email address', 'granola'),
            'billing_address_1' => \__('Address', 'granola'),
            'billing_postcode' => \__('Postcode', 'granola'),
            'billing_phone' => \__('Customer phone number', 'granola'),
        ];

        /*
         * Consent is per country. France asks the customer to opt in before
         * Millboard may telephone them and the answer is mandatory; the UK
         * asks only for an objection to legitimate interest; the US asks
         * nothing at all. Rather than encode that here, require whatever
         * THIS site's checkout requires, read live, so the two can never say
         * different things about the same consent.
         */
        /*
         * website_persona is not on every checkout: en-us has none at all.
         * Required only where the form actually renders it, or the US could
         * never satisfy it.
         */
        if ('' !== self::checkout_label('website_persona')) {
            $required['website_persona'] = \__('This project is to be installed at', 'granola');
        }

        /*
         * Gated on the CONSENT question, not on the audience field's own
         * presence. The audience is only asked because the consent block
         * branches on it, and that block only renders where the consent
         * question exists. Keying this on AUDIENCE_FIELD required it on
         * en-gb and en-us too, where the form never renders it, so neither
         * could place an order at all: required, invisible, unsatisfiable.
         *
         * The rule is "require what the FORM renders", which is not always
         * "require what the CHECKOUT has". The marketing and persona gates
         * happen to be the same question; this one is not.
         */
        if ('' !== self::checkout_label(ConsentFields::CONSENT_FIELD)) {
            $required[ConsentFields::AUDIENCE_FIELD] = \__('Who the customer is', 'granola');
        }

        if ('' !== self::checkout_label(ConsentFields::CONSENT_FIELD)) {
            $required[ConsentFields::CONSENT_FIELD] = \__('Whether the customer agreed to be contacted', 'granola');
        }

        $missing = [];

        foreach ($required as $key => $label) {
            if ('' === \trim((string) ($p[$key] ?? ''))) {
                $missing[] = $label;
            }
        }

        // A malformed address is as useless as a missing one: it is the key
        // HubSpot matches the contact on, so a typo silently creates a junk
        // contact rather than failing.
        $email = \trim((string) ($p['customer_email'] ?? ''));

        if ('' !== $email && !self::is_customer_email($email)) {
            $missing[] = self::is_internal_email($email)
                ? \__('Customer email address (use the customer’s own address, not a Millboard one)', 'granola')
                : \__('Customer email address (that address is not valid)', 'granola');
        }

        // "." passed this for as long as the form has existed, because the
        // only test was that the box was not empty.
        $phone = \trim((string) ($p['billing_phone'] ?? ''));

        if ('' !== $phone && !self::is_plausible_phone($phone)) {
            $missing[] = \__('Customer phone number (that number is not valid)', 'granola');
        }

        return $missing;
    }

    // ---------------------------------------------------------- validation

    /**
     * The internal domains, filtered so adding one costs no deploy.
     *
     * @return string[] Lower case, no leading @.
     */
    public static function internal_email_domains(): array
    {
        $domains = \apply_filters('mb_sof_internal_email_domains', self::INTERNAL_EMAIL_DOMAINS);

        return \array_values(\array_filter(\array_map(
            static fn ($d): string => \strtolower(\ltrim(\trim((string) $d), '@')),
            (array) $domains
        )));
    }

    public static function is_internal_email(string $email): bool
    {
        $at = \strrpos($email, '@');

        if (false === $at) {
            return false;
        }

        return \in_array(
            \strtolower(\substr($email, $at + 1)),
            self::internal_email_domains(),
            true
        );
    }

    /**
     * Is this an address a customer could actually have?
     *
     * ⚠️ Three things, and no more than three. A regex cannot tell you an
     * address is REAL -- test@test.com is perfectly well formed and belongs
     * to nobody -- so this rejects what is provably wrong and makes no claim
     * beyond that. Verifying the rest means sending to it.
     */
    public static function is_customer_email(string $email): bool
    {
        $email = \trim($email);

        if (!\is_email($email)) {
            return false;
        }

        /*
         * WordPress's is_email() accepts a one-character top level domain, so
         * a@b.c gets through it. Nothing real has one.
         */
        if (!\preg_match('/\.[A-Za-z]{2,}$/', $email)) {
            return false;
        }

        return !self::is_internal_email($email);
    }

    /**
     * Could this be somebody's phone number?
     *
     * Counts the DIGITS rather than the characters, because a character class
     * alone would happily accept nine full stops.
     */
    public static function is_plausible_phone(string $phone): bool
    {
        $phone = \trim($phone);

        // Only the characters a phone number is written with.
        if (!\preg_match('/^\+?[0-9\s().\-]+$/', $phone)) {
            return false;
        }

        $digits = \strlen((string) \preg_replace('/\D+/', '', $phone));

        return $digits >= self::PHONE_MIN_DIGITS && $digits <= self::PHONE_MAX_DIGITS;
    }

    /*
     * ⚠️ THE TWO PATTERNS BELOW EXIST TO SAVE THE BASKET, NOT TO SECURE
     * ANYTHING.
     *
     * The server above is the guarantee. But a server rejection calls back(),
     * which redirects to step one, and the selection and every typed field
     * are gone: that is exactly what made the 24 Nov "order vanished" case so
     * hard to explain. So the browser gets the same rules first, where the
     * message appears against the field and nothing is lost.
     *
     * Both are generated from the same constants the server checks, because a
     * browser that allows what the server refuses is worse than no browser
     * check at all.
     */

    /**
     * The `pattern` attribute for the email box.
     */
    public static function email_pattern(): string
    {
        $syntax = '[^@\s]+@[^@\s]+\.[A-Za-z]{2,}';
        $domains = self::internal_email_domains();

        if (!$domains) {
            return $syntax;
        }

        /*
         * HTML's pattern attribute is case SENSITIVE and has nowhere to put
         * the i flag, so every letter becomes its own two-case class. Built
         * here rather than written out, so it cannot drift from the list.
         */
        $blocked = \implode('|', \array_map(
            static fn (string $domain): string => (string) \preg_replace_callback(
                '/[a-z]/i',
                static fn (array $m): string => '[' . \strtoupper($m[0]) . \strtolower($m[0]) . ']',
                \preg_quote($domain, '/')
            ),
            $domains
        ));

        return \sprintf('(?!.*@(?:%s)$)%s', $blocked, $syntax);
    }

    /**
     * The `pattern` attribute for the phone box.
     */
    public static function phone_pattern(): string
    {
        // The lookahead counts digits; the part after it limits the
        // characters. Neither does the job alone: a character class accepts
        // nine full stops, and a digit count accepts "call me on 07..".
        return \sprintf(
            '(?=(?:\D*\d){%1$d,%2$d}\D*$)\+?[0-9\s().\-]{%1$d,30}',
            self::PHONE_MIN_DIGITS,
            self::PHONE_MAX_DIGITS
        );
    }

    // --------------------------------------------------------------- reuse

    /**
     * Options for a field the shop checkout already defines.
     *
     * Read live rather than copied, so a change made in Checkout Field Editor
     * reaches this form too.
     *
     * @return array<string,string>
     */
    public static function checkout_options(string $key): array
    {
        if (!\function_exists('WC') || !\WC()->checkout()) {
            return [];
        }

        foreach (\WC()->checkout()->get_checkout_fields() as $set) {
            if (isset($set[$key]['options']) && \is_array($set[$key]['options'])) {
                return $set[$key]['options'];
            }
        }

        return [];
    }

    /**
     * Translate the form's answer into HubSpot's `no_follow_up` value.
     *
     * The form asks "does this customer require a follow-up?" and the property
     * records the opposite, so the two are inverted as well as using different
     * words. Its only valid values are the literal strings "Checked" and
     * "Not Checked"; anything else is rejected.
     *
     * Deliberately public and deliberately total: the safe answer for an
     * unrecognised input is "Not Checked", because that leaves the follow-up
     * happening. Getting this backwards would silently suppress follow-up for
     * the customers who asked for one, which is the failure worth engineering
     * against.
     */
    public static function no_follow_up_value(string $follow_up): string
    {
        return 'No' === $follow_up ? 'Checked' : 'Not Checked';
    }

    /**
     * The label a checkout field carries, read live.
     *
     * The marketing opt-out wording is Legal's, maintained in Checkout Field
     * Editor. Reading it rather than copying it means a change there reaches
     * this form too, instead of the two drifting into saying different things
     * about the same consent.
     */
    public static function checkout_label(string $key): string
    {
        $field = self::checkout_field($key);

        return isset($field['label']) ? (string) $field['label'] : '';
    }

    /**
     * A checkout field's whole definition, read live.
     *
     * Returning the definition rather than just the label lets this form hand
     * it straight to woocommerce_form_field(), which fires the same renderer
     * the checkout fires - including ConsentFields' own markup for the
     * `millboard_consent` type. The consent wording therefore cannot drift
     * from the checkout's, because it IS the checkout's.
     *
     * @return array<string, mixed>
     */
    public static function checkout_field(string $key): array
    {
        if (!\function_exists('WC') || !\WC()->checkout()) {
            return [];
        }

        foreach (\WC()->checkout()->get_checkout_fields() as $set) {
            if (isset($set[$key]) && \is_array($set[$key])) {
                return $set[$key];
            }
        }

        return [];
    }

    /**
     * Millboard staff, for "Order on behalf of".
     *
     * Driven from the role rather than a hard-coded list, so it maintains
     * itself as people join and leave. The portal import is what keeps that
     * role to Millboard addresses only.
     *
     * @return string[]
     */
    public static function staff_options(): array
    {
        $names = [];

        foreach (\get_users(['role' => \Theme\Accounts\Roles::ROLE_STAFF, 'number' => -1, 'orderby' => 'display_name']) as $u) {
            $names[] = \trim((string) $u->display_name) ?: $u->user_email;
        }

        return $names;
    }

    private static function back(): void
    {
        \wp_safe_redirect(\mb_sof_form_url());
        exit;
    }

    public static function render_details(): void
    {
        $lines = self::selection();
        $units = \array_sum($lines);

        // Step one renders the component, which enqueues its own block.css on
        // the way past. This includes the template directly, so the stylesheet
        // has to be asked for by hand or step two arrives unstyled.
        \Granola\Component::enqueue_style_by_filename('wc-account-sample-ordering');

        include \get_theme_file_path('sample-ordering/template-parts/millboard-sample-details.php');
    }
}
