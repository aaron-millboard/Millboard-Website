<?php

/**
 * Registers 'Application Image' CPT & handles related functionality.
 *
 * A second gallery, separate from the inspiration gallery ('image'), so that
 * applications can carry their own categories. It renders through the same
 * gallery-loop component, so the design is identical.
 *
 * en-us only for now.
 */

namespace Theme\PostTypes;

class ApplicationImage
{
    public const SLUG = 'application_image';
    public const TAXONOMY = 'application_category';
    public const ARCHIVE_POSTS_PER_PAGE = 7;

    /**
     * Subsites this post type is registered on, by site path.
     */
    protected const SITE_PATHS = [
        '/en-us/',
    ];

    public static function init(): void
    {
        \add_action('init', [__CLASS__, 'register_post_type']);
        \add_filter('granola/templates/post-types', [__CLASS__, 'filter_granola_templates_post_types']);
        \add_action('template_redirect', [__CLASS__, 'redirect_single_cpt']);
        \add_action(
            'pre_get_posts',
            [__CLASS__, 'filter_gallery_archive_query']
        );
        \add_filter('wp_robots', [__CLASS__, 'filter_gallery_robots_link']);
    }

    /**
     * Whether the current subsite has this post type.
     */
    public static function is_enabled(): bool
    {
        $site = \get_site();

        return !empty($site) && in_array($site->path, self::SITE_PATHS, true);
    }

    public static function redirect_single_cpt()
    {
        if (is_singular(self::SLUG)) {
            wp_redirect(get_post_type_archive_link(self::SLUG), 301);
            exit;
        }
    }

    /**
     * Register CPT.
     *
     * @link https://github.com/johnbillion/extended-cpts/wiki/Registering-Post-Types
     */
    public static function register_post_type(): void
    {
        if (!function_exists('register_extended_post_type') || !self::is_enabled()) {
            return;
        }

        \register_extended_post_type(self::SLUG, [
            // Core post type configuration.
            'public' => true,
            'has_archive' => true,
            'hierarchical' => false,
            'show_in_rest' => true,
            'rewrite' => [
                'slug' => 'applications-gallery',
            ],
            'menu_position' => 25, // Below comments.
            'menu_icon' => 'dashicons-format-gallery',
            'enter_title_here' => 'Image Name',
            'supports' => [
                'title',
                'thumbnail',
                'custom-fields',
            ],
            'taxonomies' => [
                self::TAXONOMY,
            ],
            'template' => [
                [
                    'core/paragraph',
                    [
                        'placeholder' => 'Add content...',
                    ]
                ]
            ],

            // Extended post type configuration.
            'admin_cols' => [
                'title' => [
                    'title' => 'Title',
                ],
                'thumbnail' => [
                    'title'          => 'Thumbnail',
                    'featured_image' => 'thumbnail',
                    'width'          => 48,
                    'height'         => 48,
                ],
                self::TAXONOMY => [
                    'taxonomy' => self::TAXONOMY,
                ],
                'updated' => [
                    'title'      => 'Updated',
                    'post_field' => 'post_modified',
                    'date_format' => 'Y/m/d \a\t H:i a',
                ],
            ],
        ], [
            // Override the base names used for labels (optional).
            'singular' => \__('Application Image', 'granola'),
            'plural'   => \__('Applications Gallery', 'granola'),
            'slug'     => self::SLUG,
        ]);
    }

    /**
     * Filter the Granola Templates Post Types array to enable Template Pages for this post type.
     *
     * @see /Granola/WordPress/TemplatePage.php
     *
     * @return array The filtered post type array.
     */
    public static function filter_granola_templates_post_types($post_types)
    {
        if (self::is_enabled()) {
            $post_types[] = self::SLUG;
        }

        return $post_types;
    }

    /**
     * Sets the main applications gallery archive query to match the gallery loop.
     *
     * @param  \WP_Query $query The query being filtered.
     *
     * @return void
     */
    public static function filter_gallery_archive_query(\WP_Query $query): void
    {
        if (
            \is_admin()
            || !$query->is_main_query()
            || !$query->is_post_type_archive(self::SLUG)
        ) {
            return;
        }

        $query->set('posts_per_page', self::ARCHIVE_POSTS_PER_PAGE);

        $tax_query = self::get_category_tax_query_from_request();

        if (!empty($tax_query)) {
            $query->set('tax_query', $tax_query);
        }
    }

    /**
     * Gets the application category query selected through the gallery filter.
     *
     * @return array
     */
    public static function get_category_tax_query_from_request(): array
    {
        if (!isset($_GET[self::TAXONOMY]) || !is_string($_GET[self::TAXONOMY])) {
            return [];
        }

        $category = \sanitize_text_field(\wp_unslash($_GET[self::TAXONOMY]));
        $terms = array_unique(array_filter(explode(' ', $category)));

        if (empty($terms)) {
            return [];
        }

        $tax_query = [
            'relation' => 'AND',
        ];

        foreach ($terms as $term) {
            $tax_query[] = [
                'taxonomy' => self::TAXONOMY,
                'field' => 'slug',
                'terms' => $term,
            ];
        }

        return $tax_query;
    }

    /**
     * Filter the robots meta tag to add a noindex to UI filtered pages.
     *
     * @param array $robots Associative array of robots <meta> content directives.
     * @return array The filtered array of robots <meta> content directives.
     */
    public static function filter_gallery_robots_link(array $robots): array
    {
        if (!\is_post_type_archive(self::SLUG)) {
            return $robots;
        }

        $category = isset($_GET[self::TAXONOMY]) ? sanitize_text_field($_GET[self::TAXONOMY]) : '';

        if (!empty($category)) {
            $robots['noindex'] = true;
            $robots['nofollow'] = true;
        }

        return $robots;
    }
}
