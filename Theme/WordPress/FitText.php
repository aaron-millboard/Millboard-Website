<?php

namespace Theme\WordPress;

/**
 * Disables WordPress core's "Fit text" typography option.
 *
 * Core 7.1 added a fitText block attribute. On render,
 * wp_render_typography_support() enqueues the
 * '@wordpress/block-editor/utils/fit-text-frontend' script module and adds
 * Interactivity API directives, and that script sizes the text to fill its
 * container by binary-searching font sizes between 0 and 2400px.
 *
 * findOptimalFontSize() only uses the parent as the reference element when
 * that parent is display: flex. Post content sits in .site-main__content,
 * which is display: flow-root, so the block is measured against itself,
 * nearly every candidate size "fits", and the search runs away. On the Grand
 * Designs Live article it produced font-size: 1249px and an 80,725px page.
 *
 * A CSS guard alone is not enough: Perfmatters Remove Unused CSS runs on
 * en-gb and strips a rule whose class is on no page, so the guard is absent
 * at exactly the moment an editor first switches the option on. Handling it
 * server-side means neither the class, the directives nor the script ever
 * reach the page, in every locale.
 *
 * Three filters: the option is removed from the editor so it cannot be set,
 * the attribute is dropped on render so content that already carries it is
 * inert, and the class is stripped from the saved markup.
 *
 * This is a workaround for a core bug, not a theme feature. If core changes
 * findOptimalFontSize() to measure against a real container, delete this
 * class and its init() call in functions.php.
 */
class FitText
{
    public static function init(): void
    {
        \add_filter('register_block_type_args', [__CLASS__, 'remove_fit_text_support']);
        \add_action('enqueue_block_editor_assets', [__CLASS__, 'remove_fit_text_control']);
        \add_filter('render_block_data', [__CLASS__, 'remove_fit_text_attribute']);
        \add_filter('render_block', [__CLASS__, 'remove_fit_text_class'], 10, 2);
    }

    /**
     * Take the Fit text toggle out of the editor's Typography panel.
     *
     * get_block_editor_server_block_settings() passes 'supports' through to
     * wp.blocks.unstable__bootstrapServerSideBlockDefinitions(), so dropping
     * the flag at registration removes the control from the editor UI rather
     * than leaving authors a switch that silently does nothing.
     *
     * Applied to every block type that declares it, currently core/paragraph
     * and core/heading, so it still holds if core adds more.
     *
     * @param array $args Arguments the block type is registered with.
     * @return array The arguments without fitText support.
     */
    public static function remove_fit_text_support($args): array
    {
        unset($args['supports']['typography']['fitText']);

        return $args;
    }

    /**
     * Remove the Fit text control from the editor UI.
     *
     * remove_fit_text_support() cleans the server registry, but that is not
     * enough on its own: processBlockType() in wp-includes/js/dist/blocks.js
     * spreads the client registration AFTER the bootstrapped server
     * definition, and block-library.js ships supports.typography.fitText for
     * three core blocks. The client value wins.
     *
     * A blocks.registerBlockType filter does win, but only if it is added
     * before those blocks register. The theme's editor bundle loads after
     * wp-block-library, and blocks.js has no hookAdded listener, so a filter
     * added later is never reapplied. Attaching this to wp-blocks prints it
     * between wp-blocks and wp-block-library, which depends on it.
     *
     * @return void
     */
    public static function remove_fit_text_control(): void
    {
        $js = <<<'JS'
( function () {
    if ( ! window.wp || ! window.wp.hooks ) {
        return;
    }

    window.wp.hooks.addFilter(
        'blocks.registerBlockType',
        'millboard/fit-text',
        function ( settings ) {
            var typography = settings && settings.supports && settings.supports.typography;

            if ( ! typography || ! ( 'fitText' in typography ) ) {
                return settings;
            }

            typography = Object.assign( {}, typography );
            delete typography.fitText;

            return Object.assign( {}, settings, {
                supports: Object.assign( {}, settings.supports, { typography: typography } )
            } );
        }
    );
}() );
JS;

        \wp_add_inline_script('wp-blocks', $js);
    }

    /**
     * Drop the fitText attribute before block supports are applied.
     *
     * Runs on the parsed block, so core's typography support never sees the
     * attribute and skips both the script module and the directives. This is
     * render-time only and never touches stored post content.
     *
     * @param array $parsed_block The block being rendered.
     * @return array The block without its fitText attribute.
     */
    public static function remove_fit_text_attribute($parsed_block): array
    {
        unset($parsed_block['attrs']['fitText']);

        return $parsed_block;
    }

    /**
     * Strip the has-fit-text class the editor baked into saved content.
     *
     * Paragraph and heading are static blocks, so the class lives in
     * post_content rather than being added at render. It is inert once the
     * attribute is gone, but leaving it would keep the markup claiming a
     * behaviour the page no longer has.
     *
     * @param string $block_content The rendered block.
     * @param array  $block         The block being rendered.
     * @return string The rendered block without the class.
     */
    public static function remove_fit_text_class($block_content, $block): string
    {
        if (empty($block_content) || !str_contains($block_content, 'has-fit-text')) {
            return $block_content;
        }

        $processor = new \WP_HTML_Tag_Processor($block_content);

        if (!$processor->next_tag()) {
            return $block_content;
        }

        $processor->remove_class('has-fit-text');

        return $processor->get_updated_html();
    }
}
