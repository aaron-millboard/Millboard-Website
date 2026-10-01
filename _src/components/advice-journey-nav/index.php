<?php

/**
 * Advice journey navigation.
 *
 * The project stages as full-width rows, numbered in order. Each row is one
 * link, so the whole band is the target and a phone user is not left aiming
 * for a word.
 */

?>
<section <?= \Granola\Helpers::build_attributes($args['attributes']); ?>>
    <div class="advice-journey-nav__inner">

        <?php if (!empty($args['heading']) || !empty($args['intro'])) : ?>
            <div class="advice-journey-nav__header mbh-reveal">
                <?php if (!empty($args['heading'])) : ?>
                    <h2 class="advice-journey-nav__heading"><?= esc_html($args['heading']); ?></h2>
                <?php endif; ?>

                <?php if (!empty($args['intro'])) : ?>
                    <p class="advice-journey-nav__intro"><?= esc_html($args['intro']); ?></p>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($args['stages'])) : ?>
            <ol class="advice-journey-nav__stages">
                <?php foreach ($args['stages'] as $stage) : ?>
                    <?php $tag = $stage['link'] ? 'a' : 'div'; ?>
                    <li class="advice-journey-nav__stage mbh-reveal" style="--mbh-reveal-delay: <?= (int) $stage['delay']; ?>ms">
                        <<?= $tag; ?>
                            class="advice-journey-nav__row"
                            <?php if ($stage['link']) : ?>
                                href="<?= esc_url($stage['link']['url']); ?>"
                                <?php if (!empty($stage['link']['target'])) : ?>
                                    target="<?= esc_attr($stage['link']['target']); ?>" rel="noopener"
                                <?php endif; ?>
                            <?php endif; ?>
                        >
                            <span class="advice-journey-nav__number" aria-hidden="true"><?= esc_html($stage['number']); ?></span>
                            <h3 class="advice-journey-nav__title"><?= esc_html($stage['title']); ?></h3>

                            <?php if ($stage['count']) : ?>
                                <span class="advice-journey-nav__count"><?= esc_html($stage['count']); ?></span>
                            <?php endif; ?>

                            <?php if (!empty($stage['description'])) : ?>
                                <p class="advice-journey-nav__description"><?= esc_html($stage['description']); ?></p>
                            <?php endif; ?>
                        </<?= $tag; ?>>
                    </li>
                <?php endforeach; ?>
            </ol>
        <?php endif; ?>
    </div>
</section>
