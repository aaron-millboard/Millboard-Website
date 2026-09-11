<?php

namespace Theme\Summit;

/**
 * The Summit admin: registrations, and the invite list.
 *
 * ⚠️ These pages appear on EVERY site, deliberately. The registration log lives
 * on the network's main site (blog 1), but blogs 1 and 3 both serve
 * /en-gb/ and `/en-gb/wp-admin/` resolves to blog 3, so anything gated on the
 * log blog is invisible to the person running the event. That is exactly what
 * happened when this screen first shipped. Every read and write here goes
 * through Registrations::on_log_blog(), so the pages are correct from any site
 * and there is no reason to restrict them.
 *
 * The built-in CPT list screen is switched off for the same reason: it can only
 * ever list the current blog's posts, so on blog 3 it would say "no
 * registrations" however many there are.
 *
 * Everything the event needs between now and November should be doable here, so
 * that granting one company an extra place, cancelling a booking or pulling the
 * export never needs a code change and a deploy.
 */
class Admin
{
    public const MENU = 'millboard-summit';
    public const PAGE_INVITES = 'millboard-summit-invites';
    private const PER_PAGE = 50;

    public static function init(): void
    {
        \add_action('admin_menu', [self::class, 'add_pages']);
        \add_action('admin_post_mb_summit_invite', [self::class, 'handle_invite']);
        \add_action('admin_post_mb_summit_registration', [self::class, 'handle_registration']);
        \add_action('admin_post_mb_summit_export', [self::class, 'handle_export']);
    }

    public static function add_pages(): void
    {
        \add_menu_page(
            'Millboard Summit',
            'Summit',
            'manage_options',
            self::MENU,
            [self::class, 'render_registrations'],
            'dashicons-tickets-alt',
            26
        );

        \add_submenu_page(self::MENU, 'Summit registrations', 'Registrations',
            'manage_options', self::MENU, [self::class, 'render_registrations']);

        \add_submenu_page(self::MENU, 'Summit invite list', 'Invite list',
            'manage_options', self::PAGE_INVITES, [self::class, 'render_invites']);
    }

    // ------------------------------------------------------------------ utils

    private static function guard(string $nonce): void
    {
        if (!\current_user_can('manage_options')) {
            \wp_die('You do not have permission to manage the Summit.');
        }

        \check_admin_referer($nonce);
    }

    private static function notify(array $result): void
    {
        \set_transient('mb_summit_notice_' . \get_current_user_id(), $result, 60);
    }

    private static function back(string $page): void
    {
        \wp_safe_redirect(\add_query_arg('page', $page, \admin_url('admin.php')));
        exit;
    }

    private static function notice(): void
    {
        $key = 'mb_summit_notice_' . \get_current_user_id();
        $notice = \get_transient($key);

        if (!is_array($notice)) {
            return;
        }

        \delete_transient($key);
        printf(
            '<div class="notice notice-%s is-dismissible"><p>%s</p></div>',
            $notice['ok'] ? 'success' : 'error',
            \esc_html($notice['message'])
        );
    }

    // --------------------------------------------------------------- handlers

    public static function handle_invite(): void
    {
        self::guard('mb_summit_invite');

        $action = \sanitize_text_field((string) ($_POST['mb_action'] ?? ''));

        if ($action === 'add') {
            $result = InviteList::add(
                (string) ($_POST['email'] ?? ''),
                \sanitize_text_field((string) ($_POST['name'] ?? '')),
                \sanitize_text_field((string) ($_POST['company'] ?? '')),
                \sanitize_text_field((string) ($_POST['audience'] ?? ''))
            );
        } elseif ($action === 'remove') {
            $result = InviteList::remove((string) ($_POST['email'] ?? ''));
        } elseif ($action === 'override') {
            $key = \sanitize_text_field((string) ($_POST['company_key'] ?? ''));
            $cap = (int) ($_POST['cap'] ?? 0);

            if ($key === '') {
                $result = ['ok' => false, 'message' => 'No company given.'];
            } else {
                InviteList::set_cap_override($key, $cap);
                $result = [
                    'ok' => true,
                    'message' => $cap > 0
                        ? sprintf('%s can now bring %d people.', $key, $cap)
                        : sprintf('%s is back on the standard cap of %d.', $key, InviteList::cap_per_company()),
                ];
            }
        } else {
            $result = ['ok' => false, 'message' => 'Unknown action.'];
        }

        self::notify($result);
        self::back(self::PAGE_INVITES);
    }

    public static function handle_registration(): void
    {
        self::guard('mb_summit_registration');

        $id = (int) ($_POST['registration_id'] ?? 0);
        self::notify($id > 0
            ? Registrations::delete($id)
            : ['ok' => false, 'message' => 'No registration given.']);

        self::back(self::MENU);
    }

    /** Streams the registrations as CSV, ready for the HubSpot import. */
    public static function handle_export(): void
    {
        self::guard('mb_summit_export');

        $rows = Registrations::export_rows();

        if (empty($rows)) {
            self::notify(['ok' => false, 'message' => 'There is nothing to export yet.']);
            self::back(self::MENU);
        }

        \nocache_headers();
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=summit-registrations-'
            . \gmdate('Y-m-d') . '.csv');

        $out = fopen('php://output', 'w');
        fputcsv($out, array_keys($rows[0]));

        foreach ($rows as $row) {
            fputcsv($out, $row);
        }

        fclose($out);
        exit;
    }

    // ------------------------------------------------------------ registrations

    public static function render_registrations(): void
    {
        if (!\current_user_can('manage_options')) {
            return;
        }

        $rows = Registrations::list_rows();
        $used = Registrations::counts_by_day();
        $day_cap = Audiences::cap_per_day();

        $search = isset($_GET['s']) ? strtolower(trim((string) $_GET['s'])) : '';

        if ($search !== '') {
            $rows = array_values(array_filter($rows, static function ($r) use ($search) {
                return strpos(strtolower($r['email'] . ' ' . $r['company'] . ' '
                    . $r['first_name'] . ' ' . $r['last_name']), $search) !== false;
            }));
        }

        $declined = count(array_filter($rows, static fn($r) => $r['status'] === Registrations::STATUS_DECLINED));

        $page = max(1, (int) ($_GET['paged'] ?? 1));
        $pages = max(1, (int) ceil(count($rows) / self::PER_PAGE));
        $page = min($page, $pages);
        $slice = array_slice($rows, ($page - 1) * self::PER_PAGE, self::PER_PAGE);
        ?>
        <div class="wrap">
            <h1>Summit registrations</h1>
            <?php self::notice(); ?>

            <h2>Seats</h2>
            <table class="wp-list-table widefat striped" style="max-width:680px">
                <thead><tr><th>Day</th><th>Used</th><th>Left</th><th>Who attends</th></tr></thead>
                <tbody>
                    <?php
                    $who = [
                        Audiences::DAY_3RD => 'INT, FR and US only',
                        Audiences::DAY_4TH => 'everyone',
                        Audiences::DAY_5TH => 'US and UK only',
                    ];
                    foreach ($used as $day => $count) { ?>
                        <tr>
                            <td><?= \esc_html($day); ?></td>
                            <td><?= (int) $count; ?></td>
                            <td>
                                <?= (int) max(0, $day_cap - $count); ?>
                                <?php if ($count >= $day_cap) { ?><strong>(FULL)</strong><?php } ?>
                            </td>
                            <td><?= \esc_html($who[$day] ?? ''); ?></td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>

            <p>
                <?= (int) (count($rows) - $declined); ?> registered<?php
                if ($declined) { ?>, <?= (int) $declined; ?> declined<?php } ?>.
                Cap is <?= (int) $day_cap; ?> a day.
                Everyone on the 3rd is also on the 4th, so the 3rd's number is the
                combined INT, FR and US headcount, and every one of them takes a UK
                place off the 4th.
            </p>

            <form method="post" action="<?= \esc_url(\admin_url('admin-post.php')); ?>" style="display:inline">
                <?php \wp_nonce_field('mb_summit_export'); ?>
                <input type="hidden" name="action" value="mb_summit_export">
                <?php \submit_button('Download CSV for the HubSpot import', 'secondary', 'submit', false); ?>
            </form>

            <h2>Everyone registered</h2>
            <form method="get">
                <input type="hidden" name="page" value="<?= \esc_attr(self::MENU); ?>">
                <p class="search-box">
                    <label class="screen-reader-text" for="mb-reg-search">Search registrations</label>
                    <input type="search" id="mb-reg-search" name="s" value="<?= \esc_attr($search); ?>"
                           placeholder="name, email or company">
                    <?php \submit_button('Search', '', '', false); ?>
                </p>
            </form>

            <table class="wp-list-table widefat striped">
                <thead><tr>
                    <th>Registered</th><th>Name</th><th>Email</th><th>Company</th>
                    <th>Audience</th><th>Days</th><th>Status</th><th></th>
                </tr></thead>
                <tbody>
                <?php if (empty($slice)) { ?>
                    <tr><td colspan="8">Nobody yet.</td></tr>
                <?php } ?>
                <?php foreach ($slice as $r) { ?>
                    <tr>
                        <td><?= \esc_html($r['registered_at']); ?></td>
                        <td><?= \esc_html(trim($r['first_name'] . ' ' . $r['last_name'])); ?></td>
                        <td><code><?= \esc_html($r['email']); ?></code></td>
                        <td><?= \esc_html($r['company']); ?></td>
                        <td><?= \esc_html($r['audience']); ?></td>
                        <td><?= \esc_html(implode(', ', $r['days'])); ?></td>
                        <td>
                            <?= \esc_html($r['status']); ?>
                            <?php if ($r['source'] === Registrations::SOURCE_SEED) { ?>
                                <br><small>from HubSpot</small>
                            <?php } ?>
                        </td>
                        <td>
                            <form method="post" action="<?= \esc_url(\admin_url('admin-post.php')); ?>"
                                  onsubmit="return confirm('Delete the registration for <?= \esc_attr($r['email']); ?>? This frees their seats and their company place.');">
                                <?php \wp_nonce_field('mb_summit_registration'); ?>
                                <input type="hidden" name="action" value="mb_summit_registration">
                                <input type="hidden" name="registration_id" value="<?= (int) $r['id']; ?>">
                                <button type="submit" class="button-link delete">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php } ?>
                </tbody>
            </table>

            <?php self::pagination($page, $pages); ?>
        </div>
        <?php
    }

    // ------------------------------------------------------------- invite list

    public static function render_invites(): void
    {
        if (!\current_user_can('manage_options')) {
            return;
        }

        $list = InviteList::all();
        $overrides = InviteList::cap_overrides();
        $cap = InviteList::cap_per_company();
        $search = isset($_GET['s']) ? strtolower(trim((string) $_GET['s'])) : '';

        $rows = [];
        foreach ($list as $email => $record) {
            $haystack = strtolower($email . ' ' . ($record['company'] ?? '') . ' ' . ($record['name'] ?? ''));
            if ($search === '' || strpos($haystack, $search) !== false) {
                $rows[$email] = $record;
            }
        }
        ksort($rows);

        $used = [];
        foreach ($rows as $record) {
            $key = (string) ($record['company_key'] ?? '');
            if ($key !== '' && !isset($used[$key])) {
                $used[$key] = Registrations::count_for_company($key);
            }
        }

        $page = max(1, (int) ($_GET['paged'] ?? 1));
        $pages = max(1, (int) ceil(count($rows) / self::PER_PAGE));
        $page = min($page, $pages);
        $slice = array_slice($rows, ($page - 1) * self::PER_PAGE, self::PER_PAGE, true);
        ?>
        <div class="wrap">
            <h1>Summit invite list</h1>
            <?php self::notice(); ?>

            <p>
                Only these addresses can register. The check is on the exact address, so
                forwarding a registration link to somebody not listed here does not let them
                in, whether or not their company has places left.
            </p>

            <div class="notice notice-warning inline"><p>
                <strong>Adding here adds to the list.</strong> Re-importing the spreadsheet with
                <code>wp summit import-invites</code> REPLACES the whole list and would remove
                anyone added on this screen, so add them to the spreadsheet too if they are to
                survive a re-import.
            </p></div>

            <h2>Add someone</h2>
            <form method="post" action="<?= \esc_url(\admin_url('admin-post.php')); ?>">
                <?php \wp_nonce_field('mb_summit_invite'); ?>
                <input type="hidden" name="action" value="mb_summit_invite">
                <input type="hidden" name="mb_action" value="add">
                <table class="form-table" role="presentation">
                    <tr><th scope="row"><label for="mb-email">Email</label></th>
                        <td><input name="email" id="mb-email" type="email" class="regular-text" required>
                        <p class="description">The address they will type in. Case does not matter.</p></td></tr>
                    <tr><th scope="row"><label for="mb-name">Name</label></th>
                        <td><input name="name" id="mb-name" type="text" class="regular-text"></td></tr>
                    <tr><th scope="row"><label for="mb-company">Company</label></th>
                        <td><input name="company" id="mb-company" type="text" class="regular-text" required>
                        <p class="description">
                            The <?= (int) $cap; ?>-per-company limit counts against this. Spell it exactly
                            as it appears for their colleagues below, or they get their own
                            <?= (int) $cap; ?> places.
                        </p></td></tr>
                    <tr><th scope="row"><label for="mb-audience">Audience</label></th>
                        <td><select name="audience" id="mb-audience" required>
                            <option value="">Select</option>
                            <option value="UK">UK — picks one day, 4th or 5th</option>
                            <option value="INT">INT — 3rd and 4th</option>
                            <option value="US">US — 3rd, 4th and 5th</option>
                            <option value="FR">FR — 3rd and 4th</option>
                        </select>
                        <p class="description">Decides which page they can use and which days they take.</p></td></tr>
                </table>
                <?php \submit_button('Add to the invite list'); ?>
            </form>

            <h2>Give one company extra places</h2>
            <p>
                For a company that genuinely needs more than <?= (int) $cap; ?>. Copy its company key
                from the table below. Set 0 to put them back on the standard cap.
            </p>
            <form method="post" action="<?= \esc_url(\admin_url('admin-post.php')); ?>">
                <?php \wp_nonce_field('mb_summit_invite'); ?>
                <input type="hidden" name="action" value="mb_summit_invite">
                <input type="hidden" name="mb_action" value="override">
                <input name="company_key" type="text" class="regular-text" placeholder="company key, e.g. hythe landscapes" required>
                <input name="cap" type="number" min="0" max="20" value="3" style="width:5em">
                <?php \submit_button('Set', 'secondary', 'submit', false); ?>
            </form>

            <?php if ($overrides) { ?>
                <p><strong>Current exceptions:</strong>
                <?php foreach ($overrides as $key => $n) { ?>
                    <code><?= \esc_html($key); ?></code> = <?= (int) $n; ?>&nbsp;&nbsp;
                <?php } ?>
                </p>
            <?php } ?>

            <h2>Who is invited (<?= (int) count($list); ?> people)</h2>
            <form method="get">
                <input type="hidden" name="page" value="<?= \esc_attr(self::PAGE_INVITES); ?>">
                <p class="search-box">
                    <label class="screen-reader-text" for="mb-inv-search">Search the invite list</label>
                    <input type="search" id="mb-inv-search" name="s" value="<?= \esc_attr($search); ?>"
                           placeholder="email, name or company">
                    <?php \submit_button('Search', '', '', false); ?>
                </p>
            </form>

            <table class="wp-list-table widefat striped">
                <thead><tr>
                    <th>Email</th><th>Name</th><th>Company</th><th>Company key</th>
                    <th>Audience</th><th>Places used</th><th></th>
                </tr></thead>
                <tbody>
                <?php if (empty($slice)) { ?>
                    <tr><td colspan="7">Nobody matches.</td></tr>
                <?php } ?>
                <?php foreach ($slice as $email => $record) {
                    $key = (string) ($record['company_key'] ?? '');
                    $taken = $used[$key] ?? 0;
                    $their_cap = InviteList::cap_per_company($key);
                    ?>
                    <tr>
                        <td><code><?= \esc_html($email); ?></code></td>
                        <td><?= \esc_html((string) ($record['name'] ?? '')); ?></td>
                        <td><?= \esc_html((string) ($record['company'] ?? '')); ?></td>
                        <td><code><?= \esc_html($key); ?></code></td>
                        <td><?= \esc_html((string) ($record['audience'] ?? '')); ?></td>
                        <td>
                            <?= (int) $taken; ?> of <?= (int) $their_cap; ?>
                            <?php if (isset($overrides[$key])) { ?><br><small>exception</small><?php } ?>
                            <?php if ($taken >= $their_cap) { ?> <strong>(full)</strong><?php } ?>
                        </td>
                        <td>
                            <form method="post" action="<?= \esc_url(\admin_url('admin-post.php')); ?>"
                                  onsubmit="return confirm('Remove <?= \esc_attr($email); ?> from the invite list? Any registration they have already made will stand.');">
                                <?php \wp_nonce_field('mb_summit_invite'); ?>
                                <input type="hidden" name="action" value="mb_summit_invite">
                                <input type="hidden" name="mb_action" value="remove">
                                <input type="hidden" name="email" value="<?= \esc_attr($email); ?>">
                                <button type="submit" class="button-link delete">Remove</button>
                            </form>
                        </td>
                    </tr>
                <?php } ?>
                </tbody>
            </table>

            <?php self::pagination($page, $pages); ?>
        </div>
        <?php
    }

    private static function pagination(int $page, int $pages): void
    {
        if ($pages < 2) {
            return;
        }
        ?>
        <div class="tablenav"><div class="tablenav-pages">
            <?= \paginate_links([
                'base' => \add_query_arg('paged', '%#%'),
                'format' => '',
                'current' => $page,
                'total' => $pages,
            ]); ?>
        </div></div>
        <?php
    }
}
