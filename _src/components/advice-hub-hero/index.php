<?php

/**
 * Advice hub hero.
 *
 * The hub's opener and its H1: a photograph under a scrim, the breadcrumbs,
 * the heading and standfirst, then a search held to advice articles and a row
 * of quick links. It replaces the page header on the hub only, so the shared
 * `page-header` block (on several hundred pages) is left exactly as it is.
 *
 * The breadcrumbs are the theme's own component, the Yoast trail, so the
 * BreadcrumbList Yoast prints and the one on screen cannot disagree.
 */

?>
<section <?= \Granola\Helpers::build_attributes($args['attributes']); ?>>

    <?php if (!empty($args['image']['attachment_id'])) : ?>
        <?= \Granola\Component::get('image', $args['image']); ?>
    <?php endif; ?>

    <div class="advice-hub-hero__inner">
        <div class="advice-hub-hero__breadcrumbs">
            <?= \Granola\Component::get('breadcrumbs'); ?>
        </div>

        <?php if (!empty($args['eyebrow'])) : ?>
            <p class="advice-hub-hero__eyebrow"><?= esc_html($args['eyebrow']); ?></p>
        <?php endif; ?>

        <?php if (!empty($args['heading'])) : ?>
            <h1 class="advice-hub-hero__heading"><?= esc_html($args['heading']); ?></h1>
        <?php endif; ?>

        <?php if (!empty($args['standfirst'])) : ?>
            <p class="advice-hub-hero__standfirst"><?= esc_html($args['standfirst']); ?></p>
        <?php endif; ?>

        <form class="advice-hub-hero__search" role="search" method="get" action="<?= esc_url($args['search']['action']); ?>">
            <label class="advice-hub-hero__search-label" for="<?= esc_attr($args['search']['input_id']); ?>">
                <?= esc_html__('Search the advice centre', 'granola'); ?>
            </label>
            <input
                id="<?= esc_attr($args['search']['input_id']); ?>"
                class="advice-hub-hero__search-input"
                type="search"
                name="s"
                value="<?= esc_attr($args['search']['value']); ?>"
                placeholder="<?= esc_attr($args['search']['placeholder']); ?>"
                autocomplete="off"
                required
            >
            <input type="hidden" name="post_type" value="<?= esc_attr($args['search']['post_type']); ?>">
            <button class="advice-hub-hero__search-submit" type="submit"><?= esc_html__('Search', 'granola'); ?></button>
        </form>

        <?php if (!empty($args['quick_links'])) : ?>
            <nav class="advice-hub-hero__quick" aria-label="<?= esc_attr($args['popular_label'] ?: __('Popular guides', 'granola')); ?>">
                <?php if (!empty($args['popular_label'])) : ?>
                    <p class="advice-hub-hero__quick-label" aria-hidden="true"><?= esc_html($args['popular_label']); ?></p>
                <?php endif; ?>

                <ul class="advice-hub-hero__quick-list">
                    <?php foreach ($args['quick_links'] as $link) : ?>
                        <li class="advice-hub-hero__quick-item">
                            <a
                                class="advice-hub-hero__quick-link"
                                href="<?= esc_url($link['url']); ?>"
                                <?php if (!empty($link['target'])) : ?>
                                    target="<?= esc_attr($link['target']); ?>" rel="noopener"
                                <?php endif; ?>
                            ><?= esc_html($link['title']); ?></a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </nav>
        <?php endif; ?>
    </div>
</section>
