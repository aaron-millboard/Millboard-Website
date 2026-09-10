<?php

namespace Granola\Components\SummitForm;

use Theme\Summit\Audiences;
use Theme\Summit\Gate;

/**
 * Prepares the Summit registration form block.
 *
 * The form replaces the HubSpot embed because HubSpot cannot refuse a
 * submission: it has no way to check an address against an invite list, and no
 * way to hold a company to two attendees. Both rules are enforced in
 * \Theme\Summit\Gate, server-side. Nothing about the invite list reaches this
 * template or the browser.
 */
function filter_args(array $args): ?array
{
    $args = array_merge([
        'classes' => [],
        'audience' => '',
        'heading' => '',
        'description' => '',
        'intro' => '',
        'date_note' => '',
        'consent_text' => '',
        'privacy_url' => '',
        'success_heading' => '',
        'success_text' => '',
        'submit_label' => '',
    ], $args);

    $args['classes'] = array_merge([
        'summit-form',
        'wp-block',
    ], $args['classes']);

    // Anchor so a "Register" button anywhere on the page can jump to the form.
    if (empty($args['attributes']['id'])) {
        $args['attributes']['id'] = 'summit-registration';
    }

    // Defaults mirror the wording of the HubSpot form this replaces, so the
    // page reads the same if the editor leaves the fields blank.
    if ($args['intro'] === '') {
        $args['intro'] = __(
            'You have received a personal invitation to The Millboard Summit. Please do not '
            . 'forward this registration link to others; spaces are strictly limited, and we '
            . 'can only accommodate a maximum of two attendees per company. Thank you for your '
            . 'cooperation and understanding.',
            'granola'
        );
    }

    if ($args['date_note'] === '') {
        $args['date_note'] = __(
            'N.B. The Summit is a one-day event running on two dates in November, and guests '
            . 'are invited to join us for one. Places are limited, so to help us plan, please '
            . 'let us know your preferred date. We will do our best to accommodate your choice '
            . 'and will confirm in due course.',
            'granola'
        );
    }

    if ($args['consent_text'] === '') {
        $args['consent_text'] = __(
            'We process your data under legitimate interest to provide relevant content and '
            . 'communications in line with our privacy policy. We will continue to send you '
            . 'communications relating to this event; if you\'d prefer not to receive other '
            . 'marketing communications, please tick the box.',
            'granola'
        );
    }

    if ($args['submit_label'] === '') {
        $args['submit_label'] = __('Register', 'granola');
    }

    if ($args['success_heading'] === '') {
        $args['success_heading'] = __('Thank you, your place is registered.', 'granola');
    }

    if ($args['success_text'] === '') {
        $args['success_text'] = __(
            'We will confirm your date in due course.',
            'granola'
        );
    }

    // Which audience this page serves decides both the questions asked and the
    // days the registration consumes, so it has to be set. There is
    // deliberately no default: guessing UK on a US page would book a US guest
    // one day instead of three and quietly under-report the seat counts.
    $args['audience'] = strtoupper(trim((string) $args['audience']));
    $args['has_audience'] = Audiences::is_valid($args['audience']);

    if (!$args['has_audience']) {
        // Show the editor what is wrong; render nothing at all on the front end
        // rather than a form that would refuse every submission.
        return empty($args['is_preview']) ? null : $args;
    }

    // Every option is read from the same constants the validator checks
    // against, so the form can never offer a value the server would reject.
    $args['asks'] = [];
    foreach (['preferred_date', 'workshops', 'factory_tour', 'opt_out',
              'attending', 'email_contact', 'phone_contact'] as $field) {
        $args['asks'][$field] = Audiences::asks_for($args['audience'], $field);
    }

    $args['dates'] = Audiences::UK_CHOOSABLE_DAYS;
    $args['workshops'] = Gate::ALLOWED_WORKSHOPS;
    $args['fr_attending_yes'] = Gate::FR_ATTENDING_YES;
    $args['fr_attending_no'] = Gate::FR_ATTENDING_NO;

    // The days this audience is assigned, so the page can tell an INT, FR or US
    // guest which dates they are booked for. They are not asked to choose.
    $args['assigned_days'] = Audiences::chooses_day($args['audience'])
        ? []
        : Audiences::days_for($args['audience']);

    return $args;
}

/**
 * Passes the gate's endpoints and a REST nonce to the front end.
 *
 * @param  array<string,mixed> $localizations
 * @return array<string,mixed>
 */
function add_endpoint_localization($localizations): array
{
    $localizations['summit_check_endpoint'] = \rest_url(Gate::NAMESPACE . '/summit/check');
    $localizations['summit_register_endpoint'] = \rest_url(Gate::NAMESPACE . '/summit/register');
    $localizations['summit_nonce'] = \wp_create_nonce('wp_rest');

    return $localizations;
}
