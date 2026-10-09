<?php

/**
 * Home plan your space.
 *
 * A numbered list of ways to get started. Each row is one link across its full
 * width, so the number, the title, the description and the action are all the
 * same target rather than four things to aim at.
 *
 * An ordered list, because these are numbered and the order is the point: a
 * screen reader should say "1 of 4" rather than leaving the numerals to be read
 * as decoration. The visible numerals are hidden from it for the same reason,
 * or it would announce the number twice.
 */

?>
<section <?= \Granola\Helpers::build_attributes($args['attributes']); ?>>
    <div class="home-plan__inner">

        <div class="home-plan__header mbh-reveal">
            <span class="home-plan__rule" aria-hidden="true"></span>

            <?php if (!empty($args['eyebrow'])) : ?>
                <p class="home-plan__eyebrow"><?= esc_html($args['eyebrow']); ?></p>
            <?php endif; ?>

            <?php if (!empty($args['heading'])) : ?>
                <h2 class="home-plan__heading"><?= esc_html($args['heading']); ?></h2>
            <?php endif; ?>
        </div>

        <?php if (!empty($args['steps'])) : ?>
            <ol class="home-plan__list">
                <?php foreach ($args['steps'] as $step) : ?>
                    <?php
                    // The reveal sits on the item, not on the row inside it.
                    //
                    // The row needs a `transition` of its own for its hover
                    // tint, and the transition shorthand replaces rather than
                    // adds -- so on the row it wiped out the reveal's opacity
                    // and transform transitions completely. All four rows
                    // appeared instantly and together, their delays doing
                    // nothing, because there was no longer a transition for a
                    // delay to apply to. On the item the two do not meet.
                    ?>
                    <li
                        class="home-plan__item mbh-reveal"
                        style="--mbh-reveal-delay: <?= (int) $step['delay']; ?>ms"
                    >
                        <a
                            class="home-plan__row"
                            <?php if (!empty($step['link']['url'])) : ?>
                                href="<?= esc_url($step['link']['url']); ?>"
                            <?php endif; ?>
                            <?php if (!empty($step['link']['target'])) : ?>
                                target="<?= esc_attr($step['link']['target']); ?>" rel="noopener"
                            <?php endif; ?>
                        >
                            <span class="home-plan__number" aria-hidden="true"><?= esc_html($step['number']); ?></span>

                            <span class="home-plan__text">
                                <span class="home-plan__title"><?= esc_html($step['title']); ?></span>

                                <?php if (!empty($step['description'])) : ?>
                                    <span class="home-plan__description"><?= esc_html($step['description']); ?></span>
                                <?php endif; ?>
                            </span>

                            <?php if (!empty($step['link']['title'])) : ?>
                                <span class="home-plan__action">
                                    <?= esc_html($step['link']['title']); ?>
                                    <svg
                                        class="home-plan__arrow"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="1.5"
                                        aria-hidden="true"
                                        focusable="false"
                                    ><path d="M4 12h15M14 7l5 5-5 5"></path></svg>
                                </span>
                            <?php endif; ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ol>
        <?php endif; ?>
    </div>
</section>
