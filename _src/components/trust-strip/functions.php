<?php

namespace Granola\Components\TrustStrip;

/**
 * A short band of reassurance points beneath a category header.
 *
 * Nothing here is derived or defaulted. Every figure is typed in by an editor,
 * because everything this block can display is a claim: a review score, a
 * warranty term, a country of manufacture. The design arrived with placeholder
 * review figures and a warranty term that belongs to another market, so the
 * block ships empty rather than carrying either into a page.
 */
function filter_args(array $args): ?array
{
    // -------------------------------------------------------------------------
    // Default arguments.
    // -------------------------------------------------------------------------
    $args = array_merge([
        'classes' => [],
        'items' => [],
    ], $args);

    // -------------------------------------------------------------------------
    // Required classes.
    // -------------------------------------------------------------------------
    $args['classes'] = array_merge([
        'trust-strip',
        'wp-block',
        'alignfull',
    ], $args['classes']);

    $args['items'] = normalise_items($args['items']);

    // -------------------------------------------------------------------------
    // Bail early if nothing has been filled in. An empty band is worse than no
    // band.
    // -------------------------------------------------------------------------
    if (empty($args['items'])) {
        return null;
    }

    return $args;
}

/**
 * Tidy the rows, dropping any that would render a half-finished claim.
 *
 * @param array $items The rows.
 * @return array The items.
 */
function normalise_items(array $items): array
{
    $normalised = [];

    foreach ($items as $item) {
        $type = ($item['type'] ?? 'statement') === 'rating' ? 'rating' : 'statement';
        $detail = trim((string) ($item['detail'] ?? ''));

        if ($type === 'rating') {
            $rating = normalise_rating($item);

            // A score with no count, or a count with no score, is not a claim
            // worth making. Drop the row rather than show half of it.
            if ($rating === null) {
                continue;
            }

            $normalised[] = [
                'type' => 'rating',
                'rating' => $rating['value'],
                'rating_display' => $rating['display'],
                'out_of' => $rating['out_of'],
                'stars' => $rating['stars'],
                'label' => trim((string) ($item['label'] ?? '')),
                'detail' => $detail,
                'url' => trim((string) ($item['url'] ?? '')),
            ];

            continue;
        }

        $label = trim((string) ($item['label'] ?? ''));

        if ($label === '') {
            continue;
        }

        $normalised[] = [
            'type' => 'statement',
            'label' => $label,
            'detail' => $detail,
            'url' => trim((string) ($item['url'] ?? '')),
        ];
    }

    return $normalised;
}

/**
 * Validate a review score.
 *
 * @param array $item The row.
 * @return array|null The rating parts, or null if it is not usable.
 */
function normalise_rating(array $item): ?array
{
    $value = (float) ($item['rating'] ?? 0);
    $out_of = (float) ($item['out_of'] ?? 5);

    if ($value <= 0 || $out_of <= 0 || $value > $out_of) {
        return null;
    }

    // Whole stars only, so the mark can never overstate the score.
    $stars = (int) floor(($value / $out_of) * 5);

    return [
        'value' => $value,
        'display' => \number_format_i18n($value, $value == (int) $value ? 0 : 1),
        'out_of' => $out_of,
        'stars' => max(0, min(5, $stars)),
    ];
}
