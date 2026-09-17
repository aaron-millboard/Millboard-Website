<?php

namespace Granola\Components\HomeHero;

function filter_args(array $args): ?array
{
    // -------------------------------------------------------------------------
    // Default arguments.
    // -------------------------------------------------------------------------
    $args = array_merge([
        'eyebrow' => null,
        'strapline' => null,
        'film' => null,
        'film_url' => null,
        'poster' => null,
        'film_description' => null,
        'pause_hint' => null,
        'scroll_label' => null,
        'classes' => [],
        'words' => [],
        'film_src' => null,
    ], $args);

    // -------------------------------------------------------------------------
    // Required classes.
    // -------------------------------------------------------------------------
    $args['classes'] = array_merge([
        'home-hero',
        'wp-block',
    ], $args['classes']);

    // -------------------------------------------------------------------------
    // The film. A hosted URL wins over an uploaded file: the design notes say
    // production should serve this from the CDN rather than the media library,
    // and a 720p hero film in uploads is not something to encourage.
    // -------------------------------------------------------------------------
    if (!empty($args['film_url'])) {
        $args['film_src'] = $args['film_url'];
    } elseif (!empty($args['film']['url'])) {
        $args['film_src'] = $args['film']['url'];
    }

    // -------------------------------------------------------------------------
    // The poster.
    //
    // This is the hero for the first second or two of every visit: the film is
    // 8.66MB and is not handed its source until after the load event, so until
    // it has decoded a frame there is nothing behind the strapline but the page
    // colour. Everything here is about getting this one image on screen as
    // early as the markup allows.
    //
    // eager + fetchpriority, and the same skip-lazy / data-no-lazy pair the
    // film carries. Perfmatters moves src to data-src and waits for the element
    // to be scrolled into view, which for the image at the top of the page is
    // precisely wrong -- it is already in view, and lazy-loading the thing the
    // browser would otherwise have painted first is the worst available
    // outcome.
    // -------------------------------------------------------------------------
    if (!empty($args['poster'])) {
        $args['poster']['size'] = 'full';
        $args['poster']['classes'] = ['home-hero__poster', 'skip-lazy'];
        $args['poster']['loading'] = 'eager';
        $args['poster']['attributes']['fetchpriority'] = 'high';
        $args['poster']['attributes']['data-spai-eager'] = true;
        $args['poster']['attributes']['data-no-lazy'] = '1';

        $args['poster']['sizes'] = poster_sizes($args['poster']);
    }

    // -------------------------------------------------------------------------
    // Split the strapline for the rise-on-load animation.
    //
    // Each word becomes a group with `overflow: hidden` and each character a
    // span that rises out of it, staggered 60ms apart. The index is written as
    // a custom property so the delay is one CSS expression rather than an
    // inline style per letter.
    //
    // The whole h1 carries the strapline as its accessible name and every span
    // is hidden from assistive tech. Read as markup this is a pile of
    // single-character elements, which a screen reader is entitled to spell out
    // one letter at a time; the design's own prototype does exactly that and
    // would have announced "L, i, v, e".
    // -------------------------------------------------------------------------
    // Two indices per character rather than one. The stagger is 60ms per
    // character, but the design also rests an extra 20ms at each word boundary:
    // "Live." runs 240-480ms and "Life." starts at 560, not 540. A single
    // running index cannot express that, so the word ordinal comes too and the
    // delay is one expression in CSS.
    if (!empty($args['strapline'])) {
        $index = 0;

        foreach (preg_split('/\s+/u', trim($args['strapline']), -1, PREG_SPLIT_NO_EMPTY) as $ordinal => $word) {
            $characters = preg_split('//u', $word, -1, PREG_SPLIT_NO_EMPTY);

            $args['words'][] = array_map(function ($character) use (&$index, $ordinal) {
                return [
                    'character' => $character,
                    'index' => $index++,
                    'word' => $ordinal,
                ];
            }, $characters);
        }
    }

    // -------------------------------------------------------------------------
    // Return the filtered args.
    // -------------------------------------------------------------------------
    return $args;
}

/**
 * The poster's `sizes` hint, worked out from the picture's own shape.
 *
 * The poster covers a box that is the whole viewport in both directions, and a
 * landscape photograph cropped to cover a portrait screen has to reach on
 * height: the width the browser actually needs is the viewport HEIGHT times the
 * picture's aspect, which on a phone is several times the screen width. Left to
 * work it out from the box alone the browser asks for 100vw, gets a 390px file
 * for a 390px-wide screen, and then scales it up nearly four times -- and the
 * first thing anyone sees is the softest image on the page.
 *
 * The hint is calculated rather than written down because the answer depends
 * entirely on the picture: a 16:9 still needs 384vw on a phone where a 4:3 one
 * needs 288vw, and asking for the 16:9 figure regardless would have every
 * phone downloading a third more image than it can show. Whatever is uploaded,
 * this asks for what that file needs at each shape of screen and no more.
 *
 * @param array $poster The poster field, as ACF returns it.
 * @return string A sizes attribute.
 */
function poster_sizes(array $poster): string
{
    // Fall back to the viewport width if the picture does not know its own
    // size, which is the browser's own default and never worse than it.
    if (empty($poster['width']) || empty($poster['height'])) {
        return '100vw';
    }

    $aspect = $poster['width'] / $poster['height'];

    // A representative screen for each band, as height over width: a tall
    // phone, a tablet, a laptop and a desktop.
    $screens = [
        ['500px', 844 / 390],
        ['900px', 1112 / 834],
        ['1500px', 900 / 1440],
    ];

    $steps = [];

    foreach ($screens as [$until, $shape]) {
        // Never below 100vw: past the point where the crop stops reaching on
        // height, the box width is what is needed.
        $needed = max(100, (int) ceil(100 * $shape * $aspect));

        $steps[] = sprintf('(max-width: %s) %dvw', $until, $needed);
    }

    $steps[] = sprintf('%dvw', max(100, (int) ceil(100 * (1080 / 1920) * $aspect)));

    return implode(', ', $steps);
}

/**
 * Declare this block as supplying the page's own heading.
 *
 * site-main renders the default page-header whenever the content does not bring
 * a header block of its own. Without this the homepage got both: a 340px
 * page-header above the hero, and two h1 elements on the page. site-main's own
 * note asks for exactly this registration -- "any new hero or profile-header
 * block has to be added to this list".
 *
 * @param array $blocks The block names that count as a page header.
 * @return array The list, with this block added.
 */
function filter_header_blocks(array $blocks): array
{
    $blocks[] = 'acf/home-hero';

    return $blocks;
}
