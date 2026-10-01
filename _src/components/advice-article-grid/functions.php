<?php

namespace Granola\Components\AdviceArticleGrid;

use Theme\Utils\Advice;

function filter_args(array $args): ?array
{
    // -------------------------------------------------------------------------
    // Default arguments.
    // -------------------------------------------------------------------------
    $args = array_merge([
        'sort' => 'recent',
        'empty_heading' => null,
        'empty_body' => null,
        'classes' => [],
    ], $args);

    $args['classes'] = array_merge(['advice-article-grid', 'wp-block'], $args['classes']);

    $term = Advice::current_term(!empty($args['is_preview']));

    if (!$term) {
        return null;
    }

    // Every guide on the one page. The filter bar narrows them in place, and
    // the template does not paginate: its page URLs redirect to page one.
    $args['cards'] = build_cards($term, (string) $args['sort']);

    $args['heading'] = sprintf(
        // translators: %s: advice category name.
        \__('Guides in %s', 'granola'),
        Advice::term_name($term)
    );

    $args['empty_heading'] = $args['empty_heading'] ?: \__('Nothing published here yet', 'granola');
    $args['empty_body'] = $args['empty_body'] ?: \__('We are writing it. In the meantime, every guide we have published is in the Advice Centre.', 'granola');
    $args['hub_url'] = (string) \get_post_type_archive_link(Advice::POST_TYPE);
    $args['hub_label'] = \__('Browse the Advice Centre', 'granola');

    return $args;
}

/**
 * The guides in a category as cards, in the grid's order.
 *
 * Shared with the schema block, which lists the same guides in the same
 * order, so the ItemList Google reads is the list on the page.
 *
 * @param \WP_Term $term The category; its sub-categories' guides are included.
 * @param string   $sort 'recent' (last updated first) or 'title' (A to Z).
 */
function build_cards(\WP_Term $term, string $sort = 'recent'): array
{
    $ids = Advice::post_ids([$term->term_id]);

    if (!$ids) {
        return [];
    }

    // One query each for the posts, their meta, their categories and their
    // photographs, rather than several per card.
    \_prime_post_caches($ids, true, true);

    $thumbnails = array_filter(array_map(function ($post_id) {
        return (int) \get_post_thumbnail_id($post_id);
    }, $ids));

    if ($thumbnails) {
        \_prime_post_caches($thumbnails, false, true);
    }

    $labels = Advice::topic_labels($term);
    $cards = [];

    foreach ($ids as $post_id) {
        $topic = Advice::topic_of($post_id, $term);
        $attachment_id = (int) \get_post_thumbnail_id($post_id);

        $cards[] = [
            'post_id' => $post_id,
            'url' => (string) \get_permalink($post_id),
            'title' => html_entity_decode(\get_the_title($post_id), ENT_QUOTES | ENT_HTML5, 'UTF-8'),
            'standfirst' => Advice::standfirst_of($post_id),
            'topic' => $topic->term_id === $term->term_id
                ? Advice::term_name($term)
                : ($labels[$topic->term_id] ?? Advice::term_name($topic)),
            'topic_ids' => Advice::topic_ids_of($post_id, $term),
            'read_time' => Advice::read_label($post_id),
            'updated' => Advice::updated_label($post_id),
            'modified' => (int) \get_post_modified_time('U', true, $post_id),
            'image' => $attachment_id ? [
                'attachment_id' => $attachment_id,
                // Decorative: the card's heading names the guide and the whole
                // card is one link.
                'alt' => '',
                'size' => 'medium_large',
                'sizes' => '(max-width: 700px) 100vw, (max-width: 1100px) 50vw, 440px',
                'classes' => ['advice-article-grid__image'],
            ] : null,
        ];
    }

    if ($sort === 'title') {
        usort($cards, function ($a, $b) {
            return strnatcasecmp($a['title'], $b['title']);
        });
    } else {
        usort($cards, function ($a, $b) {
            return $b['modified'] <=> $a['modified'];
        });
    }

    return $cards;
}
