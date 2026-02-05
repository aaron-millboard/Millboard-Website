<?php

namespace Granola\Components\GTM\Body;

// Filter args before render.
\add_filter('granola/component/gtm/body', __NAMESPACE__ . '\\filter_args');

// Output component on relevant action hook.
\add_action('init', __NAMESPACE__ . '\\hook_component');
