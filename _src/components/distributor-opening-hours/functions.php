<?php

namespace Granola\Components\DistributorOpeningHours;

/**
 * Opening hours card for a distributor / showroom / experience centre.
 *
 * Reads the record's own `opening_hours` repeater, so no editing is needed.
 * Returns null when the repeater is empty, which is currently the case for every
 * production record, so the card vanishes rather than rendering an empty table.
 */
function filter_args(array $args): ?array
{
    $args = array_merge([
        'classes' => [],
        'heading' => '',
        'notes' => '',
    ], $args);

    $args['classes'] = array_merge([
        'distributor-opening-hours',
        'wp-block',
    ], $args['classes']);

    $post_id = !empty($args['post_id']) ? (int) $args['post_id'] : \get_the_ID();
    $rows = $post_id ? \get_field('opening_hours', $post_id) : null;

    if ((empty($rows) || !is_array($rows)) && empty($args['is_preview'])) {
        return null;
    }

    if (empty($args['heading'])) {
        $args['heading'] = \__('Opening hours', 'granola');
    }

    if (empty($args['notes']) && $post_id) {
        $args['notes'] = (string) \get_field('opening_hours_notes', $post_id);
    }

    $args['days'] = week($rows);

    if (empty($args['days']) && empty($args['is_preview'])) {
        return null;
    }

    return $args;
}

const WEEK_ORDER = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];

/**
 * Normalise the repeater into a Monday-first week.
 *
 * Rows are keyed by day rather than read in order, because the repeater lets an
 * editor enter them in any sequence and a profile that lists Sunday first reads
 * like a mistake.
 *
 * Which row is today is deliberately NOT decided here. This markup is served from
 * the full page cache, so a "today" worked out in PHP is frozen at whatever the
 * clock said when the cache entry was written and goes on highlighting a Sunday
 * for the rest of the week. The browser marks it instead, see OpeningStatus.js.
 *
 * @return array<int, array{day: string, closed: bool, hours: string}>
 */
function week($rows): array
{
    $byDay = [];
    foreach ((array) $rows as $row) {
        $day = is_array($row) ? ($row['day'] ?? '') : '';
        if ($day !== '') {
            $byDay[$day] = $row;
        }
    }

    $week = [];

    foreach (WEEK_ORDER as $day) {
        if (!isset($byDay[$day])) {
            continue;
        }

        $row = $byDay[$day];
        $closed = !empty($row['closed']);
        $open = trim((string) ($row['open'] ?? ''));
        $close = trim((string) ($row['close'] ?? ''));

        if (!$closed && ($open === '' || $close === '')) {
            // Neither closed nor a usable range, so skip rather than show a blank.
            continue;
        }

        $week[] = [
            'day' => $day,
            'closed' => $closed,
            // En dash for the range, per the design. Written as an escape so the
            // character survives whatever encoding this file is edited in.
            'hours' => $closed ? \__('Closed', 'granola') : $open . " \u{2013} " . $close,
        ];
    }

    return $week;
}

/**
 * The week as a payload the browser can read the clock against, Monday first.
 *
 * Seven slots, one per day: null where the record has no row for that day, false
 * where it says closed, and "HH:MM-HH:MM" where it is open. Compact because the
 * finder page carries one of these per card, 187 of them on en-gb today.
 *
 * Returns '' when the record says nothing usable at all, so a caller can leave the
 * element out entirely rather than print an empty one.
 */
function week_payload($rows): string
{
    if (empty($rows) || !is_array($rows)) {
        return '';
    }

    $byDay = [];
    foreach ($rows as $row) {
        $day = is_array($row) ? ($row['day'] ?? '') : '';
        if ($day !== '') {
            $byDay[$day] = $row;
        }
    }

    $week = [];
    $usable = false;

    foreach (WEEK_ORDER as $day) {
        $row = $byDay[$day] ?? null;

        if ($row === null) {
            $week[] = null;

            continue;
        }

        if (!empty($row['closed'])) {
            $week[] = false;
            $usable = true;

            continue;
        }

        $open = trim((string) ($row['open'] ?? ''));
        $close = trim((string) ($row['close'] ?? ''));

        if ($open === '' || $close === '') {
            $week[] = null;

            continue;
        }

        $week[] = $open . '-' . $close;
        $usable = true;
    }

    return $usable ? (string) \wp_json_encode($week) : '';
}

/**
 * Wording and timezone for the browser-side status line.
 *
 * The strings stay here rather than in the script so they stay editable in Loco,
 * and they are passed through with their %s intact for the browser to fill, which
 * keeps every translation already entered against them working.
 *
 * The finder cards and the profile line are worded differently ("Open now until
 * 17:00" against "Open now / closes 17:00"). Both wordings are already translated,
 * so both are carried rather than one quietly becoming the other.
 */
function add_status_localization($localizations): array
{
    $separator = " \u{00B7} ";

    $localizations['opening_status'] = [
        // The partner's clock, not the visitor's. This is the site timezone, which is
        // what the PHP used, so the answer does not change for a visitor abroad.
        'timezone' => \wp_timezone()->getName(),
        'today_class' => 'distributor-opening-hours__row--today',
        'listing' => [
            'class' => 'map__listing__hours',
            'open' => \__('Open now until %s', 'granola'),
            'opens' => \__('Closed now, opens %s', 'granola'),
            'closed_now' => \__('Closed now', 'granola'),
            'closed_today' => \__('Closed today', 'granola'),
        ],
        'profile' => [
            'class' => 'distributor-location-status__open',
            'open' => \__('Open now', 'granola') . $separator . \__('closes %s', 'granola'),
            'opens' => \__('Closed now', 'granola') . $separator . \__('opens %s', 'granola'),
            'closed_now' => \__('Closed for today', 'granola'),
            'closed_today' => \__('Closed today', 'granola'),
        ],
    ];

    return $localizations;
}
