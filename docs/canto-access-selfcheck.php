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

$asset = static fn ($value) => ['scheme' => 'image', 'additional' => ['Portal Access' => $value]];

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

echo $fail ? "{$fail} FAILED\n" : \count($cases) + \count($roles) + 1 . " checks passed\n";
exit($fail ? 1 : 0);
