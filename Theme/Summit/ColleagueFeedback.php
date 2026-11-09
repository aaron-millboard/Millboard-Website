<?php

namespace Theme\Summit;

/**
 * Internal "What did you hear?" form: colleagues who hosted at the Summit pass on what attendees
 * told them. English only, on an unlisted page. Same shape as Theme\Summit\Feedback (the external
 * survey): one public endpoint, one network-wide table, results read in wp-admin under
 * Summit > Colleague feedback. Nothing goes to HubSpot.
 *
 * Unlike the external survey this one can hold personal notes about named attendees (question 17
 * onwards, up to five per response). The form tells colleagues those notes are passed on, logged
 * in HubSpot by a person and then deleted from the form, so the results screen has a "Clear
 * attendee notes" button per response (the rest of the response is kept) and a delete button.
 *
 * The lists below are the single source of truth: the block template draws its buttons from them
 * and the endpoint checks every answer against them.
 */
final class ColleagueFeedback
{
    public const PAGE = 'millboard-summit-colleague-feedback';
    public const MAX_ATTENDEES = 5;
    private const DB_VERSION = '1';
    private const MAX_BODY = 40000;
    private const MAX_TEXT = 2000;
    private const NONE = 'None specifically';

    public const TEAMS = ['Sales UK&I', 'Sales France', 'Sales US', 'Marketing', 'Product', 'Operations', 'Other'];
    public const SCOPES = ['One attendee', 'Several attendees'];
    public const TYPES = ['Approved installer', 'Distributor or merchant', 'Architect or specifier', 'Garden or landscape designer', 'Commercial client', 'Other'];
    public const MARKETS = ['UK', 'Ireland', 'France', 'Germany', 'US', 'International'];
    public const AGAIN = ['Very likely', 'Likely', 'Unsure', 'Unlikely'];
    public const PRODUCTS = ['Envello LapBoard Heritage (Weathered Oak look)', 'Envello LapBoard Classic (Enhanced Grain look)', 'Envello RibBoard', 'Autumn Oak (new colour)', self::NONE];
    public const BRAND = ['Much more positive', 'Slightly more positive', 'No change', 'Less positive', 'Don’t know'];
    public const OPP = ['Yes, new lead', 'Yes, existing deal progressed', 'Possible, follow-up needed', 'No'];
    public const LOGGED = ['Yes', 'Not yet', 'Not applicable'];
    public const YESNO = ['Yes', 'No'];
    public const TOPICS = ['Sales follow-up', 'Product question or concern', 'Complaint or service issue', 'Relationship or account insight', 'Marketing opportunity, e.g. case study or testimonial', 'Other'];
    public const WHO = ['Sales UK&I', 'Sales France', 'Sales US', 'Marketing', 'Product', 'Customer Services', 'Specific person'];
    public const URGENCY = ['Action this week', 'Action this month', 'For information only'];
    public const HUBSPOT = ['Yes', 'No, please log for me', 'Not a HubSpot contact'];
    public const PERMISSION = ['Permission given', 'Permission not given', 'Not asked'];

    public static function init(): void
    {
        \add_action('rest_api_init', static function () {
            // Public and nonce-free on purpose, like the other Summit routes: the page is served from
            // the full page cache. Gate::allow_stale_nonce() already exempts every /summit/ path.
            \register_rest_route(Gate::NAMESPACE, '/summit/colleague-feedback', [
                'methods' => 'POST',
                'callback' => [self::class, 'handle'],
                'permission_callback' => '__return_true',
            ]);
        });

        \add_action('admin_menu', [self::class, 'add_page'], 21);
        \add_action('admin_post_mb_summit_colleague_export', [self::class, 'handle_export']);
        \add_action('admin_post_mb_summit_colleague_clear', [self::class, 'handle_clear']);
        \add_action('admin_post_mb_summit_colleague_delete', [self::class, 'handle_delete']);
    }

    // ----------------------------------------------------------------- storage

    private static function table(): string
    {
        global $wpdb;

        return $wpdb->base_prefix . 'summit_colleague_feedback';
    }

    private static function install(): void
    {
        if (\get_site_option('mb_summit_colleague_feedback_db') === self::DB_VERSION) {
            return;
        }

        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        // The few answers worth sorting on are columns; the rest, including attendee notes, is JSON.
        \dbDelta('CREATE TABLE ' . self::table() . ' (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            created_at datetime NOT NULL,
            name varchar(120) NOT NULL DEFAULT \'\',
            team varchar(40) NOT NULL DEFAULT \'\',
            market varchar(30) NOT NULL DEFAULT \'\',
            overall tinyint(3) unsigned NOT NULL,
            notes_count tinyint(3) unsigned NOT NULL DEFAULT 0,
            notes_cleared_at datetime NULL,
            page_uri varchar(500) NOT NULL DEFAULT \'\',
            answers longtext NOT NULL,
            PRIMARY KEY  (id)
        ) ' . $wpdb->get_charset_collate() . ';');

        \update_site_option('mb_summit_colleague_feedback_db', self::DB_VERSION);
    }

    // ---------------------------------------------------------------- endpoint

    /**
     * @param mixed $v
     * @param array<int,string> $list
     */
    private static function in($v, array $list): ?string
    {
        return is_string($v) && in_array($v, $list, true) ? $v : null;
    }

    /**
     * Every chosen value must be in the list. Returns the de-duplicated values, or null.
     *
     * @param mixed $v
     * @param array<int,string> $list
     * @return ?array<int,string>
     */
    private static function many($v, array $list, bool $required = true): ?array
    {
        $v = is_array($v) ? array_values(array_unique(array_filter($v, 'is_string'))) : [];

        if (($required && !$v) || array_diff($v, $list)) {
            return null;
        }

        return $v;
    }

    /**
     * Checks one submission. Returns [clean answers, ''] or [null, reason].
     *
     * @param  array<string,mixed> $a
     * @return array{0:?array<string,mixed>,1:string}
     */
    public static function validate(array $a): array
    {
        $c = [];

        $c['name'] = Feedback::text($a['name'] ?? '', 120);
        if ($c['name'] === '') {
            return [null, 'name'];
        }

        foreach (['team' => self::TEAMS, 'scope' => self::SCOPES, 'market' => self::MARKETS] as $k => $list) {
            $c[$k] = self::in($a[$k] ?? null, $list);
            if ($c[$k] === null) {
                return [null, $k];
            }
        }

        $c['types'] = self::many($a['types'] ?? null, self::TYPES);
        if ($c['types'] === null) {
            return [null, 'types'];
        }

        $overall = filter_var($a['overall'] ?? null, FILTER_VALIDATE_INT);
        if ($overall === false || $overall < 1 || $overall > 5) {
            return [null, 'overall'];
        }
        $c['overall'] = (int) $overall;

        // Optional single choices: blank is allowed, anything else must be on the list.
        foreach (['again' => self::AGAIN, 'brand' => self::BRAND, 'opp' => self::OPP, 'permission' => self::PERMISSION] as $k => $list) {
            $v = $a[$k] ?? '';
            $c[$k] = $v === '' ? '' : self::in($v, $list);
            if ($c[$k] === null) {
                return [null, $k];
            }
        }

        $c['prods'] = self::many($a['prods'] ?? [], self::PRODUCTS, false);
        if ($c['prods'] === null || (in_array(self::NONE, $c['prods'], true) && count($c['prods']) > 1)) {
            return [null, 'prods'];
        }

        // The HubSpot follow-ups only exist when an opportunity was chosen.
        $follow = $c['opp'] !== '' && $c['opp'] !== 'No';
        $logged = $a['logged'] ?? '';
        $c['logged'] = $follow && $logged !== '' ? self::in($logged, self::LOGGED) : '';
        if ($c['logged'] === null) {
            return [null, 'logged'];
        }
        $c['opp_notes'] = $follow ? Feedback::text($a['opp_notes'] ?? '') : '';

        foreach (['feeling', 'best', 'less', 'concerns', 'future', 'quotes', 'extra'] as $k) {
            $c[$k] = Feedback::text($a[$k] ?? '');
        }

        $c['notes'] = self::in($a['notes'] ?? null, self::YESNO);
        if ($c['notes'] === null) {
            return [null, 'notes'];
        }

        $c['attendees'] = [];
        if ($c['notes'] === 'Yes') {
            $list = is_array($a['attendees'] ?? null) ? array_values($a['attendees']) : [];
            if (!$list || count($list) > self::MAX_ATTENDEES) {
                return [null, 'attendees'];
            }
            foreach ($list as $i => $x) {
                if (!is_array($x)) {
                    return [null, 'attendee_' . ($i + 1)];
                }
                $att = [
                    'name' => Feedback::text($x['name'] ?? '', 120),
                    'company' => Feedback::text($x['company'] ?? '', 120),
                    'note' => Feedback::text($x['note'] ?? ''),
                    'person' => Feedback::text($x['person'] ?? '', 120),
                ];
                if ($att['name'] === '' || $att['note'] === '') {
                    return [null, 'attendee_' . ($i + 1)];
                }
                foreach (['type' => self::TYPES, 'topic' => self::TOPICS, 'urgency' => self::URGENCY, 'hubspot' => self::HUBSPOT] as $k => $l) {
                    $v = $x[$k] ?? '';
                    $att[$k] = $v === '' ? '' : self::in($v, $l);
                    if ($att[$k] === null) {
                        return [null, 'attendee_' . ($i + 1) . '_' . $k];
                    }
                }
                $att['who'] = self::many($x['who'] ?? [], self::WHO, false);
                if ($att['who'] === null) {
                    return [null, 'attendee_' . ($i + 1) . '_who'];
                }
                // Only meaningful when "Specific person" was ticked.
                $att['person'] = in_array('Specific person', $att['who'], true) ? $att['person'] : '';
                $c['attendees'][] = $att;
            }
        }

        return [$c, ''];
    }

    /**
     * @return \WP_REST_Response
     */
    public static function handle(\WP_REST_Request $request)
    {
        global $wpdb;

        $in = $request->get_json_params();

        if (!is_array($in) || !is_array($in['answers'] ?? null) || strlen((string) \wp_json_encode($in)) > self::MAX_BODY) {
            return new \WP_REST_Response(['ok' => false, 'reason' => 'bad_request'], 400);
        }

        // 40 per 10 minutes per source. A whole office shares one public address; this only stops a flood.
        $ip = isset($_SERVER['REMOTE_ADDR']) ? (string) \wp_unslash($_SERVER['REMOTE_ADDR']) : '';
        $key = 'mb_scf_' . md5(\wp_salt() . $ip);
        $hits = (int) \get_transient($key);
        if ($hits >= (int) \apply_filters('millboard/summit/colleague_feedback_rate_limit', 40)) {
            return new \WP_REST_Response(['ok' => false, 'reason' => 'rate_limited'], 429);
        }
        \set_transient($key, $hits + 1, 10 * MINUTE_IN_SECONDS);

        [$clean, $why] = self::validate($in['answers']);
        if ($clean === null) {
            return new \WP_REST_Response(['ok' => false, 'reason' => 'invalid', 'field' => $why], 400);
        }

        self::install();

        $inserted = $wpdb->insert(self::table(), [
            'created_at' => \current_time('mysql'),
            'name' => $clean['name'],
            'team' => $clean['team'],
            'market' => $clean['market'],
            'overall' => $clean['overall'],
            'notes_count' => count($clean['attendees']),
            'page_uri' => substr(\esc_url_raw((string) ($in['pageUri'] ?? '')), 0, 500),
            'answers' => \wp_json_encode($clean, JSON_UNESCAPED_UNICODE),
        ]);

        if (!$inserted) {
            error_log('Summit colleague feedback: could not store a submission: ' . $wpdb->last_error);

            return new \WP_REST_Response(['ok' => false, 'reason' => 'log_failed'], 500);
        }

        return new \WP_REST_Response(['ok' => true], 200);
    }

    // ------------------------------------------------------------------- admin

    public static function add_page(): void
    {
        \add_submenu_page(Admin::MENU, 'Summit colleague feedback', 'Colleague feedback',
            'manage_options', self::PAGE, [self::class, 'render']);
    }

    /** @return array<int,array<string,mixed>> */
    private static function rows(): array
    {
        global $wpdb;
        self::install();

        $rows = $wpdb->get_results('SELECT * FROM ' . self::table() . ' ORDER BY id DESC', ARRAY_A) ?: [];

        foreach ($rows as &$r) {
            $r['a'] = json_decode((string) $r['answers'], true) ?: [];
        }

        return $rows;
    }

    private static function guard(): void
    {
        if (!\current_user_can('manage_options')) {
            \wp_die('You do not have permission to do this.', 403);
        }
        \check_admin_referer('mb_summit_colleague');
    }

    private static function back(string $done): void
    {
        \wp_safe_redirect(\add_query_arg(['page' => self::PAGE, 'scf_done' => $done], \admin_url('admin.php')));
        exit;
    }

    /** Removes the personal notes from one response and keeps everything else. */
    public static function handle_clear(): void
    {
        self::guard();
        global $wpdb;

        $id = (int) ($_POST['id'] ?? 0);
        $row = $wpdb->get_row($wpdb->prepare('SELECT answers FROM ' . self::table() . ' WHERE id = %d', $id), ARRAY_A);

        if ($row) {
            $a = json_decode((string) $row['answers'], true) ?: [];
            $a['attendees'] = [];
            $wpdb->update(self::table(), [
                'answers' => \wp_json_encode($a, JSON_UNESCAPED_UNICODE),
                'notes_count' => 0,
                'notes_cleared_at' => \current_time('mysql'),
            ], ['id' => $id]);
        }

        self::back('cleared');
    }

    public static function handle_delete(): void
    {
        self::guard();
        global $wpdb;

        $wpdb->delete(self::table(), ['id' => (int) ($_POST['id'] ?? 0)]);
        self::back('deleted');
    }

    /** @return array<int,array<string,string>> One row per response (no personal attendee notes). */
    private static function response_rows(): array
    {
        $out = [];

        foreach (array_reverse(self::rows()) as $r) {
            $a = $r['a'];
            $out[] = [
                'id' => (string) $r['id'],
                'submitted' => (string) $r['created_at'],
                'colleague' => (string) $r['name'],
                'team' => (string) $r['team'],
                'about' => (string) ($a['scope'] ?? ''),
                'attendee_types' => implode('; ', (array) ($a['types'] ?? [])),
                'market' => (string) $r['market'],
                'overall_1_5' => (string) $r['overall'],
                'attend_again' => (string) ($a['again'] ?? ''),
                'general_feeling' => (string) ($a['feeling'] ?? ''),
                'most_positive' => (string) ($a['best'] ?? ''),
                'less_relevant_or_unclear' => (string) ($a['less'] ?? ''),
                'product_interest' => implode('; ', (array) ($a['prods'] ?? [])),
                'questions_or_concerns' => (string) ($a['concerns'] ?? ''),
                'brand_change' => (string) ($a['brand'] ?? ''),
                'opportunity' => (string) ($a['opp'] ?? ''),
                'opportunity_logged_in_hubspot' => (string) ($a['logged'] ?? ''),
                'opportunity_notes' => (string) ($a['opp_notes'] ?? ''),
                'attendee_notes_waiting' => (string) $r['notes_count'],
                'attendee_notes_cleared' => (string) ($r['notes_cleared_at'] ?? ''),
                'future_summit' => (string) ($a['future'] ?? ''),
                'quotes' => (string) ($a['quotes'] ?? ''),
                'quotes_permission' => (string) ($a['permission'] ?? ''),
                'host_extra' => (string) ($a['extra'] ?? ''),
            ];
        }

        return $out;
    }

    /** @return array<int,array<string,string>> One row per attendee note still held. */
    private static function note_rows(): array
    {
        $out = [];

        foreach (array_reverse(self::rows()) as $r) {
            foreach ((array) ($r['a']['attendees'] ?? []) as $n) {
                $out[] = [
                    'response_id' => (string) $r['id'],
                    'submitted' => (string) $r['created_at'],
                    'colleague' => (string) $r['name'],
                    'attendee' => (string) ($n['name'] ?? ''),
                    'company' => (string) ($n['company'] ?? ''),
                    'attendee_type' => (string) ($n['type'] ?? ''),
                    'relates_to' => (string) ($n['topic'] ?? ''),
                    'note' => (string) ($n['note'] ?? ''),
                    'who_needs_to_see' => implode('; ', (array) ($n['who'] ?? [])),
                    'specific_person' => (string) ($n['person'] ?? ''),
                    'urgency' => (string) ($n['urgency'] ?? ''),
                    'logged_in_hubspot' => (string) ($n['hubspot'] ?? ''),
                ];
            }
        }

        return $out;
    }

    public static function handle_export(): void
    {
        if (!\current_user_can('manage_options')) {
            \wp_die('You do not have permission to export this.', 403);
        }
        \check_admin_referer('mb_summit_colleague_export');

        $notes = ($_GET['kind'] ?? '') === 'notes';
        $rows = $notes ? self::note_rows() : self::response_rows();

        \nocache_headers();
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=summit-colleague-' . ($notes ? 'attendee-notes-' : 'feedback-') . \gmdate('Y-m-d') . '.csv');

        $out = fopen('php://output', 'w');
        fwrite($out, "\xEF\xBB\xBF"); // BOM so Excel reads accents as UTF-8
        fputcsv($out, array_keys($rows[0] ?? ['id' => 0]));

        foreach ($rows as $row) {
            fputcsv($out, array_map([Feedback::class, 'csv_safe'], $row));
        }

        fclose($out);
        exit;
    }

    /**
     * Counts how often each option was chosen, in list order, then anything unexpected.
     *
     * @param array<int,array<string,mixed>> $rows
     * @param callable $get returns the value(s) for one row
     * @param array<int,string> $list
     */
    private static function tally(array $rows, callable $get, array $list): void
    {
        $n = array_fill_keys($list, 0);

        foreach ($rows as $r) {
            foreach ((array) $get($r) as $v) {
                if ($v !== '' && isset($n[$v])) {
                    $n[$v]++;
                }
            }
        }
        ?>
        <table class="widefat striped" style="max-width:520px;margin-bottom:12px">
            <tbody>
            <?php foreach ($n as $label => $count) { ?>
                <tr><td><?= \esc_html($label); ?></td><td style="width:60px"><?= (int) $count; ?></td></tr>
            <?php } ?>
            </tbody>
        </table>
        <?php
    }

    public static function render(): void
    {
        if (!\current_user_can('manage_options')) {
            return;
        }

        $rows = self::rows();
        $nonce = \wp_nonce_field('mb_summit_colleague', '_wpnonce', true, false);
        $export = static fn(string $kind) => \wp_nonce_url(\admin_url('admin-post.php?action=mb_summit_colleague_export&kind=' . $kind), 'mb_summit_colleague_export');
        $waiting = array_filter($rows, static fn($r) => (int) $r['notes_count'] > 0);
        $done = (string) ($_GET['scf_done'] ?? '');
        ?>
        <div class="wrap">
            <h1>Summit colleague feedback</h1>
            <?php if ($done === 'cleared' || $done === 'deleted') { ?>
                <div class="notice notice-success is-dismissible"><p><?= $done === 'cleared' ? 'Attendee notes cleared. The rest of that response is kept.' : 'Response deleted.'; ?></p></div>
            <?php } ?>
            <p>
                <?= (int) count($rows); ?> responses, <?= (int) count($waiting); ?> with attendee notes still waiting to be passed on.
                <?php if ($rows) { ?>
                    <a class="button button-primary" href="<?= \esc_url($export('responses')); ?>">Download responses CSV</a>
                    <?php if ($waiting) { ?>
                        <a class="button" href="<?= \esc_url($export('notes')); ?>">Download attendee notes CSV</a>
                    <?php } ?>
                <?php } ?>
            </p>

            <?php if (!$rows) { ?>
                <p>No responses yet.</p>
            <?php } else { ?>

                <?php if ($waiting) { ?>
                    <h2>Attendee notes waiting</h2>
                    <p>Pass each note to the people it names, log it in HubSpot, then press <strong>Clear attendee notes</strong>. That removes the personal notes and keeps the rest of the response.</p>
                    <?php foreach ($waiting as $r) { ?>
                        <div style="background:#fff;border:1px solid #c3c4c7;padding:12px 16px;margin:0 0 12px;max-width:900px">
                            <p style="margin-top:0"><strong>Response <?= (int) $r['id']; ?></strong> from <?= \esc_html((string) $r['name']); ?> (<?= \esc_html((string) $r['team']); ?>), <?= \esc_html((string) $r['created_at']); ?></p>
                            <?php foreach ((array) ($r['a']['attendees'] ?? []) as $n) { ?>
                                <table class="widefat striped" style="margin-bottom:10px">
                                    <tbody>
                                    <tr><th style="width:160px">Attendee</th><td><?= \esc_html(trim(($n['name'] ?? '') . ($n['company'] ? ', ' . $n['company'] : ''))); ?> <?= $n['type'] ? '(' . \esc_html($n['type']) . ')' : ''; ?></td></tr>
                                    <tr><th>Relates to</th><td><?= \esc_html((string) $n['topic']); ?></td></tr>
                                    <tr><th>Note</th><td><?= \nl2br(\esc_html((string) $n['note'])); ?></td></tr>
                                    <tr><th>Who needs to see it</th><td><?= \esc_html(implode(', ', (array) $n['who'])); ?><?= $n['person'] ? ': ' . \esc_html($n['person']) : ''; ?></td></tr>
                                    <tr><th>Urgency</th><td><?= \esc_html((string) $n['urgency']); ?></td></tr>
                                    <tr><th>Logged in HubSpot</th><td><?= \esc_html((string) $n['hubspot']); ?></td></tr>
                                    </tbody>
                                </table>
                            <?php } ?>
                            <form method="post" action="<?= \esc_url(\admin_url('admin-post.php')); ?>" onsubmit="return confirm('Clear these attendee notes? This cannot be undone.');">
                                <?= $nonce; ?>
                                <input type="hidden" name="action" value="mb_summit_colleague_clear">
                                <input type="hidden" name="id" value="<?= (int) $r['id']; ?>">
                                <button class="button">Clear attendee notes</button>
                            </form>
                        </div>
                    <?php } ?>
                <?php } ?>

                <h2>Overall</h2>
                <p>Average rating (1-5): <strong><?= \esc_html(number_format(array_sum(array_column($rows, 'overall')) / count($rows), 1)); ?></strong></p>
                <?php self::tally($rows, static fn($r) => (string) $r['overall'], ['1', '2', '3', '4', '5']); ?>

                <div style="display:flex;flex-wrap:wrap;gap:32px">
                    <div><h3>Team</h3><?php self::tally($rows, static fn($r) => $r['team'], self::TEAMS); ?></div>
                    <div><h3>Market</h3><?php self::tally($rows, static fn($r) => $r['market'], self::MARKETS); ?></div>
                    <div><h3>Attendee type</h3><?php self::tally($rows, static fn($r) => $r['a']['types'] ?? [], self::TYPES); ?></div>
                    <div><h3>Likely to attend again</h3><?php self::tally($rows, static fn($r) => $r['a']['again'] ?? '', self::AGAIN); ?></div>
                    <div><h3>Product interest</h3><?php self::tally($rows, static fn($r) => $r['a']['prods'] ?? [], self::PRODUCTS); ?></div>
                    <div><h3>Brand perception</h3><?php self::tally($rows, static fn($r) => $r['a']['brand'] ?? '', self::BRAND); ?></div>
                    <div><h3>Opportunities</h3><?php self::tally($rows, static fn($r) => $r['a']['opp'] ?? '', self::OPP); ?></div>
                </div>

                <h2>Responses</h2>
                <table class="widefat striped">
                    <thead>
                    <tr>
                        <th>Date</th><th>Colleague</th><th>Team</th><th>Market</th><th>Rating</th>
                        <th>General feeling</th><th>Best moment</th><th>Less relevant or unclear</th>
                        <th>Questions or concerns</th><th>Opportunity</th><th>Future Summit</th><th>Quotes</th><th>Anything else</th><th></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($rows as $r) {
                        $a = $r['a']; ?>
                        <tr>
                            <td><?= \esc_html((string) $r['created_at']); ?></td>
                            <td><?= \esc_html((string) $r['name']); ?></td>
                            <td><?= \esc_html((string) $r['team']); ?></td>
                            <td><?= \esc_html((string) $r['market']); ?></td>
                            <td><?= (int) $r['overall']; ?></td>
                            <td><?= \esc_html((string) ($a['feeling'] ?? '')); ?></td>
                            <td><?= \esc_html((string) ($a['best'] ?? '')); ?></td>
                            <td><?= \esc_html((string) ($a['less'] ?? '')); ?></td>
                            <td><?= \esc_html((string) ($a['concerns'] ?? '')); ?></td>
                            <td><?= \esc_html(trim((string) ($a['opp'] ?? '') . ' ' . (string) ($a['opp_notes'] ?? ''))); ?></td>
                            <td><?= \esc_html((string) ($a['future'] ?? '')); ?></td>
                            <td><?= \esc_html((string) ($a['quotes'] ?? '')); ?> <em><?= \esc_html((string) ($a['permission'] ?? '')); ?></em></td>
                            <td><?= \esc_html((string) ($a['extra'] ?? '')); ?></td>
                            <td>
                                <form method="post" action="<?= \esc_url(\admin_url('admin-post.php')); ?>" onsubmit="return confirm('Delete this whole response? This cannot be undone.');">
                                    <?= $nonce; ?>
                                    <input type="hidden" name="action" value="mb_summit_colleague_delete">
                                    <input type="hidden" name="id" value="<?= (int) $r['id']; ?>">
                                    <button class="button-link-delete">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php } ?>
                    </tbody>
                </table>
            <?php } ?>
        </div>
        <?php
    }
}
