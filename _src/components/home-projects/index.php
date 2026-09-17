<?php

/**
 * Home projects rail.
 *
 * A sideways rail of recent projects. The rail bleeds past the container on
 * both sides, so the section carries no horizontal padding of its own and the
 * header and the track each pad themselves to the same gutter.
 *
 * Every card is a link, so the rail is reachable by tabbing through it and
 * needs no keyboard handling of its own: a scroll container whose contents are
 * all focusable does not take a tab stop under WAI's guidance, and adding one
 * would only put an empty stop in front of the first card.
 */

?>
<section <?= \Granola\Helpers::build_attributes($args['attributes']); ?>>

    <div class="home-projects__header mbh-reveal">
        <div class="home-projects__intro">
            <span class="home-projects__rule" aria-hidden="true"></span>

            <?php if (!empty($args['eyebrow'])) : ?>
                <p class="home-projects__eyebrow"><?= esc_html($args['eyebrow']); ?></p>
            <?php endif; ?>

            <?php if (!empty($args['heading'])) : ?>
                <h2 class="home-projects__heading"><?= esc_html($args['heading']); ?></h2>
            <?php endif; ?>
        </div>

        <?php if (!empty($args['link'])) : ?>
            <a
                class="home-projects__all"
                href="<?= esc_url($args['link']['url']); ?>"
                <?php if (!empty($args['link']['target'])) : ?>
                    target="<?= esc_attr($args['link']['target']); ?>" rel="noopener"
                <?php endif; ?>
            >
                <?= esc_html($args['link']['title']); ?>
                <svg
                    class="home-projects__all-arrow"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="1.5"
                    aria-hidden="true"
                    focusable="false"
                ><path d="M4 12h15M14 7l5 5-5 5"></path></svg>
            </a>
        <?php endif; ?>
    </div>

    <?php if (!empty($args['projects'])) : ?>
        <div class="home-projects__rail">
            <ul class="home-projects__track">
                <?php foreach ($args['projects'] as $project) : ?>
                    <li class="home-projects__item">
                        <a
                            class="home-projects__card mbh-reveal"
                            <?php if (!empty($project['link']['url'])) : ?>
                                href="<?= esc_url($project['link']['url']); ?>"
                            <?php endif; ?>
                            <?php if (!empty($project['link']['target'])) : ?>
                                target="<?= esc_attr($project['link']['target']); ?>" rel="noopener"
                            <?php endif; ?>
                        >
                            <?php if (!empty($project['image'])) : ?>
                                <?php
                                // The frame crops the hover scale, so the
                                // photograph grows inside its own edges rather
                                // than nudging the card's text about.
                                ?>
                                <span class="home-projects__frame">
                                    <?= \Granola\Component::get('image', $project['image']); ?>
                                </span>
                            <?php endif; ?>

                            <?php if (!empty($project['location'])) : ?>
                                <span class="home-projects__location"><?= esc_html($project['location']); ?></span>
                            <?php endif; ?>

                            <?php if (!empty($project['title'])) : ?>
                                <span class="home-projects__title"><?= esc_html($project['title']); ?></span>
                            <?php endif; ?>

                            <?php if (!empty($project['product'])) : ?>
                                <span class="home-projects__product"><?= esc_html($project['product']); ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>

        <?php if (!empty($args['hint'])) : ?>
            <p class="home-projects__hint"><?= esc_html($args['hint']); ?></p>
        <?php endif; ?>
    <?php endif; ?>
</section>
