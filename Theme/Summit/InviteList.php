<?php

namespace Theme\Summit;

/**
 * The Summit invite allowlist.
 *
 * Only people Millboard has personally invited may register, so every
 * submission is checked against this list server-side. The list is held in a
 * WordPress option rather than a file in the theme, for two reasons:
 *
 *  1. It is 152 partner names, companies and email addresses. That is personal
 *     data and it must not sit in git, and it must never be sent to the
 *     browser. Nothing in this class is exposed to the front end; the only
 *     public surface is Gate's endpoints, which answer yes or no.
 *  2. The list changes by hand right up to the event, and editing a WordPress
 *     option does not need a deploy.
 *
 * Seed or refresh it with the WP-CLI command registered in Cli.php.
 *
 * The cap is two PEOPLE PER COMPANY, so `company_key` is the load-bearing
 * field. It is derived from the Company column of the invite spreadsheet and
 * never from the email domain: the invite list holds 10 unrelated companies on
 * gmail.com and 3 on outlook.com, so a domain-keyed cap would admit two gmail
 * installers and wrongly refuse the other eight. It also has to survive the
 * opposite case, James Donaldson Timber, whose three invitees sit on three
 * different domains and are one company.
 */
class InviteList
{
    /** Option holding the allowlist, keyed by lowercased email. */
    public const OPTION = 'mb_summit_invites';

    /** Option holding the per-company cap, so it can be changed without a deploy. */
    public const OPTION_CAP = 'mb_summit_cap_per_company';

    /** Fallback cap if the option has never been set. */
    public const DEFAULT_CAP = 2;

    /**
     * Company names that normalisation cannot merge on its own, mapping the
     * variant onto the key it should share. Only ever populated on a human
     * decision: two names sharing a corporate domain may be one business or
     * two, and guessing either way changes how many places they get.
     *
     * @var array<string,string>
     */
    private const ALIASES = [
        // Both sit on covers.biz and Aaron confirmed on 10 Sep 2026 that they
        // are one business, so they share one allocation of places rather than
        // getting two.
        'wingham' => 'covers',
    ];

    public static function init(): void
    {
        // Nothing to hook. The list is passive data read by Gate.
    }

    /**
     * Normalises a company name into the key the cap is counted against.
     *
     * Lowercases, drops bracketed asides and punctuation, then strips trailing
     * company suffixes so "EH Smith" and "EH Smith Ltd" are one company rather
     * than two with two places each. The invite list contains exactly that
     * variation for EH Smith, JW Grant and Walker Landscapes.
     */
    public static function company_key(string $company): string
    {
        $key = strtolower($company);
        $key = preg_replace('/\(.*?\)/', ' ', $key);
        $key = str_replace('&', ' and ', $key);
        $key = preg_replace('/[^a-z0-9]+/', ' ', $key);
        $key = trim($key);

        // Strip any number of trailing suffixes, so "x timber ltd" -> "x timber".
        $suffixes = '/\s+(?:ltd|limited|llp|plc|inc|co|company|group|uk)$/';
        while (true) {
            $stripped = preg_replace($suffixes, '', $key);
            if ($stripped === $key) {
                break;
            }
            $key = $stripped;
        }

        $key = trim(preg_replace('/\s+/', ' ', $key));

        return self::ALIASES[$key] ?? $key;
    }

    /**
     * Normalises an email for lookup.
     *
     * The lookup has to be case-insensitive: 13 addresses on the invite
     * spreadsheet carry capitals (Alex@, Bob.Fleetwood@ and so on), and those
     * people will type their address in whatever case they please. Treating the
     * local part as case-sensitive would refuse a genuine invitee.
     */
    public static function normalise_email(string $email): string
    {
        return strtolower(trim($email));
    }

    /**
     * The whole allowlist, keyed by normalised email.
     *
     * @return array<string,array{name:string,company:string,company_key:string,category:string}>
     */
    public static function all(): array
    {
        // NETWORK-wide, not per-subsite. The four registration pages live on
        // three different subsites (UK and INT on en-gb, US on en-us, FR on
        // fr-fr), and get_option() would give each of them its own separate
        // copy of the list. One list, imported once.
        $list = \get_site_option(self::OPTION, []);

        return is_array($list) ? $list : [];
    }

    /**
     * Looks up one address on the allowlist.
     *
     * @return array{name:string,company:string,company_key:string,category:string}|null
     *         The invite record, or null if this address was not invited.
     */
    public static function find(string $email): ?array
    {
        $list = self::all();
        $key = self::normalise_email($email);

        return isset($list[$key]) && is_array($list[$key]) ? $list[$key] : null;
    }

    /** How many people from one company may attend. */
    public static function cap_per_company(): int
    {
        // Network-wide, like the list itself, so the cap cannot differ between
        // the locales the four pages sit on.
        $cap = (int) \get_site_option(self::OPTION_CAP, self::DEFAULT_CAP);

        return $cap > 0 ? $cap : self::DEFAULT_CAP;
    }

    /**
     * Replaces the allowlist wholesale.
     *
     * Rows are re-normalised here rather than trusted from the import file, so
     * a hand-edited file cannot introduce a company_key that disagrees with the
     * company name it sits beside.
     *
     * @param  array<int,array<string,string>> $rows Each with name, company, email, category.
     * @return array{imported:int,skipped:array<int,string>} What landed and what did not.
     */
    public static function replace(array $rows): array
    {
        $list = [];
        $skipped = [];

        foreach ($rows as $row) {
            $email = self::normalise_email((string) ($row['email'] ?? ''));
            $name = trim((string) ($row['name'] ?? ''));
            $company = trim((string) ($row['company'] ?? ''));

            // An address that is not a valid email can never be typed into the
            // form and matched, so importing it would create a silent
            // never-matches entry. The invite spreadsheet had one, info@PDS-LTD.
            if (!\is_email($email)) {
                $skipped[] = sprintf('%s (%s): unusable email %s', $name, $company, $email ?: '(blank)');
                continue;
            }

            if ($company === '') {
                $skipped[] = sprintf('%s: no company, so it cannot be capped', $email);
                continue;
            }

            // The spreadsheet lists three addresses twice. Keeping the first is
            // right: a repeated row is the same person, not a second place.
            if (isset($list[$email])) {
                continue;
            }

            // The audience decides which days this person consumes, so a row
            // without a usable one cannot be seated and must not be imported.
            $audience = strtoupper(trim((string) ($row['audience'] ?? '')));

            if (!Audiences::is_valid($audience)) {
                $skipped[] = sprintf(
                    '%s (%s): audience %s is not one of %s',
                    $email,
                    $company,
                    $audience !== '' ? $audience : '(blank)',
                    implode('/', Audiences::ALL)
                );
                continue;
            }

            $list[$email] = [
                'name' => $name,
                'company' => $company,
                'company_key' => self::company_key($company),
                'category' => trim((string) ($row['category'] ?? '')),
                'audience' => $audience,
            ];
        }

        \update_site_option(self::OPTION, $list);

        return ['imported' => count($list), 'skipped' => $skipped];
    }
}
