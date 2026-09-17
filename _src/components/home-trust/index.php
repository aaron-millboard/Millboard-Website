<?php

/**
 * Home trust band.
 *
 * The Trustpilot rating on one line, accreditation badges beneath it.
 *
 * The rating itself is Trustpilot's own widget rather than five drawn stars.
 * The handoff draws static ones, but a static five would be a claim that
 * cannot go down: it reads as five out of five whatever the real score is, and
 * it would need editing by hand every time the score moved. The widget says
 * what is true today and is what Trustpilot's own terms ask for.
 */

?>
<section <?= \Granola\Helpers::build_attributes($args['attributes']); ?>>

    <div class="home-trust__inner mbh-reveal">
        <?php if (!empty($args['heading'])) : ?>
            <p class="home-trust__heading"><?= esc_html($args['heading']); ?></p>
        <?php endif; ?>

        <?php if (!empty($args['embed']) || !empty($args['link'])) : ?>
            <div class="home-trust__rating">
                <?php if (!empty($args['embed'])) : ?>
                    <div class="home-trust__widget">
                        <?= wp_kses_post($args['embed']); ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($args['link'])) : ?>
                    <a
                        class="home-trust__link"
                        href="<?= esc_url($args['link']['url']); ?>"
                        <?php if (!empty($args['link']['target'])) : ?>
                            target="<?= esc_attr($args['link']['target']); ?>" rel="noopener"
                        <?php endif; ?>
                    ><?= esc_html($args['link']['title']); ?></a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <?php if (!empty($args['badges'])) : ?>
        <ul class="home-trust__badges mbh-reveal">
            <?php foreach ($args['badges'] as $badge) : ?>
                <li class="home-trust__badge">
                    <?php if (!empty($badge['link']['url'])) : ?>
                        <a
                            class="home-trust__badge-link"
                            href="<?= esc_url($badge['link']['url']); ?>"
                            <?php if (!empty($badge['link']['target'])) : ?>
                                target="<?= esc_attr($badge['link']['target']); ?>" rel="noopener"
                            <?php endif; ?>
                        >
                            <?= \Granola\Component::get('image', $badge['image']); ?>
                        </a>
                    <?php else : ?>
                        <?= \Granola\Component::get('image', $badge['image']); ?>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</section>
