<?php

namespace Granola\Components\AdviceFeaturedLongread;

use Theme\Utils\Advice;

function filter_args(array $args): ?array
{
    // -------------------------------------------------------------------------
    // Default arguments.
    // -------------------------------------------------------------------------
    $args = array_merge([
        'post' => null,
        'eyebrow' => null,
        'heading' => null,
        'standfirst' => null,
        'image' => null,
        'button_label' => null,
        'classes' => [],
    ], $args);

    // -------------------------------------------------------------------------
    // Bail early. A pinned article that has since been unpublished leaves no
    // panel rather than a dark box pointing at nothing.
    // -------------------------------------------------------------------------
    $post_id = (int) $args['post'];

    if ($post_id <= 0 || \get_post_status($post_id) !== 'publish') {
        return null;
    }

    // -------------------------------------------------------------------------
    // Required classes.
    // -------------------------------------------------------------------------
    $args['classes'] = array_merge([
        'advice-featured-longread',
        'wp-block',
    ], $args['classes']);

    // -------------------------------------------------------------------------
    // Everything not overridden is read off the article, so the panel cannot
    // drift from the guide it advertises.
    // -------------------------------------------------------------------------
    $args['url'] = \get_permalink($post_id);

    if (empty($args['heading'])) {
        $args['heading'] = html_entity_decode(\get_the_title($post_id), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    if (empty($args['standfirst']) && \has_excerpt($post_id)) {
        $args['standfirst'] = \wp_strip_all_tags(\get_the_excerpt($post_id));
    }

    $attachment_id = !empty($args['image']['attachment_id'])
        ? (int) $args['image']['attachment_id']
        : (int) \get_post_thumbnail_id($post_id);

    $args['image'] = $attachment_id ? [
        'attachment_id' => $attachment_id,
        // Decorative: the heading beside it names the guide, and the photograph
        // is not itself a link.
        'alt' => '',
        'size' => 'large',
        'sizes' => '(max-width: 820px) 100vw, 660px',
        'classes' => ['advice-featured-longread__image'],
    ] : null;

    $minutes = Advice::read_minutes($post_id);

    $args['button'] = sprintf(
        // translators: 1: button label, e.g. "Read the guide". 2: reading time in minutes.
        \_x('%1$s · %2$s', 'Advice featured longread button', 'granola'),
        $args['button_label'] ?: \__('Read the guide', 'granola'),
        sprintf(\_n('%d min', '%d min', $minutes, 'granola'), $minutes)
    );

    // -------------------------------------------------------------------------
    // Return the filtered args.
    // -------------------------------------------------------------------------
    return $args;
}
