<section <?= \Granola\Helpers::build_attributes($args['attributes']); ?>>
    <div class="spec-strip__inner">
        <?php if (!empty($args['has_header'])) { ?>
            <div class="spec-strip__header">
                <?php if (!empty($args['preheading'])) { ?>
                    <div class="spec-strip__preheading is-style-typestyle-h6"><?= wp_kses_post($args['preheading']); ?></div>
                <?php } ?>

                <?php if (!empty($args['heading'])) { ?>
                    <h2 class="spec-strip__heading"><?= wp_kses_post($args['heading']); ?></h2>
                <?php } ?>

                <?php if (!empty($args['intro'])) { ?>
                    <p class="spec-strip__intro"><?= esc_html($args['intro']); ?></p>
                <?php } ?>
            </div>
        <?php } ?>

        <ul class="spec-strip__items" role="list">
            <?php foreach ($args['points'] as $point) { ?>
                <li class="spec-strip__item">
                    <p class="spec-strip__label"><?= esc_html($point['label']); ?></p>

                    <?php if (!empty($point['detail'])) { ?>
                        <p class="spec-strip__detail"><?= esc_html($point['detail']); ?></p>
                    <?php } ?>

                    <?php if (!empty($point['link']['url'])) { ?>
                        <a
                            class="spec-strip__link"
                            href="<?= esc_url($point['link']['url']); ?>"
                            <?php if (!empty($point['link']['target'])) { ?>
                                target="<?= esc_attr($point['link']['target']); ?>" rel="noopener"
                            <?php } ?>
                        ><?= esc_html($point['link']['title'] ?: $point['label']); ?></a>
                    <?php } ?>
                </li>
            <?php } ?>
        </ul>
    </div>
</section>
