<?php

/**
 * Home story.
 *
 * The brand story on a dark ground: copy on one side, a film and two captioned
 * figures on the other. The film loads nothing until it is asked for -- the
 * iframe src stays empty and the poster carries the weight, so a page that is
 * never scrolled this far never fetches a player.
 */

?>
<section <?= \Granola\Helpers::build_attributes($args['attributes']); ?>>
    <div class="home-story__inner">

        <div class="home-story__body mbh-reveal">
            <span class="home-story__rule" aria-hidden="true"></span>

            <?php if (!empty($args['eyebrow'])) { ?>
                <p class="home-story__eyebrow"><?= esc_html($args['eyebrow']); ?></p>
            <?php } ?>

            <?php if (!empty($args['heading'])) { ?>
                <h2 class="home-story__heading"><?= esc_html($args['heading']); ?></h2>
            <?php } ?>

            <?php if (!empty($args['body'])) { ?>
                <div class="home-story__text">
                    <?= wp_kses_post($args['body']); ?>
                </div>
            <?php } ?>

            <?php if (!empty($args['link'])) { ?>
                <a
                    class="home-story__link"
                    href="<?= esc_url($args['link']['url']); ?>"
                    <?php if (!empty($args['link']['target'])) { ?>
                        target="<?= esc_attr($args['link']['target']); ?>" rel="noopener"
                    <?php } ?>
                >
                    <?= esc_html($args['link']['title']); ?>
                    <svg
                        class="home-story__link-arrow"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.4"
                        aria-hidden="true"
                        focusable="false"
                    ><path d="M4 12h15M14 7l5 5-5 5"></path></svg>
                </a>
            <?php } ?>
        </div>

        <div class="home-story__media mbh-reveal mbh-reveal--image">

            <?php if (!empty($args['poster'])) { ?>
                <div class="home-story__film">
                    <?= \Granola\Component::get('image', $args['poster']); ?>

                    <?php if (!empty($args['embed_url'])) { ?>
                        <button
                            type="button"
                            class="home-story__play"
                            data-home-story-play
                            data-embed-url="<?= esc_attr($args['embed_url']); ?>"
                        >
                            <svg
                                class="home-story__play-glyph"
                                viewBox="0 0 24 24"
                                fill="currentColor"
                                aria-hidden="true"
                                focusable="false"
                            ><path d="M8 5.5 19 12 8 18.5Z"></path></svg>
                            <span class="home-story__play-label"><?= esc_html($args['film_label']); ?></span>
                        </button>

                        <?php
                        // Empty src by design: HomeStory.js fills it on the
                        // first click, so nothing is fetched until asked for.
                        ?>
                        <div class="home-story__frame">
                            <iframe
                                class="home-story__iframe"
                                src=""
                                title="<?= esc_attr($args['film_label']); ?>"
                                loading="lazy"
                                frameborder="0"
                                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                                allowfullscreen
                            ></iframe>
                        </div>
                    <?php } ?>
                </div>
            <?php } ?>

            <?php if (!empty($args['figures'])) { ?>
                <ul class="home-story__figures">
                    <?php foreach ($args['figures'] as $figure) { ?>
                        <li class="home-story__figure mbh-reveal" style="--mbh-reveal-delay: <?= (int) $figure['delay']; ?>ms">
                            <figure class="home-story__figure-inner">
                                <?= \Granola\Component::get('image', $figure['image']); ?>

                                <?php if (!empty($figure['caption'])) { ?>
                                    <figcaption class="home-story__caption"><?= esc_html($figure['caption']); ?></figcaption>
                                <?php } ?>
                            </figure>
                        </li>
                    <?php } ?>
                </ul>
            <?php } ?>
        </div>
    </div>
</section>
