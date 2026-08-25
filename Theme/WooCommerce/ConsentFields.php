<?php

namespace Theme\WooCommerce;

/**
 * Audience-dependent marketing permission at checkout.
 *
 * France's 2026 rules mean a consumer needs prior opt-in consent, recorded with a
 * date, before we contact them for marketing by telephone OR e-mail, while a business
 * contact can still be contacted on legitimate interest provided they are informed
 * and can object easily. The two need different questions, so which question is
 * asked is driven by the "Who am I?" answer.
 *
 * The consumer question is per channel, because bundling channels into one yes/no
 * is the weaker position and someone who wants e-mail but not calls has to be able
 * to say so. Nothing is ticked by default and nothing is required: no ticks is a
 * refusal of everything, which is both a valid answer and the safe reading.
 *
 * The Checkout Field Editor plugin (v1.7.25) has no conditional logic, and it owns
 * the checkout field array via `woocommerce_billing_fields` (priority 1) and
 * `woocommerce_checkout_fields` (priority 1000). This class layers on top at 1001,
 * so the plugin stays the source of truth for everything it already manages.
 *
 * Three separate mechanisms, deliberately:
 *
 *  1. Marker classes let the JS show and hide rows as the answer changes. Cosmetic.
 *  2. `woocommerce_checkout_posted_data` discards whichever branch does not apply.
 *     This is the authoritative step, so a stale or forged value cannot be recorded
 *     and a JS failure degrades to "both questions visible" rather than "wrong basis
 *     recorded".
 *  3. The order stores what was asked, what was answered, and when, because consent
 *     you cannot evidence is not consent.
 */
class ConsentFields
{
    /**
     * Checkout field key for the consumer opt-in question.
     *
     * Posted as an array, one entry per channel the customer ticked.
     */
    public const CONSENT_FIELD = 'contact-consent-channels';

    public const CHANNEL_EMAIL = 'email';
    public const CHANNEL_PHONE = 'phone';

    /**
     * Existing Checkout Field Editor key for the legitimate-interest objection.
     */
    public const OBJECTION_FIELD = 'marketing-opt-in';

    /**
     * Existing Checkout Field Editor key for the audience question.
     */
    public const AUDIENCE_FIELD = 'who-am-i';

    /**
     * Custom `woocommerce_form_field` type, rendered by this class.
     *
     * The plugin's own radio renderer uses the visible option text as the submitted
     * value, which would put localised display strings ("Oui" / "Non") into the
     * permission record. Rendering it here keeps the stored values stable and
     * locale-independent.
     */
    private const FIELD_TYPE = 'millboard_consent';

    public const PERMITTED_YES = 'yes';
    public const PERMITTED_NO = 'no';

    public const BASIS_CONSENT = 'consent';
    public const BASIS_LEGITIMATE_INTEREST = 'legitimate-interest';
    public const BASIS_NONE = 'none';

    /**
     * Order meta written for every order on a configured locale.
     *
     * `permitted` is the operational answer the sales team needs before dialling.
     * `basis` is why we are allowed to, which is a different question and has to be
     * recorded separately or the two get conflated.
     */
    public const META_PERMITTED = 'phone-contact-permitted';
    public const META_EMAIL_PERMITTED = 'email-contact-permitted';
    public const META_BASIS = 'phone-contact-basis';
    public const META_EMAIL_BASIS = 'email-contact-basis';
    public const META_RECORDED = 'phone-contact-recorded';
    public const META_AUDIENCE = 'phone-contact-audience';
    public const META_WORDING = 'phone-contact-wording';
    public const META_CONSENT_DATE = 'phone-consent-date';

    /**
     * Per-locale configuration. Locales absent from this list are untouched.
     *
     * `wording` is stamped onto every order so that a later change to the copy does
     * not invalidate the evidence for orders taken under the previous wording. Bump
     * it whenever `consumer_legend`, `consumer_hint`, `consumer_options` or
     * `business_label` change.
     *
     * @return array<string, array<string, mixed>>
     */
    private static function locales(): array
    {
        $locales = [
            'fr_FR' => [
                'wording' => 'fr-2026-08-v3',

                // Consumer branch: explicit opt-in, one box per channel. Granular
                // rather than a single yes/no, because bundling channels is the weaker
                // position and someone who wants e-mail but not calls has to be able
                // to say so. Nothing is ticked and nothing is required: no ticks is a
                // refusal of everything, which is a valid answer and the safe reading.
                //
                // Wording supplied by James Etheridge (Head of Compliance), Aug 2026.
                // Do not reword any of the four strings below without bumping `wording`,
                // or orders stop pointing at the text their customer actually saw.
                'consumer_legend' => 'Restons en contact avec Millboard',
                'consumer_hint' => 'Millboard France SAS souhaite vous envoyer des informations sur ses produits de terrasse et de bardage, notamment les nouveaux produits, de l\'inspiration et des offres.',
                'consumer_options' => [
                    self::CHANNEL_EMAIL => 'Par e-mail',
                    self::CHANNEL_PHONE => 'Par téléphone',
                ],

                // Rendered after the checkboxes. %1$s is the withdrawal e-mail address
                // and %2$s the telephone number, injected as escaped values into an
                // already-escaped pattern so config cannot introduce markup.
                //
                // The 12 month duration is not decoration: it means consent has to age
                // off the call list on its own, which is why `phone-consent-date` is
                // written as a real date and the FR call list filters on
                // "less than 365 days ago". Change the duration here and that filter
                // has to change with it.
                'consumer_terms' => 'Si vous cochez « Par téléphone », vous consentez à être appelé à des '
                    . 'fins de prospection concernant nos produits de terrasse et de bardage pendant '
                    . '12 mois à compter de ce jour. Ce consentement ne sera pas renouvelé '
                    . 'automatiquement, nous vous le redemanderons. Vous pouvez retirer votre '
                    . 'consentement à tout moment en écrivant à %1$s ou en appelant le %2$s, et vous '
                    . 'pouvez à tout moment nous demander la preuve de votre consentement. Les '
                    . 'messages relatifs à votre demande ou à votre commande ne constituent pas de '
                    . 'la prospection et continueront de vous être envoyés.',
                'withdraw_email' => 'dataprotection@millboard.com',
                'withdraw_phone' => '05.25.53.00.28',
                'consent_months' => 12,

                // Business branch: legitimate interest, with the channels named and
                // an easy objection, which is what the exemption is conditional on.
                'business_label' => 'Nous traitons vos données professionnelles sur la base de l\'intérêt légitime afin de vous adresser des informations pertinentes par e-mail, par courrier et par téléphone, conformément à notre politique de confidentialité. Si vous ne souhaitez pas être contacté à des fins de prospection, cochez cette case.',

                // The policy link is back, at James's request. It duplicates the one in
                // WooCommerce's own privacy block above the Order button, deliberately:
                // his wording ends with an explicit route to the notice, and a consent
                // statement that relies on boilerplate elsewhere on the page is weaker.
                'policy_link_text' => 'politique de confidentialité',
                'policy_prompt' => 'Comment nous utilisons vos données : %s',

                // Company name strengthens the record that we approached the person
                // in a professional capacity, which matters most for sole traders.
                'company_label' => 'Nom de l\'entreprise',
                'show_company_for_business' => true,
            ],
        ];

        return apply_filters('millboard/consent_locales', $locales);
    }

    public static function init(): void
    {
        if (self::config() === null) {
            return;
        }

        // 1001 so the Checkout Field Editor (1000) has already built the array.
        \add_filter('woocommerce_checkout_fields', [__CLASS__, 'add_fields'], 1001);
        \add_filter('woocommerce_form_field_' . self::FIELD_TYPE, [__CLASS__, 'render_field'], 10, 4);
        \add_filter('woocommerce_checkout_posted_data', [__CLASS__, 'discard_inapplicable_branch']);
        \add_action('woocommerce_checkout_create_order', [__CLASS__, 'record_permission'], 10, 2);
        \add_action('woocommerce_admin_order_data_after_billing_address', [__CLASS__, 'render_admin_summary']);
    }

    /**
     * @return array<string, mixed>|null
     */
    private static function config(): ?array
    {
        $locales = self::locales();
        $locale = \get_locale();

        return isset($locales[$locale]) && is_array($locales[$locale]) ? $locales[$locale] : null;
    }

    /**
     * Add the consumer question and mark up the conditional rows.
     *
     * @param array<string, array<string, mixed>> $fields
     * @return array<string, array<string, mixed>>
     */
    public static function add_fields(array $fields): array
    {
        $config = self::config();

        if ($config === null || !isset($fields['billing']) || !is_array($fields['billing'])) {
            return $fields;
        }

        // If the audience question or the objection checkbox has been renamed or
        // disabled in the Checkout Field Editor, do nothing rather than show a
        // consent question that cannot be driven by anything.
        if (!isset($fields['billing'][self::AUDIENCE_FIELD], $fields['billing'][self::OBJECTION_FIELD])) {
            return $fields;
        }

        $objection_priority = (int) ($fields['billing'][self::OBJECTION_FIELD]['priority'] ?? 160);

        $fields['billing'][self::CONSENT_FIELD] = [
            'type' => self::FIELD_TYPE,
            'label' => $config['consumer_legend'],
            'description' => $config['consumer_hint'] ?? '',
            'options' => $config['consumer_options'],
            'required' => false, // Nothing is required: no ticks is a valid refusal.
            'priority' => $objection_priority - 5,
            'class' => ['form-row-wide', 'mb-consent', 'mb-consent--consumer'],
            'validate' => [],
        ];

        $fields['billing'][self::OBJECTION_FIELD]['class'] = array_merge(
            (array) ($fields['billing'][self::OBJECTION_FIELD]['class'] ?? []),
            ['mb-consent', 'mb-consent--business']
        );

        $fields['billing'][self::OBJECTION_FIELD]['label'] = $config['business_label'];

        if (!empty($config['show_company_for_business'])) {
            $fields['billing'] = self::add_company_field($fields['billing'], $config);
        }

        // The JS keys off these, so it never has to know the option labels. Which
        // options count as a consumer is decided in one place, server side.
        $fields['billing'][self::AUDIENCE_FIELD]['class'] = array_merge(
            (array) ($fields['billing'][self::AUDIENCE_FIELD]['class'] ?? []),
            ['mb-consent-trigger']
        );

        $fields['billing'][self::AUDIENCE_FIELD]['custom_attributes'] = array_merge(
            (array) ($fields['billing'][self::AUDIENCE_FIELD]['custom_attributes'] ?? []),
            [
                'data-mb-consumer-values' => \wp_json_encode(
                    self::consumer_option_values($fields['billing'][self::AUDIENCE_FIELD])
                ),
            ]
        );

        return $fields;
    }

    /**
     * Which of the "Who am I?" options mean a private individual.
     *
     * Derived from the live option list rather than hard-coded, so rewording an
     * option in the Checkout Field Editor cannot silently leave the browser and the
     * server disagreeing about who gets asked for consent.
     *
     * @param array<string, mixed> $audience_field
     * @return array<int, string>
     */
    private static function consumer_option_values(array $audience_field): array
    {
        $values = [];

        foreach ((array) ($audience_field['options'] ?? []) as $option_value => $option_label) {
            $option_value = (string) $option_value;

            if ($option_value === '') {
                continue; // Placeholder row.
            }

            if (Audience::is_homeowner([self::AUDIENCE_FIELD => $option_value])) {
                $values[] = $option_value;
            }
        }

        return $values;
    }

    /**
     * WooCommerce removes billing_company entirely when the store setting is
     * "hidden", which it is on every locale. Put it back for the business branch
     * only, optional, so a sole trader can identify the capacity we approached them
     * in without a homeowner being asked for a company name.
     *
     * @param array<string, array<string, mixed>> $billing
     * @param array<string, mixed> $config
     * @return array<string, array<string, mixed>>
     */
    private static function add_company_field(array $billing, array $config): array
    {
        if (isset($billing['billing_company'])) {
            $billing['billing_company']['class'] = array_merge(
                (array) ($billing['billing_company']['class'] ?? []),
                ['mb-consent', 'mb-consent--business']
            );

            return $billing;
        }

        $billing['billing_company'] = [
            'type' => 'text',
            'label' => $config['company_label'] ?? \__('Company name', 'granola'),
            'required' => false,
            'autocomplete' => 'section-billing billing organization',
            // Sits immediately above the permission question rather than up with the
            // address fields, so it reads as a follow-up to "I am an installer" and
            // does not pop in above the answer that triggered it. Also keeps it out
            // of the two-column pairing of the qualifying questions.
            'priority' => self::company_priority($billing),
            'class' => ['form-row-wide', 'mb-consent', 'mb-consent--business'],
            'validate' => [],
        ];

        return $billing;
    }

    /**
     * @param array<string, array<string, mixed>> $billing
     */
    private static function company_priority(array $billing): int
    {
        $objection_priority = (int) ($billing[self::OBJECTION_FIELD]['priority'] ?? 160);

        return $objection_priority - 8;
    }

    /**
     * Render the consumer opt-in as a checkbox group, one box per channel.
     *
     * @param string $field Markup built by WooCommerce, discarded.
     * @param string $key
     * @param array<string, mixed> $args
     * @param string|null $value
     */
    public static function render_field($field, $key, $args, $value): string
    {
        $classes = (array) ($args['class'] ?? []);

        if (!empty($args['required'])) {
            $classes[] = 'validate-required';
        }

        $options = (array) ($args['options'] ?? []);
        $hint = (string) ($args['description'] ?? '');
        $hint_id = $key . '_hint';

        $html = '<div class="form-row ' . \esc_attr(implode(' ', array_unique($classes))) . '"'
            . ' id="' . \esc_attr($key) . '_field"'
            . ' data-priority="' . \esc_attr((string) ($args['priority'] ?? '')) . '">';

        $html .= '<fieldset class="mb-consent__fieldset"'
            . ($hint !== '' ? ' aria-describedby="' . \esc_attr($hint_id) . '"' : '') . '>';

        $html .= '<legend class="mb-consent__legend">' . \esc_html((string) ($args['label'] ?? ''));

        if (!empty($args['required'])) {
            $html .= '&nbsp;<span class="required" aria-hidden="true">*</span>';
        }

        $html .= '</legend>';

        if ($hint !== '') {
            $html .= '<p class="mb-consent__hint" id="' . \esc_attr($hint_id) . '">' . \esc_html($hint) . '</p>';
        }

        $ticked = is_array($value) ? array_map('strval', $value) : array_filter([(string) $value]);

        foreach ($options as $option_value => $option_label) {
            $option_value = (string) $option_value;
            $input_id = $key . '_' . \sanitize_html_class($option_value);

            $html .= '<label class="mb-consent__option" for="' . \esc_attr($input_id) . '">'
                . '<input type="checkbox"'
                . ' id="' . \esc_attr($input_id) . '"'
                . ' name="' . \esc_attr($key) . '[]"'
                . ' value="' . \esc_attr($option_value) . '"'
                . (in_array($option_value, $ticked, true) ? ' checked="checked"' : '')
                . ' /> <span>' . \esc_html((string) $option_label) . '</span>'
                . '</label>';
        }

        $config = self::config();
        $terms = (string) ($config['consumer_terms'] ?? '');

        if ($terms !== '') {
            // The pattern is escaped first, then the address and number go in as
            // escaped values, so nothing in config can inject markup. The e-mail is a
            // mailto because it is the primary withdrawal route; the number is left as
            // plain text, matching the site's existing choice not to link telephone
            // numbers.
            $email = (string) ($config['withdraw_email'] ?? '');
            $phone = (string) ($config['withdraw_phone'] ?? '');

            $email_html = $email === ''
                ? ''
                : '<a href="mailto:' . \esc_attr($email) . '">' . \esc_html($email) . '</a>';

            $html .= '<p class="mb-consent__terms">'
                . sprintf(\esc_html($terms), $email_html, \esc_html($phone))
                . '</p>';
        }

        $privacy_url = \get_privacy_policy_url();

        if ($privacy_url !== '' && !empty($config['policy_prompt'])) {
            $link = '<a href="' . \esc_url($privacy_url) . '" target="_blank" rel="noopener">'
                . \esc_html((string) ($config['policy_link_text'] ?? '')) . '</a>';

            $html .= '<p class="mb-consent__policy">'
                . sprintf(\esc_html((string) $config['policy_prompt']), $link)
                . '</p>';
        }

        $html .= '</fieldset></div>';

        return $html;
    }

    /**
     * Discard whichever branch the customer was not shown.
     *
     * This is the authoritative resolution of the conditional. The browser decides
     * what to display; it does not decide what is recorded.
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public static function discard_inapplicable_branch(array $data): array
    {
        $audience = Audience::from_posted_data($data);

        if ($audience === Audience::CONSUMER) {
            $data['billing_company'] = '';

            // The existing chain treats an EMPTY objection field as "accepted", so
            // clearing it here would silently opt every homeowner in to email. Drive
            // it from the email tick instead, inverted, which lets the whole existing
            // email chain (4278768829 then 3978160366) keep working untouched.
            $data[self::OBJECTION_FIELD] = self::channel_ticked($data, self::CHANNEL_EMAIL) ? '' : '1';

            return $data;
        }

        if ($audience === Audience::BUSINESS) {
            $data[self::CONSENT_FIELD] = [];
        }

        return $data;
    }

    /**
     * @param array<string, mixed> $data
     */
    private static function channel_ticked(array $data, string $channel): bool
    {
        $ticked = $data[self::CONSENT_FIELD] ?? [];

        if (!is_array($ticked)) {
            $ticked = $ticked === '' ? [] : [$ticked];
        }

        return in_array($channel, array_map('strval', $ticked), true);
    }

    /**
     * Write the permission record onto the order.
     *
     * Written for every order on a configured locale, including refusals, because
     * "asked and declined" and "never asked" are operationally different and the
     * Checkout Field Editor's own save skips empty values so it records neither.
     *
     * @param \WC_Order $order
     * @param array<string, mixed> $data
     */
    public static function record_permission($order, $data): void
    {
        if (!$order instanceof \WC_Order || !is_array($data)) {
            return;
        }

        $config = self::config();

        if ($config === null) {
            return;
        }

        $audience = Audience::from_posted_data($data);

        if ($audience === Audience::CONSUMER) {
            // Per channel, from what they actually ticked. Nothing ticked is a
            // refusal of everything, which is the safe reading and a valid answer.
            $phone = self::channel_ticked($data, self::CHANNEL_PHONE);
            $email = self::channel_ticked($data, self::CHANNEL_EMAIL);

            $permitted = $phone ? self::PERMITTED_YES : self::PERMITTED_NO;
            $email_permitted = $email ? self::PERMITTED_YES : self::PERMITTED_NO;

            // Basis is per channel, not per consent event. A refused channel has no
            // basis at all, so it is `none`. Aaron caught the earlier version writing
            // `consent` to the phone basis for someone who had refused the phone,
            // which reads on the record as "we may call them" and is exactly the
            // wrong conclusion for a reader to draw.
            $basis = $phone ? self::BASIS_CONSENT : self::BASIS_NONE;
            $email_basis = $email ? self::BASIS_CONSENT : self::BASIS_NONE;
        } elseif ($audience === Audience::BUSINESS) {
            // One legitimate-interest statement covering e-mail, post and telephone,
            // with one objection, so the objection applies to every channel at once.
            $objected = !empty($data[self::OBJECTION_FIELD]);
            $permitted = $objected ? self::PERMITTED_NO : self::PERMITTED_YES;
            $email_permitted = $permitted;
            $basis = $objected ? self::BASIS_NONE : self::BASIS_LEGITIMATE_INTEREST;
            $email_basis = $basis;
        } else {
            // Should be unreachable: "Who am I?" is a required field. Fail closed.
            $permitted = self::PERMITTED_NO;
            $email_permitted = self::PERMITTED_NO;
            $basis = self::BASIS_NONE;
            $email_basis = self::BASIS_NONE;
        }

        $order->update_meta_data(self::META_PERMITTED, $permitted);
        $order->update_meta_data(self::META_EMAIL_PERMITTED, $email_permitted);
        $order->update_meta_data(self::META_BASIS, $basis);
        $order->update_meta_data(self::META_EMAIL_BASIS, $email_basis);
        $order->update_meta_data(self::META_RECORDED, \gmdate('c'));

        // A real date alongside the ISO string. The string is the evidence, including
        // the time; this is what HubSpot can actually filter on, because relative date
        // filters do not work against a text property. It is what makes consent age
        // off the FR call list after 12 months without anything having to run.
        $order->update_meta_data(self::META_CONSENT_DATE, \gmdate('Y-m-d'));
        $order->update_meta_data(self::META_AUDIENCE, $audience);
        $order->update_meta_data(self::META_WORDING, (string) $config['wording']);
    }

    /**
     * Surface the record on the admin order screen, so it is checkable without a
     * database query when someone queries whether a call was allowed.
     *
     * @param \WC_Order $order
     */
    public static function render_admin_summary($order): void
    {
        if (!$order instanceof \WC_Order) {
            return;
        }

        $permitted = (string) $order->get_meta(self::META_PERMITTED);

        if ($permitted === '') {
            return;
        }

        $email_permitted = (string) $order->get_meta(self::META_EMAIL_PERMITTED);

        $rows = [
            \__('Telephone contact', 'granola') => $permitted === self::PERMITTED_YES
                ? \__('Permitted', 'granola')
                : \__('Not permitted', 'granola'),
            \__('Email marketing', 'granola') => $email_permitted === self::PERMITTED_YES
                ? \__('Permitted', 'granola')
                : \__('Not permitted', 'granola'),
            \__('Telephone basis', 'granola') => (string) $order->get_meta(self::META_BASIS),
            \__('Email basis', 'granola') => (string) $order->get_meta(self::META_EMAIL_BASIS),
            \__('Recorded', 'granola') => (string) $order->get_meta(self::META_RECORDED),
            \__('Audience', 'granola') => (string) $order->get_meta(self::META_AUDIENCE),
            \__('Wording shown', 'granola') => (string) $order->get_meta(self::META_WORDING),
        ];

        echo '<h3>' . \esc_html__('Marketing permission', 'granola') . '</h3><p>';

        foreach ($rows as $label => $value) {
            echo '<strong>' . \esc_html((string) $label) . ':</strong> ' . \esc_html($value) . '<br />';
        }

        echo '</p>';
    }
}
