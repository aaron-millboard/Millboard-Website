<?php

namespace Granola\Components\HomeHero;

\add_filter('granola/component/home-hero', __NAMESPACE__ . '\\filter_args');
\add_filter('granola/components/site-main/header_blocks', __NAMESPACE__ . '\\filter_header_blocks');
