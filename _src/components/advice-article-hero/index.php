<?php

/**
 * Advice article hero.
 *
 * The article's opener and its H1: the photograph full bleed under a scrim,
 * the breadcrumbs, the category, the title and the standfirst, and a caption
 * naming the boards in the picture. It replaces the page header on the
 * articles that carry it; the shared page-header block is left as it is.
 *
 * The breadcrumbs are the theme's Yoast trail. Its last crumb, the article's
 * own title, is kept for screen readers but not drawn, because the H1 says
 * the same thing a line below.
 */

?>
<section <?= \Granola\Helpers::build_attributes($args['attributes']); ?>>

    <?php if (!empty($args['image'])) : ?>
        <?= \Granola\Component::get('image', $args['image']); ?>
    <?php endif; ?>

    <?php if (!empty($args['caption'])) : ?>
        <p class="advice-article-hero__caption"><?= esc_html($args['caption']); ?></p>
    <?php endif; ?>

    <div class="advice-article-hero__inner">
        <div class="advice-article-hero__breadcrumbs">
            <?= \Granola\Component::get('breadcrumbs'); ?>
        </div>

        <p class="advice-article-hero__eyebrow"><?= esc_html($args['eyebrow']); ?></p>
        <h1 class="advice-article-hero__heading"><?= esc_html($args['heading']); ?></h1>

        <?php if (!empty($args['standfirst'])) : ?>
            <p class="advice-article-hero__standfirst"><?= esc_html($args['standfirst']); ?></p>
        <?php endif; ?>
    </div>
</section>
