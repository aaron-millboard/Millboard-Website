<?php

namespace Theme\Summit;

/**
 * "Summit invite list" admin screen.
 *
 * So people can be added to the allowlist without a developer, a spreadsheet
 * re-import, or an SSH session. The Summit is invite only and the list is the
 * only thing standing between a forwarded link and an uninvited registration,
 * so it has to be editable by the person running the event.
 *
 * Adding here APPENDS. The WP-CLI import REPLACES the whole list from the
 * spreadsheet, which would wipe anything added on this screen, so the notice
 * below says so plainly.
 *
 * Lives on the log blog only, beside Summit registrations. The list is a
 * network-wide site option, so one screen serves all four locales.
 */
class Admin
{
    public const PAGE = 'summit-invite-list';
    private const PER_PAGE = 50;

    public static function init(): void
    {
        \add_action('admin_menu', [self::class, 'add_page']);
        \add_action('admin_post_mb_summit_invite', [self::class, 'handle_post']);
    }

    public static function add_page(): void
    {
        if (\get_current_blog_id() !== Registrations::log_blog_id()) {
            return;
        }

        \add_submenu_page(
            'edit.php?post_type=' . Registrations::POST_TYPE,
            'Summit invite list',
            'Invite list',
            'manage_options',
            self::PAGE,
            [self::class, 'render']
        );
    }

    /** Handles add and remove, then redirects so a refresh cannot resubmit. */
    public static function handle_post(): void
    {
        if (!\current_user_can('manage_options')) {
            \wp_die('You do not have permission to change the Summit invite list.');
        }

        \check_admin_referer('mb_summit_invite');

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
        } else {
            $result = ['ok' => false, 'message' => 'Unknown action.'];
        }

        \set_transient(
            'mb_summit_admin_notice_' . \get_current_user_id(),
            $result,
            60
        );

        \wp_safe_redirect(\add_query_arg(
            ['post_type' => Registrations::POST_TYPE, 'page' => self::PAGE],
            \admin_url('edit.php')
        ));
        exit;
    }

    public static function render(): void
    {
        if (!\current_user_can('manage_options')) {
            return;
        }

        $list = InviteList::all();
        $search = isset($_GET['s']) ? strtolower(trim((string) $_GET['s'])) : '';
        $notice = \get_transient('mb_summit_admin_notice_' . \get_current_user_id());

        if (is_array($notice)) {
            \delete_transient('mb_summit_admin_notice_' . \get_current_user_id());
        }

        $rows = [];
        foreach ($list as $email => $record) {
            if ($search !== ''
                && strpos($email, $search) === false
                && strpos(strtolower((string) ($record['company'] ?? '')), $search) === false
                && strpos(strtolower((string) ($record['name'] ?? '')), $search) === false) {
                continue;
            }
            $rows[$email] = $record;
        }

        ksort($rows);

        // How many places each company has already used, so the screen can say
        // whether adding another person to it would achieve anything.
        $used = [];
        foreach ($rows as $record) {
            $key = (string) ($record['company_key'] ?? '');
            if ($key !== '' && !isset($used[$key])) {
                $used[$key] = Registrations::count_for_company($key);
            }
        }

        $page = max(1, (int) ($_GET['paged'] ?? 1));
        $total = count($rows);
        $pages = max(1, (int) ceil($total / self::PER_PAGE));
        $page = min($page, $pages);
        $slice = array_slice($rows, ($page - 1) * self::PER_PAGE, self::PER_PAGE, true);

        $cap = InviteList::cap_per_company();
        ?>
        <div class="wrap">
            <h1>Summit invite list</h1>

            <?php if (is_array($notice)) { ?>
                <div class="notice notice-<?= $notice['ok'] ? 'success' : 'error'; ?>">
                    <p><?= \esc_html($notice['message']); ?></p>
                </div>
            <?php } ?>

            <p>
                Only these addresses can register. The check is on the exact address, so
                forwarding a registration link to someone not listed here does not let them in,
                whether or not their company has places left.
            </p>

            <div class="notice notice-warning inline">
                <p>
                    <strong>Adding here adds to the list.</strong> Re-importing the spreadsheet with
                    <code>wp summit import-invites</code> REPLACES the whole list and would remove
                    anyone added on this screen, so add them to the spreadsheet as well if you want
                    them to survive a re-import.
                </p>
            </div>

            <h2>Add someone</h2>
            <form method="post" action="<?= \esc_url(\admin_url('admin-post.php')); ?>">
                <?php \wp_nonce_field('mb_summit_invite'); ?>
                <input type="hidden" name="action" value="mb_summit_invite">
                <input type="hidden" name="mb_action" value="add">
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><label for="mb-email">Email</label></th>
                        <td><input name="email" id="mb-email" type="email" class="regular-text" required>
                            <p class="description">The address they will type into the form. Case does not matter.</p></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="mb-name">Name</label></th>
                        <td><input name="name" id="mb-name" type="text" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="mb-company">Company</label></th>
                        <td><input name="company" id="mb-company" type="text" class="regular-text" required>
                            <p class="description">
                                The <?= (int) $cap; ?>-per-company limit counts against this. Spell it exactly
                                as it appears for their colleagues, or they will get their own
                                <?= (int) $cap; ?> places. Search below to check the spelling first.
                            </p></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="mb-audience">Audience</label></th>
                        <td>
                            <select name="audience" id="mb-audience" required>
                                <option value="">Select</option>
                                <option value="UK">UK — picks one day, 4th or 5th</option>
                                <option value="INT">INT — 3rd and 4th</option>
                                <option value="US">US — 3rd, 4th and 5th</option>
                                <option value="FR">FR — 3rd and 4th</option>
                            </select>
                            <p class="description">
                                Decides which registration page they can use and which days they take.
                                It must match the page you send them to.
                            </p>
                        </td>
                    </tr>
                </table>
                <?php \submit_button('Add to the invite list'); ?>
            </form>

            <h2>Who is on the list (<?= (int) count($list); ?> people)</h2>

            <form method="get">
                <input type="hidden" name="post_type" value="<?= \esc_attr(Registrations::POST_TYPE); ?>">
                <input type="hidden" name="page" value="<?= \esc_attr(self::PAGE); ?>">
                <p class="search-box">
                    <label class="screen-reader-text" for="mb-search">Search the invite list</label>
                    <input type="search" id="mb-search" name="s" value="<?= \esc_attr($search); ?>"
                           placeholder="email, name or company">
                    <?php \submit_button('Search', '', '', false); ?>
                </p>
            </form>

            <?php if ($search !== '') { ?>
                <p><?= (int) $total; ?> match<?= $total === 1 ? '' : 'es'; ?> for
                    "<?= \esc_html($search); ?>".</p>
            <?php } ?>

            <table class="wp-list-table widefat striped">
                <thead>
                    <tr>
                        <th>Email</th><th>Name</th><th>Company</th>
                        <th>Audience</th><th>Places used</th><th></th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($slice)) { ?>
                    <tr><td colspan="6">Nobody matches.</td></tr>
                <?php } ?>
                <?php foreach ($slice as $email => $record) {
                    $key = (string) ($record['company_key'] ?? '');
                    $taken = $used[$key] ?? 0;
                    ?>
                    <tr>
                        <td><code><?= \esc_html($email); ?></code></td>
                        <td><?= \esc_html((string) ($record['name'] ?? '')); ?></td>
                        <td><?= \esc_html((string) ($record['company'] ?? '')); ?></td>
                        <td><?= \esc_html((string) ($record['audience'] ?? '')); ?></td>
                        <td>
                            <?= (int) $taken; ?> of <?= (int) $cap; ?>
                            <?php if ($taken >= $cap) { ?>
                                <strong>(full)</strong>
                            <?php } ?>
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

            <?php if ($pages > 1) { ?>
                <div class="tablenav"><div class="tablenav-pages">
                    <?= \paginate_links([
                        'base' => \add_query_arg('paged', '%#%'),
                        'format' => '',
                        'current' => $page,
                        'total' => $pages,
                    ]); ?>
                </div></div>
            <?php } ?>
        </div>
        <?php
    }
}
