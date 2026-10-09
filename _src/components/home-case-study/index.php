<?php

/**
 * Home featured case study.
 *
 * One project photograph at full bleed, with the headline sitting on the floor
 * of the frame. The scrim is a separate element rather than a gradient on the
 * image, so the photograph can be swapped without anyone having to re-tune the
 * overlay to it.
 */

?>
<section <?= \Granola\Helpers::build_attributes($args['attributes']); ?>>
    <div class="home-case-study__stage">

        <?php if (!empty($args['image'])) : ?>
            <?= \Granola\Component::get('image', $args['image']); ?>
        <?php endif; ?>

        <?php
        // Decorative: it exists to hold the type legible over a photograph.
        ?>
        <span class="home-case-study__scrim" aria-hidden="true"></span>

        <div class="home-case-study__inner mbh-reveal">
            <span class="home-case-study__rule" aria-hidden="true"></span>

            <?php if (!empty($args['eyebrow'])) : ?>
                <p class="home-case-study__eyebrow"><?= esc_html($args['eyebrow']); ?></p>
            <?php endif; ?>

            <?php if (!empty($args['heading'])) : ?>
                <h2 class="home-case-study__heading"><?= esc_html($args['heading']); ?></h2>
            <?php endif; ?>

            <?php if (!empty($args['link']) || !empty($args['meta'])) : ?>
                <div class="home-case-study__actions">
                    <?php if (!empty($args['link'])) : ?>
                        <?= \Granola\Component::get('link', [
                            'url' => $args['link']['url'],
                            'content' => $args['link']['title'],
                            'target' => $args['link']['target'] ?? '',
                            'classes' => $args['link']['classes'],
                        ]); ?>
                    <?php endif; ?>

                    <?php if (!empty($args['meta'])) : ?>
                        <p class="home-case-study__meta"><?= esc_html($args['meta']); ?></p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>
