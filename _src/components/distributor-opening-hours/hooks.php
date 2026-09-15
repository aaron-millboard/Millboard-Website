<?php

namespace Granola\Components\DistributorOpeningHours;

\add_filter('granola/component/distributor-opening-hours', __NAMESPACE__ . '\\filter_args');

// The open-or-closed wording is worked out in the browser, so it has to reach the
// script. It rides on `params` alongside the rest of the theme's localisation.
\add_filter('granola/scripts/localization', __NAMESPACE__ . '\\add_status_localization');
