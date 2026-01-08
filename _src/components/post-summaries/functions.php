<?php

namespace Granola\Components\PostSummaries;

function filter_args(array $args): ?array
{
    // -------------------------------------------------------------------------
    // Default arguments.
    // -------------------------------------------------------------------------
    $args = array_merge([
        'classes' => [],
        'sidebar_tags' => [],
    ], $args);

    // -------------------------------------------------------------------------
    // Required classes.
    // -------------------------------------------------------------------------
    $args['classes'] = array_merge([
        'post-summaries',
        'wp-block',
    ], $args['classes']);

    if (!empty($args['heading'])) {
        $args['heading'] = [
            'content' => $args['heading'],
            'classes' => ['post-summaries__heading'],
        ];
    }

    if (\is_search()) {
        $query = \Granola\WordPress\PageObject::get();

        if (!empty($query->query['s'])) {
            $args['heading'] = [
                'content' => sprintf(
                    \_n(
                        // translators: 1: quantity of comments. 2: post title.
                        'Displaying %1$s search result for \'%2$s\'',
                        'Displaying %1$s search results for \'%2$s\'',
                        $query->found_posts,
                        'granola'
                    ),
                    \number_format_i18n($query->found_posts),
                    $query->query['s']
                ),
                'classes' => [
                    'post-summaries__heading',
                    'is-style-typestyle-h6',
                ],
            ];
        }

        $args['sidebar_tags'] = get_search_query_post_type_tags($query);
        $args['sidebar_heading'] = [
            'content' => \__('Content types', 'granola'),
            'classes' => [
                'post-summaries__sidebar-heading',
                'is-style-typestyle-h6',
            ],
        ];

        $args['classes'][] = 'is-search-results';
    }

    // -------------------------------------------------------------------------
    // Return the filtered args.
    // -------------------------------------------------------------------------
    return $args;
}

/**
 * Generates link tags
 *
 * @param \WP_Query $query
 * @return array
 */
function get_search_query_post_type_tags(\WP_Query $query): array
{
    $tags = [];
    $sidebar_query = new \WP_Query([
        'posts_per_page' => 500, // arbitrary large number.
        's' => $query->query['s'],

        // Query optimisation.
        'no_found_rows' => true,
        'update_post_meta_cache' => false,
        'update_post_term_cache' => false,
    ]);

    foreach ($sidebar_query->posts as $query_post) {
        $pt = $query_post->post_type;

        if (empty($tags[$pt]['count'])) {
            $tags[$pt]['count'] = 1;
        } else {
            $tags[$pt]['count']++;
        }
    }

    $tags = array_map(function ($tag, $pt_name) use ($query) {
        $pt_object = \get_post_type_object($pt_name);
        $tag['content'] = sprintf(
            _n(
                // translators: 1: Singular post type label. 2: Plural post type label. 3: Post count.
                '%1$s (%3$s)',
                '%2$s (%3$s)',
                $tag['count'],
                'granola',
            ),
            $pt_object->labels->singular_name,
            $pt_object->labels->name,
            $tag['count']
        );

        $tag['url'] = \add_query_arg([
            's' => $query->query['s'],
            'post_type' => $pt_name,
        ], \home_url());

        $tag['classes'] = [
            'g-button',
        ];

        // Remove unnecessary data.
        unset($tag['count']);

        return $tag;
    }, $tags, array_keys($tags));

    if (!empty($tags)) {
        array_unshift($tags, [
            'content' => sprintf(
                // translators: The number of search results.
                _x('All (%s)', 'Search sidebar all items label', 'granola'),
                $query->found_posts
            ),
            'url' => \add_query_arg([
                's' => $query->query['s'],
            ], \home_url()),
            'classes' => [
                'g-button',
            ],
        ]);
    }

    return $tags;
}
