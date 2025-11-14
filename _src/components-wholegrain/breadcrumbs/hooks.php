<?php

namespace Granola\Components\Breadcrumbs;

\add_filter('granola/partial/assets/components/breadcrumbs', __NAMESPACE__ . '\\filter_args');

\add_filter('wpseo_breadcrumb_separator', __NAMESPACE__ . '\\alter_yoast_separator_markup');
\add_filter('wpseo_breadcrumb_output_class', __NAMESPACE__ . '\\set_yoast_wrapper_markup_class');
