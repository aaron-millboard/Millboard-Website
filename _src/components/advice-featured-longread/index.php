<?php

/**
 * Advice featured longread.
 *
 * One pinned guide on a Sustainability Green panel, copy beside its featured
 * photograph. The button carries the reading time, worked out from the
 * article's own text, so it is right the day the article is edited.
 */

?>
<section <?= \Granola\Helpers::build_attributes($args['attributes']); ?>>
    <div class="advice-featured-longread__panel mbh-reveal">
        <div class="advice-featured-longread__body">
            <?php if (!empty($args['eyebrow'])) : ?>
                <p class="advice-featured-longread__eyebrow"><?= esc_html($args['eyebrow']); ?></p>
            <?php endif; ?>

            <h2 class="advice-featured-longread__heading"><?= esc_html($args['heading']); ?></h2>

            <?php if (!empty($args['standfirst'])) : ?>
                <p class="advice-featured-longread__standfirst"><?= esc_html($args['standfirst']); ?></p>
            <?php endif; ?>

            <a class="advice-featured-longread__button" href="<?= esc_url($args['url']); ?>">
                <?= esc_html($args['button']); ?>
                <span class="advice-featured-longread__time">· <?= esc_html($args['read_time']); ?></span>
            </a>
        </div>

        <?php if (!empty($args['image'])) : ?>
            <div class="advice-featured-longread__media">
                <?= \Granola\Component::get('image', $args['image']); ?>
            </div>
        <?php endif; ?>
    </div>
</section>
