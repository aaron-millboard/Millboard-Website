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
 * Mark the page as an FAQPage only when an accordion item actually carries FAQ.
 *
 * render_accordion_item_block_schema() adds a Question to mainEntity for every
 * accordion item except one whose faq_content is present and empty (the FAQ
 * toggle off). So the page gains a mainEntity only when at least one item is a
 * real FAQ. The page type must follow the same rule: flipping it to FAQPage on
 * block presence alone leaves a page of plain content accordions marked as an
 * FAQPage with no mainEntity, which Google reports as "A value for the
 * mainEntity field is required."
 *
 * Only suppress the flip when every item is positively content-only, so an
 * unexpected block shape falls through to the flip and can never strip the type
 * from a genuine FAQ page.
 *
 * @param array $blocks The blocks of this type on the current page.
 */
function prepare_accordion_item_schema($blocks)
{
    foreach ((array) $blocks as $block) {
        $data = is_array($block) ? ($block['attrs']['data'] ?? null) : null;

        $content_only = is_array($data)
            && array_key_exists('faq_content', $data)
            && empty($data['faq_content']);

        if (!$content_only) {
            \add_filter('wpseo_schema_webpage', __NAMESPACE__ . '\\change_schema_page_type');

            return;
        }
    }
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
