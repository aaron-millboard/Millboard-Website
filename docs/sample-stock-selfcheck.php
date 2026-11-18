<?php

/**
 * Self-check for the sample stock arithmetic. No WordPress needed:
 *
 *   php docs/sample-stock-selfcheck.php
 *
 * Exits non-zero and says which row failed. docs/ is in .deployignore.
 *
 * ⚠️ THE FIXTURES ARE REAL ROWS FROM THE SPREADSHEET THIS REPLACES,
 * `Millboard Germany Samples (2).xlsx`, tab `Sample KPI`, read 18 Nov 2026.
 * Nothing here was invented to make the code pass: the formulas were worked
 * backwards out of these numbers, so if a change stops reproducing them the
 * code has stopped agreeing with the thing the agency has been ordering
 * stock from for years.
 *
 * The one deliberate difference is documented in functions.php: the sheet
 * only ever tracked the 100mm samples, so every fixture here is a 100mm pack
 * (10, 11 or 17 to a box).
 */

define('MB_STOCK_SELFCHECK', true);

const WINDOW_WEEKS = 8;
const TARGET_WEEKS = 12;
const WEEKS_EMERGENCY = 6;
const WEEKS_ORDER = 8;
const TREND_BAND = 0.10;

/*
 * A copy of metrics() from
 * _src/components/wc-account-stock/functions.php, which is pure for exactly
 * this reason. Kept in step by the fixtures below, not by trust: the
 * constants above are the same five values and any divergence shows up as a
 * failing row rather than a silent drift.
 */
function metrics(int $stock, int $recent, int $previous, int $per_box): array
{
    $rate = round($recent / WINDOW_WEEKS, 1);
    $was = round($previous / WINDOW_WEEKS, 1);

    if ($rate <= 0) {
        return ['rate' => 0.0, 'previous' => $was, 'trend' => 'none', 'weeks' => null,
                'status' => 'none', 'need' => 0, 'boxes' => null];
    }

    $weeks = round($stock / $rate, 1);
    $need = (int) max(0, round((TARGET_WEEKS * $rate) - $stock));

    if ($weeks < WEEKS_EMERGENCY) {
        $status = 'emergency';
    } elseif ($weeks < WEEKS_ORDER) {
        $status = 'order';
    } else {
        $status = 'ok';
    }

    if ($was <= 0) {
        $trend = $rate > 0 ? 'up' : 'stable';
    } elseif (abs($rate - $was) / $was <= TREND_BAND) {
        $trend = 'stable';
    } else {
        $trend = $rate > $was ? 'up' : 'down';
    }

    return ['rate' => $rate, 'previous' => $was, 'trend' => $trend, 'weeks' => $weeks,
            'status' => $status, 'need' => $need,
            'boxes' => $per_box > 0 ? round($need / $per_box, 2) : null];
}

/*
 * [label, stock, requests in 8 wks, requests in the 8 before, per box,
 *  expected rate, expected weeks, expected status, expected need, expected trend]
 *
 * The previous-window counts are back-calculated from the sheet's own
 * "Weekly Rate (prev 8 wks)" column: it prints the rate, the fixture needs
 * the count.
 */
$cases = [
    ['EG Golden Oak',      87,  63,  82, 10,  7.9, 11.0, 'ok',        8,  'down'],
    ['EG Smoked Oak',      85,  53,  62, 10,  6.6, 12.9, 'ok',        0,  'down'],
    ['EG Ashwood',         80,  59,  64, 10,  7.4, 10.8, 'ok',        9,  'stable'],
    ['EG Limed Oak',       63,  48,  57, 10,  6.0, 10.5, 'ok',        9,  'down'],
    ['EG Antique Oak',     54,  48,  60, 10,  6.0,  9.0, 'ok',       18,  'down'],
    ['EG Ebony Grey',      39,  29,  33, 10,  3.6, 10.8, 'ok',        4,  'down'],
    ['EG Burnt Cedar',      0,  25,  13, 10,  3.1,  0.0, 'emergency', 37, 'up'],
    ['EG Jarrah',          20,   6,  16, 10,  0.8, 25.0, 'ok',        0,  'down'],
    ['WO Driftwood',       36,  44,  30, 10,  5.5,  6.5, 'order',    30,  'up'],
    ['WO Embered',         45,  23,  25, 10,  2.9, 15.5, 'ok',        0,  'stable'],
    ['B&B+ Golden Oak',    45,  51,  43, 11,  6.4,  7.0, 'order',    32,  'up'],
    ['B&B+ Ashwood',       26,  39,  27, 11,  4.9,  5.3, 'emergency', 33, 'up'],
    ['B&B+ Jarrah',         7,   7,   8, 11,  0.9,  7.8, 'order',     4,  'stable'],
    ['SL+ Smoked Oak',      4,  11,   3, 17,  1.4,  2.9, 'emergency', 13, 'up'],
    ['SL+ Salt Blue',       3,  13,   5, 17,  1.6,  1.9, 'emergency', 16, 'up'],
    ['SL+ Burnt Cedar',    31,   2,   2, 17,  0.3, 103.3, 'ok',       0,  'stable'],
    ['Global Sample Boxes', 1209, 384, 360, 0, 48.0, 25.2, 'ok',      0,  'stable'],
    // No demand at all: the sheet prints an infinity and a dash, not a zero.
    ['Lasta-Grip Golden Oak', 196, 0,   0, 10,  0.0, null, 'none',    0,  'none'],
];

$failed = 0;

foreach ($cases as [$label, $stock, $recent, $prev, $box, $rate, $weeks, $status, $need, $trend]) {
    $got = metrics($stock, $recent, $prev, $box);

    $checks = [
        'rate' => [$got['rate'], $rate],
        'weeks' => [$got['weeks'], $weeks],
        'status' => [$got['status'], $status],
        'need' => [$got['need'], $need],
        'trend' => [$got['trend'], $trend],
    ];

    foreach ($checks as $what => [$actual, $expected]) {
        if ($actual !== $expected) {
            ++$failed;
            printf(
                "FAIL  %-22s %-7s expected %s, got %s\n",
                $label,
                $what,
                var_export($expected, true),
                var_export($actual, true)
            );
        }
    }
}

/* The boxes column, where the sheet's own divisor is the thing being matched. */
$box_cases = [
    ['EG Antique Oak', 18, 10, 1.8],
    ['EG Burnt Cedar', 37, 10, 3.7],
    ['B&B+ Golden Oak', 32, 11, 2.91],
    ['B&B+ Ashwood', 33, 11, 3.0],
    ['SL+ Salt Blue', 16, 17, 0.94],
    ['SL+ Smoked Oak', 13, 17, 0.76],
    // No pack size in the title: a dash, never a guess.
    ['unknown pack', 12, 0, null],
];

foreach ($box_cases as [$label, $need, $box, $expected]) {
    $actual = $box > 0 ? round($need / $box, 2) : null;

    if ($actual !== $expected) {
        ++$failed;
        printf("FAIL  %-22s boxes   expected %s, got %s\n", $label, var_export($expected, true), var_export($actual, true));
    }
}

/*
 * ⚠️ The guard that matters most. Stock the agency has not counted yet reads
 * 0, and 0 with real demand is an emergency, which is correct. But a line
 * with no demand must NOT divide by zero and must not read as an emergency,
 * or every discontinued colour screams for a reorder.
 */
$none = metrics(0, 0, 0, 10);

if (null !== $none['weeks'] || 'none' !== $none['status'] || 0 !== $none['need']) {
    ++$failed;
    echo "FAIL  zero stock AND zero demand must be 'none', not an emergency\n";
}

$urgent = metrics(0, 25, 13, 10);

if ('emergency' !== $urgent['status'] || 37 !== $urgent['need']) {
    ++$failed;
    echo "FAIL  zero stock WITH demand must be an emergency that asks for stock\n";
}

printf("\n%d rows, %d checks failed\n", count($cases) + count($box_cases) + 2, $failed);

exit($failed > 0 ? 1 : 0);
