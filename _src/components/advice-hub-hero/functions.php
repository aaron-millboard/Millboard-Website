<?php

namespace Granola\Components\AdviceHubHero;

function filter_args(array $args): ?array
{
    // -------------------------------------------------------------------------
    // Default arguments.
    // -------------------------------------------------------------------------
    $args = array_merge([
        'image' => null,
        'image_alt' => '',
        'eyebrow' => null,
        'heading' => null,
        'standfirst' => null,
        'search_placeholder' => null,
        'popular_label' => null,
        'quick_links' => [],
        'classes' => [],
    ], $args);

    // -------------------------------------------------------------------------
    // Required classes.
    // -------------------------------------------------------------------------
    $args['classes'] = array_merge([
        'advice-hub-hero',
        'wp-block',
    ], $args['classes']);

    // -------------------------------------------------------------------------
    // Image.
    //
    // An <img>, not a CSS background as the design draws it, so it gets a
    // srcset and the browser fetches a size that fits the screen. It is the
    // largest thing above the fold, so it loads eagerly and at high priority:
    // lazy-loading the LCP image is the most common way to lose a second.
    // -------------------------------------------------------------------------
    if (!empty($args['image'])) {
        $args['image'] = [
            'attachment_id' => $args['image']['attachment_id'] ?? 0,
            'alt' => (string) $args['image_alt'],
            'size' => 'full',
            'sizes' => '100vw',
            'loading' => 'eager',
            'classes' => ['advice-hub-hero__image', 'skip-lazy'],
            'attributes' => [
                'fetchpriority' => 'high',
                'data-no-lazy' => '1',
            ],
        ];

        \Theme\Utils\Advice::mark_image_shown((int) $args['image']['attachment_id']);
    }

    // -------------------------------------------------------------------------
    // Search.
    //
    // A plain GET to the site's own search, held to advice articles by the
    // post_type field. It needs no script and works with the URL on its own,
    // which is also what the SearchAction in the schema points at.
    // -------------------------------------------------------------------------
    $args['search'] = [
        'action' => \home_url('/'),
        'post_type' => \Theme\Utils\Advice::POST_TYPE,
        'input_id' => \wp_unique_id('advice-hub-search-'),
        'value' => isset($_GET['s']) ? \sanitize_text_field(\wp_unslash($_GET['s'])) : '',
        'placeholder' => $args['search_placeholder'] ?: \__('Search advice', 'granola'),
    ];

    // -------------------------------------------------------------------------
    // Quick links. A row with no URL is an empty repeater row.
    // -------------------------------------------------------------------------
    $args['quick_links'] = array_values(array_filter(array_map(function ($row) {
        return $row['link'] ?? null;
    }, (array) $args['quick_links']), function ($link) {
        return !empty($link['url']) && !empty($link['title']);
    }));

    // -------------------------------------------------------------------------
    // Return the filtered args.
    // -------------------------------------------------------------------------
    return $args;
}
