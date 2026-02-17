<?php

namespace Granola\Components\AccordionItem;

\add_filter('granola/partial/assets/components/accordion-item', __NAMESPACE__ . '\\filter_args');

// Uncomment for easier Yoast Schema Graph debugging.
// \add_filter('yoast_seo_development_mode', '__return_true');

\add_action('wpseo_pre_schema_block_type_acf/accordion-item', __NAMESPACE__ . '\\prepare_accordion_item_schema', 10);
\add_filter('wpseo_schema_block_acf/accordion-item', __NAMESPACE__ . '\\render_accordion_item_block_schema', 10, 2);
