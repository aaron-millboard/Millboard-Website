<?php

namespace Granola\Components\TrustStrip;

// Component args.
\add_filter('granola/component/trust-strip', __NAMESPACE__ . '\\filter_args');
