<?php

namespace Granola\Components\IntroTabs;

/**
 * Introductory copy for a category page, held in a tab set.
 *
 * The pages winning these searches all carry several hundred words above the
 * product grid. Tabs let that copy exist without the first product falling off
 * the screen. Every panel is in the markup whether or not it is the open one,
 * so the copy is there without JavaScript and for anything reading the page.
 */
function filter_args(array $args): ?array
{
    // -------------------------------------------------------------------------
    // Default arguments.
    // -------------------------------------------------------------------------
    $args = array_merge([
        'classes' => [],
        'tabs' => [],
        'uid' => \wp_unique_id('intro-tabs-'),
    ], $args);

    // -------------------------------------------------------------------------
    // Required classes.
    // -------------------------------------------------------------------------
    $args['classes'] = array_merge([
        'intro-tabs',
        'wp-block',
        'alignfull',
    ], $args['classes']);

    // -------------------------------------------------------------------------
    // Drop empty rows, then give each tab the ids the ARIA pattern needs.
    // -------------------------------------------------------------------------
    $tabs = [];

    foreach ($args['tabs'] as $tab) {
        if (empty($tab['label']) || empty($tab['content'])) {
            continue;
        }

        $index = count($tabs);

        $tabs[] = [
            'label' => $tab['label'],
            'content' => $tab['content'],
            'tab_id' => $args['uid'] . '-tab-' . $index,
            'panel_id' => $args['uid'] . '-panel-' . $index,
            'active' => $index === 0,
        ];
    }

    $args['tabs'] = $tabs;

    // -------------------------------------------------------------------------
    // Bail early if there is nothing to show.
    // -------------------------------------------------------------------------
    if (empty($args['tabs'])) {
        return null;
    }

    return $args;
}
