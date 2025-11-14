<?php

namespace Granola\Components\Polylang;

\add_filter('granola/templates/template-page-id', __NAMESPACE__ . '\\filter_template_id_for_translated_post_id', 10, 2);
