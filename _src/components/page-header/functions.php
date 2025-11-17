<?php

namespace Granola\Components\PageHeader;

function filter_args(array $args): ?array
{
    // ---------------------------------------
    // Default arguments.
    // ---------------------------------------
    $args = array_merge([
        'classes' => [],
        'type' => '',
        'image_position' => 'background',
        'background' => 'brand-1',
        'attributes' => [],
        'content' => [],
        'show_breadcrumbs' => true,
    ], $args);

    // ---------------------------------------
    // Required classes.
    // ---------------------------------------
    $args['classes'] = array_merge([
        'page-header',
        'wp-block',
        'alignfull',
    ], $args['classes']);

    // Handle editor preview
    if (!empty($args['is_preview'])) {
        $args['object'] = \get_post($args['post_id']);
    } else {
        $args['object'] = \Granola\WordPress\PageObject::get() ?? null;
    }

    // Set up page header args for each type of view (singular posts, archive pages and terms)
    if (!empty($args['object'])) {
        $object = $args['object'];

        if ($object instanceof \WP_Term) {
            if (empty($args['heading'])) {
                $args['heading'] = $object->name;
            }
        } elseif ($object instanceof \WP_Post_Type) {
            // If the content has a connected archive content page, set the object to that page
            if ($template_page = \Granola\WordPress\TemplatePage::get_template_page($object)) {
                $object = $template_page;
            } else {
                if (empty($args['heading'])) {
                    $args['heading'] = $object->label;
                }
            }
        } elseif ($object instanceof \WP_Query && $object->is_404()) {
            if (empty($args['heading'])) {
                $args['heading'] = \__('404', 'granola');
            }
        } elseif ($object instanceof \WP_Query && $object->is_search()) {
            if (empty($args['heading'])) {
                $args['heading'] = \__('Search', 'granola');
            }

            if (!empty($object->query['s'])) {
                $args['subheading'] = sprintf(
                    // translators: query string.
                    \__("Showing results for '%s'", 'granola'),
                    $object->query['s']
                );
            }
        } elseif ($object instanceof \WP_User) {
            if (empty($args['heading'])) {
                $args['heading'] = sprintf(
                    // translators: author name.
                    \__('Posts by %s', 'granola'),
                    $object->data->display_name
                );
            }
        }

        if ($object instanceof \WP_Post) {
            // -----------------------------------------------------------------
            // Handle filtering content from WordPress posts
            // -----------------------------------------------------------------

            if (empty($args['heading'])) {
                $args['heading'] = $object->post_title;
            }

            if (empty($args['image'])) {
                $args['image'] = \get_post_thumbnail_id($object);
            }

            if ($object->post_type === 'post') {
                $args['meta'] = sprintf(
                    // translators: post publish date.
                    \__('Published on %s ', 'granola'),
                    \get_the_date(\get_option('date_format'), $object->ID)
                );

                $args['labels'] = \Theme\Meta\ObjectMeta::get_object_labels($object->ID, [
                    'limit' => 3,
                    'taxonomies' => ['category']
                ]);

                $args['background'] = false;
                $args['image_position'] = 'inset';

                $args['type'] = 'article';

                if ($author_name = \get_the_author_meta('display_name', $object->post_author)) {
                    $args['meta'] .= sprintf(
                        // translators: author name.
                        \__('by %s', 'granola'),
                        $author_name
                    );
                }
            } elseif ($object->post_type === 'page') {
                if (\is_front_page()) {
                    $args['classes'][] = 'page-header--home';
                    $args['show_breadcrumbs'] = false;
                }

                if (empty($object->post_parent)) {
                    $args['show_breadcrumbs'] = false;
                }
            }

            // Handle the default post title before the post has been saved
            if ($args['heading'] === 'Auto Draft') {
                $args['heading'] = \__('Post Title', 'granola');
            }

            unset($args['object']);
        }
    }

    // -------------------------------------------------------------------------
    // Set up default placeholders in preview if none is provided
    // -------------------------------------------------------------------------
    if (!empty($args['is_preview'])) {
        if (empty($args['heading'])) {
            $args['heading'] = _x('Add title', 'Placeholder for page header title', 'granola');
        }

        if (empty($args['subheading'])) {
            $args['subheading'] = _x('Add subheading', 'Placeholder for page header subheading', 'granola');
        }
    }

    // -------------------------------------------------------------------------
    // Pull the image if one exists
    // -------------------------------------------------------------------------
    if (!empty($args['image'])) {
        if (!is_array($args['image'])) {
            $args['image'] = [
                'attachment_id' => $args['image'],
            ];
        }

        if ($args['image_position'] === 'inset') {
            $args['image']['size'] = 'medium';
            $args['classes'][] = 'has-inset-image';
        } else {
            $args['image']['size'] = 'granola_super';
            $args['classes'][] = 'has-background';
            $args['classes'][] = 'has-background-image';
        }

        // Loading, set to eager
        $args['image']['loading'] = 'eager';
    }

    if (!empty($args['heading'])) {
        $args['heading'] = [
            'content' => $args['heading'],
            'el'      => 'h1',
            'classes' => [
                'page-header__heading',
                'is-style-typestyle-h1'
            ],
        ];
    }

    if (!empty($args['primary_call_to_action'])) {
        $args['primary_call_to_action']['classes'] = 'g-button';
        $args['primary_call_to_action']['content'] = $args['primary_call_to_action']['title'];
    }

    if (!empty($args['background']) && $args['background'] !== 'none') {
        $args['classes'][] = 'has-' . $args['background'] . '-background-color';
        $args['classes'][] = 'has-background';
    }

    if (!empty($args['type'])) {
        $args['classes'][] = 'page-header--type--' . $args['type'];
    }

    if (!empty($args['image_overlay_opacity'])) {
        $args['attributes']['style']['--page-header--overlay-opacity'] = $args['image_overlay_opacity'] . '%';
    }

    if (!empty($args['show_breadcrumbs'])) {
        $args['classes'][] = 'has-breadcrumbs';
    }

    // -------------------------------------------------------------------------
    // Return the filtered args.
    // -------------------------------------------------------------------------
    return $args;
}
