<?php

namespace Granola\Components\AdviceArticleByline;

use Theme\Utils\Advice;

function filter_args(array $args): ?array
{
    // -------------------------------------------------------------------------
    // Default arguments.
    // -------------------------------------------------------------------------
    $args = array_merge([
        'reviewer' => null,
        'review_date' => null,
        'post_id' => 0,
        'classes' => [],
    ], $args);

    $post_id = (int) ($args['post_id'] ?: \get_the_ID());

    if ($post_id <= 0) {
        return null;
    }

    $args['classes'] = array_merge(['advice-article-byline', 'wp-block'], $args['classes']);

    // -------------------------------------------------------------------------
    // People. The author is the article's own author, as Yoast names them in
    // the schema. A reviewer is shown only when one is named on the block,
    // and never the author reviewing their own article.
    // -------------------------------------------------------------------------
    $args['author'] = Advice::person((int) \get_post_field('post_author', $post_id));
    $args['author_url'] = '#author';

    if ($args['author'] && $args['author']['image']) {
        $args['author']['portrait'] = [
            'attachment_id' => $args['author']['image'],
            'alt' => '',
            'size' => 'thumbnail',
            'sizes' => '56px',
            'classes' => ['advice-article-byline__portrait'],
        ];
    }

    $reviewer = Advice::person((int) $args['reviewer']);
    $args['reviewer'] = $reviewer && (!$args['author'] || $reviewer['id'] !== $args['author']['id']) ? $reviewer : null;

    $format = \_x('j F Y', 'advice byline date format', 'granola');
    $review_time = $args['review_date'] ? strtotime((string) $args['review_date']) : false;

    $args['review_line'] = $args['reviewer']
        ? ($review_time
            ? sprintf(
                // translators: %s: the date an article was reviewed.
                \__('Technical review · %s', 'granola'),
                \wp_date($format, $review_time)
            )
            : \__('Technical review', 'granola'))
        : '';

    // -------------------------------------------------------------------------
    // Dates and length. "Updated" only when the article has been changed on a
    // later day than it went up.
    // -------------------------------------------------------------------------
    $published = (int) \get_post_time('U', true, $post_id);
    $modified = (int) \get_post_modified_time('U', true, $post_id);

    $args['published'] = sprintf(
        // translators: %s: the date an article was published.
        \__('Published %s', 'granola'),
        \wp_date($format, $published)
    );

    $args['updated'] = \wp_date('Y-m-d', $modified) !== \wp_date('Y-m-d', $published)
        ? sprintf(
            // translators: %s: the date an article was last updated.
            \__('Updated %s', 'granola'),
            \wp_date($format, $modified)
        )
        : '';

    $args['published_iso'] = \wp_date('c', $published);
    $args['updated_iso'] = \wp_date('c', $modified);

    $words = Advice::word_count($post_id);

    $args['length'] = sprintf(
        // translators: 1: reading time, e.g. "8 min read". 2: number of words.
        \_n('%1$s · %2$s word', '%1$s · %2$s words', $words, 'granola'),
        Advice::read_label($post_id),
        \number_format_i18n($words)
    );

    return $args;
}
