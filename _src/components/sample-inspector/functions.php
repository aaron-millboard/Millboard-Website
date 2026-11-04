<?php

namespace Granola\Components\SampleInspector;

/**
 * Bump when the files in _src/static/sample-inspector change. The site strips
 * ?ver= from assets, so the cache buster is ?mv=.
 */
const VIEWER_VERSION = '4';

/**
 * The 3D sample inspector, carried over from the original site.
 *
 * The viewer itself (application.min.js, style.css and the model) is the
 * original Pikcells build, self-hosted in _src/static/sample-inspector. Two
 * edits were made to application.min.js: the Order Free Sample button reads its
 * link from data-cta-url instead of a hardcoded millboard.co.uk address, and
 * the Underside hotspot wording was brought in line with the compliance
 * crib sheet. The viewer text is English only.
 */
function filter_args(array $args): ?array
{
    // -------------------------------------------------------------------------
    // Default arguments.
    // -------------------------------------------------------------------------
    $args = array_merge([
        'classes' => [],
        'heading' => '',
        'intro' => '',
        'cta_url' => '',
    ], $args);

    // -------------------------------------------------------------------------
    // Required classes.
    // -------------------------------------------------------------------------
    $args['classes'] = array_merge([
        'sample-inspector',
        'wp-block',
    ], $args['classes']);

    $args['heading'] = $args['heading'] ?: \__('Decking Sample Inspector', 'granola');
    $args['intro'] = $args['intro'] ?: \__('Please keep in mind that every board and sample is unique and will differ slightly in appearance.', 'granola');

    // -------------------------------------------------------------------------
    // The button goes to this site's own samples category unless an editor
    // sets a link, so it never depends on another domain.
    // -------------------------------------------------------------------------
    if (empty($args['cta_url'])) {
        $link = \get_term_link('decking-samples', 'product_cat');
        $args['cta_url'] = \is_wp_error($link) ? \home_url('/') : $link;
    }

    $base = \trailingslashit(\Granola\Paths::theme_asset_url('static/sample-inspector'));

    // Everything the script needs.
    $args['config'] = [
        'script' => $base . 'application.min.js?mv=' . VIEWER_VERSION,
        'style' => $base . 'style.css?mv=' . VIEWER_VERSION,
        'assetPath' => $base . 'asset/',
        'ctaUrl' => \esc_url_raw($args['cta_url']),
    ];

    return $args;
}
