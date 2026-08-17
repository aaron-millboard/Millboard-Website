<?php

namespace Granola\Components\InstallerCoverage;

function filter_args(array $args): ?array
{
    $args = array_merge([
        'classes' => [],
        'heading' => '',
        'intro' => '',
        'map_type' => 'embed',
        'map_side' => 'left',
        'map_embed' => '',
        'map_image' => null,
        'display' => 'rows',
        'coverage' => [],
        'button' => null,
    ], $args);

    $args['classes'] = array_merge([
        'installer-coverage',
        'wp-block',
    ], $args['classes']);

    if (($args['map_side'] ?? 'left') === 'right') {
        $args['classes'][] = 'installer-coverage--map-right';
    }

    // Normalise coverage rows; split towns into a list for the chips display.
    $args['coverage'] = array_values(array_filter(array_map(function ($row) {
        $towns = isset($row['towns']) ? (string) $row['towns'] : '';
        $list = array_values(array_filter(array_map('trim', preg_split('/[,\x{00B7}\n]+/u', $towns) ?: [])));
        return [
            'county' => $row['county'] ?? '',
            'towns' => $towns,
            'towns_list' => $list,
        ];
    }, (array) $args['coverage']), function ($row) {
        return !empty($row['county']) || !empty($row['towns']);
    }));

    if ($args['map_type'] === 'embed' && !empty($args['map_embed'])) {
        $args['map_embed'] = opt_out_of_lazy_loading((string) $args['map_embed']);
    }

    $args['has_map'] = ($args['map_type'] === 'embed' && !empty($args['map_embed']))
        || ($args['map_type'] === 'image' && !empty($args['map_image']));

    // Bail early - return null for no output (unless previewing in the editor).
    if (!$args['has_map'] && empty($args['coverage']) && empty($args['is_preview'])) {
        return null;
    }

    return $args;
}

/**
 * Marks a pasted map embed so Perfmatters leaves its iframe alone.
 *
 * Perfmatters' iframe lazy loading moves `src` to `data-src` and relies on its own
 * script to put it back. With script delaying on that never runs, so the map rendered
 * as an empty box on every installer profile. The distributor location map opts out
 * with these same two attributes, but that iframe is written by the theme; this one is
 * pasted into an ACF field by an editor, so they have to be added here.
 *
 * Only the first iframe is touched, and only attributes that are missing, so an embed
 * that already carries them is left as it is.
 */
function opt_out_of_lazy_loading(string $embed): string
{
    if ($embed === '' || stripos($embed, '<iframe') === false) {
        return $embed;
    }

    return (string) preg_replace_callback('~<iframe\b([^>]*)>~i', function (array $matches): string {
        $attributes = $matches[1];

        if (stripos($attributes, 'data-no-lazy') === false) {
            $attributes .= ' data-no-lazy="1"';
        }

        if (stripos($attributes, 'skip-lazy') !== false) {
            return '<iframe' . $attributes . '>';
        }

        // Append to an existing class attribute rather than adding a second one.
        if (preg_match('~\sclass\s*=\s*(["\'])(.*?)\1~i', $attributes)) {
            $attributes = (string) preg_replace(
                '~(\sclass\s*=\s*(["\']))(.*?)(\2)~i',
                '$1$3 skip-lazy$4',
                $attributes,
                1
            );
        } else {
            $attributes .= ' class="skip-lazy"';
        }

        return '<iframe' . $attributes . '>';
    }, $embed, 1);
}
