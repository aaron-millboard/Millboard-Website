<?php

namespace Granola\Components\StyleGuide;

// Options page.
\add_filter('acf/init', __NAMESPACE__ . '\\add_style_guide_options_sub_page');

// Redirects.
\add_filter('template_redirect', __NAMESPACE__ . '\\redirect_from_style_guide_pages', 10);
\add_filter('wp_head', __NAMESPACE__ . '\\no_robots_style_guide', 10);

// Filter blocks to append style guide content, if on the correct page.
\add_filter('granola/partial/assets/components/site-main', __NAMESPACE__ . '\\add_cards_block_to_site_main', 1);
\add_filter('granola/partial/assets/components/site-main', __NAMESPACE__ . '\\add_widths_layout_to_site_main', 1);
\add_filter('granola/partial/assets/components/page-header', __NAMESPACE__ . '\\filter_page_header_args', 11);

// Add the state of pages in admin columns.
\add_filter('display_post_states', __NAMESPACE__ . '\\admin_page_columns_show_style_guide_page', 10, 2);

// Style pages in admin columns.
\add_filter('post_class', __NAMESPACE__ . '\\set_admin_page_row_classes', 10, 3);
\add_action('admin_enqueue_scripts', __NAMESPACE__ . '\\enqueue_admin_styles');
\add_action('wp_enqueue_scripts', __NAMESPACE__ . '\\enqueue_style_guide_styles');


// Exclude style guide posts from search results.
// TODO if we start to use the adv component for search more this should be done in a filter in the endpoint (for performance)
\add_filter('pre_get_posts', __NAMESPACE__ . '\\exclude_style_guide_pages_internal_search_results', 10, 1);
