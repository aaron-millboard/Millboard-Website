<?php

namespace Granola\Components\TemplateLoop;

function filter_args(array $args): ?array
{
    $args = array_merge([
        'items' => [],
        'object' => \Granola\WordPress\PageObject::get(),
        'items_component_args' => [],
        'taxonomy_filters_args' => [],
        'taxonomy' => 'category',
        'filter_label' => \__('Explore and filter all articles', 'granola'),
        'post_type' => null,
    ], $args);

    // Determine post type.
    // On search pages:
    // - if a filter is selected, use it
    // - otherwise show all matching post types
    if (\is_search()) {
        if (isset($_GET['post_type']) && $_GET['post_type'] !== '') {
            $post_type = \sanitize_key(\wp_unslash($_GET['post_type']));
        } else {
            $post_type = 'any';
        }
    }

    // Set default taxonomy filter arguments.
    $args['taxonomy_filters'] = [
        'label' => $args['filter_label'],
        'taxonomy' => $args['taxonomy'],
        'object' => $args['object'],
    ];

    // Fill items into items component args.
    $args['items_component_args']['items'] = $args['items'];
    $args['items_component_args']['wp_query'] = false;

    if (!empty($args['post_type'])) {
        $post_type = $args['post_type'];
    } else {
        $post_type = get_post_type_from_object($args['object']);
    }

    $args['items_component_args']['post_type'] = $post_type;
    $args['taxonomy_filters']['post_type'] = $post_type;

    $paged = \get_query_var('paged') ? \get_query_var('paged') : 1;
    $taxonomy = $args['taxonomy'];

    $query_args = [
        'post_type' => $post_type,
        'posts_per_page' => 12,
        'post_status' => 'publish',
        'paged' => $paged,
    ];

    // Preserve search term on search pages.
    if (\is_search()) {
        $search_term = \get_search_query();

        if ($search_term !== '') {
            $query_args['s'] = $search_term;
        }
    }

    // Filter by taxonomy if provided in URL.
    if (isset($_GET[$taxonomy]) && !empty($_GET[$taxonomy])) {
        $terms = array_filter(
            explode(' ', \sanitize_text_field(\wp_unslash($_GET[$taxonomy])))
        );

        if (!empty($terms)) {
            $query_args['tax_query'] = [
                'relation' => 'AND',
            ];

            foreach ($terms as $term) {
                $query_args['tax_query'][] = [
                    'taxonomy' => $taxonomy,
                    'field' => 'slug',
                    'terms' => $term,
                ];
            }
        }
    }

    if (empty($args['items'])) {
        $query = new \WP_Query($query_args);
        $args['items'] = [];

        foreach ($query->posts as $post) {
            $args['items'][] = [
                'object' => $post,
            ];
        }

        $max_num_pages = $query->max_num_pages;
    } else {
        $max_num_pages = 1;
    }

    $args['taxonomy_filters_args'] = [
        'label' => $args['filter_label'],
        'taxonomy' => $args['taxonomy'],
        'object' => $args['object'],
        'show_images' => false,
        'preserve_url' => true,
    ];

    $args['items_component_args']['items'] = $args['items'];
    $args['items_component_args']['wp_query'] = false;
    $args['items_component_args']['post_type'] = $post_type;
    $args['items_component_args']['limit'] = 12;

    if (!isset($args['items_component_args']['columns'])) {
        $args['items_component_args']['columns'] = 3;
    }

    if ($post_type === 'case-study') {
        $args['items_component_args']['columns'] = 2;
        $args['taxonomy_filters']['label'] = \__('Explore and filter all case studies', 'granola');
    } elseif ($post_type === 'product') {
        $args['items_component_args']['columns'] = 4;
        unset($args['taxonomy_filters']);
    }

    $args['pagination_args'] = [
        'paged' => $paged,
        'max_num_pages' => $max_num_pages,
    ];

    $args['items_component'] = \apply_filters('granola/components/template-loop/items-component', 'cards-automatic');

    $args['items_component_args'] = \apply_filters(
        'granola/components/template-loop/items-component/args',
        $args['items_component_args']
    );

    return $args;
}

/**
 * Gets the relevant post type from the queried/template object.
 *
 * @param object|null $object The queried or templated object.
 * @return string|null The resolved post type, if available.
 */
function get_post_type_from_object(?object $object): ?string
{
    if ($object instanceof \WP_Post) {
        return $object->post_type;
    }

    if ($object instanceof \WP_Post_Type) {
        return $object->name;
    }

    if ($object instanceof \WP_Term) {
        $taxonomy = \get_taxonomy($object->taxonomy);

        if ($taxonomy instanceof \WP_Taxonomy) {
            return get_post_type_from_taxonomy($taxonomy);
        }
    }

    if ($object instanceof \WP_Taxonomy) {
        return get_post_type_from_taxonomy($object);
    }

    return \get_post_type() ?: null;
}

/**
 * Gets the first registered post type for a taxonomy.
 *
 * @param \WP_Taxonomy $taxonomy The taxonomy object.
 * @return string|null The resolved post type, if available.
 */
function get_post_type_from_taxonomy(\WP_Taxonomy $taxonomy): ?string
{
    if (empty($taxonomy->object_type)) {
        return null;
    }

    $object_types = array_values($taxonomy->object_type);

    return $object_types[0] ?? null;
}

/**
 * Filters what component should be used to display the template loop items on search pages.
 *
 * @param string $component The name of the component to filter.
 * @return string The filtered name of the component used to display posts.
 */
function filter_search_template_loop(string $component): string
{
    if (\is_search()) {
        return 'post-summaries';
    }

    return $component;
}