<?php

namespace Granola\Components\AdviceFilterBar;

use Theme\Utils\Advice;

function filter_args(array $args): ?array
{
    // -------------------------------------------------------------------------
    // Default arguments.
    // -------------------------------------------------------------------------
    $args = array_merge([
        'label' => null,
        'all_label' => null,
        'classes' => [],
    ], $args);

    $term = Advice::current_term(!empty($args['is_preview']));

    if (!$term) {
        return null;
    }

    // -------------------------------------------------------------------------
    // One chip per category below this one that has guides in it. The design
    // also draws chips for empty topics, each opening on "nothing published
    // yet"; a chip that only ever leads to that is left out instead, and turns
    // up by itself once a guide is filed there.
    // -------------------------------------------------------------------------
    $total = Advice::guide_count([$term->term_id]);
    $labels = Advice::topic_labels($term);
    $args['chips'] = [];
    $narrows = false;

    foreach (Advice::descendants($term) as $child) {
        $count = Advice::guide_count([$child->term_id]);

        if (!$count) {
            continue;
        }

        $args['chips'][] = [
            'id' => $child->term_id,
            'slug' => $child->slug,
            'label' => $labels[$child->term_id] ?? Advice::term_name($child),
        ];

        $narrows = $narrows || $count < $total;
    }

    // Nothing to filter between: a category with no topics, or one whose only
    // topic holds every guide, shows the grid without a bar above it.
    if (!$narrows) {
        return null;
    }

    $args['classes'] = array_merge(['advice-filter-bar', 'wp-block'], $args['classes']);
    $args['label'] = $args['label'] ?: \__('Filter by topic', 'granola');
    $args['all_label'] = $args['all_label'] ?: \__('All guides', 'granola');

    // The script fills in the first number as the chips are pressed; the
    // total is fixed, so the plural is settled here.
    $args['status_template'] = sprintf(
        // translators: 1: guides now shown, 2: guides in the category.
        \_n('Showing %1$s of %2$s guide', 'Showing %1$s of %2$s guides', $total, 'granola'),
        '{shown}',
        number_format_i18n($total)
    );
    $args['status'] = str_replace('{shown}', number_format_i18n($total), $args['status_template']);
    $args['label_id'] = \wp_unique_id('advice-filter-label-');

    return $args;
}
