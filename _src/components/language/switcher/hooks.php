<?php

namespace Granola\Components\Language\Switcher;

\add_filter('granola/partial/assets/components/language/switcher', __NAMESPACE__ . '\\filter_args');
\add_filter('wp_get_nav_menu_items', __NAMESPACE__ . '\\filter_menu_items_to_alternates', 10, 2);
