<?php

namespace Granola\Components\TemplateLoop;

\add_filter('granola/component/template-loop', __NAMESPACE__ . '\\filter_args');

// Filter the template loop component on search pages.
\add_filter('granola/components/template-loop/items-component', __NAMESPACE__ . '\\filter_search_template_loop');
