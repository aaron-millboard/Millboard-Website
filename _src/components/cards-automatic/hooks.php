<?php

namespace Granola\Components\CardsAutomatic;

// Component args.
\add_filter('granola/component/cards-automatic', __NAMESPACE__ . '\\filter_args');

// Theme.json data: sets the cards background color palette.
// \add_filter('wp_theme_json_data_theme', __NAMESPACE__ . '\\set_cards_background_color_palette');

// Remove the "media|attachment" post type from the relationship field.
\add_filter('acf/load_field/name=selected', __NAMESPACE__ . '\\remove_attachment_post_type_from_selected_relationship_field');
