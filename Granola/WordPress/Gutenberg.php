<?php

namespace Granola\WordPress;

class Gutenberg
{
    public static function init(): void
    {
        \add_action('init', [__CLASS__, 'set_color_palette']);
        // After Gravity Forms registers its block on init at the default priority.
        \add_action('init', [__CLASS__, 'unregister_legacy_blocks'], 20);
        \add_action('after_setup_theme', [__CLASS__, 'gutenberg_support']);
        \add_filter('block_categories_all', [__CLASS__, 'gutenberg_block_category']);
    }

    /**
     * Drop third-party blocks that are still registered at an old block API
     * version.
     *
     * WordPress 7.1 iframes the editor canvas. A block registered at
     * apiVersion 1 or 2 is not built for that, and the editor says so itself:
     *
     *   "Block with API version 2 or lower is deprecated ... the block
     *    'gravityforms/form' is registered with API version 2. This means that
     *    the post editor may work as a non-iframe editor."
     *
     * A canvas that is iframed for some blocks and not for others gets torn
     * down and rebuilt, which leaves elements pointing at a document that no
     * longer has a window. That is what produces the editor's "Cannot read
     * properties of null (reading 'getSelection')" and the SecurityError on a
     * cross-origin frame.
     *
     * gravityforms/form registers at apiVersion 1 and is used in no published
     * content on any of the thirteen sites, only in one old revision on de-de.
     * Forms are placed with the shortcode and with the theme's own blocks, so
     * nothing visible changes. Same reasoning, and the same shape, as
     * mb-leadin-editor-fix.php in mu-plugins.
     *
     * REMOVE THIS when Gravity Forms ship an apiVersion 3 block.
     */
    public static function unregister_legacy_blocks(): void
    {
        if (!\function_exists('unregister_block_type')) {
            return;
        }

        $registry = \WP_Block_Type_Registry::get_instance();

        foreach (['gravityforms/form'] as $name) {
            if ($registry->is_registered($name)) {
                \unregister_block_type($name);
            }
        }
    }

    public static function gutenberg_support(): void
    {
        // Add custom CSS support for Gutenberg.
        // Not to be confused with custom CSS support for TinyMCE (editor-style).
        \add_theme_support('editor-styles');

        // Add the CSS file path to be enqueued by WordPress.
        // The path to the asset must be relative to the theme root.
        $file = \Granola\Asset::extract('editor.css');
        if (!empty($file)) {
            \add_editor_style(\Granola\Paths::theme_asset_path($file, true));
        }

        // Add support for embeds to responsively keep their aspect ratio.
        \add_theme_support('responsive-embeds');

        // Deactivate the block directory.
        \remove_action('enqueue_block_editor_assets', 'wp_enqueue_editor_block_directory_assets');
        \remove_action('enqueue_block_editor_assets', 'gutenberg_enqueue_block_editor_assets_block_directory');

        // Deactivate block patterns.
        \remove_theme_support('core-block-patterns');
    }

    /**
     * Filters the Gutenberg block categories array to add a custom category.
     *
     * @link https://developer.wordpress.org/reference/hooks/block_categories/
     *
     * @param array[] $categories A list of registered block categories.
     * @return array[] The filtered list of registered block categories.
     */
    public static function gutenberg_block_category($categories): array
    {
        $block_category = [
            'title' => \esc_html__('Granola Blocks', 'granola'),
            'slug' => 'granola-blocks'
        ];

        $category_slugs = \wp_list_pluck($categories, 'slug');

        // Bail early - this category slug is already registered.
        if (in_array($block_category['slug'], $category_slugs, true)) {
            return $categories;
        }

        array_unshift($categories, $block_category);

        return $categories;
    }

    /**
     * Define Granola's color palette (from the theme.json file), to be used elsewhere.
     *
     * @return void
     */
    public static function set_color_palette(): void
    {
        $theme_json = json_decode(file_get_contents(\get_theme_file_path('theme.json')));

        if (!empty($theme_json->settings->color->palette)) {
            $color_palette_arrs = [];

            foreach ($theme_json->settings->color->palette as $color_palette_obj) {
                $color_palette_arrs[] = (array) $color_palette_obj;
            }

            define('GRANOLA_COLOR_PALETTE', $color_palette_arrs);
        }
    }
}
