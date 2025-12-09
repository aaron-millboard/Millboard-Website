<?php

namespace Granola\Components\People;

// Component args.
\add_filter('granola/component/people', __NAMESPACE__ . '\\filter_args');

// Render block data: adds the count of total inner blocks to the block data.
\add_filter('render_block_data', __NAMESPACE__ . '\\get_count_of_total_inner_blocks', 10, 3);

// Theme.json data: sets the cards background color palette.
// \add_filter('wp_theme_json_data_theme', __NAMESPACE__ . '\\set_cards_background_color_palette');
