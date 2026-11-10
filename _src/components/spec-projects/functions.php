<?php

namespace Granola\Components\SpecProjects;

/**
 * Projects rail for specifier pages.
 *
 * The rail, the card and its photograph, place caption, title and product line
 * are the home-projects block's own: this block asks that block for each card
 * (so the photograph, place and boards are still read off the case study) and
 * draws it with the same classes. What it adds is up to three labelled detail
 * rows under a card (design team, client, constraint) that the editor types per
 * project. home-projects is used on live pages and is left alone.
 */
function filter_args(array $args): ?array
{
    // -------------------------------------------------------------------------
    // Default arguments.
    // -------------------------------------------------------------------------
    $args = array_merge([
        'classes' => [],
        'attributes' => [],
        'preheading' => '',
        'heading' => '',
        'link' => [],
        'cards' => [],
    ], $args);

    // -------------------------------------------------------------------------
    // The cards, each read off its case study.
    // -------------------------------------------------------------------------
    if (!function_exists('\\Granola\\Components\\HomeProjects\\build_card')) {
        $file = \get_theme_file_path('assets/components/home-projects/functions.php');

        if (file_exists($file)) {
            require_once $file;
        }
    }

    if (!function_exists('\\Granola\\Components\\HomeProjects\\build_card')) {
        return null;
    }

    $projects = [];

    foreach ((array) $args['cards'] as $card) {
        $built = \Granola\Components\HomeProjects\build_card((int) ($card['case_study'] ?? 0));

        if (!$built) {
            continue;
        }

        $built['details'] = array_slice(array_values(array_filter((array) ($card['details'] ?? []), static function ($row) {
            return !empty($row['label']) && !empty($row['value']);
        })), 0, 3);

        $projects[] = $built;
    }

    if (empty($projects)) {
        return null;
    }

    $args['projects'] = $projects;

    // -------------------------------------------------------------------------
    // Link beside the heading.
    // -------------------------------------------------------------------------
    if (!empty($args['link']['url'])) {
        $args['link']['classes'][] = 'home-projects__all';
    } else {
        $args['link'] = [];
    }

    // -------------------------------------------------------------------------
    // Classes: the home-projects root classes bring its look and its scripts.
    // -------------------------------------------------------------------------
    // alignfull: home-projects gets it from its block.json default, which this block does not have, so the band would sit inset.
    $args['classes'] = array_merge([
        'home-projects',
        'spec-projects',
        'wp-block',
        'alignfull',
    ], $args['classes']);

    return $args;
}
