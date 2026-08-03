<?php

namespace Granola\Components\DistributorContactCard;

/**
 * Contact card for a distributor / showroom / experience centre.
 *
 * Per the design: the Millboard champion leads the card when one is set, and the
 * branch details sit below as a secondary row. When no champion is set the branch
 * actions are promoted to the primary buttons instead, so the card still earns its
 * place. That matters because none of the 376 production records has a champion
 * yet, while nearly all of them have a phone, email and website.
 *
 * Returns null only when there is nothing at all to contact.
 */
function filter_args(array $args): ?array
{
    $args = array_merge([
        'classes' => [],
        'heading' => '',
        'note' => '',
    ], $args);

    $args['classes'] = array_merge([
        'distributor-contact-card',
        'wp-block',
    ], $args['classes']);

    $post_id = !empty($args['post_id']) ? (int) $args['post_id'] : \get_the_ID();
    if (!$post_id) {
        return empty($args['is_preview']) ? null : $args;
    }

    // -------------------------------------------------------------------------
    // Branch details.
    // -------------------------------------------------------------------------
    $branchPhone = trim((string) \get_field('phone', $post_id));
    $branchEmail = trim((string) \get_field('email', $post_id));
    $branchSite = trim((string) \get_field('website', $post_id));

    // -------------------------------------------------------------------------
    // Champion.
    // -------------------------------------------------------------------------
    $champion = \get_field('champion', $post_id);
    $championName = is_array($champion) ? trim((string) ($champion['name'] ?? '')) : '';

    $args['champion'] = null;

    if ($championName !== '') {
        if (empty($args['heading'])) {
            $args['heading'] = \__('Millboard Champion', 'granola');
        }

        $role = trim((string) ($champion['role'] ?? ''));

        $args['champion'] = [
            'name' => $championName,
            // "Decking specialist - Westover Building Supplies" in the design.
            'role' => $role === ''
                ? \get_the_title($post_id)
                : $role . " \u{00B7} " . \get_the_title($post_id),
            'photo' => attachment_id($champion['photo'] ?? null),
        ];

        // Champion contact details fall back to the branch, since a champion is
        // often reachable only on the branch number.
        $args['actions'] = personal_actions(
            $championName,
            trim((string) ($champion['phone'] ?? '')) ?: $branchPhone,
            trim((string) ($champion['email'] ?? '')) ?: $branchEmail
        );
    } else {
        // No champion, so the branch takes the primary buttons.
        $args['actions'] = branch_actions($branchPhone, $branchEmail, $branchSite, true);
    }

    // -------------------------------------------------------------------------
    // Secondary branch row, only when the champion already owns the buttons.
    // -------------------------------------------------------------------------
    $args['branch'] = $args['champion']
        ? branch_actions($branchPhone, $branchEmail, $branchSite, false)
        : [];

    $args['branch_heading'] = \__('Branch contact', 'granola');

    if (empty($args['actions']) && empty($args['branch']) && empty($args['is_preview'])) {
        return null;
    }

    $args['classes'][] = $args['champion']
        ? 'distributor-contact-card--has-champion'
        : 'distributor-contact-card--branch-only';

    return $args;
}

/**
 * Normalise an ACF image value to an attachment ID.
 *
 * The Distributor Details image fields are set to return_format "array", so
 * get_field() hands back the whole attachment array rather than an ID, and the
 * theme's image component silently renders nothing when given an array.
 *
 * On this site that array carries the ID as `attachment_id`, not the `ID` / `id`
 * ACF documents, because the image data is filtered before it reaches us. All three
 * are accepted so the block does not break if that filtering changes or a field is
 * switched to return an ID.
 */
function attachment_id($value): ?int
{
    if (is_numeric($value)) {
        return (int) $value;
    }

    if (is_array($value)) {
        foreach (['attachment_id', 'ID', 'id'] as $key) {
            if (!empty($value[$key]) && is_numeric($value[$key])) {
                return (int) $value[$key];
            }
        }

        return null;
    }

    if (is_object($value) && !empty($value->ID)) {
        return (int) $value->ID;
    }

    return null;
}

/**
 * Primary buttons naming the champion, e.g. "Call Sarah".
 *
 * @return array<int, array<string, mixed>>
 */
function personal_actions(string $name, string $phone, string $email): array
{
    // First name only. A full name makes the button wrap on mobile.
    $first = trim((string) strtok($name, ' '));
    $actions = [];

    if ($phone !== '') {
        $actions[] = [
            /* translators: %s: the champion's first name. */
            'label' => sprintf(\__('Call %s', 'granola'), $first),
            'url' => 'tel:' . preg_replace('/[^0-9+]/', '', $phone),
            'icon' => 'phone',
            'primary' => true,
            'data' => 'champion_phone',
        ];
    }

    if ($email !== '') {
        $actions[] = [
            /* translators: %s: the champion's first name. */
            'label' => sprintf(\__('Email %s', 'granola'), $first),
            'url' => 'mailto:' . $email,
            'icon' => '',
            'primary' => false,
            'data' => 'champion_email',
        ];
    }

    return $actions;
}

/**
 * Branch contact actions.
 *
 * @param bool $primary Whether these are the card's main buttons rather than the
 *                      quiet secondary row.
 * @return array<int, array<string, mixed>>
 */
function branch_actions(string $phone, string $email, string $site, bool $primary): array
{
    $actions = [];

    if ($phone !== '') {
        $actions[] = [
            'label' => \__('Call us', 'granola'),
            'url' => 'tel:' . preg_replace('/[^0-9+]/', '', $phone),
            'icon' => 'phone',
            'primary' => $primary,
            'data' => 'phone',
        ];
    }

    if ($email !== '') {
        $actions[] = [
            'label' => \__('Email us', 'granola'),
            'url' => 'mailto:' . $email,
            'icon' => 'email',
            'primary' => false,
            'data' => 'email',
        ];
    }

    if ($site !== '') {
        $actions[] = [
            // The design shows the bare host rather than the full URL.
            'label' => host_label($site),
            'url' => $site,
            'icon' => 'external',
            'primary' => false,
            'external' => true,
            'data' => 'website',
        ];
    }

    return $actions;
}

/**
 * Strip a URL back to a readable host, e.g. "westoverbuilding.co.uk".
 */
function host_label(string $url): string
{
    $host = parse_url($url, PHP_URL_HOST);

    if (!$host) {
        // Not a parseable URL, so show it as entered rather than nothing.
        return rtrim(preg_replace('~^https?://~i', '', $url), '/');
    }

    return preg_replace('~^www\.~i', '', $host);
}
