<?php

namespace Granola\Components\AdviceAuthors;

use Theme\Utils\Advice;

function filter_args(array $args): ?array
{
    // -------------------------------------------------------------------------
    // Default arguments.
    // -------------------------------------------------------------------------
    $args = array_merge([
        'heading' => null,
        'intro' => null,
        'authors' => [],
        'classes' => [],
    ], $args);

    // -------------------------------------------------------------------------
    // Required classes.
    // -------------------------------------------------------------------------
    $args['classes'] = array_merge([
        'advice-authors',
        'wp-block',
    ], $args['classes']);

    $args['people'] = build_people((array) $args['authors']);

    // -------------------------------------------------------------------------
    // Return the filtered args.
    // -------------------------------------------------------------------------
    return $args;
}

/**
 * The author cards.
 *
 * The name and the count come from the user record: the name is their display
 * name, the count is the advice articles they author. Both are what the
 * articles themselves print in their bylines and schema, so the hub cannot
 * credit someone with a guide they did not write.
 *
 * Shared with the schema block.
 */
function build_people(array $rows): array
{
    $people = [];

    foreach ($rows as $index => $row) {
        $user = !empty($row['user']) ? \get_userdata((int) $row['user']) : false;

        if (!$user instanceof \WP_User) {
            continue;
        }

        $count = (int) \count_user_posts($user->ID, Advice::POST_TYPE, true);

        $people[] = [
            'user_id' => $user->ID,
            'name' => $user->display_name,
            'role' => trim((string) ($row['role'] ?? '')),
            'expertise' => trim((string) ($row['expertise'] ?? '')),
            'count' => $count ? Advice::guide_label($count) : '',
            'portrait' => !empty($row['portrait']['attachment_id']) ? [
                'attachment_id' => (int) $row['portrait']['attachment_id'],
                // The name sits directly under the portrait, so naming them
                // again in the alt text would only be read twice.
                'alt' => '',
                'size' => 'medium_large',
                'sizes' => '(max-width: 600px) 60vw, 300px',
                'classes' => ['advice-authors__portrait'],
            ] : null,
            'delay' => min($index, 3) * 120,
        ];
    }

    return $people;
}
