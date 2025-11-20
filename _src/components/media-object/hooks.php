<?php

namespace Granola\Components\MediaObject;

\add_filter('granola/partial/assets/components/media-object', __NAMESPACE__ . '\\set_heading_level', 5, 2);
\add_filter('granola/partial/assets/components/media-object', __NAMESPACE__ . '\\filter_args');
