<?php

namespace Theme\WooCommerce;

/**
 * "May we contact you on WhatsApp?" at checkout, and the number if the answer is yes.
 *
 * The Checkout Field Editor cannot show a field conditionally, so the question and the
 * number both live here. Same layering as ConsentFields: the plugin builds the field
 * array at priority 1000 and this class works on it at 1001. The question reuses the
 * Checkout Field Editor's key, `whatsapp-opt-in`, so a copy of it that still exists in
 * the editor is replaced rather than shown twice, and a site that never had one still
 * gets the question.
 *
 * Three mechanisms, kept apart on purpose:
 *
 *  1. The checkout JS hides the number row until Yes is chosen. Cosmetic.
 *  2. `woocommerce_checkout_posted_data` throws the number away unless the answer is
 *     yes, and normalises it when it is. This is the authoritative step.
 *  3. `woocommerce_checkout_create_order` records what was answered and when.
 *
 * Both fields are required and neither has a default. `required` stays false in the
 * field array, as in ConsentFields: the number is only required when the answer is yes,
 * which a field array cannot say, and WooCommerce's own message would quote the whole
 * legend sentence as the field name. validate() enforces both instead, and the
 * asterisks are drawn here.
 */
class WhatsAppFields
{
    /**
     * The question's field key. Same as the Checkout Field Editor's, deliberately.
     */
    public const QUESTION_FIELD = 'whatsapp-opt-in';

    public const NUMBER_FIELD = 'whatsapp-number';

    /**
     * Custom `woocommerce_form_field` type, drawn by this class so the submitted values
     * are `yes` / `no` rather than whatever text the option happens to display.
     */
    private const FIELD_TYPE = 'millboard_whatsapp_question';

    public const ANSWER_YES = 'yes';
    public const ANSWER_NO = 'no';

    /**
     * Order meta, written on every order on a configured locale, refusals included.
     * The Checkout Field Editor skips empty values, so it records neither a "no" nor a
     * blank number on its own.
     *
     * META_ANSWER is `true` / `false` because that is what the HubSpot contact property
     * `whatsapp_opt_in` accepts: HubSpot silently drops any other value for an
     * enumeration. It is NOT `whatsapp-opt-in`, which the Checkout Field Editor also
     * writes (as `yes`) while its copy of the field exists, and would overwrite.
     */
    public const META_ANSWER = 'whatsapp-contact-permitted';
    public const META_NUMBER = 'whatsapp-number';
    public const META_RECORDED = 'whatsapp-opt-in-recorded';
    public const META_WORDING = 'whatsapp-opt-in-wording';

    /**
     * Per-locale configuration. A locale that is not listed is untouched.
     *
     * `wording` is stamped on every order so a later copy change does not rewrite what
     * earlier customers were shown. Bump it whenever a string the customer reads here
     * changes, including the option labels.
     *
     * @return array<string, array<string, mixed>>
     */
    private static function locales(): array
    {
        $locales = [
            'en_GB' => [
                'wording' => 'gb-2026-11-v1',
                'legend' => 'We’d welcome the opportunity to discuss your project with you on WhatsApp. Please let us know whether you’re happy to be contacted this way. You can change your preference at any time.',
                'options' => [
                    self::ANSWER_YES => 'Yes',
                    self::ANSWER_NO => 'No',
                ],
                'number_label' => 'WhatsApp number',
                'question_error' => 'Please tell us whether you are happy to be contacted on WhatsApp.',
                'number_error' => 'Please enter your WhatsApp number, including the country code if it is not a UK number.',
                // A number typed without a country code is read as national, so
                // 07700 900123 becomes +447700900123.
                'dialling_code' => '44',
            ],
        ];

        return apply_filters('millboard/whatsapp_locales', $locales);
    }

    public static function init(): void
    {
        if (self::config() === null) {
            return;
        }

        // 1001 so the Checkout Field Editor (1000) has already built the array.
        \add_filter('woocommerce_checkout_fields', [__CLASS__, 'add_fields'], 1001);
        \add_filter('woocommerce_form_field_' . self::FIELD_TYPE, [__CLASS__, 'render_question'], 10, 4);
        \add_filter('woocommerce_checkout_posted_data', [__CLASS__, 'resolve_posted_data']);
        \add_action('woocommerce_after_checkout_validation', [__CLASS__, 'validate'], 10, 2);
        \add_action('woocommerce_checkout_create_order', [__CLASS__, 'record'], 10, 2);
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
     * @param array<string, array<string, mixed>> $fields
     * @return array<string, array<string, mixed>>
     */
    public static function add_fields(array $fields): array
    {
        $config = self::config();

        if ($config === null || !isset($fields['billing']) || !is_array($fields['billing'])) {
            return $fields;
        }

        // Keep the place the question already has if the Checkout Field Editor placed
        // it, otherwise go after the marketing objection.
        $priority = (int) ($fields['billing'][self::QUESTION_FIELD]['priority']
            ?? ((int) ($fields['billing']['marketing-opt-in']['priority'] ?? 160) + 10));

        $fields['billing'][self::QUESTION_FIELD] = [
            'type' => self::FIELD_TYPE,
            'label' => $config['legend'],
            'options' => $config['options'],
            'required' => false, // Enforced in validate(), see the class comment.
            'priority' => $priority,
            'class' => ['form-row-wide', 'mb-consent', 'mb-whatsapp'],
            'validate' => [],
        ];

        // A plain tel field, so it is drawn exactly like Phone: same label, same input.
        $fields['billing'][self::NUMBER_FIELD] = [
            'type' => 'tel',
            'label' => $config['number_label'],
            'required' => false, // Enforced in validate(), see the class comment.
            'autocomplete' => 'tel',
            'priority' => $priority + 1,
            'class' => ['form-row-wide', 'mb-whatsapp-number'],
            'validate' => [],
        ];

        return $fields;
    }

    /**
     * The question, drawn in the same markup the consent questions use, so it picks up
     * their type size and spacing, plus `mb-whatsapp` for square boxes.
     *
     * @param string $field Markup WooCommerce built, discarded.
     * @param string $key
     * @param array<string, mixed> $args
     * @param string|null $value
     */
    public static function render_question($field, $key, $args, $value): string
    {
        $classes = array_unique((array) ($args['class'] ?? []));

        $html = '<div class="form-row ' . \esc_attr(implode(' ', $classes)) . '"'
            . ' id="' . \esc_attr($key) . '_field"'
            . ' data-priority="' . \esc_attr((string) ($args['priority'] ?? '')) . '">'
            . '<fieldset class="mb-consent__fieldset">'
            . '<legend class="mb-consent__legend">' . \esc_html((string) ($args['label'] ?? ''))
            . '&nbsp;<span class="required" aria-hidden="true">*</span></legend>';

        foreach ((array) ($args['options'] ?? []) as $option_value => $option_label) {
            $option_value = (string) $option_value;
            $input_id = $key . '_' . \sanitize_html_class($option_value);

            $html .= '<label class="mb-consent__option" for="' . \esc_attr($input_id) . '">'
                . '<input type="radio" required id="' . \esc_attr($input_id) . '"'
                . ' name="' . \esc_attr($key) . '"'
                . ' value="' . \esc_attr($option_value) . '"'
                . \checked((string) $value, $option_value, false)
                . ' /> <span>' . \esc_html((string) $option_label) . '</span></label>';
        }

        return $html . '</fieldset></div>';
    }

    /**
     * Resolve the conditional on the server. The browser decides what to show, never
     * what is recorded: the number survives only on a yes, and only in a clean form.
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public static function resolve_posted_data(array $data): array
    {
        if (!array_key_exists(self::QUESTION_FIELD, $data)) {
            return $data; // Not this form (the field is not on it).
        }

        if (self::answer($data) !== self::ANSWER_YES) {
            $data[self::NUMBER_FIELD] = '';

            return $data;
        }

        // Left as typed when it cannot be read, so validate() can reject it.
        $data[self::NUMBER_FIELD] = self::normalise((string) ($data[self::NUMBER_FIELD] ?? '')) ?? '';

        return $data;
    }

    /**
     * @param array<string, mixed> $data Already resolved by resolve_posted_data().
     * @param \WP_Error $errors
     */
    public static function validate($data, $errors): void
    {
        $config = self::config();

        if (!is_array($data) || !$errors instanceof \WP_Error || $config === null) {
            return;
        }

        if (!array_key_exists(self::QUESTION_FIELD, $data)) {
            return;
        }

        $answer = self::answer($data);

        if ($answer === '') {
            $errors->add('required-field', (string) $config['question_error'], ['id' => self::QUESTION_FIELD]);

            return;
        }

        // An unreadable number was blanked by resolve_posted_data(), so empty covers
        // both "left it blank" and "typed something that is not a number".
        if ($answer === self::ANSWER_YES && (string) ($data[self::NUMBER_FIELD] ?? '') === '') {
            $errors->add('required-field', (string) $config['number_error'], ['id' => self::NUMBER_FIELD]);
        }
    }

    /**
     * @param \WC_Order $order
     * @param array<string, mixed> $data
     */
    public static function record($order, $data): void
    {
        $config = self::config();

        if (!$order instanceof \WC_Order || !is_array($data) || $config === null) {
            return;
        }

        // The question was not on this checkout, so nothing was asked. Writing "no"
        // here would turn "never asked" into "declined".
        if (!array_key_exists(self::QUESTION_FIELD, $data)) {
            return;
        }

        $answer = self::answer($data);

        if ($answer === '') {
            return; // Unreachable once validate() has run; do not invent an answer.
        }

        $order->update_meta_data(self::META_ANSWER, $answer === self::ANSWER_YES ? 'true' : 'false');
        $order->update_meta_data(self::META_NUMBER, $answer === self::ANSWER_YES ? (string) ($data[self::NUMBER_FIELD] ?? '') : '');
        $order->update_meta_data(self::META_RECORDED, \gmdate('c'));
        $order->update_meta_data(self::META_WORDING, (string) $config['wording']);
    }

    /**
     * @param array<string, mixed> $data
     */
    private static function answer(array $data): string
    {
        $answer = strtolower(trim((string) ($data[self::QUESTION_FIELD] ?? '')));

        return in_array($answer, [self::ANSWER_YES, self::ANSWER_NO], true) ? $answer : '';
    }

    /**
     * Phone number to international format, or null if it does not look like one.
     *
     * Not a validity check, which needs a number-plan library. It catches typos and
     * rubbish and gives HubSpot and any WhatsApp tooling one shape to expect.
     */
    private static function normalise(string $raw): ?string
    {
        $number = (string) preg_replace('/[\s().\-]/', '', $raw);

        if ($number === '') {
            return null;
        }

        if (strpos($number, '00') === 0) {
            $number = '+' . substr($number, 2);
        } elseif ($number[0] === '0') {
            $number = '+' . (string) (self::config()['dialling_code'] ?? '44') . substr($number, 1);
        }

        return preg_match('/^\+[1-9]\d{7,14}$/', $number) === 1 ? $number : null;
    }
}
