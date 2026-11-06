<?php

namespace Granola\Components\SummitFeedbackSurvey;

/**
 * Prepares the Summit feedback survey block.
 *
 * The questions are fixed in Theme\Summit\Feedback; only the language, the closing
 * date, the privacy link and the logo are editable here. Answers go to our own
 * endpoint (not HubSpot), so there is no form ID to set.
 */
function filter_args(array $args): ?array
{
    $args = array_merge([
        'classes' => [],
        'audience' => 'UK',
        'closing_date' => '',
        'privacy_url' => '',
        'logo_url' => '',
    ], $args);

    $args['classes'] = array_merge(['summit-feedback', 'wp-block'], $args['classes']);

    if (empty($args['attributes']['id'])) {
        $args['attributes']['id'] = 'summit-feedback-survey';
    }

    $args['is_fr'] = strtoupper(trim((string) $args['audience'])) === 'FR';

    return $args;
}
