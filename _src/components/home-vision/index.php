<?php

/**
 * Home vision tiles.
 *
 * A section heading beside a row of square tiles. Above the home-wide threshold
 * the heading and the tiles sit side by side and centre on each other; below it
 * the heading takes the full width and the tiles halve beneath it.
 *
 * The whole tile is the link, so the image, the label and the chevron are all
 * one target rather than three things to aim at.
 */

?>
<section <?= \Granola\Helpers::build_attributes($args['attributes']); ?>>
    <div class="home-vision__inner">

        <div class="home-vision__header mbh-reveal">
            <?php if (!empty($args['eyebrow'])) : ?>
                <p class="home-vision__eyebrow"><?= esc_html($args['eyebrow']); ?></p>
            <?php endif; ?>

            <?php if (!empty($args['heading'])) : ?>
                <h2 class="home-vision__heading"><?= esc_html($args['heading']); ?></h2>
            <?php endif; ?>

            <?php if (!empty($args['link'])) : ?>
                <div class="home-vision__link-wrap">
                    <?= \Granola\Component::get('link', [
                        'url' => $args['link']['url'],
                        'content' => $args['link']['title'],
                        'target' => $args['link']['target'] ?? '',
                        'classes' => $args['link']['classes'],
                    ]); ?>
                </div>
            <?php endif; ?>
        </div>

        <?php if (!empty($args['tiles'])) : ?>
            <ul class="home-vision__tiles">
                <?php foreach ($args['tiles'] as $tile) : ?>
                    <li class="home-vision__tile">
                        <?php
                        // A list, because this is a set of destinations of the
                        // same kind and a screen reader should be told how many
                        // there are before it starts reading them out.
                        ?>
                        <a
                            class="home-vision__tile-link mbh-reveal mbh-reveal--image"
                            style="--mbh-reveal-delay: <?= (int) $tile['delay']; ?>ms"
                            <?php if (!empty($tile['link']['url'])) : ?>
                                href="<?= esc_url($tile['link']['url']); ?>"
                            <?php endif; ?>
                            <?php if (!empty($tile['link']['target'])) : ?>
                                target="<?= esc_attr($tile['link']['target']); ?>" rel="noopener"
                            <?php endif; ?>
                        >
                            <?php if (!empty($tile['image'])) : ?>
                                <span class="home-vision__frame">
                                    <?= \Granola\Component::get('image', $tile['image']); ?>
                                </span>
                            <?php endif; ?>

                            <span class="home-vision__caption">
                                <?php if (!empty($tile['label'])) : ?>
                                    <span class="home-vision__label"><?= esc_html($tile['label']); ?></span>
                                <?php endif; ?>

                                <?php
                                // Decorative: the label is the accessible name,
                                // and a chevron announced as an image would only
                                // repeat it.
                                ?>
                                <svg
                                    class="home-vision__chevron"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="1.6"
                                    aria-hidden="true"
                                    focusable="false"
                                >
                                    <path d="m9 5 7 7-7 7"></path>
                                </svg>
                            </span>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</section>
