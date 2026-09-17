<?php

/**
 * Home feature pair.
 *
 * A square image beside a headed block of copy. The two sections that use it
 * mirror each other: decking on Spring Wood with its image to the left,
 * cladding on Mist with its image to the right.
 *
 * The image and the copy swap places in the markup rather than with `order`,
 * so the stacked order on a phone follows the same sequence a reader sees on a
 * desktop instead of inverting under them.
 */

$media = '';

if (!empty($args['image'])) {
    $media = '<div class="home-feature__media mbh-reveal mbh-reveal--image">'
        . \Granola\Component::get('image', $args['image'])
        . '</div>';
}

?>
<section <?= \Granola\Helpers::build_attributes($args['attributes']); ?>>
    <div class="home-feature__inner">

        <?php if ($args['image_side'] === 'left') { ?>
            <?= $media; ?>
        <?php } ?>

        <div class="home-feature__body mbh-reveal <?= esc_attr($args['body_reveal']); ?>">
            <?php
            // A 56px olive rule opens every feature section. Decorative, so it
            // is a span with no accessible name rather than an <hr>, which
            // would be announced as a separator.
            ?>
            <span class="home-feature__rule" aria-hidden="true"></span>

            <?php if (!empty($args['eyebrow'])) { ?>
                <p class="home-feature__eyebrow"><?= esc_html($args['eyebrow']); ?></p>
            <?php } ?>

            <?php if (!empty($args['heading'])) { ?>
                <h2 class="home-feature__heading"><?= esc_html($args['heading']); ?></h2>
            <?php } ?>

            <?php if (!empty($args['body'])) { ?>
                <div class="home-feature__text">
                    <?= wp_kses_post($args['body']); ?>
                </div>
            <?php } ?>

            <?php if (!empty($args['link'])) { ?>
                <div class="home-feature__link-wrap">
                    <?= \Granola\Component::get('link', [
                        'url' => $args['link']['url'],
                        'content' => $args['link']['title'],
                        'target' => $args['link']['target'] ?? '',
                        'classes' => $args['link']['classes'],
                    ]); ?>
                </div>
            <?php } ?>
        </div>

        <?php if ($args['image_side'] === 'right') { ?>
            <?= $media; ?>
        <?php } ?>
    </div>
</section>
