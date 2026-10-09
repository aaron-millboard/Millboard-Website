<?php

/**
 * Maps WooCommerce checkout field values onto HubSpot property values.
 *
 * The whole point of this class is that HubSpot enumeration properties accept
 * the option's INTERNAL VALUE, not its label, and an invalid value is discarded
 * silently with a 200 response. That is exactly how the country field ended up
 * empty on 74 German contacts: the store sent "Deutschland" to a Select whose
 * only German option is "Germany".
 *
 * So nothing here guesses. Every value was read from the live property
 * definitions on 5 Aug 2026, and anything unrecognised is logged and skipped
 * rather than sent.
 */

declare(strict_types=1);

namespace Millboard\HubSpotOrderFields;

if (!defined('ABSPATH')) {
    exit;
}

final class Mapper
{
    /**
     * WooCommerce meta key => HubSpot contact property.
     *
     * `marketing-opt-in` is intentionally absent. See the plugin header.
     */
    private const FIELDS = [
        'project-size'              => 'project_size_dropdown',
        'project-start-time'        => 'de_project_start_time',
        'who-am-i'                  => 'who_am_i_de',
        'how-did-you-hear-about-us' => 'how_did_you_hear_about_us___cloned_',
    ];

    /**
     * Value translations, per HubSpot property.
     *
     * Where a property is omitted here, the WooCommerce value already matches
     * the HubSpot internal value exactly and is passed through untouched.
     *
     * Verified against the live German checkout and the HubSpot property
     * definitions. Note the en dash (U+2013) in the Monate values: it is the
     * same character on both sides, so those pass through.
     */
    private const VALUE_MAP = [
        // WooCommerce sends localised German labels here, but HubSpot's internal
        // values are English slugs, so this one genuinely has to be translated.
        // Without it the value is rejected even when it is transmitted.
        'how_did_you_hear_about_us___cloned_' => [
            'Soziale Medien'                       => 'social-media',
            'Suchmaschine'                         => 'search-engine',
            'Online-Werbung'                       => 'online-advertisement',
            'Freund, Familie oder Kollege'         => 'friend-family-or-colleague',
            'Event, Messe oder Branchenempfehlung' => 'event-trade-show-or-industry Recommendation',
            'Empfehlung für Installateure'         => 'Installer Recommendation',
            'E-Mail-Newsletter'                    => 'email-newsletter',
            'Blog, Artikel oder Online-Bewertung'  => 'blog-article-or-nline-review',
            'Einzelhandel oder Händler'            => 'retail-store-or-distributor',
            'Print- oder Außenwerbung'             => 'print-or-outdoor-advertisement',
            'Influencer'                           => 'Influencer',
            'Google Ads'                           => 'Google Ads',
            'Sonstiges'                            => 'other',
        ],

        // Values pass through, but "I don't know" is the one option whose
        // internal value differs from its label.
        'project_size_dropdown' => [
            'Ich weiß nicht'    => 'unknown_project_size',
            'Weiß nicht'        => 'unknown_project_size',
            "I don't know"      => 'unknown_project_size',
            'Keine Angabe'      => 'unknown_project_size',
        ],
    ];

    /**
     * Options that are valid without translation, so we can reject anything else
     * before it reaches HubSpot rather than after.
     */
    private const PASSTHROUGH_ALLOWED = [
        'project_size_dropdown' => [
            'unknown_project_size', '0-20m²', '21-50m²', '51-100m²', '100+m²',
        ],
        'de_project_start_time' => [
            'innerhalb eines Monats', '1–3 Monate', '3–6 Monate', '4–6 Monate', '6+ Monate',
        ],
        'who_am_i_de' => [
            'Bauherr / Hauseigentümer', 'Handwerker / Bauunternehmer', 'Architekt / Designer',
            'Händler / Wiederverkäufer', 'Kommerziell',
        ],
        'how_did_you_hear_about_us___cloned_' => [
            'Influencer', 'search-engine', 'online-advertisement', 'friend-family-or-colleague',
            'event-trade-show-or-industry Recommendation', 'email-newsletter',
            'blog-article-or-nline-review', 'retail-store-or-distributor',
            'print-or-outdoor-advertisement', 'other', 'social-media', 'Google Ads',
            'Social Media Ads', 'Installer Recommendation',
        ],
    ];

    /**
     * Read the custom fields off an order and produce HubSpot properties.
     *
     * @param \WC_Order $order
     * @return array{properties: array<string,string>, skipped: array<string,string>}
     */
    public static function from_order(\WC_Order $order): array
    {
        $properties = [];
        $skipped    = [];

        foreach (self::FIELDS as $meta_key => $property) {
            $raw = self::read_meta($order, $meta_key);

            if ($raw === '') {
                continue;
            }

            $mapped = self::translate($property, $raw);

            if ($mapped === null) {
                // Log it rather than sending something HubSpot will discard.
                $skipped[$property] = $raw;
                continue;
            }

            $properties[$property] = $mapped;
        }

        return [
            'properties' => $properties,
            'skipped'    => $skipped,
        ];
    }

    /**
     * The checkout field editor stores values with or without a leading
     * underscore depending on how the field was created, so try both.
     */
    private static function read_meta(\WC_Order $order, string $key): string
    {
        foreach ([$key, '_' . $key] as $candidate) {
            $value = $order->get_meta($candidate, true);

            if (is_scalar($value) && trim((string) $value) !== '') {
                return trim((string) $value);
            }
        }

        return '';
    }

    /**
     * Translate one value, or return null when it cannot be mapped safely.
     */
    private static function translate(string $property, string $value): ?string
    {
        $map = self::VALUE_MAP[$property] ?? [];

        if (isset($map[$value])) {
            return $map[$value];
        }

        $allowed = self::PASSTHROUGH_ALLOWED[$property] ?? null;

        // No allow-list recorded for this property, so pass the value through
        // and let HubSpot decide. Should not happen with the fields above.
        if ($allowed === null) {
            return $value;
        }

        if (in_array($value, $allowed, true)) {
            return $value;
        }

        return null;
    }

    /**
     * Exposed for the admin screen so the mapping can be eyeballed without
     * reading the source.
     *
     * @return array<string,string>
     */
    public static function fields(): array
    {
        return self::FIELDS;
    }
}
