<?php

/**
 * Advice author panel.
 *
 * Who wrote the article, from their user profile: profile image, name, job
 * title and biographical info, on a Mist band. Edited on the person's profile
 * (Users), so a change shows on every article they have written.
 */

?>
<section <?= \Granola\Helpers::build_attributes($args['attributes']); ?>>
    <div class="advice-author-panel__inner">
        <div class="advice-author-panel__media">
            <div class="advice-author-panel__frame">
                <?php if (!empty($args['portrait'])) : ?>
                    <?= \Granola\Component::get('image', $args['portrait']); ?>
                <?php else : ?>
                    <span class="advice-author-panel__initials" aria-hidden="true"><?= esc_html($args['person']['initials']); ?></span>
                <?php endif; ?>
            </div>
        </div>

        <div class="advice-author-panel__body">
            <p class="advice-author-panel__eyebrow"><?= esc_html__('About the author', 'granola'); ?></p>
            <h2 class="advice-author-panel__name"><?= esc_html($args['person']['name']); ?></h2>

            <?php if (!empty($args['role'])) : ?>
                <p class="advice-author-panel__role"><?= esc_html($args['role']); ?></p>
            <?php endif; ?>

            <?php if (!empty($args['person']['bio'])) : ?>
                <p class="advice-author-panel__bio"><?= esc_html($args['person']['bio']); ?></p>
            <?php endif; ?>

            <?php if (!empty($args['facts'])) : ?>
                <dl class="advice-author-panel__facts">
                    <?php foreach ($args['facts'] as $fact) : ?>
                        <div class="advice-author-panel__fact">
                            <dt class="advice-author-panel__fact-label"><?= esc_html($fact['label']); ?></dt>
                            <dd class="advice-author-panel__fact-value"><?= esc_html($fact['value']); ?></dd>
                        </div>
                    <?php endforeach; ?>
                </dl>
            <?php endif; ?>
        </div>
    </div>
</section>
