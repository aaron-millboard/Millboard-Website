<?php

namespace Granola\Components\ModelloCreator;

/**
 * The Modello laying-pattern tool.
 *
 * Twelve rows of boards. Each row can be dragged left or right in whole steps
 * (one step is 1/18 of a board) and flipped through 180 degrees. A preset is
 * those twelve offsets in row order; a trailing "r" means the board is flipped.
 * Recovered from the original tool, which shared one preset list between
 * Linear and Contour.
 */
const ROWS = 12;

/**
 * The preset patterns, keyed by pattern number.
 *
 * @return array<string, string[]> Twelve step values per pattern.
 */
function presets(): array
{
    $presets = [
        '1' => '3,-3,3,-3,3,-3,3,-3,3,-3,3,-3',
        '11' => '4,-4,4,-4,4,-4,4,-4,4,-4,4,-4',
        '12' => '-4,0,4,-4,0,4,-4,0,4,-4,0,4',
        '13' => '-4,4,0,4,-4,4,0,4,-4,4,0,4',
        '14' => '-7,1,7,1,-7,1,7,1,-7,1,7,1',
        '15' => '-7,7,1,-1,-7,7,1,-1,-7,7,1,-1',
        '17' => '4,0,2,0,4,-4,4,0,2,0,4,-4',
        '2' => '3r,-3,3r,-3,3r,-3,3r,-3,3r,-3,3r,-3',
        '21' => '4r,-4,4r,-4,4r,-4,4r,-4,4r,-4,4r,-4',
        '22' => '-4,0r,4,-4r,0,4r,-4,0r,4,-4r,0,4r',
        '23' => '-4,4r,0,4r,-4,4r,0,4r,-4,4r,0,4r',
        '26' => '-7,7r,1,-7r,7,1r,-7,7r,1,-7r,7,1r',
        '3' => '3r,-3r,3r,-3,3,-3,3r,-3r,3r,-3,3,-3',
        '32' => '0r,4r,-4r,0,4,-4,0r,4r,-4r,0,4,-4',
        '37' => '4r,2r,-4r,4,2,-4,4r,2r,-4r,4,2,-4',
        '4' => '3r,-3,3,-3r,3,-3,3r,-3,3,-3r,3,-3',
        '43' => '4,0,4r,-4,4,0r,4,-4,4r,0,4,-4r',
        '45' => '7,1,-1r,-7,7,1r,-1,-7,7r,1,-1,-7r',
        '47' => '4r,2,-4,4r,2,-4,4r,2,-4,4r,2,-4',
        '5' => '3r,-3r,3,-3,3r,-3r,3,-3,3r,-3r,3,-3',
        '53' => '4r,0r,4,-4,4r,0r,4,-4,4r,0r,4,-4',
        '6' => '3r,-3r,3r,-3r,3,-3,3,-3,3r,-3r,3r,-3r',
        '7' => '3r,-3,3,-3,3,-3,3r,-3,3,-3,3,-3',
        '72' => '3r,0,-3,3,0,-3,3r,0,-3,3,0,-3',
    ];

    return \array_map(fn ($steps) => \explode(',', $steps), $presets);
}

/**
 * The Modello colours, keyed by the slug the board images are named after.
 *
 * @return array<string, string> Slug => translated name.
 */
function colours(): array
{
    return [
        'antique-oak' => \_x('Antique Oak', 'Modello colour', 'granola'),
        'ashwood' => \_x('Ashwood', 'Modello colour', 'granola'),
        'brushed-basalt' => \_x('Brushed Basalt', 'Modello colour', 'granola'),
        'burnt-cedar' => \_x('Burnt Cedar', 'Modello colour', 'granola'),
        'coppered-oak' => \_x('Coppered Oak', 'Modello colour', 'granola'),
        'ebony-grey' => \_x('Ebony Grey', 'Modello colour', 'granola'),
        'golden-oak' => \_x('Golden Oak', 'Modello colour', 'granola'),
        'jarrah' => \_x('Jarrah', 'Modello colour', 'granola'),
        'limed-oak' => \_x('Limed Oak', 'Modello colour', 'granola'),
        'smoked-oak' => \_x('Smoked Oak', 'Modello colour', 'granola'),
    ];
}

function filter_args(?array $args): ?array
{
    // Bail extra early - args have been nulled.
    if (\is_null($args)) {
        return null;
    }

    // -------------------------------------------------------------------------
    // Default arguments.
    // -------------------------------------------------------------------------
    $args = \array_merge([
        'classes' => [],
        'heading' => '',
        'intro' => '',
        'instruction' => '',
        'default_collection' => 'contour',
        'default_colour' => 'antique-oak',
    ], $args);

    // -------------------------------------------------------------------------
    // Required classes.
    // -------------------------------------------------------------------------
    $args['classes'] = \array_merge([
        'modello-creator',
        'wp-block',
        'alignwide',
    ], $args['classes']);

    // Plain defaults, deliberately free of numeric or absolute claims. Editors
    // can replace any of them in the block fields.
    $args['heading'] = $args['heading'] ?: \__('Laying patterns', 'granola');
    $args['intro'] = $args['intro'] ?: \__('Offset the same Modello board to create a range of laying patterns. Choose a pattern below, or drag and flip the boards to design your own.', 'granola');
    $args['instruction'] = $args['instruction'] ?: \__('Drag the boards to the left or right, or click on them to flip them.', 'granola');

    $args['collections'] = [
        'linear' => \_x('Linear', 'Modello collection', 'granola'),
        'contour' => \_x('Contour', 'Modello collection', 'granola'),
    ];
    $args['colours'] = colours();

    if (!isset($args['collections'][$args['default_collection']])) {
        $args['default_collection'] = 'contour';
    }

    if (!isset($args['colours'][$args['default_colour']])) {
        $args['default_colour'] = 'antique-oak';
    }

    $presets = presets();
    $args['preset_ids'] = \array_keys($presets);
    $args['rows'] = ROWS;

    // Everything the script needs, including every string it prints.
    $args['config'] = [
        'imagesUrl' => \trailingslashit(\Granola\Paths::theme_asset_url('images/modello-creator')),
        'presets' => \array_values($presets),
        'rows' => ROWS,
        'i18n' => [
            'pattern' => \__('Pattern', 'granola'),
            /* translators: 1: board number, 2: its offset, 3: whether it is flipped. */
            'board' => \__('Board %1$s, %2$s, %3$s', 'granola'),
            'centred' => \__('centred', 'granola'),
            /* translators: %s: number of steps. */
            'right' => \__('%s steps right', 'granola'),
            /* translators: %s: number of steps. */
            'left' => \__('%s steps left', 'granola'),
            'flipped' => \__('flipped', 'granola'),
            'notFlipped' => \__('not flipped', 'granola'),
        ],
    ];

    return $args;
}
