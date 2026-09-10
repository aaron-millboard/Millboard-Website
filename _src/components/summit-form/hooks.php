<?php

namespace Granola\Components\SummitForm;

\add_filter('granola/component/summit-form', __NAMESPACE__ . '\\filter_args');

\add_filter('granola/scripts/localization', __NAMESPACE__ . '\\add_endpoint_localization');
