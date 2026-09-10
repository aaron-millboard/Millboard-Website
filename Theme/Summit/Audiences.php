<?php

namespace Theme\Summit;

/**
 * The four Summit audiences, the days each one consumes, and the fields each
 * one is asked for.
 *
 * This is the single source of truth. The block renders its fields from here
 * and the gate validates against the same constants, so the form can never
 * offer a value the server would reject, and the day rules cannot drift
 * between the two.
 *
 * The days are NOT independent. Every INT, FR and US guest attends their days
 * TOGETHER and there is no partial version of their invitation, while no UK
 * guest attends the 3rd. So:
 *
 *   3rd = INT + FR + US
 *   4th = INT + FR + US + UK who chose the 4th
 *   5th = US + UK who chose the 5th
 *
 * Which means INT, FR and US share ONE cap of 60 between them (the 3rd limits
 * them), every non-UK registration takes a UK place off the 4th one for one,
 * and when the 3rd fills, INT, FR and US registration stops dead for all three
 * at once because none of them can be seated on a partial set of days.
 */
class Audiences
{
    public const UK = 'UK';
    public const INT = 'INT';
    public const US = 'US';
    public const FR = 'FR';

    public const ALL = [self::UK, self::INT, self::US, self::FR];

    /** The three days, exactly as they are stored. */
    public const DAY_3RD = '3rd November';
    public const DAY_4TH = '4th November';
    public const DAY_5TH = '5th November';

    public const ALL_DAYS = [self::DAY_3RD, self::DAY_4TH, self::DAY_5TH];

    /** Option holding the per-day seat cap, changeable without a deploy. */
    public const OPTION_CAP_PER_DAY = 'mb_summit_cap_per_day';
    public const DEFAULT_CAP_PER_DAY = 60;

    /**
     * Days each audience is automatically assigned.
     *
     * UK is the exception: UK guests attend ONE day and choose which, so their
     * days come from the submitted preference instead. An empty array here
     * means "ask them".
     */
    private const ASSIGNED_DAYS = [
        self::UK => [],
        self::INT => [self::DAY_3RD, self::DAY_4TH],
        self::FR => [self::DAY_3RD, self::DAY_4TH],
        self::US => [self::DAY_3RD, self::DAY_4TH, self::DAY_5TH],
    ];

    /** The days a UK guest may pick between. The 3rd is not offered to them. */
    public const UK_CHOOSABLE_DAYS = [self::DAY_4TH, self::DAY_5TH];

    /**
     * Which questions each audience is asked, matching the HubSpot forms these
     * replace. INT and US are field-identical; FR asks a different set again
     * and in French.
     */
    private const FIELDS = [
        self::UK => ['preferred_date', 'workshops', 'factory_tour', 'opt_out'],
        self::INT => ['opt_out'],
        self::US => ['opt_out'],
        self::FR => ['attending', 'email_contact', 'phone_contact'],
    ];

    public static function is_valid(string $audience): bool
    {
        return in_array($audience, self::ALL, true);
    }

    /** Does this audience choose its own day? */
    public static function chooses_day(string $audience): bool
    {
        return $audience === self::UK;
    }

    public static function asks_for(string $audience, string $field): bool
    {
        return in_array($field, self::FIELDS[$audience] ?? [], true);
    }

    /**
     * The days this registration would consume.
     *
     * @param  string $audience      UK / INT / US / FR.
     * @param  string $chosen_day    The UK guest's pick; ignored for the others.
     * @return array<int,string>     Day labels, or empty if the input is unusable.
     */
    public static function days_for(string $audience, string $chosen_day = ''): array
    {
        if (!self::is_valid($audience)) {
            return [];
        }

        if (self::chooses_day($audience)) {
            return in_array($chosen_day, self::UK_CHOOSABLE_DAYS, true) ? [$chosen_day] : [];
        }

        return self::ASSIGNED_DAYS[$audience];
    }

    /** How many seats a registration from this audience costs. */
    public static function seats_for(string $audience): int
    {
        return self::chooses_day($audience) ? 1 : count(self::ASSIGNED_DAYS[$audience] ?? []);
    }

    public static function cap_per_day(): int
    {
        // Network-wide: 60 is a room capacity shared by every locale, not a
        // per-subsite setting.
        $cap = (int) \get_site_option(self::OPTION_CAP_PER_DAY, self::DEFAULT_CAP_PER_DAY);

        return $cap > 0 ? $cap : self::DEFAULT_CAP_PER_DAY;
    }

    /** Human label for a locale-appropriate audience name, for internal notices. */
    public static function label(string $audience): string
    {
        $labels = [
            self::UK => 'UK',
            self::INT => 'International',
            self::US => 'US',
            self::FR => 'France',
        ];

        return $labels[$audience] ?? $audience;
    }
}
