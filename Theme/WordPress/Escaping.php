<?php

namespace Theme\WordPress;

class Escaping
{
    public static function init(): void
    {
        \add_action('wp_kses_allowed_html', [__CLASS__, 'filter_wp_kses_allowed_html'], 10, 2);
    }

    /**
     * Add iframe, responsive image, and tabindex attributes to allowed
     * wp_kses_post tags.
     *
     * @param array $tags Allowed tags, attributes, and/or entities.
     * @param string $context Context to judge allowed tags by.
     *
     * @return array The filtered array of allowed tags, attributes, and/or entities.
     */
    public static function filter_wp_kses_allowed_html($tags, $context): array
    {
        if ('post' === $context) {
            $tags['iframe'] = [
                'src' => true,
                'height' => true,
                'width' => true,
                'frameborder' => true,
                'allowfullscreen' => true,
                'loading' => true,
            ];

            $tags['img']['sizes'] = true;
            $tags['img']['srcset'] = true;

            $tags['a']['tabindex'] = true;

            // Basic SVG filtering.
            $tags['svg'] = [
                'class' => true,
                'aria-hidden' => true,
                'aria-labelledby' => true,
                'role' => true,
                'xmlns' => true,
                'width' => true,
                'height' => true,
                'fill' => true,
                'viewbox' => true, // Must be lower case.
            ];

            $tags['path'] = [
                'd' => true,
                'fill' => true,
            ];

            $tags['g'] = [
                'id' => true,
                'stroke' => true,
                'stroke-width' => true,
                'fill' => true,
                'fill-rule' => true,
            ];
        }

        return $tags;
    }
}
