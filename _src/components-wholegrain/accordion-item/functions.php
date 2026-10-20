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

/**
 * Drops FAQPage from the page type when the page carries no FAQ.
 *
 * change_schema_page_type() flips the page to ["WebPage","FAQPage"] whenever an
 * accordion-item block is present, but only items with FAQ content add a
 * Question to mainEntity. A page whose accordions are plain content (FAQ toggle
 * off) is then published as an FAQPage with no mainEntity, which Google reports
 * as "A value for the mainEntity field is required." This runs on the finished
 * graph, after the block filters have added any questions, and only strips the
 * type where mainEntity is genuinely empty, so a real FAQ page is left as is.
 *
 * @param array $graph The finished Yoast schema graph.
 *
 * @return array
 */
function drop_empty_faqpage($graph)
{
    if (!is_array($graph)) {
        return $graph;
    }

    foreach ($graph as $index => $piece) {
        if (!is_array($piece)) {
            continue;
        }

        $types = (array) ($piece['@type'] ?? []);

        if (!in_array('FAQPage', $types, true) || !empty($piece['mainEntity'])) {
            continue;
        }

        $types = array_values(array_filter($types, static function ($type) {
            return $type !== 'FAQPage';
        }));

        $graph[$index]['@type'] = count($types) === 1 ? $types[0] : $types;

        unset($graph[$index]['mainEntity']);
    }

    return $graph;
}

/**
 * Question and answer text for the schema: no tags, entities decoded (&amp; &nbsp; &reg;), one space between words.
 */
function schema_plain_text($text)
{
    $text = html_entity_decode(\wp_strip_all_tags((string) $text), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $text = str_replace("\xC2\xA0", ' ', $text);

    return trim((string) preg_replace('/\s+/', ' ', $text));
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
        'name' => schema_plain_text($block['attrs']['data']['title'] ?? ''),
        'answerCount' => 1,
        'acceptedAnswer' => [
            '@type' => 'Answer',
            'text' => schema_plain_text($block['attrs']['data']['content'] ?? ''),
            'inLanguage' => \get_bloginfo('language'),
        ],
        'inLanguage' => \get_bloginfo('language'),
    ];

    if (empty($graph["0"]['mainEntity'])) {
        $graph["0"]['mainEntity'] = [];
    }

    $graph["0"]['mainEntity'][] = [
        '@id' => $url . '#' . $id,
    ];

    return $graph;
}
