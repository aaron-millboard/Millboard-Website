<?php

namespace Granola\Components\CardsContent;

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
        'header_cta' => [],
        'cards' => [],
    ], $args);

    // -------------------------------------------------------------------------
    // Required classes.
    // -------------------------------------------------------------------------
    $args['classes'] = array_merge([
        'cards-content',
        'wp-block',
        'alignfull',
    ], $args['classes']);

    // -------------------------------------------------------------------------
    // Bail early if no content
    // -------------------------------------------------------------------------
    if (empty($args['cards'])) {
        return null;
    }

    // -------------------------------------------------------------------------
    // Process header CTA
    // -------------------------------------------------------------------------
    if (!empty($args['header_cta'])) {
        $args['header_cta_data'] = [
            'title'    => $args['header_cta']['title'] ?? '',
            'url'      => $args['header_cta']['url'] ?? '',
            'attributes' => [
                'target' => $args['header_cta']['target'] ?? '',
                'rel'    => $args['header_cta']['rel'] ?? '',
            ],
            'classes' => [
                'cards-content__header-cta',
                'g-button',
            ],
        ];
    }

    // -------------------------------------------------------------------------
    // Process cards
    // -------------------------------------------------------------------------
    if (!empty($args['cards']) && is_array($args['cards'])) {
        foreach ($args['cards'] as $key => $card) {
            // Process image
            if (!empty($card['image'])) {
                $args['cards'][$key]['image_data'] = [
                    'attachment_id' => $card['image'],
                    'size' => 'large'
                ];
            }

            // Process CTA
            if (!empty($card['cta'])) {
                $args['cards'][$key]['cta_data'] = [
                    'title'    => $card['cta']['title'] ?? '',
                    'url'      => $card['cta']['url'] ?? '',
                    'attributes' => [
                        'target' => $card['cta']['target'] ?? '',
                        'rel'    => $card['cta']['rel'] ?? '',
                    ],
                    'classes' => [
                        'cards-content__card-cta'
                    ],
                ];
            }
        }
    }

    // -------------------------------------------------------------------------
    // Return the filtered args.
    // -------------------------------------------------------------------------
    return $args;
}
