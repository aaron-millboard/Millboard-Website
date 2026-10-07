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
