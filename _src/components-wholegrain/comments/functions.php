<?php

namespace Granola\Components\Comments;

function filter_args(array $args): ?array
{
    // ---------------------------------------
    // Default arguments.
    // ---------------------------------------
    $args = array_merge([
        'classes' => [],
        'attributes' => [
            'id' => 'comments',
        ]
    ], $args);

    // ---------------------------------------
    // Bail early - return null for no output.
    // ---------------------------------------
    // The current post is password protected
    // and the visitor has not yet entered the
    // password, or comments aren't supported.
    // ---------------------------------------
    if (\post_password_required() || !\post_type_supports(\get_post_type(), 'comments')) {
        return null;
    }

    // ---------------------------------------
    // Bail early - return null for no output.
    // ---------------------------------------
    // Comments are closed and no existing
    // comments.
    // ---------------------------------------
    if (!\comments_open() && empty(\get_comments_number())) {
        return null;
    }

    // ---------------------------------------
    // Required classes.
    // ---------------------------------------
    $args['classes'] = array_merge([
        'comments',
        'alignwide',
    ], $args['classes']);

    $comment_count = \get_comments_number();

    if ($comment_count === 1) {
        $heading_content = sprintf(
            \__(
                // translators: post title.
                'One thought on &ldquo;%s&rdquo;',
                'granola'
            ),
            \Granola\Component::get('element', [
                'content' => \get_the_title(),
            ]),
        );
    } else {
        $heading_content = sprintf(
            \_nx(
                // translators: 1: quantity of comments. 2: post title.
                '%1$s thought on &ldquo;%2$s&rdquo;',
                '%1$s thoughts on &ldquo;%2$s&rdquo;',
                \get_comments_number(),
                'comments title',
                'granola'
            ),
            \number_format_i18n(\get_comments_number()),
            \Granola\Component::get('element', [
                'content' => \get_the_title(),
            ]),
        );
    }

    $args['heading'] = [
        'content' => $heading_content,
        'classes' => [
            'comments__heading',
        ],
    ];

    // Set a message if comments are closed but there are already comments.
    if (!\comments_open() && !empty(\get_comments_number())) {
        $args['closed_message'] = \__('Comments are closed.', 'granola');
    }

    // -------------------------------------------------------------------------
    // Return the filtered args.
    // -------------------------------------------------------------------------
    return $args;
}

/**
 * Filters the default comment form fields to remove the rarely needed 'website' field.
 *
 * @param array $fields An array of the default comment fields.
 * @return array The filtered array of comment fields.
 */
function remove_comment_form_website_field(array $fields): array
{
    // Remove 'website' field.
    unset($fields['url']);

    // Remove reference to the 'website' field from the consent message.
    if (!empty($fields['cookies'])) {
        $fields['cookies'] = str_replace(
            'Save my name, email, and website in this browser for the next time I comment.',
            \__('Save my name and email in this browser for the next time I comment.', 'granola'),
            $fields['cookies']
        );
    }

    return $fields;
}
