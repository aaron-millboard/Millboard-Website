<?php

namespace Granola\Components\SpecCredentials;

/**
 * Credentials row for a specifier page: who wrote it, who checked it, and when
 * it was published and last updated.
 *
 * It is the Advice Centre's article byline idea, for pages. The Advice Centre
 * reads the author from the post and the reviewer from a user picker, and user
 * ids are not the same on every environment (the local and staging copies hold
 * different people at the ids production uses). So here the person is named
 * on the block and looked up on the site by the name on their profile; their
 * bio, job title and portrait are read from that profile when it has them.
 *
 * The review line is a claim that someone has checked the page, so it is only
 * drawn when a reviewer and the day of the review are both given, and never
 * for the author reviewing their own page.
 */
function filter_args(array $args): ?array
{
    // -------------------------------------------------------------------------
    // Default arguments.
    // -------------------------------------------------------------------------
    $args = array_merge([
        'classes' => [],
        'attributes' => [],
        'author' => '',
        'author_role' => '',
        'reviewer' => '',
        'reviewer_role' => '',
        'review_date' => '',
    ], $args);

    $author = person((string) $args['author'], (string) $args['author_role']);

    // No named author: nothing, rather than a "Written by" over nobody.
    if (!$author) {
        return null;
    }

    $args['author_person'] = $author;

    // -------------------------------------------------------------------------
    // The reviewer: named, dated, and not the author.
    // -------------------------------------------------------------------------
    $format = \_x('j F Y', 'specifier page byline date format', 'granola');
    $reviewer = person((string) $args['reviewer'], (string) $args['reviewer_role']);
    $review_time = $args['review_date'] ? strtotime((string) $args['review_date']) : false;

    $args['reviewer_person'] = ($reviewer && $review_time && strcasecmp($reviewer['name'], $author['name']) !== 0) ? $reviewer : null;
    $args['review_line'] = $args['reviewer_person']
        ? sprintf(
            // translators: %s: the date a page was technically reviewed.
            \__('Technical review · %s', 'granola'),
            \wp_date($format, $review_time)
        )
        : '';

    // -------------------------------------------------------------------------
    // Dates: when the page went up, and when it last changed (shown only when
    // that is a later day than it went up).
    // -------------------------------------------------------------------------
    $post_id = (int) \get_the_ID();
    $args['published'] = '';
    $args['updated'] = '';

    if ($post_id > 0) {
        $published = (int) \get_post_time('U', true, $post_id);
        $modified = (int) \get_post_modified_time('U', true, $post_id);

        $args['published'] = sprintf(
            // translators: %s: the date a page was published.
            \__('Published %s', 'granola'),
            \wp_date($format, $published)
        );
        $args['published_iso'] = \wp_date('c', $published);

        if (\wp_date('Y-m-d', $modified) !== \wp_date('Y-m-d', $published)) {
            $args['updated'] = sprintf(
                // translators: %s: the date a page was last updated.
                \__('Updated %s', 'granola'),
                \wp_date($format, $modified)
            );
            $args['updated_iso'] = \wp_date('c', $modified);
        }
    }

    // -------------------------------------------------------------------------
    // Required classes. Spring Wood unless the editor picks another background.
    // -------------------------------------------------------------------------
    $args['classes'] = array_merge([
        'spec-credentials',
        'wp-block',
        'alignfull',
    ], $args['classes']);

    if (empty($args['background_color']) || $args['background_color'] === 'none') {
        $args['classes'][] = 'has-brand-2-background-color';
        $args['classes'][] = 'has-background';
    }

    return $args;
}

/**
 * A named person, with what their profile holds.
 *
 * @return array{name: string, role: string, bio: string, initials: string, portrait: array|null}|null
 */
function person(string $name, string $role = ''): ?array
{
    $name = trim($name);

    if ($name === '') {
        return null;
    }

    $user = find_user($name);
    $profile_role = '';
    $bio = '';
    $portrait = null;

    if ($user) {
        $profile_role = function_exists('get_field') ? trim((string) \get_field('advice_job_title', 'user_' . $user->ID)) : '';
        $bio = trim((string) \get_the_author_meta('description', $user->ID));
        $image = function_exists('get_field') ? \get_field('user_image', 'user_' . $user->ID) : null;
        $image_id = (int) (is_array($image) ? ($image['attachment_id'] ?? 0) : $image);

        if ($image_id > 0) {
            $portrait = [
                'attachment_id' => $image_id,
                // The name is printed beside the portrait, so it is decorative.
                'alt' => '',
                'size' => 'thumbnail',
                'sizes' => '56px',
                'classes' => ['spec-credentials__portrait'],
            ];
        }
    }

    $words = preg_split('/\s+/u', $name);

    return [
        'name' => $name,
        'role' => trim($role) !== '' ? trim($role) : $profile_role,
        'bio' => $bio,
        'initials' => mb_strtoupper(mb_substr($words[0], 0, 1) . (count($words) > 1 ? mb_substr(end($words), 0, 1) : '')),
        'portrait' => $portrait,
    ];
}

/**
 * The staff profile whose name is this, on any site of the network.
 *
 * More than one account can share a name (an imported partner or customer, say,
 * and display names can be edited). Only an account with a job title on its
 * profile is a staff profile, so only that one is used. With none, nothing is
 * returned: the byline still shows the name and job title typed on the block,
 * and no stranger's bio or portrait is printed under it.
 *
 * Looked up once per name per request (the byline and the schema both ask).
 */
function find_user(string $name): ?\WP_User
{
    static $found = [];

    $key = strtolower($name);

    if (array_key_exists($key, $found)) {
        return $found[$key];
    }

    $found[$key] = null;

    if (!function_exists('get_field')) {
        return null;
    }

    $users = \get_users([
        'search' => $name,
        'search_columns' => ['display_name'],
        'blog_id' => 0,
        'number' => 5,
    ]);

    foreach ($users as $user) {
        if (strcasecmp($user->display_name, $name) === 0 && trim((string) \get_field('advice_job_title', 'user_' . $user->ID)) !== '') {
            $found[$key] = $user;
            break;
        }
    }

    return $found[$key];
}
