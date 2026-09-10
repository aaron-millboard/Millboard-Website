<?php

namespace Theme\Summit;

/**
 * Server-side gate for the Summit registration form.
 *
 * Two rules, both enforced here and never in the browser:
 *
 *  1. The email address must be on the invite allowlist. The Summit is invite
 *     only and the registration link is not supposed to be forwarded.
 *  2. No more than two PEOPLE per company, whatever addresses they use. So one
 *     person cannot book four colleagues on their own address, and once two
 *     people from a company are in, a third invited colleague is refused.
 *
 * Both checks live server-side for a reason that is easy to get wrong: the
 * allowlist is 152 partner names, companies and email addresses. Shipping it to
 * the browser to do the check in JavaScript would publish the lot in
 * view-source. Nothing here ever returns the list, or any other person's name.
 *
 * The cap is counted against the WordPress registration log, which is the only
 * record of attendance until the post-close HubSpot import. See Registrations.
 */
class Gate
{
    public const NAMESPACE = 'millboard/v1';

    /** The only dates the UK form offers. Never trust the posted value. */
    public const ALLOWED_DATES = ['4th November', '5th November'];

    /** The only workshops on offer. */
    public const ALLOWED_WORKSHOPS = [
        'Partner Portal',
        'Configurator',
        'AI Visualiser',
        'Decking & Cladding Calculator',
    ];

    /** Max requests per IP per window, per endpoint. */
    private const RATE_LIMIT = 12;
    private const RATE_WINDOW = 600;

    public static function init(): void
    {
        \add_action('rest_api_init', [self::class, 'register_routes']);
    }

    public static function register_routes(): void
    {
        // Lets the form tell someone their address is not invited, or their
        // company is full, before they fill the rest of it in. Advisory only:
        // register re-runs every check.
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
     * given address is on the invite list, which is a slow way to enumerate our
     * partner list. This does not make that impossible, it makes it not worth
     * doing for a 152-row list.
     */
    private static function rate_limited(string $bucket): bool
    {
        $ip = isset($_SERVER['REMOTE_ADDR']) ? (string) $_SERVER['REMOTE_ADDR'] : '';

        if ($ip === '') {
            return false;
        }

        $key = 'mb_summit_rl_' . $bucket . '_' . md5($ip);
        $hits = (int) \get_transient($key);

        if ($hits >= self::RATE_LIMIT) {
            return true;
        }

        \set_transient($key, $hits + 1, self::RATE_WINDOW);

        return false;
    }

    /**
     * Runs both rules for one address.
     *
     * @return array{ok:bool,reason:string,message:string,invite:?array}
     */
    private static function evaluate(string $email): array
    {
        $invite = InviteList::find($email);

        if ($invite === null) {
            return [
                'ok' => false,
                'reason' => 'not_invited',
                // Deliberately does not say whether the address exists anywhere
                // else, and never names anyone.
                'message' => 'We cannot match that email address to an invitation. '
                    . 'The Summit is invite only, so please use the address your '
                    . 'invitation was sent to. If you think this is wrong, reply to '
                    . 'your invitation and we will sort it out.',
                'invite' => null,
            ];
        }

        $cap = InviteList::cap_per_company();
        $taken = Registrations::count_for_company($invite['company_key']);

        if ($taken >= $cap) {
            return [
                'ok' => false,
                'reason' => 'company_full',
                // Says how many, not who. A colleague's name is not ours to
                // hand out, and the count is enough to explain the refusal.
                'message' => sprintf(
                    'We can only take %d %s per company, and %s already has %d registered. '
                    . 'If you need to change who is coming, reply to your invitation and we '
                    . 'will help.',
                    $cap,
                    $cap === 1 ? 'person' : 'people',
                    $invite['company'],
                    $taken
                ),
                'invite' => $invite,
            ];
        }

        return [
            'ok' => true,
            'reason' => 'ok',
            'message' => '',
            'invite' => $invite,
            'places_left' => $cap - $taken,
        ];
    }

    /** Advisory pre-check as the visitor leaves the email field. */
    public static function handle_check(\WP_REST_Request $request)
    {
        if (self::rate_limited('check')) {
            return new \WP_REST_Response([
                'ok' => false,
                'reason' => 'rate_limited',
                'message' => 'Too many attempts. Please wait a few minutes and try again.',
            ], 429);
        }

        $email = InviteList::normalise_email((string) $request->get_param('email'));

        if (!\is_email($email)) {
            return new \WP_REST_Response([
                'ok' => false,
                'reason' => 'invalid_email',
                'message' => 'Please enter a valid email address.',
            ], 200);
        }

        $result = self::evaluate($email);

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
        if (self::rate_limited('register')) {
            return new \WP_REST_Response([
                'ok' => false,
                'reason' => 'rate_limited',
                'message' => 'Too many attempts. Please wait a few minutes and try again.',
            ], 429);
        }

        // Bots fill every field they can see, including one positioned off
        // screen. A hit here is answered with a cheerful 200 so the bot has
        // nothing to tune against.
        if (trim((string) $request->get_param('company_website')) !== '') {
            return new \WP_REST_Response(['ok' => true, 'reason' => 'ok', 'message' => ''], 200);
        }

        $email = InviteList::normalise_email((string) $request->get_param('email'));
        $first = \sanitize_text_field((string) $request->get_param('first_name'));
        $last = \sanitize_text_field((string) $request->get_param('last_name'));
        $company_typed = \sanitize_text_field((string) $request->get_param('company_typed'));
        $date = \sanitize_text_field((string) $request->get_param('preferred_date'));
        $tour = \sanitize_text_field((string) $request->get_param('factory_tour'));
        $workshops_in = $request->get_param('workshops');
        $workshops_in = is_array($workshops_in) ? $workshops_in : [];

        $errors = [];

        if ($first === '') {
            $errors['first_name'] = 'Please enter your first name.';
        }
        if ($last === '') {
            $errors['last_name'] = 'Please enter your last name.';
        }
        if (!\is_email($email)) {
            $errors['email'] = 'Please enter a valid email address.';
        }
        if ($company_typed === '') {
            $errors['company_typed'] = 'Please enter your company name.';
        }
        if (!in_array($date, self::ALLOWED_DATES, true)) {
            $errors['preferred_date'] = 'Please choose which date you would like to attend.';
        }
        if (!in_array($tour, ['Yes', 'No'], true)) {
            $errors['factory_tour'] = 'Please let us know about the factory tour.';
        }

        // Only known workshops, and at least one, matching the live form where
        // the question is required.
        $workshops = array_values(array_intersect(
            array_map('strval', $workshops_in),
            self::ALLOWED_WORKSHOPS
        ));

        if (empty($workshops)) {
            $errors['workshops'] = 'Please choose at least one workshop.';
        }

        if (!empty($errors)) {
            return new \WP_REST_Response([
                'ok' => false,
                'reason' => 'validation',
                'message' => 'Please check the highlighted fields.',
                'errors' => $errors,
            ], 200);
        }

        // One company's places are counted then consumed, so two people
        // submitting in the same instant could both pass the count and take a
        // third place. Unlikely across 152 invitees, cheap to rule out.
        $invite = InviteList::find($email);
        $lock_key = 'mb_summit_lock_' . md5((string) ($invite['company_key'] ?? $email));

        if (\get_transient($lock_key)) {
            return new \WP_REST_Response([
                'ok' => false,
                'reason' => 'busy',
                'message' => 'Someone from your company is registering right now. '
                    . 'Please try again in a moment.',
            ], 200);
        }

        \set_transient($lock_key, 1, 15);

        try {
            $result = self::evaluate($email);

            if (!$result['ok']) {
                return new \WP_REST_Response([
                    'ok' => false,
                    'reason' => $result['reason'],
                    'message' => $result['message'],
                ], 200);
            }

            $invite = $result['invite'];

            $post_id = Registrations::create([
                'email' => $email,
                'first_name' => $first,
                'last_name' => $last,
                // The cap is counted against the invite list's company, never
                // what the visitor typed, so nobody can dodge a full company by
                // typing a different name.
                'company' => $invite['company'],
                'company_key' => $invite['company_key'],
                'company_typed' => $company_typed,
                'category' => $invite['category'],
                'invited_name' => $invite['name'],
                'preferred_date' => $date,
                'workshops' => $workshops,
                'factory_tour' => $tour,
                'opt_out' => \rest_sanitize_boolean($request->get_param('opt_out_marketing')),
            ]);

            if (\is_wp_error($post_id)) {
                return new \WP_REST_Response([
                    'ok' => false,
                    'reason' => 'save_failed',
                    'message' => 'Something went wrong saving your registration. '
                        . 'Please try again, or reply to your invitation.',
                ], 500);
            }
        } finally {
            \delete_transient($lock_key);
        }

        return new \WP_REST_Response([
            'ok' => true,
            'reason' => 'ok',
            'message' => 'Thank you, your place is registered. We will confirm your date in due course.',
        ], 200);
    }
}
