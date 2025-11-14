<?php

namespace Granola\Components\CarbonBadge;

\add_filter('granola/partial/assets/components/carbon-badge', __NAMESPACE__ . '\\filter_args');

\add_filter('wp_enqueue_scripts', __NAMESPACE__ . '\\localize_carbon_badge', 50); // 50: localize after default priority.
