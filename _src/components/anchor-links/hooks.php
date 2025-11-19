<?php

namespace Granola\Components\AnchorLinks;

\add_filter('granola/component/anchor-links', __NAMESPACE__ . '\\filter_args');
\add_filter('the_content', __NAMESPACE__ . '\\set_content_headings_ids', 99);
