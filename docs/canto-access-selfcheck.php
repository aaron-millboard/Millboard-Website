<?php

/**
 * Self-check for Theme\Canto\Access. No WordPress needed:
 *
 *   php docs/canto-access-selfcheck.php
 *
 * Exits non-zero and says which case failed. docs/ is in .deployignore.
 */

require __DIR__ . '/../Theme/Canto/Access.php';

use Theme\Canto\Access;

$staff_roles = ['administrator', 'editor', 'shop_manager', 'millboard_staff'];
$staff = Access::audiences(['administrator'], $staff_roles);
$dist = Access::audiences(['millboard_distributor'], $staff_roles);
$inst = Access::audiences(['millboard_installer'], $staff_roles);

// Usage rights default to cleared, so these cases keep testing Portal Access
// alone. The usage gate has its own cases below, which vary the second argument.
$asset = static fn ($value, $usage = [Access::OPEN_USAGE]) => [
    'scheme' => 'image',
    'additional' => [Access::FIELD => $value, Access::USAGE_FIELD => $usage],
];

$cases = [
    // [label, item, audiences, expected]
    ['embargoed hidden from staff', $asset(['Embargoed']), $staff, false],
    ['embargoed beats every other value', $asset(['Embargoed', 'Distributor', 'Internal staff']), $staff, false],
    ['embargoed hidden from distributor', $asset(['Embargoed']), $dist, false],
    ['empty hidden from staff', $asset([]), $staff, false],
    ['null hidden from staff', $asset(null), $staff, false],
    ['no additional block hidden from staff', ['scheme' => 'image'], $staff, false],
    ['field absent hidden from staff', ['scheme' => 'image', 'additional' => ['SKU' => 'X']], $staff, false],
    ['staff-only visible to staff', $asset(['Internal staff']), $staff, true],
    ['staff-only hidden from distributor', $asset(['Internal staff']), $dist, false],
    ['staff-only hidden from installer', $asset(['Internal staff']), $inst, false],
    ['distributor-only visible to distributor', $asset(['Distributor']), $dist, true],
    ['distributor-only hidden from installer', $asset(['Distributor']), $inst, false],
    ['distributor-only visible to staff', $asset(['Distributor']), $staff, true],
    ['installer-only visible to installer', $asset(['Installer']), $inst, true],
    ['all three visible to installer', $asset(['Distributor', 'Installer', 'Internal staff']), $inst, true],
    ['plain string value works', $asset('Distributor'), $dist, true],
    ['unknown value hidden from partner', $asset(['Everyone']), $dist, false],
    ['unknown value hidden from staff', $asset(['Everyone']), $staff, false],
    ['logged out sees nothing', $asset(['Distributor', 'Installer', 'Internal staff']), [], false],
    ['folder always passes', ['scheme' => 'folder'], $dist, true],
    ['album always passes', ['scheme' => 'album'], [], true],
    ['document is filtered too', ['scheme' => 'document', 'additional' => ['Portal Access' => ['Embargoed']]], $staff, false],
    ['video is filtered too', ['scheme' => 'video'], $dist, false],

    // ---- usage rights: the second gate ----------------------------------
    // Only the single value OPEN_USAGE passes, for everyone including staff.
    ['open usage visible to staff', $asset(['Internal staff'], [Access::OPEN_USAGE]), $staff, true],
    ['open usage visible to distributor', $asset(['Distributor'], [Access::OPEN_USAGE]), $dist, true],
    ['missing usage rights hidden from staff', $asset(['Internal staff'], []), $staff, false],
    ['missing usage rights hidden from partner', $asset(['Distributor'], []), $dist, false],
    ['null usage rights hidden', $asset(['Internal staff'], null), $staff, false],
    ['internal-use-only hidden from staff', $asset(['Internal staff'], ['Internal use or project reference only']), $staff, false],
    ['approval-needed hidden from staff', $asset(['Internal staff'], ['Approval needed from Global Marketing']), $staff, false],
    ['rights-unknown hidden from staff', $asset(['Internal staff'], ['Usage rights unknown']), $staff, false],
    // The combinations Aaron ruled on: open usage must stand alone.
    ['open usage plus internal-only is hidden', $asset(['Internal staff'], [Access::OPEN_USAGE, 'Internal use or project reference only']), $staff, false],
    ['open usage plus paid media is hidden', $asset(['Distributor'], ['Paid Media Approved', Access::OPEN_USAGE]), $dist, false],
    ['open usage plus attribution needed is hidden', $asset(['Distributor'], [Access::OPEN_USAGE, 'Photographer or supplier attribution needed']), $dist, false],
    // A value nobody has seen yet must fail closed, not sneak through.
    ['an unknown future usage value is hidden', $asset(['Internal staff'], ['Some new option']), $staff, false],
    ['plain string usage value works', $asset(['Distributor'], Access::OPEN_USAGE), $dist, true],
    // Containers carry no usage rights and must still pass.
    ['folder passes without usage rights', ['scheme' => 'folder'], $dist, true],
];

$roles = [
    ['administrator', [Access::STAFF]],
    ['editor', [Access::STAFF]],
    ['millboard_distributor', [Access::DISTRIBUTOR]],
    ['millboard_installer', [Access::INSTALLER]],
    ['millboard_architect', [Access::INSTALLER]],
    ['millboard_asset_viewer', [Access::INSTALLER]],
    ['customer', []],
    ['some_future_role', []],
];

$fail = 0;

foreach ($cases as [$label, $item, $audiences, $expected]) {
    if (Access::visible($item, $audiences) !== $expected) {
        echo "FAIL: {$label}\n";
        ++$fail;
    }
}

foreach ($roles as [$role, $expected]) {
    if (Access::audiences([$role], $staff_roles) !== $expected) {
        echo "FAIL: role {$role}\n";
        ++$fail;
    }
}

$mixed = Access::filter([$asset(['Embargoed']), $asset(['Distributor']), ['scheme' => 'folder']], $dist);

if (2 !== \count($mixed)) {
    echo "FAIL: filter keeps 2 of 3\n";
    ++$fail;
}

// partner index rules
$pv = [
    ['distributor value reaches partners', $asset(['Distributor']), true],
    ['installer value reaches partners', $asset(['Installer']), true],
    ['all three reach partners', $asset(['Distributor', 'Installer', 'Internal staff']), true],
    ['staff-only never in the partner index', $asset(['Internal staff']), false],
    ['embargoed never in the partner index', $asset(['Embargoed', 'Distributor']), false],
    ['empty never in the partner index', $asset([]), false],
    ['word elsewhere is not enough', ['scheme' => 'image', 'name' => 'Distributor Events', 'additional' => ['Portal Access' => ['Internal staff']]], false],
    ['containers never in the partner index', ['scheme' => 'album', 'additional' => ['Portal Access' => ['Distributor']]], false],
    // The usage gate applies here too, or a restricted asset would be indexed
    // for partners and served from the index without ever being re-checked.
    ['restricted usage never in the partner index', $asset(['Distributor'], ['Internal use or project reference only']), false],
    ['missing usage rights never in the partner index', $asset(['Distributor'], []), false],
];

foreach ($pv as [$label, $item, $expected]) {
    if (Access::partner_visible($item) !== $expected) {
        echo "FAIL: {$label}\n";
        ++$fail;
    }
}

$full = [
    'id' => 'abc', 'scheme' => 'image', 'name' => 'Deck.jpg',
    'url' => ['previewURI240' => 'p', 'directUrlOriginal' => 'o', 'detail' => 'd', 'download' => 'secret'],
    'additional' => ['Portal Access' => ['Distributor'], 'Usage Rights' => [Access::OPEN_USAGE], 'SKU' => 'X'],
    'relatedAlbums' => [['idPath' => 'N0G8G/KFTUP'], ['idPath' => '05/12345']],
];
$c = Access::compact($full);

if (['N0G8G', 'KFTUP', '05', '12345'] !== $c['albums'] || $c['albums'] !== \array_map('strval', $c['albums'])) {
    echo "FAIL: compact keeps every folder and album id as a string\n";
    ++$fail;
}

if (isset($c['url']['download']) || isset($c['additional']['SKU']) || ['Distributor'] !== $c['additional']['Portal Access']) {
    echo "FAIL: compact keeps only what the panel renders\n";
    ++$fail;
}

if (!Access::visible($c, $dist) || Access::visible($c, $inst)) {
    echo "FAIL: a compact record is filtered exactly like the full one\n";
    ++$fail;
}

// The trap this guards: compact() used to keep Portal Access only. Once usage
// rights became a second gate, a record that dropped them failed usage_cleared()
// and every indexed asset would have vanished for partners.
if ([Access::OPEN_USAGE] !== ($c['additional'][Access::USAGE_FIELD] ?? null)) {
    echo "FAIL: compact carries Usage Rights, or the index cannot be filtered\n";
    ++$fail;
}

$restricted = Access::compact(\array_replace_recursive($full, [
    'additional' => ['Usage Rights' => ['Internal use or project reference only']],
]));

if (Access::visible($restricted, $dist) || Access::visible($restricted, $staff)) {
    echo "FAIL: a compacted restricted-usage asset stays hidden\n";
    ++$fail;
}

echo $fail ? "{$fail} FAILED\n" : \count($cases) + \count($roles) + \count($pv) + 4 . " checks passed\n";
exit($fail ? 1 : 0);
