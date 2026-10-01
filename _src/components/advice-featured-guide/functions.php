<?php

namespace Granola\Components\AdviceFeaturedGuide;

use Theme\Utils\Advice;

function filter_args(array $args): ?array
{
    // -------------------------------------------------------------------------
    // Default arguments.
    // -------------------------------------------------------------------------
    $args = array_merge([
        'eyebrow' => null,
        'button_label' => null,
        'classes' => [],
    ], $args);

    // -------------------------------------------------------------------------
    // The guide: the one picked on the category being viewed, else its
    // newest. Picked on the category rather than on the block, because one
    // template serves every category and a guide picked there would be
    // featured on all of them.
    // -------------------------------------------------------------------------
    $term = Advice::current_term(!empty($args['is_preview']));
    $post_id = $term ? Advice::featured_guide_id($term) : 0;

    if (!$post_id) {
        return null;
    }

    $args['classes'] = array_merge(['advice-featured-guide', 'wp-block'], $args['classes']);

    $args['url'] = \get_permalink($post_id);
    $args['eyebrow'] = $args['eyebrow'] ?: \__('Start here', 'granola');
    $args['button'] = $args['button_label'] ?: \__('Read the guide', 'granola');
    $args['heading'] = html_entity_decode(\get_the_title($post_id), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $args['standfirst'] = Advice::standfirst_of($post_id);
    $args['read_time'] = Advice::read_label($post_id);
    $args['updated'] = Advice::updated_label($post_id);

    $topic = Advice::topic_of($post_id, $term);
    $args['topic'] = $topic->term_id === $term->term_id
        ? Advice::term_name($term)
        : (Advice::topic_labels($term)[$topic->term_id] ?? Advice::term_name($topic));

    $attachment_id = (int) \get_post_thumbnail_id($post_id);

    $args['image'] = $attachment_id ? [
        'attachment_id' => $attachment_id,
        // Decorative: the heading names the guide, and the whole panel is the
        // one link to it.
        'alt' => '',
        'size' => 'full',
        'sizes' => '(max-width: 1440px) 100vw, 1312px',
        'classes' => ['advice-featured-guide__image'],
    ] : null;

    Advice::mark_image_shown($attachment_id);

    return $args;
}
