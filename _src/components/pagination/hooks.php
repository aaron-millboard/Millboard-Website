<?php

namespace Granola\Components\Pagination;

\add_filter('granola/component/pagination', __NAMESPACE__ . '\\filter_args');

\add_filter('navigation_markup_template', __NAMESPACE__ . '\\filter_pagination_markup_template');
