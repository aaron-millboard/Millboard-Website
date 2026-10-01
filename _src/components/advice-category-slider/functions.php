<?php

namespace Granola\Components\AdviceCategorySlider;

use Theme\Utils\Advice;

function filter_args(array $args): ?array
{
    // -------------------------------------------------------------------------
    // Default arguments.
    // -------------------------------------------------------------------------
    $args = array_merge([
        'heading' => null,
        'slides' => [],
        'classes' => [],
    ], $args);

    // -------------------------------------------------------------------------
    // Required classes.
    // -------------------------------------------------------------------------
    $args['classes'] = array_merge([
        'advice-category-slider',
        'wp-block',
    ], $args['classes']);

    $args['cards'] = build_cards((array) $args['slides']);

    // -------------------------------------------------------------------------
    // Summary beside the heading: the guides across every card, each counted
    // once. Two cards for a parent and its own child would otherwise count the
    // same guide twice.
    // -------------------------------------------------------------------------
    $args['summary'] = '';

    if ($args['cards']) {
        $total = Advice::guide_count(array_column($args['cards'], 'term_id'));
        $args['summary'] = Advice::guide_label($total);
    }

    // -------------------------------------------------------------------------
    // Return the filtered args.
    // -------------------------------------------------------------------------
    return $args;
}

/**
 * One card per chosen category, everything read off the category unless the
 * row overrides it.
 *
 * Shared with the schema block, which lists the same categories.
 */
function build_cards(array $rows): array
{
    $cards = [];

    foreach ($rows as $row) {
        $term = !empty($row['term']) ? \get_term((int) $row['term'], Advice::TAXONOMY) : null;

        if (!$term instanceof \WP_Term) {
            continue;
        }

        $posts = Advice::post_ids([$term->term_id]);

        // An empty category is skipped rather than shown as a card that opens
        // on "no articles found". It appears by itself once a guide is filed.
        if (!$posts) {
            continue;
        }

        $link = \get_term_link($term);

        if (\is_wp_error($link)) {
            continue;
        }

        $description = trim((string) ($row['description'] ?? ''));

        if ($description === '') {
            $description = trim(\wp_strip_all_tags(\term_description($term)));
        }

        // Image: the row's own, else the newest guide's featured image, so a
        // card is never a blank box while someone finds a photograph.
        $attachment_id = !empty($row['image']['attachment_id']) ? (int) $row['image']['attachment_id'] : 0;

        if (!$attachment_id) {
            foreach ($posts as $post_id) {
                $attachment_id = (int) \get_post_thumbnail_id($post_id);

                if ($attachment_id) {
                    break;
                }
            }
        }

        $cards[] = [
            'term_id' => $term->term_id,
            'name' => Advice::term_name($term),
            'url' => $link,
            'description' => $description,
            'topics' => topics_of($term, (string) ($row['topics'] ?? '')),
            'count' => count($posts),
            'count_label' => Advice::guide_label(count($posts)),
            'image' => $attachment_id ? [
                'attachment_id' => $attachment_id,
                // Decorative: the card's heading names the category and the
                // whole card is one link.
                'alt' => '',
                'size' => 'large',
                'sizes' => '(max-width: 600px) 85vw, 440px',
                'classes' => ['advice-category-slider__image'],
            ] : null,
        ];
    }

    return $cards;
}

/**
 * The topic line under a card: typed one per line, or else the category's own
 * sub-categories that have something in them.
 */
function topics_of(\WP_Term $term, string $typed): array
{
    $typed = array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $typed))));

    if ($typed) {
        return $typed;
    }

    $children = \get_terms([
        'taxonomy' => Advice::TAXONOMY,
        'parent' => $term->term_id,
        'hide_empty' => false,
    ]);

    if (\is_wp_error($children) || !$children) {
        return [];
    }

    $names = [];

    foreach ($children as $child) {
        if (Advice::guide_count([$child->term_id])) {
            $names[] = Advice::term_name($child);
        }
    }

    return $names;
}
