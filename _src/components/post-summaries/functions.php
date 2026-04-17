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

        if (!empty($query->get('s'))) {
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
                    $query->get('s')
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

    //global $wp_query;

    echo '<pre>';
    var_dump([
        'is_search' => is_search(),
        'pageobject_s' => \Granola\WordPress\PageObject::get()->get('s'),
        'mainquery_s' => $query->get('s'),
        'get_search_query' => get_search_query(),
        'requested_post_type' => get_query_var('post_type'),
        'found_posts' => $query->found_posts,
    ]);
    echo '</pre>';
    $tags = [];
    $search_term = (string) $query->get('s');

    if ($search_term === '') {
        return $tags;
    }

    $sidebar_query = new \WP_Query([
        'posts_per_page' => 500,
        's' => $search_term,
        'post_status' => 'publish',
        'perm' => 'readable',

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

    $total = 0;

    $tags = array_map(function ($tag, $pt_name) use ($search_term, &$total) {
        $pt_object = \get_post_type_object($pt_name);

        if (!$pt_object) {
            return null;
        }

        $total += (int) $tag['count'];

        $tag['content'] = sprintf(
            _n(
                '%1$s (%3$s)',
                '%2$s (%3$s)',
                $tag['count'],
                'granola'
            ),
            $pt_object->labels->singular_name,
            $pt_object->labels->name,
            \number_format_i18n($tag['count'])
        );

        $tag['url'] = \add_query_arg([
            's' => $search_term,
            'post_type' => $pt_name,
        ], \home_url('/'));

        $tag['classes'] = [
            'g-tag',
            'is-interactive',
        ];

        unset($tag['count']);

        return $tag;
    }, $tags, array_keys($tags));

    $tags = array_values(array_filter($tags));

    if (!empty($tags)) {
        array_unshift($tags, [
            'content' => sprintf(
                _x('All (%s)', 'Search sidebar all items label', 'granola'),
                \number_format_i18n($total)
            ),
            'url' => \add_query_arg([
                's' => $search_term,
            ], \home_url('/')),
            'classes' => [
                'g-tag',
                'is-interactive',
            ],
        ]);
    }

    return $tags;
}
