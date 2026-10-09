<?php

/**
 * Advice authors.
 *
 * The people whose names are on the guides: a square portrait, the name, the
 * role and what they write about, and how many guides carry their name. On a
 * narrow screen the row scrolls sideways rather than stacking four tall cards.
 */

?>
<section <?= \Granola\Helpers::build_attributes($args['attributes']); ?>>
    <div class="advice-authors__inner">

        <?php if (!empty($args['heading']) || !empty($args['intro'])) : ?>
            <div class="advice-authors__header mbh-reveal">
                <?php if (!empty($args['heading'])) : ?>
                    <h2 class="advice-authors__heading"><?= esc_html($args['heading']); ?></h2>
                <?php endif; ?>

                <?php if (!empty($args['intro'])) : ?>
                    <p class="advice-authors__intro"><?= esc_html($args['intro']); ?></p>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($args['people'])) : ?>
            <ul class="advice-authors__list" tabindex="0" aria-label="<?php echo esc_attr__('Article authors', 'granola'); ?>">
                <?php foreach ($args['people'] as $person) : ?>
                    <li class="advice-authors__person mbh-reveal" style="--mbh-reveal-delay: <?= (int) $person['delay']; ?>ms">
                        <span class="advice-authors__frame">
                            <?php if (!empty($person['portrait'])) : ?>
                                <?= \Granola\Component::get('image', $person['portrait']); ?>
                            <?php endif; ?>
                        </span>

                        <p class="advice-authors__name"><?= esc_html($person['name']); ?></p>

                        <?php if ($person['role'] || $person['expertise']) : ?>
                            <p class="advice-authors__role">
                                <?= esc_html(implode(' · ', array_filter([$person['role'], $person['expertise']]))); ?>
                            </p>
                        <?php endif; ?>

                        <?php if ($person['count']) : ?>
                            <p class="advice-authors__count"><?= esc_html($person['count']); ?></p>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</section>
