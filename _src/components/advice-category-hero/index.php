<?php

/**
 * Advice category hero.
 *
 * The category page's opener and its H1, on Spring Wood: breadcrumbs, the
 * category's name and description, three counted figures, then a photograph
 * the full width of the page beneath. Replaces the default page header on the
 * advice category template only.
 */

?>
<section <?= \Granola\Helpers::build_attributes($args['attributes']); ?>>
    <div class="advice-category-hero__inner">
        <div class="advice-category-hero__breadcrumbs">
            <?= \Granola\Component::get('breadcrumbs'); ?>
        </div>

        <p class="advice-category-hero__eyebrow"><?= esc_html($args['eyebrow']); ?></p>
        <h1 class="advice-category-hero__heading"><?= esc_html($args['heading']); ?></h1>

        <?php if (!empty($args['intro'])) : ?>
            <p class="advice-category-hero__intro"><?= esc_html($args['intro']); ?></p>
        <?php endif; ?>

        <?php if (!empty($args['stats'])) : ?>
            <dl class="advice-category-hero__stats">
                <?php foreach ($args['stats'] as $stat) : ?>
                    <div class="advice-category-hero__stat">
                        <dt class="advice-category-hero__stat-label"><?= esc_html($stat['label']); ?></dt>
                        <dd class="advice-category-hero__stat-value"><?= esc_html($stat['value']); ?></dd>
                    </div>
                <?php endforeach; ?>
            </dl>
        <?php endif; ?>
    </div>

    <?php if (!empty($args['image'])) : ?>
        <div class="advice-category-hero__media">
            <?= \Granola\Component::get('image', $args['image']); ?>
        </div>
    <?php endif; ?>
</section>
