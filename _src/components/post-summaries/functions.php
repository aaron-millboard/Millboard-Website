<?php

namespace Granola\Components\PostSummaries;

function filter_args(array $args): ?array
{
    $args = array_merge([
        'classes' => [],
        'sidebar_tags' => [],
    ], $args);

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
        $search_term = (string) $query->get('s');
        $selected_post_type = '';

        if (isset($_GET['post_type']) && $_GET['post_type'] !== '') {
            $selected_post_type = \sanitize_key(\wp_unslash($_GET['post_type']));
        }

        $display_count = get_filtered_search_result_count($search_term, $selected_post_type);

        if ($search_term !== '') {
            $args['heading'] = [
                'content' => sprintf(
                    \_n(
                        'Displaying %1$s search result for \'%2$s\'',
                        'Displaying %1$s search results for \'%2$s\'',
                        $display_count,
                        'granola'
                    ),
                    \number_format_i18n($display_count),
                    $search_term
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

    return $args;
}

/**
 * Return the count for the current search term and optional selected post type.
 *
 * @param string $search_term
 * @param string $selected_post_type
 * @return int
 */
function get_filtered_search_result_count(string $search_term, string $selected_post_type = ''): int
{
    if ($search_term === '') {
        return 0;
    }

    $query_args = [
        'post_type' => $selected_post_type !== '' ? $selected_post_type : 'any',
        'posts_per_page' => 1,
        's' => $search_term,
        'post_status' => 'publish',
        'perm' => 'readable',
    ];

    $count_query = new \WP_Query($query_args);

    return (int) $count_query->found_posts;
}

/**
 * Generates link tags for post types in current search results.
 *
 * @param \WP_Query $query
 * @return array
 */
function get_search_query_post_type_tags(\WP_Query $query): array
{
    $tags = [];
    $search_term = (string) $query->get('s');
    $current_post_type = '';

    if (isset($_GET['post_type']) && $_GET['post_type'] !== '') {
        $current_post_type = \sanitize_key(\wp_unslash($_GET['post_type']));
    }

    if ($search_term === '') {
        return $tags;
    }

    $sidebar_query = new \WP_Query([
        'post_type' => 'any',
        'posts_per_page' => -1,
        's' => $search_term,
        'post_status' => 'publish',
        'perm' => 'readable',
        'no_found_rows' => true,
        'update_post_meta_cache' => false,
        'update_post_term_cache' => false,
    ]);

    $counts = [];

    foreach ($sidebar_query->posts as $query_post) {
        $pt = $query_post->post_type;

        if (empty($counts[$pt])) {
            $counts[$pt] = 1;
        } else {
            $counts[$pt]++;
        }
    }

    $total = 0;

    foreach ($counts as $pt_name => $count) {
        $pt_object = \get_post_type_object($pt_name);

        if (!$pt_object) {
            continue;
        }

        $total += (int) $count;

        $classes = [
            'g-tag',
            'is-interactive',
        ];

        if ($current_post_type === $pt_name) {
            $classes[] = 'is-active';
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
                \number_format_i18n($count)
            ),
            'url' => \add_query_arg([
                's' => $search_term,
                'post_type' => $pt_name,
            ], \home_url('/')),
            'classes' => $classes,
        ];
    }

    if (!empty($tags)) {
        $all_classes = [
            'g-tag',
            'is-interactive',
        ];

        if ($current_post_type === '') {
            $all_classes[] = 'is-active';
        }

        array_unshift($tags, [
            'content' => sprintf(
                _x('All (%s)', 'Search sidebar all items label', 'granola'),
                \number_format_i18n($total)
            ),
            'url' => \add_query_arg([
                's' => $search_term,
            ], \home_url('/')),
            'classes' => $all_classes,
        ]);
    }

    return $tags;
}