<?php

namespace Granola\Components\AdviceArticleHero;

use Theme\Utils\Advice;

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
        'caption' => null,
        'post_id' => 0,
        'classes' => [],
    ], $args);

    $post_id = (int) ($args['post_id'] ?: \get_the_ID());

    if ($post_id <= 0) {
        return null;
    }

    $args['classes'] = array_merge(['advice-article-hero', 'wp-block'], $args['classes']);

    // -------------------------------------------------------------------------
    // Copy. Everything left empty is read off the article, so the opener
    // cannot drift from what the article is called and filed under.
    // -------------------------------------------------------------------------
    if (empty($args['heading'])) {
        $args['heading'] = html_entity_decode(\get_the_title($post_id), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    if (empty($args['standfirst'])) {
        $args['standfirst'] = Advice::standfirst_of($post_id);
    }

    if (empty($args['eyebrow'])) {
        $args['eyebrow'] = eyebrow_of($post_id);
    }

    // -------------------------------------------------------------------------
    // Image: the block's own, else the article's featured image. It is the
    // largest thing above the fold, so it loads eagerly and at high priority,
    // and the lazy-loaders are told to leave it alone.
    // -------------------------------------------------------------------------
    $attachment_id = (int) ($args['image']['attachment_id'] ?? 0) ?: (int) \get_post_thumbnail_id($post_id);

    $args['image'] = $attachment_id ? [
        'attachment_id' => $attachment_id,
        'alt' => (string) $args['image_alt'],
        'size' => 'full',
        'sizes' => '100vw',
        'loading' => 'eager',
        'classes' => ['advice-article-hero__image', 'skip-lazy'],
        'attributes' => [
            'fetchpriority' => 'high',
            'data-no-lazy' => '1',
            'data-spai-eager' => true,
        ],
    ] : null;

    return $args;
}

/**
 * "Guide · Cladding Comparisons": the article's primary category, named the
 * way its parent's topic chips name it.
 */
function eyebrow_of(int $post_id): string
{
    $term = \Theme\Utils\Taxonomies::get_primary_term($post_id, Advice::TAXONOMY);
    $label = '';

    if ($term instanceof \WP_Term) {
        $parent = $term->parent ? \get_term($term->parent, Advice::TAXONOMY) : null;
        $label = $parent instanceof \WP_Term
            ? (Advice::topic_labels($parent)[$term->term_id] ?? Advice::term_name($term))
            : Advice::term_name($term);
    }

    return $label === ''
        ? \__('Guide', 'granola')
        : sprintf(
            // translators: %s: the advice category an article is filed under.
            \__('Guide · %s', 'granola'),
            $label
        );
}
