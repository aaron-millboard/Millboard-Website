<?php

namespace Granola\Components\AdviceAuthorPanel;

use Theme\Utils\Advice;

function filter_args(array $args): ?array
{
    // -------------------------------------------------------------------------
    // Default arguments.
    // -------------------------------------------------------------------------
    $args = array_merge([
        'post_id' => 0,
        'classes' => [],
    ], $args);

    $post_id = (int) ($args['post_id'] ?: \get_the_ID());
    $args['person'] = $post_id > 0 ? Advice::person((int) \get_post_field('post_author', $post_id)) : null;

    // No author on record: no panel, rather than "About the author" over
    // nothing.
    if (!$args['person']) {
        return null;
    }

    $args['classes'] = array_merge(['advice-author-panel', 'wp-block'], $args['classes']);

    // The byline's "Written by" links here.
    if (empty($args['attributes']['id'])) {
        $args['attributes']['id'] = 'author';
    }

    $args['portrait'] = $args['person']['image'] ? [
        'attachment_id' => $args['person']['image'],
        'alt' => '',
        'size' => 'medium_large',
        'sizes' => '240px',
        'classes' => ['advice-author-panel__portrait'],
    ] : null;

    $args['role'] = $args['person']['role']
        ? sprintf(
            // translators: %s: an author's job title.
            \__('%s · Millboard', 'granola'),
            $args['person']['role']
        )
        : '';

    // -------------------------------------------------------------------------
    // Company facts, from Advice Articles > Advice Articles Settings. Typed
    // there once, by someone who can stand behind them, rather than per
    // article; left empty, the row is not drawn.
    // -------------------------------------------------------------------------
    $args['facts'] = [];
    $rows = function_exists('get_field') ? \get_field('advice_company_facts', 'option') : [];

    foreach (is_array($rows) ? $rows : [] as $row) {
        $value = trim((string) ($row['value'] ?? ''));
        $label = trim((string) ($row['label'] ?? ''));

        if ($value !== '' && $label !== '') {
            $args['facts'][] = ['value' => $value, 'label' => $label];
        }
    }

    return $args;
}
