<?php

namespace Theme\Plugins;

class YoastSEO
{
    public static function init(): void
    {
        // Reposition Yoast metabox.
        \add_filter('wpseo_metabox_prio', [__CLASS__, 'priority']);

        // Enable Yoast Breadcrumbs by default.
        \add_theme_support('yoast-seo-breadcrumbs');
    }

    /**
     * Reduce the priority of the Yoast meta box so it sits below content meta fields.
     *
     * @return string The new Yoast metabox priority.
     */
    public static function priority(): string
    {
        return 'low';
    }
}
