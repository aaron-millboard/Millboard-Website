<?php

namespace Granola\Components\AnchorLinks;

function filter_args(?array $args): ?array
{
    // Bail extra early - args have been nulled.
    if (is_null($args)) {
        return null;
    }

    // -------------------------------------------------------------------------
    // Default arguments.
    // -------------------------------------------------------------------------
    $args = array_merge([
        'heading' => \_x('On this page:', 'Anchor Links Block Heading', 'granola'),
        'items' => [],
        'classes' => [],
        'background' => 'brand-2',
    ], $args);

    // -------------------------------------------------------------------------
    // Required classes.
    // -------------------------------------------------------------------------
    $args['classes'] = array_merge([
        'anchor-links__wrapper',
        'alignwide',
    ], $args['classes']);

    // Move all styling to inner element.
    $args['inner_attributes'] = [
        'class' => [
            'anchor-links',
        ],
    ];

    $args['expander_id'] = \wp_unique_id('anchor-links-');

    if (!empty($args['heading']) && is_string($args['heading'])) {
        $args['heading'] = [
            'content' => \Granola\Component::get('button', [
                'content' => $args['heading'],
                'attributes' => [
                    'aria-controls' => $args['expander_id'],
                ],
                'classes' => [
                    'anchor-links__toggle',
                ],
            ]),
            'classes' => [
                'anchor-links__heading',
                'is-style-typestyle-h6',
            ],
        ];
    }

    $args['items'] = array_map(function ($link) {
        return [
            'content' => \Granola\Component::get('element', [
                'content' => $link['text']
            ]),
            'url' => '#' . $link['id'],
            'classes' => [
                'anchor-links__link',
                'is-style-typestyle-small',
            ],
        ];
    }, get_anchor_links_data());

    // ---------------------------------------
    // Bail early - return null for no output.
    // ---------------------------------------
    if (empty($args['items'])) {
        return null;
    }

    // -------------------------------------------------------------------------
    // Return the filtered args.
    // -------------------------------------------------------------------------
    return $args;
}

/**
 * Generate an array of page headings text and their IDs. Used to create anchor links to those headings.
 *
 * @link https://github.com/WordPress/gutenberg/issues/61440#issuecomment-2107797038
 *
 * @param integer|null $post_id A post ID to generate headings links for. Defaults to the current post.
 * @return array An array of headings data to create anchor links from.
 */
function get_anchor_links_data(?int $post_id = null): array
{
    $links = [];
    $post = \get_post($post_id);

    if (empty($post)) {
        return [];
    }

    // Return `null` for the anchor-link component when parsing post content - prevents an infinite loop.
    \add_filter('granola/component/anchor-links', '__return_null', 5);

    $html = \do_blocks($post->post_content);

    // Remove previously added null return for future post content processing.
    \remove_filter('granola/component/anchor-links', '__return_null', 5);

    // Reset Helper ID counter array so unique IDs begin again.
    // Using wp_unique_prefixed_id() generates new IDs the second time block content is generated.
    \Granola\Helpers::$id_counters = [];

    $element_tags = [
        // 'H1',
        'H2',
        // 'H3',
        // 'H4',
        // 'H5',
        // 'H6',
    ];

    $tags = new \WP_HTML_Tag_Processor($html);

    while ($tags->next_tag()) {
        // Check against element array for flexibility.
        if (!in_array($tags->get_tag(), $element_tags, true)) {
            continue;
        }

        $level = (int) str_replace('H', '', $tags->get_tag());
        $id = $tags->get_attribute('id');
        $text = '';

        // Capture current heading inner text.
        while ($tags->next_token()) {
            // Skip all tokens that are not text or heading close tags.
            if ('#text' === $tags->get_token_type()) {
                $text .= $tags->get_modifiable_text();
            } elseif ("H{$level}" === $tags->get_tag() && $tags->is_tag_closer()) {
                // All inner text found.
                break;
            }
        }

        // Generate an ID attribute, if needed, and insert into heading tag.
        if (empty($id)) {
            // Compress whitespace to avoid newline issues.
            $text = preg_replace('/\s+/', ' ', $text);

            // Return to starting tag and update ID attribute.
            $id = sanitize_title($text);
        }

        // insert item to toc
        // Include check for text (without tags) - as some headings can be empty with just <span> elements.
        if (!empty(strip_tags($text))) {
            $links[] = [
                'text' => $text,
                'id' => $id,
                'level' => $level, // Not needed (yet).
            ];
        }
    }

    return $links;
}

/**
 * Process the post content to add ID attributes to any specificed headings without one.
 *
 * @param string $content The post content.
 * @return string Processed post content with heading IDs added.
 */
function set_content_headings_ids(string $content): string
{
    $element_tags = [
        // 'H1',
        'H2',
        // 'H3',
        // 'H4',
        // 'H5',
        // 'H6',
    ];

    $tags = new \WP_HTML_Tag_Processor($content);

    while ($tags->next_tag()) {
        // Check against element array for flexibility.
        if (!in_array($tags->get_tag(), $element_tags, true)) {
            continue;
        }

        $level = (int) str_replace('H', '', $tags->get_tag());
        $id = $tags->get_attribute('id');

        // Set bookmark to come back to if an ID must be generated for this heading.
        $tags->set_bookmark('current_heading_start');

        $text = '';

        // Capture current heading inner text.
        while ($tags->next_token()) {
            // Skip all tokens that are not text or heading close tags.
            if ('#text' === $tags->get_token_type()) {
                $text .= $tags->get_modifiable_text();
            } elseif ("H{$level}" === $tags->get_tag() && $tags->is_tag_closer()) {
                // All inner text found.
                $tags->set_bookmark('current_heading_end');
                break;
            }
        }

        // Generate an ID attribute, if needed, and insert into heading tag.
        if (empty($id)) {
            // Compress whitespace to avoid newline issues.
            $text = preg_replace('/\s+/', ' ', $text);

            // Return to starting tag and update ID attribute.
            $id = sanitize_title($text);

            // Insert new ID.
            $tags->seek('current_heading_start');
            $tags->set_attribute('id', $id);

            // Resume processing and clean up bookmarks.
            $tags->seek('current_heading_end');
            $tags->release_bookmark('current_heading_start');
            $tags->release_bookmark('current_heading_end');
        }
    }

    return $tags->get_updated_html();
}
