<?php

/**
 * Advice featured guide.
 *
 * One guide set apart at the top of a category: its photograph the full width
 * of the content, its title, summary, topic, reading time and last update
 * beneath. The whole panel is one link. The filter bar hides it while a topic
 * is chosen, because it stands for the category as a whole.
 */

?>
<section <?= \Granola\Helpers::build_attributes($args['attributes']); ?>>
    <a class="advice-featured-guide__link" href="<?= esc_url($args['url']); ?>">
        <div class="advice-featured-guide__media">
            <?php if (!empty($args['image'])) : ?>
                <?= \Granola\Component::get('image', $args['image']); ?>
            <?php endif; ?>
        </div>

        <div class="advice-featured-guide__body">
            <p class="advice-featured-guide__eyebrow"><?= esc_html($args['eyebrow']); ?></p>
            <h2 class="advice-featured-guide__heading"><?= esc_html($args['heading']); ?></h2>

            <?php if (!empty($args['standfirst'])) : ?>
                <p class="advice-featured-guide__standfirst"><?= esc_html($args['standfirst']); ?></p>
            <?php endif; ?>

            <div class="advice-featured-guide__meta">
                <span class="advice-featured-guide__topic"><?= esc_html($args['topic']); ?></span>
                <span class="advice-featured-guide__detail"><?= esc_html($args['read_time']); ?></span>
                <span class="advice-featured-guide__detail"><?= esc_html($args['updated']); ?></span>
                <span class="advice-featured-guide__more"><?= esc_html($args['button']); ?></span>
            </div>
        </div>
    </a>
</section>
