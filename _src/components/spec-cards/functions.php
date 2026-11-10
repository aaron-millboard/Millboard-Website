<?php

namespace Granola\Components\SpecCards;

/**
 * Bordered cards for a range, a zone or a step: title, image, description, a
 * short list of key facts, up to three document links and a button.
 *
 * Drawn like the cards-content block (bordered, centred, title above the
 * image) but always showing its description and button, where cards-content
 * hides the button until hover, and it takes up to six cards three across.
 * cards-content is used on live pages and is left alone.
 */
function filter_args(array $args): ?array
{
    // -------------------------------------------------------------------------
    // Default arguments.
    // -------------------------------------------------------------------------
    $args = array_merge([
        'classes' => [],
        'attributes' => [],
        'preheading' => '',
        'heading' => '',
        'intro' => '',
        'columns' => '3',
        'ratio' => '3-2',
        'cards' => [],
    ], $args);

    // -------------------------------------------------------------------------
    // Cards.
    // -------------------------------------------------------------------------
    $args['cards'] = array_values(array_filter((array) $args['cards'], static function ($card) {
        return !empty($card['title']);
    }));

    if (empty($args['cards'])) {
        return null;
    }

    foreach ($args['cards'] as $key => $card) {
        if (!empty($card['image'])) {
            $args['cards'][$key]['image_data'] = [
                'attachment_id' => $card['image'],
                'size' => 'large',
                // The photograph shows the product (colour, finish), which the card text does not, so it keeps the library alt text.
                'alt' => (string) \get_post_meta((int) $card['image'], '_wp_attachment_image_alt', true),
            ];
        }

        $args['cards'][$key]['facts'] = array_values(array_filter((array) ($card['facts'] ?? []), static function ($fact) {
            return !empty($fact['label']) && !empty($fact['value']);
        }));

        $args['cards'][$key]['links'] = array_values(array_filter(array_map(static function ($row) {
            return $row['link'] ?? null;
        }, (array) ($card['links'] ?? [])), static function ($link) {
            return !empty($link['url']);
        }));

        if (!empty($card['cta']['url'])) {
            $args['cards'][$key]['cta_data'] = [
                'title' => $card['cta']['title'] ?? '',
                'url' => $card['cta']['url'],
                'attributes' => [
                    'target' => $card['cta']['target'] ?? '',
                    'rel' => $card['cta']['rel'] ?? '',
                    'aria-label' => trim(($card['cta']['title'] ?? '') . ': ' . \wp_strip_all_tags($card['title'])),
                ],
                'classes' => ['spec-cards__cta', 'g-button'],
            ];
        }
    }

    // -------------------------------------------------------------------------
    // Required classes.
    // -------------------------------------------------------------------------
    $args['classes'] = array_merge([
        'spec-cards',
        'wp-block',
        'alignfull',
        'spec-cards--cols-' . ($args['columns'] === '2' ? '2' : '3'),
        'spec-cards--ratio-' . (in_array($args['ratio'], ['3-2', '16-10', '1-1'], true) ? $args['ratio'] : '3-2'),
    ], $args['classes']);

    if (!empty($args['background']) && $args['background'] !== 'none') {
        $args['classes'][] = 'has-' . $args['background'] . '-background-color';
        $args['classes'][] = 'has-background';
    }

    return $args;
}
