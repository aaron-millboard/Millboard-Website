<?php

/**
 * Advice article grid.
 *
 * Every guide in the category being viewed, and in the categories below it,
 * as cards. Each card names the topics it belongs to, which the filter bar
 * reads to narrow the grid in place.
 *
 * The heading is for screen readers: on the page the filter bar heads the
 * grid, but without a heading here the cards' h3s would sit under the featured
 * guide's h2, as if they were part of it.
 */

?>
<section <?= \Granola\Helpers::build_attributes($args['attributes']); ?>>
    <div class="advice-article-grid__inner">
        <h2 class="advice-article-grid__heading"><?= esc_html($args['heading']); ?></h2>

        <?php if (!empty($args['cards'])) : ?>
            <ul class="advice-article-grid__list">
                <?php foreach ($args['cards'] as $card) : ?>
                    <li class="advice-article-grid__item" data-advice-topics="<?= esc_attr(implode(' ', $card['topic_ids'])); ?>">
                        <a class="advice-article-grid__card" href="<?= esc_url($card['url']); ?>">
                            <span class="advice-article-grid__frame">
                                <?php if (!empty($card['image'])) : ?>
                                    <?= \Granola\Component::get('image', $card['image']); ?>
                                <?php endif; ?>
                            </span>

                            <span class="advice-article-grid__topic"><?= esc_html($card['topic']); ?></span>
                            <h3 class="advice-article-grid__title"><?= esc_html($card['title']); ?></h3>

                            <?php if (!empty($card['standfirst'])) : ?>
                                <p class="advice-article-grid__standfirst"><?= esc_html($card['standfirst']); ?></p>
                            <?php endif; ?>

                            <span class="advice-article-grid__meta">
                                <?= esc_html($card['read_time']); ?> · <?= esc_html($card['updated']); ?>
                            </span>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else : ?>
            <div class="advice-article-grid__empty">
                <h3 class="advice-article-grid__empty-heading"><?= esc_html($args['empty_heading']); ?></h3>
                <p class="advice-article-grid__empty-body"><?= esc_html($args['empty_body']); ?></p>

                <?php if (!empty($args['hub_url'])) : ?>
                    <a class="advice-article-grid__empty-link" href="<?= esc_url($args['hub_url']); ?>"><?= esc_html($args['hub_label']); ?></a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</section>
