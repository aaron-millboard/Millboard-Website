<?php

/**
 * Home reasons grid.
 *
 * A short headed intro above a grid of reasons, each a line icon, a caps title
 * and a sentence or two. The grid is auto-fit, so the same markup gives five
 * across at 1440, three on a tablet and one on a phone with nothing to
 * configure.
 */

?>
<section <?= \Granola\Helpers::build_attributes($args['attributes']); ?>>
    <div class="home-reasons__inner">

        <div class="home-reasons__header mbh-reveal">
            <span class="home-reasons__rule" aria-hidden="true"></span>

            <?php if (!empty($args['eyebrow'])) { ?>
                <p class="home-reasons__eyebrow"><?= esc_html($args['eyebrow']); ?></p>
            <?php } ?>

            <?php if (!empty($args['heading'])) { ?>
                <h2 class="home-reasons__heading"><?= esc_html($args['heading']); ?></h2>
            <?php } ?>
        </div>

        <?php if (!empty($args['reasons'])) { ?>
            <?php
            // A list, because this is a set of the same kind of thing and a
            // screen reader should be told there are nine before reading them.
            ?>
            <ul class="home-reasons__grid">
                <?php foreach ($args['reasons'] as $reason) { ?>
                    <li
                        class="home-reasons__item mbh-reveal"
                        style="--mbh-reveal-delay: <?= (int) $reason['delay']; ?>ms"
                    >
                        <?php if (!empty($reason['icon_markup'])) { ?>
                            <?php
                            // Decorative: the title beside it is the name, and
                            // an announced icon would only repeat it.
                            ?>
                            <svg
                                class="home-reasons__icon"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="1.4"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                aria-hidden="true"
                                focusable="false"
                            ><?= $reason['icon_markup']; ?></svg>
                        <?php } ?>

                        <?php if (!empty($reason['title'])) { ?>
                            <p class="home-reasons__title"><?= esc_html($reason['title']); ?></p>
                        <?php } ?>

                        <?php if (!empty($reason['text'])) { ?>
                            <p class="home-reasons__text"><?= esc_html($reason['text']); ?></p>
                        <?php } ?>
                    </li>
                <?php } ?>
            </ul>
        <?php } ?>
    </div>
</section>
