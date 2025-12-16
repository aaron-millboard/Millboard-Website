<?php

namespace Granola\Components\PageHeader;

function filter_args(array $args): ?array
{
    // ---------------------------------------
    // Default arguments.
    // ---------------------------------------
    $args = array_merge([
        'classes' => [],
        'source' => 'current', // post, custom
        'object' => null,
        'type' => 'page',
        'background' => 'brand-1',
        'attributes' => [],
        'show_breadcrumbs' => true,
        'bg_gradient' => false,
    ], $args);

    // -------------------------------------------------------------------------
    // Add Required classes
    // -------------------------------------------------------------------------
    $args['classes'] = array_merge([
        'page-header',
        'wp-block',
        'alignfull',
    ], $args['classes']);

    // -------------------------------------------------------------------------
    // Define our object
    // -------------------------------------------------------------------------
    if ($args['source'] === 'current') {
        $args['object'] = \Granola\WordPress\PageObject::get() ?? null;

        // Check if this is not page, set type to post
        if ($args['object'] instanceof \WP_Post && $args['object']->post_type !== 'page') {
            $args['type'] = 'post';
        }
    }

    // -------------------------------------------------------------------------
    // Mapping content based on the provided object
    // -------------------------------------------------------------------------
    if (!empty($args['object']) && ($args['source'] === 'post' || $args['source'] === 'current')) {
        $object = $args['object'];

        // Terms
        if ($object instanceof \WP_Term) {
            if (empty($args['heading'])) {
                $args['heading'] = $object->name;
            }

        // Template Pages
        } elseif ($object instanceof \WP_Post_Type) {
            // If the content has a connected archive content page, set the object to that page
            if ($template_page = \Granola\WordPress\TemplatePage::get_template_page($object)) {
                $object = $template_page;
            } else {
                if (empty($args['heading'])) {
                    $args['heading'] = $object->label;
                }
            }

        // Error 404
        } elseif ($object instanceof \WP_Query && $object->is_404()) {
            if (empty($args['heading'])) {
                $args['heading'] = \__('404', 'granola');
            }

        // Search Results
        } elseif ($object instanceof \WP_Query && $object->is_search()) {
            if (empty($args['heading'])) {
                $args['heading'] = \__('Search', 'granola');
            }

            if (!empty($object->query['s'])) {
                $args['preheading'] = \__('Search', 'granola');
                $args['heading'] = sprintf(
                    // translators: query string.
                    \__("Showing results for '%s'", 'granola'),
                    $object->query['s']
                );
            }

        // Authors
        } elseif ($object instanceof \WP_User) {
            if (empty($args['heading'])) {
                $args['heading'] = sprintf(
                    // translators: author name.
                    \__('Posts by %s', 'granola'),
                    $object->data->display_name
                );
            }
        }

        // Single Posts, Pages and other CPTs
        if ($object instanceof \WP_Post) {
            // Manage heading default (post title)
            if (empty($args['heading'])) {
                $args['heading'] = $object->post_title;
            }

            // Manage featured image default
            if (empty($args['image'])) {
                $args['image'] = \get_post_thumbnail_id($object);
            }

            // Manage description default (post excerpt)
            if (!empty($object->post_excerpt)) {
                $args['description'] = $object->post_excerpt;
            }

            // Specific to post + case-study CPT args (to be reviewed)
            if ($object->post_type === 'post' || $object->post_type === 'case-study') {
                $args['type'] = 'post';

                $args['preheading'] = __('Featured article', 'granola');

                // Show CTA if this is not current post
                if (!is_singular('post') || get_the_ID() !== $object->ID) {
                    $args['cta'] = [
                        'title' => __('Read article', 'granola'),
                        'url' => get_permalink($object),
                    ];
                } else {
                    // If this is current post, no CTA
                    unset($args['cta']);
                }
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

        if (empty($args['preheading'])) {
            $args['preheading'] = _x('Add preheading', 'Placeholder for page header preheading', 'granola');
        }
    }

    // -------------------------------------------------------------------------
    // Prepeare args for sub-components
    // -------------------------------------------------------------------------
    if (!empty($args['image'])) {
        if (!is_array($args['image'])) {
            $args['image'] = [
                'attachment_id' => $args['image'],
            ];
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

    if (!empty($args['description'])) {
        $args['description'] = [
            'content' => $args['description'],
            'classes' => [
                'page-header__description-text'
            ],
        ];
    }

    if (!empty($args['cta'])) {
        $args['cta'] = [
            'title'    => $args['cta']['title'] ?? '',
            'url'      => $args['cta']['url'] ?? '',
            'attributes' => [
                'target' => $args['cta']['target'] ?? '',
                'rel'    => $args['cta']['rel'] ?? '',
            ],
            'classes' => [
                'page-header__cta',
                'g-button'
            ],
        ];
    }

    // -------------------------------------------------------------------------
    // Manipulate classes based on args
    // -------------------------------------------------------------------------
    if (!empty($args['background']) && $args['background'] !== 'none') {
        $args['classes'][] = 'has-' . $args['background'] . '-background-color';
        $args['classes'][] = 'has-background';
    }

    if (!empty($args['type'])) {
        $args['classes'][] = 'page-header--type--' . $args['type'];
    }

    if (!empty($args['show_breadcrumbs'])) {
        $args['classes'][] = 'has-breadcrumbs';
    }

    // -------------------------------------------------------------------------
    // Return the filtered args.
    // -------------------------------------------------------------------------
    return $args;
}
