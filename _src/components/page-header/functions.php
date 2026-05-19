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
        'background_color' => 'brand-1',
        'attributes' => [],
        'show_breadcrumbs' => true,
        'bg_gradient' => false,
        'author_info' => [],
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

        // Check if this is WC product - set type to product
        if ((class_exists('\\WooCommerce')) && $args['object'] instanceof \WC_Product) {
            $args['type'] = 'product';
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
                $args['heading'] = sprintf(
                    \_n(
                        // translators: 1: quantity of comments. 2: post title.
                        'Displaying %1$s search result for \'%2$s\'',
                        'Displaying %1$s search results for \'%2$s\'',
                        $object->found_posts,
                        'granola'
                    ),
                    \number_format_i18n($object->found_posts),
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
        } elseif ((class_exists('\\WooCommerce')) && $object instanceof \WC_Product) {
            // WP Products
            // Adjust background color for products
            $args['background_color'] = 'brand-2';
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

                // Check if post has is_featured field set to true
                if (\get_post_meta($object->ID, 'is_featured', true)) {
                    $args['preheading'] = \__('Featured article', 'granola');
                }

                // Show CTA if this is not current post
                if (\get_the_ID() !== $object->ID) {
                    $args['cta'] = [
                        'title' => \__('Read article', 'granola'),
                        'url' => \get_permalink($object),
                    ];
                } else {
                    // If this is current post, no CTA
                    unset($args['cta']);
                }
            }

            $args['author_info'] = get_author_info($object);

            unset($args['object']);
        }


        // WC Checkout Order Received page
        if ((class_exists('\\WooCommerce')) && \is_order_received_page()) {
            // Get WC order
            $order_id = absint(\get_query_var('order-received'));
            $order = \wc_get_order($order_id);
            $is_failed = $order ? $order->has_status('failed') : false;
            if ($is_failed) {
                $args['heading'] = \__('Sorry', 'granola');
                $args['preheading'] = \__('There was an issue with your order', 'granola');
            } else {
                $args['heading'] = \__('Thank you', 'granola');
                $args['preheading'] = \__('Your order has been received', 'granola');
            }
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
    // Prepare args for sub-components
    // -------------------------------------------------------------------------
    if (!empty($args['image'])) {
        if (!is_array($args['image'])) {
            $args['image'] = [
                'attachment_id' => $args['image'],
            ];
        }

        // Loading, set to eager
        $args['image']['loading'] = 'eager';
        $args['image']['attributes']['fetchpriority'] = 'high';
        $args['image']['attributes']['data-spai-eager'] = true;
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
    if (!empty($args['background_color']) && $args['background_color'] !== 'none') {
        $args['classes'][] = 'has-' . $args['background_color'] . '-background-color';
        $args['classes'][] = 'has-background';

        // Only allow gradient on some brand colors.
        if ($args['background_color'] !== 'brand-5' && $args['background_color'] !== 'brand-6') {
            $args['bg_gradient'] = false;
        }
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

/**
 * Build author info for supported single post types.
 */
function get_author_info(\WP_Post $post): array
{
    $supported_author_types = ['post', 'case-study', 'advice-centre'];

    if (!\is_singular($supported_author_types) || (int) \get_queried_object_id() !== (int) $post->ID) {
        return [];
    }

    $author_id = (int) $post->post_author;

    if ($author_id <= 0) {
        return [];
    }

    $first_name = trim((string) \get_the_author_meta('first_name', $author_id));
    $last_name = trim((string) \get_the_author_meta('last_name', $author_id));
    $display_name = trim($first_name . ' ' . $last_name);

    if ($display_name === '') {
        $display_name = trim((string) \get_the_author_meta('display_name', $author_id));
    }

    if ($display_name === '') {
        $user = \get_userdata($author_id);
        $display_name = ($user instanceof \WP_User) ? (string) $user->display_name : '';
    }

    if ($display_name === '') {
        return [];
    }

    $author_info = [
        'display_name' => $display_name,
        'bio' => '',
        'image' => [],
    ];

    $bio = trim((string) \get_the_author_meta('description', $author_id));

    if ($bio !== '') {
        $author_info['bio'] = $bio;
    }

    $attachment_id = get_author_image_attachment_id($author_id);

    if ($attachment_id > 0) {
        $author_info['image'] = [
            'attachment_id' => $attachment_id,
            'size' => 'thumbnail',
            'alt' => $display_name,
            'classes' => ['page-header__author-avatar-image'],
        ];
    }

    return $author_info;
}

/**
 * Resolve author avatar attachment id from ACF user fields.
 */
function get_author_image_attachment_id(int $author_id): int
{
    if (!\function_exists('get_field')) {
        return 0;
    }

    $field_names = \apply_filters(
        'granola/components/page-header/author-image-fields',
        ['user_image', 'author_image', 'image']
    );

    if (!is_array($field_names)) {
        $field_names = ['user_image', 'author_image', 'image'];
    }

    $field_names = array_values(array_unique(array_filter($field_names, 'is_string')));
    $user_field_reference = 'user_' . $author_id;

    foreach ($field_names as $field_name) {
        $image_value = \get_field($field_name, $user_field_reference);

        if (is_array($image_value)) {
            if (!empty($image_value['attachment_id'])) {
                return (int) $image_value['attachment_id'];
            }

            if (!empty($image_value['id'])) {
                return (int) $image_value['id'];
            }

            if (!empty($image_value['ID'])) {
                return (int) $image_value['ID'];
            }
        }

        if (is_numeric($image_value)) {
            return (int) $image_value;
        }
    }

    return 0;
}
