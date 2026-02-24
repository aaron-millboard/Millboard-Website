<?php

namespace Granola\Components\GalleryLoop;

\add_filter('granola/component/gallery-loop', __NAMESPACE__ . '\\filter_args');
\add_action('wp_head', __NAMESPACE__ . '\\add_noindex_meta', 10);
