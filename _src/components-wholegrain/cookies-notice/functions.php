<?php

namespace Granola\Components\CookiesNotice;

function filter_args($args): ?array
{
    // Set up defaults
    $args = array_merge([
        'attributes' => [],
        'classes' => [],
        'content' => '',
        'heading' => '',
    ], $args);

    $args['attributes'] = array_merge([
        'id' => 'site-cookies-notice',
        'aria-hidden' => 'true',
    ], $args['attributes']);

    $args['classes'] = array_merge([
        'cookies-notice',
    ], $args['classes']);

    if ($heading = \get_field('cookies_notice_heading', 'option')) {
        $args['heading'] = $heading;
    }

    if ($content = \get_field('cookies_notice_description', 'option')) {
        $args['content'] = $content;
    } elseif (!empty(\get_privacy_policy_url())) {
        $args['content'] = sprintf(
            // translators: cookie policy link.
            \__('We use cookies. Read more about them in our %s', 'granola'),
            \Granola\Component::get('link', [
                'content' => \_x('Privacy Policy', 'Cookie notice link text', 'granola'),
                'url'     => \get_privacy_policy_url(),
            ])
        );
    }

    // ----------------------------------------
    // Bail early - return null for no content.
    // ----------------------------------------
    if (empty($args['content'])) {
        return null;
    }

    // -------------------------------------------------------------------------
    // Return the content to the render functions
    // -------------------------------------------------------------------------
    return $args;
}
