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
\Theme\WordPress\AcfBlockPreviews::init();
\Theme\Commercial\PartnerProfiles::init();
\Theme\WordPress\ClientSideMedia::init();
\Theme\WordPress\Emails::init();
\Theme\WordPress\Escaping::init();
\Theme\WordPress\Excerpt::init();
\Theme\WordPress\FitText::init();
\Theme\WordPress\Forms::init();
\Theme\WordPress\Menus::init();
\Theme\WordPress\MimeTypes::init();
\Theme\WordPress\Preloads::init();
\Theme\WordPress\Sidebars::init();

// ----------------------------------------------------
// Structured data (extends Yoast's graph).
// ----------------------------------------------------
\Theme\Meta\Schema::init();
\Theme\Meta\ProductSchema::init();
\Theme\Meta\SiteVerification::init();

// ----------------------------------------------------
// Custom Shortcodes.
// ----------------------------------------------------
\Theme\Shortcodes\Year::init();

// ----------------------------------------------------
// Custom Post Types.
// ----------------------------------------------------
\Theme\PostTypes\AdviceCentre::init();
\Theme\PostTypes\CaseStudy::init();
\Theme\PostTypes\Image::init();
\Theme\PostTypes\ApplicationImage::init();
\Theme\PostTypes\Installer::init();
\Theme\PostTypes\Page::init();
\Theme\PostTypes\Post::init();
\Theme\PostTypes\Product::init();
\Theme\PostTypes\Distributor::init();
\Theme\PostTypes\Showroom::init();
\Theme\PostTypes\ExperienceCentre::init();

// ----------------------------------------------------
// Custom Taxonomies.
// ----------------------------------------------------
\Theme\Taxonomies\AdviceCategory::init();
\Theme\Taxonomies\Category::init();
\Theme\Taxonomies\ImageCategory::init();
\Theme\Taxonomies\ApplicationCategory::init();
\Theme\Taxonomies\DistributorType::init();
\Theme\Taxonomies\InstallerType::init();
\Theme\Taxonomies\ProductCategory::init();

// ----------------------------------------------------
// Custom Plugin functionality.
// ----------------------------------------------------
\Theme\Plugins\ACF::init();
\Theme\Plugins\YoastSEO::init();
\Theme\Plugins\YoastRedirectQueryString::init();

// ----------------------------------------------------
// WooCommerce functionality.
// ----------------------------------------------------

\Theme\WooCommerce\SampleShipping::init();
\Theme\WooCommerce\ConsentFields::init();
\Theme\WooCommerce\OrderAttribution::init();
\Theme\WooCommerce\OrderIdentity::init();
\Theme\WooCommerce\StripePaymentGuard::init();
\Theme\WooCommerce\QuoteShare::init();
\Theme\WooCommerce\Settings::init();
\Theme\WooCommerce\OrderEssentials::init();
\Theme\WooCommerce\ScriptOptimisation::init();
// Keeps the imported sample/POS ordering lines out of Google. They are
// hidden from the catalogue, which does not stop them being indexed.
\Theme\WooCommerce\SampleCatalogueImport::init();
// 'Sample ordering' inside WooCommerce Analytics: who is ordering how
// much, now that nothing is costed or budgeted.
\Theme\WooCommerce\SampleOrderReport::init();
// Finish a sample order inside My Account rather than the shop basket.
\Theme\WooCommerce\SampleOrderCheckout::init();
// The dispatch queue a third-party agency works. Germany has no Saltbox, so
// fulfilment has always happened in a Google Sheet; this puts it back on the
// order. Read the class docblock before touching German order statuses.
\Theme\WooCommerce\SampleFulfilment::init();
// Australian orders shipping to NSW go to TPD (via Sam), not Concept Materials.
\Theme\WooCommerce\RegionalOrderRouting::init();
// A working unsubscribe link in the order confirmation. Compliance's condition
// for letting a rep enter a customer's details on their behalf.
\Theme\WooCommerce\EmailUnsubscribe::init();
// Show the terms of sale that actually apply: consumer or business, by persona.
\Theme\WooCommerce\EmailTerms::init();

// The partner portal launch email. Registered so it appears in WooCommerce's
// email settings, but it has NO trigger: the only way to send it is
// `wp millboard portal-launch send --live`.
// Instantiated INSIDE the filter, never at theme load: the class extends
// WC_Email, so touching it before WooCommerce has defined that class fatals the
// whole site.
\add_filter('woocommerce_email_classes', static function (array $emails): array {
    $emails['Theme_Emails_PortalLaunch'] = new \Theme\Emails\PortalLaunch();

    return $emails;
});

// Same reason: guard on WC_Email already existing, without autoloading, before
// naming the class. By the time a password reset actually runs, it does.
\add_filter('password_reset_expiration', static function ($seconds) {
    if (!\class_exists('WC_Email', false)) {
        return $seconds;
    }

    return \Theme\Emails\PortalLaunch::extend_key_expiry($seconds);
});

\Theme\Emails\Cli::init();

// ----------------------------------------------------
// Partner account types.
// ----------------------------------------------------
// Distributor and installer roles, and the capabilities that decide who
// reaches Brand assets, Sample ordering and the POS lines inside it.
// ----------------------------------------------------
\Theme\Accounts\Roles::init();

// ----------------------------------------------------
// Internal sample ordering (IT team's mb-sof package).
// ----------------------------------------------------
// Hand-maintained by IT and kept verbatim apart from one asset-path edit,
// which is commented in the file. The My Account tab it renders into is the
// wc-account component's; see that component's hooks.php for how the two
// halves agree on one endpoint, one menu item and one access gate.
// ----------------------------------------------------
/*
 * The account endpoint slug, per site, so France can have a French URL.
 *
 * The package defines MB_SOF_ENDPOINT itself behind an `if (!defined())`, and
 * bakes it into its hook names (`woocommerce_account_{slug}_endpoint`), so it
 * has to be settled BEFORE the require below or the two halves wire
 * themselves to different hooks and the panel never renders.
 *
 * An option rather than __(): the text domain is not loaded this early, so a
 * translated slug would silently come back English. It also means adding a
 * market is a setting, the same as the market switch itself:
 *
 *   wp option update millboard_sample_ordering_slug commande-echantillons --url=<site>/fr-fr/
 *   wp option delete millboard_sample_ordering_slug --url=<site>/fr-fr/
 *
 * wc-account/hooks.php fingerprints the registered endpoint list and flushes
 * the rewrite rules when it changes, so the new URL resolves on the next
 * request with nothing to run by hand.
 */
if (!\defined('MB_SOF_ENDPOINT')) {
    $mb_sof_slug = \sanitize_title((string) \get_option('millboard_sample_ordering_slug', ''));

    \define('MB_SOF_ENDPOINT', '' !== $mb_sof_slug ? $mb_sof_slug : 'sample-ordering');

    unset($mb_sof_slug);
}

require_once __DIR__ . '/sample-ordering/inc/millboard-sample-ordering.php';

// ----------------------------------------------------
// Other custom functionality.
// ----------------------------------------------------
\Theme\Hubspot\InlineEmbeds::init();
\Theme\Hubspot\WriteGuard::init();
\Theme\Hubspot\Attribution::init();

// ----------------------------------------------------
// Analytics guards and server-side conversions.
// ----------------------------------------------------
\Theme\Analytics\TagManagerGuard::init();
\Theme\Analytics\ConversionEmitter::init();

// ----------------------------------------------------
// Millboard Summit 2026 invite-only registration.
// ----------------------------------------------------
\Theme\Summit\InviteList::init();
\Theme\Summit\Registrations::init();
\Theme\Summit\Gate::init();
\Theme\Summit\Admin::init();
\Theme\Summit\Cli::init();
\Theme\Summit\ReviewLog::init();
