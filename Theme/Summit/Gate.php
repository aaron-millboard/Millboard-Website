<?php

namespace Theme\Summit;

/**
 * Server-side gate for the Summit registration forms.
 *
 * Four rules, all enforced here and none of them in the browser:
 *
 *  1. The email must be on the invite allowlist. The Summit is invite only and
 *     the registration links are not meant to be forwarded.
 *  2. The invite's audience must match the page's audience, so a US invitee
 *     cannot register through the UK page and be given the wrong days.
 *  3. No more than two PEOPLE per company, whatever addresses they use.
 *  4. No more than 60 people on any one day, checked against EVERY day the
 *     registration would consume.
 *
 * Rule 4 is why this cannot be done in HubSpot: forms there have no inventory
 * and cannot refuse a submission. It is also why a refusal can look drastic.
 * INT, FR and US guests attend their days together and there is no partial
 * version of their invitation, so when the 3rd fills, those three audiences
 * stop being able to register at all rather than losing one day. See Audiences.
 *
 * Rules 1 and 2 live server-side for a reason that is easy to get wrong: the
 * allowlist is 197 partner names, companies and email addresses. Doing the
 * check in JavaScript would publish the lot in view-source. Nothing here ever
 * returns the list, another person's name, or which audience an address belongs
 * to.
 *
 * Every message a visitor can see goes through Strings::t(), which returns the
 * French wording for the FR audience and the English default for everyone else.
 * That is why the FR page does not depend on a Loco translation existing: it
 * did not, and French partners were being shown English refusals.
 */
class Gate
{
    public const NAMESPACE = 'millboard/v1';

    /** The only workshops on offer, asked of UK guests only. */
    public const ALLOWED_WORKSHOPS = [
        'Partner Portal',
        'Configurator',
        'AI Visualiser',
        'Decking & Cladding Calculator',
    ];

    /** FR attendance answers, matching the will_you_be_there property exactly. */
    public const FR_ATTENDING_YES = 'Oui, je serai présent(e)';
    public const FR_ATTENDING_NO = 'Non, je ne pourrai pas venir';

    /**
     * Requests per IP per window, per endpoint.
     *
     * ⚠️ The limit is keyed on IP, and the audience for this form is corporate
     * offices where colleagues share one public address. We invite TWO people
     * per company and expect both to register, so two people at Lawsons or
     * Cladco look like a single IP. Each of them typing an address, correcting
     * a typo and submitting is four or five requests, so a limit of 12 turned
     * the third person away with "too many attempts" — a genuine invitee
     * refused by a bot guard. That happened during testing on 10 Sep 2026.
     *
     * The check endpoint is advisory and cheap, so it gets the looser limit.
     * Register stays tighter because it writes.
     *
     * Enumeration is still not worth attempting: 40 per ten minutes is 240 an
     * hour against a 197-row list, and the prize is merely learning which of
     * our partners were invited.
     */
    private const RATE_LIMITS = [
        'check' => 40,
        'register' => 20,
    ];

    private const RATE_WINDOW = 600;

    /** One global lock, so no two registrations can take the same last seat. */
    private const LOCK_KEY = 'mb_summit_registration_lock';
    private const LOCK_SECONDS = 15;

    public static function init(): void
    {
        \add_action('rest_api_init', [self::class, 'register_routes']);
    }

    public static function register_routes(): void
    {
        // Lets the form tell someone early that they are not invited, that
        // their company is full, or that the days they need are gone. Advisory
        // only: register re-runs every check.
        \register_rest_route(self::NAMESPACE, '/summit/check', [
            'methods' => 'POST',
            'callback' => [self::class, 'handle_check'],
            'permission_callback' => '__return_true',
        ]);

        \register_rest_route(self::NAMESPACE, '/summit/register', [
            'methods' => 'POST',
            'callback' => [self::class, 'handle_register'],
            'permission_callback' => '__return_true',
        ]);
    }

    /**
     * Crude per-IP rate limit.
     *
     * The check endpoint would otherwise be an oracle for testing whether any
     * address is on the invite list, which is a slow way to enumerate our
     * partner list. This does not make that impossible, it makes it not worth
     * doing against a 197-row list.
     */
    private static function rate_limited(string $bucket): bool
    {
        $ip = isset($_SERVER['REMOTE_ADDR']) ? (string) $_SERVER['REMOTE_ADDR'] : '';

        if ($ip === '') {
            return false;
        }

        // Filterable so the limit can be loosened during the event without a
        // deploy, if a big partner turns out to sit behind one address.
        $limit = (int) \apply_filters(
            'millboard/summit/rate_limit',
            self::RATE_LIMITS[$bucket] ?? 20,
            $bucket
        );

        $key = 'mb_summit_rl_' . $bucket . '_' . md5($ip);
        $hits = (int) \get_transient($key);

        if ($hits >= $limit) {
            return true;
        }

        \set_transient($key, $hits + 1, self::RATE_WINDOW);

        return false;
    }

    /**
     * Runs every rule for one address on one page.
     *
     * @param  string $email      The submitted address.
     * @param  string $audience   The audience of the PAGE the form sits on.
     * @param  string $chosen_day A UK guest's chosen day; ignored otherwise.
     * @param  bool   $declining  FR only: they answered that they cannot come.
     * @return array{ok:bool,reason:string,message:string,invite:?array,days:array}
     */
    private static function evaluate(
        string $email,
        string $audience,
        string $chosen_day = '',
        bool $declining = false
    ): array {
        $fail = static function (string $reason, string $message, ?array $invite = null): array {
            return [
                'ok' => false,
                'reason' => $reason,
                'message' => $message,
                'invite' => $invite,
                'days' => [],
            ];
        };

        if (!Audiences::is_valid($audience)) {
            return $fail('bad_audience', Strings::t('misconfigured', $audience, __(
                'This registration form is not set up correctly. Please reply to your invitation and we will register you.',
                'granola'
            )));
        }

        $invite = InviteList::find($email);

        if ($invite === null) {
            // Deliberately says nothing about whether the address exists
            // elsewhere, and never names anyone.
            return $fail('not_invited', Strings::t('not_invited', $audience, __(
                'We cannot match that email address to an invitation. The Summit is invite only, so please use the address your invitation was sent to. If you think this is wrong, reply to your invitation and we will sort it out.',
                'granola'
            )));
        }

        // Rule 2. A US invitee arriving on the UK page would otherwise be given
        // a single UK day instead of all three, which would quietly under-book
        // them and misreport the seat counts.
        if (($invite['audience'] ?? '') !== $audience) {
            return $fail('wrong_page', Strings::t('wrong_page', $audience, __(
                'Your invitation is for a different session. Please use the registration link from your own invitation email, or reply to it and we will help.',
                'granola'
            )), $invite);
        }

        // An FR guest telling us they cannot come is recorded but consumes no
        // seats, so neither cap applies to them.
        if ($declining) {
            return [
                'ok' => true,
                'reason' => 'declined',
                'message' => '',
                'invite' => $invite,
                'days' => [],
            ];
        }

        // Rule 3. The cap is looked up per company, so an exception granted in
        // the admin is honoured here without a code change.
        $company_cap = InviteList::cap_per_company($invite['company_key']);
        $taken = Registrations::count_for_company($invite['company_key']);

        if ($taken >= $company_cap) {
            if ($audience === Audiences::FR) {
                return $fail('company_full', sprintf(
                    Strings::fr('company_full'),
                    $company_cap,
                    $invite['company'],
                    $taken
                ), $invite);
            }

            return $fail('company_full', sprintf(
                // Says how many, not who: a colleague's name is not ours to
                // hand out, and the count is enough to explain the refusal.
                _n(
                    'We can only take %1$d person per company, and %2$s already has %3$d registered. If you need to change who is coming, reply to your invitation and we will help.',
                    'We can only take %1$d people per company, and %2$s already has %3$d registered. If you need to change who is coming, reply to your invitation and we will help.',
                    $company_cap,
                    'granola'
                ),
                $company_cap,
                $invite['company'],
                $taken
            ), $invite);
        }

        $days = Audiences::days_for($audience, $chosen_day);

        if (empty($days)) {
            return $fail('no_day', __(
                'Please choose which date you would like to attend.',
                'granola'
            ), $invite);
        }

        // Rule 4. Every day this registration needs must have room, because
        // there is no partial version of an INT, FR or US invitation.
        $day_cap = Audiences::cap_per_day();

        foreach ($days as $day) {
            if (Registrations::count_for_day($day) < $day_cap) {
                continue;
            }

            $message = count($days) > 1
                ? sprintf(
                    Strings::t('day_full_multi', $audience, __(
                        'The %s is now full, and your invitation covers more than one day, so we cannot complete your registration. Please reply to your invitation and we will see what we can do.',
                        'granola'
                    )),
                    $day
                )
                : sprintf(
                    __(
                        'The %s is now fully booked. Please choose the other date, or reply to your invitation and we will help.',
                        'granola'
                    ),
                    $day
                );

            return $fail('day_full', $message, $invite);
        }

        return [
            'ok' => true,
            'reason' => 'ok',
            'message' => '',
            'invite' => $invite,
            'days' => $days,
        ];
    }

    /** Advisory pre-check as the visitor leaves the email field. */
    public static function handle_check(\WP_REST_Request $request)
    {
        // Read the audience first: every message below may need to come back in
        // French, and the rate-limit refusal is one a French visitor can
        // realistically hit.
        $audience = strtoupper(trim((string) $request->get_param('audience')));

        if (self::rate_limited('check')) {
            return new \WP_REST_Response([
                'ok' => false,
                'reason' => 'rate_limited',
                'message' => Strings::t('rate_limited', $audience, __(
                    'Too many attempts from your network. Please wait a few minutes and try again, or reply to your invitation and we will register you.',
                    'granola'
                )),
            ], 429);
        }

        $email = InviteList::normalise_email((string) $request->get_param('email'));

        if (!\is_email($email)) {
            return new \WP_REST_Response([
                'ok' => false,
                'reason' => 'invalid_email',
                'message' => Strings::t('err_email', $audience,
                    __('Please enter a valid email address.', 'granola')),
            ], 200);
        }

        // A UK guest has not picked a day yet at this point, so the day cap
        // cannot be judged. Skip it here and let submit decide; reporting "full"
        // against a day they have not chosen would be wrong.
        $result = self::evaluate($email, $audience, Audiences::DAY_4TH);

        // Never leak which audience an address belongs to. A mismatch is
        // reported as the generic wrong-link message and nothing more.
        return new \WP_REST_Response([
            'ok' => $result['ok'],
            'reason' => $result['reason'],
            'message' => $result['message'],
        ], 200);
    }

    /**
     * The real submission. Re-runs every rule; the pre-check is not trusted.
     */
    public static function handle_register(\WP_REST_Request $request)
    {
        // Read first, for the same reason as handle_check.
        $audience = strtoupper(trim((string) $request->get_param('audience')));

        if (self::rate_limited('register')) {
            return new \WP_REST_Response([
                'ok' => false,
                'reason' => 'rate_limited',
                'message' => Strings::t('rate_limited', $audience, __(
                    'Too many attempts from your network. Please wait a few minutes and try again, or reply to your invitation and we will register you.',
                    'granola'
                )),
            ], 429);
        }

        // Bots fill every field they can see, including one positioned off
        // screen. A hit is answered with a bland success so there is nothing to
        // tune against.
        if (trim((string) $request->get_param('company_website')) !== '') {
            return new \WP_REST_Response(['ok' => true, 'reason' => 'ok', 'message' => ''], 200);
        }

        if (!Audiences::is_valid($audience)) {
            return new \WP_REST_Response([
                'ok' => false,
                'reason' => 'bad_audience',
                'message' => Strings::t('misconfigured', $audience, __(
                    'This registration form is not set up correctly. Please reply to your invitation and we will register you.',
                    'granola'
                )),
            ], 400);
        }

        $email = InviteList::normalise_email((string) $request->get_param('email'));
        $first = \sanitize_text_field((string) $request->get_param('first_name'));
        $last = \sanitize_text_field((string) $request->get_param('last_name'));
        $company_typed = \sanitize_text_field((string) $request->get_param('company_typed'));

        $errors = [];

        if ($first === '') {
            $errors['first_name'] = Strings::t('err_first_name', $audience,
                __('Please enter your first name.', 'granola'));
        }
        if ($last === '') {
            $errors['last_name'] = Strings::t('err_last_name', $audience,
                __('Please enter your last name.', 'granola'));
        }
        if (!\is_email($email)) {
            $errors['email'] = Strings::t('err_email', $audience,
                __('Please enter a valid email address.', 'granola'));
        }
        if ($company_typed === '') {
            $errors['company_typed'] = Strings::t('err_company', $audience,
                __('Please enter your company name.', 'granola'));
        }

        // --- per-audience questions
        $chosen_day = '';
        $workshops = [];
        $tour = '';
        $attending = '';
        $email_contact = '';
        $phone_contact = '';
        $declining = false;

        if (Audiences::chooses_day($audience)) {
            $chosen_day = \sanitize_text_field((string) $request->get_param('preferred_date'));

            if (!in_array($chosen_day, Audiences::UK_CHOOSABLE_DAYS, true)) {
                $errors['preferred_date'] = __('Please choose which date you would like to attend.', 'granola');
            }
        }

        if (Audiences::asks_for($audience, 'workshops')) {
            $submitted = $request->get_param('workshops');
            $workshops = array_values(array_intersect(
                array_map('strval', is_array($submitted) ? $submitted : []),
                self::ALLOWED_WORKSHOPS
            ));

            if (empty($workshops)) {
                $errors['workshops'] = __('Please choose at least one workshop.', 'granola');
            }
        }

        if (Audiences::asks_for($audience, 'factory_tour')) {
            $tour = \sanitize_text_field((string) $request->get_param('factory_tour'));

            if (!in_array($tour, ['Yes', 'No'], true)) {
                $errors['factory_tour'] = __('Please let us know about the factory tour.', 'granola');
            }
        }

        if (Audiences::asks_for($audience, 'attending')) {
            $attending = \sanitize_text_field((string) $request->get_param('attending'));

            if (!in_array($attending, [self::FR_ATTENDING_YES, self::FR_ATTENDING_NO], true)) {
                $errors['attending'] = Strings::t('err_attending', $audience,
                    __('Please let us know whether you can attend.', 'granola'));
            }

            $declining = $attending === self::FR_ATTENDING_NO;
        }

        // The email/phone contact-consent radios were removed from the FR form
        // on 14 Sep 2026 and no other audience ever asked for them, so there is
        // nothing left to validate. The meta keys are still written as empty
        // strings so the export keeps a stable set of columns.

        if (!empty($errors)) {
            return new \WP_REST_Response([
                'ok' => false,
                'reason' => 'validation',
                'message' => Strings::t('err_check_fields', $audience,
                    __('Please check the highlighted fields.', 'granola')),
                'errors' => $errors,
            ], 200);
        }

        // Seats are counted then consumed, so without a lock two people
        // submitting in the same instant could both pass a check that leaves
        // only one place. One global lock rather than one per company, because
        // any two registrations compete for the same day capacity. At this
        // volume serialising registrations costs nothing.
        if (\get_transient(self::LOCK_KEY)) {
            return new \WP_REST_Response([
                'ok' => false,
                'reason' => 'busy',
                'message' => Strings::t('busy', $audience, __(
                    'Someone else is registering right now. Please try again in a moment.',
                    'granola'
                )),
            ], 200);
        }

        \set_transient(self::LOCK_KEY, 1, self::LOCK_SECONDS);

        try {
            $result = self::evaluate($email, $audience, $chosen_day, $declining);

            if (!$result['ok']) {
                return new \WP_REST_Response([
                    'ok' => false,
                    'reason' => $result['reason'],
                    'message' => $result['message'],
                ], 200);
            }

            $invite = $result['invite'];

            $post_id = Registrations::create([
                'audience' => $audience,
                'email' => $email,
                'first_name' => $first,
                'last_name' => $last,
                // The cap counts the invite list's company, never what the
                // visitor typed, so a full company cannot be dodged by typing a
                // different name.
                'company' => $invite['company'],
                'company_key' => $invite['company_key'],
                'company_typed' => $company_typed,
                'category' => $invite['category'],
                'invited_name' => $invite['name'],
                'days' => $result['days'],
                'preferred_date' => $chosen_day,
                'workshops' => $workshops,
                'factory_tour' => $tour,
                'attending' => $attending,
                'email_contact' => $email_contact,
                'phone_contact' => $phone_contact,
                'status' => $declining ? 'Declined' : 'Registered',
                'opt_out' => \rest_sanitize_boolean($request->get_param('opt_out_marketing')),
            ]);

            if (\is_wp_error($post_id)) {
                return new \WP_REST_Response([
                    'ok' => false,
                    'reason' => 'save_failed',
                    'message' => Strings::t('save_failed', $audience, __(
                        'Something went wrong saving your registration. Please try again, or reply to your invitation.',
                        'granola'
                    )),
                ], 500);
            }
        } finally {
            \delete_transient(self::LOCK_KEY);
        }

        return new \WP_REST_Response([
            'ok' => true,
            'reason' => $declining ? 'declined' : 'ok',
            'message' => $declining
                ? Strings::t('declined', $audience, __('Thank you for letting us know.', 'granola'))
                : Strings::t('success_heading', $audience,
                    __('Thank you, your place is registered.', 'granola')),
        ], 200);
    }
}
