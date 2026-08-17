<?php

namespace Granola\Components\DistributorContactHours;

/**
 * The contact card and opening hours row on a distributor / showroom /
 * experience-centre profile.
 *
 * The design draws these as two separate blocks side by side, but this theme
 * deliberately unregisters core/group, core/columns and core/column in the editor
 * (_src/scripts/editor/allowed-blocks.js), so there is no wrapper block to put a
 * pair in. Rather than allowlist a core block site-wide for one layout, the row is
 * a single block that renders both panels, which is how the rest of the theme works
 * anyway.
 *
 * The two panels remain separate components with their own markup and styles; they
 * simply no longer carry a block.json, so they are composed here instead of being
 * inserted individually.
 */
function filter_args(array $args): ?array
{
    $args = array_merge([
        'classes' => [],
        'contact_heading' => '',
        'contact_note' => '',
        'hours_heading' => '',
    ], $args);

    $args['classes'] = array_merge([
        'distributor-contact-hours',
        'wp-block',
    ], $args['classes']);

    $post_id = !empty($args['post_id']) ? (int) $args['post_id'] : \get_the_ID();
    if (!$post_id) {
        return empty($args['is_preview']) ? null : $args;
    }

    // Render both panels up front so the block can bail when neither has anything
    // to show, rather than outputting an empty grid.
    $args['contact'] = (string) \Granola\Component::get('distributor-contact-card', [
        'post_id' => $post_id,
        'heading' => $args['contact_heading'],
        'note' => $args['contact_note'],
    ]);

    $args['hours'] = (string) \Granola\Component::get('distributor-opening-hours', [
        'post_id' => $post_id,
        'heading' => $args['hours_heading'],
    ]);

    if (trim($args['contact']) === '' && trim($args['hours']) === '' && empty($args['is_preview'])) {
        return null;
    }

    return $args;
}
