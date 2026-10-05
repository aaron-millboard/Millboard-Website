<?php

namespace Granola\Components\HomeTrust;

function filter_args(array $args): ?array
{
    // -------------------------------------------------------------------------
    // Default arguments.
    // -------------------------------------------------------------------------
    $args = array_merge([
        'heading' => null,
        'embed' => null,
        'link' => [],
        'badges' => [],
        'classes' => [],
    ], $args);

    // -------------------------------------------------------------------------
    // Required classes.
    // -------------------------------------------------------------------------
    $args['classes'] = array_merge([
        'home-trust',
        'wp-block',
    ], $args['classes']);

    // -------------------------------------------------------------------------
    // Badges.
    //
    // A row with no image is an empty repeater row. Logos vary wildly in
    // aspect -- a stacked roundel against a wide wordmark -- so they are
    // capped on both axes rather than set to one height, which would make the
    // tall ones tower over the wide ones.
    // -------------------------------------------------------------------------
    $args['badges'] = array_values(array_filter((array) $args['badges'], function ($badge) {
        return !empty($badge['image']);
    }));

    $args['badges'] = array_map(function ($badge) {
        $badge['image']['size'] = 'medium';
        $badge['image']['classes'] = ['home-trust__badge-image'];

        return $badge;
    }, $args['badges']);

    // -------------------------------------------------------------------------
    // The widget, in the editor.
    //
    // Trustpilot's bootstrap replaces the widget div with a cross-origin
    // iframe. On the front end that is fine. Inside the editor canvas it is
    // not: core walks every frame in the document when it wires up rich text,
    // and reading a property off a cross-origin window throws a SecurityError
    // that takes the whole editor down with it.
    //
    // The editor gets a line of text instead. The front end is untouched, so
    // the live rating is still Trustpilot's own widget saying what is true
    // today.
    // -------------------------------------------------------------------------
    if (!empty($args['is_preview']) && !empty($args['embed'])) {
        $args['embed'] = '<p class="home-trust__widget-note">'
            . \esc_html__('Trustpilot rating, live on the published page.', 'granola')
            . '</p>';
    }

    // -------------------------------------------------------------------------
    // Link.
    // -------------------------------------------------------------------------
    if (!empty($args['link'])) {
        $args['link']['classes'][] = 'home-trust__link';
    }

    // -------------------------------------------------------------------------
    // Return the filtered args.
    // -------------------------------------------------------------------------
    return $args;
}
