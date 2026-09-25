<?php

namespace Theme\Accounts;

/**
 * Import the Craft portal's users as WordPress accounts.
 *
 * ⚠️ SILENT BY DESIGN. The portal is not released, so nobody may be emailed.
 * WordPress sends mail from several places on user creation, so every one is
 * shut off for the duration of the run and restored afterwards:
 *
 *   - `wp_new_user_notification` is replaced wholesale via a pluggable guard
 *   - `send_password_change_email` / `send_email_change_email` filtered false
 *   - `wp_mail` short-circuited through `pre_wp_mail`, so even a plugin
 *     hooking user creation cannot get a message out
 *
 * The last one is the belt to the others' braces: it does not trust this file
 * to know every sender. Nothing is sent, and the count of blocked attempts is
 * reported so a silent run can be proved rather than assumed.
 *
 * Accounts are created with a long random password nobody holds. There are no
 * password hashes in the export, so every user needs a reset before they can
 * log in — that reset is the launch communication, and it is a separate,
 * deliberate step that this importer does not perform.
 */
class PortalImport
{
    /** Craft `field_userType` => WordPress role. */
    public const TYPE_MAP = [
        'premierKeyDistributors' => Roles::ROLE_DISTRIBUTOR,
        'usDealers' => Roles::ROLE_DISTRIBUTOR,
        'export' => Roles::ROLE_DISTRIBUTOR,
        'approvedInstallers' => Roles::ROLE_INSTALLER,
        'architects' => Roles::ROLE_ARCHITECT,
        'imageLibraryAccess' => Roles::ROLE_ASSET_VIEWER,
        'employee' => Roles::ROLE_STAFF,
        'admin' => Roles::ROLE_STAFF,
        'usAdmin' => Roles::ROLE_STAFF,
    ];

    /** Meta keys written on every imported account. */
    public const META_SOURCE = 'millboard_portal_id';
    public const META_TYPE = 'millboard_portal_type';
    public const META_COMPANY = 'millboard_company';
    public const META_DORMANT = 'millboard_portal_dormant';
    public const META_IMPORTED = 'millboard_portal_imported';

    /** @var int Messages blocked during a run. */
    private static int $blocked = 0;

    /**
     * @param array<int,array<string,mixed>> $records Decoded portal export.
     * @param bool $commit False (the default) changes nothing at all.
     * @return array<string,mixed> A report.
     */
    public static function run(array $records, bool $commit = false): array
    {
        $report = [
            'mode' => $commit ? 'COMMIT' : 'DRY RUN',
            'total' => \count($records),
            'created' => 0,
            'updated' => 0,
            'skipped' => [],
            'by_role' => [],
            'mail_blocked' => 0,
            'errors' => [],
        ];

        if ($commit) {
            self::silence();
        }

        foreach ($records as $r) {
            $email = \strtolower(\trim((string) ($r['email'] ?? '')));
            $type = (string) ($r['field_userType'] ?? '');

            if ('' === $email || !\is_email($email)) {
                $report['skipped']['no usable email'] = ($report['skipped']['no usable email'] ?? 0) + 1;
                continue;
            }

            if (!empty($r['suspended']) || !empty($r['archived']) || empty($r['enabled'])) {
                $report['skipped']['suspended or disabled in the portal'] = ($report['skipped']['suspended or disabled in the portal'] ?? 0) + 1;
                continue;
            }

            $role = self::TYPE_MAP[$type] ?? null;

            if (null === $role) {
                $report['skipped']['unmapped type: ' . $type] = ($report['skipped']['unmapped type: ' . $type] ?? 0) + 1;
                continue;
            }

            $existing = \get_user_by('email', $email);

            // Never touch an administrator. If somebody here is already an
            // admin of this site, the portal is not the authority on that.
            if ($existing && \user_can($existing->ID, 'manage_options')) {
                $report['skipped']['already an administrator here'] = ($report['skipped']['already an administrator here'] ?? 0) + 1;
                continue;
            }

            $report['by_role'][$role] = ($report['by_role'][$role] ?? 0) + 1;

            if ($existing) {
                $report['updated']++;
            } else {
                $report['created']++;
            }

            if (!$commit) {
                continue;
            }

            $result = $existing
                ? self::upgrade($existing, $role, $r)
                : self::create($email, $role, $r);

            if (\is_wp_error($result)) {
                $report['errors'][] = $email . ': ' . $result->get_error_message();
            }
        }

        if ($commit) {
            $report['mail_blocked'] = self::$blocked;
            self::unsilence();
        }

        return $report;
    }

    /**
     * An existing account keeps its orders, its addresses and its password.
     * Only the role and the portal metadata are applied.
     */
    private static function upgrade(\WP_User $user, string $role, array $r)
    {
        $user->set_role($role);
        self::write_meta($user->ID, $r);

        return $user->ID;
    }

    private static function create(string $email, string $role, array $r)
    {
        $login = self::unique_login($email);

        $id = \wp_insert_user([
            'user_login' => $login,
            'user_email' => $email,
            // Nobody holds this. The export carries no hashes, so every
            // account needs a reset before it can be used.
            'user_pass' => \wp_generate_password(40, true, true),
            'first_name' => (string) ($r['firstName'] ?? ''),
            'last_name' => (string) ($r['lastName'] ?? ''),
            'display_name' => \trim(($r['firstName'] ?? '') . ' ' . ($r['lastName'] ?? '')) ?: $email,
            'role' => $role,
        ]);

        if (\is_wp_error($id)) {
            return $id;
        }

        self::write_meta($id, $r);

        return $id;
    }

    private static function write_meta(int $user_id, array $r): void
    {
        \update_user_meta($user_id, self::META_SOURCE, (int) ($r['id'] ?? 0));
        \update_user_meta($user_id, self::META_TYPE, (string) ($r['field_userType'] ?? ''));
        \update_user_meta($user_id, self::META_IMPORTED, \current_time('mysql'));

        $company = \trim((string) ($r['field_companyName'] ?? ''));

        if ('' !== $company) {
            \update_user_meta($user_id, self::META_COMPANY, $company);

            // So checkout and the account prefill with something useful.
            if (!\get_user_meta($user_id, 'billing_company', true)) {
                \update_user_meta($user_id, 'billing_company', $company);
            }
        }

        // Flagged rather than filtered out, so a launch mailing can choose to
        // skip the people who never used the old portal.
        $dormant = empty($r['lastLoginDate']) || !empty($r['pending']);
        \update_user_meta($user_id, self::META_DORMANT, $dormant ? 'yes' : 'no');

        $phone = \trim((string) ($r['field_telephoneNumber'] ?? ''));

        if ('' !== $phone && !\get_user_meta($user_id, 'billing_phone', true)) {
            \update_user_meta($user_id, 'billing_phone', $phone);
        }
    }

    /**
     * A login derived from the email, made unique without ever colliding.
     */
    private static function unique_login(string $email): string
    {
        $base = \sanitize_user(\current(\explode('@', $email)), true);
        $base = $base !== '' ? $base : 'partner';
        $login = $base;
        $n = 1;

        while (\username_exists($login)) {
            $login = $base . '-' . (++$n);
        }

        return $login;
    }

    // ---------------------------------------------------------------- mail

    public static function silence(): void
    {
        self::$blocked = 0;

        \add_filter('send_password_change_email', '__return_false', 99);
        \add_filter('send_email_change_email', '__return_false', 99);
        \add_filter('wp_send_new_user_notification_to_user', '__return_false', 99);
        \add_filter('wp_send_new_user_notification_to_admin', '__return_false', 99);
        \add_filter('woocommerce_email_enabled_customer_new_account', '__return_false', 99);

        // The catch-all: nothing leaves, whoever tried to send it.
        \add_filter('pre_wp_mail', [__CLASS__, 'block_mail'], 1);
    }

    public static function unsilence(): void
    {
        \remove_filter('send_password_change_email', '__return_false', 99);
        \remove_filter('send_email_change_email', '__return_false', 99);
        \remove_filter('wp_send_new_user_notification_to_user', '__return_false', 99);
        \remove_filter('wp_send_new_user_notification_to_admin', '__return_false', 99);
        \remove_filter('woocommerce_email_enabled_customer_new_account', '__return_false', 99);
        \remove_filter('pre_wp_mail', [__CLASS__, 'block_mail'], 1);
    }

    /**
     * Short-circuit wp_mail(). Returning a non-null value stops it dead.
     *
     * @param null|bool $short
     * @return bool
     */
    public static function block_mail($short)
    {
        self::$blocked++;

        return true; // Reported as sent, actually discarded.
    }

    public static function blocked(): int
    {
        return self::$blocked;
    }
}
