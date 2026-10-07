<?php

namespace Granola\Components\AdviceCategoryHero;

use Theme\Utils\Advice;

function filter_args(array $args): ?array
{
    // -------------------------------------------------------------------------
    // Default arguments.
    // -------------------------------------------------------------------------
    $args = array_merge([
        'eyebrow' => null,
        'heading' => null,
        'intro' => null,
        'image' => null,
        'image_alt' => '',
        'classes' => [],
    ], $args);

    $args['classes'] = array_merge(['advice-category-hero', 'wp-block'], $args['classes']);

    // -------------------------------------------------------------------------
    // The category being viewed. The block sits on the one template every
    // advice category shares, so everything below is read off the category
    // the visitor is on; the editor previews it with a real one.
    // -------------------------------------------------------------------------
    $term = Advice::current_term(!empty($args['is_preview']));

    // Placed on a page that is not a category, there is nothing to open.
    if (!$term && empty($args['is_preview'])) {
        return null;
    }

    $args['eyebrow'] = $args['eyebrow'] ?: \__('Category', 'granola');
    $args['heading'] = $args['heading'] ?: ($term ? Advice::term_name($term) : \__('Category name', 'granola'));

    if (empty($args['intro']) && $term) {
        $args['intro'] = trim(\wp_strip_all_tags(\term_description($term)));
    }

    // -------------------------------------------------------------------------
    // Figures. All counted, none typed: the guides in this category and the
    // categories below it, the topics that hold any, and the newest edit.
    // -------------------------------------------------------------------------
    $args['stats'] = [];
    $posts = $term ? Advice::post_ids([$term->term_id]) : [];

    if ($posts) {
        $args['stats'][] = [
            'value' => (string) count($posts),
            'label' => \_n('Guide in this category', 'Guides in this category', count($posts), 'granola'),
        ];

        $topics = 0;

        foreach (Advice::descendants($term) as $child) {
            if (Advice::guide_count([$child->term_id])) {
                $topics++;
            }
        }

        if ($topics) {
            $args['stats'][] = [
                'value' => (string) $topics,
                'label' => \_n('Topic', 'Topics', $topics, 'granola'),
            ];
        }

        // "Last updated", not the design's "Last reviewed": this is the newest
        // edit to any guide here, and an edit is not a review.
        \_prime_post_caches($posts, false, false);

        $newest = max(array_map(function ($id) {
            return (int) \get_post_modified_time('U', true, $id);
        }, $posts));

        $args['stats'][] = [
            'value' => \wp_date('M Y', $newest),
            'label' => \__('Last updated', 'granola'),
        ];
    }

    // -------------------------------------------------------------------------
    // Image: the block's own, else the one picked on the category, else the
    // newest guide's featured image, passing over the guide the featured
    // panel shows next, so one photograph is not drawn twice in a row.
    // -------------------------------------------------------------------------
    $attachment_id = (int) ($args['image']['attachment_id'] ?? 0);

    if (!$attachment_id && $term && function_exists('get_field')) {
        $field = \get_field('advice_hero_image', $term);
        $attachment_id = (int) (is_array($field) ? ($field['attachment_id'] ?? 0) : $field);
    }

    if (!$attachment_id && $posts) {
        $featured = Advice::featured_guide_id($term);
        $avoid = $featured ? (int) \get_post_thumbnail_id($featured) : 0;

        foreach ($posts as $post_id) {
            $thumbnail = (int) \get_post_thumbnail_id($post_id);

            if ($thumbnail && $thumbnail !== $avoid) {
                $attachment_id = $thumbnail;
                break;
            }
        }
    }

    // Not lazy: at desktop widths the top of the photograph is in the first
    // screen, and a lazy image there is held back until layout.
    $args['image'] = $attachment_id ? [
        'attachment_id' => $attachment_id,
        'alt' => (string) $args['image_alt'],
        'size' => 'full',
        'sizes' => '100vw',
        'loading' => 'eager',
        'classes' => ['advice-category-hero__image'],
    ] : null;

    Advice::mark_image_shown($attachment_id);

    return $args;
}
