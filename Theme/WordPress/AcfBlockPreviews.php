<?php

namespace Theme\WordPress;

/**
 * Keeps the live preview in the editor for every ACF block.
 *
 * ACF 6.8.9 added a `renderPreview` block option. Its editor script (xe() in acf-pro-blocks.min.js) shows
 * only a placeholder with an "Edit Block" button, and renders no template, for any block that is
 * `"mode": "edit"` with `"supports": { "mode": false }` unless the block says `renderPreview: true`.
 * Most blocks in this theme are configured that way, so after ACF 6.8.9+ every block preview in the
 * editor turned into a white box.
 *
 * This sets `renderPreview` to true for every ACF block that has not set it itself, so previews come back
 * without editing each block.json. A block can still opt out with `"renderPreview": false`.
 *
 * Blocks defined by block.json (all of ours) never pass through ACF's `acf/register_block_type_args`, so
 * the value is added to the raw metadata with core's `block_type_metadata` filter, before ACF reads it.
 * The ACF filter covers any block registered in PHP with acf_register_block_type().
 */
class AcfBlockPreviews
{
    public static function init()
    {
        \add_filter('block_type_metadata', [__CLASS__, 'preview_json_blocks']);
        \add_filter('acf/register_block_type_args', [__CLASS__, 'preview_php_blocks']);
    }

    /**
     * @param array $metadata The raw block.json metadata.
     * @return array
     */
    public static function preview_json_blocks(array $metadata): array
    {
        if (!empty($metadata['acf']) && is_array($metadata['acf']) && !array_key_exists('renderPreview', $metadata['acf'])) {
            $metadata['acf']['renderPreview'] = true;
        }

        return $metadata;
    }

    /**
     * @param array $block The arguments for registering an ACF block type in PHP.
     * @return array
     */
    public static function preview_php_blocks(array $block): array
    {
        if (!array_key_exists('render_preview', $block)) {
            $block['render_preview'] = true;
        }

        return $block;
    }
}
