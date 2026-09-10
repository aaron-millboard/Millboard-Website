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
                    'audience' => $rec['audience'] ?? self::audience_from_category($rec['category'] ?? ''),
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
                'audience' => $assoc['audience'] ?? self::audience_from_category($assoc['category'] ?? ''),
            ];
        }

        fclose($handle);

        return $rows;
    }

    /**
     * Derives the audience from the Category column, for a CSV that has no
     * audience column of its own.
     *
     * The lists spell their categories inconsistently ("FR - Installer",
     * "FR - Installeur", "FR - Installateur", "Int", "US - Reseller"), so match
     * on the prefix only.
     */
    private static function audience_from_category(string $category): string
    {
        $c = strtolower(trim($category));

        foreach ([
            'uk' => Audiences::UK,
            'int' => Audiences::INT,
            'fr' => Audiences::FR,
            'us' => Audiences::US,
        ] as $prefix => $audience) {
            if (strpos($c, $prefix) === 0) {
                return $audience;
            }
        }

        return '';
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
        // Count declines separately. Lumping them in reads as "5 people
        // registered" while the audience tally below says 4, and on the FR list
        // (17 invited people) declines could easily outnumber acceptances.
        $declined = 0;
        foreach ($rows as $row) {
            if (($row['status'] ?? '') === Registrations::STATUS_DECLINED) {
                $declined++;
            }
        }

        \WP_CLI::log(sprintf(
            '%d people registered%s.',
            count($rows) - $declined,
            $declined > 0 ? sprintf(', %d declined', $declined) : ''
        ));

        // A populated per-subsite copy means the list was imported before the
        // network-wide change was deployed. InviteList::all() falls back to it
        // so nothing is broken, but two copies is one too many: re-import and
        // delete the stale one.
        $legacy = \get_option(InviteList::OPTION, []);

        if (is_array($legacy) && !empty($legacy)) {
            \WP_CLI::warning(sprintf(
                'A stale per-subsite copy of the allowlist is present on this blog (%d addresses), '
                . 'which means it was imported before the network-wide change was deployed. '
                . 'Re-run import-invites, then remove it with: '
                . 'wp option delete %s',
                count($legacy),
                InviteList::OPTION
            ));
        }

        // The per-day figures are the ones that matter, and they are not the
        // same as a headcount: an INT guest sits on two days and a US guest on
        // three. See Audiences for why the days are not independent.
        $day_cap = Audiences::cap_per_day();
        $used = Registrations::counts_by_day();

        \WP_CLI::log(sprintf('Seats, cap %d per day:', $day_cap));
        foreach ($used as $day => $count) {
            \WP_CLI::log(sprintf('  %-16s %3d used, %3d left%s',
                $day, $count, $day_cap - $count, $count >= $day_cap ? '   FULL' : ''));
        }

        // Everyone on the 3rd is also on the 4th, and no UK guest is on the
        // 3rd, so the 3rd's occupancy IS the combined non-UK headcount and it
        // comes straight off the UK's allowance on the 4th.
        $non_uk = $used[Audiences::DAY_3RD] ?? 0;
        \WP_CLI::log(sprintf('Non-UK guests booked (INT + FR + US): %d of %d', $non_uk, $day_cap));
        \WP_CLI::log(sprintf('UK places left on the 4th: %d',
            max(0, $day_cap - ($used[Audiences::DAY_4TH] ?? 0))));
        \WP_CLI::log(sprintf('UK places left on the 5th: %d',
            max(0, $day_cap - ($used[Audiences::DAY_5TH] ?? 0))));

        $by_audience = [];
        $by_company = [];

        foreach ($rows as $row) {
            if (($row['status'] ?? '') === Registrations::STATUS_DECLINED) {
                continue;
            }
            $by_audience[$row['audience']] = ($by_audience[$row['audience']] ?? 0) + 1;
            $by_company[$row['company']] = ($by_company[$row['company']] ?? 0) + 1;
        }

        \WP_CLI::log('People by audience:');
        foreach ($by_audience as $aud => $count) {
            \WP_CLI::log(sprintf('  %-4s %d', $aud, $count));
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
