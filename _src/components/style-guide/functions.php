<?php

namespace Granola\Components\StyleGuide;

const STYLE_GUIDE_FIELD_NAME = 'style_guide_page';
const STYLE_GUIDE_LOCKED = false;

/**
 * Appends a cards block to the site-main if we are on the style-guide parent page.
 * The cards block shows the style-guide parent page's children.
 * This can therefore be used to automatically keep the style-guide landing page showing all of the sub-pages.
 * Without requiring extra admin effort manually adding posts.
 *
 * @hook granola/partial/assets/components/site-main
 * @return (array) $args: the components args.
 */
function add_cards_block_to_site_main($args): ?array
{
    $style_guide_page_id = get_style_guide_page_id();
    $is_logged_in = \is_user_logged_in();
    $current_page_id = \get_the_ID();
    $page_parent = \wp_get_post_parent_id($current_page_id);

    // If user not logged in, return args.
    if (!$is_logged_in && STYLE_GUIDE_LOCKED) {
        return $args;
    }

    // If we are not the correct page, return args.
    if ($current_page_id !== $style_guide_page_id) {
        return $args;
    }

    // -------------------------------------------------------------------------
    // Handle the style guide landing page.
    // -------------------------------------------------------------------------
    // Get Children pages.
    $style_guide_sub_pages = get_child_pages($current_page_id);

    if (!empty($style_guide_sub_pages)) {
        $args['content'] = $args['content'] .
            \Granola\Component::get('cards-automatic', [
                'card_source' => 'selected',
                'selected' => $style_guide_sub_pages,
                'heading' => __('Style Guide: Blocks & Layouts', 'granola'),
                'align' => 'wide',
                'columns' => 3,
                'attributes' => [
                    'data-width' => 'xl',
                ],
            ]);
    }

    return $args;
}

/**
 * Appends a diagram of the widths on the site to a nominated styleguide page.
 *
 * @hook granola/partial/assets/components/site-main
 * @return (array) $args: the components args.
 */
function add_widths_layout_to_site_main($args): ?array
{
    $style_guide_widths_page_id = \get_field('style_guide_widths_page', 'options');
    $is_logged_in = \is_user_logged_in();
    $current_page_id = \get_the_ID();

    // If user not logged in, return args.
    if (!$is_logged_in && STYLE_GUIDE_LOCKED) {
        return $args;
    }

    // If we are not the correct page, return args.
    if ($current_page_id !== $style_guide_widths_page_id) {
        return $args;
    }

    // Add width variables.
    $width_layouts = [];
    $width_layouts[] = '<h2 class="is-style-typestyle-h3">Widths</h2>';
    $width_layouts[] = '<div class="style-guide__width-layout has-background has-contrast-background-color width--xlarge"><span>X Large</span></div>';
    $width_layouts[] = '<div class="style-guide__width-layout has-background has-contrast-background-color width--large"><span>Large</span></div>';
    $width_layouts[] = '<div class="style-guide__width-layout has-background has-contrast-background-color width--medium"><span>Medium</span></div>';
    $width_layouts[] = '<div class="style-guide__width-layout has-background has-contrast-background-color width--small"><span>Small</span></div>';

    // // Inner layouts inside xlarge.
    // $width_layouts[] = '<h2 class="is-style-typestyle-h3">Widths inside X Large</h2>';
    // $width_layouts[] = '<div class="style-guide__width-layout width--xlarge layout-grid">';
    // $width_layouts[] = '<div class="style-guide__width-layout width--large"><span>Large</span></div>';
    // $width_layouts[] = '<div class="style-guide__width-layout width--medium"><span>Medium</span></div>';
    // $width_layouts[] = '<div class="style-guide__width-layout width--small"><span>Small</span></div>';
    // $width_layouts[] = '</div>';
    // // Inner layouts inside large.
    // $width_layouts[] = '<h2 class="is-style-typestyle-h3">Widths inside Large</h2>';
    // $width_layouts[] = '<div class="style-guide__width-layout width--large layout-grid">';
    // $width_layouts[] = '<div class="style-guide__width-layout width--medium"><span>Medium</span></div>';
    // $width_layouts[] = '<div class="style-guide__width-layout width--small"><span>Small</span></div>';
    // $width_layouts[] = '</div>';
    // // Inner layouts inside medium.
    // $width_layouts[] = '<h2 class="is-style-typestyle-h3">Widths inside Medium</h2>';
    // $width_layouts[] = '<div class="style-guide__width-layout width--medium layout-grid">';
    // $width_layouts[] = '<div class="style-guide__width-layout width--small"><span>Small</span></div>';
    // $width_layouts[] = '</div>';
    // // Inner layouts inside small.
    // $width_layouts[] = '<div class="style-guide__width-layout width--small"><span>Small</span></div>';


    $args['content'] = $args['content'] . implode('', $width_layouts);

    return $args;
}

/**
 * Filter on wp_head
 *
 * Checks if we are on the style guide pages. Then adds a noindex meta tag to the head.
 *
 * TODO: Question: as we are 302-redirecting away in the redirectFromStyleGuidePages(), is this needed?
 *
 *
 */
function no_robots_style_guide()
{
    $style_guide_page_id = get_style_guide_page_id();
    $current_page_id = get_the_ID();
    $page_parent = wp_get_post_parent_id($current_page_id);

    // If on style guide page, redirect.
    if ($current_page_id === $style_guide_page_id || $page_parent === $style_guide_page_id) {
        echo "\t<meta name='robots' content='noindex, nofollow' />\r\n";
    }
}

/**
 * Hook: template_redirect
 *
 * Checks which page we are on and redirects if user is not logged in.
 *
 * @return (array) $args: the components args.
 */
function redirect_from_style_guide_pages()
{
    $is_logged_in = \is_user_logged_in();
    $current_page_id = \get_the_ID();
    $page_parent = \wp_get_post_parent_id($current_page_id);
    $style_guide_page_id = get_style_guide_page_id();

    // If user not logged in.
    if (!$is_logged_in && STYLE_GUIDE_LOCKED) {
        // If on style guide page, redirect.
        if ($current_page_id === $style_guide_page_id) {
            \wp_redirect('/');
            exit();
        }

        // If post parent is style guide page, redirect.
        if ($page_parent === $style_guide_page_id) {
            \wp_redirect('/');
            exit();
        }
    }
}

/**
 * Filters the page header args
 *  @hook granola/partial/assets/components/site-main
 * @return (array) $args: the components args.
 */
function filter_page_header_args($args): ?array
{
    $style_guide_page_id = get_style_guide_page_id();
    $is_logged_in = \is_user_logged_in();
    $current_page_id = \get_the_ID();
    $page_parent = \wp_get_post_parent_id($current_page_id);

    // If user not logged in, return args.
    if (!$is_logged_in && STYLE_GUIDE_LOCKED) {
        return $args;
    }

    // If we are not the correct page, return args.
    if ($page_parent !== $style_guide_page_id && $current_page_id !== $style_guide_page_id) {
        return $args;
    }

    // We are on the page header, on either the style-guide parent page or a subpage.
    // For subpages, prefix the header with "style guide:".
    if ($page_parent === $style_guide_page_id) {
        // Breadcrumbs show as true.
        $args['show_breadcrumbs'] = true;

        if (isset($args['heading']['heading'])) {
            $args['heading']['heading'] = sprintf(
                // translators: The original page heading.
                __('Style Guide: %s', 'granola'),
                $args['heading']['heading']
            );
        }
    }


    // For both the parent page and subpages, set a background style.
    // $args['background_color'] = 'style-guide';

    return $args;
}


/**
 * Hooks into acf/init
 *
 * To add an additional options page for the style guide.
 *
 */
function add_style_guide_options_sub_page()
{
    $options_page = _x('Style Guide', 'ACF options page name', 'granola');

    // Create sub-page.
    \acf_add_options_sub_page($options_page);
}


/**
 * Get style-guide page ID.
 *
 * @return (int) the style guide page's POST ID.
 */
function get_style_guide_page_id()
{
    $style_guide_page_id = \get_field(STYLE_GUIDE_FIELD_NAME, 'options');

    if ($style_guide_page_id instanceof \WP_Post) {
        $style_guide_page_id = $style_guide_page_id->ID;
    }

    return $style_guide_page_id ?? null;
}


/**
 * Gets child pages.
 *
 * Can be used by other components if needed.
 *
 * @return array : of posts which are child pages.
 */
function get_child_pages($parent_page)
{
    // Get Children pages .
    $child_pages_args = [
        'posts_per_page' => -1,
        'order'          => 'ASC',
        'orderby'        => 'menu_order',
        'post_parent'    => $parent_page,
        'post_status'    => 'publish',
    ];

    $objects = get_children($child_pages_args);

    $child_pages = [];
    if ($objects) {
        foreach ($objects as $post) {
            $child_pages[] = $post;
        }
    }

    return $child_pages ?? false;
}

 /**
 * Hook into the post state (such as -- Homepage or -- Subpage),
 * And add a flag for the page to show it is a style guide page. Useful in pages list and in menus.
 *
 * @return array Post states array
 */

function admin_page_columns_show_style_guide_page($post_states, $post)
{
    $style_guide_page_id = get_style_guide_page_id();
    $post_id = $post->ID;
    $page_parent = \wp_get_post_parent_id($post_id);

    if ($post_id === $style_guide_page_id || $page_parent === $style_guide_page_id) {
        $post_states[] = __('Style Guide', 'granola');
    }

    return $post_states;
}

function set_admin_page_row_classes($classes, $class, $post_id)
{
    // Make sure we are in the admin screens.
    if (!is_admin()) {
        return $classes;
    }

    // Get the screen we are on.
    $screen = get_current_screen();

    // Bail if not on pages screen.
    if ('page' !== $screen->post_type && 'edit' !== $screen->base) {
        return $classes;
    }

    // Do style guide checks.
    $style_guide_page_id = get_style_guide_page_id();
    $page_parent = \wp_get_post_parent_id($post_id);

    // If a style guide page, add class.
    if ($post_id === $style_guide_page_id || $page_parent === $style_guide_page_id) {
        $classes[] = 'style-guide-page';
    }

    // Return the array
    return $classes;
}


/**
 * Enqueue custom CSS for the map on the back-end.
 */
function enqueue_admin_styles()
{

    if (!\is_admin()) {
        return;
    }

    \wp_enqueue_style(
        'granola-style-guide-admin-styles',
        \Granola\Asset::URL('components/style-guide/styles/admin-styles.css', true),
        [],
        false
    );
}

/**
 * Enqueue custom CSS for the WP admin bar on the front- and back-end.
 */
function enqueue_style_guide_styles()
{
    $is_logged_in = \is_user_logged_in();
    $current_page_id = \get_the_ID();
    $page_parent = \wp_get_post_parent_id($current_page_id);
    $style_guide_page_id = get_style_guide_page_id();

    // If user not logged in.
    if (!$is_logged_in && STYLE_GUIDE_LOCKED) {
        return;
    }
    // If on style guide pag.
    if ($current_page_id !== $style_guide_page_id && $page_parent !== $style_guide_page_id) {
        return;
    }


    \wp_enqueue_style(
        'granola-style-guide-styles',
        \Granola\Asset::URL('components/style-guide/styles/style-guide-styles.css', true),
        [],
        false
    );
}


/**
 * Exclude selected posts from queries with the exception of the query for its singular page.
 */
function exclude_style_guide_pages_internal_search_results($query)
{
    // Bail early - query on any admin page.
    if ($query->is_admin || \is_admin()) {
        return;
    }

    // Bail early - on a single post template.
    if ($query->is_single) {
        return;
    }

    // Bail early - not running a search.
    if (!$query->is_search) {
        return;
    }

    // Bail early - not the main query. TODO: confirm if this check is necessary.
    // if (!$query->is_main_query()) {
    //     return;
    // }

    // Guards done - remove styleguide pages.
    $style_guide_page_id = \get_field(STYLE_GUIDE_FIELD_NAME, 'options');

    // Children of style guide.
    $style_guide_child_objects = \get_pages([
        'child_of' => $style_guide_page_id,
    ]);

    $style_guide_child_pages = [];

    if ($style_guide_child_objects) {
        foreach ($style_guide_child_objects as $key => $object) {
            $style_guide_child_pages[] = $object->ID;
        }
    }

    // Add these post IDs to the search.
    $query->set('post__not_in', array_merge([$style_guide_page_id], $style_guide_child_pages));

    return;
}
