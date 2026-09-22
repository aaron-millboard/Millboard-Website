<?php

namespace Granola\Components\SummitForm;

use Theme\Summit\Audiences;
use Theme\Summit\Gate;
use Theme\Summit\Strings;

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

    if ($args['intro'] === '') {
        $args['intro'] = Strings::t('intro', $args['audience'], __(
            'You have received a personal invitation to The Millboard Summit. Please do not '
            . 'forward this registration link to others; spaces are strictly limited, and we '
            . 'can only accommodate a maximum of two attendees per company. Thank you for your '
            . 'cooperation and understanding.',
            'granola'
        ));
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
        $args['consent_text'] = Strings::t('consent', $args['audience'], __(
            'We process your data under legitimate interest to provide relevant content and '
            . 'communications in line with our privacy policy. We will continue to send you '
            . 'communications relating to this event; if you\'d prefer not to receive other '
            . 'marketing communications, please tick the box.',
            'granola'
        ));
    }

    if ($args['submit_label'] === '') {
        $args['submit_label'] = Strings::t('submit', $args['audience'],
            __('Register', 'granola'));
    }

    if ($args['success_heading'] === '') {
        $args['success_heading'] = Strings::t('success_heading', $args['audience'],
            __('Thank you, your place is registered.', 'granola'));
    }

    if ($args['success_text'] === '') {
        $args['success_text'] = Strings::t('success_text', $args['audience'], __(
            'We will confirm your date in due course.',
            'granola'
        ));
    }

    // Every option is read from the same constants the validator checks
    // against, so the form can never offer a value the server would reject.
    $args['asks'] = [];
    foreach (['preferred_date', 'workshops', 'factory_tour', 'opt_out',
              'attending', 'email_contact', 'phone_contact'] as $field) {
        $args['asks'][$field] = Audiences::asks_for($args['audience'], $field);
    }

    // Every visible string, already in the right language for this audience.
    // English is untouched: Strings::t falls straight through to the default
    // unless the audience is FR, so the UK, INT and US pages cannot be altered
    // by anything in here.
    $a = $args['audience'];
    $args['t'] = [
        'first_name' => Strings::t('first_name', $a, __('First name', 'granola')),
        'last_name' => Strings::t('last_name', $a, __('Last name', 'granola')),
        'email' => Strings::t('email', $a, __('Business Email', 'granola')),
        'company' => Strings::t('company', $a, __('Company name', 'granola')),
        'email_hint' => Strings::t('email_hint', $a,
            __('Please use the address your invitation was sent to.', 'granola')),
        'opt_out' => Strings::t('opt_out', $a, __(
            'I\'d like to not to receive other marketing communications from Millboard.',
            'granola'
        )),
        /* translators: %s is a list of dates. */
        'assigned_days' => Strings::t('assigned_days', $a,
            __('Your invitation covers %s.', 'granola')),
        'noscript' => Strings::t('noscript', $a, __(
            'This registration form needs JavaScript enabled. Please turn it on and reload the page, or reply to your invitation and we will register you.',
            'granola'
        )),
        // The script renders these three itself, so unlike every other message
        // the visitor sees they cannot come back translated from the gate.
        'js_busy' => Strings::t('js_busy', $a, __('Please wait…', 'granola')),
        'js_unavailable' => Strings::t('js_unavailable', $a, __(
            'Registration is unavailable right now. Please reply to your invitation and we will register you.',
            'granola'
        )),
        'js_failed' => Strings::t('js_failed', $a, __(
            'Something went wrong sending your registration. Please try again, or reply to your invitation.',
            'granola'
        )),
    ];

    // France chooses its company from a list instead of typing one, so the
    // per-company cap binds to a key we control rather than to free text.
    $args['company_options'] = Audiences::company_options($args['audience']);
    $args['select_prompt'] = Strings::t('select_prompt', $args['audience'],
        __('Please Select', 'granola'));

    $args['dates'] = Audiences::UK_CHOOSABLE_DAYS;
    $args['workshops'] = Gate::ALLOWED_WORKSHOPS;
    $args['fr_attending_yes'] = Gate::FR_ATTENDING_YES;
    $args['fr_attending_no'] = Gate::FR_ATTENDING_NO;

    // The days this audience is assigned, so the page can tell an INT, FR or US
    // guest which dates they are booked for. They are not asked to choose.
    $args['assigned_days'] = Audiences::chooses_day($args['audience'])
        ? []
        : Audiences::days_for($args['audience']);

    // The list as the sentence around it reads it. English keeps wp_sprintf's
    // %l, which joins with the locale's own "and"; French cannot use it,
    // because the day labels are storage keys and so are English whatever the
    // locale says.
    $args['assigned_days_text'] = $args['audience'] === Audiences::FR
        ? Strings::fr_day_list($args['assigned_days'])
        : \wp_sprintf('%l', $args['assigned_days']);

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
