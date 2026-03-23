<?php

namespace Granola\Components\AccordionItem;

function filter_args(array $args): ?array
{
    // ---------------------------------------
    // Default arguments.
    // ---------------------------------------
    $args = array_merge([
        'classes' => [],
        'content' => '',
        'title' => '',
        'panel_id' => wp_unique_id('accordion-panel-'),
        'is_opened' => false,
    ], $args);

    // ---------------------------------------
    // Required classes.
    // ---------------------------------------
    $args['classes'] = array_merge([
        'accordion__item',
        'wp-block',
    ], $args['classes']);

    // ---------------------------------------
    // Assign unique IDs and prepare button & panel attributes
    // ---------------------------------------
    $args['button'] = [
        'id' => sanitize_title($args['title']),
        'classes' => [
            'accordion__item__trigger',
            'js-accordion-button'
        ],
        'attributes' => [
            'aria-expanded' => $args['is_opened'] ? 'true' : 'false',
            'aria-controls' => $args['panel_id'],
        ],
        'content' => \Granola\Component::get('heading', [
            'el' => 'h3',
            'content' => $args['title'],
            'classes' => ['accordion__item__heading']
        ]),
    ];

    // ---------------------------------------
    // Prepare panel attributes
    // ---------------------------------------
    $args['panel_attributes'] = [
        'id' => $args['panel_id'],
        'class' => \Granola\Helpers::build_classes([
            'accordion__item__panel',
            'js-expandable-element'
        ]),
        'aria-labelledby' => sanitize_title($args['title']),
    ];

    if (!$args['is_opened']) {
        $args['panel_attributes']['hidden'] = 'true';
        $args['panel_attributes']['aria-hidden'] = 'true';
    }

    if (!empty($args['cta'])) {
        $args['cta'] = [
            'title'    => $args['cta']['title'] ?? '',
            'url'      => $args['cta']['url'] ?? '',
            'attributes' => [
                'target' => $args['cta']['target'] ?? '',
                'rel'    => $args['cta']['rel'] ?? '',
            ],
            'classes' => [
                'accordion__item__panel__inner__cta',
                'g-button'
            ],
        ];
    }

    // -------------------------------------------------------------------------
    // Return the filtered args.
    // -------------------------------------------------------------------------
    return $args;
}

/**
 * If this fires, we know there's an Accordion Item block on the page, so filter the page type.
 *
 * @param array $blocks The blocks of this type on the current page.
 */
function prepare_accordion_item_schema($blocks)
{
    \add_filter('wpseo_schema_webpage', __NAMESPACE__ . '\\change_schema_page_type');
}

/**
 * Changes @type of Webpage Schema data.
 *
 * @param array $data Schema.org Webpage data array.
 *
 * @return array Schema.org Webpage data array.
 */
function change_schema_page_type($data)
{
    $data['@type'] = [
        'WebPage',
        'FAQPage',
    ];

    return $data;
}

function render_accordion_item_block_schema($graph, $block)
{
    $not_faq = isset($block['attrs']['data']['faq_content']) && empty($block['attrs']['data']['faq_content']);

    // Bail early - this accordion item block does not contain FAQ data.
    if ($not_faq) {
        return $graph;
    }

    $url_node = array_find($graph, function ($node) {
        return !empty($node['url']);
    });

    if (empty($url_node)) {
        return $graph;
    }

    $url = $url_node['url'];

    // Calculate FAQ 'position'.
    $position = 1; // default.

    $faqs = array_filter($graph, function ($item) {
        return !empty($item['@type']) && $item['@type'] === 'Question';
    });

    if (!empty($faqs)) {
        $last_faq = end($faqs);
        $position = intval($last_faq['position']) + 1;
    }

    $id = sanitize_title(!empty($block['attrs']['data']['title']) ? $block['attrs']['data']['title'] : '');

    // Append new Schema graph Question.
    $graph[] = [
        '@type' => 'Question',
        '@id' => $url . '#' . $id,
        'position' => $position,
        'url' => $url . '#' . $id,
        'name' => $block['attrs']['data']['title'] ?? '',
        'answerCount' => 1,
        'acceptedAnswer' => [
            '@type' => 'Answer',
            'text' => $block['attrs']['data']['content'] ?? '',
            'inLanguage' => \get_bloginfo('language'),
        ],
        'inLanguage' => \get_bloginfo('language'),
    ];

    return $graph;
}
