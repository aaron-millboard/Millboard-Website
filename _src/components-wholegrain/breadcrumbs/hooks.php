<?php

namespace Granola\Components\Breadcrumbs;

\add_filter('granola/partial/assets/components/breadcrumbs', __NAMESPACE__ . '\\filter_args');

\add_filter('wpseo_breadcrumb_separator', __NAMESPACE__ . '\\alter_yoast_separator_markup');
\add_filter('wpseo_breadcrumb_output_class', __NAMESPACE__ . '\\set_yoast_wrapper_markup_class');
\add_filter('wpseo_breadcrumb_links', __NAMESPACE__ . '\\remove_duplicate_yoast_breadcrumb_links', 99);
\add_filter('wpseo_breadcrumb_single_link', __NAMESPACE__ . '\granola_yoast_breadcrumb_variable_product', 10, 2);
