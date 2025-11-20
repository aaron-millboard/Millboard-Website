<?php

namespace Granola;

class Helpers
{
    /**
     * An array of ID counters for each prefix.
     *
     * @var array
     */
    public static $id_counters = [];

    public static function starts_with($haystack, $needle): bool
    {
        $length = strlen($needle);
        return substr($haystack, 0, $length) === $needle;
    }

    /**
     * Builds an HTML string from an array of attribute key-value pairs.
     *
     * Valid attribute value types are: scalars (int, float, string, and bool) and arrays.
     * An empty string is considered a valid value; equivalent to a `true` boolean.
     *
     * @link https://developer.mozilla.org/en-US/docs/Web/HTML/Attributes#boolean_attributes
     *
     * @param array $attributes An array of attribute key-value pairs.
     * @return string A HTML string containing space-separated, escaped HTML element attributes.
     */
    public static function build_attributes(array $attributes = []): string
    {
        if (empty($attributes)) {
            return '';
        }

        $html = array_map(
            function ($key, $val) {
                if (!isset($val) || (!is_scalar($val) && !is_array($val))) {
                    return ''; // invalid value type.
                } elseif (is_bool($val)) {
                    return $val ? \esc_html($key) : '';
                } elseif (is_array($val)) {
                    // Special case: Build CSS declarations for 'style' attribute.
                    if ($key === 'style') {
                        $val = array_map(
                            function ($k, $v) {
                                if (!empty($k) && (!empty($v) || is_numeric($v))) {
                                    return "$k: $v;";
                                }
                            },
                            array_keys($val),
                            $val
                        );
                    }

                    // Build value string from valid array values.
                    $val = implode(' ', array_filter(
                        array_unique($val),
                        fn ($v) => !empty($v) || is_numeric($v)
                    ));
                }

                $key = \esc_html($key);
                $val = self::is_url_attribute($key) ? \esc_url(trim($val)) : \esc_attr(trim($val));

                return "$key=\"$val\"";
            },
            array_keys($attributes),
            $attributes
        );

        return implode(' ', $html);
    }

    /**
     * Determines whether the given attribute must contain a "valid" URL string.
     *
     * Excludes the itemid, itemprop, and ping attributes, as they may not always contain a URL, or
     * may contain a space-separated list of URLs.
     *
     * @link https://url.spec.whatwg.org/#valid-url-string
     * @link https://html.spec.whatwg.org/multipage/indices.html#attributes-3
     *
     * @param string $attribute The attribute name.
     * @return boolean Whether the attribute must contain a URL.
     */
    public static function is_url_attribute(string $attribute): bool
    {
        return !empty($attribute) && in_array($attribute, [
            // Attributes that must contain URL strings.
            'action',
            'cite', // https://developer.mozilla.org/en-US/docs/Web/HTML/Element/blockquote#attr-cite
            'data', // https://developer.mozilla.org/en-US/docs/Web/HTML/Element/object#attr-data
            'formaction',
            'href',
            'poster',
            'src',
        ], true);
    }

    /**
     * Builds an HTML classes string.
     *
     * @param array $classes An array of class strings.
     * @return string An escaped string of classes.
     */
    public static function build_classes(array $classes = []): string
    {
        $classes_string = '';

        if (empty($classes)) {
            return $classes_string;
        }

        $classes_string = implode(' ', array_unique($classes));

        return \esc_attr(trim($classes_string));
    }

    /**
     * Determines whether a given object is an instance of a given set of classes.
     *
     * @param object $object The object to check.
     * @param array $class_names An array of class names to validate against. Default empty array.
     */
    public static function is_valid_class($object, $class_names = []): bool
    {
        foreach ($class_names as $class_name) {
            if (is_a($object, $class_name)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determines whether the query is for any existing taxonomy archive page.
     *
     * Combines WordPress's `is_tax()`, `is_category()`, and `is_tag()` functions.
     *
     * @param string|string[] $taxonomy Taxonomy slug or slugs to check against.
     * @param int|string|int[]|string[] $term Term ID, name, slug, or array of such to check against.
     * @return boolean Whether the query is for an existing taxonomy archive page (custom or built-in).
     */
    public static function is_taxonomy($taxonomy = '', $term = ''): bool
    {
        if ($taxonomy === 'category') {
            return \is_category($term);
        } elseif ($taxonomy === 'post_tag') {
            return \is_tag($term);
        } elseif (!empty($taxonomy)) {
            return \is_tax($taxonomy, $term);
        }

        return \is_tax($taxonomy, $term) || \is_category($term) || \is_tag($term);
    }

    /**
     * Generates an incremental ID that is independent per each different prefix.
     *
     * Expands the functionality of `wp_unique_prefixed_id` so the counter can be reset.
     * Using wp_unique_prefixed_id() generates new IDs the second time page content is generated (i.e. in anchor links).
     *
     * @link https://developer.wordpress.org/reference/functions/wp_unique_prefixed_id/
     *
     * @param string $prefix
     * @return string
     */
    public static function unique_prefixed_id(string $prefix = ''): string
    {
        if (!isset(self::$id_counters[ $prefix ])) {
            self::$id_counters[ $prefix ] = 0;
        }

        $id = ++self::$id_counters[ $prefix ];

        return $prefix . (string) $id;
    }


    /**
     * Override the theme.json color palette for a specific block.
     *
     * @param string $block_name The block name to override.
     * @param array $new_pallete The new pallete to pass in.
     * @param \WP_Theme_JSON_Data $theme_json The theme.json object.
     * @return \WP_Theme_JSON_Data The original, or the overridden one.
    */
    public static function override_theme_json_with_new_palette_for_block($block_name, $new_pallete, $theme_json)
    {
        if (empty($block_name) || empty($new_pallete)) {
            return $theme_json;
        }

        $theme_json_override = [
        'version' => 3,
        'settings' => [
            'blocks' => [
                $block_name => [
                    'color' => [
                        'palette' => [
                            'theme' => $new_pallete
                        ]
                    ]
                ]
            ]
        ]
        ];

        return $theme_json->update_with($theme_json_override);
    }


    /**
     * Recursively merges arrays, allowing nested arrays to be properly merged.
     * Unlike array_merge(), this function will merge nested arrays instead of replacing them.
     *
     * @param array $defaults The default values array.
     * @param array $args The arguments array to merge with defaults.
     * @return array The recursively merged array.
     */
    public static function array_merge_recursive_distinct(array $defaults, array $args): array
    {
        foreach ($args as $key => $value) {
            if (is_array($value) && isset($defaults[$key]) && is_array($defaults[$key])) {
                $defaults[$key] = self::array_merge_recursive_distinct($defaults[$key], $value);
            } else {
                $defaults[$key] = $value;
            }
        }

        return $defaults;
    }

    /**
     * Builds an InnerBlocks tag.
     * See https://www.billerickson.net/innerblocks-with-acf-blocks/ for more on InnerBlocks.
     *
     * @param array $attributes An array of InnerBlocks attributes.
     * @return string An InnerBlocks tag.
     */
    public static function build_inner_blocks_tag(array $attributes = []): string
    {
        if (!is_array($attributes) || empty($attributes)) {
            return '';
        }

        $html = '<InnerBlocks ';

        foreach ($attributes as $key => $value) {
            if ($value === true) {
                $html .= \esc_html($key) . ' ';
            } elseif (!empty($value)) {
                if (is_array($value)) {
                    $value = \wp_json_encode($value);
                }

                $html .= \esc_html($key) . '="' . \esc_attr($value) . '" ';
            }
        }

        $html .= '/>';

        return $html;
    }
}
