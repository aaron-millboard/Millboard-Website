<?php

namespace Granola\Components\GTM\Head;

// Filter args before render.
\add_filter('granola/component/gtm/head', __NAMESPACE__ . '\\filter_args');

// Output component on relevant action hook.
\add_action('init', __NAMESPACE__ . '\\hook_component');
