<?php

namespace Granola\Components\Gallery;

\add_filter('granola/component/gallery', __NAMESPACE__ . '\\filter_args');

// Product choices come from the consumer site, so they are built in the editor only.
foreach (['field_prodcapa1p', 'field_prodcapa2p'] as $key) {
    \add_filter('acf/load_field/key=' . $key, static function (array $field): array {
        if (\is_admin()) {
            $field['choices'] = product_choices();
        }

        return $field;
    });
}
