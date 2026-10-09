<?php

namespace Granola\Components\Language\Switcher;

\add_filter('granola/partial/assets/components/language/switcher', __NAMESPACE__ . '\\filter_args');
\add_filter('wp_get_nav_menu_items', __NAMESPACE__ . '\\filter_menu_items_to_alternates', 10, 2);
\add_filter('wp_get_nav_menu_items', __NAMESPACE__ . '\\mark_current_locale_item', 10, 2);
// Priority 20, after menu-item's own filter_args. Component hooks load
// alphabetically, so language/switcher registers before menu/menu-item, and at
// equal priority this ran first -- then filter_args ASSIGNS $args['link'] whole
// and the attribute was gone before it could render.
\add_filter('granola/component/menu/menu-item', __NAMESPACE__ . '\\set_current_locale_aria', 20);
