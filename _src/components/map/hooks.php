<?php

namespace Granola\Components\Map;

\add_filter('granola/partial/assets/components/map', __NAMESPACE__ . '\\filter_args');

\add_filter('granola/scripts/localization', __NAMESPACE__ . '\\add_google_api_key_localization');

\add_action('rest_api_init', __NAMESPACE__ . '\\register_road_distances_endpoint');

// Send a record's own page to whatever `directory_link` points at, so a partner that
// already has a fuller page does not end up with two competing pages of its own.
\add_action('template_redirect', __NAMESPACE__ . '\\redirect_to_directory_link');
