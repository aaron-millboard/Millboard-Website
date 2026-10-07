<?php

namespace Theme\Emails;

/**
 * The partner portal launch email.
 *
 * WHY THIS IS NOT THE PASSWORD RESET EMAIL.
 * The portal launch sends roughly 1,235 partners a message none of them asked
 * for. WooCommerce's reset email says "someone has requested a password reset",
 * which, arriving unprompted, reads exactly like phishing: the recipient did
 * not request anything, so the email is telling them something untrue and
 * inviting them to click a link about their password. The design handoff
 * raised this and the answer is a separate email that says what actually
 * happened -- an account has been created for you -- and gives the recipient a
 * way to check it is genuine.
 *
 * IT NEVER SENDS ITSELF. There is no trigger hooked to anything. The only way
 * to send it is `wp millboard portal-launch send --live`, which is deliberate:
 * nothing about an order, an import or a cron should ever be able to mail a
 * thousand people by accident. See Theme\Emails\Cli.
 *
 * KEY EXPIRY. WordPress reset keys last 24 hours by default, so a bulk send on
 * a Friday leaves most links dead before they are opened. Rather than weaken
 * every reset on the site, expiry is extended only for users this email was
 * actually sent to, resolved from the request. See extend_key_expiry().
 */
class PortalLaunch extends \WC_Email
{
    /** When this user was sent the launch email. Also what extends their key. */
    public const META_SENT = 'millboard_portal_launch_sent';

    /**
     * Held back from the launch mailing, whatever else is true of them.
     *
     * Phase one launches in the UK alone, and the staff import brought across
     * everyone on a Millboard domain, which includes the US, France, Germany
     * and the export team. Mailing them an account for something that has not
     * launched in their market is worse than not mailing them: they would set
     * a password, find sample ordering gated to the UK site, and ask why.
     *
     * UNCONDITIONAL. Neither --force nor --only reaches a held account,
     * because --force means "already sent to" and --only is a convenience, and
     * neither is a reason to mail somebody whose market is not open. Releasing
     * someone is deliberate: delete the flag, which is what the US, French and
     * German launches will each do for their own people.
     *
     * Aaron, 7 Oct 2026.
     */
    public const META_HOLD = 'millboard_portal_launch_hold';

    /** Set by the CLI immediately before send(). */
    public ?\WP_User $user = null;
    public string $reset_key = '';

    public function __construct()
    {
        $this->id = 'millboard_portal_launch';
        $this->title = \__('Partner portal launch', 'granola');
        $this->description = \__('Sent once to each partner when the portal opens, telling them an account has been created and inviting them to choose a password. Never sent automatically.', 'granola');
        $this->customer_email = true;
        $this->template_html = 'emails/customer-portal-launch.php';
        $this->template_plain = 'emails/plain/customer-portal-launch.php';
        $this->placeholders = [
            '{site_title}' => $this->get_blogname(),
        ];

        parent::__construct();

        // No trigger. This email is sent only by the CLI, on purpose.
        $this->manual = true;
    }

    public function get_default_subject(): string
    {
        return \__('Your Millboard partner portal account is ready', 'granola');
    }

    public function get_default_heading(): string
    {
        return \__('Your partner portal account is ready', 'granola');
    }

    /**
     * Is this an internal Millboard account?
     *
     * Phase one of the launch goes to staff alone, and what staff are being
     * told is narrower than the email's original promise: sample ordering is
     * open in My Account, the Canto asset library is not, and installers and
     * distributors keep using the old portal meanwhile. Saying "the new
     * partner portal is ready" to someone who then finds no assets in it is
     * the same category of fault as the reset email claiming they asked for
     * something. So the body says which half is ready. Aaron, 7 Oct 2026.
     *
     * Role, not email domain. PortalImport already settled who is staff, by
     * address, and reaching a different answer here would eventually disagree
     * with the limits and the POS catalogue, which both read the role.
     */
    public static function is_staff(?\WP_User $user): bool
    {
        return $user instanceof \WP_User
            && \in_array(\Theme\Accounts\Roles::ROLE_STAFF, (array) $user->roles, true);
    }

    /**
     * The name to greet this person by, or '' for a bare "Hi,".
     *
     * The Craft export is not tidy and the launch email is where that shows:
     * of the 128 staff, 14 have an all-lower-case first name and 3 have none
     * at all, so without this they would be greeted "Hi adrien," or "Hi,".
     * Both read as a mailshot, which is the one thing this email cannot afford
     * to look like when it is also asking the recipient to click a link about
     * their password.
     *
     * Fixed at render rather than in wp_users on purpose. It needs no data
     * step, no backup and no rollback, it is identical on staging and
     * production, and it leaves the imported record as the export gave it.
     *
     * ONLY AN ALL-LOWER-CASE NAME IS TOUCHED, so this corrects an import
     * artefact and never restyles a name somebody chose. "Jean-Pierre" and
     * "McDonald" pass through as they are.
     *
     * THE ADDRESS IS A FALLBACK FOR STAFF ALONE. Millboard addresses are
     * first.last, so andreea.ionel@ gives "Andreea". Partner addresses are
     * nothing of the kind -- info@, sales@, hola@ -- and greeting someone "Hi
     * Info," is worse than not greeting them by name, so for them an empty
     * first name stays empty.
     */
    public static function greeting_name(?\WP_User $user): string
    {
        if (!$user instanceof \WP_User) {
            return '';
        }

        $name = \trim($user->first_name);

        if ('' === $name && self::is_staff($user)) {
            $local = \strstr($user->user_email, '@', true);
            $first = \explode('.', (string) $local)[0];

            // Only a plain word. Not "r", not "a.ionel2", not "info".
            if (\preg_match('/^[a-z]{2,}$/i', $first) && 'info' !== \strtolower($first)) {
                $name = $first;
            }
        }

        if ($name === \mb_strtolower($name)) {
            $name = \mb_convert_case($name, MB_CASE_TITLE, 'UTF-8');
        }

        return (string) \apply_filters('millboard_portal_launch_greeting_name', $name, $user);
    }

    /**
     * Send to one user. Returns true only when WordPress accepted the message.
     */
    public function send_to(\WP_User $user, string $reset_key): bool
    {
        $this->user = $user;
        $this->reset_key = $reset_key;
        $this->recipient = $user->user_email;

        if (!$this->is_enabled() || !$this->get_recipient()) {
            return false;
        }

        return $this->send(
            $this->get_recipient(),
            $this->get_subject(),
            $this->get_content(),
            $this->get_headers(),
            $this->get_attachments()
        );
    }

    public function get_content_html(): string
    {
        return \wc_get_template_html(
            $this->template_html,
            [
                'user' => $this->user,
                'reset_key' => $this->reset_key,
                'email_heading' => $this->get_heading(),
                'additional_content' => $this->get_additional_content(),
                'sent_to_admin' => false,
                'plain_text' => false,
                'email' => $this,
            ]
        );
    }

    public function get_content_plain(): string
    {
        return \wc_get_template_html(
            $this->template_plain,
            [
                'user' => $this->user,
                'reset_key' => $this->reset_key,
                'email_heading' => $this->get_heading(),
                'additional_content' => $this->get_additional_content(),
                'sent_to_admin' => false,
                'plain_text' => true,
                'email' => $this,
            ]
        );
    }

    /**
     * The link the recipient clicks. The same shape WooCommerce's own reset
     * email uses, so it lands on the account page's reset form.
     */
    public static function reset_url(\WP_User $user, string $key): string
    {
        return \add_query_arg(
            [
                'key' => $key,
                'id' => $user->ID,
            ],
            \wc_get_endpoint_url('lost-password', '', \wc_get_page_permalink('myaccount'))
        );
    }

    // ------------------------------------------------------------ key expiry

    public static function init_expiry(): void
    {
        \add_filter('password_reset_expiration', [__CLASS__, 'extend_key_expiry']);
    }

    /**
     * Give launch recipients longer than 24 hours, and nobody else.
     *
     * WordPress offers no user context on this filter, so the user is resolved
     * from the request instead. When that cannot be done the default stands,
     * which is the safe direction: a reset the site cannot attribute keeps the
     * ordinary 24-hour window.
     *
     * @param mixed $seconds
     * @return mixed
     */
    public static function extend_key_expiry($seconds)
    {
        $user = self::user_in_request();

        if (!$user instanceof \WP_User) {
            return $seconds;
        }

        if (!\get_user_meta($user->ID, self::META_SENT, true)) {
            return $seconds;
        }

        /**
         * How long a launch recipient has to choose a password. Long enough to
         * survive a holiday, and it only applies to people who were sent the
         * launch email.
         */
        return (int) \apply_filters('millboard_portal_launch_key_expiry', 21 * DAY_IN_SECONDS);
    }

    private static function user_in_request(): ?\WP_User
    {
        // phpcs:disable WordPress.Security.NonceVerification.Recommended -- read-only identification, the key itself is the credential.
        $id = isset($_GET['id']) ? \absint($_GET['id']) : 0;

        if ($id) {
            $user = \get_user_by('id', $id);

            if ($user instanceof \WP_User) {
                return $user;
            }
        }

        $login = isset($_GET['login']) ? \sanitize_user(\wp_unslash($_GET['login'])) : '';
        // phpcs:enable

        // WooCommerce stashes "<id>:<key>" in a cookie across the redirect.
        if ('' === $login && isset($_COOKIE['wp-resetpass-' . COOKIEHASH])) {
            $parts = \explode(':', (string) \wp_unslash($_COOKIE['wp-resetpass-' . COOKIEHASH]), 2);
            $login = $parts[0] ?? '';
        }

        if ('' === $login) {
            return null;
        }

        $user = \is_numeric($login) ? \get_user_by('id', (int) $login) : \get_user_by('login', $login);

        return $user instanceof \WP_User ? $user : null;
    }
}
