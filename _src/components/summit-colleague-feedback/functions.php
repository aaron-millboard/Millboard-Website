<?php

namespace Granola\Components\SummitColleagueFeedback;

/**
 * Prepares the Summit colleague feedback block.
 *
 * The questions are fixed in Theme\Summit\ColleagueFeedback; only the logo is editable here.
 * Answers go to our own endpoint (not HubSpot), so there is nothing to wire up.
 */
function filter_args(array $args): ?array
{
    $args = array_merge([
        'classes' => [],
        'logo_url' => '',
    ], $args);

    $args['classes'] = array_merge(['summit-colleague', 'wp-block'], $args['classes']);

    if (empty($args['attributes']['id'])) {
        $args['attributes']['id'] = 'summit-colleague-feedback';
    }

    return $args;
}
