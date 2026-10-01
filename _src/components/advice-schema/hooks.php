<?php

namespace Granola\Components\AdviceSchema;

\add_filter('granola/component/advice-schema', __NAMESPACE__ . '\\filter_args');
\add_filter('wpseo_schema_graph', __NAMESPACE__ . '\\filter_schema_graph', 10, 2);
