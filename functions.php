<?php

// ----------------------------------------------------
// Register the autoloader from Composer.
// ----------------------------------------------------

if (file_exists($autoloader = __DIR__ . '/vendor/autoload.php')) {
    require $autoloader;
}

// ----------------------------------------------------
// Load config values.
// ----------------------------------------------------
\Granola\Config::init();

// ----------------------------------------------------
// Load core Granola functionality.
// ----------------------------------------------------
\Granola\Partial::init();
\Granola\Component::init();

\Granola\WordPress\Admin::init();
\Granola\WordPress\Cleanup::init();
\Granola\WordPress\Comments::init();
\Granola\WordPress\EditHomepage::init();
\Granola\WordPress\Enqueue::init();
\Granola\WordPress\Gutenberg::init();
\Granola\WordPress\Head::init();
\Granola\WordPress\Images::init();
\Granola\WordPress\PostsPT::init();
\Granola\WordPress\Security::init();
\Granola\WordPress\TemplatePage::init();
\Granola\WordPress\ThemeSetup::init();
\Granola\WordPress\Updates::init();
\Granola\WordPress\UploadMimes::init();

// ----------------------------------------------------
// Load custom Theme functionality.
// ----------------------------------------------------
// All custom functions for a theme should go in the
// 'Theme' folder and (ideally) follow a namespaced,
// class-based approach.
// ----------------------------------------------------
// WordPress - Optimisation & Other Functionality.
// ----------------------------------------------------
\Theme\WordPress\Emails::init();
\Theme\WordPress\Escaping::init();
\Theme\WordPress\Excerpt::init();
\Theme\WordPress\Menus::init();
\Theme\WordPress\MimeTypes::init();
\Theme\WordPress\Preloads::init();
\Theme\WordPress\Sidebars::init();

// ----------------------------------------------------
// Custom Shortcodes.
// ----------------------------------------------------
\Theme\Shortcodes\Year::init();

// ----------------------------------------------------
// Custom Post Types.
// ----------------------------------------------------
// \Theme\PostTypes\Event::init();
\Theme\PostTypes\Page::init();
\Theme\PostTypes\Post::init();
\Theme\PostTypes\CaseStudy::init();

// ----------------------------------------------------
// Custom Taxonomies.
// ----------------------------------------------------
// \Theme\Taxonomies\Location::init();
\Theme\Taxonomies\Category::init();

// ----------------------------------------------------
// Custom Plugin functionality.
// ----------------------------------------------------
\Theme\Plugins\ACF::init();
\Theme\Plugins\YoastSEO::init();
