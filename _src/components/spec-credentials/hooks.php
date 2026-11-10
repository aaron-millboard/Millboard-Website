<?php

namespace Granola\Components\SpecCredentials;

\add_filter('granola/component/spec-credentials', __NAMESPACE__ . '\\filter_args');
\add_filter('wpseo_schema_graph', __NAMESPACE__ . '\\filter_schema_graph', 10, 2);

/**
 * Say in the page's structured data what the page shows: who wrote it and,
 * when named and dated, who reviewed it and when.
 *
 * Yoast prints a WebPage for a page but gives it no author. The author and
 * the reviewer are added as Person nodes the WebPage names, with the review
 * day as lastReviewed (author, reviewedBy and lastReviewed are all properties
 * of a schema.org WebPage). Nothing is printed a second time: it extends the
 * graph Yoast is about to print, as the Advice Centre's schema block does.
 *
 * @param array $graph
 * @param \Yoast\WP\SEO\Context\Meta_Tags_Context $context
 */
function filter_schema_graph(array $graph, $context): array
{
    if (!\is_singular()) {
        return $graph;
    }

    $post = \get_queried_object();

    if (!$post instanceof \WP_Post || !\has_block('acf/spec-credentials', $post)) {
        return $graph;
    }

    $fields = block_fields($post, 'acf/spec-credentials')[0] ?? [];
    $author = person((string) ($fields['author'] ?? ''), (string) ($fields['author_role'] ?? ''));

    if (!$author) {
        return $graph;
    }

    $reviewer = person((string) ($fields['reviewer'] ?? ''), (string) ($fields['reviewer_role'] ?? ''));
    $reviewed = !empty($fields['review_date']) ? strtotime((string) $fields['review_date']) : false;
    $reviewer = ($reviewer && $reviewed && strcasecmp($reviewer['name'], $author['name']) !== 0) ? $reviewer : null;

    // Point at Yoast's Organization only when it is in this graph (the commercial site prints none, and a reference to
    // a node that is not there is a dangling @id); otherwise name the company inline.
    $organization_id = $context->site_url . \Yoast\WP\SEO\Config\Schema_IDs::ORGANIZATION_HASH;
    $has_organization = in_array($organization_id, array_column(array_filter($graph, 'is_array'), '@id'), true);
    $organization = $has_organization ? ['@id' => $organization_id] : ['@type' => 'Organization', 'name' => 'Millboard'];
    $page_id = (string) $context->main_schema_id;
    $person_id = static function (array $person) use ($context): string {
        return $context->site_url . '#/schema/person/' . md5(strtolower($person['name']));
    };

    foreach ($graph as &$piece) {
        if (!is_array($piece) || ($piece['@id'] ?? '') !== $page_id) {
            continue;
        }

        $piece['author'] = ['@id' => $person_id($author)];

        if ($reviewer) {
            $piece['reviewedBy'] = ['@id' => $person_id($reviewer)];
            $piece['lastReviewed'] = \wp_date('Y-m-d', $reviewed);
        }
    }
    unset($piece);

    $printed = array_column(array_filter($graph, 'is_array'), '@id');

    foreach (array_filter([$author, $reviewer]) as $person) {
        if (in_array($person_id($person), $printed, true)) {
            continue;
        }

        $graph[] = array_filter([
            '@type' => 'Person',
            '@id' => $person_id($person),
            'name' => $person['name'],
            'jobTitle' => $person['role'] ?: null,
            'description' => $person['bio'] ?: null,
            'worksFor' => $organization,
        ]);
    }

    return $graph;
}

/**
 * The saved field values of every block of this name on the page.
 *
 * @return array<int, array<string, mixed>>
 */
function block_fields(\WP_Post $post, string $name): array
{
    $found = [];

    $walk = function (array $blocks) use (&$walk, &$found, $name) {
        foreach ($blocks as $block) {
            if (($block['blockName'] ?? '') === $name && !empty($block['attrs']['data'])) {
                $id = \acf_get_block_id($block['attrs']);
                \acf_setup_meta($block['attrs']['data'], $id, true);
                $found[] = (array) \get_fields();
                \acf_reset_meta($id);
            }

            if (!empty($block['innerBlocks'])) {
                $walk($block['innerBlocks']);
            }
        }
    };

    $walk(\parse_blocks($post->post_content));

    return $found;
}
