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

    $all_results_query = new \WP_Query([
        's' => $query->get('s'),
        'post_status' => 'publish',
        'perm' => 'readable',
        'posts_per_page' => -1,
        'fields' => 'ids',
        'no_found_rows' => true,
        'update_post_meta_cache' => false,
        'update_post_term_cache' => false,
    ]);

    $post_type_counts = [];

    foreach ($all_results_query->posts as $post_id) {
        $pt = get_post_type($post_id);

        if (!$pt) {
            continue;
        }

        $post_type_counts[$pt] = ($post_type_counts[$pt] ?? 0) + 1;
    }

    foreach ($post_type_counts as $pt_name => $count) {
        $pt_object = get_post_type_object($pt_name);

        if (!$pt_object) {
            continue;
        }

        $tags[] = [
            'content' => sprintf(
                _n(
                    '%1$s (%3$s)',
                    '%2$s (%3$s)',
                    $count,
                    'granola'
                ),
                $pt_object->labels->singular_name,
                $pt_object->labels->name,
                number_format_i18n($count)
            ),
            'url' => add_query_arg([
                's' => $query->get('s'),
                'post_type' => $pt_name,
            ], home_url('/')),
            'classes' => [
                'g-tag',
                'is-interactive',
            ],
        ];
    }

    $total = array_sum($post_type_counts);

    if ($total > 0) {
        array_unshift($tags, [
            'content' => sprintf(
                _x('All (%s)', 'Search sidebar all items label', 'granola'),
                number_format_i18n($total)
            ),
            'url' => add_query_arg([
                's' => $query->get('s'),
            ], home_url('/')),
            'classes' => [
                'g-tag',
                'is-interactive',
            ],
        ]);
    }

    return $tags;
}
