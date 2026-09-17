<?php

/**
 * Home hero.
 *
 * A tall section containing a sticky full-viewport stage. Everything is driven
 * by one scroll variable, `--p`, running 0 to 1 across the section, and its
 * inverse `--op`: the film frame starts inset inside the header and the side
 * margins and opens to full bleed as the page moves.
 *
 * The play/pause control is a real button covering the frame, so it is keyboard
 * operable and has an accessible name, rather than a click handler on a div.
 */

?>
<section <?= \Granola\Helpers::build_attributes($args['attributes']); ?>>
    <div class="home-hero__stage">
        <div class="home-hero__frame">

            <div class="home-hero__aperture">
                <?php
                // The poster, always, whether or not there is a film over it.
                //
                // It used to render only when there was no film, with the film
                // carrying the image on its own `poster` attribute instead.
                // That attribute is not markup the preload scanner can act on:
                // the browser cannot start fetching it until it has built the
                // media element, which is after the stylesheet. An <img> in the
                // HTML is requested while the document is still streaming.
                //
                // The film is absolutely positioned over this in the same box
                // and comes later in the DOM, so it simply paints on top once
                // it has a frame to show. No swap, no class, no script.
                ?>
                <?php if (!empty($args['poster'])) : ?>
                    <?= \Granola\Component::get('image', $args['poster']); ?>
                <?php endif; ?>

                <?php if (!empty($args['film_src'])) : ?>
                    <?php
                    // skip-lazy and data-no-lazy opt the film out of Perfmatters'
                    // lazy loading, which moves src to data-src and waits for the
                    // element to be scrolled into view. This one is already in
                    // view -- it IS the fold -- and it never got its src back, so
                    // the hero sat on its poster with readyState 0. Same opt-out
                    // pair the distributor map uses for its iframe.
                    ?>
                    <video
                        class="home-hero__film skip-lazy"
                        data-no-lazy="1"
                        data-home-hero-film
                        <?php
                        // The source is handed over by HomeHero.js after the
                        // page has loaded, not by the browser during it.
                        //
                        // This film is 8.66MB. With a plain src and preload="auto"
                        // the browser pulls all of it in parallel with the
                        // stylesheet, the fonts and the scripts, on the one
                        // connection that has to paint the page. Deferring it
                        // costs the film a moment and gives everything else the
                        // bandwidth first.
                        ?>
                        data-film-src="<?= esc_url($args['film_src']); ?>"
                        <?php
                        // No `poster` attribute: the <img> above is the poster,
                        // and it is the responsive one. Naming the file here as
                        // well would fetch the same picture twice at two
                        // different widths.
                        ?>
                        <?php if (!empty($args['film_description'])) : ?>
                            aria-label="<?= esc_attr($args['film_description']); ?>"
                        <?php endif; ?>
                        preload="none"
                        muted
                        loop
                        playsinline
                        autoplay
                    ></video>
                <?php endif; ?>

                <?php
                // Two scrims: a vertical gradient for the type top and bottom,
                // and a soft central pool so the strapline holds up over
                // whatever the footage happens to be doing behind it.
                ?>
                <div class="home-hero__scrim home-hero__scrim--linear" aria-hidden="true"></div>
                <div class="home-hero__scrim home-hero__scrim--radial" aria-hidden="true"></div>



                <?php
                // Inside the aperture, so the load-in slide reveals the words with
                // the film rather than over the surround. That surround is the page
                // colour now, and white type on Spring Wood cannot be read -- the
                // first letters rise at 240ms, while the aperture is barely a third
                // open. Clipped alongside the film they simply arrive as it does,
                // and no timing has to move.
                ?>
                <div class="home-hero__content">
                    <?php if (!empty($args['eyebrow'])) : ?>
                        <p class="home-hero__eyebrow"><?= esc_html($args['eyebrow']); ?></p>
                    <?php endif; ?>

                    <?php if (!empty($args['words'])) : ?>
                        <h1
                            class="home-hero__strapline"
                            aria-label="<?= esc_attr($args['strapline']); ?>">
                            <?php
                            // The letters are emitted with no whitespace between
                            // them, and the words with. Flexbox drops the text nodes
                            // either way, so this changes nothing visually -- but it
                            // is what anything reading the DOM without CSS extracts.
                            // Indented apart, the page h1 reads "L i v e . L i f e ."
                            // to a crawler; run together it reads "Live. Life.
                            // Outside." The aria-label already covers assistive tech.
                            ?>
                            <?php foreach ($args['words'] as $word) : ?>
                                <span class="home-hero__word" aria-hidden="true"><?php
                                    foreach ($word as $letter) :
                                        ?><span
                                            class="home-hero__letter"
                                            style="--home-hero-letter: <?= (int) $letter['index']; ?>; --home-hero-word: <?= (int) $letter['word']; ?>"
                                        ><?= esc_html($letter['character']); ?></span><?php
                                    endforeach;
                                ?></span>
                            <?php endforeach; ?>
                        </h1>
                    <?php endif; ?>
                </div>
            </div>

            <?php if (!empty($args['film_src'])) : ?>
                <button
                    type="button"
                    class="home-hero__toggle"
                    data-home-hero-toggle
                    aria-label="<?= esc_attr__('Play or pause the background film', 'granola'); ?>"
                ></button>

                <?php
                // The cursor-following ring. Decorative: the button above is
                // what carries the name and the keyboard behaviour.
                ?>
                <div class="home-hero__ring" aria-hidden="true">
                    <?php
                    // Bare glyphs, inline, rather than the theme's
                    // icons-custom/play.svg and pause.svg. Those two are
                    // circular BUTTON icons -- a ring plus the glyph, drawn at
                    // 28 and 56px -- so inside this 108px ring they became a
                    // circle within a circle crushed into 16px. pause.svg also
                    // hardcodes fill #F9F7F1, the pre-refresh Spring Wood, so it
                    // could not take currentColor either.
                    ?>
                    <span class="home-hero__ring-icons">
                        <svg class="home-hero__ring-icon home-hero__ring-icon--play" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" focusable="false">
                            <path d="M8 5.5 19 12 8 18.5Z"></path>
                        </svg>
                        <svg class="home-hero__ring-icon home-hero__ring-icon--pause" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" focusable="false">
                            <path d="M8 6h3v12H8zM13 6h3v12h-3z"></path>
                        </svg>
                    </span>

                    <?php if (!empty($args['film_description'])) : ?>
                        <span class="home-hero__ring-label"><?= esc_html__('The film', 'granola'); ?></span>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>

        <?php if (!empty($args['pause_hint']) || !empty($args['scroll_label'])) : ?>
            <div class="home-hero__baseline">
                <?php if (!empty($args['pause_hint']) && !empty($args['film_src'])) : ?>
                    <p class="home-hero__hint"><?= esc_html($args['pause_hint']); ?></p>
                <?php endif; ?>

                <?php if (!empty($args['scroll_label'])) : ?>
                    <div class="home-hero__cue">
                        <span class="home-hero__cue-label"><?= esc_html($args['scroll_label']); ?></span>
                        <span class="home-hero__cue-rule" aria-hidden="true"></span>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</section>
