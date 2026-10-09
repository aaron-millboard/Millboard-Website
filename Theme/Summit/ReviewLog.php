<?php

namespace Theme\Summit;

/**
 * Logged relay for the Summit pre-arrival form.
 *
 * The browser used to post straight to HubSpot, so a HubSpot error, a dropped
 * connection or a bad deploy lost the delegate's answers with nothing to replay.
 * Now the form posts here first. Every submission is written to a table BEFORE
 * anything else happens, then forwarded to HubSpot, and the outcome is recorded
 * against the row. If HubSpot refuses or is down the delegate is still told it
 * worked, because we hold their answers, and `wp summit review-replay` sends the
 * failed rows again once the cause is fixed.
 *
 * One table for the whole network (base prefix), so a submission from any
 * locale lands in the same place and no blog switching is needed to read it.
 *
 *   wp summit review-status
 *   wp summit review-export --file=review-log.csv
 *   wp summit review-replay [--id=<n>] [--dry-run]
 *
 * Deliberately not stored: the visitor's IP address. It is passed to HubSpot for
 * its own analytics and nothing here needs it.
 */
class ReviewLog
{
    public const PORTAL_ID = '26853518';
    private const DB_VERSION = '1';
    private const MAX_BODY = 20000;

    /** Fields the form may set. HubSpot ignores the rest anyway; this keeps junk out of the log. */
    private const ALLOWED = [
        'firstname', 'lastname', 'company', 'email',
        'summit_nda_accepted', 'summit_hs_accepted', 'summit_competition_law_accepted',
        'summit_data_consent', 'summit_health_data_consent', 'summit_dietary_requirements',
        'summit_dietary_notes', 'summit_accessibility_requirements', 'summit_photo_consent',
    ];

    public static function init(): void
    {
        \add_action('rest_api_init', static function () {
            // Public and nonce-free on purpose: the page is served from the full
            // page cache, and a nonce printed into cached HTML is what refused
            // genuine registrations in September. The summit routes are already
            // exempted in Gate::allow_stale_nonce(), which matches this path.
            \register_rest_route(Gate::NAMESPACE, '/summit/review', [
                'methods' => 'POST',
                'callback' => [self::class, 'handle'],
                'permission_callback' => '__return_true',
            ]);
        });

        if (defined('WP_CLI') && \WP_CLI) {
            \WP_CLI::add_command('summit review-status', [self::class, 'cli_status']);
            \WP_CLI::add_command('summit review-export', [self::class, 'cli_export']);
            \WP_CLI::add_command('summit review-replay', [self::class, 'cli_replay']);
        }
    }

    private static function table(): string
    {
        global $wpdb;

        return $wpdb->base_prefix . 'summit_review_log';
    }

    private static function install(): void
    {
        if (\get_site_option('mb_summit_review_log_db') === self::DB_VERSION) {
            return;
        }

        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        \dbDelta('CREATE TABLE ' . self::table() . ' (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            created_at datetime NOT NULL,
            sent_at datetime NULL,
            status varchar(12) NOT NULL,
            http_status int(11) NOT NULL DEFAULT 0,
            attempts int(11) NOT NULL DEFAULT 0,
            email varchar(190) NOT NULL DEFAULT \'\',
            form_guid varchar(40) NOT NULL DEFAULT \'\',
            page_uri varchar(500) NOT NULL DEFAULT \'\',
            payload longtext NOT NULL,
            response text NULL,
            PRIMARY KEY  (id),
            KEY status (status),
            KEY email (email)
        ) ' . $wpdb->get_charset_collate() . ';');

        \update_site_option('mb_summit_review_log_db', self::DB_VERSION);
    }

    /**
     * Sends one payload to HubSpot. Returns [ok, http status, response text].
     *
     * @param  array<int,array<string,string>> $fields
     * @return array{0:bool,1:int,2:string}
     */
    private static function forward(string $form_guid, array $fields, string $page_uri, string $ip = ''): array
    {
        $context = ['pageUri' => $page_uri, 'pageName' => 'Summit pre-arrival form'];

        if ($ip !== '') {
            $context['ipAddress'] = $ip;
        }

        $response = \wp_remote_post(
            'https://api.hsforms.com/submissions/v3/integration/submit/' . self::PORTAL_ID . '/' . $form_guid,
            [
                'timeout' => 15,
                'headers' => ['Content-Type' => 'application/json'],
                'body' => \wp_json_encode(['fields' => $fields, 'context' => $context]),
            ]
        );

        if (\is_wp_error($response)) {
            return [false, 0, $response->get_error_message()];
        }

        $code = (int) \wp_remote_retrieve_response_code($response);

        return [$code === 200, $code, substr((string) \wp_remote_retrieve_body($response), 0, 2000)];
    }

    /**
     * @return \WP_REST_Response|\WP_Error
     */
    public static function handle(\WP_REST_Request $request)
    {
        global $wpdb;

        $form_guid = (string) $request->get_param('form');
        $page_uri = substr(\esc_url_raw((string) $request->get_param('pageUri')), 0, 500);
        $in = $request->get_param('fields');

        if (!preg_match('/^[0-9a-f]{8}(-[0-9a-f]{4}){3}-[0-9a-f]{12}$/i', $form_guid) || !is_array($in)
            || strlen((string) \wp_json_encode($in)) > self::MAX_BODY) {
            return new \WP_REST_Response(['ok' => false, 'reason' => 'bad_request'], 400);
        }

        $fields = [];
        foreach ($in as $f) {
            if (is_array($f) && isset($f['name'], $f['value']) && in_array($f['name'], self::ALLOWED, true)) {
                $fields[] = [
                    'objectTypeId' => '0-1',
                    'name' => $f['name'],
                    'value' => \sanitize_textarea_field((string) $f['value']),
                ];
            }
        }

        $email = '';
        foreach ($fields as $f) {
            if ($f['name'] === 'email') {
                $email = \sanitize_email($f['value']);
            }
        }

        if (!\is_email($email)) {
            return new \WP_REST_Response(['ok' => false, 'reason' => 'bad_email'], 400);
        }

        self::install();

        // Written BEFORE anything is forwarded. If this insert fails we say so
        // with a 500 and the page falls back to posting to HubSpot directly.
        $inserted = $wpdb->insert(self::table(), [
            'created_at' => \current_time('mysql'),
            'status' => 'received',
            'email' => substr($email, 0, 190),
            'form_guid' => $form_guid,
            'page_uri' => $page_uri,
            'payload' => \wp_json_encode($fields),
        ]);

        if (!$inserted) {
            return new \WP_REST_Response(['ok' => false, 'reason' => 'log_failed'], 500);
        }

        $id = (int) $wpdb->insert_id;
        $ip = isset($_SERVER['REMOTE_ADDR']) ? \sanitize_text_field(\wp_unslash($_SERVER['REMOTE_ADDR'])) : '';
        [$ok, $code, $body] = self::forward($form_guid, $fields, $page_uri, $ip);

        self::mark($id, $ok, $code, $body);

        if (!$ok) {
            // Visible to the host's PHP error log as well, so it is noticed.
            error_log('Summit review form: HubSpot refused submission #' . $id . ' (HTTP ' . $code . ')');
        }

        // The delegate is told it worked either way: we hold the answers.
        return new \WP_REST_Response(['ok' => true, 'logged' => true, 'forwarded' => $ok], 200);
    }

    private static function mark(int $id, bool $ok, int $code, string $body): void
    {
        global $wpdb;

        $wpdb->query($wpdb->prepare('UPDATE ' . self::table() . ' SET attempts = attempts + 1 WHERE id = %d', $id));

        $set = ['status' => $ok ? 'sent' : 'failed', 'http_status' => $code, 'response' => $body];
        if ($ok) {
            $set['sent_at'] = \current_time('mysql');
        }

        $wpdb->update(self::table(), $set, ['id' => $id]);
    }

    /**
     * Counts by status, and any rows that need attention.
     */
    public static function cli_status(): void
    {
        global $wpdb;
        self::install();

        $rows = $wpdb->get_results('SELECT status, COUNT(*) AS n FROM ' . self::table() . ' GROUP BY status', ARRAY_A);
        $counts = array_column($rows, 'n', 'status');
        \WP_CLI::log(sprintf('sent %d | failed %d | received (never forwarded) %d',
            $counts['sent'] ?? 0, $counts['failed'] ?? 0, $counts['received'] ?? 0));

        $bad = $wpdb->get_results('SELECT id, created_at, status, http_status, email FROM ' . self::table()
            . " WHERE status <> 'sent' ORDER BY id", ARRAY_A);

        if ($bad) {
            \WP_CLI\Utils\format_items('table', $bad, ['id', 'created_at', 'status', 'http_status', 'email']);
            \WP_CLI::warning(count($bad) . ' not delivered. Fix the cause, then: wp summit review-replay');
        }
    }

    /**
     * ## OPTIONS
     *
     * [--file=<path>]
     * : Write CSV here instead of stdout.
     *
     * @param array<int,string>    $args
     * @param array<string,string> $assoc_args
     */
    public static function cli_export(array $args, array $assoc_args): void
    {
        global $wpdb;
        self::install();

        $rows = $wpdb->get_results('SELECT id, created_at, sent_at, status, http_status, attempts, email, page_uri, payload, response FROM '
            . self::table() . ' ORDER BY id', ARRAY_A);
        $out = !empty($assoc_args['file']) ? fopen($assoc_args['file'], 'w') : fopen('php://output', 'w');
        fputcsv($out, array_keys($rows[0] ?? ['id' => 0]));
        foreach ($rows as $r) {
            fputcsv($out, $r);
        }
        fclose($out);
        \WP_CLI::success(count($rows) . ' rows exported.');
    }

    /**
     * Sends every undelivered submission to HubSpot again.
     *
     * ## OPTIONS
     *
     * [--id=<id>]
     * : Replay just this row.
     *
     * [--dry-run]
     * : List what would be sent without sending.
     *
     * @param array<int,string>    $args
     * @param array<string,string> $assoc_args
     */
    public static function cli_replay(array $args, array $assoc_args): void
    {
        global $wpdb;
        self::install();

        $where = !empty($assoc_args['id']) ? $wpdb->prepare('id = %d', (int) $assoc_args['id']) : "status <> 'sent'";
        $rows = $wpdb->get_results('SELECT * FROM ' . self::table() . ' WHERE ' . $where . ' ORDER BY id', ARRAY_A);
        $done = $left = 0;

        foreach ($rows as $r) {
            if (!empty($assoc_args['dry-run'])) {
                \WP_CLI::log('would send #' . $r['id'] . ' ' . $r['email']);
                continue;
            }

            [$ok, $code, $body] = self::forward($r['form_guid'], json_decode($r['payload'], true) ?: [], $r['page_uri']);
            self::mark((int) $r['id'], $ok, $code, $body);
            $ok ? $done++ : $left++;
            \WP_CLI::log(($ok ? 'sent   ' : 'FAILED ') . '#' . $r['id'] . ' ' . $r['email'] . ' (HTTP ' . $code . ')');
        }

        \WP_CLI::success("Replayed $done, still failing $left.");
    }
}
