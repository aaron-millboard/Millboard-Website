<?php

namespace Granola\Components\SummitReviewForm;

/**
 * Prepares the Summit pre-arrival form block.
 *
 * The browser posts straight to HubSpot's form submission endpoint, so the
 * portal and form GUID are the only wiring. Both are public by design (they sit
 * in every HubSpot embed). Without a form GUID the block shows nothing on the
 * front end rather than a form that cannot submit.
 */
function filter_args(array $args): ?array
{
    $args = array_merge([
        'classes' => [],
        'hubspot_portal_id' => '26853518',
        'hubspot_form_guid' => '',
        'deadline' => '17 October 2026',
        'nda_url' => '',
        'health_safety_url' => '',
        'competition_law_url' => '',
        'privacy_url' => '',
        'audience' => 'UK',
        'agenda_url' => '',
    ], $args);

    $args['classes'] = array_merge(['summit-review', 'wp-block'], $args['classes']);

    if (empty($args['attributes']['id'])) {
        $args['attributes']['id'] = 'summit-before-you-arrive';
    }

    $audience = strtoupper(trim((string) $args['audience']));
    $args['is_int'] = $audience === 'INT';
    $args['is_fr'] = $audience === 'FR';
    $args['is_us'] = $audience === 'US';

    $args['has_form'] = trim((string) $args['hubspot_form_guid']) !== '';

    if (!$args['has_form']) {
        return empty($args['is_preview']) ? null : $args;
    }

    return $args;
}
