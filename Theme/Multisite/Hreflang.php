<?php

namespace Theme\Multisite;

/**
 * Handles the generation of hreflang links.
 */
class Hreflang
{
    public static function init()
    {
        \add_filter('granola/wordpress/head/links', [__CLASS__, 'add_hreflang_links']);
        \add_action('wp_head', [__CLASS__, 'output_bulk_href_html'], 0);
    }

    /**
     * Outputs any bulk hreflang HTML into the <head>.
     *
     * TODO: This should be reviewed and replaced in future as it is a potential security risk.
     *
     * @return void
     */
    public static function output_bulk_href_html()
    {
        $bulk_href_html = \get_field('hreflang_bulk_upload');
        if (empty($bulk_href_html)) {
            return;
        }

        echo $bulk_href_html;
    }

    /**
     * Add hreflang links to the head if there are valid links set on the page object.
     *
     * @param array $links The unfiltered list of <link> attribute arrays.
     * @return array The filtered list of <link> attribute arrays, with hreflangs added as required.
     */
    public static function add_hreflang_links(array $links): array
    {
        $page_object = \Granola\WordPress\PageObject::get();

        // Bail early - invalid objects for adding hreflang links.
        if (!\Granola\Helpers::is_valid_class($page_object, ['WP_Post', 'WP_Post_Type', 'WP_Taxonomy', 'WP_Term'])) {
            return $links;
        }

        // Special case - Template Pages.
        if (!($page_object instanceof \WP_Post)) {
            $template_page = \Granola\WordPress\TemplatePage::get_template_page($page_object);
            if (!empty($template_page)) {
                $page_object = $template_page;
            }
        }

        $hreflang_links = \get_field('hreflang_links', $page_object);

        // Bail early - no links to add.
        if (empty($hreflang_links)) {
            return $links;
        }

        foreach ($hreflang_links as $hreflang_link) {
            $hreflang = $hreflang_link['language'];
            if (!empty($hreflang_link['region'])) {
                $hreflang .= '-' . $hreflang_link['region'];
            }

            $links[] = [
                'rel' => 'alternate',
                'hreflang' => $hreflang,
                'href' => $hreflang_link['url'],
            ];
        }

        return $links;
    }
}
