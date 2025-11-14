<?php

namespace Granola\Components\Polylang;

/**
 * Filter the template ID when looking up the template page.
 * If polylang is installed we find the translated post's ID and return that.
 *
 * @param integer|string $template_id The post ID where the template post's content is saved.
 * @param object $object A WP_Post_Type which has been set as the post that contains the template page.
 * @see /Granola/WordPress/TemplatePage.php
 */
function filter_template_id_for_translated_post_id($template_id, $object)
{
    // Bail early - required Polylang functions not available.
    if (!function_exists('pll_get_post_translations') || !function_exists('pll_current_language')) {
        return $template_id;
    }

    // Get translations from the template page's post_id & get the current language.
    $translations = \pll_get_post_translations($template_id);
    $current_language = \pll_current_language('slug');

    // If we have a language and there is more than 1 translation:
    // Then return the template page of that language.
    if (isset($current_language) && strlen(trim($current_language)) > 1 && sizeof($translations) > 1) {
        if (array_key_exists($current_language, $translations)) {
            $template_id = $translations[$current_language];
        }
    }

    return $template_id;
}
