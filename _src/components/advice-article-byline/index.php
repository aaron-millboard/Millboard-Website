<?php

/**
 * Advice article byline.
 *
 * Who wrote the article, who checked it if anyone did, when it went up and
 * when it last changed, and how long it is. The author's name leads to the
 * author panel further down the page.
 */

?>
<section <?= \Granola\Helpers::build_attributes($args['attributes']); ?>>
    <div class="advice-article-byline__inner">
        <?php if (!empty($args['author'])) : ?>
            <div class="advice-article-byline__person">
                <span class="advice-article-byline__avatar" aria-hidden="true">
                    <?php if (!empty($args['author']['portrait'])) : ?>
                        <?= \Granola\Component::get('image', $args['author']['portrait']); ?>
                    <?php else : ?>
                        <?= esc_html($args['author']['initials']); ?>
                    <?php endif; ?>
                </span>

                <div class="advice-article-byline__text">
                    <span class="advice-article-byline__label"><?= esc_html__('Written by', 'granola'); ?></span>
                    <a class="advice-article-byline__name" href="<?= esc_url($args['author_url']); ?>"><?= esc_html($args['author']['name']); ?></a>

                    <?php if (!empty($args['author']['role'])) : ?>
                        <span class="advice-article-byline__detail"><?= esc_html(sprintf(
                            // translators: %s: an author's job title.
                            __('%s, Millboard', 'granola'),
                            $args['author']['role']
                        )); ?></span>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <?php if (!empty($args['reviewer'])) : ?>
            <div class="advice-article-byline__text advice-article-byline__text--reviewer">
                <span class="advice-article-byline__label"><?= esc_html__('Reviewed by', 'granola'); ?></span>
                <span class="advice-article-byline__name">
                    <?= esc_html($args['reviewer']['role'] ? $args['reviewer']['name'] . ', ' . $args['reviewer']['role'] : $args['reviewer']['name']); ?>
                </span>
                <span class="advice-article-byline__detail"><?= esc_html($args['review_line']); ?></span>
            </div>
        <?php endif; ?>

        <div class="advice-article-byline__text advice-article-byline__text--dates">
            <time class="advice-article-byline__detail" datetime="<?= esc_attr($args['published_iso']); ?>"><?= esc_html($args['published']); ?></time>

            <?php if (!empty($args['updated'])) : ?>
                <time class="advice-article-byline__detail" datetime="<?= esc_attr($args['updated_iso']); ?>"><?= esc_html($args['updated']); ?></time>
            <?php endif; ?>

            <span class="advice-article-byline__length"><?= esc_html($args['length']); ?></span>
        </div>
    </div>
</section>
