<?php

/**
 * Self-check for the Resources ordering rules. No WordPress needed:
 *
 *   php docs/canto-order-selfcheck.php
 *
 * Exits non-zero and says which case failed. docs/ is in .deployignore.
 *
 * Covers::sort_containers() is pure when it is handed an order, which is why
 * it takes one. The cases that matter are the ones nobody would notice going
 * wrong: a tie, a position pointing at nothing, and whether the unplaced
 * containers keep the order Canto gave them.
 */

$fails = 0;
$checks = 0;

/** A copy of Covers::sort_containers(), which is pure for exactly this reason. */
function sort_containers(array $items, array $order): array
{
    if (!$order) {
        return array_values($items);
    }

    $placed = [];
    $rest = [];

    foreach (array_values($items) as $i => $item) {
        $id = is_array($item) ? (string) ($item['id'] ?? '') : '';

        if ('' !== $id && isset($order[$id])) {
            $placed[] = [$order[$id], $i, $item];
        } else {
            $rest[] = $item;
        }
    }

    usort($placed, static fn (array $a, array $b): int => [$a[0], $a[1]] <=> [$b[0], $b[1]]);

    return array_merge(array_column($placed, 2), $rest);
}

function names(array $items): string
{
    return implode(',', array_map(static fn ($i) => (string) ($i['id'] ?? '?'), $items));
}

function check(string $what, array $items, array $order, string $want): void
{
    global $fails, $checks;

    ++$checks;
    $got = names(sort_containers($items, $order));
    $ok = $got === $want;

    if (!$ok) {
        ++$fails;
    }

    printf("  %s %-54s got %-22s want %s\n", $ok ? 'ok  ' : 'FAIL', $what, $got, $want);
}

/* The real top level, in the order Canto gives it. */
$canto = [];

foreach (['01', '02', '03', '04', '05', 'About', 'Brand'] as $id) {
    $canto[] = ['id' => $id, 'name' => $id];
}

echo "RESOURCES ORDER\n" . str_repeat('=', 92) . "\n";

check('no order at all leaves Canto alone', $canto, [], '01,02,03,04,05,About,Brand');

check('one placed first', $canto, ['Brand' => 1], 'Brand,01,02,03,04,05,About');

check('the new album first, which is the actual ask', array_merge($canto, [['id' => '2027']]),
    ['2027' => 1], '2027,01,02,03,04,05,About,Brand');

check('two placed, lowest first', $canto, ['Brand' => 2, 'About' => 1], 'About,Brand,01,02,03,04,05');

check('negative sorts before 1, so there is room above', $canto,
    ['Brand' => 1, 'About' => -5], 'About,Brand,01,02,03,04,05');

/*
 * ⚠️ A TIE MUST NOT SHUFFLE. Two containers set to the same number is an
 * easy thing to do by hand, and a sort that broke ties by anything unstable
 * would reorder the panel between page loads for no visible reason.
 */
check('a tie keeps Canto order and does not shuffle', $canto,
    ['About' => 1, 'Brand' => 1], 'About,Brand,01,02,03,04,05');

check('...and the same the other way round', $canto,
    ['Brand' => 1, 'About' => 1], 'About,Brand,01,02,03,04,05');

/*
 * ⚠️ 0 IS A REAL POSITION, NOT "unset". The admin screen stores a blank box
 * as no row at all for this reason; if a blank were read as 0 it would pin
 * every untouched folder to the front.
 */
check('zero is a position, not an absence', $canto, ['Brand' => 0], 'Brand,01,02,03,04,05,About');

check('a position for something not in the list is ignored', $canto,
    ['Nope' => 1], '01,02,03,04,05,About,Brand');

check('unplaced containers keep Canto order among themselves', $canto,
    ['03' => 1], '03,01,02,04,05,About,Brand');

check('everything placed, nothing left over', [['id' => 'a'], ['id' => 'b']],
    ['b' => 1, 'a' => 2], 'b,a');

/* An item with no id at all must not be mistaken for a placed one. */
check('an item with no id falls through', [['name' => 'odd'], ['id' => 'x']],
    ['x' => 1], 'x,?');

echo "\n" . str_repeat('=', 92) . "\n";
printf("%d checks, %d failed\n", $checks, $fails);

exit($fails ? 1 : 0);
