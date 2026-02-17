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
        'button_id' => wp_unique_id('accordion-button-'),
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
        'id' => $args['button_id'],
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
        'aria-labelledby' => $args['button_id']
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
    // TODO: Determine if accordion is set to be an 'FAQ'.
    $is_faq = !empty($block);

    if ($is_faq === false) {
        return $graph;
    }

    // Calculate FAQ 'position'.
    $faqs = array_filter($graph, function ($item) {
        return !empty($item['@type']) && $item['@type'] === 'Question';
    });

    $last_faq = end($faqs);
    $position = intval($last_faq['position']) + 1;

    // Append new Schema graph Question.
    $graph[] = [
        '@type' => 'Question',
        '@id' => 'https://millboard.test/en-gb/6503-2/#faq-question-1771320117377',
        'position' => $position,
        'url' => 'https://millboard.test/en-gb/6503-2/#faq-question-1771320117377',
        'name' => $block['attrs']['data']['title'],
        'answerCount' => 1,
        'acceptedAnswer' => [
            '@type' => 'Answer',
            'text' => $block['attrs']['data']['content'],
            'inLanguage' => \get_bloginfo('language'),
        ],
        'inLanguage' => \get_bloginfo('language'),
    ];

    return $graph;
}
