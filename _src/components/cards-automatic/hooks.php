<?php

namespace Granola\Components\CardsAutomatic;

// Component args.
\add_filter('granola/component/cards-automatic', __NAMESPACE__ . '\\filter_args');

// Theme.json data: sets the cards background color palette.
\add_filter('wp_theme_json_data_theme', __NAMESPACE__ . '\\set_cards_background_color_palette');
