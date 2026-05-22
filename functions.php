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
\Theme\WordPress\Forms::init();
\Theme\WordPress\Menus::init();
\Theme\WordPress\MimeTypes::init();
\Theme\WordPress\Preloads::init();
\Theme\WordPress\Sidebars::init();

// ----------------------------------------------------
// Custom Shortcodes.
// ----------------------------------------------------
\Theme\Shortcodes\Year::init();

// ----------------------------------------------------
// Multisite features.
// ----------------------------------------------------
\Theme\Multisite\Hreflang::init();

// ----------------------------------------------------
// Custom Post Types.
// ----------------------------------------------------
\Theme\PostTypes\AdviceCentre::init();
\Theme\PostTypes\CaseStudy::init();
\Theme\PostTypes\Image::init();
\Theme\PostTypes\Installer::init();
\Theme\PostTypes\Page::init();
\Theme\PostTypes\Post::init();
\Theme\PostTypes\Product::init();
\Theme\PostTypes\Distributor::init();
\Theme\PostTypes\DisplayArea::init();

// ----------------------------------------------------
// Custom Taxonomies.
// ----------------------------------------------------
\Theme\Taxonomies\AdviceCategory::init();
\Theme\Taxonomies\Category::init();
\Theme\Taxonomies\ImageCategory::init();
\Theme\Taxonomies\DistributorType::init();
\Theme\Taxonomies\InstallerType::init();
\Theme\Taxonomies\ProductCategory::init();

// ----------------------------------------------------
// Custom Plugin functionality.
// ----------------------------------------------------
\Theme\Plugins\ACF::init();
\Theme\Plugins\YoastSEO::init();

// ----------------------------------------------------
// WooCommerce functionality.
// ----------------------------------------------------

\Theme\WooCommerce\CountryRestrictions::init();
\Theme\WooCommerce\QuoteShare::init();
\Theme\WooCommerce\Settings::init();

// ----------------------------------------------------
// Other custom functionality.
// ----------------------------------------------------
\Theme\Hubspot\Attribution::init();
