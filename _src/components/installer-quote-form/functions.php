<?php

namespace Granola\Components\InstallerQuoteForm;

function filter_args(array $args): ?array
{
    $args = array_merge([
        'classes' => [],
        'eyebrow' => '',
        'heading' => '',
        'intro' => '',
        'hs_form_id' => '',
        'hs_portal_id' => '26853518',
        'hs_region' => 'eu1',
    ], $args);

    $args['classes'] = array_merge([
        'installer-quote-form',
        'wp-block',
    ], $args['classes']);

    // Contact buttons come from the installer's own phone + email fields.
    $post_id = !empty($args['post_id']) ? (int) $args['post_id'] : \get_the_ID();
    $args['phone'] = \get_field('phone', $post_id);
    $args['email'] = \get_field('email', $post_id);

    $args['has_form'] = !empty($args['hs_form_id']);

    // Enqueue the HubSpot embed script properly (inline <script> in block output
    // is unreliable / can be stripped). The developer embed auto-initialises any
    // .hs-form-html divs on the page. Deduped per portal.
    if (!empty($args['has_form'])) {
        $region = preg_replace('/[^a-z0-9]/', '', strtolower((string) $args['hs_region']));
        $portal = preg_replace('/[^0-9]/', '', (string) $args['hs_portal_id']);
        if ($region && $portal) {
            $handle = 'hs-forms-embed-' . $portal;
            if (!\wp_script_is($handle, 'enqueued')) {
                \wp_enqueue_script(
                    $handle,
                    "https://js-{$region}.hsforms.net/forms/embed/developer/{$portal}.js",
                    [],
                    null,
                    ['in_footer' => true, 'strategy' => 'defer']
                );
            }
        }
    }

    // Bail early - return null for no output (unless previewing in the editor).
    if (empty($args['heading']) && !$args['has_form'] && empty($args['is_preview'])) {
        return null;
    }

    return $args;
}
