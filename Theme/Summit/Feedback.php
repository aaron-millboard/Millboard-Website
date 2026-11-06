<?php

namespace Theme\Summit;

/**
 * Post-event feedback survey for the Millboard Summit (UK and French pages).
 *
 * The survey is not sent to HubSpot. Every submission is written to one table for
 * the whole network (base prefix), so an answer from the UK page and one from the
 * French page land in the same place and no blog switching is needed to read them.
 * Read them under Summit > Feedback survey in wp-admin, which also downloads a CSV.
 *
 * Each question is defined once here as [stored value, English label, French label].
 * The block template draws its markup from these, and the endpoint checks answers
 * against the same lists, so the page and the server cannot drift apart. Answers
 * are always STORED in English whatever language the page was in, so UK and French
 * responses report together.
 *
 * The visitor's IP address is not stored. It is only hashed into a short-lived
 * counter that stops one source flooding the endpoint.
 */
final class Feedback
{
    public const PAGE = 'millboard-summit-feedback';
    private const DB_VERSION = '1';
    private const MAX_BODY = 12000;
    private const MAX_TEXT = 2000;
    private const NONE = 'None of these';
    private const SKIP = "Didn't see";

    /** Five-point agreement scale for question 2. */
    public const AGREE = [
        ['Strongly disagree', 'Pas du tout d’accord'],
        ['Disagree', 'Pas d’accord'],
        ['Neutral', 'Neutre'],
        ['Agree', 'D’accord'],
        ['Strongly agree', 'Tout à fait d’accord'],
    ];

    /** Question 2 statements: key => [English, French]. */
    public const STATEMENTS = [
        'plans' => ['I have a clearer understanding of Millboard’s plans for 2027.', 'J’ai une meilleure compréhension des projets de Millboard pour 2027.'],
        'ideas' => ['I left with ideas I can act on in my business.', 'Je repars avec des idées que je peux mettre en pratique dans mon activité.'],
        'heard' => ['I felt my views and feedback were heard.', 'J’ai eu le sentiment que mon avis et mes retours ont été entendus.'],
    ];

    /** Question 4 products: key => [stored name, English label, French label, English note, French note]. */
    public const PRODUCTS = [
        'lapheritage' => ['Envello LapBoard Heritage', 'Envello LapBoard Heritage', 'Envello LapBoard Heritage', 'Weathered Oak style finish', 'Finition de style Weathered Oak'],
        'lapclassic' => ['Envello LapBoard Classic', 'Envello LapBoard Classic', 'Envello LapBoard Classic', 'Enhanced Grain style finish', 'Finition de style Enhanced Grain'],
        'ribboard' => ['Envello RibBoard', 'Envello RibBoard', 'Envello RibBoard', '', ''],
        'autumnoak' => ['Autumn Oak', 'Autumn Oak (new colour)', 'Autumn Oak (nouveau coloris)', '', ''],
    ];

    /** Question 4 "most likely to sell, specify or recommend" chips (the four products, then the exclusive option). */
    public const NONE_OPTION = [self::NONE, 'None of these', 'Aucun de ces produits'];

    public const SESSIONS = [
        ['New product first look', 'Premier aperçu des nouveaux produits'],
        ['Certification and Fire-Rating Update', 'Point sur les certifications et le classement au feu'],
        ['Millboard Brand Positioning', 'Le positionnement de la marque Millboard'],
        ['Customer Insight: What Really Matters?', 'Connaissance client : qu’est-ce qui compte vraiment ?'],
        ['Equip to Sell', 'Equip to Sell : les moyens de vendre'],
        ['Manufacturing facility tour', 'Visite du site de fabrication'],
        ['Networking and 1:1 meetings', 'Réseautage et rendez-vous individuels'],
        ['Other', 'Autre'],
    ];

    public const MARKETS = [
        ['United Kingdom', 'Royaume-Uni'],
        ['Ireland', 'Irlande'],
        ['United States', 'États-Unis'],
        ['France', 'France'],
        ['Germany', 'Allemagne'],
        ['Rest of Europe', 'Reste de l’Europe'],
        ['Australia', 'Australie'],
        ['Canada', 'Canada'],
        ['Other', 'Autre'],
    ];

    public const ROLES = [
        ['Distributor', 'Distributeur'],
        ['Installer', 'Installateur'],
        ['Designer', 'Designer'],
        ['Developer', 'Promoteur'],
        ['Architect', 'Architecte'],
        ['Showspace partner', 'Partenaire showspace'],
        ['Other', 'Autre'],
    ];

    public static function init(): void
    {
        \add_action('rest_api_init', static function () {
            // Public and nonce-free on purpose: the page is served from the full
            // page cache, and a nonce printed into cached HTML is what refused
            // genuine registrations in September. Gate::allow_stale_nonce() already
            // exempts every path under /summit/, which includes this one.
            \register_rest_route(Gate::NAMESPACE, '/summit/feedback', [
                'methods' => 'POST',
                'callback' => [self::class, 'handle'],
                'permission_callback' => '__return_true',
            ]);
        });

        \add_action('admin_menu', [self::class, 'add_page'], 20);
        \add_action('admin_post_mb_summit_feedback_export', [self::class, 'handle_export']);
    }

    // ----------------------------------------------------------------- storage

    private static function table(): string
    {
        global $wpdb;

        return $wpdb->base_prefix . 'summit_feedback';
    }

    private static function install(): void
    {
        if (\get_site_option('mb_summit_feedback_db') === self::DB_VERSION) {
            return;
        }

        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        // The few answers worth sorting on are columns; everything else is in `answers` as JSON.
        \dbDelta('CREATE TABLE ' . self::table() . ' (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            created_at datetime NOT NULL,
            audience varchar(4) NOT NULL DEFAULT \'UK\',
            overall tinyint(3) unsigned NOT NULL,
            relevance tinyint(3) unsigned NOT NULL,
            again tinyint(3) unsigned NOT NULL,
            market varchar(40) NOT NULL DEFAULT \'\',
            role varchar(40) NOT NULL DEFAULT \'\',
            email varchar(190) NOT NULL DEFAULT \'\',
            page_uri varchar(500) NOT NULL DEFAULT \'\',
            answers longtext NOT NULL,
            PRIMARY KEY  (id),
            KEY audience (audience)
        ) ' . $wpdb->get_charset_collate() . ';');

        \update_site_option('mb_summit_feedback_db', self::DB_VERSION);
    }

    // ---------------------------------------------------------------- endpoint

    /**
     * @param mixed $v
     */
    private static function scale($v): ?int
    {
        $n = filter_var($v, FILTER_VALIDATE_INT);

        return $n !== false && $n >= 0 && $n <= 10 ? (int) $n : null;
    }

    /**
     * @param mixed  $v
     * @param array<int,array<int,string>> $options
     */
    private static function pick($v, array $options): ?string
    {
        return is_string($v) && in_array($v, array_column($options, 0), true) ? $v : null;
    }

    private static function text($v, int $max = self::MAX_TEXT): string
    {
        return mb_substr(trim(\sanitize_textarea_field(is_string($v) ? $v : '')), 0, $max);
    }

    /**
     * Checks one submission. Returns [clean answers, ''] or [null, reason].
     *
     * @param  array<string,mixed> $a
     * @return array{0:?array<string,mixed>,1:string}
     */
    public static function validate(array $a): array
    {
        $clean = [];

        foreach (['overall', 'relevance', 'again'] as $k) {
            $n = self::scale($a[$k] ?? null);
            if ($n === null) {
                return [null, $k];
            }
            $clean[$k] = $n;
        }

        foreach (array_keys(self::STATEMENTS) as $k) {
            $v = self::pick($a['obj'][$k] ?? null, self::AGREE);
            if ($v === null) {
                return [null, 'obj_' . $k];
            }
            $clean['obj'][$k] = $v;
        }

        foreach (self::PRODUCTS as $k => $p) {
            $v = isset($a['prod'][$k]) ? (string) $a['prod'][$k] : '';
            if (!in_array($v, ['1', '2', '3', '4', '5', self::SKIP], true)) {
                return [null, 'prod_' . $k];
            }
            $clean['prod'][$k] = $v;
        }

        $allowed = array_merge(array_column(self::PRODUCTS, 0), [self::NONE]);
        $interest = is_array($a['interest'] ?? null) ? array_values(array_unique(array_filter($a['interest'], 'is_string'))) : [];
        if (!$interest || array_diff($interest, $allowed) || (in_array(self::NONE, $interest, true) && count($interest) > 1)) {
            return [null, 'interest'];
        }
        $clean['interest'] = $interest;
        $clean['prod_notes'] = self::text($a['prod_notes'] ?? '');

        $session = self::pick($a['session'] ?? null, self::SESSIONS);
        $other = self::text($a['session_other'] ?? '', 200);
        if ($session === null || ($session === 'Other' && $other === '')) {
            return [null, 'session'];
        }
        $clean['session'] = $session;
        $clean['session_other'] = $session === 'Other' ? $other : '';

        $clean['improve'] = self::text($a['improve'] ?? '');

        foreach (['market' => self::MARKETS, 'role' => self::ROLES] as $k => $options) {
            $v = self::pick($a[$k] ?? null, $options);
            if ($v === null) {
                return [null, $k];
            }
            $clean[$k] = $v;
        }

        $clean['name'] = self::text($a['name'] ?? '', 120);
        $email = \sanitize_email((string) ($a['email'] ?? ''));
        if ($email !== '' && !\is_email($email)) {
            return [null, 'email'];
        }
        $clean['email'] = $email;

        return [$clean, ''];
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

        // 40 per 10 minutes per source. Colleagues in one office share a public
        // address, so this is generous; it only stops a flood.
        $ip = isset($_SERVER['REMOTE_ADDR']) ? (string) \wp_unslash($_SERVER['REMOTE_ADDR']) : '';
        $key = 'mb_sf_' . md5(\wp_salt() . $ip);
        $hits = (int) \get_transient($key);
        if ($hits >= (int) \apply_filters('millboard/summit/feedback_rate_limit', 40)) {
            return new \WP_REST_Response(['ok' => false, 'reason' => 'rate_limited'], 429);
        }
        \set_transient($key, $hits + 1, 10 * MINUTE_IN_SECONDS);

        [$clean, $why] = self::validate($in['answers']);
        if ($clean === null) {
            return new \WP_REST_Response(['ok' => false, 'reason' => 'invalid', 'field' => $why], 400);
        }

        self::install();

        $audience = ($in['audience'] ?? '') === 'FR' ? 'FR' : 'UK';

        $inserted = $wpdb->insert(self::table(), [
            'created_at' => \current_time('mysql'),
            'audience' => $audience,
            'overall' => $clean['overall'],
            'relevance' => $clean['relevance'],
            'again' => $clean['again'],
            'market' => $clean['market'],
            'role' => $clean['role'],
            'email' => substr($clean['email'], 0, 190),
            'page_uri' => substr(\esc_url_raw((string) ($in['pageUri'] ?? '')), 0, 500),
            'answers' => \wp_json_encode($clean, JSON_UNESCAPED_UNICODE),
        ]);

        if (!$inserted) {
            error_log('Summit feedback survey: could not store a submission: ' . $wpdb->last_error);

            return new \WP_REST_Response(['ok' => false, 'reason' => 'log_failed'], 500);
        }

        return new \WP_REST_Response(['ok' => true], 200);
    }

    // ------------------------------------------------------------------- admin

    public static function add_page(): void
    {
        \add_submenu_page(Admin::MENU, 'Summit feedback survey', 'Feedback survey',
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

    /** Flat columns for the CSV, oldest first. @return array<int,array<string,string>> */
    private static function export_rows(): array
    {
        $out = [];

        foreach (array_reverse(self::rows()) as $r) {
            $a = $r['a'];
            $row = [
                'id' => (string) $r['id'],
                'submitted' => (string) $r['created_at'],
                'page_language' => (string) $r['audience'],
                'overall_0_10' => (string) $r['overall'],
            ];
            foreach (self::STATEMENTS as $k => $s) {
                $row['agree_' . $k] = (string) ($a['obj'][$k] ?? '');
            }
            $row['relevance_0_10'] = (string) $r['relevance'];
            foreach (self::PRODUCTS as $k => $p) {
                $row['rating_' . $k] = (string) ($a['prod'][$k] ?? '');
            }
            $row['most_likely_to_sell_specify_recommend'] = implode('; ', (array) ($a['interest'] ?? []));
            $row['product_comments'] = (string) ($a['prod_notes'] ?? '');
            $row['most_valuable_session'] = (string) ($a['session'] ?? '');
            $row['most_valuable_session_other'] = (string) ($a['session_other'] ?? '');
            $row['attend_again_0_10'] = (string) $r['again'];
            $row['one_thing_to_improve'] = (string) ($a['improve'] ?? '');
            $row['market'] = (string) $r['market'];
            $row['business'] = (string) $r['role'];
            $row['name'] = (string) ($a['name'] ?? '');
            $row['email'] = (string) $r['email'];
            $out[] = $row;
        }

        return $out;
    }

    /** Stops a spreadsheet running free text that starts with = + - or @ as a formula. */
    private static function csv_safe(string $v): string
    {
        return $v !== '' && strpbrk($v[0], "=+-@\t\r") !== false ? "'" . $v : $v;
    }

    public static function handle_export(): void
    {
        if (!\current_user_can('manage_options')) {
            \wp_die('You do not have permission to export this.', 403);
        }
        \check_admin_referer('mb_summit_feedback_export');

        $rows = self::export_rows();

        \nocache_headers();
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=summit-feedback-' . \gmdate('Y-m-d') . '.csv');

        $out = fopen('php://output', 'w');
        fwrite($out, "\xEF\xBB\xBF"); // BOM so Excel reads the French accents as UTF-8
        fputcsv($out, array_keys($rows[0] ?? ['id' => 0]));

        foreach ($rows as $row) {
            fputcsv($out, array_map([self::class, 'csv_safe'], $row));
        }

        fclose($out);
        exit;
    }

    /** @param array<int,array<string,mixed>> $rows */
    private static function average(array $rows, string $col): string
    {
        return $rows ? number_format(array_sum(array_column($rows, $col)) / count($rows), 1) : '-';
    }

    public static function render(): void
    {
        if (!\current_user_can('manage_options')) {
            return;
        }

        $rows = self::rows();
        $uk = array_filter($rows, static fn($r) => $r['audience'] === 'UK');
        $fr = array_filter($rows, static fn($r) => $r['audience'] === 'FR');
        $export = \wp_nonce_url(\admin_url('admin-post.php?action=mb_summit_feedback_export'), 'mb_summit_feedback_export');
        ?>
        <div class="wrap">
            <h1>Summit feedback survey</h1>
            <p>
                <?= (int) count($rows); ?> responses (<?= (int) count($uk); ?> UK page, <?= (int) count($fr); ?> French page).
                Answers are stored in English whichever page they came from.
                <?php if ($rows) { ?>
                    <a class="button button-primary" href="<?= \esc_url($export); ?>">Download CSV</a>
                <?php } ?>
            </p>

            <?php if (!$rows) { ?>
                <p>No responses yet.</p>
            <?php } else { ?>
                <h2>Averages</h2>
                <table class="widefat striped" style="max-width:760px">
                    <thead><tr><th></th><th>All</th><th>UK page</th><th>French page</th></tr></thead>
                    <tbody>
                    <?php foreach (['overall' => 'Overall rating (0-10)', 'relevance' => 'Relevance to business (0-10)', 'again' => 'Likely to attend again (0-10)'] as $col => $label) { ?>
                        <tr>
                            <td><?= \esc_html($label); ?></td>
                            <td><?= \esc_html(self::average($rows, $col)); ?></td>
                            <td><?= \esc_html(self::average($uk, $col)); ?></td>
                            <td><?= \esc_html(self::average($fr, $col)); ?></td>
                        </tr>
                    <?php } ?>
                    </tbody>
                </table>

                <h2>New products</h2>
                <table class="widefat striped" style="max-width:760px">
                    <thead><tr><th>Product</th><th>Average (1-5)</th><th>Rated</th><th>Didn't see</th><th>Most likely to sell, specify or recommend</th></tr></thead>
                    <tbody>
                    <?php foreach (self::PRODUCTS as $k => $p) {
                        $scores = [];
                        $skipped = $liked = 0;
                        foreach ($rows as $r) {
                            $v = (string) ($r['a']['prod'][$k] ?? '');
                            if ($v === self::SKIP) {
                                $skipped++;
                            } elseif ($v !== '') {
                                $scores[] = (int) $v;
                            }
                            $liked += in_array($p[0], (array) ($r['a']['interest'] ?? []), true) ? 1 : 0;
                        } ?>
                        <tr>
                            <td><?= \esc_html($p[1]); ?></td>
                            <td><?= $scores ? \esc_html(number_format(array_sum($scores) / count($scores), 1)) : '-'; ?></td>
                            <td><?= (int) count($scores); ?></td>
                            <td><?= (int) $skipped; ?></td>
                            <td><?= (int) $liked; ?></td>
                        </tr>
                    <?php } ?>
                    </tbody>
                </table>

                <h2>Responses</h2>
                <table class="widefat striped">
                    <thead>
                    <tr>
                        <th>Date</th><th>Page</th><th>Market</th><th>Business</th>
                        <th>Overall</th><th>Relevance</th><th>Again</th>
                        <th>Most valuable</th><th>Product comments</th><th>One thing to improve</th><th>Name / email</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($rows as $r) {
                        $a = $r['a'];
                        $session = (string) ($a['session'] ?? '');
                        if (!empty($a['session_other'])) {
                            $session .= ': ' . $a['session_other'];
                        } ?>
                        <tr>
                            <td><?= \esc_html((string) $r['created_at']); ?></td>
                            <td><?= \esc_html((string) $r['audience']); ?></td>
                            <td><?= \esc_html((string) $r['market']); ?></td>
                            <td><?= \esc_html((string) $r['role']); ?></td>
                            <td><?= (int) $r['overall']; ?></td>
                            <td><?= (int) $r['relevance']; ?></td>
                            <td><?= (int) $r['again']; ?></td>
                            <td><?= \esc_html($session); ?></td>
                            <td><?= \esc_html((string) ($a['prod_notes'] ?? '')); ?></td>
                            <td><?= \esc_html((string) ($a['improve'] ?? '')); ?></td>
                            <td><?= \esc_html(trim(($a['name'] ?? '') . ' ' . $r['email'])); ?></td>
                        </tr>
                    <?php } ?>
                    </tbody>
                </table>
            <?php } ?>
        </div>
        <?php
    }
}
