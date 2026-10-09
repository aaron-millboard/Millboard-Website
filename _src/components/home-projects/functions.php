<?php

namespace Granola\Components\HomeProjects;

/**
 * The rail's cards are read off the case studies themselves.
 *
 * Everything on a card -- the photograph, the title, the location, the boards
 * and the link -- already exists on the case study it points at, so the block
 * asks only which case studies to show. Typing them again by hand meant four
 * copies of the same facts drifting out of step with the source and with each
 * other, and a mistyped URL pointing a card at nothing.
 *
 * The photograph, the title and the link are exact: they are the case study's
 * own featured image, post title and permalink. The location and the boards are
 * read from its categories, which is the only place either is recorded -- see
 * the two slug lists below for what that can and cannot tell us.
 */

/**
 * Category roots that name a place.
 *
 * Matched on slug, not on name: the slugs are the same English strings on every
 * subsite, so this holds for the German and export sites too, where the term
 * names are translated.
 *
 * Positive matching, deliberately. The alternative -- treat anything that is
 * not a product term as a location -- reads every stray colour term as a place
 * on any site whose category tree has flattened, and a card captioned "Burnt
 * Cedar" where a town should be is worse than a card with no caption.
 */
const LOCATION_ROOTS = [
    'united-kingdom',
    'ireland',
    'europe',
    'usa',
    'canada',
    'australia',
    'france',
    'germany',
    'guernsey',
    'jersey',
    'isle-of-man',
];

/**
 * Places that sit at the top level on some subsites.
 *
 * The tree is meant to be country > county, and on most sites it is. On at
 * least one it has flattened, so these are recognised wherever they sit.
 */
const LOCATION_ALSO = [
    'cornwall',
    'devon',
    'essex',
    'london',
    'somerset',
    'wales',
    'yorkshire',
];

/**
 * Category roots that name a board range. Their children are the colours.
 */
const RANGE_ROOTS = [
    'enhanced-grain',
    'weathered-oak',
    'lasta-grip',
    'modello-contour',
    'modello-linear',
    'board-batten',
    'shadow-line',
    'decor',
];

function filter_args(array $args): ?array
{
    // -------------------------------------------------------------------------
    // Default arguments.
    // -------------------------------------------------------------------------
    $args = array_merge([
        'eyebrow' => null,
        'heading' => null,
        'link' => [],
        'hint' => null,
        'case_studies' => [],
        'classes' => [],
    ], $args);

    // -------------------------------------------------------------------------
    // Required classes.
    // -------------------------------------------------------------------------
    $args['classes'] = array_merge([
        'home-projects',
        'wp-block',
    ], $args['classes']);

    // -------------------------------------------------------------------------
    // The cards.
    // -------------------------------------------------------------------------
    $args['projects'] = array_values(array_filter(array_map(
        __NAMESPACE__ . '\\build_card',
        array_map('intval', (array) $args['case_studies'])
    )));

    // -------------------------------------------------------------------------
    // Link.
    // -------------------------------------------------------------------------
    if (!empty($args['link'])) {
        $args['link']['classes'][] = 'home-projects__all';
    }

    // -------------------------------------------------------------------------
    // Return the filtered args.
    // -------------------------------------------------------------------------
    return $args;
}

/**
 * One case study, as the card the template draws.
 *
 * Returns null for anything that has since been unpublished or deleted, so a
 * case study retired after it was picked leaves a shorter rail rather than an
 * empty card pointing nowhere.
 */
function build_card(int $id): ?array
{
    if ($id <= 0 || \get_post_status($id) !== 'publish') {
        return null;
    }

    $card = [
        'location' => location_of($id),
        'title' => \get_the_title($id),
        'product' => boards_of($id),
        'link' => [
            'url' => \get_permalink($id),
            // The card's own text names the project, so the link needs no
            // label of its own; this is only here for the shape the template
            // expects.
            'title' => \get_the_title($id),
        ],
    ];

    $thumbnail = (int) \get_post_thumbnail_id($id);

    if ($thumbnail) {
        $card['image'] = [
            'attachment_id' => $thumbnail,
            // Decorative: the card's title says what the photograph is of, and
            // the whole card is one link.
            'alt' => '',
            'size' => 'full',
            'classes' => ['home-projects__image'],

            // A 3:4 portrait crop of a landscape source needs a MUCH wider
            // source than the card: the height is what has to reach, so the
            // width needed is the box height times the source's own aspect.
            // A 400px card is 533px tall, and these photographs run 4:3 to 3:2,
            // so the worst case is 533 x 1.5 = 800px. Sized to the card width
            // instead, the browser scaled them up 1.32x.
            'sizes' => '(max-width: 500px) 160vw, 800px',
        ];
    }

    return $card;
}

/**
 * A term's name as text.
 *
 * Several are stored with their entities intact -- "Board &amp; Batten+" is
 * the literal name in the database. The template escapes whatever it is given,
 * so passing that straight through escapes the ampersand twice and the card
 * shows the markup.
 */
function term_name(\WP_Term $term): string
{
    return html_entity_decode($term->name, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

/**
 * The most specific place a case study is tagged with.
 *
 * A county beats the country it sits in -- "Devon" says more than "United
 * Kingdom" -- so a child of a location root wins over the root itself.
 */
function location_of(int $id): string
{
    $terms = \wp_get_post_terms($id, 'category');

    if (\is_wp_error($terms) || !$terms) {
        return '';
    }

    $root = '';

    foreach ($terms as $term) {
        if ($term->parent) {
            $parent = \get_term($term->parent);

            if ($parent && !\is_wp_error($parent) && \in_array($parent->slug, LOCATION_ROOTS, true)) {
                return term_name($term);
            }

            continue;
        }

        if (\in_array($term->slug, LOCATION_ALSO, true)) {
            return term_name($term);
        }

        if (!$root && \in_array($term->slug, LOCATION_ROOTS, true)) {
            $root = term_name($term);
        }
    }

    return $root;
}

/**
 * The boards a case study used, as the card captions them.
 *
 * Ranges first, then the colours tagged under them: "Enhanced Grain, Limed Oak",
 * or "Enhanced Grain and Shadow Line+, Burnt Cedar" where a project used two.
 * A range tagged without a colour simply has no colour to name.
 */
function boards_of(int $id): string
{
    $terms = \wp_get_post_terms($id, 'category');

    if (\is_wp_error($terms) || !$terms) {
        return '';
    }

    $ranges = [];
    $colours = [];

    foreach ($terms as $term) {
        if (!$term->parent) {
            if (\in_array($term->slug, RANGE_ROOTS, true)) {
                $ranges[$term->term_id] = term_name($term);
            }

            continue;
        }

        $parent = \get_term($term->parent);

        if ($parent && !\is_wp_error($parent) && \in_array($parent->slug, RANGE_ROOTS, true)) {
            $colours[] = term_name($term);
            // A colour implies its range, which is usually tagged too but is
            // not always.
            $ranges[$parent->term_id] = term_name($parent);
        }
    }

    if (!$ranges) {
        return '';
    }

    $boards = join_names(array_unique($ranges));

    if ($colours) {
        $boards .= ', ' . implode(', ', array_unique($colours));
    }

    return $boards;
}

/**
 * "A", "A and B", "A, B and C".
 *
 * Plain `and` between every one reads as a list someone forgot to finish once
 * a project used three ranges, which several do.
 */
function join_names(array $names): string
{
    $names = array_values($names);
    $last = array_pop($names);

    return $names ? implode(', ', $names) . ' and ' . $last : (string) $last;
}
