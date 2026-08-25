<?php

namespace Theme\WooCommerce;

/**
 * Works out which audience a checkout belongs to from the "Who am I?" field.
 *
 * The same question drives two unrelated features (sample shipping rates and the
 * French telephone consent question), so the matching lives here rather than being
 * duplicated. If the two ever disagree about what counts as a homeowner, one of them
 * silently does the wrong thing, and in the consent case that means calling someone
 * we are not allowed to call.
 */
class Audience
{
    public const CONSUMER = 'consumer';
    public const BUSINESS = 'business';
    public const UNKNOWN = 'unknown';

    /**
     * Values of the "Who am I?" field that mean the customer is a private individual.
     *
     * Matched loosely (accent and case insensitive, substring) because the option
     * labels are authored per locale in the Checkout Field Editor and have been
     * reworded before now.
     */
    private const HOMEOWNER_MATCH_TERMS = [
        'homeowner',
        'house owner',
        'hausbesitzer',
        'hauseigentuemer',
        'hauseigentumer',
        'bauherr',
        'proprietaire',
        'proprietario',
        'propietario',
        'woningeigenaar',
        'huis eigenaar',
    ];

    /**
     * Keys the "Who am I?" answer has been posted under across locales and versions.
     */
    private const FIELD_KEYS = [
        'who-am-i?',
        'who-am-i',
        'who_am_i',
        'billing_who-am-i?',
        'billing_who-am-i',
        'billing_who_am_i',
        'shipping_who-am-i?',
        'shipping_who-am-i',
        'shipping_who_am_i',
    ];

    /**
     * Resolve the audience from an already-parsed set of posted data.
     *
     * @param array<string, mixed> $posted_data
     */
    public static function from_posted_data(array $posted_data): string
    {
        $answer = self::find_answer($posted_data);

        if ($answer === null || $answer === '') {
            return self::UNKNOWN;
        }

        return self::value_is_homeowner($answer) ? self::CONSUMER : self::BUSINESS;
    }

    /**
     * Resolve the audience from the current request, falling back to the session.
     *
     * Covers the plain checkout POST, WooCommerce's `update_order_review` ajax call
     * (where the fields arrive url-encoded inside `post_data`), and the value cached
     * on the session by SampleShipping.
     */
    public static function from_request(): string
    {
        foreach (self::request_data_sets() as $data_set) {
            $audience = self::from_posted_data($data_set);

            if ($audience !== self::UNKNOWN) {
                return $audience;
            }
        }

        return self::UNKNOWN;
    }

    /**
     * @param array<string, mixed> $posted_data
     */
    public static function is_homeowner(array $posted_data): bool
    {
        return self::from_posted_data($posted_data) === self::CONSUMER;
    }

    /**
     * The raw "Who am I?" answer as the customer chose it, for the audit record.
     *
     * @param array<string, mixed> $posted_data
     */
    public static function find_answer(array $posted_data): ?string
    {
        foreach (self::FIELD_KEYS as $key) {
            if (array_key_exists($key, $posted_data) && is_string($posted_data[$key]) && $posted_data[$key] !== '') {
                return $posted_data[$key];
            }
        }

        foreach ($posted_data as $key => $value) {
            if (!is_string($key) || !is_string($value) || $value === '') {
                continue;
            }

            $normalized_key = strtolower($key);

            if (str_contains($normalized_key, 'who') && str_contains($normalized_key, 'am')) {
                return $value;
            }
        }

        return null;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private static function request_data_sets(): array
    {
        if (!function_exists('wp_unslash')) {
            return [];
        }

        $data_sets = [];

        // WooCommerce's update_order_review ajax call nests the whole form here.
        $post_data = $_POST['post_data'] ?? null;

        if (is_string($post_data) && $post_data !== '') {
            $parsed = [];
            parse_str((string) wp_unslash($post_data), $parsed);
            $data_sets[] = $parsed;
        }

        $raw_posted = [];

        foreach ($_POST as $key => $value) {
            if (!is_string($key)) {
                continue;
            }

            $raw_posted[$key] = is_string($value) ? wp_unslash($value) : $value;
        }

        $data_sets[] = $raw_posted;

        return $data_sets;
    }

    private static function value_is_homeowner(string $value): bool
    {
        $normalized = self::normalize($value);

        if ($normalized === '') {
            return false;
        }

        $terms = apply_filters('millboard/homeowner_match_terms', self::HOMEOWNER_MATCH_TERMS);

        if (!is_array($terms)) {
            $terms = self::HOMEOWNER_MATCH_TERMS;
        }

        foreach ($terms as $term) {
            if (!is_string($term)) {
                continue;
            }

            $normalized_term = self::normalize($term);

            if ($normalized_term !== '' && str_contains($normalized, $normalized_term)) {
                return true;
            }
        }

        return false;
    }

    private static function normalize(string $value): string
    {
        $normalized = strtolower(trim($value));
        $normalized = str_replace(['-', '_'], ' ', $normalized);

        if (function_exists('remove_accents')) {
            $normalized = remove_accents($normalized);
        }

        $normalized = preg_replace('/\s+/', ' ', $normalized);

        return is_string($normalized) ? $normalized : '';
    }
}
