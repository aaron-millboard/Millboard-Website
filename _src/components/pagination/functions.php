<?php

namespace Granola\Components\Pagination;

function filter_args(array $args): ?array
{
    // ---------------------------------------
    // Default arguments.
    // ---------------------------------------
    $args = array_merge([
        'classes' => [],
        'paged' => null,
        'max_num_pages' => null,
    ], $args);

    // ---------------------------------------
    // Required classes.
    // ---------------------------------------
    $args['classes'] = array_merge([
        'pagination',
        'alignfull',
        'wp-block',
    ], $args['classes']);

    $pagination_args = [
        'prev_text' => \__('Previous page', 'granola'),
        'next_text' => \__('Next page', 'granola'),
        'before_page_number' => \Granola\Component::get('element', [
            'content' => \__('Page', 'granola'),
            'classes' => ['visually-hidden'],
        ]),
        'class' => 'pagination__inner',
    ];

    // Pass custom pagination parameters if provided
    if ($args['paged'] !== null && $args['max_num_pages'] !== null) {
        $pagination_args['current'] = $args['paged'];
        $pagination_args['total'] = $args['max_num_pages'];
    }

    $args['output'] = get_the_posts_custom_pagination($pagination_args);

    // ---------------------------------------
    // Bail early - return null for no output.
    // ---------------------------------------
    if (empty($args['output'])) {
        return null;
    }

    // -------------------------------------------------------------------------
    // Return the filtered args.
    // -------------------------------------------------------------------------
    return $args;
}

/**
 * Filters the navigation markup template to insert 'visually-hidden' utility class.
 *
 * @link https://developer.wordpress.org/reference/hooks/navigation_markup_template/
 *
 * @param string $template The default navigation template markup.
 * @return string The filtered navigation template markup.
 */
function filter_pagination_markup_template(string $template): string
{
    return str_replace('screen-reader-text', 'visually-hidden', $template);
}

/**
 * Custom posts pagination function that supports both global query and custom parameters.
 *
 * @param array $args Optional pagination arguments.
 * @return string Navigation markup.
 */
function get_the_posts_custom_pagination(array $args = []): string
{
    global $wp_query;

    $navigation = '';

    // Determine max_num_pages - use custom if provided, otherwise use global query
    $max_num_pages = isset($args['total']) ? $args['total'] : $wp_query->max_num_pages;

    // Don't print empty markup if there's only one page.
    if ($max_num_pages > 1) {
        // Make sure the nav element has an aria-label attribute: fallback to the screen reader text.
        if (!empty($args['screen_reader_text']) && empty($args['aria_label'])) {
            $args['aria_label'] = $args['screen_reader_text'];
        }

        $args = wp_parse_args(
            $args,
            [
                'mid_size'           => 1,
                'prev_text'          => \_x('Previous', 'previous set of posts'),
                'next_text'          => \_x('Next', 'next set of posts'),
                'screen_reader_text' => \__('Posts pagination'),
                'aria_label'         => \__('Posts pagination'),
                'class'              => 'pagination',
            ]
        );

        /**
         * Filters the arguments for posts pagination links.
         *
         * @since 6.1.0
         *
         * @param array $args Optional. Default pagination arguments.
         */
        $args = \apply_filters('the_posts_pagination_args', $args);

        // Make sure we get a string back. Plain is the next best thing.
        if (isset($args['type']) && 'array' === $args['type']) {
            $args['type'] = 'plain';
        }

        // Set up paginated links.
        $links = \paginate_links($args);

        if ($links) {
            $navigation = \_navigation_markup($links, $args['class'], $args['screen_reader_text'], $args['aria_label']);
        }
    }

    return $navigation;
}
