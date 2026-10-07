<?php

namespace Theme\Emails;

/**
 * WP-CLI for the portal launch mailing.
 *
 * This is the only way the launch email is ever sent. Nothing hooks it to an
 * order, an import or a cron, because a thousand-recipient send should never be
 * something the site can do to itself by accident.
 *
 *   wp millboard portal-launch status
 *   wp millboard portal-launch send                 # dry run, sends nothing
 *   wp millboard portal-launch send --limit=5 --live
 *   wp millboard portal-launch send --live
 *
 * DRY RUN IS THE DEFAULT. `--live` is required to send anything, because the
 * cost of getting this wrong is 1,235 people receiving an email twice, or
 * receiving one that should not have gone at all.
 */
class Cli
{
    public static function init(): void
    {
        if (!\defined('WP_CLI') || !\WP_CLI) {
            return;
        }

        \WP_CLI::add_command('millboard portal-launch status', [self::class, 'status']);
        \WP_CLI::add_command('millboard portal-launch send', [self::class, 'send']);
    }

    /**
     * Who would be mailed, who has been already, and who is excluded.
     *
     * ## EXAMPLES
     *
     *     wp millboard portal-launch status
     */
    public static function status(): void
    {
        $counts = self::counts();

        \WP_CLI::log('');
        \WP_CLI::log(\sprintf('  imported portal users   %d', $counts['imported']));
        \WP_CLI::log(\sprintf('  already sent            %d', $counts['sent']));
        \WP_CLI::log(\sprintf('  disabled, will skip     %d', $counts['disabled']));
        \WP_CLI::log(\sprintf('  no email address        %d', $counts['no_email']));
        \WP_CLI::log(\sprintf('  WOULD SEND              %d', $counts['pending']));
        \WP_CLI::log('');
        \WP_CLI::log(\sprintf('  email enabled           %s', self::email() && self::email()->is_enabled() ? 'yes' : 'NO -- nothing will send'));
        \WP_CLI::log(\sprintf('  site                    %s', \network_site_url()));
        \WP_CLI::log('');
    }

    /**
     * Send the launch email.
     *
     * ## OPTIONS
     *
     * [--live]
     * : Actually send. Without this nothing is sent and nothing is recorded.
     *
     * [--limit=<number>]
     * : Stop after this many. Use a small number for the first run.
     *
     * [--only=<ids>]
     * : Comma-separated user ids, for sending to yourself before the real run.
     *
     * [--force]
     * : Include people already sent to. Think hard before using this.
     *
     * ## EXAMPLES
     *
     *     wp millboard portal-launch send --only=10 --live
     *     wp millboard portal-launch send --limit=25 --live
     *
     * @param array<int,string> $args
     * @param array<string,mixed> $assoc
     */
    public static function send(array $args, array $assoc): void
    {
        $live = (bool) \WP_CLI\Utils\get_flag_value($assoc, 'live', false);
        $force = (bool) \WP_CLI\Utils\get_flag_value($assoc, 'force', false);
        $limit = (int) \WP_CLI\Utils\get_flag_value($assoc, 'limit', 0);
        $only = (string) \WP_CLI\Utils\get_flag_value($assoc, 'only', '');

        $email = self::email();

        if (!$email) {
            \WP_CLI::error('The portal launch email is not registered.');
        }

        if ($live && !$email->is_enabled()) {
            \WP_CLI::error('The portal launch email is disabled in WooCommerce settings, so nothing would send.');
        }

        $users = self::recipients($only, $force);

        if ($limit > 0) {
            $users = \array_slice($users, 0, $limit);
        }

        if (!$users) {
            \WP_CLI::success('Nobody to send to.');

            return;
        }

        if (!$live) {
            \WP_CLI::log(\sprintf('DRY RUN. %d would be sent. Nothing has been sent and nothing recorded.', \count($users)));
            \WP_CLI::log('Add --live to send for real.');

            foreach (\array_slice($users, 0, 10) as $u) {
                \WP_CLI::log(\sprintf('  %-6d %-34s %s', $u->ID, $u->user_email, $u->user_login));
            }

            if (\count($users) > 10) {
                \WP_CLI::log(\sprintf('  ... and %d more', \count($users) - 10));
            }

            return;
        }

        \WP_CLI::log(\sprintf('Sending to %d people from %s', \count($users), \network_site_url()));
        $progress = \WP_CLI\Utils\make_progress_bar('Sending', \count($users));
        $sent = 0;
        $failed = 0;

        foreach ($users as $user) {
            $key = \get_password_reset_key($user);

            if (\is_wp_error($key)) {
                \WP_CLI::warning(\sprintf('%s: could not create a reset key: %s', $user->user_email, $key->get_error_message()));
                $failed++;
                $progress->tick();
                continue;
            }

            // Recorded BEFORE the send. A crash halfway through should leave
            // someone un-mailed rather than mailed twice, and the extended key
            // expiry keys off this meta so it has to exist when they click.
            \update_user_meta($user->ID, PortalLaunch::META_SENT, \gmdate('c'));

            if ($email->send_to($user, $key)) {
                $sent++;
            } else {
                $failed++;
                \delete_user_meta($user->ID, PortalLaunch::META_SENT);
                \WP_CLI::warning(\sprintf('%s: send failed', $user->user_email));
            }

            $progress->tick();
        }

        $progress->finish();
        \WP_CLI::success(\sprintf('Sent %d, failed %d.', $sent, $failed));
    }

    // ----------------------------------------------------------------- parts

    private static function email(): ?PortalLaunch
    {
        if (!\function_exists('WC')) {
            return null;
        }

        foreach (\WC()->mailer()->get_emails() as $mail) {
            if ($mail instanceof PortalLaunch) {
                return $mail;
            }
        }

        return null;
    }

    /**
     * @return \WP_User[]
     */
    private static function recipients(string $only, bool $force): array
    {
        if ('' !== $only) {
            $ids = \array_filter(\array_map('absint', \explode(',', $only)));
            $users = [];

            foreach ($ids as $id) {
                $user = \get_user_by('id', $id);

                if ($user instanceof \WP_User) {
                    $users[] = $user;
                }
            }

            return $users;
        }

        $meta = [
            [
                'key' => \Theme\Accounts\PortalImport::META_IMPORTED,
                'compare' => 'EXISTS',
            ],
            [
                'key' => 'millboard_portal_disabled',
                'compare' => 'NOT EXISTS',
            ],
        ];

        if (!$force) {
            $meta[] = [
                'key' => PortalLaunch::META_SENT,
                'compare' => 'NOT EXISTS',
            ];
        }

        $users = \get_users([
            'meta_query' => $meta,
            'number' => -1,
            'orderby' => 'ID',
        ]);

        return \array_values(\array_filter($users, static fn(\WP_User $u): bool => (bool) \is_email($u->user_email)));
    }

    /**
     * @return array<string,int>
     */
    private static function counts(): array
    {
        $imported = \count(\get_users([
            'meta_key' => \Theme\Accounts\PortalImport::META_IMPORTED,
            'meta_compare' => 'EXISTS',
            'number' => -1,
            'fields' => 'ID',
        ]));

        $sent = \count(\get_users([
            'meta_key' => PortalLaunch::META_SENT,
            'meta_compare' => 'EXISTS',
            'number' => -1,
            'fields' => 'ID',
        ]));

        $disabled = \count(\get_users([
            'meta_key' => 'millboard_portal_disabled',
            'meta_compare' => 'EXISTS',
            'number' => -1,
            'fields' => 'ID',
        ]));

        $pending = self::recipients('', false);

        $no_email = \count(\get_users([
            'meta_query' => [
                ['key' => \Theme\Accounts\PortalImport::META_IMPORTED, 'compare' => 'EXISTS'],
            ],
            'number' => -1,
        ])) - \count(\array_filter(\get_users([
            'meta_query' => [
                ['key' => \Theme\Accounts\PortalImport::META_IMPORTED, 'compare' => 'EXISTS'],
            ],
            'number' => -1,
        ]), static fn(\WP_User $u): bool => (bool) \is_email($u->user_email)));

        return [
            'imported' => $imported,
            'sent' => $sent,
            'disabled' => $disabled,
            'no_email' => $no_email,
            'pending' => \count($pending),
        ];
    }
}
