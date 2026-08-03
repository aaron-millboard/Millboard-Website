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

/**
 * Normalise the repeater into a Monday-first week, marking today.
 *
 * Rows are keyed by day rather than read in order, because the repeater lets an
 * editor enter them in any sequence and a profile that lists Sunday first reads
 * like a mistake.
 *
 * @return array<int, array{day: string, is_today: bool, closed: bool, hours: string}>
 */
function week($rows): array
{
    $order = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
    $today = \current_time('l');

    $byDay = [];
    foreach ((array) $rows as $row) {
        $day = $row['day'] ?? '';
        if ($day !== '') {
            $byDay[$day] = $row;
        }
    }

    $week = [];

    foreach ($order as $day) {
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
            'is_today' => ($day === $today),
            'closed' => $closed,
            // En dash for the range, per the design. Written as an escape so the
            // character survives whatever encoding this file is edited in.
            'hours' => $closed ? \__('Closed', 'granola') : $open . " \u{2013} " . $close,
        ];
    }

    return $week;
}

/**
 * Resolve the record's hours into a live open-or-closed line.
 *
 * Shared with the location status block, which shows this beside the badges.
 *
 * @return array{label: string, state: string}|null
 */
function today_status(int $post_id): ?array
{
    $rows = \get_field('opening_hours', $post_id);

    if (empty($rows) || !is_array($rows)) {
        return null;
    }

    $today = \current_time('l');
    $now = (int) \current_time('H') * 60 + (int) \current_time('i');
    $separator = " \u{00B7} ";

    foreach ((array) $rows as $row) {
        if (($row['day'] ?? '') !== $today) {
            continue;
        }

        if (!empty($row['closed'])) {
            return ['label' => \__('Closed today', 'granola'), 'state' => 'closed'];
        }

        $openLabel = trim((string) ($row['open'] ?? ''));
        $closeLabel = trim((string) ($row['close'] ?? ''));
        $open = to_minutes($openLabel);
        $close = to_minutes($closeLabel);

        if ($open === null || $close === null) {
            return null;
        }

        if ($now < $open) {
            return [
                'label' => \__('Closed now', 'granola') . $separator . sprintf(\__('opens %s', 'granola'), $openLabel),
                'state' => 'closed',
            ];
        }

        if ($now >= $close) {
            return ['label' => \__('Closed for today', 'granola'), 'state' => 'closed'];
        }

        return [
            'label' => \__('Open now', 'granola') . $separator . sprintf(\__('closes %s', 'granola'), $closeLabel),
            'state' => 'open',
        ];
    }

    return null;
}

function to_minutes(string $time): ?int
{
    $time = trim($time);

    if ($time === '' || !preg_match('~^(\d{1,2})[:.]?(\d{2})?~', $time, $m)) {
        return null;
    }

    return ((int) $m[1]) * 60 + (int) ($m[2] ?? 0);
}
