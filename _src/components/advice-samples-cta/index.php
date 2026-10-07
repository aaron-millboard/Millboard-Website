<?php

/**
 * Advice samples CTA.
 *
 * A full-bleed photograph with the samples offer laid over its lower edge and
 * two buttons: the samples page, and a second route such as finding an
 * installer. Both are plain link fields, so each region points them at its own
 * pages (en-us has no installer finder, for instance).
 */

?>
<section <?= \Granola\Helpers::build_attributes($args['attributes']); ?>>
    <?php if (!empty($args['image'])) : ?>
        <?= \Granola\Component::get('image', $args['image']); ?>
    <?php endif; ?>

    <div class="advice-samples-cta__inner mbh-reveal">
        <div class="advice-samples-cta__copy">
            <?php if (!empty($args['eyebrow'])) : ?>
                <p class="advice-samples-cta__eyebrow"><?= esc_html($args['eyebrow']); ?></p>
            <?php endif; ?>

            <?php if (!empty($args['heading']) || !empty($args['heading_accent'])) : ?>
                <h2 class="advice-samples-cta__heading">
                    <?= esc_html($args['heading']); ?>
                    <?php if (!empty($args['heading_accent'])) : ?>
                        <span class="advice-samples-cta__accent"><?= esc_html($args['heading_accent']); ?></span>
                    <?php endif; ?>
                </h2>
            <?php endif; ?>

            <?php if (!empty($args['body'])) : ?>
                <p class="advice-samples-cta__body"><?= esc_html($args['body']); ?></p>
            <?php endif; ?>
        </div>

        <?php if (!empty($args['primary_link']) || !empty($args['secondary_link'])) : ?>
            <div class="advice-samples-cta__actions">
                <?php foreach (['primary_link' => 'primary', 'secondary_link' => 'secondary'] as $key => $style) : ?>
                    <?php if (!empty($args[$key])) : ?>
                        <a
                            class="advice-samples-cta__button advice-samples-cta__button--<?= esc_attr($style); ?>"
                            href="<?= esc_url($args[$key]['url']); ?>"
                            <?php if (!empty($args[$key]['target'])) : ?>
                                target="<?= esc_attr($args[$key]['target']); ?>" rel="noopener"
                            <?php endif; ?>
                        ><?= esc_html($args[$key]['title']); ?></a>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>
