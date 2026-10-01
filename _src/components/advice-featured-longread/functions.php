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

    if (empty($args['standfirst'])) {
        $args['standfirst'] = standfirst_of($post_id);
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

    Advice::mark_image_shown($attachment_id);

    $minutes = Advice::read_minutes($post_id);

    // Label and reading time apart, so the template can keep "· 8 min" on one
    // line: on a phone the button otherwise broke between the 8 and the min.
    $args['button'] = $args['button_label'] ?: \__('Read the guide', 'granola');
    $args['read_time'] = sprintf(
        // translators: %d: reading time in minutes.
        \_n('%d min', '%d min', $minutes, 'granola'),
        $minutes
    );

    // -------------------------------------------------------------------------
    // Return the filtered args.
    // -------------------------------------------------------------------------
    return $args;
}

/**
 * The article's own one-line summary.
 *
 * Its excerpt when it has one. Most guides do not, but every one carries a
 * meta description, which is written as exactly this: a sentence saying what
 * the guide covers. Read through Yoast so any %%variables%% in it are filled.
 */
function standfirst_of(int $post_id): string
{
    if (\has_excerpt($post_id)) {
        return \wp_strip_all_tags(\get_the_excerpt($post_id));
    }

    if (function_exists('YoastSEO')) {
        $meta = \YoastSEO()->meta->for_post($post_id);

        // Read into a variable before testing it. `description` is a magic
        // property, and empty() on one asks __isset first, which says no, so
        // `empty($meta->description)` is true even when it holds a sentence.
        $description = $meta ? (string) $meta->description : '';

        if ($description !== '') {
            return \wp_strip_all_tags(html_entity_decode($description, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        }
    }

    return '';
}
