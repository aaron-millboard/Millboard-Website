<?php

namespace Granola\Components\CardsManual;

function filter_args(array $args): ?array
{
    // ---------------------------------------
    // Default arguments.
    // ---------------------------------------
    $args = array_merge([
        'classes' => [],
        'inner_attributes' => [
            'class' => ['cards__inner'],
        ],
        // Config.
        'columns' => null,
    ], $args);

    // ---------------------------------------
    // Required classes.
    // ---------------------------------------
    $args['classes'] = array_merge([
        'cards-manual',
        'cards',
        'wp-block',
        'animate',
    ], $args['classes']);

    // Handle count inner blocks.
    if (isset($args['count_inner_blocks'])) {
        // Bail early if no inner blocks and not in admin.
        if ($args['count_inner_blocks'] === 0 && !is_admin()) {
            return null;
        }

        $args['inner_attributes']['style']['--cards-custom--count-inner-blocks'] = $args['count_inner_blocks'];
        $args['inner_attributes']['style']['--cards--best-column-fit'] = \Granola\Components\Cards\best_column_count(
            $args['count_inner_blocks'],
            (int) $args['columns']
        );
    }

    // Handle shared card args logic.
    $args = \Granola\Components\Cards\handle_shared_card_args_logic($args);

    // ---------------------------------------
    // Set up the innerblocks tag.
    // ---------------------------------------
    $innerblocks_attrs = [
        'class' => 'cards__items',
        'allowedBlocks' => ['acf/card'],
        'template' => [
            ['acf/card'],
            ['acf/card'],
        ],
        'templateLock' => false,
    ];

    $args['innerblocks_tag'] = \Granola\Helpers::build_inner_blocks_tag($innerblocks_attrs);
    // -------------------------------------------------------------------------
    // Return the filtered args.
    // -------------------------------------------------------------------------
    return $args;
}


/**
 * Gets the count of total inner blocks.
 *
 * @link https://developer.wordpress.org/reference/hooks/render_block_data/
 *
 * @param array $parsed_block The parsed block.
 * @param array $source_block The source block.
 * @param \WP_Block_Parser_Block|null $parent_block The parent block.
 * @return array The filtered parsed block.
 */
function get_count_of_total_inner_blocks(array $parsed_block): array
{
    if ($parsed_block['blockName'] === 'acf/cards-manual') {
        $parsed_block['attrs']['count_inner_blocks'] = count($parsed_block['innerBlocks']);
    }

    return $parsed_block;
}


/**
 * Hooks into the theme.json data and allows an override to be passed in.
 *
 * @param WP_Theme_JSON_Data $theme_json The theme.json object.
 * @return WP_Theme_JSON_Data With updated data.
 */
function set_cards_background_color_palette(\WP_Theme_JSON_Data $theme_json): \WP_Theme_JSON_Data
{
    $new_palette = [
        [
            'name' => 'Contrast',
            'slug' => 'contrast',
            'color' => '#008080',
        ],
    ];

    return \Granola\Helpers::override_theme_json_with_new_palette_for_block('acf/cards-automatic', $new_palette, $theme_json);
}
