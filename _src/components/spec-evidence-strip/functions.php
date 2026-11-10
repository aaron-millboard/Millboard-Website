<?php

namespace Granola\Components\SpecEvidenceStrip;

/**
 * Evidence strip: the points a specifier looks for (slip, loading, warranty,
 * documents), each with a short statement and a link to the document behind it.
 *
 * Looks like the trust-strip block (a ruled band, points on a fluid grid) with
 * two things that block does not do: a link under each point and a three-across
 * layout for six points. trust-strip is used on live pages and is left alone.
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
        'columns' => 'auto',
        'points' => [],
    ], $args);

    // -------------------------------------------------------------------------
    // Points: keep the ones that say something.
    // -------------------------------------------------------------------------
    $args['points'] = array_values(array_filter((array) $args['points'], static function ($point) {
        return !empty($point['label']);
    }));

    if (empty($args['points'])) {
        return null;
    }

    // -------------------------------------------------------------------------
    // Required classes.
    // -------------------------------------------------------------------------
    $args['classes'] = array_merge([
        'spec-strip',
        'wp-block',
        'alignfull',
    ], $args['classes']);

    $has_header = !empty($args['preheading']) || !empty($args['heading']) || !empty($args['intro']);

    if ($has_header) {
        $args['classes'][] = 'spec-strip--panel';
    }

    if ($args['columns'] === '3') {
        $args['classes'][] = 'spec-strip--three';
    }

    $args['has_header'] = $has_header;

    if (!empty($args['background']) && $args['background'] !== 'none') {
        $args['classes'][] = 'has-' . $args['background'] . '-background-color';
        $args['classes'][] = 'has-background';
    }

    return $args;
}
