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
 *
 * STAFF ONLY IS ALSO THE DEFAULT. Aaron, 7 Oct 2026: installers and
 * distributors stay on the old Craft portal until the Canto asset library is
 * finished, so phase one tells internal Millboard people and nobody else.
 * `--audience` exists to widen that, never to narrow it, and the safe value is
 * the one you get by typing nothing. Theme\Accounts\PortalImport gates the
 * import the same way and is the real control: a partner who was never
 * imported cannot be mailed by a slip here.
 *
 * USE --sleep ON THE REAL RUN. 1,235 messages pushed through the mail provider
 * in one unbroken loop is how a sending account gets rate limited or throttled,
 * and a run that stalls halfway leaves the cohort part-mailed.
 *
 * ⚠ In `## OPTIONS`, a wrapped description continues with INDENTATION, never
 * with another `: `. WP-CLI parses that block as the command synopsis and reads
 * a second colon-prefixed line as another parameter, which it then rejects:
 * --sleep first shipped printing "invalid synopsis part: part-mailed" on every
 * invocation, because the last word of a wrapped line was taken for an option.
 * Theme\Summit\Cli has it right and was the model.
 */
class Cli
{
    /**
     * `--audience` values, in the words someone would actually type.
     *
     * Phase one is `staff`. `all` is the eventual partner send and has to be
     * asked for by name.
     *
     * @var array<string,string[]|null>
     */
    private const AUDIENCES = [
        'staff' => [\Theme\Accounts\Roles::ROLE_STAFF],
        'partners' => [
            \Theme\Accounts\Roles::ROLE_DISTRIBUTOR,
            \Theme\Accounts\Roles::ROLE_INSTALLER,
            \Theme\Accounts\Roles::ROLE_ARCHITECT,
            \Theme\Accounts\Roles::ROLE_ASSET_VIEWER,
        ],
        'all' => null,
    ];

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
        \WP_CLI::log('');

        foreach (self::AUDIENCES as $name => $roles) {
            \WP_CLI::log(\sprintf(
                '  --audience=%-9s %s %d',
                $name,
                'staff' === $name ? 'WOULD SEND ' : 'would send ',
                \count(self::recipients('', false, $roles))
            ));
        }

        \WP_CLI::log('');
        \WP_CLI::log('  Phase one is staff. Installers and distributors stay on the old');
        \WP_CLI::log('  portal until the asset library is ready, so --audience=staff is');
        \WP_CLI::log('  the default and the other two have to be asked for by name.');
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
     * [--audience=<who>]
     * : Which group to send to. Defaults to staff, because installers and
     *   distributors stay on the old portal until the asset library is
     *   finished. One of staff, partners, all.
     * ---
     * default: staff
     * options:
     *   - staff
     *   - partners
     *   - all
     * ---
     *
     * [--force]
     * : Include people already sent to. Think hard before using this.
     *
     * [--sleep=<seconds>]
     * : Seconds to pause between sends, decimals allowed. Use it on the real
     *   run: 1,235 messages in one unbroken loop is how a sending account gets
     *   throttled. --sleep=0.5 puts the whole send at roughly ten minutes.
     *   Default 0.
     *
     * ## EXAMPLES
     *
     *     wp millboard portal-launch send --only=10 --live
     *     wp millboard portal-launch send --limit=25 --live
     *     wp millboard portal-launch send --live --sleep=0.5
     *     wp millboard portal-launch send --audience=all --live --sleep=0.5
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
        $sleep = (float) \WP_CLI\Utils\get_flag_value($assoc, 'sleep', 0);
        $audience = (string) \WP_CLI\Utils\get_flag_value($assoc, 'audience', 'staff');

        if (!\array_key_exists($audience, self::AUDIENCES)) {
            \WP_CLI::error(\sprintf('Unknown --audience=%s. One of: %s.', $audience, \implode(', ', \array_keys(self::AUDIENCES))));
        }

        $email = self::email();

        if (!$email) {
            \WP_CLI::error('The portal launch email is not registered.');
        }

        if ($live && !$email->is_enabled()) {
            \WP_CLI::error('The portal launch email is disabled in WooCommerce settings, so nothing would send.');
        }

        $users = self::recipients($only, $force, self::AUDIENCES[$audience]);

        if ($limit > 0) {
            $users = \array_slice($users, 0, $limit);
        }

        if (!$users) {
            \WP_CLI::success('Nobody to send to.');

            return;
        }

        if (!$live) {
            \WP_CLI::log(\sprintf('Audience: %s.', $audience));
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

        \WP_CLI::log(\sprintf('Sending to %d people (audience: %s) from %s', \count($users), $audience, \network_site_url()));

        if ($sleep > 0) {
            \WP_CLI::log(\sprintf(
                'Pausing %ss between sends, so expect this to take roughly %s.',
                $sleep,
                \human_time_diff(0, (int) \max(1, \round($sleep * \count($users))))
            ));
        }
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

            // After the send rather than before, and skipped on the last one,
            // so a run does not finish with a pointless wait.
            if ($sleep > 0 && $user !== \end($users)) {
                \usleep((int) \round($sleep * 1000000));
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
     * @param string[]|null $roles Null means every imported account.
     * @return \WP_User[]
     */
    private static function recipients(string $only, bool $force, ?array $roles = null): array
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

        $query = [
            'meta_query' => $meta,
            'number' => -1,
            'orderby' => 'ID',
        ];

        if (null !== $roles) {
            $query['role__in'] = $roles;
        }

        $users = \get_users($query);

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
        ];
    }
}
