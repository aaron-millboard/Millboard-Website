<?php

namespace Granola\Components\AdviceSchema;

/**
 * Advice schema.
 *
 * The block is a switch, not an emitter. Yoast prints the page's JSON-LD in
 * the head, long before any block in the body renders, so a block that printed
 * its own script would be a second, disconnected graph further down the page:
 * a second CollectionPage, a second BreadcrumbList and a second WebSite, each
 * contradicting Yoast's. Instead the graph Yoast is about to print is extended
 * from here, and the block's only job is to say that it should be.
 *
 * What it adds is what the page's own blocks show, read from the same builders
 * they render with. On the hub: the stages and the categories as ItemLists
 * that the CollectionPage names as its main entity, and the authors as Person
 * nodes under the same @id Yoast gives them on their articles, so the hub and
 * the articles describe one person rather than two. On a category: its guides,
 * in the order the grid shows them.
 *
 * Deliberately left out of the design's list: FAQPage (the hub and the
 * categories have no FAQ, and marking up questions that are not on the page is
 * against Google's rules), and WebSite, Organization and BreadcrumbList (Yoast
 * already prints them).
 */

function filter_args(array $args): ?array
{
    // Nothing on the page. In the editor, a line saying what it is, so the
    // block is not an invisible thing someone deletes as clutter.
    if (empty($args['is_preview'])) {
        return null;
    }

    $args['classes'] = array_merge(['advice-schema', 'wp-block'], $args['classes'] ?? []);

    return $args;
}

/**
 * The template page behind the advice hub, if it carries this block.
 *
 * Not on a category: the category URLs set this post type on a term query,
 * which makes WordPress call them the post type archive too.
 */
function hub_template(): ?\WP_Post
{
    if (!\is_post_type_archive(\Theme\Utils\Advice::POST_TYPE) || \is_tax() || \is_search() || \is_paged()) {
        return null;
    }

    $template = \Granola\WordPress\TemplatePage::get_template_page(
        \get_post_type_object(\Theme\Utils\Advice::POST_TYPE)
    );

    if (!$template instanceof \WP_Post || !\has_block('acf/advice-schema', $template)) {
        return null;
    }

    return $template;
}

/**
 * The template page behind the advice category being viewed, if it carries
 * this block.
 */
function category_template(): ?\WP_Post
{
    $term = \Theme\Utils\Advice::current_term();

    if (!$term || \is_search() || \is_paged()) {
        return null;
    }

    $template = \Granola\WordPress\TemplatePage::get_template_page($term);

    if (!$template instanceof \WP_Post || !\has_block('acf/advice-schema', $template)) {
        return null;
    }

    return $template;
}

/**
 * The field values of every block of one type on the template, in order.
 *
 * ACF stores block fields flat in the block comment (`stages_0_title`), so
 * they are read back through ACF itself rather than picked apart here.
 */
function block_fields(\WP_Post $template, string $name): array
{
    $found = [];

    $walk = function (array $blocks) use (&$walk, &$found, $name) {
        foreach ($blocks as $block) {
            // A block left on its defaults is saved with no data at all, and
            // is still on the page.
            if (($block['blockName'] ?? '') === $name && empty($block['attrs']['data'])) {
                $found[] = [];
            } elseif (($block['blockName'] ?? '') === $name) {
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

    $walk(\parse_blocks($template->post_content));

    return $found;
}

/**
 * Extend Yoast's graph on the advice hub and the advice categories.
 *
 * @param array $graph The graph pieces Yoast is about to print.
 * @param \Yoast\WP\SEO\Context\Meta_Tags_Context $context
 */
function filter_schema_graph($graph, $context)
{
    if (!is_array($graph) || !function_exists('acf_setup_meta')) {
        return $graph;
    }

    $template = hub_template();

    if ($template) {
        return hub_graph($graph, $context, $template);
    }

    $template = category_template();

    if ($template) {
        return category_graph($graph, $context, $template);
    }

    return $graph;
}

/**
 * On a category: its guides, in the grid's order, as the ItemList its
 * CollectionPage names as its main entity.
 *
 * @param array $graph
 * @param \Yoast\WP\SEO\Context\Meta_Tags_Context $context
 */
function category_graph(array $graph, $context, \WP_Post $template): array
{
    $term = \Theme\Utils\Advice::current_term();
    $grids = block_fields($template, 'acf/advice-article-grid');

    if (!$term || !$grids) {
        return $graph;
    }

    $cards = \Granola\Components\AdviceArticleGrid\build_cards($term, (string) ($grids[0]['sort'] ?? 'recent'));

    if (!$cards) {
        return $graph;
    }

    $page_id = (string) $context->main_schema_id;
    $base = (string) $context->canonical ?: (string) \get_term_link($term);

    $list = [
        '@type' => 'ItemList',
        '@id' => $base . '#advice-guides',
        'name' => \Theme\Utils\Advice::term_name($term),
        'numberOfItems' => count($cards),
        'itemListElement' => array_map(function ($card, $position) {
            return [
                '@type' => 'ListItem',
                'position' => $position + 1,
                'name' => $card['title'],
                'url' => $card['url'],
            ];
        }, $cards, array_keys($cards)),
    ];

    foreach ($graph as &$piece) {
        if (is_array($piece) && ($piece['@id'] ?? '') === $page_id) {
            $piece['mainEntity'] = ['@id' => $list['@id']];
        }
    }
    unset($piece);

    $graph[] = $list;

    return $graph;
}

/**
 * On the hub: the stages, the categories and the authors.
 *
 * @param array $graph
 * @param \Yoast\WP\SEO\Context\Meta_Tags_Context $context
 */
function hub_graph(array $graph, $context, \WP_Post $template): array
{
    // Read straight off the context: its properties are generated on first
    // read, so they are fetched rather than tested.
    $page_id = (string) $context->main_schema_id;
    $base = (string) $context->canonical ?: (string) \get_post_type_archive_link(\Theme\Utils\Advice::POST_TYPE);
    $lists = [];
    $people = [];

    // Stages.
    foreach (block_fields($template, 'acf/advice-journey-nav') as $index => $fields) {
        // Only the stages that lead somewhere. Google reads an ItemList on a
        // summary page as a list of pages and wants a URL on every item, so
        // a stage that is not yet a link would be reported as a broken item.
        $stages = array_values(array_filter(
            \Granola\Components\AdviceJourneyNav\build_stages((array) ($fields['stages'] ?? [])),
            function ($stage) {
                return !empty($stage['link']['url']);
            }
        ));

        if (!$stages) {
            continue;
        }

        $lists[] = [
            '@type' => 'ItemList',
            '@id' => $base . '#advice-stages' . ($index ? '-' . ($index + 1) : ''),
            'name' => $fields['heading'] ?? '',
            'itemListOrder' => 'https://schema.org/ItemListOrderAscending',
            'numberOfItems' => count($stages),
            'itemListElement' => array_map(function ($stage, $position) {
                return array_filter([
                    '@type' => 'ListItem',
                    'position' => $position + 1,
                    'name' => $stage['title'],
                    'description' => $stage['description'] ?: null,
                    'url' => $stage['link']['url'],
                ]);
            }, $stages, array_keys($stages)),
        ];
    }

    // Categories.
    foreach (block_fields($template, 'acf/advice-category-slider') as $index => $fields) {
        $cards = \Granola\Components\AdviceCategorySlider\build_cards((array) ($fields['slides'] ?? []));

        if (!$cards) {
            continue;
        }

        $lists[] = [
            '@type' => 'ItemList',
            '@id' => $base . '#advice-categories' . ($index ? '-' . ($index + 1) : ''),
            'name' => $fields['heading'] ?? '',
            'numberOfItems' => count($cards),
            'itemListElement' => array_map(function ($card, $position) {
                return array_filter([
                    '@type' => 'ListItem',
                    'position' => $position + 1,
                    'name' => $card['name'],
                    'description' => $card['description'] ?: null,
                    'url' => $card['url'],
                ]);
            }, $cards, array_keys($cards)),
        ];
    }

    // Authors, under Yoast's own id for each user.
    $ids = function_exists('YoastSEO') ? \YoastSEO()->helpers->schema->id : null;

    foreach (block_fields($template, 'acf/advice-authors') as $fields) {
        foreach (\Granola\Components\AdviceAuthors\build_people((array) ($fields['authors'] ?? [])) as $person) {
            $id = $ids ? $ids->get_user_schema_id($person['user_id'], $context) : '';

            if (!$id || isset($people[$id])) {
                continue;
            }

            $people[$id] = array_filter([
                '@type' => 'Person',
                '@id' => $id,
                'name' => $person['name'],
                'jobTitle' => $person['role'] ?: null,
                'knowsAbout' => $person['expertise']
                    ? array_values(array_filter(array_map('trim', explode(',', $person['expertise']))))
                    : null,
                'worksFor' => ['@id' => $context->site_url . \Yoast\WP\SEO\Config\Schema_IDs::ORGANIZATION_HASH],
            ]);
        }
    }

    if (!$lists && !$people) {
        return $graph;
    }

    // Name the lists and the people from the page itself.
    foreach ($graph as &$piece) {
        if (!is_array($piece) || ($piece['@id'] ?? '') !== $page_id) {
            continue;
        }

        if ($lists) {
            $piece['mainEntity'] = array_map(function ($list) {
                return ['@id' => $list['@id']];
            }, $lists);
        }

        if ($people) {
            $piece['contributor'] = array_map(function ($id) {
                return ['@id' => $id];
            }, array_keys($people));
        }
    }
    unset($piece);

    // A Person Yoast already printed is left as Yoast printed it.
    $printed = array_column(array_filter($graph, 'is_array'), '@id');

    foreach ($people as $id => $node) {
        if (!in_array($id, $printed, true)) {
            $lists[] = $node;
        }
    }

    return array_merge($graph, $lists);
}
