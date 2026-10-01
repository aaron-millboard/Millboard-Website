<?php

/**
 * Advice category slider.
 *
 * A sideways rail of categories. The rail bleeds off the right edge, so the
 * section pads only its left side and the next card is always seen half in
 * view, which is what says "there is more".
 *
 * Every card is a link, so the rail can be tabbed through without the buttons.
 * The buttons and the progress bar are added by the script and stay hidden
 * until it runs, so without it the rail is a plain scrolling list.
 */

?>
<section <?= \Granola\Helpers::build_attributes($args['attributes']); ?>>
    <?php if (!empty($args['heading']) || !empty($args['cards'])) : ?>
        <div class="advice-category-slider__header mbh-reveal">
            <?php if (!empty($args['heading'])) : ?>
                <h2 class="advice-category-slider__heading"><?= esc_html($args['heading']); ?></h2>
            <?php endif; ?>

            <div class="advice-category-slider__controls">
                <?php if (!empty($args['summary'])) : ?>
                    <p class="advice-category-slider__summary"><?= esc_html($args['summary']); ?></p>
                <?php endif; ?>

                <div class="advice-category-slider__buttons" hidden>
                    <button type="button" class="advice-category-slider__button" data-advice-rail="prev" aria-label="<?= esc_attr__('Previous categories', 'granola'); ?>">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true" focusable="false"><path d="M20 12H5M10 7l-5 5 5 5"></path></svg>
                    </button>
                    <button type="button" class="advice-category-slider__button" data-advice-rail="next" aria-label="<?= esc_attr__('Next categories', 'granola'); ?>">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true" focusable="false"><path d="M4 12h15M14 7l5 5-5 5"></path></svg>
                    </button>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <?php if (!empty($args['cards'])) : ?>
        <ul class="advice-category-slider__track">
            <?php foreach ($args['cards'] as $index => $card) : ?>
                <li class="advice-category-slider__item mbh-reveal" style="--mbh-reveal-delay: <?= (int) min($index, 3) * 120; ?>ms">
                    <a class="advice-category-slider__card" href="<?= esc_url($card['url']); ?>">
                        <span class="advice-category-slider__frame">
                            <?php if (!empty($card['image'])) : ?>
                                <?= \Granola\Component::get('image', $card['image']); ?>
                            <?php endif; ?>
                        </span>

                        <h3 class="advice-category-slider__name"><?= esc_html($card['name']); ?></h3>

                        <?php if (!empty($card['description'])) : ?>
                            <p class="advice-category-slider__description"><?= esc_html($card['description']); ?></p>
                        <?php endif; ?>

                        <?php if (!empty($card['topics'])) : ?>
                            <span class="advice-category-slider__topics">
                                <?php foreach ($card['topics'] as $topic) : ?>
                                    <span class="advice-category-slider__topic"><?= esc_html($topic); ?></span>
                                <?php endforeach; ?>
                            </span>
                        <?php endif; ?>

                        <span class="advice-category-slider__more">
                            <?= esc_html(sprintf(
                                // translators: %s: guide count, e.g. "12 guides".
                                _x('%s · Browse', 'Advice category card link', 'granola'),
                                $card['count_label']
                            )); ?>
                        </span>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>

        <div class="advice-category-slider__progress" aria-hidden="true" hidden>
            <span class="advice-category-slider__progress-bar"></span>
        </div>
    <?php endif; ?>
</section>
