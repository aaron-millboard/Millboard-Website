<?php

namespace Theme\Summit;

/**
 * WP-CLI commands for the Summit invite gate.
 *
 * The invite list is deliberately not committed to the theme: it is 152 partner
 * names, companies and email addresses, so it lives in the database and is
 * seeded from a file that stays outside git. Build that file with
 * `millboard-ops\summit-form\build_allowlist.py` and import it here.
 *
 *   wp summit import-invites "C:\...\summit-invites.json"
 *   wp summit import-invites ./invites.csv --dry-run
 *   wp summit status
 *   wp summit export --file=summit-registrations.csv
 */
class Cli
{
    public static function init(): void
    {
        if (!defined('WP_CLI') || !\WP_CLI) {
            return;
        }

        \WP_CLI::add_command('summit import-invites', [self::class, 'import_invites']);
        \WP_CLI::add_command('summit status', [self::class, 'status']);
        \WP_CLI::add_command('summit export', [self::class, 'export']);
    }

    /**
     * Replaces the invite allowlist from a JSON or CSV file.
     *
     * Replaces rather than merges, so removing someone from the spreadsheet
     * actually removes their access. Registrations already taken are untouched.
     *
     * ## OPTIONS
     *
     * <file>
     * : Path to the JSON built by build_allowlist.py, or a CSV with
     *   Category, Name, Company, Email columns.
     *
     * [--dry-run]
     * : Report what would be imported without writing anything.
     *
     * @param array<int,string>    $args
     * @param array<string,string> $assoc_args
     */
    public static function import_invites(array $args, array $assoc_args): void
    {
        $path = $args[0] ?? '';

        if (!is_readable($path)) {
            \WP_CLI::error('Cannot read ' . $path);
        }

        $rows = self::read_rows($path);

        if (empty($rows)) {
            \WP_CLI::error('No usable rows found in ' . $path);
        }

        if (!empty($assoc_args['dry-run'])) {
            // Normalise through the same code path so a dry run cannot
            // disagree with the real one.
            $preview = [];
            foreach ($rows as $row) {
                $email = InviteList::normalise_email((string) ($row['email'] ?? ''));
                if (!\is_email($email) || trim((string) ($row['company'] ?? '')) === '') {
                    continue;
                }
                $preview[$email] = InviteList::company_key((string) $row['company']);
            }
            \WP_CLI::log(sprintf(
                'Dry run: %d rows in, %d usable addresses, %d companies.',
                count($rows),
                count($preview),
                count(array_unique($preview))
            ));
            return;
        }

        $result = InviteList::replace($rows);

        foreach ($result['skipped'] as $reason) {
            \WP_CLI::warning('Skipped: ' . $reason);
        }

        $companies = count(array_unique(array_column(InviteList::all(), 'company_key')));

        \WP_CLI::success(sprintf(
            'Imported %d addresses across %d companies. Cap is %d per company.',
            $result['imported'],
            $companies,
            InviteList::cap_per_company()
        ));
    }

    /**
     * Reads either the JSON the builder emits or a raw CSV export.
     *
     * @return array<int,array<string,string>>
     */
    private static function read_rows(string $path): array
    {
        $raw = (string) file_get_contents($path);
        $rows = [];

        if (strtolower((string) pathinfo($path, PATHINFO_EXTENSION)) === 'json') {
            $decoded = json_decode($raw, true);
            $invites = $decoded['invites'] ?? $decoded;

            if (!is_array($invites)) {
                return [];
            }

            foreach ($invites as $email => $rec) {
                if (!is_array($rec)) {
                    continue;
                }
                $rows[] = [
                    'email' => is_string($email) ? $email : ($rec['email'] ?? ''),
                    'name' => $rec['name'] ?? '',
                    'company' => $rec['company'] ?? '',
                    'category' => $rec['category'] ?? '',
                ];
            }

            return $rows;
        }

        $handle = fopen($path, 'r');

        if (!$handle) {
            return [];
        }

        $header = null;

        while (($line = fgetcsv($handle)) !== false) {
            if ($header === null) {
                // Strip a UTF-8 BOM off the first cell, otherwise the first
                // column name never matches.
                $line[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string) $line[0]);
                $header = array_map(
                    static fn($h) => strtolower(trim((string) $h)),
                    $line
                );
                continue;
            }

            $assoc = [];
            foreach ($header as $i => $key) {
                $assoc[$key] = isset($line[$i]) ? trim((string) $line[$i]) : '';
            }

            $rows[] = [
                'email' => $assoc['email'] ?? '',
                'name' => $assoc['name'] ?? '',
                'company' => $assoc['company'] ?? '',
                'category' => $assoc['category'] ?? '',
            ];
        }

        fclose($handle);

        return $rows;
    }

    /** Where registration currently stands, per company and per date. */
    public static function status(): void
    {
        $invites = InviteList::all();
        $cap = InviteList::cap_per_company();
        $rows = Registrations::export_rows();

        \WP_CLI::log(sprintf(
            '%d invited addresses, %d companies, cap %d per company.',
            count($invites),
            count(array_unique(array_column($invites, 'company_key'))),
            $cap
        ));
        \WP_CLI::log(sprintf('%d people registered.', count($rows)));

        $by_date = [];
        $by_company = [];

        foreach ($rows as $row) {
            $by_date[$row['preferred_date']] = ($by_date[$row['preferred_date']] ?? 0) + 1;
            $by_company[$row['company']] = ($by_company[$row['company']] ?? 0) + 1;
        }

        foreach ($by_date as $date => $count) {
            \WP_CLI::log(sprintf('  %-16s %d', $date, $count));
        }

        $full = array_filter($by_company, static fn($n) => $n >= $cap);

        if (!empty($full)) {
            \WP_CLI::log(sprintf('%d companies are now full:', count($full)));
            foreach ($full as $company => $count) {
                \WP_CLI::log(sprintf('  %-38s %d', $company, $count));
            }
        }
    }

    /**
     * Writes every registration to CSV, one row per attendee, ready for the
     * post-close HubSpot import.
     *
     * ## OPTIONS
     *
     * [--file=<path>]
     * : Where to write. Defaults to stdout.
     *
     * @param array<int,string>    $args
     * @param array<string,string> $assoc_args
     */
    public static function export(array $args, array $assoc_args): void
    {
        $rows = Registrations::export_rows();

        if (empty($rows)) {
            \WP_CLI::warning('No registrations yet.');
            return;
        }

        $path = $assoc_args['file'] ?? 'php://stdout';
        $handle = fopen($path, 'w');

        if (!$handle) {
            \WP_CLI::error('Cannot write to ' . $path);
        }

        fputcsv($handle, array_keys($rows[0]));

        foreach ($rows as $row) {
            fputcsv($handle, $row);
        }

        if ($path !== 'php://stdout') {
            fclose($handle);
            \WP_CLI::success(sprintf('Wrote %d registrations to %s', count($rows), $path));
        }
    }
}
